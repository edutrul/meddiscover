#!/usr/bin/env python3
"""Independent source/readback consistency checks. No network or extra packages."""
from collections import Counter, defaultdict
from hashlib import sha256
import itertools
import json
from pathlib import Path
import re
import statistics
from urllib.parse import urlsplit, unquote
import xml.etree.ElementTree as ET

ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/'docs/discovery'
P=json.loads((OUT/'dataset-profile.json').read_text())
raw=(ROOT/P['source']['path']).read_bytes()
assert sha256(raw).hexdigest()==P['source']['sha256']=='b71e4ae9d979fe8061ddb0651326fa78a9db93d5c0d7f8bd55b8fdfee0140e93'
ts=list(ET.fromstring(raw)); ids={t.get('id') for t in ts}; index={t.get('id'):t for t in ts}
assert len(ts)==len(ids)==int(P['source']['root_attributes']['total'])==2033
checks=['Original XML hash and root total']
for scope,prof in P['scopes'].items():
    subset=[t for t in ts if scope=='All' or t.get('language')==scope]
    assert len(subset)==prof['topics']
    sites=[s for t in subset for s in t.findall('site')]
    urls=Counter(s.get('url') for s in sites)
    domains=Counter(urlsplit(s.get('url')).hostname for s in sites)
    assert len(sites)==prof['sites']['occurrences']
    assert dict(urls)==prof['sites']['urls']
    assert dict(domains)==prof['sites']['domains']
    for path,field in prof['fields'].items():
        counts=[len(t.findall(path)) for t in subset]
        assert sum(counts)==field['occurrences'],(scope,path)
        assert sum(c>0 for c in counts)==field['topics_present']
        assert round(100*sum(c>0 for c in counts)/len(counts),2)==field['coverage_pct']
        st=field['per_topic']
        assert (min(counts),max(counts),round(statistics.mean(counts),2),statistics.median(counts))==(st['min'],st['max'],st['mean'],st['median'])
    for catalog,tag in [('groups','group'),('categories','information-category'),('organizations','organization')]:
        owners=defaultdict(set)
        topicids=defaultdict(set)
        for t in subset:
            for j,parent in enumerate([t] if catalog=='groups' else t.findall('site')):
                for e in parent.findall(tag):
                    key=(e.text or '').strip(); owners[key].add((t.get('id'),j)); topicids[key].add(t.get('id'))
        assert set(owners)==set(prof[catalog])
        for key,v in prof[catalog].items():
            assert len(topicids[key])==v['topic_count']
            if catalog!='groups':assert len(owners[key])==v['resource_count']
    checks.append(scope+': fields, repetition statistics, URL/domain catalogs, groups/categories/organizations')
edges=P['relationships']['edges']
assert dict(Counter(e['type'] for e in edges))==P['relationships']['counts']
for tag in ('related-topic','language-mapped-topic'):
    source_edges=Counter((t.get('id'),e.get('id')) for t in ts for e in t.findall(tag))
    output_edges=Counter((e['source'],e['target']) for e in edges if e['type']==tag)
    assert source_edges==output_edges
    assert all(b in ids for a,b in source_edges)
english={t.get('id') for t in ts if t.get('language')=='English'}
for name,types in [('english_related_graph',{'related-topic'}),('english_related_and_summary_graph',{'related-topic','summary-link'})]:
    adj={i:set() for i in english}
    for e in edges:
        a,b=e['source'],e['target']
        if e['type'] in types and a in english and b in english and a!=b:adj[a].add(b);adj[b].add(a)
    g=P['relationships'][name]
    assert set(g['isolated'])=={i for i,v in adj.items() if not v}
    assert all(d['neighbors']==len(adj[d['id']]) for d in g['degrees'])
for kind,path,attr in [('mesh','mesh-heading/descriptor','id'),('group','group','id'),('resource','site','url')]:
    memberships=defaultdict(set)
    for t in ts:
        if t.get('id') in english:
            for e in t.findall(path):memberships[e.get(attr)].add(t.get('id'))
    pairs=set(pair for vals in memberships.values() for pair in itertools.combinations(sorted(vals),2))
    shared=P['relationships']['english_shared'][kind]
    assert len(pairs)==shared['pair_count']
    assert {'|'.join(pair) for pair in pairs}==set(shared['pairs'])
checks.append('Explicit edge readback, graph degree/isolation, and shared-key projections')
m=json.loads((OUT/'minimal-selection.json').read_text()); sample=m['topics']; sampleids={t['id'] for t in sample}
assert len(sampleids)==len(sample)==30
assert Counter(t['language'] for t in sample)=={'English':24,'Spanish':6}
for t in sample:
    original=index[t['id']]
    for k in ('title','url','language'):assert t[k]==original.get(k)
selected_sites=[s for tid in sampleids for s in index[tid].findall('site')]
assert len(selected_sites)==m['metrics']['sites']==1122
assert len({s.get('url') for s in selected_sites})==m['metrics']['unique_site_urls']==981
checks.append('30-record manifest, source IDs/titles/URLs, and selected resource counts')
required=['01-dataset-profile.md','02-data-quality.md','03-relationships.md','04-search-opportunities.md','05-user-journeys.md','06-four-layer-framework.md','07-impact-effort-matrix.md','08-minimal-dataset.md','09-data-gaps-and-risks.md','EXECUTIVE-SUMMARY.md','dataset-profile.json']
for name in required:assert (OUT/name).stat().st_size>0
for path in OUT.glob('*.md'):
    text=path.read_text()
    assert text.count('```')%2==0,path
    for target in re.findall(r'\]\(([^)]+)\)',text):
        if target.startswith(('<http','http')):continue
        target=unquote(target.split('#')[0].strip('<>'))
        if target:assert (path.parent/target).exists(),(path,target)
checks.append('All requested artifacts exist, local Markdown links resolve, code fences balance')
result=dict(status='passed',source_sha256=P['source']['sha256'],checks=checks,limitations=['No DTD validation: remote DTD unavailable','No external link availability checks','No medical correctness or retrieval-performance certification'])
(OUT/'verification.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps(result,indent=2))
