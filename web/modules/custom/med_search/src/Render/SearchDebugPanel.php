<?php

namespace Drupal\med_search\Render;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\med_search\EmbedderInterface;
use Drupal\med_search\GeneratorInterface;
use Drupal\med_search\SearchStrategyInterface;
use Drupal\med_search\SearchStrategyRegistry;
use Drupal\med_search\StrategyBrand;

/**
 * Developer mode panel: strategy, stage timings, models and answer status.
 *
 * Callers check the "view med search ranking details" permission.
 */
final class SearchDebugPanel {

  use StringTranslationTrait;

  public function __construct(
    private SearchStrategyRegistry $strategies,
    private ConfigFactoryInterface $configFactory,
    private StrategyBrand $brand,
    private EmbedderInterface $embedder,
    private GeneratorInterface $generator,
  ) {}

  /**
   * Builds the panel for one search.
   */
  public function build(array $response, float $search_ms, float $enrichment_ms, SearchStrategyInterface $strategy): array {
    $t = $response['timings'];
    $row = static fn ($label, $value, $help): array => [['data' => MetricHelp::build($label, $help)], $value];
    $open = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => ['type' => 'button', 'data-med-strategy-open' => TRUE, 'aria-haspopup' => 'dialog'],
      '#value' => $this->t('Strategy'),
    ];
    $active = [
      '#type' => 'container',
      'active' => ['#plain_text' => (string) $strategy->label()],
      'dialog' => $this->strategyDialog($strategy),
    ];
    $rows = [[['data' => $open], ['data' => $active]]];
    $config = $this->configFactory->get('med_search.settings');
    foreach ($this->stages() as $key => [$label, $help]) {
      if (!isset($t[$key])) {
        continue;
      }
      $rows[] = $row($label, number_format($t[$key], 1) . ' ms', $help);
      if (in_array($key, ['embedding_ms', 'generation_ms'], TRUE)) {
        $embedding = $key === 'embedding_ms';
        $model = $embedding ? $this->embedder->modelId() : $this->generator->modelId();
        $family = str_starts_with($model, 'meddiscover-qwen:') ? 'qwen2.5' : explode(':', $model, 2)[0];
        $library = in_array($family, ['nomic-embed-text', 'gemma3', 'qwen2.5'], TRUE) ? '/' . $family : '';
        $rows[] = $row(
          $embedding ? $this->t('Embedding model') : $this->t('Answer model'),
          [
            'data' => [
              '#type' => 'link',
              '#title' => $this->t('Ollama · @model', ['@model' => $model]),
              '#url' => Url::fromUri('https://ollama.com/library' . $library),
            ],
          ],
          $embedding ? $this->t('Configured model for query embeddings. The link opens its Ollama library page.') : $this->t('Configured model for answer generation. The link opens the Ollama library for its model family.'),
        );
      }
    }
    $rows[] = $row($this->t('Enrichment (all results)'), number_format($enrichment_ms, 1) . ' ms', $this->t('Time to look up and attach details to the results after ranking, including related topics, terminology, and resource links. It covers all returned topics.'));
    $rows[] = $row($this->t('Total server time'), number_format($search_ms + $enrichment_ms, 1) . ' ms', $this->t('Search and enrichment time measured on the server. Browser and network time are separate.'));
    $rows[] = $row($this->t('Results shown'), count($response['results']), $this->t('Number of topics returned by the search before any display filter.'));
    if (isset($response['answer'])) {
      $status = $response['answer']['status'];
      if (isset($response['answer']['reason'])) {
        $status .= ' (' . $response['answer']['reason'] . ')';
      }
      $rows[] = $row($this->t('Answer status'), $status, $this->t('Whether a cited answer was produced, skipped for insufficient evidence, withheld, or unavailable.'));
    }
    return [
      '#type' => 'details',
      '#title' => $this->t('Developer details for this search'),
      '#open' => TRUE,
      '#weight' => -50,
      '#attributes' => ['class' => ['med-debug', 'med-debug-panel']],
      'table' => ['#type' => 'table', '#rows' => $rows],
    ];
  }

  /**
   * Dialog: when each registered strategy is useful.
   */
  private function strategyDialog(SearchStrategyInterface $current): array {
    $guidance = [
      'lexical' => $this->t('Use this when you know the term or title. Search for “HbA1c” and Solr looks for that wording in topic titles, alternate names, and other indexed fields.'),
      'semantic' => $this->t('Use this when you can describe what you mean but may not know the official topic name. For “knee pain when walking,” pgvector can bring up “Walking Problems” even though the phrases differ.'),
      'hybrid' => $this->t('Use this for a broad search. medDiscover checks both the words you typed and what they mean. If a knee topic ranks well in both lists, it can move above a topic found by only one approach.'),
      'conversational' => $this->t('Use this when you want a short answer to a question. Try “What is HbA1c?” medDiscover looks up relevant topics, then asks Ollama to write an answer using those sources. Open the citations to check the original information.'),
    ];
    $title_id = Html::getUniqueId('med-strategy-dialog-title');
    $dialog = [
      '#type' => 'html_tag',
      '#tag' => 'dialog',
      '#attributes' => ['class' => ['med-strategy-dialog'], 'aria-labelledby' => $title_id],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#attributes' => ['id' => $title_id],
        '#value' => $this->t('How did medDiscover find these results?'),
      ],
      'current' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('This search used @strategy. Here’s when each approach is useful.', ['@strategy' => $current->label()]),
      ],
    ];
    // Every registered strategy, so a new one appears without editing this.
    foreach ($this->strategies->ids() as $id) {
      $strategy = $this->strategies->get($id);
      $dialog[$id] = [
        '#type' => 'html_tag',
        '#tag' => 'section',
        'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $strategy->label()],
        'brand' => $this->brand->build($id),
        'body' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => $guidance[$id] ?? $strategy->description()],
      ];
    }
    $dialog['close'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => ['type' => 'button', 'data-med-strategy-close' => TRUE],
      '#value' => $this->t('Close'),
    ];
    return $dialog;
  }

  /**
   * Timed stages with a label and help text, in pipeline order.
   */
  private function stages(): array {
    return [
      'embedding_ms' => [
        $this->t('Query embedding'),
        $this->t('Time for Ollama to turn the query into a vector. The same embedding model was used for the topic vectors stored in PostgreSQL, so pgvector can compare them.'),
      ],
      'lexical_ms' => [
        $this->t('Solr retrieval'),
        $this->t('Time to retrieve and rank topics by keyword matches in Solr. The index contains fields such as titles, alternate names, MeSH terms, and summaries.'),
      ],
      'semantic_ms' => [
        $this->t('pgvector retrieval'),
        $this->t('Time to compare the query vector with stored topic vectors. Similarity is 1 minus cosine distance, so a distance of 0.26 gives a similarity of 0.74. The semantic search applies a configured minimum similarity.'),
      ],
      'fusion_ms' => [
        $this->t('RRF fusion'),
        $this->t('Time to merge the keyword and semantic rankings. A topic earns 1 / (k + rank) for each list where it appears. With k = 60, ranks 1 and 5 contribute 1/61 + 1/65.'),
      ],
      'generation_ms' => [
        $this->t('LLM generation'),
        $this->t('Time for the answer model to read the retrieved source excerpts and attempt a cited response. This stage only runs when conversational search has sources to send.'),
      ],
    ];
  }

}
