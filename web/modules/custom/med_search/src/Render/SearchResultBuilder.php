<?php

namespace Drupal\med_search\Render;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\med_search\ResultEnricher;
use Drupal\med_search\SearchStrategyInterface;

/**
 * Builds the result cards, their enrichment and the topic-group filter.
 *
 * Presentation only: the ranking comes from a SearchStrategyInterface, the
 * extra topic data from ResultEnricher.
 */
final class SearchResultBuilder {

  use StringTranslationTrait;

  public function __construct(
    private ResultEnricher $enricher,
    private AccountInterface $currentUser,
  ) {}

  /**
   * Result cards for a ranking, plus the topic groups they belong to.
   *
   * @param array $results
   *   Strategy results (each with a 'node'), best first.
   * @param \Drupal\med_search\SearchStrategyInterface $strategy
   *   The strategy that ranked them, for score explanations and analytics.
   * @param bool $browsing
   *   TRUE for the initial topic list (no search yet): no enrichment, scores
   *   or click tracking.
   *
   * @return array
   *   A pair: render arrays keyed by result delta, and group facets keyed by
   *   term ID (label and count).
   */
  public function cards(array $results, SearchStrategyInterface $strategy, bool $browsing): array {
    $cards = $facets = [];
    $can_debug = !$browsing && $this->currentUser->hasPermission('view med search ranking details');
    foreach ($results as $delta => $result) {
      $node = $result['node'];
      $groups = [];
      foreach ($node->field_med_groups->referencedEntities() as $term) {
        $groups[$term->id()] = $term;
      }
      foreach ($groups as $id => $group) {
        $facets[$id] ??= ['label' => $group->label(), 'count' => 0];
        $facets[$id]['count']++;
      }
      $enrichment = $browsing ? [] : $this->enricher->enrich($node);
      $cards[$delta] = [
        '#theme' => 'med_search_result',
        // For other modules (for example activation): the topic and its
        // already computed enrichment.
        '#med_topic' => $browsing ? NULL : $node,
        '#med_topic_groups' => array_keys($groups),
        '#med_enrichment' => $enrichment,
        'title' => $browsing ? $node->toLink()->toRenderable() : SearchLinks::track(
          ['#type' => 'link', '#title' => $node->label(), '#url' => $node->toUrl()],
          'search_result_clicked',
          $strategy->id() . ':' . SearchLinks::rankBucket($delta + 1),
        ),
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          'text' => ['#plain_text' => (string) $node->field_med_description->value],
        ],
        // Scores mean nothing to patients and can read like medical
        // confidence; only users who debug or demo ranking see them.
        'score' => $can_debug ? [
          '#type' => 'details',
          '#title' => $this->t('Ranking details'),
          '#attributes' => ['class' => ['med-ranking-details', 'med-debug']],
          'text' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            'text' => ['#plain_text' => $strategy->describeScore($result)],
          ],
        ] : [],
        'source' => $node->field_med_source_url->isEmpty() ? [] : SearchLinks::track(SearchLinks::external([
          '#type' => 'link',
          '#title' => $this->t('Source: MedlinePlus, National Library of Medicine.'),
          '#url' => Url::fromUri($node->field_med_source_url->uri),
        ]), 'external_resource_clicked', 'source'),
      ] + ($browsing ? [] : $this->enrichment($enrichment));
    }
    return [$cards, $facets];
  }

  /**
   * A filter for the topic groups in the returned ranking.
   */
  public function topicFacet(array $facets, int $total): array {
    uasort($facets, fn(array $a, array $b) => strnatcasecmp($a['label'], $b['label']));
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-topic-results'], 'data-med-topic-results' => ''],
      'facet' => [
        '#type' => 'html_tag',
        '#tag' => 'details',
        '#attributes' => ['class' => ['med-topic-facet'], 'hidden' => TRUE, 'open' => TRUE],
        'heading' => ['#type' => 'html_tag', '#tag' => 'summary', '#value' => $this->t('Filter by topic')],
      ],
      'items' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['med-topic-results__items']],
      ],
    ];
    $facets = ['all' => ['label' => $this->t('All topics'), 'count' => $total]] + $facets;
    foreach ($facets as $id => $facet) {
      $build['facet']['topic_' . $id] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => [
          'type' => 'button',
          'data-med-topic-filter' => $id,
          'data-med-topic-label' => $facet['label'],
          'aria-pressed' => $id === 'all' ? 'true' : 'false',
        ],
        '#value' => $this->t('@topic (@count)', ['@topic' => $facet['label'], '@count' => $facet['count']]),
      ];
    }
    return $build;
  }

  /**
   * Enrichment as three collapsible sections.
   */
  private function enrichment(array $data): array {
    $build = [];
    $info = [];
    foreach (['also_called' => $this->t('Also called'), 'see_reference' => $this->t('See also')] as $key => $label) {
      if ($data[$key]) {
        $info[$key] = $this->itemList($label, array_map(fn($v) => ['#plain_text' => $v], $data[$key]));
      }
    }
    if ($data['groups']) {
      $info['groups'] = $this->itemList($this->t('MedlinePlus groups'), array_map(fn($g) => SearchLinks::link($g['label'], $g['url']), $data['groups']));
    }
    if ($data['mesh']) {
      $info['mesh'] = $this->itemList($this->t('MeSH descriptors'), array_map(fn($m) => [
        '#plain_text' => $m['descriptor'] . ($m['qualifier'] ? ' / ' . $m['qualifier'] : ''),
      ], $data['mesh']));
    }
    if ($data['summary_links']) {
      $info['summary_links'] = $this->itemList(
        $this->t('Further reading from the summary'),
        array_map(fn($l) => SearchLinks::track(SearchLinks::link($l['title'], $l['url']), 'external_resource_clicked', 'further_reading'), $data['summary_links']),
      );
    }
    if ($info) {
      $build['info'] = ['#type' => 'details', '#title' => $this->t('Explore Health Information'), '#weight' => 10] + $info;
    }
    if ($data['related']) {
      $items = [];
      foreach ($data['related'] as $item) {
        $items[] = [
          'link' => SearchLinks::track($item['node'] ? [
            '#type' => 'link',
            '#title' => $item['title'],
            '#url' => $item['node']->toUrl(),
          ] : SearchLinks::link($item['title'] . ' (MedlinePlus)', $item['url']), 'related_content_clicked', $item['explicit'] ? 'explicit' : 'inferred'),
          'provenance' => [
            '#type' => 'html_tag',
            '#tag' => 'small',
            '#value' => $this->t('@kind: @why', [
              '@kind' => $item['explicit'] ? $this->t('Explicit') : $this->t('Inferred'),
              '@why' => implode('; ', $item['provenance']),
            ]),
            '#prefix' => ' — ',
          ],
        ];
      }
      $build['related'] = [
        '#type' => 'details',
        '#title' => $this->t('Related Content (@count)', ['@count' => count($items)]),
        '#weight' => 11,
        'list' => ['#theme' => 'item_list', '#items' => $items],
      ];
    }
    if ($data['resources']) {
      $build['resources'] = [
        '#type' => 'details',
        '#title' => $this->t('Explore Healthcare Resources (@count categories)', ['@count' => count($data['resources'])]),
        '#weight' => 12,
      ];
      foreach ($data['resources'] as $category => $resources) {
        $build['resources'][] = $this->itemList($category . ' (' . count($resources) . ')', array_map(fn($r) => [
          'link' => SearchLinks::track(SearchLinks::link($r['title'], $r['url']), 'external_resource_clicked', 'resources'),
          'org' => $r['organizations'] ? [
            '#type' => 'html_tag',
            '#tag' => 'small',
            '#value' => $this->t('— @orgs', ['@orgs' => implode(', ', $r['organizations'])]),
            '#prefix' => ' ',
          ] : [],
        ], $resources));
      }
    }
    return $build;
  }

  /**
   * Titled item list.
   */
  private function itemList(string|\Stringable $title, array $items): array {
    return ['#theme' => 'item_list', '#title' => $title, '#items' => $items];
  }

}
