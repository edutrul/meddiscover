<?php

namespace Drupal\med_search;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Indexes topic inputs and performs exact PostgreSQL cosine search.
 */
final class SemanticSearch {

  public const MED_INPUT_VERSION = 'topic-v1';

  public function __construct(
    private Connection $db,
    private EntityTypeManagerInterface $entities,
    private EmbedderInterface $embedder,
    private ConfigFactoryInterface $configFactory,
    private LockBackendInterface $lock,
    private TimeInterface $time,
    private AccountInterface $currentUser,
  ) {}

  /**
   * Build precisely the same document representation for indexing/freshness.
   */
  public function input(NodeInterface $node): string {
    $text = 'search_document: Title: ' . $node->label()
      . "\nDescription: " . $node->field_med_description->value
      . "\nSummary: " . $node->field_med_summary_plain->value;
    if ($this->configFactory->get('med_search.settings')->get('include_metadata')) {
      $aliases = [];
      foreach (['field_med_also_called', 'field_med_see_reference'] as $field) {
        foreach ($node->get($field) as $item) {
          $aliases[] = $item->value;
        }
      }
      $mesh = [];
      foreach ($node->field_med_mesh->referencedEntities() as $term) {
        $mesh[] = $term->field_med_descriptor_label->value;
      }
      $text .= "\nAlternative terms: " . implode('; ', $aliases) . "\nMeSH: " . implode('; ', $mesh);
    }
    return $text;
  }

  /**
   * Generate missing/changed embeddings; publish each row only after success.
   */
  public function indexTopics(?int $topic_id = NULL): array {
    if (!$this->lock->acquire('med_search_index', 900)) {
      throw new \RuntimeException('A topic embedding import is already running.');
    }
    try {
      $config = $this->configFactory->get('med_search.settings');
      $storage = $this->entities->getStorage('node');
      $query = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'med_health_topic')
        ->condition('langcode', 'en')->condition('status', 1);
      if ($topic_id !== NULL) {
        $query->condition('nid', $topic_id);
      }
      $ids = $query->execute();
      $model_verified = FALSE;
      $counts = ['generated' => 0, 'skipped' => 0];
      foreach ($storage->loadMultiple($ids) as $node) {
        $text = $this->input($node);
        $hash = hash('sha256', $text);
        $args = [
          ':topic' => $node->id(),
          ':digest' => $config->get('model_digest'),
          ':version' => self::MED_INPUT_VERSION,
        ];
        $existing = $this->db->query("SELECT content_hash, dimensions FROM {med_topic_embeddings} WHERE topic_id = :topic AND model_digest = :digest AND input_version = :version AND slot = 'topic'", $args)->fetchAssoc();
        if ($existing && $existing['content_hash'] === $hash && (int) $existing['dimensions'] === (int) $config->get('dimensions')) {
          $counts['skipped']++;
          continue;
        }
        if (!$model_verified) {
          $this->embedder->verifyModel();
          $model_verified = TRUE;
        }
        $vector = $this->embedder->embed($text);
        $args += [
          ':model' => $this->embedder->modelId(),
          ':dimensions' => $config->get('dimensions'),
          ':hash' => $hash,
          ':text' => $text,
          ':vector' => json_encode($vector, JSON_THROW_ON_ERROR),
          ':generated' => $this->time->getCurrentTime(),
        ];
        $this->db->query("INSERT INTO {med_topic_embeddings} (topic_id, slot, model, model_digest, dimensions, input_version, content_hash, input_text, embedding, generated_at)
          VALUES (:topic, 'topic', :model, :digest, :dimensions, :version, :hash, :text, CAST(:vector AS vector), :generated)
          ON CONFLICT (topic_id, model_digest, slot, input_version) DO UPDATE SET model = EXCLUDED.model, dimensions = EXCLUDED.dimensions, content_hash = EXCLUDED.content_hash, input_text = EXCLUDED.input_text, embedding = EXCLUDED.embedding, generated_at = EXCLUDED.generated_at", $args);
        $counts['generated']++;
      }
      return $counts;
    }
    finally {
      $this->lock->release('med_search_index');
    }
  }

  /**
   * Rank by actual cosine distance, rejecting stale or inaccessible topics.
   */
  public function search(string $query, ?float $threshold = NULL, ?int $limit = NULL, bool $includeEmbedding = FALSE): array {
    $query = trim($query);
    if ($query === '' || mb_strlen($query) > 500) {
      throw new \InvalidArgumentException('Enter a query between 1 and 500 characters.');
    }
    $config = $this->configFactory->get('med_search.settings');
    $threshold ??= (float) $config->get('threshold');
    $limit ??= (int) $config->get('result_limit');
    if (!is_finite($threshold) || $threshold < -1 || $threshold > 1 || $limit < 1 || $limit > 20) {
      throw new \InvalidArgumentException('Invalid search threshold or result limit.');
    }
    $start = hrtime(TRUE);
    $vector = $this->embedder->embedQuery($query);
    $embedding_ms = (hrtime(TRUE) - $start) / 1e6;
    $start = hrtime(TRUE);
    $args = [
      ':vector' => json_encode($vector, JSON_THROW_ON_ERROR),
      ':digest' => $config->get('model_digest'),
      ':dimensions' => $config->get('dimensions'),
      ':version' => self::MED_INPUT_VERSION,
    ];
    // Filter freshness/access before limiting the exact ranking, so stale rows
    // cannot hide valid lower-ranked topics.
    $rows = $this->db->query("SELECT topic_id, content_hash, 1 - (embedding <=> CAST(:vector AS vector)) AS similarity
      FROM {med_topic_embeddings} WHERE model_digest = :digest AND dimensions = :dimensions
      AND input_version = :version AND slot = 'topic' ORDER BY similarity DESC, topic_id ASC", $args)->fetchAll();
    $storage = $this->entities->getStorage('node');
    $results = [];
    foreach ($rows as $row) {
      if ((float) $row->similarity < $threshold) {
        continue;
      }
      $node = $storage->load($row->topic_id);
      if (!$node || $node->bundle() !== 'med_health_topic' || !$node->isPublished()
        || $node->language()->getId() !== 'en' || !$node->access('view', $this->currentUser)
        || hash('sha256', $this->input($node)) !== $row->content_hash) {
        continue;
      }
      $results[] = ['node' => $node, 'similarity' => (float) $row->similarity];
      if (count($results) === $limit) {
        break;
      }
    }
    $response = [
      'results' => $results,
      'embedding_ms' => $embedding_ms,
      'retrieval_ms' => (hrtime(TRUE) - $start) / 1e6,
    ];
    if ($includeEmbedding) {
      $response['embedding_debug'] = [
        'model' => $this->embedder->modelId(),
        'input' => $this->embedder->queryInput($query),
        'response' => ['dimensions' => count($vector), 'embedding' => $vector],
      ];
    }
    return $response;
  }

}
