<?php

/**
 * @file
 * Activation rule and CTA contract checks on the synthetic fixture only.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\med_activation\Entity\ActivationRule;

/**
 * Fail the run with a clear message.
 */
function act_expect(bool $value, string $message): void {
  if (!$value) {
    throw new RuntimeException($message);
  }
}

$storage = \Drupal::entityTypeManager()->getStorage('node');
$topics = $storage->loadByProperties(['type' => 'med_health_topic']);
act_expect(count($topics) === 2, 'Tests require the two-topic synthetic migration database.');
$by_source = [];
foreach ($topics as $topic) {
  act_expect(str_starts_with($topic->field_med_source_id->value, 'test-'), 'Refusing production corpus.');
  $by_source[$topic->field_med_source_id->value] = $topic;
}
$one = $by_source['test-1'];
$two = $by_source['test-2'];
$engine = \Drupal::service('med_activation.engine');
$builder = \Drupal::service('med_activation.builder');
$enricher = \Drupal::service('med_search.enricher');
$settings = \Drupal::configFactory()->getEditable('med_activation.settings');
$saved_settings = $settings->getRawData();
$saved_rules = array_map(fn($rule) => $rule->toArray(), ActivationRule::loadMultiple());
$labels = fn(array $items) => array_column($items, 'label');

try {
  // 1. Default rules ship in configuration, in priority order.
  act_expect(array_map(fn($rule) => $rule->id(), $engine->rules()) === ['start_here', 'patient_handouts', 'find_an_expert', 'clinical_trials', 'journal_articles', 'learn_more'], 'Default rules must be installed in priority order.');
  act_expect(ActivationRule::load('clinical_trials')->getCtaLabel() === 'Explore Clinical Trials' && in_array('trial', ActivationRule::load('clinical_trials')->getKeywords(), TRUE), 'Rule labels and keywords come from configuration.');

  // 2. CTAs from the topic's own resources, with source URLs and reasons.
  $items = $engine->recommend($one);
  act_expect($labels($items) === ['Read Patient Resources', 'Learn More', 'Explore: Synthetic Topic Two'], 'CTAs follow rule priority for available categories, then one related topic: ' . implode(', ', $labels($items)));
  $resource_urls = [];
  foreach ($enricher->resources($one) as $category_items) {
    $resource_urls = array_merge($resource_urls, array_column($category_items, 'url'));
  }
  foreach (array_slice($items, 0, 2) as $item) {
    act_expect(in_array($item['url'], $resource_urls, TRUE) && $item['url'] === 'https://example.org/resource', 'CTA destinations must be imported resource URLs.');
    act_expect($item['organizations'] === ['Fixture Org A', 'Fixture Org B'] && $item['type'] === 'resource', 'Organization attribution must be preserved.');
  }
  act_expect($items[0]['reason'] === 'MedlinePlus lists 1 “Patient Handouts” resource for Synthetic Topic One.' && $items[0]['keyword'] === NULL, 'Each CTA must explain why it is shown.');
  act_expect($items[2]['type'] === 'topic' && $items[2]['node']->id() === $two->id() && str_contains($items[2]['reason'], 'Related topic listed by MedlinePlus'), 'The related-topic recommendation must come from an explicit relationship and say so.');
  act_expect($engine->recommend($one, '', $enricher->enrich($one)) === $items, 'Precomputed enrichment must give the same result.');

  // 3. Search context: a keyword raises its rule and is named in the reason.
  $items = $engine->recommend($one, 'I would like more information, please');
  act_expect($labels($items)[0] === 'Learn More' && str_starts_with($items[0]['reason'], 'Your search mentions “more information”.'), 'A matching keyword must move its rule first and be explained.');
  act_expect($labels($engine->recommend($one, 'detailsx informational'))[0] === 'Read Patient Resources', 'Keywords match whole words only.');
  act_expect($labels($engine->recommend($one, 'LEARN MORE!'))[0] === 'Learn More', 'Keyword matching ignores case and punctuation.');

  // 4. Business rules.
  $settings->set('max_ctas', 1)->save();
  act_expect($labels($engine->recommend($one)) === ['Read Patient Resources', 'Explore: Synthetic Topic Two'], 'The maximum must limit resource CTAs.');
  $settings->set('max_ctas', 3)->set('include_related_topic', FALSE)->save();
  act_expect($labels($engine->recommend($one)) === ['Read Patient Resources', 'Learn More'], 'The related-topic recommendation must be configurable.');
  ActivationRule::load('patient_handouts')->disable()->save();
  act_expect($labels($engine->recommend($one)) === ['Learn More'], 'Disabled rules must not produce CTAs.');
  act_expect($labels($engine->recommend($two)) === ['Learn More'], 'Topics use only their own resources.');
  ActivationRule::load('learn_more')->set('require_organization', TRUE)->save();
  act_expect($labels($engine->recommend($two)) === [] && $labels($engine->recommend($one)) === ['Learn More'], 'Attribution rules must exclude resources without a named organization.');
  ActivationRule::load('learn_more')->set('weight', -10)->set('cta_label', 'Read More Sources')->save();
  ActivationRule::load('patient_handouts')->enable()->save();
  act_expect($labels($engine->recommend($one)) === ['Read More Sources', 'Read Patient Resources'], 'Labels and priority must follow edited rules.');

  // 5. Configuration validation (schema constraints).
  $typed = \Drupal::service('config.typed');
  $bad_rule = ActivationRule::load('learn_more')->toArray();
  $bad_rule['category'] = '';
  $bad_rule['cta_label'] = '';
  act_expect(count($typed->createFromNameAndData('med_activation.rule.learn_more', $bad_rule)->validate()) >= 2, 'Rules without a category or label must fail validation.');
  act_expect(count($typed->createFromNameAndData('med_activation.settings', ['max_ctas' => 99, 'external_behavior' => 'popup', 'include_related_topic' => TRUE])->validate()) === 2, 'Settings must reject out-of-range limits and unknown link behavior.');

  // 6. Rendering: external links are identified; internal links are not.
  $settings->set('include_related_topic', TRUE)->save();
  $renderer = \Drupal::service('renderer');
  $html = (string) $renderer->renderInIsolation($builder->build($engine->recommend($one), 'same_window'));
  act_expect(str_contains($html, 'href="https://example.org/resource"') && str_contains($html, 'rel="noopener noreferrer"') && str_contains($html, '(external site: example.org)'), 'External CTAs must show their destination and use rel=noopener.');
  act_expect(!str_contains($html, 'target="_blank"') && str_contains($html, 'provided by Fixture Org A, Fixture Org B, listed by MedlinePlus') && str_contains($html, 'not medical recommendations'), 'Same-window links, attribution and the non-clinical notice must render.');
  $related_path = preg_quote($two->toUrl()->toString(), '#');
  act_expect((bool) preg_match('#<a href="' . $related_path . '"[^>]*>Explore: Synthetic Topic Two</a>#', $html), 'Imported related topics link locally without an external notice.');
  $html = (string) $renderer->renderInIsolation($builder->build($engine->recommend($one), 'new_window'));
  act_expect(str_contains($html, 'target="_blank"') && str_contains($html, 'opens in a new tab'), 'New-window links must be announced.');
  act_expect($builder->build([], 'same_window') === [], 'No CTAs render nothing.');

  // 7. Search form integration through the form-alter hook.
  $form = ['results' => [0 => ['#med_topic' => $one, '#med_enrichment' => $enricher->enrich($one)], 'heading' => []]];
  // Other modules' form alters expect a form object.
  $form_object = new class() extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
      return 'med_search';
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
  $form_state = (new FormState())->setFormObject($form_object)->setValue('query', 'more sources please');
  $form_id = 'med_search';
  \Drupal::moduleHandler()->alter('form', $form, $form_state, $form_id);
  act_expect(isset($form['results'][0]['activation']['list']) && !isset($form['results']['heading']['activation']), 'Search results must receive Next steps.');
  $other = ['results' => [0 => ['#med_topic' => $one]]];
  $form_id = 'user_login_form';
  \Drupal::moduleHandler()->alter('form', $other, $form_state, $form_id);
  act_expect(!isset($other['results'][0]['activation']), 'Other forms must not be altered.');

  echo "PASS: configured rules and priority, source-URL CTAs with attribution and reasons, keyword context, limits, disabled rules, attribution requirements, schema validation, external-link labeling and search-form integration.\n";
}
finally {
  $settings->setData($saved_settings)->save();
  foreach ($saved_rules as $id => $values) {
    $rule = ActivationRule::load($id);
    foreach ($values as $key => $value) {
      $rule->set($key, $value);
    }
    $rule->save();
  }
}
