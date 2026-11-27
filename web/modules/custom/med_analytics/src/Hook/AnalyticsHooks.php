<?php

namespace Drupal\med_analytics\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\med_analytics\AnalyticsRecorder;

/**
 * Impression counting, click beacons and retention.
 */
class AnalyticsHooks {

  public function __construct(private AnalyticsRecorder $recorder) {}

  /**
   * Implements hook_form_alter(), after other modules added their links.
   */
  #[Hook('form_alter', order: Order::Last)]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // The search pages and the Compare page (it tracks its public examples).
    if (!in_array($form_id, ['med_search', 'med_compare_search'], TRUE)) {
      return;
    }
    $form['#attached']['library'][] = 'med_analytics/beacon';
    $form['#attached']['drupalSettings']['medAnalytics']['endpoint'] = Url::fromRoute('med_analytics.event')->toString();
    // Impressions: one count per call to action actually shown.
    if (empty($form['results'])) {
      return;
    }
    foreach (Element::children($form['results']) as $key) {
      foreach ($form['results'][$key]['activation']['list']['#items'] ?? [] as $item) {
        $attributes = $item['link']['#attributes'] ?? [];
        $shown = match ($attributes['data-med-event'] ?? NULL) {
          'cta_clicked' => 'cta_shown',
          'recommendation_clicked' => 'recommendation_shown',
          default => NULL,
        };
        if ($shown) {
          $this->recorder->record($shown, (string) $attributes['data-med-dimension']);
        }
      }
    }
  }

  /**
   * Implements hook_cron(): enforce the retention period.
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->recorder->purge();
  }

}
