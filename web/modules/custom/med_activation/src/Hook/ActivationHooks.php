<?php

namespace Drupal\med_activation\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\med_activation\ActivationBuilder;
use Drupal\med_activation\ActivationEngine;

/**
 * Adds explainable "Next steps" to every medDiscover search result.
 */
class ActivationHooks {

  public function __construct(
    private ActivationEngine $engine,
    private ActivationBuilder $builder,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_form_alter() for the medDiscover search forms.
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!preg_match('/^med_[a-z]+_search$/', $form_id) || empty($form['results'])) {
      return;
    }
    $query = (string) $form_state->getValue('query', '');
    $behavior = (string) $this->configFactory->get('med_activation.settings')->get('external_behavior');
    foreach (Element::children($form['results']) as $key) {
      $result = &$form['results'][$key];
      if (!isset($result['#med_topic'])) {
        continue;
      }
      $result['activation'] = $this->builder->build(
        $this->engine->recommend($result['#med_topic'], $query, $result['#med_enrichment'] ?? NULL),
        $behavior,
      ) + ['#weight' => 5];
    }
    $form['#cache']['tags'][] = 'config:med_activation.settings';
    $form['#cache']['tags'][] = 'config:med_activation_rule_list';
  }

}
