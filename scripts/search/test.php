<?php

/**
 * @file
 * Exact vector/service contract checks on synthetic CI data, never production.
 */

use Drupal\med_search\AiEmbedder;
use Drupal\med_search\EmbedderInterface;
use Drupal\med_search\SemanticSearch;

function search_expect(bool $value, string $message): void {
  if (!$value) {
    throw new RuntimeException($message);
  }
}
$db = \Drupal::database();
$storage = \Drupal::entityTypeManager()->getStorage('node');
$ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'med_health_topic')->execute();
$nodes = array_values($storage->loadMultiple($ids));
search_expect(count($nodes) === 2, 'Tests require the two-topic synthetic migration database.');
foreach ($nodes as $node) {
  search_expect(str_starts_with($node->field_med_source_id->value, 'test-'), 'Refusing production corpus.');
}
search_expect((int) $db->query('SELECT COUNT(*) FROM {med_topic_embeddings}')->fetchField() === 0, 'Tests require empty embedding storage.');
$config = \Drupal::configFactory()->getEditable('med_search.settings');
$original = $config->getRawData();
$config->set('dimensions', 3)->set('model_digest', str_repeat('f', 64))->save();

/**
 * Synthetic embedder: queued vectors, or a provider failure.
 *
 * The real AiEmbedder goes through the Drupal AI provider; here the contract
 * SemanticSearch depends on is exercised without a model.
 */
final class FixtureEmbedder implements EmbedderInterface {

  public function __construct(private array $vectors = [], private ?string $failure = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function modelId(): string {
    return 'fixture-model';
  }

  /**
   * {@inheritdoc}
   */
  public function verifyModel(): void {
    if ($this->failure === 'model_changed') {
      throw new RuntimeException('The pinned embedding model is unavailable or has changed.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function queryInput(string $query): string {
    return 'search_query: ' . $query;
  }

  /**
   * {@inheritdoc}
   */
  public function embedQuery(string $query): array {
    $this->verifyModel();
    return $this->embed($this->queryInput($query));
  }

  /**
   * {@inheritdoc}
   */
  public function embed(string $text): array {
    if ($this->failure !== NULL || !$this->vectors) {
      throw new RuntimeException('Embedding generation failed; please try again later.');
    }
    $vector = array_shift($this->vectors);
    AiEmbedder::validateVector($vector, 3);
    return $vector;
  }

}

function fixture_embedder(array $vectors = [], ?string $failure = NULL): FixtureEmbedder {
  return new FixtureEmbedder($vectors, $failure);
}
function fixture_search(EmbedderInterface $embedder): SemanticSearch {
  return new SemanticSearch(\Drupal::database(), \Drupal::entityTypeManager(), $embedder,
    \Drupal::configFactory(), \Drupal::lock(), \Drupal::time(), \Drupal::currentUser());
}
try {
  foreach ([[0, 0, 0], [1, 0], [NAN, 0, 0], ['bad', 0, 1]] as $bad) {
    $rejected = FALSE;
    try {
      AiEmbedder::validateVector($bad, 3);
    }
    catch (RuntimeException) {
      $rejected = TRUE;
    }
    search_expect($rejected, 'Invalid vector accepted.');
  }
  $indexed = fixture_search(fixture_embedder([[1, 0, 0], [0, 1, 0]]))->indexTopics();
  search_expect($indexed === ['generated' => 2, 'skipped' => 0], 'Indexing did not generate two rows.');
  $snapshot = $db->query('SELECT * FROM {med_topic_embeddings} ORDER BY id')->fetchAllAssoc('id');
  $repeated = fixture_search(fixture_embedder())->indexTopics();
  search_expect($repeated === ['generated' => 0, 'skipped' => 2], 'Repeat indexing called the provider unnecessarily.');
  search_expect($snapshot == $db->query('SELECT * FROM {med_topic_embeddings} ORDER BY id')->fetchAllAssoc('id'), 'Repeat indexing changed metadata.');
  $result = fixture_search(fixture_embedder([[1, 0, 0]]))->search('synthetic exact match');
  search_expect(count($result['results']) === 1 && abs($result['results'][0]['similarity'] - 1.0) < 1e-6, 'Actual cosine score/threshold incorrect.');
  $all = fixture_search(fixture_embedder([[1, 0, 0]]))->search('synthetic ranking', -1.0, 2);
  search_expect(count($all['results']) === 2 && abs($all['results'][1]['similarity']) < 1e-6, 'Exact ranking or limit incorrect.');
  $empty = fixture_search(fixture_embedder([[0, 0, 1]]))->search('unrelated synthetic query');
  search_expect($empty['results'] === [], 'Empty-result threshold failed.');
  $node = $result['results'][0]['node'];
  $description = $node->field_med_description->value;
  $node->field_med_description = 'Changed synthetic source description';
  $node->save();
  $stale = fixture_search(fixture_embedder([[1, 0, 0]]))->search('synthetic stale query');
  search_expect($stale['results'] === [], 'Stale embeddings must be excluded.');
  $updated = fixture_search(fixture_embedder([[0, 0, 1]]))->indexTopics();
  search_expect($updated === ['generated' => 1, 'skipped' => 1], 'Only changed content should regenerate.');
  search_expect((int) $db->query('SELECT COUNT(*) FROM {med_topic_embeddings}')->fetchField() === 2, 'Regeneration duplicated rows.');
  $node->field_med_description = $description;
  $node->save();
  fixture_search(fixture_embedder([[1, 0, 0]]))->indexTopics();
  foreach (['unavailable', 'model_changed', [[0, 0, 0]]] as $failure) {
    $rejected = FALSE;
    try {
      $embedder = is_array($failure) ? fixture_embedder($failure) : fixture_embedder([], $failure);
      fixture_search($embedder)->search('synthetic provider failure');
    }
    catch (RuntimeException $error) {
      $rejected = TRUE;
      search_expect(!str_contains($error->getMessage(), 'synthetic provider failure'), 'Query leaked into error.');
    }
    search_expect($rejected, 'Provider failure/model mismatch accepted.');
  }
  // Real indexes must contain no approximate vector access method.
  $indexes = $db->query("SELECT indexdef FROM pg_indexes WHERE tablename = 'med_topic_embeddings'")->fetchCol();
  foreach ($indexes as $index) {
    search_expect(!preg_match('/hnsw|ivfflat/i', $index), 'Approximate index exists.');
  }
  echo "PASS: vectors, exact cosine ranking, thresholds/limits, freshness, regeneration, stable identity, provider failures and no approximate indexes.\n";
}
finally {
  $config->setData($original)->save();
}
