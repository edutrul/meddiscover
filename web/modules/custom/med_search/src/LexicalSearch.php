<?php

namespace Drupal\med_search;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api_solr\SolrBackendInterface;

/**
 * Solr lexical retrieval through the Search API "med_topics" index.
 *
 * Field boosts live in the exported index configuration. This class adds the
 * query-time behavior: EDisMax with a minimum-should-match rule, phrase boosts
 * for exact names, exact-name promotion and ambiguity notices.
 */
final class LexicalSearch implements SearchStrategyInterface {

  use StringTranslationTrait;

  /**
   * Fields searched for exact (phrase) names, with phrase boosts.
   */
  public const MED_PHRASE_FIELDS = [
    'title_unstemmed' => 21,
    'also_called_unstemmed' => 13,
    'see_reference' => 5,
  ];

  public function __construct(
    private EntityTypeManagerInterface $entities,
    private ConfigFactoryInterface $configFactory,
    private ParseModePluginManager $parseModes,
    private AccountInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'lexical';
  }

  /**
   * {@inheritdoc}
   */
  public function search(string $query, ?int $limit = NULL): array {
    $config = $this->configFactory->get('med_search.settings');
    $limit ??= (int) $config->get('result_limit');
    $query = QueryText::validate($query, $limit);

    /** @var \Drupal\search_api\IndexInterface|null $index */
    $index = $this->entities->getStorage('search_api_index')->load($config->get('lexical_index'));
    if (!$index || !$index->status() || !$index->isServerEnabled()) {
      throw new \RuntimeException('The lexical index is unavailable.');
    }
    $backend = $index->getServerInstance()->getBackend();
    if (!$backend instanceof SolrBackendInterface) {
      throw new \RuntimeException('The lexical index is not on a Solr server.');
    }
    $names = $backend->getSolrFieldNamesKeyedByLanguage(['en'], $index);
    $phrase_fields = [];
    foreach (self::MED_PHRASE_FIELDS as $field => $boost) {
      $phrase_fields[] = $names[$field]['en'] . '^' . $boost;
    }

    // Search API escapes the keys; OR plus "mm" lets Solr decide how many
    // terms must match instead of requiring every word of a sentence.
    $parse_mode = $this->parseModes->createInstance('terms');
    $parse_mode->setConjunction('OR');
    $search = $index->query(['limit' => $limit]);
    // Ignore question framing consistently across all queried fields.
    $keys = preg_replace('/^(?:how do i|what (?:is|are)|information about)\s+/iu', '', $query);
    $search->setParseMode($parse_mode)->keys($keys !== '' ? $keys : $query);
    $search->addCondition('status', TRUE);
    $search->setLanguages(['en']);
    $search->setOption('solr_param_defType', 'edismax');
    $search->setOption('solr_param_mm', $keys !== $query ? '4<75%' : (string) $config->get('lexical_min_match'));
    $search->setOption('solr_param_pf', implode(' ', $phrase_fields));

    $capture = $this->currentUser->hasPermission('view med search ranking details');
    $search->setOption('med_solr_debug', $capture);
    $start = hrtime(TRUE);
    try {
      $result_set = $search->execute();
      $items = $result_set->getResultItems();
    }
    catch (\Throwable) {
      // Do not rethrow: backend messages can contain the request.
      throw new \RuntimeException('Lexical search is unavailable.');
    }

    $results = [];
    $wanted = QueryText::normalizeName($query);
    $exact = [];
    foreach ($items as $item) {
      $node = $item->getOriginalObject()?->getValue();
      if (!$node instanceof NodeInterface || $node->bundle() !== 'med_health_topic'
        || !$node->isPublished() || $node->language()->getId() !== 'en' || !$node->access('view', $this->currentUser)) {
        continue;
      }
      $id = (string) ($node->field_med_source_id->value ?: 'local:' . $node->id());
      if (isset($results[$id])) {
        continue;
      }
      $results[$id] = ['node' => $node, 'id' => $id, 'score' => (float) $item->getScore()];
      if (in_array($wanted, self::names($node), TRUE)) {
        $exact[$id] = $node->label();
      }
    }
    // Exact name matches (title, also-called or see reference) first, keeping
    // Solr order inside both groups.
    uksort($results, fn($a, $b) => isset($exact[$b]) <=> isset($exact[$a]));
    $results = array_values($results);
    foreach ($results as $position => &$result) {
      $result['ranks'] = ['lexical' => $position + 1];
    }
    unset($result);

    $notices = [];
    if (count($exact) > 1) {
      $notices[] = $this->t('“@query” is a name used by several topics: @topics. Choose the one that fits.', [
        '@query' => $query,
        '@topics' => implode(', ', $exact),
      ]);
    }
    $response = [
      'results' => $results,
      'timings' => ['lexical_ms' => (hrtime(TRUE) - $start) / 1e6],
      'notices' => $notices,
    ];
    if ($capture && $parameters = $search->getOption('med_solr_debug_params')) {
      $raw = $result_set->getExtraData('search_api_solr_response');
      $response['solr_debug'] = [
        'parameters' => $parameters,
        'solr_matches' => $raw['response']['numFound'] ?? $result_set->getResultCount(),
        'website_results' => array_map(static fn(array $item): array => [
          'title' => $item['node']->label(),
          'score' => $item['score'],
        ], $results),
      ];
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function describeScore(array $result): TranslatableMarkup {
    return $this->t('Solr relevance score: @score (comparable only within this search)', ['@score' => number_format($result['score'], 2)]);
  }

  /**
   * Normalized names a topic is known by.
   */
  public static function names(NodeInterface $node): array {
    $names = [$node->label()];
    foreach (['field_med_also_called', 'field_med_see_reference'] as $field) {
      foreach ($node->get($field) as $item) {
        $names[] = $item->value;
      }
    }
    return array_values(array_unique(array_map([QueryText::class, 'normalizeName'], $names)));
  }

}
