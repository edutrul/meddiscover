<?php

namespace Drupal\med_search;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Deterministic topic enrichment from imported structured data.
 *
 * Uses entity references and structured queries only: no vector search and
 * no language-model calls.
 */
class ResultEnricher {

  /**
   * Related-content sources in priority order (lower runs first).
   */
  public const PRIORITY = [
    'curated' => 1,
    'summary_link' => 2,
    'shared_mesh' => 3,
    'shared_group' => 4,
  ];

  /**
   * Maximum inferred associations appended after explicit relationships.
   */
  public const INFERRED_LIMIT = 5;

  /**
   * Resource category display order, following MedlinePlus topic pages.
   *
   * Categories not listed here follow alphabetically.
   */
  public const CATEGORY_ORDER = [
    'Start Here', 'Diagnosis and Tests', 'Prevention and Risk Factors',
    'Treatments and Therapies', 'Living With', 'Related Issues', 'Specifics',
    'Symptoms', 'Genetics', 'Videos and Tutorials', 'Test Your Knowledge',
    'Health Check Tools', 'Images', 'Learn More', 'Statistics and Research',
    'Clinical Trials', 'Journal Articles', 'Find an Expert', 'Children',
    'Teenagers', 'Women', 'Older Adults', 'Patient Handouts', 'Encyclopedia',
    'NIH MedlinePlus Magazine', 'Reference Desk',
  ];

  public function __construct(private EntityTypeManagerInterface $entities) {}

  /**
   * Enrich one published English health topic.
   *
   * @return array
   *   Keys: also_called, see_reference, groups, mesh, summary_links,
   *   related and resources.
   */
  public function enrich(NodeInterface $topic): array {
    $nodes = $this->entities->getStorage('node');
    $own_source = (string) $topic->field_med_source_id->value;

    $groups = [];
    foreach ($topic->field_med_groups->referencedEntities() as $term) {
      $groups[$term->id()] = [
        'label' => $term->label(),
        'url' => $term->field_med_source_url->uri,
      ];
    }
    $mesh = [];
    foreach ($topic->field_med_mesh->referencedEntities() as $term) {
      $mesh[$term->field_med_source_id->value] = [
        'descriptor' => $term->field_med_descriptor_label->value,
        'qualifier' => $term->field_med_qualifier_label->value ?: NULL,
      ];
    }

    $related = $summary_links = [];
    $ids = $nodes->getQuery()->accessCheck(FALSE)
      ->condition('type', 'med_relationship')
      ->condition('field_med_topic', $topic->id())
      ->condition('field_med_relation_type', ['curated', 'summary_link'], 'IN')
      ->execute();
    $edges = $nodes->loadMultiple($ids);
    uasort($edges, fn($a, $b) => [self::PRIORITY[$a->field_med_relation_type->value], self::ordinal($a)]
      <=> [self::PRIORITY[$b->field_med_relation_type->value], self::ordinal($b)]);
    foreach ($edges as $edge) {
      $type = $edge->field_med_relation_type->value;
      $source_id = (string) $edge->field_med_target_source_id->value;
      $url = $edge->field_med_target_url->uri;
      if ($source_id === $own_source) {
        continue;
      }
      if ($type === 'summary_link' && $source_id === '') {
        // Links to non-topic pages (lab tests, genetics) are further reading,
        // not related topics.
        $summary_links[self::normalizeUrl($url)] ??= [
          'title' => $edge->field_med_target_title->value,
          'url' => $url,
        ];
        continue;
      }
      $target = $edge->field_med_target->entity;
      $this->addRelated($related, $source_id, [
        'title' => $target ? $target->label() : $edge->field_med_target_title->value,
        'node' => $target,
        'url' => $url,
        'source' => $type,
        'provenance' => $type === 'curated' ? 'Related topic listed by MedlinePlus' : 'Linked in the topic summary',
      ]);
    }

    // Inferred associations: other imported topics sharing a MeSH descriptor,
    // then a medical group. Explicit relationships always rank first.
    $inferred = 0;
    $descriptor_terms = $mesh ? $this->entities->getStorage('taxonomy_term')->getQuery()->accessCheck(FALSE)
      ->condition('vid', 'med_mesh')->condition('field_med_source_id', array_keys($mesh), 'IN')->execute() : [];
    foreach ([
      'shared_mesh' => ['field_med_mesh', $descriptor_terms],
      'shared_group' => ['field_med_groups', array_keys($groups)],
    ] as $source => [$field, $term_ids]) {
      if (!$term_ids) {
        continue;
      }
      $candidates = $nodes->getQuery()->accessCheck(FALSE)
        ->condition('type', 'med_health_topic')->condition('status', 1)->condition('langcode', 'en')
        ->condition($field, $term_ids, 'IN')->condition('nid', $topic->id(), '<>')
        ->sort('title')->execute();
      foreach ($nodes->loadMultiple($candidates) as $candidate) {
        $shared = array_filter($candidate->get($field)->referencedEntities(), fn($term) => in_array($term->id(), $term_ids));
        $label = $source === 'shared_mesh'
          ? 'Shares MeSH descriptor: ' . implode(', ', array_unique(array_map(fn($t) => $t->field_med_descriptor_label->value, $shared)))
          : 'Same MedlinePlus group: ' . implode(', ', array_map(fn($t) => $t->label(), $shared));
        $key = (string) $candidate->field_med_source_id->value;
        if (!isset($related[$key]) && $inferred >= self::INFERRED_LIMIT) {
          continue;
        }
        $inferred += isset($related[$key]) ? 0 : 1;
        $this->addRelated($related, $key, [
          'title' => $candidate->label(),
          'node' => $candidate,
          'url' => $candidate->field_med_source_url->uri,
          'source' => $source,
          'provenance' => $label,
        ]);
      }
    }

    return [
      'also_called' => array_column($topic->field_med_also_called->getValue(), 'value'),
      'see_reference' => array_column($topic->field_med_see_reference->getValue(), 'value'),
      'groups' => array_values($groups),
      'mesh' => array_values($mesh),
      'summary_links' => array_values($summary_links),
      'related' => array_values($related),
      'resources' => $this->resources($topic),
    ];
  }

  /**
   * Group external resources by category, preserving source order.
   */
  private function resources(NodeInterface $topic): array {
    $nodes = $this->entities->getStorage('node');
    $ids = $nodes->getQuery()->accessCheck(FALSE)
      ->condition('type', 'med_resource')->condition('field_med_topic', $topic->id())->execute();
    $resources = $nodes->loadMultiple($ids);
    uasort($resources, fn($a, $b) => self::ordinal($a) <=> self::ordinal($b));
    $grouped = [];
    foreach ($resources as $resource) {
      $item = [
        'title' => $resource->label(),
        'url' => $resource->field_med_url->uri,
        'organizations' => array_column($resource->field_med_organizations->getValue(), 'value'),
      ];
      foreach ($resource->field_med_categories->referencedEntities() as $category) {
        // One entry per URL within a category, even if assigned twice.
        $grouped[$category->label()][self::normalizeUrl($item['url'])] ??= $item;
      }
    }
    $rank = array_flip(self::CATEGORY_ORDER);
    uksort($grouped, fn($a, $b) => [$rank[$a] ?? PHP_INT_MAX, $a] <=> [$rank[$b] ?? PHP_INT_MAX, $b]);
    return array_map('array_values', $grouped);
  }

  /**
   * Merge a related item, keeping the highest-priority source first.
   */
  private function addRelated(array &$related, string $key, array $item): void {
    $key = $key !== '' ? $key : self::normalizeUrl($item['url']);
    if (isset($related[$key])) {
      if (!in_array($item['provenance'], $related[$key]['provenance'], TRUE)) {
        $related[$key]['provenance'][] = $item['provenance'];
      }
      return;
    }
    $related[$key] = [
      'title' => $item['title'],
      // Link imported topics locally; out-of-corpus topics go to MedlinePlus.
      'node' => $item['node'] instanceof NodeInterface && $item['node']->isPublished() && $item['node']->access('view') ? $item['node'] : NULL,
      'url' => $item['url'],
      'source' => $item['source'],
      'explicit' => in_array($item['source'], ['curated', 'summary_link'], TRUE),
      'provenance' => [$item['provenance']],
    ];
  }

  /**
   * Source order from the stable migration key (topic:type:ordinal).
   */
  private static function ordinal(NodeInterface $node): int {
    return (int) substr(strrchr((string) $node->field_med_key->value, ':'), 1);
  }

  /**
   * Compare URLs without fragment, trailing slash or scheme/host case.
   */
  public static function normalizeUrl(string $url): string {
    $parts = parse_url($url) ?: [];
    $path = rtrim($parts['path'] ?? '', '/');
    return strtolower(($parts['host'] ?? '')) . $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
  }

}
