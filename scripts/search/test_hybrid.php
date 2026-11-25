<?php

/**
 * @file
 * RRF, lexical and hybrid contract checks on the synthetic fixture only.
 *
 * Needs the two-topic fixture migration and the DDEV Solr collection
 * (ddev drush solr-upload-conf med_solr --numShards=1). The semantic side is
 * a stub, so no embedding provider is needed.
 */

use Drupal\med_search\HybridSearch;
use Drupal\med_search\SearchStrategyInterface;
use Drupal\node\Entity\Node;
use Drupal\search_api\Entity\Index;

/**
 * Fail the run with a clear message.
 */
function hybrid_expect(bool $value, string $message): void {
  if (!$value) {
    throw new RuntimeException($message);
  }
}

/**
 * Assert that a callable throws the given exception class.
 */
function hybrid_throws(string $class, callable $callable, string $message): void {
  try {
    $callable();
  }
  catch (Throwable $e) {
    hybrid_expect($e instanceof $class, $message . ' (got ' . get_class($e) . ')');
    return;
  }
  throw new RuntimeException($message . ' (nothing thrown)');
}

/**
 * Canonical IDs of a strategy response.
 */
function hybrid_ids(array $response): array {
  return array_column($response['results'], 'id');
}

/**
 * Reindex the fixture and wait for Solr's soft commit (commitWithin 1 s).
 */
function hybrid_reindex(Index $index, int $expected): void {
  $index->reindex();
  $index->indexItems();
  $deadline = microtime(TRUE) + 15;
  do {
    $count = $index->query(['limit' => 0])->execute()->getResultCount();
    if ($count === $expected) {
      return;
    }
    usleep(250000);
  } while (microtime(TRUE) < $deadline);
  throw new RuntimeException("Solr holds $count documents, expected $expected.");
}

// 1. Reciprocal Rank Fusion, pure function.
$fused = HybridSearch::fuse(['lexical' => ['a', 'b', 'c'], 'semantic' => ['c', 'a']], 60);
hybrid_expect(array_column($fused, 'id') === ['a', 'c', 'b'], 'RRF order must follow summed reciprocal ranks.');
hybrid_expect(abs($fused[0]['score'] - (1 / 61 + 1 / 62)) < 1e-12 && abs($fused[1]['score'] - (1 / 63 + 1 / 61)) < 1e-12 && abs($fused[2]['score'] - 1 / 62) < 1e-12, 'RRF scores must equal sum(1 / (k + rank)).');
hybrid_expect($fused[0]['ranks'] === ['lexical' => 1, 'semantic' => 2] && $fused[2]['ranks'] === ['lexical' => 2], 'RRF must report per-strategy ranks.');
hybrid_expect(array_column(HybridSearch::fuse(['lexical' => ['a', 'a', 'b']], 60), 'ranks') === [['lexical' => 1], ['lexical' => 2]], 'Repeated IDs in one list keep their first rank.');
hybrid_expect(array_column(HybridSearch::fuse(['lexical' => ['x'], 'semantic' => ['y']], 60), 'id') === ['x', 'y'], 'Equal scores fall back to strategy priority.');
hybrid_expect(array_column(HybridSearch::fuse(['semantic' => ['y'], 'lexical' => ['x']], 60), 'id') === ['y', 'x'], 'Strategy priority follows input order.');
hybrid_expect(array_column(HybridSearch::fuse(['lexical' => ['a', 'b'], 'semantic' => ['c', 'b']], 0), 'id') === ['b', 'a', 'c'], 'With k = 0, two second places (0.5 + 0.5) tie one first place and win by list count.');
hybrid_expect(HybridSearch::fuse(['lexical' => [], 'semantic' => []], 60) === [], 'Empty rankings fuse to nothing.');
hybrid_expect(array_column(HybridSearch::fuse(['lexical' => [157, '157']], 60), 'id') === ['157'], 'IDs are canonical strings.');
hybrid_throws(InvalidArgumentException::class, fn() => HybridSearch::fuse([], -1), 'Negative k must be rejected.');

// 2. Lexical retrieval through Search API Solr.
$storage = \Drupal::entityTypeManager()->getStorage('node');
$topics = $storage->loadByProperties(['type' => 'med_health_topic']);
hybrid_expect(count($topics) === 2, 'Tests require the two-topic synthetic migration database.');
$by_source = [];
foreach ($topics as $topic) {
  hybrid_expect(str_starts_with($topic->field_med_source_id->value, 'test-'), 'Refusing production corpus.');
  $by_source[$topic->field_med_source_id->value] = $topic;
}
$index = Index::load('med_topics');
hybrid_expect($index && $index->isServerEnabled() && $index->getServerInstance()->isAvailable(), 'Solr collection unavailable; run solr-upload-conf first.');
$lexical = \Drupal::service('med_search.strategy.lexical');
$config = \Drupal::configFactory();
$created = [];

try {
  hybrid_reindex($index, 2);

  $response = $lexical->search('Synthetic Topic One');
  hybrid_expect(hybrid_ids($response) === ['test-1', 'test-2'], 'Title match must rank first; partial matches follow: ' . implode(', ', hybrid_ids($response)));
  $first = $response['results'][0];
  hybrid_expect(array_keys($first) === ['node', 'id', 'score', 'ranks'] && $first['ranks'] === ['lexical' => 1] && $first['node']->id() === $by_source['test-1']->id(), 'Lexical results must use the shared result structure.');
  hybrid_expect($response['results'][1]['ranks'] === ['lexical' => 2] && isset($response['timings']['lexical_ms']) && $response['notices'] === [], 'Ranks, timings and notices must be reported.');
  hybrid_expect(hybrid_ids($lexical->search('fixture alias')) === ['test-1'], 'Alternative terms must match case-insensitively.');
  hybrid_expect(hybrid_ids($lexical->search('Synthetic Topic', 1)) !== [] && count($lexical->search('Synthetic Topic', 1)['results']) === 1, 'The limit must be applied.');
  hybrid_expect(hybrid_ids($lexical->search('bicycle telescope galaxies')) === [], 'Unrelated terms must return no topics.');
  hybrid_expect(hybrid_ids($lexical->search('synthetic bicycle telescope galaxies')) === [], 'Minimum-should-match must reject one stray shared word.');
  foreach (['knee" OR (', 'title:', 'AND', '\\', '{!lucene}'] as $raw) {
    // Must not raise a Solr syntax error.
    $lexical->search($raw);
  }
  hybrid_expect(hybrid_ids($lexical->search('*:*')) === [], 'Query syntax must be escaped: *:* must not match everything.');
  hybrid_throws(InvalidArgumentException::class, fn() => $lexical->search('   '), 'Empty queries must be rejected.');
  hybrid_throws(InvalidArgumentException::class, fn() => $lexical->search('fixture', 21), 'Oversized limits must be rejected.');

  // Exact names are promoted and shared names produce an ambiguity notice.
  $created[] = $shared = Node::create([
    'type' => 'med_health_topic',
    'title' => 'Synthetic Lexical Shared',
    'langcode' => 'en',
    'status' => 1,
    'moderation_state' => 'published',
    'field_med_source_id' => 'test-lexical-shared',
    'field_med_source_url' => 'https://medlineplus.gov/test-lexical-shared.html',
    'field_med_description' => 'Ambiguous fixture fixture fixture text repeated to raise term frequency.',
    'field_med_see_reference' => [['value' => 'Ambiguous Fixture']],
  ]);
  $shared->save();
  $created[] = $hidden = Node::create([
    'type' => 'med_health_topic',
    'title' => 'Synthetic Unpublished Zyxwvut',
    'langcode' => 'en',
    'status' => 0,
    'moderation_state' => 'draft',
    'field_med_source_id' => 'test-lexical-hidden',
    'field_med_source_url' => 'https://medlineplus.gov/test-lexical-hidden.html',
  ]);
  $hidden->save();
  // Title boosts make Solr rank this partial name first; exact names must
  // still be promoted above it.
  $created[] = $partial = Node::create([
    'type' => 'med_health_topic',
    'title' => 'Ambiguous Fixture Overview',
    'langcode' => 'en',
    'status' => 1,
    'moderation_state' => 'published',
    'field_med_source_id' => 'test-lexical-partial',
    'field_med_source_url' => 'https://medlineplus.gov/test-lexical-partial.html',
  ]);
  $partial->save();
  hybrid_reindex($index, 4);
  $response = $lexical->search('Ambiguous Fixture');
  $ids = hybrid_ids($response);
  hybrid_expect(count($ids) === 3 && $ids[2] === 'test-lexical-partial', 'Exact see-reference names must rank above a partial title match: ' . implode(', ', $ids));
  hybrid_expect(count($response['notices']) === 1 && str_contains($response['notices'][0], 'Synthetic Topic One') && str_contains($response['notices'][0], 'Synthetic Lexical Shared'), 'A name shared by several topics must produce an ambiguity notice.');
  $response = $lexical->search('fixture alias');
  hybrid_expect(hybrid_ids($response)[0] === 'test-1', 'Exact alternative-term matches rank first.');
  hybrid_expect(hybrid_ids($lexical->search('Zyxwvut')) === [], 'Unpublished topics must not be returned.');

  // 3. Hybrid fusion with a stub semantic strategy.
  $stub = new class() implements SearchStrategyInterface {

    /**
     * Results to return, or NULL to simulate an unavailable provider.
     */
    public ?array $results = [];

    /**
     * {@inheritdoc}
     */
    public function id(): string {
      return 'semantic';
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, ?int $limit = NULL): array {
      if ($this->results === NULL) {
        throw new RuntimeException('Provider unavailable (synthetic).');
      }
      return ['results' => array_slice($this->results, 0, $limit), 'timings' => ['semantic_ms' => 0.1], 'notices' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function describeScore(array $result): string {
      return '';
    }

  };
  $row = fn(string $source, int $rank) => ['node' => $by_source[$source], 'id' => $source, 'score' => 1 - $rank / 10, 'ranks' => ['semantic' => $rank]];
  $stub->results = [$row('test-2', 1), $row('test-1', 2)];
  $hybrid = new HybridSearch(['lexical' => $lexical, 'semantic' => $stub], $config);
  $response = $hybrid->search('Synthetic Topic One');
  hybrid_expect(hybrid_ids($response) === ['test-1', 'test-2'], 'Equal RRF scores must prefer the lexical first place: ' . implode(', ', hybrid_ids($response)));
  hybrid_expect($response['results'][0]['ranks'] === ['lexical' => 1, 'semantic' => 2] && $response['results'][0]['node']->id() === $by_source['test-1']->id(), 'Hybrid results must carry both ranks and the topic node.');
  hybrid_expect(abs($response['results'][0]['score'] - (1 / 61 + 1 / 62)) < 1e-12, 'Hybrid must use the configured k = 60.');
  hybrid_expect(isset($response['timings']['lexical_ms'], $response['timings']['semantic_ms'], $response['timings']['fusion_ms']), 'Hybrid must report timings for each stage.');

  $stub->results = [$row('test-2', 1), $row('test-2', 2), $row('test-1', 3)];
  $ids = hybrid_ids($hybrid->search('Synthetic Topic One', 5));
  hybrid_expect(count($ids) === count(array_unique($ids)) && count($ids) === 2, 'Hybrid results must be deduplicated by canonical topic ID.');
  $stub->results = [$row('test-2', 1)];
  hybrid_expect(hybrid_ids($hybrid->search('bicycle telescope galaxies')) === ['test-2'], 'Semantic-only matches must survive fusion.');
  hybrid_expect(count($hybrid->search('Synthetic Topic One', 1)['results']) === 1, 'The hybrid limit must be applied after fusion.');
  $stub->results = [];
  hybrid_expect(hybrid_ids($hybrid->search('bicycle telescope galaxies')) === [], 'Hybrid must stay empty when no retriever accepts a result.');

  $stub->results = NULL;
  $response = $hybrid->search('Synthetic Topic One');
  hybrid_expect(hybrid_ids($response) === ['test-1', 'test-2'] && str_contains(implode(' ', $response['notices']), 'Semantic retrieval is unavailable'), 'A failed retriever must degrade to the other with a notice.');
  $broken = new HybridSearch(['semantic' => $stub], $config);
  hybrid_throws(RuntimeException::class, fn() => $broken->search('Synthetic'), 'All retrievers failing must raise an error.');
  hybrid_throws(InvalidArgumentException::class, fn() => $hybrid->search(str_repeat('a', 501)), 'Invalid queries must not be swallowed by degradation.');

  echo "PASS: RRF arithmetic and tie-breaks, lexical title/alias/exact matching, ambiguity notices, minimum-should-match, escaping, unpublished exclusion, hybrid fusion, deduplication, limits and degradation.\n";
}
finally {
  foreach ($created as $node) {
    $node->delete();
  }
  hybrid_reindex($index, 2);
}
