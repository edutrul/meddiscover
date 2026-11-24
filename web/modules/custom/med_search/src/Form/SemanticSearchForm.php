<?php

namespace Drupal\med_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\med_search\ResultEnricher;
use Drupal\med_search\SemanticSearch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Minimal POST-only educational discovery form in the existing Drupal theme.
 */
class SemanticSearchForm extends FormBase {

  public function __construct(
    private SemanticSearch $search,
    private ResultEnricher $enricher,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('med_search.search'), $container->get('med_search.enricher'));
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
        ] + $this->buildEnrichment($this->enricher->enrich($node));
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
   * Render enrichment as three collapsible sections in the existing theme.
   */
  private function buildEnrichment(array $data): array {
    $build = [];
    $info = [];
    foreach (['also_called' => $this->t('Also called'), 'see_reference' => $this->t('See also')] as $key => $label) {
      if ($data[$key]) {
        $info[$key] = $this->itemList($label, array_map(fn($v) => ['#plain_text' => $v], $data[$key]));
      }
    }
    if ($data['groups']) {
      $info['groups'] = $this->itemList($this->t('MedlinePlus groups'), array_map(fn($g) => $this->link($g['label'], $g['url']), $data['groups']));
    }
    if ($data['mesh']) {
      $info['mesh'] = $this->itemList($this->t('MeSH descriptors'), array_map(fn($m) => [
        '#plain_text' => $m['descriptor'] . ($m['qualifier'] ? ' / ' . $m['qualifier'] : ''),
      ], $data['mesh']));
    }
    if ($data['summary_links']) {
      $info['summary_links'] = $this->itemList($this->t('Further reading from the summary'), array_map(fn($l) => $this->link($l['title'], $l['url']), $data['summary_links']));
    }
    if ($info) {
      $build['info'] = ['#type' => 'details', '#title' => $this->t('Explore Health Information')] + $info;
    }
    if ($data['related']) {
      $items = [];
      foreach ($data['related'] as $item) {
        $items[] = [
          'link' => $item['node'] ? [
            '#type' => 'link',
            '#title' => $item['title'],
            '#url' => $item['node']->toUrl(),
          ] : $this->link($item['title'] . ' (MedlinePlus)', $item['url']),
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
        'list' => ['#theme' => 'item_list', '#items' => $items],
      ];
    }
    if ($data['resources']) {
      $build['resources'] = [
        '#type' => 'details',
        '#title' => $this->t('Explore Healthcare Resources (@count categories)', ['@count' => count($data['resources'])]),
      ];
      foreach ($data['resources'] as $category => $resources) {
        $build['resources'][] = $this->itemList($category . ' (' . count($resources) . ')', array_map(fn($r) => [
          'link' => $this->link($r['title'], $r['url']),
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
   * External link to an original source URL.
   */
  private function link(string $title, string $url): array {
    return ['#type' => 'link', '#title' => $title, '#url' => Url::fromUri($url)];
  }

  /**
   * Titled item list.
   */
  private function itemList($title, array $items): array {
    return ['#theme' => 'item_list', '#title' => $title, '#items' => $items];
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
