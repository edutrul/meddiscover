<?php

namespace Drupal\med_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;
use Drupal\med_search\SearchStrategyRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lets reviewers run the same query through each retrieval strategy.
 */
final class CompareSearchForm extends FormBase {

  public function __construct(private SearchStrategyRegistry $strategies) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('med_search.strategies'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_compare_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form_state->disableCache();
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['data-med-search'] = 'compare';
    $form['#attributes']['class'][] = 'med-compare';
    $form['#attached']['library'][] = 'med_search/search';
    (new Htmx())->post(Url::fromRoute('med_search.compare'))->history()
      ->onlyMainContent()->select('form[data-med-search="compare"]')
      ->target('this')->swap('outerHTML')->indicator('#med-compare-loading')
      ->disabledElt('find [type="submit"]')->applyTo($form);

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query to compare'),
      '#description' => $this->t('Use a demo query or enter your own words without personal details.'),
      '#maxlength' => 500,
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Compare')];
    $form['examples'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-examples']],
    ];
    foreach (['HbA1c', 'Backache', 'Knee pain when walking'] as $index => $example) {
      $form['examples'][$index] = [
        '#type' => 'submit',
        '#name' => 'compare_example_' . $index,
        '#value' => $example,
        '#med_example' => $example,
      ];
    }

    $form['loading'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['id' => 'med-compare-loading', 'class' => ['med-loading'], 'role' => 'status'],
      '#value' => $this->t('Running all three searches…'),
    ];

    $response = $form_state->getTemporaryValue('comparison');
    if ($response === NULL) {
      return $form;
    }
    if ($example = $form_state->getTriggeringElement()['#med_example'] ?? NULL) {
      $form['#attributes']['data-med-public-example'] = $example;
    }
    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Comparison results'),
      '#attributes' => ['tabindex' => '-1', 'data-med-results-heading' => ''],
    ];
    $form['ranking'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-comparison-grid']],
    ];
    $can_debug = $this->currentUser()->hasPermission('view med search ranking details');
    foreach (['lexical' => $this->t('Keyword'), 'semantic' => $this->t('Semantic'), 'hybrid' => $this->t('Hybrid')] as $id => $label) {
      $column = [
        '#type' => 'html_tag',
        '#tag' => 'section',
        'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $label],
        'about' => ['#plain_text' => $this->t('@description', ['@description' => SearchForm::STRATEGIES[$id][1]])],
      ];
      $result = $response[$id];
      if ($result === NULL || !$result['results']) {
        $column['status'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $result === NULL ? $this->t('This strategy is temporarily unavailable.') : $this->t('No matching topics.'),
        ];
      }
      else {
        $strategy = $this->strategies->get($id);
        $column['topics'] = ['#theme' => 'item_list', '#list_type' => 'ol', '#items' => []];
        foreach ($result['results'] as $item) {
          $entry = ['topic' => $item['node']->toLink()->toRenderable()];
          if ($can_debug) {
            $entry['score'] = [
              '#type' => 'container',
              '#attributes' => ['class' => ['med-debug']],
              'value' => ['#plain_text' => $strategy->describeScore($item)],
            ];
          }
          $column['topics']['#items'][] = $entry;
        }
        if ($can_debug) {
          $column['timing'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#attributes' => ['class' => ['med-debug']],
            '#value' => $this->t('Elapsed server time: @ms ms', ['@ms' => number_format($result['comparison_ms'], 1)]),
          ];
        }
      }
      foreach ($result['notices'] ?? [] as $index => $notice) {
        $column['notice_' . $index] = ['#plain_text' => $notice];
      }
      $form['ranking'][$id] = $column;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $query = trim((string) ($form_state->getTriggeringElement()['#med_example'] ?? $form_state->getValue('query')));
    if ($query === '' || mb_strlen($query) > 500) {
      $form_state->setErrorByName('query', $this->t('Enter a query between 1 and 500 characters.'));
    }
    $form_state->setValue('query', $query);
    $input = $form_state->getUserInput();
    $input['query'] = $query;
    $form_state->setUserInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $response = [];
    foreach (['lexical', 'semantic', 'hybrid'] as $id) {
      $start = hrtime(TRUE);
      try {
        $result = $this->strategies->get($id)->search((string) $form_state->getValue('query'), 5);
        $result['comparison_ms'] = (hrtime(TRUE) - $start) / 1e6;
        $response[$id] = $result;
      }
      catch (\RuntimeException) {
        $response[$id] = NULL;
      }
    }
    $form_state->setTemporaryValue('comparison', $response)->disableCache();
    $form_state->setRebuild();
  }

}
