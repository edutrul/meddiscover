<?php

namespace Drupal\med_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Analytics settings, validated by schema constraints.
 */
class AnalyticsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_analytics_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['med_analytics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Record aggregate metrics'),
      '#description' => $this->t('Daily counters only. Search text, topics, URLs and visitor identifiers are never stored.'),
      '#config_target' => 'med_analytics.settings:enabled',
    ];
    $form['retention_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Keep daily aggregates for (days)'),
      '#min' => 1,
      '#max' => 365,
      '#config_target' => 'med_analytics.settings:retention_days',
    ];
    $form['report_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Days shown in the report'),
      '#min' => 1,
      '#max' => 365,
      '#config_target' => 'med_analytics.settings:report_days',
    ];
    return parent::buildForm($form, $form_state);
  }

}
