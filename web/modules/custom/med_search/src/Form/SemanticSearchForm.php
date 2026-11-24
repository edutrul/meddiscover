<?php

namespace Drupal\med_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\med_search\SemanticSearch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Minimal POST-only educational discovery form in the existing Drupal theme.
 */
class SemanticSearchForm extends FormBase {

  public function __construct(private SemanticSearch $search) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('med_search.search'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_semantic_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form_state->disableCache();
    $form['#cache']['max-age'] = 0;
    $form['intro'] = ['#plain_text' => $this->t('Explore educational health topics. This proof of concept does not provide diagnosis or personalized medical advice.')];
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search health information'),
      '#required' => TRUE,
      '#maxlength' => 500,
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Search')];
    $response = $form_state->getTemporaryValue('med_response');
    if ($response !== NULL) {
      $form['results'] = ['#type' => 'container', '#attributes' => ['aria-live' => 'polite']];
      $form['results']['heading'] = ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $this->t('Search results')];
      if (!$response['results']) {
        $form['results']['empty'] = ['#plain_text' => $this->t('No topics met the relevance threshold. Try another educational health topic.')];
      }
      foreach ($response['results'] as $delta => $result) {
        $node = $result['node'];
        $form['results'][$delta] = [
          '#type' => 'container',
          'title' => ['#type' => 'link', '#title' => $node->label(), '#url' => $node->toUrl()],
          'description' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('@description', ['@description' => $node->field_med_description->value]),
          ],
          'score' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('Cosine similarity: @score', ['@score' => number_format($result['similarity'], 4)]),
          ],
          'source' => [
            '#type' => 'link',
            '#title' => $this->t('Source: MedlinePlus, National Library of Medicine.'),
            '#url' => Url::fromUri($node->field_med_source_url->uri),
          ],
        ];
      }
    }
    $error = $form_state->getTemporaryValue('med_error');
    if ($error) {
      $form['error'] = [
        '#type' => 'container',
        '#attributes' => ['role' => 'alert'],
        'text' => ['#plain_text' => $this->t('Semantic search is temporarily unavailable. Please try again later.')],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $form_state->setTemporaryValue('med_response', $this->search->search((string) $form_state->getValue('query')));
    }
    catch (\Throwable) {
      $form_state->setTemporaryValue('med_error', TRUE);
    }
    // Only temporary state holds results; raw query text is never logged.
    $form_state->disableCache();
    $form_state->setRebuild(TRUE);
  }

}
