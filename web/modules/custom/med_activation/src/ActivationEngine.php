<?php

namespace Drupal\med_activation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\med_activation\Entity\ActivationRule;
use Drupal\med_search\QueryText;
use Drupal\med_search\ResultEnricher;
use Drupal\node\NodeInterface;

/**
 * Deterministic, explainable calls to action for one health topic.
 *
 * Inputs are imported data only: the topic's resources grouped by category,
 * its explicit MedlinePlus relationships, the enabled rules and the current
 * search text (used while building the page, never stored). The output is
 * navigation to educational resources, not a clinical recommendation.
 */
class ActivationEngine {

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private ConfigFactoryInterface $configFactory,
    private ResultEnricher $enricher,
  ) {}

  /**
   * Calls to action for a topic, best first.
   *
   * @param \Drupal\node\NodeInterface $topic
   *   A health topic shown in search results.
   * @param string $query
   *   The search text, for keyword context only.
   * @param array|null $enrichment
   *   Output of ResultEnricher::enrich() when already computed.
   *
   * @return array
   *   Items with type (resource|topic), rule, label, url, node (topics),
   *   title, organizations, category, count, keyword and reason.
   */
  public function recommend(NodeInterface $topic, string $query = '', ?array $enrichment = NULL): array {
    $settings = $this->configFactory->get('med_activation.settings');
    $resources = $enrichment['resources'] ?? $this->enricher->resources($topic);
    $search = ' ' . QueryText::normalizeName($query) . ' ';

    $items = [];
    foreach ($this->rules() as $position => $rule) {
      $candidates = $resources[$rule->getCategory()] ?? [];
      if ($rule->requiresOrganization()) {
        $candidates = array_values(array_filter($candidates, fn($resource) => $resource['organizations'] !== []));
      }
      if (!$candidates) {
        continue;
      }
      $keyword = NULL;
      foreach ($rule->getKeywords() as $candidate) {
        $normalized = QueryText::normalizeName($candidate);
        if ($normalized !== '' && str_contains($search, ' ' . $normalized . ' ')) {
          $keyword = $candidate;
          break;
        }
      }
      $first = $candidates[0];
      $count = count($candidates);
      $listed = sprintf('MedlinePlus lists %d “%s” %s for %s.', $count, $rule->getCategory(), $count === 1 ? 'resource' : 'resources', $topic->label());
      $items[] = [
        'type' => 'resource',
        'rule' => $rule->id(),
        'label' => $rule->getCtaLabel(),
        'url' => $first['url'],
        'node' => NULL,
        'title' => $first['title'],
        'organizations' => $first['organizations'],
        'category' => $rule->getCategory(),
        'count' => $count,
        'keyword' => $keyword,
        'reason' => $keyword !== NULL ? sprintf('Your search mentions “%s”. %s', $keyword, $listed) : $listed,
        'position' => $position,
      ];
    }
    // Context-matched rules first; otherwise the configured priority.
    usort($items, fn($a, $b) => [$a['keyword'] === NULL, $a['position']] <=> [$b['keyword'] === NULL, $b['position']]);
    $items = array_slice($items, 0, (int) $settings->get('max_ctas'));

    if ($settings->get('include_related_topic')) {
      $related = $enrichment['related'] ?? $this->enricher->enrich($topic)['related'];
      foreach ($related as $candidate) {
        if (!$candidate['explicit']) {
          continue;
        }
        $items[] = [
          'type' => 'topic',
          'rule' => 'related_topic',
          'label' => 'Explore: ' . $candidate['title'],
          'url' => $candidate['url'],
          'node' => $candidate['node'],
          'title' => $candidate['title'],
          'organizations' => [],
          'category' => NULL,
          'count' => 1,
          'keyword' => NULL,
          'reason' => sprintf('Related to %s. %s.', $topic->label(), implode('; ', $candidate['provenance'])),
          'position' => PHP_INT_MAX,
        ];
        break;
      }
    }
    foreach ($items as &$item) {
      unset($item['position']);
    }
    return $items;
  }

  /**
   * Enabled rules in display priority.
   *
   * @return \Drupal\med_activation\ActivationRuleInterface[]
   *   Rules.
   */
  public function rules(): array {
    $rules = $this->entityTypeManager->getStorage('med_activation_rule')->loadByProperties(['status' => TRUE]);
    uasort($rules, [ActivationRule::class, 'sort']);
    return array_values($rules);
  }

}
