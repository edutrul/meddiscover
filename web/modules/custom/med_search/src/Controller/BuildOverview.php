<?php

namespace Drupal\med_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Describes the architecture and provides entry points for reviewers.
 */
final class BuildOverview extends ControllerBase {

  /**
   * Presents the four layers in the order used in the demo.
   */
  public function page(): array {
    $layers = [
      [
        $this->t('Experience'),
        $this->t('A Drupal theme and Single Directory Components render the search experience. Form API handles submission and validation, and core HTMX updates results in place.'),
      ],
      [
        $this->t('Data'),
        $this->t('Drupal Migrate imports 24 English MedlinePlus topics and their MeSH metadata. Solr matches keywords, pgvector ranks by meaning, and hybrid search combines both rankings with Reciprocal Rank Fusion.'),
      ],
      [
        $this->t('Activation'),
        $this->t('Answers cite the MedlinePlus topics they draw from. Results link to health information and relevant resources so visitors can take the next step.'),
      ],
      [
        $this->t('Optimization'),
        $this->t('Relevance tests and aggregate latency, empty-result, and resource-click metrics feed back into retrieval and interface improvements.'),
      ],
    ];
    $page = ['#type' => 'container', '#attributes' => ['class' => ['med-build-overview']]];
    foreach ($layers as $index => [$title, $description]) {
      $page['layer_' . $index] = [
        '#type' => 'html_tag',
        '#tag' => 'section',
        'title' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $title],
        'description' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => $description],
      ];
    }
    $page['layer_1']['comparison_intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Run the same query in Keyword, Semantic, and Hybrid to see how each mode ranks the topics.'),
    ];
    $page['layer_1']['workspace'] = [
      '#type' => 'link',
      '#title' => $this->t('Compare search modes'),
      '#url' => Url::fromRoute('med_search.compare'),
    ];
    $page['compare'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Try each search mode'),
      '#items' => [],
    ];
    $modes = [
      'conversational' => $this->t('Ask'),
      'hybrid' => $this->t('Hybrid search'),
      'semantic' => $this->t('Semantic search'),
      'lexical' => $this->t('Keyword search'),
    ];
    foreach ($modes as $mode => $label) {
      $page['compare']['#items'][] = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => Url::fromRoute('med_search.' . $mode),
      ];
    }
    return $page;
  }

}
