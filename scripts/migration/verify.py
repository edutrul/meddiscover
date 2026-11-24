#!/usr/bin/env python3
"""Independently compare Drupal readback with the immutable XML source."""
from collections import Counter
from datetime import datetime
from hashlib import sha256
from html.parser import HTMLParser
import json
from pathlib import Path
import subprocess
from urllib.parse import urljoin, urldefrag
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[2]
manifest = json.loads((ROOT / 'docs/discovery/implementation-selection.json').read_text())
raw = (ROOT / 'data/raw/mplus_topics_2026-11-24.xml').read_bytes()
assert sha256(raw).hexdigest() == manifest['source_sha256']
xml = ET.fromstring(raw)
source = {t.get('id'): t for t in xml}
selected = [source[t['id']] for t in manifest['topics']]
by_url = {t.get('url'): t.get('id') for t in xml}

def readback():
    return json.loads(subprocess.check_output(['ddev', 'drush', 'php:script', 'scripts/migration/readback.php'], cwd=ROOT, text=True))

def values(row, name, key='value'):
    return [x[key] for x in row['fields'].get('field_med_' + name, []) if x.get(key) is not None]

def value(row, name, key='value'):
    return next(iter(values(row, name, key)), '')

class Links(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.hrefs = []
    def handle_starttag(self, tag, attrs):
        if tag == 'a':
            href = dict(attrs).get('href', '')
            url = urljoin('https://medlineplus.gov/', href)
            if href.startswith(('https://', 'http://', '/')):
                self.hrefs.append(url)

class Safe(HTMLParser):
    def handle_starttag(self, tag, attrs):
        assert tag in {'p', 'a', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'br', 'h2', 'h3', 'h4', 'blockquote'}
        for key, val in attrs:
            assert tag == 'a' and key in {'href', 'title'}
            if key == 'href':
                assert val.startswith(('https://', 'http://'))

before = readback()
nodes = before['nodes']
terms = {str(t['id']): t for t in before['terms']}
topics = {value(n, 'source_id'): n for n in nodes if n['bundle'] == 'med_health_topic'}
assert len(topics) == sum(n['bundle'] == 'med_health_topic' for n in nodes) == 24
assert set(topics) == {t.get('id') for t in selected}
node_to_source = {str(n['id']): sid for sid, n in topics.items()}
resources = {value(n, 'key'): n for n in nodes if n['bundle'] == 'med_resource'}
relations = {value(n, 'key'): n for n in nodes if n['bundle'] == 'med_relationship'}
assert len(resources) == 882
assert len(resources) == sum(n['bundle'] == 'med_resource' for n in nodes)
assert len(relations) == sum(n['bundle'] == 'med_relationship' for n in nodes)
expected_relations = 0
expected_types = Counter()
for topic in selected:
    sid = topic.get('id')
    row = topics[sid]
    assert row['label'] == topic.get('title') and row['language'] == 'en'
    assert value(row, 'description') == topic.get('meta-desc').strip()
    assert value(row, 'summary_raw') == topic.findtext('full-summary', '')
    assert value(row, 'summary_plain')
    Safe().feed(value(row, 'summary_html'))
    assert row['fields']['field_med_summary_html'][0]['format'] == 'med_source_html'
    assert value(row, 'source_url', 'uri') == topic.get('url')
    assert value(row, 'source_date') == datetime.strptime(topic.get('date-created'), '%m/%d/%Y').strftime('%Y-%m-%d')
    assert value(row, 'snapshot_date') == '2026-11-24'
    assert value(row, 'attribution') == manifest['attribution']
    for field, tag in [('also_called', 'also-called'), ('see_reference', 'see-reference')]:
        assert values(row, field) == [(e.text or '').strip() for e in topic.findall(tag)]
    actual_groups = [terms[str(i) ] for i in values(row, 'groups', 'target_id')]
    assert [(value(t, 'source_id'), t['label'], value(t, 'source_url', 'uri')) for t in actual_groups] == [(g.get('id'), (g.text or '').strip(), g.get('url')) for g in topic.findall('group')]
    expected_mesh = []
    for h in topic.findall('mesh-heading'):
        d = h.find('descriptor')
        for q in h.findall('qualifier') or [None]:
            expected_mesh.append((d.get('id'), d.text.strip(), q.get('id') if q is not None else '', q.text.strip() if q is not None else ''))
    actual_mesh = [terms[str(i)] for i in values(row, 'mesh', 'target_id')]
    assert [(value(t, 'source_id'), value(t, 'descriptor_label'), value(t, 'qualifier_id'), value(t, 'qualifier_label')) for t in actual_mesh] == expected_mesh
    for ordinal, site in enumerate(topic.findall('site')):
        r = resources[f'{sid}:site:{ordinal}']
        assert r['label'] == site.get('title').strip()
        assert node_to_source[str(value(r, 'topic', 'target_id'))] == sid
        assert value(r, 'url', 'uri') == site.get('url')
        assert value(r, 'mapped_url', 'uri') == site.get('language-mapped-url', '')
        for field, tag in [('organizations', 'organization'), ('standard_desc', 'standard-description')]:
            assert values(r, field) == [(e.text or '').strip() for e in site.findall(tag)]
        assert [terms[str(i)]['label'] for i in values(r, 'categories', 'target_id')] == [(e.text or '').strip() for e in site.findall('information-category')]
    for tag, relation_type in [('related-topic', 'curated'), ('language-mapped-topic', 'language_mapping')]:
        for ordinal, target in enumerate(topic.findall(tag)):
            r = relations[f'{sid}:{relation_type}:{ordinal}']
            expected_relations += 1
            expected_types[relation_type] += 1
            assert value(r, 'target_source_id') == target.get('id')
            assert value(r, 'target_title') == (target.text or '').strip()
            assert value(r, 'target_url', 'uri') == target.get('url')
    links = Links(); links.feed(topic.findtext('full-summary', ''))
    for ordinal, url in enumerate(links.hrefs):
        r = relations[f'{sid}:summary_link:{ordinal}']
        expected_relations += 1
        expected_types['summary_link'] += 1
        assert value(r, 'target_url', 'uri') == url
        assert value(r, 'target_source_id') == by_url.get(urldefrag(url)[0], '')
for r in relations.values():
    assert node_to_source[str(value(r, 'topic', 'target_id'))] in topics
    target = value(r, 'target_source_id')
    ref = value(r, 'target', 'target_id')
    assert (node_to_source[str(ref)] == target) if target in topics else not ref
    assert value(r, 'provenance') == 'MedlinePlus XML: ' + value(r, 'relation_type')
assert len(relations) == expected_relations
assert Counter(value(r, 'relation_type') for r in relations.values()) == expected_types
assert len({value(r, 'url', 'uri') for r in resources.values()}) == 744
assert Counter(t['bundle'] for t in terms.values()) == {'med_groups': 16, 'med_mesh': 30, 'med_resource_categories': 26}
# A second import must preserve IDs and every field, not just aggregate counts.
result = subprocess.run(['ddev', 'drush', 'migrate:import', '--tag=med', '--execute-dependencies'], cwd=ROOT, text=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
if result.returncode:
    raise SystemExit(result.stdout)
after = readback()
assert sorted(before['nodes'], key=lambda x: int(x['id'])) == sorted(after['nodes'], key=lambda x: int(x['id']))
assert sorted(before['terms'], key=lambda x: int(x['id'])) == sorted(after['terms'], key=lambda x: int(x['id']))
report = {'status': 'passed', 'topics': len(topics), 'resources': len(resources), 'unique_resource_urls': 744, 'relationships': len(relations), 'relationship_types': dict(expected_types), 'taxonomies': dict(Counter(t['bundle'] for t in terms.values())), 'checks': ['source integrity', 'metadata and aliases', 'group and MeSH pairing', 'resource fields and multiple values', 'internal/external relationships', 'safe HTML', 'rerun preserves all IDs and fields']}
(ROOT / '.local').mkdir(exist_ok=True)
(ROOT / '.local/phase-02-verification.json').write_text(json.dumps(report, indent=2) + '\n')
print(json.dumps(report, indent=2))
