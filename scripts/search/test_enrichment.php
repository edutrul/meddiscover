<?php

/**
 * @file
 * Enrichment contract checks on the synthetic fixture, never production.
 */

use Drupal\med_search\ResultEnricher;
use Drupal\node\Entity\Node;

/**
 * Fail the run with a clear message.
 */
function enrich_expect(bool $value, string $message): void {
  if (!$value) {
    throw new RuntimeException($message);
  }
}

$storage = \Drupal::entityTypeManager()->getStorage('node');
$topics = $storage->loadByProperties(['type' => 'med_health_topic']);
enrich_expect(count($topics) === 2, 'Tests require the two-topic synthetic migration database.');
$by_source = [];
foreach ($topics as $topic) {
  enrich_expect(str_starts_with($topic->field_med_source_id->value, 'test-'), 'Refusing production corpus.');
  $by_source[$topic->field_med_source_id->value] = $topic;
}
$one = $by_source['test-1'];
$enricher = \Drupal::service('med_search.enricher');
$created = [];

/**
 * Create a disposable synthetic topic sharing taxonomy with topic one.
 */
function enrich_topic(array &$created, string $id, array $values): Node {
  $node = Node::create($values + [
    'type' => 'med_health_topic',
    'title' => "Synthetic Inferred $id",
    'langcode' => 'en',
    'status' => 1,
    'moderation_state' => ($values['status'] ?? 1) === 0 ? 'draft' : 'published',
    'field_med_source_id' => "test-inferred-$id",
    'field_med_source_url' => "https://medlineplus.gov/test-inferred-$id.html",
  ]);
  $node->save();
  $created[] = $node;
  return $node;
}

try {
  // Explicit relationships only: no inferred topics exist yet.
  $data = $enricher->enrich($one);
  enrich_expect($data['also_called'] === ['Fixture Alias'] && $data['see_reference'] === ['Ambiguous Fixture'], 'Alternative terms missing.');
  enrich_expect($data['groups'] === [['label' => 'Synthetic Group', 'url' => 'https://medlineplus.gov/testgroup.html']], 'Medical group missing.');
  enrich_expect($data['mesh'] === [['descriptor' => 'Synthetic Descriptor', 'qualifier' => 'Synthetic Qualifier']], 'MeSH pairing missing.');
  $related = array_column($data['related'], NULL, 'title');
  enrich_expect(array_keys($related) === ['Synthetic Topic Two', 'Outside Topic'], 'Related content must be curated order, deduplicated, without self or language mappings: ' . implode(', ', array_keys($related)));
  $two = $related['Synthetic Topic Two'];
  enrich_expect($two['explicit'] && $two['node'] && $two['node']->id() === $by_source['test-2']->id(), 'In-corpus related topic must link locally.');
  enrich_expect($two['provenance'] === ['Related topic listed by MedlinePlus', 'Linked in the topic summary'], 'Provenance from both sources must be preserved on the deduplicated item.');
  $outside = $related['Outside Topic'];
  enrich_expect($outside['node'] === NULL && $outside['url'] === 'https://medlineplus.gov/outside.html', 'Out-of-corpus topic must link to its original source.');
  enrich_expect(!str_contains(json_encode(array_column($data['related'], 'title')), 'Spanish'), 'Language mappings are not related content.');
  enrich_expect(array_keys($data['resources']) === ['Learn More', 'Patient Handouts'], 'Resources must be grouped by category in display order.');
  foreach ($data['resources'] as $items) {
    enrich_expect($items === [[
      'title' => 'Synthetic Resource',
      'url' => 'https://example.org/resource',
      'organizations' => ['Fixture Org A', 'Fixture Org B'],
    ]], 'Resource URL and organization attribution must be preserved.');
  }
  enrich_expect($enricher->enrich($by_source['test-2'])['resources'] === ['Learn More' => [[
    'title' => 'Shared URL Resource',
    'url' => 'https://example.org/resource',
    'organizations' => [],
  ]]], 'Resources belong only to their own topic.');

  // A non-topic summary link is further reading, not a related topic.
  $edge = Node::create([
    'type' => 'med_relationship',
    'moderation_state' => 'published',
    'title' => 'Synthetic further reading',
    'field_med_key' => 'test-1:summary_link:99',
    'field_med_topic' => $one->id(),
    'field_med_relation_type' => 'summary_link',
    'field_med_provenance' => 'MedlinePlus XML: summary_link',
    'field_med_target_title' => 'lab test',
    'field_med_target_url' => 'https://medlineplus.gov/lab-tests/example/#section',
  ]);
  $edge->save();
  $created[] = $edge;

  // Inferred associations: one shares MeSH and group, five share the group,
  // one is unpublished. Only INFERRED_LIMIT published ones may appear.
  $mesh_and_group = enrich_topic($created, 'a', [
    'title' => 'Synthetic Inferred 0 MeSH',
    'field_med_mesh' => $one->field_med_mesh->getValue(),
    'field_med_groups' => $one->field_med_groups->getValue(),
  ]);
  foreach (range(1, 5) as $i) {
    enrich_topic($created, "g$i", ['title' => "Synthetic Inferred $i Group", 'field_med_groups' => $one->field_med_groups->getValue()]);
  }
  enrich_topic($created, 'hidden', ['title' => 'Synthetic Inferred Unpublished', 'status' => 0, 'field_med_groups' => $one->field_med_groups->getValue()]);
  $storage->resetCache();

  $data = $enricher->enrich($storage->load($one->id()));
  enrich_expect($data['summary_links'] === [['title' => 'lab test', 'url' => 'https://medlineplus.gov/lab-tests/example/#section']], 'Non-topic summary link must be further reading.');
  $titles = array_column($data['related'], 'title');
  enrich_expect(array_slice($titles, 0, 2) === ['Synthetic Topic Two', 'Outside Topic'], 'Explicit relationships must rank before inferred associations.');
  $inferred = array_values(array_filter($data['related'], fn($r) => !$r['explicit']));
  enrich_expect(count($inferred) === ResultEnricher::MED_INFERRED_LIMIT, 'Inferred associations must respect the limit.');
  enrich_expect($inferred[0]['title'] === 'Synthetic Inferred 0 MeSH' && $inferred[0]['source'] === 'shared_mesh', 'Shared MeSH must rank before shared groups.');
  enrich_expect($inferred[0]['provenance'] === ['Shares MeSH descriptor: Synthetic Descriptor', 'Same MedlinePlus group: Synthetic Group'], 'Inferred provenance must be explicit and deduplicated.');
  enrich_expect(!in_array('Synthetic Inferred Unpublished', $titles, TRUE), 'Unpublished topics must not be recommended.');
  enrich_expect(count($titles) === count(array_unique($titles)), 'Related content must not contain duplicates.');
  enrich_expect($mesh_and_group->id() === $inferred[0]['node']->id(), 'Inferred in-corpus topic must link locally.');
  enrich_expect(ResultEnricher::normalizeUrl('https://MedlinePlus.gov/a/#x') === ResultEnricher::normalizeUrl('https://medlineplus.gov/a'), 'URL normalization must ignore case, fragment and trailing slash.');

  $start = hrtime(TRUE);
  $enricher->enrich($one);
  echo 'PASS: metadata, explicit-first related content, provenance, deduplication, out-of-corpus links, inferred limits, unpublished exclusion and grouped resources (' . round((hrtime(TRUE) - $start) / 1e6, 1) . " ms).\n";
}
finally {
  foreach (array_reverse($created) as $node) {
    $node->delete();
  }
}
