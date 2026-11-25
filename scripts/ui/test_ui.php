<?php

/**
 * @file
 * UI contract checks: theme, components, HTMX enhancement and states.
 *
 * Synthetic fixture only. Search behavior itself is covered by the other
 * contract tests; this checks that the presentation keeps every part.
 */

use Drupal\Core\Form\FormState;
use Drupal\med_search\Form\SearchForm;
use Drupal\med_search\SearchStrategyInterface;

/**
 * Fail the run with a clear message.
 */
function ui_expect(bool $value, string $message): void {
  if (!$value) {
    throw new RuntimeException($message);
  }
}

$topics = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'med_health_topic']);
ui_expect(count($topics) === 2, 'Tests require the two-topic synthetic migration database.');
foreach ($topics as $topic) {
  ui_expect(str_starts_with($topic->field_med_source_id->value, 'test-'), 'Refusing production corpus.');
  $by_source[$topic->field_med_source_id->value] = $topic;
}

// 1. Theme configuration.
$theme = \Drupal::config('system.theme');
ui_expect($theme->get('default') === 'meddiscover' && $theme->get('admin') === 'claro', 'medDiscover must be the default theme and Claro the admin theme.');
ui_expect(\Drupal::config('system.site')->get('page.front') === '/search/conversational', 'The front page must be the conversational search.');
ui_expect(\Drupal::service('theme.manager')->getActiveTheme()->getName() === 'meddiscover', 'Rendering must use the medDiscover theme.');
$block = \Drupal::entityTypeManager()->getStorage('block')->load('meddiscover_powered');
ui_expect($block && !$block->status(), 'The Powered by Drupal block must stay disabled.');

// 2. Single Directory Components.
$components = \Drupal::service('plugin.manager.sdc');
foreach (['meddiscover:result-card', 'meddiscover:notice'] as $id) {
  ui_expect((bool) $components->find($id), "Component $id must be discoverable.");
}
$renderer = \Drupal::service('renderer');
$element = [
  '#type' => 'component',
  '#component' => 'meddiscover:notice',
  '#props' => ['variant' => 'warning', 'heading' => 'Synthetic heading'],
  '#slots' => ['content' => ['#markup' => '<p>Synthetic body</p>']],
];
$notice = (string) $renderer->renderInIsolation($element);
ui_expect(str_contains($notice, 'notice--warning') && str_contains($notice, 'aria-label="Synthetic heading"') && str_contains($notice, 'Synthetic body'), 'The notice component must render its variant, label and content.');

// 3. The search form: results through the result-card component.
$stub = new class() implements SearchStrategyInterface {

  /**
   * Response to return.
   */
  public array $response = [];

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'conversational';
  }

  /**
   * {@inheritdoc}
   */
  public function search(string $query, ?int $limit = NULL): array {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function describeScore(array $result): string {
    return 'Synthetic score: 0.9';
  }

};
$form_object = new SearchForm($stub, \Drupal::service('med_search.enricher'), \Drupal::service('event_dispatcher'));
$build = function (array $response) use ($form_object, $renderer): string {
  $form_state = (new FormState())->setFormObject($form_object)->setValue('query', 'synthetic');
  $form_state->setTemporaryValue('med_response', $response)->setTemporaryValue('med_search_ms', 1.0);
  $form = \Drupal::formBuilder()->buildForm($form_object, $form_state);
  return (string) $renderer->renderInIsolation($form);
};
$one = $by_source['test-1'];
$result = ['node' => $one, 'id' => 'test-1', 'score' => 0.9, 'ranks' => ['semantic' => 1]];
$html = $build([
  'results' => [$result],
  'timings' => [],
  'notices' => ['Synthetic notice.'],
  'answer' => ['status' => 'answered', 'statements' => [['text' => 'Synthetic statement.', 'citations' => ['test-1']]], 'dropped' => 0],
]);
ui_expect(str_contains($html, 'class="med-result result-card"') && str_contains($html, 'result-card__title') && str_contains($html, 'Synthetic Topic One'), 'Results must render through the result-card component.');
ui_expect(str_contains($html, 'Synthetic score: 0.9') && str_contains($html, 'Source: MedlinePlus, National Library of Medicine.'), 'Score explanation and source attribution must be kept.');
ui_expect(str_contains($html, 'med-next-steps') && str_contains($html, 'Explore Health Information'), 'Next steps and enrichment must be kept inside the card.');
ui_expect(str_contains($html, 'med-answer--answered') && (bool) preg_match('#<li>\[1\] <a [^>]*>Synthetic Topic One</a>#', $html), 'Answers must show numbered sources.');
ui_expect(str_contains($html, 'med-state--notice') && str_contains($html, 'Synthetic notice.'), 'Notices must be styled states.');

// 4. HTMX progressive enhancement and accessibility hooks.
ui_expect(str_contains($html, 'data-med-search="conversational"') && str_contains($html, 'data-hx-post="/') && str_contains($html, 'data-hx-select="form[data-med-search]"') && str_contains($html, 'data-hx-target="this"') && str_contains($html, 'data-hx-swap="outerHTML'), 'The form must post in place with core HTMX.');
ui_expect(str_contains($html, 'data-hx-drupal-only-main-content') && str_contains($html, 'data-hx-indicator="#med-search-loading"') && str_contains($html, 'data-hx-disabled-elt="find [type=&quot;submit&quot;]"'), 'HTMX must request main content only, show the indicator and disable submit.');
ui_expect((bool) preg_match('#id="med-search-loading"[^>]*role="status"|role="status"[^>]*id="med-search-loading"#', $html) && str_contains($html, '10 to 20 seconds'), 'The loading indicator must announce the expected wait.');
ui_expect(str_contains($html, 'data-med-search-error') && str_contains($html, 'hidden="hidden"') && str_contains($html, 'role="alert"'), 'A hidden alert must exist for request errors.');
ui_expect(str_contains($html, 'data-med-results-heading') && str_contains($html, 'tabindex="-1"'), 'The results heading must be focusable for focus management.');

// 5. States.
$html = $build(['results' => [], 'timings' => [], 'notices' => [], 'answer' => ['status' => 'urgent', 'type' => 'emergency']]);
ui_expect(str_contains($html, 'med-answer--urgent') && str_contains($html, '911') && str_contains($html, 'med-state--empty'), 'Urgent and empty states must be distinct.');

echo "PASS: theme and front page, SDC result card and notice, preserved result parts and attribution, numbered sources, HTMX enhancement, loading/error/empty/urgent states and focus target.\n";
