<?php

namespace Drupal\med_activation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Global activation settings, validated by the config schema constraints.
 */
class ActivationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_activation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['med_activation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['max_ctas'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum resource calls to action per topic'),
      '#min' => 0,
      '#max' => 10,
      '#config_target' => 'med_activation.settings:max_ctas',
    ];
    $form['external_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('External links'),
      '#options' => [
        'same_window' => $this->t('Open in the same window (recommended for accessibility)'),
        'new_window' => $this->t('Open in a new tab, announced in the link text'),
      ],
      '#config_target' => 'med_activation.settings:external_behavior',
    ];
    $form['include_related_topic'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Also recommend the first topic MedlinePlus explicitly relates to the result'),
      '#config_target' => 'med_activation.settings:include_related_topic',
    ];
    return parent::buildForm($form, $form_state);
  }

}
