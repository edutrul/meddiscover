<?php

/**
 * @file
 * UI contract checks: theme, components, HTMX enhancement and states.
 *
 * Synthetic fixture only. Search behavior itself is covered by the other
 * contract tests; this checks that the presentation keeps every part.
 */

use Drupal\Core\Form\FormState;
use Drupal\Core\Session\UserSession;
use Drupal\med_search\Form\SearchForm;
use Drupal\med_search\SearchStrategyInterface;
use Drupal\med_search\SearchStrategyRegistry;

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
ui_expect(\Drupal::config('system.site')->get('page.front') === '/search', 'The front page must be automatic search.');
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
  public function label(): string {
    return 'Synthetic conversational';
  }

  /**
   * {@inheritdoc}
   */
  public function description(): string {
    return 'Synthetic conversational description.';
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
// The real strategies, with the stub standing in for "conversational".
$registry = new SearchStrategyRegistry();
$real = \Drupal::service('med_search.strategies');
foreach ($real->ids() as $id) {
  $registry->addStrategy($real->get($id));
}
$registry->addStrategy($stub);
$form_object = new SearchForm(
  $registry,
  \Drupal::service('med_search.homepage_router'),
  \Drupal::service('med_search.result_builder'),
  \Drupal::service('med_search.debug_panel'),
  \Drupal::service('event_dispatcher'),
  \Drupal::entityTypeManager(),
);
// Route defaults reach buildForm() as build arguments: /search/conversational.
$route_args = ['conversational'];
$build = function (array $response) use ($form_object, $renderer, $route_args): string {
  $form_state = (new FormState())->setFormObject($form_object)->setValue('query', 'synthetic');
  $form_state->addBuildInfo('args', $route_args);
  $form_state->setTemporaryValue('med_response', $response)->setTemporaryValue('med_search_ms', 1.0);
  $form = \Drupal::formBuilder()->buildForm($form_object, $form_state);
  return (string) $renderer->renderInIsolation($form);
};
$one = $by_source['test-1'];
$result = ['node' => $one, 'id' => 'test-1', 'score' => 0.9, 'ranks' => ['semantic' => 1]];
// The public view is built for an account without the ranking permission.
// The demo grants that permission to anonymous visitors (Developer mode),
// so the anonymous role cannot stand in for "a visitor without it".
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(new UserSession(['uid' => 0, 'roles' => []]));
try {
  $html = $build([
    'results' => [$result],
    'timings' => [],
    'notices' => ['Synthetic notice.'],
    'answer' => ['status' => 'answered', 'statements' => [['text' => 'Synthetic statement.', 'citations' => ['test-1']]], 'dropped' => 0],
  ]);
}
finally {
  $switcher->switchBack();
}
ui_expect(str_contains($html, 'class="med-result result-card"') && str_contains($html, 'result-card__title') && str_contains($html, 'Synthetic Topic One'), 'Results must render through the result-card component.');
ui_expect(!str_contains($html, 'Synthetic score') && str_contains($html, 'Source: MedlinePlus, National Library of Medicine.'), 'Public visitors see source attribution but no ranking scores.');
ui_expect(str_contains($html, 'med-link--external') && str_contains($html, '(opens in a new tab)') && str_contains($html, 'target="_blank"'), 'External links must be marked for the icon and screen readers.');
$switcher->switchTo(new UserSession(['uid' => 1, 'roles' => ['authenticated']]));
try {
  $debug_html = $build(['results' => [$result], 'timings' => [], 'notices' => []]);
}
finally {
  $switcher->switchBack();
}
ui_expect(str_contains($debug_html, 'Ranking details') && str_contains($debug_html, 'Synthetic score: 0.9'), 'Users with the ranking permission see scores in a disclosure.');
ui_expect(str_contains($html, 'med-next-steps') && str_contains($html, 'Explore Health Information'), 'Next steps and enrichment must be kept inside the card.');
ui_expect(str_contains($html, 'med-answer--answered') && (bool) preg_match('#<li>\[1\] <a [^>]*>Synthetic Topic One</a>#', $html), 'Answers must show numbered sources.');
ui_expect(str_contains($html, 'med-state--notice') && str_contains($html, 'Synthetic notice.'), 'Notices must be styled states.');

// 4. HTMX progressive enhancement and accessibility hooks.
ui_expect(str_contains($html, 'data-med-search="conversational"') && str_contains($html, 'data-hx-post="/') && str_contains($html, 'data-hx-select="form[data-med-search]"') && str_contains($html, 'data-hx-target="this"') && str_contains($html, 'data-hx-swap="outerHTML'), 'The form must post in place with core HTMX.');
ui_expect(str_contains($html, 'data-hx-drupal-only-main-content') && str_contains($html, 'data-hx-indicator="#med-search-loading"') && str_contains($html, 'data-hx-disabled-elt="find [type=&quot;submit&quot;]"'), 'HTMX must request main content only, show the indicator and disable submit.');
ui_expect((bool) preg_match('#id="med-search-loading"[^>]*role="status"|role="status"[^>]*id="med-search-loading"#', $html) && str_contains($html, '10 to 20 seconds'), 'The loading indicator must announce the expected wait.');
ui_expect(str_contains($html, 'data-med-search-error') && str_contains($html, 'hidden="hidden"') && str_contains($html, 'role="alert"'), 'A hidden alert must exist for request errors.');
ui_expect(str_contains($html, 'data-med-results-heading') && str_contains($html, 'tabindex="-1"'), 'The results heading must be focusable for focus management.');

// 5. Entry guidance: no duplicate mode list, examples, hint and headings.
$empty = $build(['results' => [], 'timings' => [], 'notices' => [], 'answer' => ['status' => 'abstained', 'reason' => 'no_evidence']]);
$form_state = (new FormState())->setFormObject($form_object)->addBuildInfo('args', $route_args);
$fresh = \Drupal::formBuilder()->buildForm($form_object, $form_state);
$fresh_html = (string) $renderer->renderInIsolation($fresh);
ui_expect(!str_contains($fresh_html, 'Search mode') && str_contains($fresh_html, 'How it works') && !preg_match('#<h3#', preg_replace('#<article.*?</article>#s', '', $fresh_html)), 'The empty page must explain how it works without skipping heading levels.');
ui_expect(str_contains($fresh_html, 'placeholder="e.g. What can trigger a migraine?"') && str_contains($fresh_html, 'required="required"') && str_contains($fresh_html, 'Do not include names or other personal details') && preg_match('#aria-describedby="(edit-query[^"]*--description)"#', $fresh_html, $described) && str_contains($fresh_html, 'id="' . $described[1] . '"'), 'The box needs an example placeholder, native required check and a linked privacy hint.');
ui_expect(substr_count($fresh_html, 'med-examples__button') === 3 && substr_count($fresh_html, 'formnovalidate="formnovalidate"') === 3 && str_contains($fresh_html, 'value="Ask"'), 'Three example buttons must skip native validation; conversational submits with Ask.');
$example_state = (new FormState())->setFormObject($form_object);
$example_state->setTriggeringElement(['#med_example' => 'What is HbA1c?']);
$example_form = [];
$form_object->validateForm($example_form, $example_state);
ui_expect($example_state->getValue('query') === 'What is HbA1c?' && $example_state->getUserInput()['query'] === 'What is HbA1c?' && !$example_state->hasAnyErrors(), 'An example button must become the query.');
$chosen_state = (new FormState())->setFormObject($form_object)->setUserInput(['query' => 'what can trigger a migraine?'])->addBuildInfo('args', $route_args);
$chosen = \Drupal::formBuilder()->buildForm($form_object, $chosen_state);
ui_expect(in_array('is-selected', $chosen['search_panel']['examples']['example_2']['#attributes']['class'], TRUE) && $chosen['search_panel']['examples']['example_2']['#attributes']['aria-current'] === 'true' && !in_array('is-selected', $chosen['search_panel']['examples']['example_0']['#attributes']['class'], TRUE), 'The example matching the query must be marked selected.');
$blank_state = (new FormState())->setFormObject($form_object)->setValue('query', '   ');
$blank_state->setTriggeringElement(['#name' => 'op']);
$form_object->validateForm($example_form, $blank_state);
ui_expect($blank_state->hasAnyErrors(), 'A blank query must be rejected server-side.');
ui_expect(\Drupal::moduleHandler()->moduleExists('inline_form_errors'), 'Inline form errors must be enabled so HTMX swaps include validation messages.');

// 6. States.
$html = $build(['results' => [], 'timings' => [], 'notices' => [], 'answer' => ['status' => 'urgent', 'type' => 'emergency']]);
ui_expect(str_contains($html, 'med-answer--urgent') && str_contains($html, '911') && str_contains($html, 'med-state--empty'), 'Urgent and empty states must be distinct.');

echo "PASS: theme and front page, SDC result card and notice, preserved result parts and attribution, numbered sources, HTMX enhancement, loading/error/empty/urgent states and focus target.\n";
