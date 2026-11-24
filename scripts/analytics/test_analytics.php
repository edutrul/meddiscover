<?php

/**
 * @file
 * Analytics contract checks on the synthetic fixture only.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\med_analytics\Controller\EventController;
use Drupal\med_search\Event\SearchCompletedEvent;
use Drupal\med_search\Form\SearchForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fail the run with a clear message.
 */
function ana_expect(bool $value, string $message): void {
  if (!$value) {
    throw new RuntimeException($message);
  }
}

$topics = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'med_health_topic']);
ana_expect(count($topics) === 2, 'Tests require the two-topic synthetic migration database.');
foreach ($topics as $topic) {
  ana_expect(str_starts_with($topic->field_med_source_id->value, 'test-'), 'Refusing production corpus.');
  $by_source[$topic->field_med_source_id->value] = $topic;
}
$db = \Drupal::database();
$recorder = \Drupal::service('med_analytics.recorder');
$settings = \Drupal::configFactory()->getEditable('med_analytics.settings');
$saved = $settings->getRawData();
$db->truncate('med_analytics_daily')->execute();
$count = fn(string $event, string $dimension) => (int) $db->query('SELECT SUM(count) FROM {med_analytics_daily} WHERE event = :e AND dimension = :d', [':e' => $event, ':d' => $dimension])->fetchField();
$ms = fn(string $event, string $dimension) => (float) $db->query('SELECT SUM(total_ms) FROM {med_analytics_daily} WHERE event = :e AND dimension = :d', [':e' => $event, ':d' => $dimension])->fetchField();

try {
  // 1. Allowlist: fixed vocabularies only.
  foreach ([
    ['search_performed', 'hybrid'],
    ['search_result_clicked', 'semantic:rank2-3'],
    ['cta_clicked', 'clinical_trials'],
    ['answer_outcome', 'abstained'],
    ['latency', 'conversational:generation'],
    ['latency_bucket', 'lexical:lt500ms'],
    ['external_resource_clicked', 'citation'],
  ] as [$event, $dimension]) {
    ana_expect($recorder->isAllowed($event, $dimension), "Contract pair must be allowed: $event/$dimension");
  }
  foreach ([
    ['search_performed', 'What is backache?'],
    ['cta_clicked', 'my knee hurts'],
    ['cta_clicked', 'not_a_rule'],
    ['external_resource_clicked', 'https://example.org/resource'],
    ['search_result_clicked', 'semantic:test-1'],
    ['topic_viewed', 'test-1'],
    ['latency', 'semantic:query'],
  ] as [$event, $dimension]) {
    ana_expect(!$recorder->isAllowed($event, $dimension) && !$recorder->record($event, $dimension), "Pair outside the contract must be rejected: $event/$dimension");
  }

  // 2. Daily aggregation and latency sums.
  $recorder->record('search_performed', 'semantic');
  $recorder->record('search_performed', 'semantic');
  $recorder->recordLatency('semantic', 'total', 120.5);
  $recorder->recordLatency('semantic', 'total', 30.0);
  ana_expect($count('search_performed', 'semantic') === 2 && (int) $db->query('SELECT COUNT(*) FROM {med_analytics_daily}')->fetchField() === 4, 'Repeated events must increment one daily row.');
  ana_expect($count('latency', 'semantic:total') === 2 && abs($ms('latency', 'semantic:total') - 150.5) < 0.001, 'Latency must aggregate count and sum.');
  ana_expect($count('latency_bucket', 'semantic:lt500ms') === 1 && $count('latency_bucket', 'semantic:lt100ms') === 1, 'Totals must be bucketed.');
  ana_expect((bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $db->query('SELECT MIN(day) FROM {med_analytics_daily}')->fetchField()), 'Rows must be keyed by UTC day only.');
  ana_expect(array_keys($db->query('SELECT * FROM {med_analytics_daily}')->fetchAssoc()) === ['day', 'event', 'dimension', 'count', 'total_ms'], 'The table must hold no identifier, query, topic or URL column.');

  // 3. Disabled means no writes.
  $settings->set('enabled', FALSE)->save();
  ana_expect(!$recorder->record('search_performed', 'lexical') && $count('search_performed', 'lexical') === 0, 'Disabled analytics must not write.');
  $settings->set('enabled', TRUE)->save();

  // 4. Retention.
  $db->insert('med_analytics_daily')->fields(['day' => gmdate('Y-m-d', time() - 200 * 86400), 'event' => 'search_performed', 'dimension' => 'lexical', 'count' => 5, 'total_ms' => 0])->execute();
  $settings->set('retention_days', 90)->save();
  ana_expect($recorder->purge() === 1 && $count('search_performed', 'lexical') === 0 && $count('search_performed', 'semantic') === 2, 'Rows older than the retention period must be purged, newer ones kept.');
  $db->insert('med_analytics_daily')->fields(['day' => gmdate('Y-m-d', time() - 200 * 86400), 'event' => 'search_performed', 'dimension' => 'hybrid', 'count' => 1, 'total_ms' => 0])->execute();
  \Drupal::service('cron')->run();
  ana_expect($count('search_performed', 'hybrid') === 0, 'Cron must enforce retention.');

  // 5. Search event subscriber.
  $db->truncate('med_analytics_daily')->execute();
  $dispatcher = \Drupal::service('event_dispatcher');
  $dispatcher->dispatch(new SearchCompletedEvent('lexical', 0, FALSE, NULL, ['total' => 8.0, 'retrieval' => 3.0]), SearchCompletedEvent::NAME);
  $dispatcher->dispatch(new SearchCompletedEvent('conversational', 0, FALSE, 'urgent', ['total' => 40.0]), SearchCompletedEvent::NAME);
  $dispatcher->dispatch(new SearchCompletedEvent('conversational', 3, FALSE, 'answered', ['total' => 12000.0, 'generation' => 11000.0]), SearchCompletedEvent::NAME);
  $dispatcher->dispatch(new SearchCompletedEvent('hybrid', 0, TRUE, NULL, ['total' => 5.0]), SearchCompletedEvent::NAME);
  ana_expect($count('search_performed', 'lexical') === 1 && $count('search_no_results', 'lexical') === 1 && abs($ms('latency', 'lexical:retrieval') - 3.0) < 0.001, 'Searches, zero results and stage latency must be recorded.');
  ana_expect($count('search_performed', 'conversational') === 2 && $count('search_no_results', 'conversational') === 0 && $count('answer_outcome', 'urgent') === 1 && $count('answer_outcome', 'answered') === 1, 'Urgent responses are outcomes, not zero results.');
  ana_expect($count('latency_bucket', 'conversational:lt15s') === 1 && $count('latency', 'conversational:generation') === 1, 'Generation latency must be bucketed and summed.');
  ana_expect($count('search_error', 'hybrid') === 1 && $count('search_no_results', 'hybrid') === 0, 'Errors must not count as zero results.');

  // 6. Click endpoint.
  $controller = EventController::create(\Drupal::getContainer());
  $post = fn(array $data) => $controller->record(Request::create('/med-analytics/event', 'POST', $data))->getStatusCode();
  ana_expect($post(['event' => 'cta_clicked', 'dimension' => 'learn_more']) === 204 && $count('cta_clicked', 'learn_more') === 1, 'Allowed clicks must be counted.');
  ana_expect($post(['event' => 'search_performed', 'dimension' => 'semantic']) === 400, 'Server-side events cannot be reported by browsers.');
  ana_expect($post(['event' => 'cta_clicked', 'dimension' => 'What is backache?']) === 400 && $post(['event' => str_repeat('x', 65)]) === 400 && $post([]) === 400, 'Free text, oversized or empty input must be rejected.');

  // 7. Impressions and beacon attachment on the search form.
  $form_object = new class() extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
      return 'med_semantic_search';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {}

  };
  $one = $by_source['test-1'];
  $form = ['results' => [0 => ['#med_topic' => $one, '#med_enrichment' => \Drupal::service('med_search.enricher')->enrich($one)]]];
  $form_state = (new FormState())->setFormObject($form_object)->setValue('query', 'fixture');
  $form_id = 'med_semantic_search';
  \Drupal::moduleHandler()->alter('form', $form, $form_state, $form_id);
  ana_expect($count('cta_shown', 'patient_handouts') === 1 && $count('cta_shown', 'learn_more') === 1 && $count('recommendation_shown', 'related_topic') === 1, 'Each shown call to action must count one impression.');
  ana_expect(in_array('med_analytics/beacon', $form['#attached']['library'], TRUE) && $form['#attached']['drupalSettings']['medAnalytics']['endpoint'] === '/med-analytics/event', 'The beacon must be attached with its endpoint.');
  $link = $form['results'][0]['activation']['list']['#items'][0]['link']['#attributes'];
  ana_expect($link['data-med-event'] === 'cta_clicked' && $link['data-med-dimension'] === 'patient_handouts', 'CTA links carry only the event and rule ID.');
  ana_expect(SearchForm::rankBucket(1) === 'rank1' && SearchForm::rankBucket(3) === 'rank2-3' && SearchForm::rankBucket(9) === 'rank4plus', 'Result clicks are bucketed by rank.');

  // 8. Nothing sensitive is stored.
  $leaks = (int) $db->query("SELECT COUNT(*) FROM {med_analytics_daily} WHERE dimension LIKE '%fixture%' OR dimension LIKE '%http%' OR dimension LIKE '%test-%' OR dimension LIKE '% %'")->fetchField();
  ana_expect($leaks === 0, 'No query text, URL or topic identifier may be stored.');

  echo "PASS: allowlisted fixed vocabularies, daily aggregation, latency sums and buckets, disable switch, retention and cron purge, search event subscriber, click endpoint validation, impressions, beacon attachment and no sensitive values.\n";
}
finally {
  $settings->setData($saved)->save();
  $db->truncate('med_analytics_daily')->execute();
}
