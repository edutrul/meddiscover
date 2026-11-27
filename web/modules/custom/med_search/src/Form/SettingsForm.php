<?php

namespace Drupal\med_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;
use Drupal\Core\Url;

/**
 * Search tuning for medDiscover.
 *
 * Each element maps to a key with #config_target, so Drupal saves it and
 * validates it against the constraints in med_search.schema.yml. Providers
 * and models are configured in the Drupal AI module.
 */
final class SettingsForm extends ConfigFormBase {

  use RedundantEditableConfigNamesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $target = static fn(string $key): string => 'med_search.settings:' . $key;

    $form['models'] = [
      '#type' => 'item',
      '#title' => $this->t('Providers and models'),
      '#markup' => $this->t('The embedding model, the answer model and the Ollama host are set in the <a href=":ai">Drupal AI settings</a> (default providers for "Embeddings" and "Chat with structured response") and the <a href=":ollama">Ollama provider settings</a>.', [
        ':ai' => Url::fromRoute('ai.settings_form')->toString(),
        ':ollama' => Url::fromRoute('ai_provider_ollama.settings_form')->toString(),
      ]),
    ];

    $form['retrieval'] = [
      '#type' => 'details',
      '#title' => $this->t('Retrieval'),
      '#open' => TRUE,
    ];
    $form['retrieval']['result_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum results'),
      '#min' => 1,
      '#max' => 20,
      '#config_target' => $target('result_limit'),
    ];
    $form['retrieval']['threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Semantic similarity cutoff'),
      '#description' => $this->t('Minimum cosine similarity (1 minus pgvector cosine distance). Higher values return fewer, closer topics.'),
      '#step' => 0.01,
      '#min' => -1,
      '#max' => 1,
      '#config_target' => $target('threshold'),
    ];
    $form['retrieval']['lexical_min_match'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solr minimum-should-match (mm)'),
      '#description' => $this->t('EDisMax rule, for example <code>2&lt;75%</code>: up to two terms all are required; with more, 75% of them.'),
      '#config_target' => $target('lexical_min_match'),
    ];
    $form['retrieval']['hybrid_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Hybrid candidates per list'),
      '#min' => 1,
      '#max' => 100,
      '#config_target' => $target('hybrid_depth'),
    ];
    $form['retrieval']['hybrid_rrf_k'] = [
      '#type' => 'number',
      '#title' => $this->t('Reciprocal Rank Fusion constant k'),
      '#description' => $this->t('A topic at rank r earns 1 / (k + r) points from each list. 60 is the standard value.'),
      '#min' => 0,
      '#max' => 1000,
      '#config_target' => $target('hybrid_rrf_k'),
    ];

    $form['answers'] = [
      '#type' => 'details',
      '#title' => $this->t('Answers (RAG)'),
      '#open' => TRUE,
    ];
    $form['answers']['rag_strategy'] = [
      '#type' => 'select',
      '#title' => $this->t('Retrieval used for answers'),
      '#options' => [
        'semantic' => $this->t('Semantic'),
        'lexical' => $this->t('Keyword (Lexical)'),
        'hybrid' => $this->t('Hybrid (Keyword + Semantic)'),
      ],
      '#config_target' => $target('rag_strategy'),
    ];
    $form['answers']['rag_sources'] = [
      '#type' => 'number',
      '#title' => $this->t('Topics given to the model'),
      '#min' => 1,
      '#max' => 10,
      '#config_target' => $target('rag_sources'),
    ];
    $form['answers']['rag_source_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Characters of each topic'),
      '#min' => 200,
      '#max' => 10000,
      '#config_target' => $target('rag_source_chars'),
    ];
    $form['answers']['generation_max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum answer tokens'),
      '#description' => $this->t('A longer answer is treated as a failure, never shown truncated.'),
      '#min' => 32,
      '#max' => 4096,
      '#config_target' => $target('generation_max_tokens'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
