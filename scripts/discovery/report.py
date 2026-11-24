#!/usr/bin/env python3
"""Render evidence tables and editorial recommendations from analyze.py output."""
from collections import Counter, defaultdict
import json
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'docs/discovery'
P = json.loads((OUT/'dataset-profile.json').read_text())
S, R, Q, G = P['scopes'], P['records'], P['quality'], P['relationships']
EN = S['English']
XML = ET.parse(ROOT/P['source']['path']).getroot()

def table(headers, rows):
    def cell(x): return str(x).replace('|','\\|').replace('\n',' ')
    return '\n'.join(['| '+' | '.join(headers)+' |','| '+' | '.join(['---']*len(headers))+' |']+['| '+' | '.join(map(cell,row))+' |' for row in rows])+'\n'

def write(name, title, body):
    (OUT/name).write_text(f'# {title}\n\nPhase 0 · snapshot generated {P["source"]["root_attributes"]["date-generated"]} · analysis date 2026-11-24.\n\n'+body.strip()+'\n')

def topic(tid):
    t=R[tid]
    return f'[{t["title"]}](<{t["url"]}>) (ID {tid})'

def titles(ids): return '; '.join(topic(i) for i in ids) or 'None'
def pct(n,d): return f'{100*n/d:.2f}%'
def scope_rows(field):
    for path in EN[field]:
        yield [path]+[str(S[sc][field][path]['occurrences'])+' / '+(str(S[sc][field][path]['coverage_pct'])+'%' if S[sc][field][path]['coverage_pct'] is not None else 'N/A (no owner elements)') for sc in ('All','English','Spanish')]

def field_rows(scope):
    for path,x in S[scope]['fields'].items():
        st=x['per_topic']
        yield [path,x['topics_present'],f'{x["coverage_pct"]:.2f}%',x['occurrences'],st['min'],st['max'],st['mean'],st['median'],x['unique_leaf_values']]

def catalog_rows(key):
    names=sorted({n for sc in ('English','Spanish') for n in S[sc][key]})
    for name in names:
        yield [name]+[S[sc][key].get(name,{}).get('topic_count' if key=='groups' else 'resource_count',0) for sc in ('English','Spanish')]

source_note = f'''The full local XML was parsed offline with Python standard library `xml.etree.ElementTree`, `html.parser`, `urllib.parse`, `statistics`, and counters/sets. No resource website was crawled or checked for availability. Only NLM schema/usage documentation was consulted online; those are metadata references, not additional datasets.

Source: `{P['source']['path']}`; **{P['source']['bytes']:,} bytes** ({P['source']['bytes']/1_000_000:.2f} MB / {P['source']['bytes']/1048576:.2f} MiB). SHA-256: `{P['source']['sha256']}`. The root declares 2,033 records; parsed count agrees. The XML is well-formed. External DTD resolution is disabled by the parser.

Reproduce from the project root:

```sh
rtk proxy python3 scripts/discovery/analyze.py
rtk proxy python3 scripts/discovery/report.py
rtk proxy python3 scripts/discovery/verify.py
```

`dataset-profile.json` is a discovery evidence artifact, not a database or the proposed POC payload. It includes all per-language unique leaf/attribute values and counts, exact resource URLs and hostnames, record evidence, relationship edges, inferred pair projections, and all flagged issues. No search engine, embeddings, LLM calls, or database was created. Full-summary values in that inventory are XML-decoded HTML strings; the record evidence contains extracted plain text.
'''

body=source_note+'\n## Counts and definitions\n\n'+table(['Scope','Topics','Site assignments','Unique site URLs','Off-MedlinePlus site assignments','Unique site hostnames'],[[sc,S[sc]['topics'],S[sc]['sites']['occurrences'],S[sc]['sites']['unique_urls'],S[sc]['sites']['outside_medlineplus_occurrences'],S[sc]['sites']['unique_domains']] for sc in ('All','English','Spanish')])
body+='''
A `site` is an external-resource link assignment on a topic, even when it points to another MedlinePlus service. Off-MedlinePlus means hostname other than `medlineplus.gov` or `www.medlineplus.gov`. Counts of assignments, distinct URLs, and distinct documents are different: identical URLs can be reused by many topics, while different URLs can lead to the same document. Domains below mean hostnames retaining subdomains, not registrable domains. Exact URL identity preserves case, query string, trailing slash, and fragment. Unique counts across languages are not additive.

Coverage is the percentage of topics containing at least one occurrence of a field. Attribute coverage uses the number of its owning XML elements as denominator. Repeating-element statistics include zero occurrences for all topics in each scope; nested occurrences are counted per topic. `unique_leaf_values=0` for containers means not applicable, not empty content. Optionality is separate from coverage.

Strictly off-MedlinePlus resource identities:

'''+table(['Scope','Off-host unique URLs','Off-host unique hostnames'],[[sc,S[sc]['sites']['outside_medlineplus_unique_urls'],S[sc]['sites']['outside_medlineplus_unique_domains']] for sc in S])+'''

Embedded summary hyperlinks are a separate asset, not included in site-resource totals:

'''+table(['Scope','Topics with href','Href occurrences','Unique resolved href URLs','Off-MedlinePlus href occurrences'],[[sc,S[sc]['summary_hyperlinks']['topics_present'],S[sc]['summary_hyperlinks']['occurrences'],S[sc]['summary_hyperlinks']['unique_resolved_urls'],S[sc]['summary_hyperlinks']['outside_medlineplus_occurrences']] for sc in S])+'''

## XML vocabulary: every observed element and attribute

'''
body+=table(['Element path','Occurrences, all languages','Observed attributes'],[[k,v['occurrences'],', '.join(v['attributes']) or 'None'] for k,v in P['xml_inventory'].items()])
body+='''
### Required versus optional: documentation evidence and DTD limitation

The DOCTYPE references [mplus_topics.dtd](https://medlineplus.gov/xml/mplus_topics.dtd). No DTD exists in this workspace; direct retrieval failed because shell networking could not resolve the host and the web reader could not open this DTD. **DTD cardinalities, attribute declarations, order constraints, and formal validation remain unverified.** The table uses the [NLM XML description](https://medlineplus.gov/xmldescription.html), accessed 2026-11-24, as a documentation fallback. It must not be represented as a parsed DTD.

'''+table(['Field','Published guide expectation; not DTD validation'],[
('full-summary','One per topic'),('group; site','At least one each per topic'),('also-called; see-reference; related-topic; other-language','Optional, repeating'),('language-mapped-topic','Optional, maximum one'),('primary-institute','Optional'),('mesh-heading / descriptor','Heading may repeat; contains descriptor'),('qualifier','Observed in XML; requirement not verified'),('site / organization; standard-description','Can repeat; organization may be absent'),('site / information-category','Can repeat'),('site @language-mapped-url','Optional'),('All other XML attributes','Observed below; required/default status not verified')])
body+='\n### Actual attribute coverage and cardinality\n\nCells show occurrences / percentage of owner elements. Root attributes `total` and `date-generated` each occur once (100%); their values are in source metadata.\n\n'+table(['Attribute relative to health-topic','All','English','Spanish'],scope_rows('attributes'))
for sc in ('All','English','Spanish'):
    body+=f'\n## Field coverage and repetition — {sc}\n\n'+table(['Field','Topics present','Coverage','Occurrences','Min/topic','Max/topic','Mean/topic','Median/topic','Unique leaf values'],field_rows(sc))
body+='\n## Full-summary lengths\n\nHTML is decoded once by the XML parser and interpreted with HTMLParser; plain text collapses whitespace. Word counts split on whitespace, not a medical tokenizer. The single absent Spanish summary counts as length zero. Percentiles use linear interpolation at `(n-1)*p`.\n\n'
body+=table(['Scope / unit','Min','P10','P25','Median','Mean','P75','P90','P95','Max'],[[sc+' / '+unit]+[st[k] for k in ('min','p10','p25','median','mean','p75','p90','p95','max')] for sc in S for unit,st in S[sc]['summary_lengths'].items()])
body+='\n## MeSH descriptors and qualifiers\n\n'+table(['Scope','Topics with descriptor','Coverage','Descriptor assignments','Distinct descriptor IDs','Topics with qualifier','Qualifier assignments','Distinct qualifier IDs'],[[sc,S[sc]['fields']['mesh-heading/descriptor']['topics_present'],str(S[sc]['fields']['mesh-heading/descriptor']['coverage_pct'])+'%',S[sc]['fields']['mesh-heading/descriptor']['occurrences'],len(S[sc]['mesh_descriptor_ids']),S[sc]['fields']['mesh-heading/qualifier']['topics_present'],S[sc]['fields']['mesh-heading/qualifier']['occurrences'],len(S[sc]['mesh_qualifier_ids'])] for sc in S])
body+='\nAll qualifier values (English only):\n\n'+table(['Qualifier','Assignments'],EN['unique_values']['mesh-heading/qualifier'].items())
body+='\n## All medical groups and topic counts\n\nLabels are language-specific; 88 labels represent 44 group IDs. Exact IDs, URLs, and topic memberships are retained in JSON. Counts represent distinct topics per label.\n\n'+table(['Group label','English topics','Spanish topics'],catalog_rows('groups'))
body+='\n## All information categories and resource counts\n\nCounts are distinct site assignments carrying each label; a multi-category resource contributes to each category. Totals exceed resource counts. `NON-DISPLAY (Patient Handout)` is source metadata and should be excluded from public display pending policy review.\n\n'+table(['Category label','English resources','Spanish resources'],catalog_rows('categories'))
body+='\n## All organizations and resource counts\n\nEach resource can credit several organizations. Missing organization does not imply an untrusted resource. Exact labels are preserved without entity merging.\n\n'+table(['Organization label','English resources','Spanish resources'],catalog_rows('organizations'))
body+='\n## All resource hostnames\n\n'+table(['Hostname','All assignments','English assignments','Spanish assignments'],[[host,S['All']['sites']['domains'].get(host,0),S['English']['sites']['domains'].get(host,0),S['Spanish']['sites']['domains'].get(host,0)] for host in sorted(S['All']['sites']['domains'])])
body+='\n## Unique values and representative XML\n\nEvery field’s exact unique leaf or attribute values and frequencies are in `scopes.<scope>.unique_values`; cardinalities are in `fields` and `attributes`. This includes high-cardinality summaries, titles, dates, URLs, aliases, descriptor IDs, and low-cardinality language/resource labels. `groups`, `categories`, and `organizations` also list topic memberships. Root values are in `source.root_attributes`.\n\nSelected elements below are serialized from original record 6308 (omitted elements are not shown):\n\n```xml\n'
t=next(t for t in XML if t.get('id')=='6308')
example=ET.Element(t.tag,t.attrib)
for ch in t:
    if ch.tag in ('also-called','group','language-mapped-topic','mesh-heading','see-reference'): example.append(ch)
body+=ET.tostring(example,encoding='unicode').strip()+'\n```\n'
body+='\nAdditional exact elements serialized from source: a qualified MeSH heading from How to Prevent Diabetes (6661), and a directory resource from Knee Injuries and Disorders (304).\n\n```xml\n'
prevent=next(t for t in XML if t.get('id')=='6661')
knee=next(t for t in XML if t.get('id')=='304')
body+=ET.tostring(prevent.find('mesh-heading'),encoding='unicode').strip()+'\n'
body+=ET.tostring(next(s for s in knee.findall('site') if 'Find a Doctor' in s.get('title','')),encoding='unicode').strip()+'\n```\n'
write('01-dataset-profile.md','Dataset profile',body)

quality_rows=[
('Duplicate topic IDs',len(Q['duplicate_ids']),'Exact, all languages'),('Duplicate canonical topic URLs',len(Q['duplicate_topic_urls']),'Exact URL'),('Duplicate titles',len(Q['duplicate_titles_exact']),'Exact within language'),('Duplicate normalized titles',len(Q['duplicate_titles_normalized']),'NFKC/casefold/whitespace, within language'),('Repeated normalized alias keys',len(Q['duplicate_aliases']),'Includes repeat across also-called and see-reference'),('Aliases assigned to multiple topics',len(Q['cross_topic_alias_collisions']),'109 English; 50 Spanish; normalized within language'),('Alias capitalization-variant keys',len(Q['capitalization_variants']),'256 English; 2 Spanish; editorial differences, not necessarily defects'),('Duplicate site URLs within a topic',len(Q['duplicate_site_urls_within_topic']),'Exact URL; cross-topic reuse analyzed separately'),('Empty leaf/attribute values',len(Q['empty_values']),'Empty meta-desc only'),('Missing full-summary',len(Q['missing_full_summary']),'Spanish ID 1900'),('URL occurrences flagged syntactically',len(Q['invalid_urls']),'566 other-language fragment strings + 2 summary links'),('Broken related/mapped ID references',len(Q['broken_id_references']),'Both languages; all targets found'),('Reference URL/title/language mismatches',len(Q['reference_metadata_mismatches']),'Referenced target metadata compared'),('Summary HTML present',Q['summary_markup_topics'],'Of 2,033; one summary absent'),('Summaries with selected markup anomalies',len(Q['summary_markup_anomalies']),'Heuristic tag/attribute checks; not HTML validation')]
body='''## Findings and interpretation

'''+table(['Check','Count','Definition / interpretation'],quality_rows)
body+='''
The XML is well-formed; no claim of DTD-validity or HTML-validity is made. Element presence is not equivalent to nonempty content or clinical completeness. All issue instances are retained under `quality` in the JSON, including topic IDs and paths.

There are **50 exact title strings repeated across languages**, such as Alcohol (1558 English / 1732 Spanish) and Anemia (139 English / 1743 Spanish). These are legitimate bilingual labels, not same-language duplicate topics. The full cross-language title inventory is preserved separately in JSON.

## Missing, empty, and optional metadata

'''+table(['Issue','Real records / evidence','Design consequence'],[
('Empty meta-desc',topic('6055'),'Fall back to an attributed summary excerpt; do not invent description'),('Missing full-summary',topic('1900'),'Render explicit unavailable state; preserve source link'),('No English MeSH descriptor',titles(EN['missing_mesh']),'Do not require MeSH for inclusion'),('No Spanish MeSH descriptors','1,016 / 1,016 topics','Join via verified language mapping if using English MeSH; label inherited metadata'),('No outgoing related-topic','179 English / 227 Spanish','Absence does not mean no incoming edges or no relevant topic'),('No primary-institute','209 English topics','Do not require an institute label'),('No language mapping',topic('5526'),'Disable Spanish switch for this record')])
body+='\nResource child coverage, with **site assignments as denominator**:\n\n'
rows=[]
for sc in ('All','English','Spanish'):
    ts=[t for t in R.values() if sc=='All' or t['language']==sc]
    sites=[site for t in ts for site in t['sites']]
    for field in ('categories','organizations','descriptions'):
        n=sum(bool(s[field]) for s in sites)
        counts=[len(s[field]) for s in sites]
        rows.append([sc,field,n,len(sites)-n,pct(n,len(sites)),min(counts),max(counts),round(sum(counts)/len(counts),2)])
body+=table(['Scope','Site field','Present','Absent','Coverage','Min/site','Max/site','Mean/site'],rows)
body+='''
All observed topic and site attributes and topic-level absence rates are enumerated in [01](01-dataset-profile.md). Optional missing aliases, resource descriptions, institute, or qualifiers are not treated as errors. `date-created` is present but is not a revision date. No unexpected empty literal leaf nodes were found.

Although meta-desc attribute presence is 100%, nonempty coverage is 1,016/1,017 English topics (99.90%) and 2,032/2,033 overall (99.95%); Spanish descriptions are all nonempty. All other observed literal leaf/attribute values are nonempty. The missing Spanish summary is an absence, separate from an empty value.

## Duplicate URLs and aliases

'''+table(['Alias duplicate definition','Repeated keys'],[['Exact language + alias text (all topics/alias fields)',len(Q['duplicate_aliases_exact'])],['Normalized alias repeated within a topic (across alias fields)',len(Q['duplicate_aliases_within_topic'])],['Normalized alias repeated within the same topic and field',len(Q['duplicate_aliases_within_same_field'])]])+'''

'''+table(['Scope','Distinct site URLs reused across topics','Assignments beyond first occurrence'],[[sc,sum(v>1 for v in S[sc]['sites']['urls'].values()),S[sc]['sites']['occurrences']-S[sc]['sites']['unique_urls']] for sc in S])
body+='''
Repeated site URLs across topics are expected reusable resources; retain topic-resource assignments and optionally deduplicate resource entities by exact URL. Query strings/fragments can be meaningful, so no aggressive canonicalization was used.

An alias is a retrieval cue, not an equivalence proof. The 811 repeated normalized alias keys include within-topic repeats and cross-topic collisions, not 811 duplicate topics. The 159 title-plus-alias ambiguity keys happen to match the alias-only collision count in this snapshot. No cross-language duplicate is automatically an error.

Representative collisions and capitalization variants:

'''
rows=[]
for key in ['English|allergic rhinitis','English|vascular headache','English|sugar diabetes','English|art','English|stomach ache','English|neuropathy']:
    entries=Q['cross_topic_alias_collisions'].get(key,[])
    rows.append([key,titles(sorted({x['topic'] for x in entries})),'Offer multiple labeled results; no one-to-one rewrite'])
body+=table(['Normalized alias','Topics','Handling'],rows)
body+='\nA1C (6308) uses `HbA1C` in also-called and `HbA1c` in see-reference. Preserve original display strings and use case-insensitive matching. The same normalization handles `Blood sugar` / `Blood Sugar` on Blood Glucose (6121). Do not rewrite scientific acronym casing globally. Accent removal, plural handling, and stemming were not applied in these statistics.\n'
body+='\n## URL syntax and internal integrity\n\n'+table(['Path / issue','Occurrences'],Counter(x['path'] for x in Q['invalid_urls']).items())
body+='''
These are **syntax warnings**, not failed HTTP checks. Most are spaces in other-language fragment identifiers that browsers may repair. The check flags whitespace/control/unsafe characters, malformed percent escapes, non-HTTP(S) schemes, missing/suspicious hostnames, parser errors, invalid ports, and userinfo. It is a conservative heuristic, not exhaustive RFC/IRI validation; Unicode hostnames and unencoded URL characters can require additional handling. Original URLs are preserved.

'''+table(['Topic','Summary href','Issue'],[[topic(x['topic']),x['url'],'; '.join(x['issues'])] for x in Q['invalid_urls'] if x['path']=='full-summary/a/@href'])
body+='''
All 6,289 related-topic and 2,032 language-mapped-topic ID references resolve, with matching target URLs, normalized titles, and mapping language. Summary URLs resolve against the source topic URL, with fragments removed only for local topic lookup. **735 MedlinePlus summary href occurrences do not resolve to a topic record**: many are lab tests, encyclopedia, or genetics pages outside this topic corpus. They are not proven broken links. The full list is in JSON; link availability, redirect targets, and fragment existence were not tested.

### Topic-shaped summary targets absent from the snapshot

'''+table(['Source topic','Original href'],[[topic(x['topic']),x['url']] for x in Q['unmatched_topic_shaped_summary_links']])+'''

These 10 href occurrences match the usual topic URL path shape but have no exact canonical target in this snapshot. They are candidate stale topic references, potentially old slugs or redirects. Redirects were not followed, so their availability and intended replacement are unknown. Preserve the original href and flag for source-owner review; do not automatically map an old slug by guessing. Other unmatched internal hrefs chiefly target non-topic services.

## HTML inside summaries

The XML stores escaped HTML, which becomes a string after parsing. 2,032 summaries contain markup; the missing summary is Spanish 1900. Strip HTML for indexing and sanitize with an explicit allowlist before any future display; do not blindly render source HTML. These are design recommendations only.

'''+table(['Observed HTML tag','Occurrences'],Q['summary_html_tags'].items())
body+='\nSelected anomalies (heuristic):\n\n'+table(['Topic','Flagged tags / attributes'],[[topic(x['topic']),', '.join(k for k in x['tags'] if k in ('topic','ph3','p.','style'))+('; a@hre' if 'a@hre' in x['attributes'] else '')] for x in Q['summary_markup_anomalies']])
body+='''
Examples include nonstandard `<topic>`, `<ph3>`, `<p.>`, an `a@hre` typo, and inline style content. HTMLParser is tolerant: balanced tags, browser rendering, malicious payload absence, and complete HTML semantics were not certified. The JSON records all observed HTML tags and attribute keys. Eight image tags are not permission to reproduce images. No executable UI was created.

## Ambiguous terminology and review priorities

`Vascular Headache` maps to Headache and Migraine; `Allergic Rhinitis` maps to Allergy and Hay Fever; `Sugar Diabetes` maps to Diabetes and Diabetes Type 2. `ART` maps to two unrelated topic labels (see collision inventory); acronyms such as `OA`, `RA`, `DM`, and `DI` can also be ambiguous outside this corpus even when locally unique. These latter ambiguity risks are editorial judgments, not measured clinical ambiguity scores. “Knee pain” is a symptom expression, not a diagnosis or a unique disease alias.

Recommended order: preserve provenance; handle the absent summary and empty description; normalize matching while preserving display; expose collisions as choices; sanitize summary display; review the two malformed summary hrefs; then review multilingual fragments. Do not silently change the original XML. Clinical terminology and user-facing safety text need qualified review before public release.
'''
write('02-data-quality.md','Data quality',body)

body='''## Relationship semantics

'''+table(['Field','Explicitly supplied','Permitted inference / limit'],[
('also-called','Term attached to a topic','Synonym retrieval; no separate topic edge'),('see-reference','Index entry attached to a topic','Search redirect cue; collisions prevent one-to-one equivalence'),('group','Topic membership with ID/name/URL','Shared-group association; broad navigation, not clinical relation'),('mesh-heading / descriptor / qualifier','Controlled descriptor and optional qualifier','Same descriptor gives candidate association; keep qualifier context'),('related-topic','Directed curated topic ID/title/URL','Safe to label “Related topic”; reciprocity not assumed'),('language-mapped-topic','Directed equivalent topic mapping','Spanish switch; equivalence does not mean word-for-word translation'),('other-language','Language name/vernacular and URL','Link-out only; does not add full topic records in 52 languages'),('primary-institute','Assigned institute name/URL','Provenance/ownership context; no clinician referral'),('site','Topic-to-resource assignment','Shared URL gives resource overlap, not clinical equivalence'),('information-category','Resource presentation/category label','Intent grouping only; sharing category gives no clinical relation'),('organization','Resource producer label','Attribution facet; sharing producer gives no clinical relation'),('summary hyperlinks','Author-supplied hyperlinks in prose','Canonical URL lookup resolves topic targets; context is not a typed clinical claim')])
body+='\n## Explicit edges across the full corpus\n\n'+table(['Edge type','Directed occurrences'],G['counts'].items())
body+='''
Summary edges count anchor occurrences whose resolved URL exactly matches a local canonical topic URL after fragment removal; repeated anchors can create repeated edges. Unmatched hyperlinks remain external/non-topic nodes. Related and language mappings use source IDs. No equivalence or cause/treatment relation is inferred from an anchor alone.

## English graph connectivity

An undirected projection is used only for neighborhood and weak-component measurement; source edges remain directed. Degree counts distinct English neighbors across incoming and outgoing edges, excludes self-neighbors, and does not count aliases, language mappings, broad groups, or resources. Incoming/outgoing columns below count edge occurrences.

'''
for name,label in [('english_related_graph','Curated related-topic only'),('english_related_and_summary_graph','Curated related-topic plus resolved summary hyperlinks')]:
    g=G[name]
    body+=f'\n### {label}\n\n{len(g["components"])} weak components; largest has {len(g["components"][0])} topics; {len(g["isolated"])} topics have zero distinct English neighbors.\n\n'
    body+=table(['Topic','Distinct neighbors','Outgoing','Incoming'],[[topic(x['id']),x['neighbors'],x['outgoing'],x['incoming']] for x in g['degrees'][:10]])
    body+='\nIsolated under this definition: '+titles(g['isolated'])+'.\n'
body+='''
179 English topics have no **outgoing** related-topic, but only 20 have no incoming or outgoing curated neighbor. After adding summary topic hyperlinks, only Anatomy (138) and Hiccups (6084) remain isolated. They still have resource/group relationships; “isolated” is graph-definition dependent. Full degree lists/components are in JSON.

## Inferred associations, English topics only

'''+table(['Shared key','Keys shared by >=2 topics','Distinct undirected topic pairs','Meaning'],[[k,len(x['shared_keys']),x['pair_count'],{'mesh':'Shared descriptor ID, qualifiers not collapsed into equivalence','group':'Broad browse membership','resource':'Exact same resource URL'}[k]] for k,x in G['english_shared'].items()])
body+='''
Pairs can overlap across signals. They are not added to the explicit edge counts. Shared descriptor analysis uses descriptor IDs, not spelling, and reports descriptor-only overlap; a qualified heading such as “prevention & control” must retain that qualifier when displayed. Resource overlap includes common institute homepages, which can dominate pair counts. Do not present all shared-resource/group pairs as clinical recommendations.

Real examples:

- Diabetes Type 2 (5930) and How to Prevent Diabetes (6661) share descriptor D003924. Topic 6661 adds qualifier Q000517 (`prevention & control`). This supports a labeled association, not interchangeability.
- Diabetes (4), Diabetes in Children and Teens (6026), and Diabetic Heart Disease (6011) share D003920.
- Knee Injuries and Disorders (304), Osteoarthritis (1250), and Back Pain (157) share group 10, Bones, Joints and Muscles. This is a broad browse grouping.
- Back Pain (157) and Walking Problems (5673) both link to `https://www.choosept.com/find-a-pt`. The evidence is shared directory access, not shared diagnosis.
- A1C (6308) has no outgoing related-topic, but its summary links to Diabetes Type 2 (5930), Prediabetes (5784), and Blood Glucose (6121). Diabetes (4) explicitly points to A1C. Direction matters.

## English–Spanish mappings

There are **1,016 distinct English–Spanish pairs**, represented by 2,032 reciprocal directed mappings. All targets and labels resolve; zero nonreciprocal mappings. Medical Ethics (5526) is the only English topic without a mapping. No Spanish record carries MeSH; any MeSH bridge is derived through the explicit mapping and must record its English provenance. English site assignments have 19,150 mapped-resource URLs (61.00%); topic translation does not guarantee every resource is translated. Other-language links support additional link-outs but do not constitute local multilingual corpora.

'''+table(['English record','Spanish record'],[[topic(i),topic(R[i]['mapped'][0]['id'])] for i in ('304','157','6308','4','273','24')])
body+='''
## Potential clusters for exploration

These are analyst-selected thematic clusters based on actual edges and metadata, not a community-detection result, clinical ontology, or validated care pathway:

'''+table(['Cluster','Seed records','Evidence and boundary'],[
('Knee and movement',titles(['304','3924','5673','1250','23']),'304 → 3924 explicit related; 5673 → 304 explicit related; 304 summary → 1250; group 10 shared'),('Back and spine',titles(['157','1532','284']),'157 explicitly relates to Sciatica and Herniated Disk; group overlap adds broad browse context'),('Diabetes and testing',titles(['4','6308','6121','5930','5784','6661']),'Diabetes related edges; A1C summary links; D003924 shared by type 2/prevention'),('Headache discovery',titles(['273','3157']),'Reciprocal curated edges; Vascular Headache alias collision'),('Allergy discovery',titles(['24','4746','1256','6257']),'Allergy related edges; Allergic Rhinitis alias collision; summary anchors')])
body+='''
## Lightweight knowledge graph feasibility — recommendation

**Yes, for a bounded discovery experience.** Plain files and in-memory maps can represent topics, terms, groups, descriptors/qualifiers, organizations, and resources, with typed adjacency lists. A topic-ID map, canonical-URL map, term-to-topic multimap, and descriptor/group/resource reverse maps are sufficient. Keep explicit `related-topic`, `language-mapped-topic`, and `summary-link` edges separate from inferred `shared-descriptor`, `shared-group`, and `shared-resource` candidates; store source field and snapshot hash on every derivation. Cap expansion at one hop initially and label why each neighbor appears.

A graph database adds no demonstrated value to the proposed 30-record POC. Broad group/resource projections are dense: compute bounded neighbors when needed rather than pre-materializing every possible topic pair in a future UI payload. The discovery JSON stores these pair inventories for audit only. No graph database or graph UI was implemented. Typed clinical inference, MeSH ancestry, physician networks, and treatment recommendations are unsupported by these edges.
'''
write('03-relationships.md','Relationship analysis',body)

# One opportunity registry supplies both search analysis and the priority matrix.
OPPS=[
('Lexical search with field boosting','Find the right topic despite noisy prose','@title; @meta-desc; full-summary plain text; site @title','Experience / Data / Optimization','High','Low','None beyond normalization and a reviewed evaluation set','Long summaries and resource titles can swamp topic names; ranking must be tested','Now','Boost title and exact alias, then descriptor, description, summary; resource titles lower. No engine chosen.'),
('Exact and synonym matching','HbA1c, Backache, and other known terms are hard to find','@title; also-called; see-reference; descriptor','Experience / Data','High','Low','Language-aware term multimap; collision handling','Aliases are not universally equivalent; 109 English collision keys','Now','Normalize case/whitespace; preserve source label; return choices on collisions.'),
('Autocomplete and curated query suggestions','Users do not know the dataset vocabulary','@title; also-called; see-reference','Experience / Data / Optimization','High','Low','Deduplicated dictionary; accessible keyboard behavior','Avoid symptom-to-diagnosis completions and popular-query logging','Now','Suggest existing labels with canonical topic context; no generated or behavior-derived phrases.'),
('Faceted resource discovery','Users need education, trials, or publications quickly','group; site / information-category; organization; standard-description','Experience / Data / Activation','High','Low','Separate topic vs resource facets; exclusion of NON-DISPLAY label','Resource categories are mixed intent/audience/format; counts must reflect assignments','Now','Start with medical group, resource category, and language. Keep producer optional.'),
('Related topics and Spanish switch','Users need adjacent information and preferred language','related-topic; language-mapped-topic; summary href','Experience / Data / Activation','High','Low','ID/URL resolution; explicit edge labels; selected Spanish records','One topic lacks Spanish mapping; anchor context is untyped','Now','Use curated related topics first and explicit translation mapping; offer summary-linked topics with label.'),
('Clinical trial and publication link-outs','Find existing research resources without copying them','site @url/@title; information-category; organization','Experience / Data / Activation','High','Low','Topic selected; external-navigation labels','Search links are not trial enrollments, eligibility decisions, article records, or live results','Next','Expose existing Clinical Trials and Journal Articles links in topic detail; no trial matching.'),
('Shared MeSH recommendations','Find topics with a specific controlled concept','descriptor @id; qualifier @id; mesh-heading','Experience / Data / Activation','Medium','Low','Reverse descriptor map; reason labels; qualifier preservation','Only 175 English pairs; no MeSH hierarchy; Spanish inheritance must be explicit','Next','Show bounded “Shares MeSH descriptor” links separately from curated relations.'),
('Shared group/resource exploration','Browse beyond explicit related topics','group @id; site @url','Experience / Data / Activation','Medium','Low','Bounded candidate lists; resource URL identity','Broad groups and institute homepages create irrelevant associations','Next','Use group browse and “also links to” labels; do not call these clinical similarities.'),
('Multilingual search','Discover translated topic using English or Spanish label','language-mapped-topic; Spanish @title / aliases / summary; site @language-mapped-url','Experience / Data / Activation','High','Medium','Explicit language control; bilingual evaluation; separate analyzers','No Spanish MeSH; content parity and resource parity differ','Next','Start with Spanish switching; bilingual label lookup can follow without machine translation.'),
('Search intent detection','Distinguish learning from trials or provider navigation','information-category; site @title; topic labels','Experience / Activation','Medium','Medium','Reviewed lexical intent rules or user-selected intent','Dataset has no labeled queries or validated symptom/triage intents','Next','Prefer visible user-selected resource intents; rules may suggest filters, never care routing.'),
('Privacy-preserving optimization','Find relevance failures without retaining health queries','Offline evaluation cases; aggregate UI events','Optimization / Experience','High','Low','Reviewed metrics schema; suppression and retention controls','Topic IDs, destination URLs, and stable identifiers can disclose health interests too','Now','Use synthetic queries offline; collect only broad aggregate metrics without query/topic identifiers by default.'),
('Semantic search','Match “my knees hurt when I walk” to relevant wording','full-summary plain text; @title; aliases; descriptor','Experience / Data / Optimization','Medium','High','Embedding model/runtime and relevance/safety evaluation, not approved','Benefit unmeasured; false symptom-to-diagnosis similarity; model privacy','Later','Compare against lexical baseline only if paraphrase failures remain. No embeddings generated.'),
('Hybrid search','Improve recall while keeping exact medical terms','Lexical fields plus future semantic representations','Experience / Data / Optimization','Medium','High','Both retrieval systems; score fusion; benchmark','Additional cost and opaque ranking without demonstrated improvement','Later','Require measured incremental value over lexical search; no engine implemented.'),
('Future grounded RAG','Answer supported educational questions with citations','Attributable summaries; source IDs/URLs; metadata; relationships','Experience / Data / Activation / Optimization','Medium','High','Rights, versioned retrieval, safe abstention, clinical review, model approval','Insufficient evidence, stale links, hallucinations, personalized medical advice','Later','Treat summary-grounded education as research only; external linked bodies are not locally available.'),
('Provider locator or clinical routing','Find nearby providers or pick a specialty for symptoms','Only site link-outs; no provider entities','Experience / Activation','High','High','Verified directory/geographic/availability data and care governance','No local providers, coordinates, insurance, appointments, or patient context','Avoid','Avoid claiming native near-me search or automated specialty advice from this corpus.'),
]
body='''## Recommended search foundation

The existing structure supports a useful lexical experience before AI or new infrastructure. This is a design assessment, not a measured retrieval benchmark: no search engine was implemented. Effort refers to a bounded file-backed POC with a small UI, not production integration, clinical approval, accessibility certification, or ongoing operations. Low means a few bounded parsing/UI rules; Medium adds significant language or interaction evaluation; High introduces model systems, new datasets, or governance. Impact is an analyst estimate and needs user validation.

English has 925 also-called assignments on 507 topics and 1,553 see-reference assignments on 750 topics. Descriptor labels cover 1,015 topics; every topic has a title, summary, and resources. This gives multiple complementary retrieval fields. Do not assume corpus coverage establishes ranking quality.

## Opportunity-by-opportunity assessment

'''
for name,problem,fields,layers,impact,effort,deps,risks,rec,design in OPPS:
    body+=f'\n### {name}\n\n**User problem:** {problem}. **Fields:** {fields}. **Expected value:** {impact}. **Effort:** {effort}. **Priority:** {rec}.\n\n{design}\n\n**Additional data / dependencies:** {deps}. **Limits:** {risks}.\n'
body+='''
## Field use and initial ranking hypothesis

Use language as a hard scope unless the user opts into bilingual search. Keep normalized title/aliases as exact keys and tokenizable text separately. Rank exact title/alias matches before partial titles, descriptors, descriptions, and summary text. Use source resource titles as a lower-weight recall signal; category/organization should filter or contextualize rather than dominate clinical term relevance. These are qualitative hypotheses: no numeric boost values or relevance improvements have been established.

Autocomplete should display the matched alias and destination topic together; show both Migraine and Headache for `Vascular Headache`. Query suggestions should come from the vocabulary, not from logging users’ health queries. Preserve `HbA1c` case in display while matching case-insensitively. Plural handling (`knees` → `knee`) and a small reviewed lay-term dictionary could improve lexical recall, but they are new authored configuration and need review; the XML does not explicitly define `hurt` as a synonym of `pain`.

Semantic discovery may help paraphrases, yet Knee Injuries and Disorders (304) already says “pain and difficulty walking” in its summary. A lexical baseline indexing summaries and resource titles may be sufficient for this example. Evaluate broad symptom phrasing without declaring a diagnosis. Hybrid and RAG value remains hypothetical until a baseline shows a real gap.

## Proposed offline relevance cases, not executed retrieval tests

'''+table(['Synthetic query','Evidence-based expected destination / behavior'],[
('HbA1c',topic('6308')+' through source aliases'),('Backache',topic('157')+' through also-called'),('Vascular Headache','Both '+titles(['273','3157'])+'; explain ambiguity'),('Allergic Rhinitis','Both '+titles(['24','4746'])),('knees hurt when I walk',topic('304')+' as educational candidate; optionally '+topic('5673')+'; no diagnostic inference'),('diabetes clinical trials',topic('4')+' with Clinical Trials resources'),('dolor de cabeza',topic('1933')+' in Spanish scope; mapped English optional'),('doctor near me','Explain native directory gap; offer source-listed directory links')])
body+='''
Acceptable outcomes and graded relevance require content-owner review. The 30-record subset is intentionally demonstrative, so its relevance/zero-result measures cannot be generalized to all healthcare queries. No term logs, popularity data, click histories, or patient data are necessary for the initial POC.
'''
write('04-search-opportunities.md','Search opportunity analysis',body)

JOURNEYS=[
('I have knee pain. What should I do?',['304','1250','23'],'Knee/pain terms in summary and site titles; ACL Injuries, Meniscus, Patella are source index terms, not general knee-pain diagnoses.','304 → Knee Replacement (3924), Leg Injuries and Disorders; 304 summary links to Osteoarthritis.','Read source education and Start Here resources; explore related topics.','No symptom history, examination, triage protocol, personal treatment plan, or diagnostic confidence.','Lexical title+summary+resource search can find knee material; semantic benefit for conversational wording is unproven. Return education, not a “what to do” medical answer.'),
('My knees hurt when I walk.',['304','5673','1250'],'304 summary contains “pain and difficulty walking”; 5673 has Gait Abnormalities; 1250 has OA/Osteoarthrosis. “Hurt” is not an explicit pain alias.','Walking Problems explicitly points to Knee Injuries and Disorders; knee summary links to Osteoarthritis; shared group 10 is browse context.','Explore knee and movement education; open source-selected resources.','No way to determine the cause of an individual’s walking-related pain.','Lexical plural normalization and summary indexing may suffice; semantic search is a Later experiment, with broad candidate presentation and no diagnosis.'),
('What is HbA1c?',['6308','6121'],'HbA1C/HbA1c, Glycohemoglobin, Hemoglobin A1C test, Hemoglobin A1c; descriptor Glycated Hemoglobin.','A1C has no outgoing related-topic; summary links to type 2 diabetes, prediabetes, blood glucose; incoming Diabetes → A1C.','Open attributed A1C overview, NLM lab-test link, NIDDK education; switch to Spanish.','No individual lab interpretation, reference-range customization, or clinician advice.','Case-insensitive exact alias matching directly solves this; semantics not needed.'),
('Where can I learn more about diabetes?',['4','5930','6308','5784'],'DM, Diabetes mellitus, Sugar Diabetes; Sugar Diabetes also appears on type 2 topic.','Diabetes has 19 outgoing related topics including A1C, Blood Glucose, both diabetes types, complications and diet.','Read Start Here and patient education resources; narrow to tests, living with, or research.','No patient diagnosis, care-plan tracking, patient measurements, or treatment personalization.','Lexical and faceted search are sufficient starting points; broad diabetes query should retain type distinctions.'),
('Are there clinical trials related to my condition?',['4','304','157','273','24','192'],'Condition topic labels plus Clinical Trials resource category; trial titles contain condition terminology.','Topic → site assignments; Clinical Trials (192) supplies general education, not a validated eligibility relationship.','Open the condition’s existing ClinicalTrials.gov search link; read trial education.','No actual local trial records, current enrollment status, eligibility, geography, contact details, or matching algorithm.','Lexical topic lookup plus user-selected Clinical Trials category; semantics unnecessary for explicit trial intent.'),
('Can I read this information in Spanish?',['304','157','6308','4','273','24'],'Explicit language-mapped-topic titles: see record evidence and minimal selection.','Each of these six has a reciprocal Spanish topic mapping; resource translation coverage is separate.','Switch topic using source mapping; use mapped resource URL only where supplied.','Not every resource is bilingual; Medical Ethics lacks a topic equivalent. Other-language links are not locally stored translations.','No semantic translation needed. Bilingual exact labels can improve discovery later; never synthesize a missing translation.'),
('What type of doctor should I see?',['1433','304','157','24'],'Choosing a Doctor or Health Care Service; specialist-related resource titles such as Find an Allergist or Find a Physical Medicine and Rehabilitation Physician.','1433 explicitly relates to Health Facilities and Patient Safety. Condition-to-directory links are editorial resource assignments, not clinical referrals.','Read provider-selection education; navigate to source-listed professional organizations/directories with clear labels.','No validated symptom-to-specialty rules, patient context, referral requirements, credentials assessment, or care urgency.','Lexical provider-selection terms and source resource titles can expose education. Semantic matching cannot validate a specialty recommendation.'),
('Where can I find a doctor near me?',['1433','486','304','5673','4746'],'Directory/navigation words occur in site titles; near-me location fields do not exist.','Topic → directory links; shared directory links do not prove a provider relationship.','Open Medicare Care Compare, AAHKS Find a Doctor, AAAAI Find an Allergist, or ChoosePT where relevant to selected topic.','No provider/hospital entities with addresses, coordinates, distance, availability, insurance, or appointment booking. Geographic applicability of each directory is unverified.','Lexical navigation can find links; neither lexical nor semantic search supplies missing local directory data.'),
]
body='''## Scope and safety boundary

These are discovery journeys based on local source records, not medical advice or generated answers. Listed external URLs and organizations are copied from XML; no destination was opened to verify availability, clinical content, service area, or current trial status. “Potential action” means an educational/navigation action. Candidate topics must not be interpreted as diagnoses, specialty referrals, or treatment recommendations.

## Query-by-query evidence
'''
for query,ids,terms,relations,actions,gap,search in JOURNEYS:
    body+=f'\n### “{query}”\n\n**Relevant records:** {titles(ids)}.\n\n**Matching vocabulary:** {terms}\n\n**Relationships:** {relations}\n\n**Resources and potential actions:** {actions} Source-specific resource URLs and categories for these records appear in the evidence cards below.\n\n**Missing information:** {gap}\n\n**Discovery assessment:** {search}\n'
body+='''
## Back pain, headaches, and allergies: additional inspected journeys

'''+table(['Journey','Real records and vocabulary','Source relationships','Useful discovery / limit'],[
('Backache / low back pain',titles(['157','1532','284'])+'; Backache, Lumbago, Low Back Pain','Back Pain → Sciatica and Herniated Disk explicit related-topic','Education, trials link and resource navigation; no cause diagnosis or recommended intervention'),('Headaches / Vascular Headache',titles(['273','3157'])+'; Cluster Headache, Tension Headache, Vascular Headache','Headache ↔ Migraine explicit; Vascular Headache belongs to both aliases','Display disambiguation and educational choices; do not classify headache subtype'),('Allergies / allergic rhinitis',titles(['24','4746','1256','6257'])+'; Hypersensitivity, Allergic Rhinitis, food-specific index terms','Allergy → Hay Fever, Food Allergy, Anaphylaxis explicit','Scope choice and source education; no individual allergy diagnosis or emergency triage')])
body+='\n## Real-record evidence cards\n\nEach card lists exact source vocabulary, curated relationships, all resource-category counts, and representative resource links. Category counts overlap for multi-category resources. Full resource lists and summary text are in `dataset-profile.json` → `records.<id>`. These cards are evidence about content availability, not clinical endorsements.\n'
cardids=['304','5673','1250','23','157','1532','284','6308','6121','4','5930','5784','273','3157','24','4746','1256','6257','1433','486','192']
for tid in cardids:
    t=R[tid]
    body+=f'\n### {t["title"]} — {tid}\n\nCanonical source: {topic(tid)}.\n\n'
    body+='Aliases: '+('; '.join(f'{a["value"]} ({a["field"]})' for a in t['aliases']) or 'None')+'.\n\n'
    body+='Groups: '+('; '.join(f'{g["value"]} ({g["id"]})' for g in t['groups']))+'. MeSH: '+('; '.join(f'{m["descriptor"]["value"]} ({m["descriptor"]["id"]})'+(' / '+', '.join(q['value'] for q in m['qualifiers']) if m['qualifiers'] else '') for m in t['mesh']) or 'None')+'.\n\n'
    body+='Curated related-topic: '+titles([e['id'] for e in t['related']])+'.\n\n'
    body+='Spanish equivalent: '+titles([e['id'] for e in t['mapped']])+'.\n\n'
    body+=f'Resources: **{len(t["sites"])} site assignments**. Categories: '+('; '.join(f'{k}: {v}' for k,v in sorted(Counter(c for s in t['sites'] for c in s['categories']).items())))+'.\n\n'
    selected=[]
    for cat in ('Start Here','Learn More','Patient Handouts','Clinical Trials','Journal Articles','Find an Expert'):
        candidates=[s for s in t['sites'] if cat in s['categories'] and s not in selected]
        # Prefer actual directory-labeled resource over a general institution homepage.
        if cat=='Find an Expert': candidates.sort(key=lambda s:not any(w in s['title'].lower() for w in ('find','directory','compare','docinfo')))
        if candidates: selected.append(candidates[0])
    body+=table(['Resource (source URL)','Category','Organization attribution'],[[f'[{s["title"]}](<{s["url"]}>)',', '.join(s['categories']),'; '.join(s['organizations']) or 'Not supplied'] for s in selected])
body+='''
## Journey design recommendation

Show a topic result and its provenance, then provide explicit actions: Read overview, Explore related topics, View education resources, View research links, or Read in Spanish. Distinguish general organizations from actual directory links inside Find an Expert. An unanswered care question should lead to an honest data-limit explanation, never a fabricated doctor, facility, treatment, trial, or referral. Summary-only retrieval is insufficient to generate comprehensive answers about the linked documents, because their bodies are absent.
'''
write('05-user-journeys.md','Healthcare user journeys',body)

body='''## Experience

**Recommendation:** begin with title/alias/summary lexical discovery, source vocabulary autocomplete, group browsing, resource-category filters, labeled related-topic navigation, and explicit Spanish switching. Use ambiguous terms as opportunities to show clear choices rather than hide alternatives. Preserve canonical source links and distinguish topic-level results from linked resources. Existing summaries and source resource titles support knee, back, diabetes, headache, and allergy exploration without authored medical answers.

## Data

**Verified assets:** 2,033 topic records, 44 group IDs, 52,562 topic-resource assignments, exact aliases/index references, controlled MeSH IDs with limited qualifiers, primary-institute attribution, resource organizations/categories/descriptions, explicit related and language mappings, and embedded summary links. English has 31,393 site assignments and 1,015 MeSH-covered topics. All explicit related/mapped ID references resolve. No structured provider or hospital directory exists. Spanish records have no MeSH assignments.

**Proposed local representation:** source topic ID, title, canonical URL, language, dates, source description and summary, aliases, group/descriptor/qualifier IDs, typed edges, site metadata, and snapshot hash. Store original values plus separate normalized retrieval fields. External document bodies remain external. Keep inherited translation/MeSH metadata and inferred relationships labeled. This is a proposed contract; no content types or database were created.

## Activation

'''+table(['User action','Source fields','Boundary'],[
('Read trusted medical information','full-summary; @url; primary-institute','Attribution and canonical source; no personalized answer'),('Explore related topics','related-topic; summary href','Label curated versus summary-linked versus inferred'),('Access patient education','site; Patient Handouts / Start Here / Learn More','External document rights and availability are separate'),('Discover clinical trials','site + Clinical Trials','Open existing search links; no eligibility or enrollment claim'),('Access scientific publications','site + Journal Articles','Often PubMed queries, not publication bodies or citations stored as articles'),('Navigate healthcare resources','site + Find an Expert / organization','General organization may not be a directory; no native near-me claim'),('Switch to Spanish','language-mapped-topic; site @language-mapped-url','Topic mapping does not guarantee resource parity')])
body+='''
## Optimization

All measures below are proposed; no analytics collection was installed and no baseline measurements were fabricated. Start with an offline, clinician/content-owner-reviewed set of synthetic queries and expected topic IDs. Separate small-subset POC results from full-corpus claims.

'''+table(['Measure','Definition / method','Privacy-preserving default'],[
('Search relevance','Offline Recall@5 / graded nDCG@5 or reciprocal rank on reviewed synthetic cases','Retain synthetic query IDs and judgments only; no real user terms'),('Zero-result rate','Zero-result searches / all searches in an aggregate interval','Counts only; no query string, result IDs, or session linkage'),('Search latency','P50/P95 time from submitted search to rendered results','Aggregate durations only; no search URL, request body, or topic labels'),('Result CTR','Search result clicks / result impressions or search submissions; choose one denominator and name it','Broad event counts only; avoid item IDs/rank histories tied to a person'),('External-resource CTR','Outbound clicks / resource-link impressions','Aggregate action counts; do not retain destination URLs or referrers'),('Related-topic engagement','Related-topic clicks / related-topic section impressions','Counts only; topic IDs and graph paths can reveal health interests'),('Spanish switch use','Language-switch clicks / eligible detail views','Aggregate counts; no cross-session language/health profile'),('Query reformulation','Repeated search before a click; definition requires session context','Offline moderated synthetic tasks first; real-session measurement off by default'),('Task completion','Participant reaches the expected education resource / completes task','Consenting task study using synthetic scenarios, not personal health details')])
body+='''
If any analytics is later approved, keep raw health-related queries out of logs, telemetry, error traces, session replay, analytics URLs, browser-history sharing, and third-party requests by default. Query hashing is not sufficient protection against dictionary reversal. Topic IDs, result titles, destination URLs, and persistent user/session identifiers also reveal health interests. Use coarse aggregation, minimal retention, access controls, and small-cell suppression. Aggregate figures alone still require privacy review if groups are small. Avoid query-bearing referrers on outbound links; do not forward query text to external services unless the user explicitly initiates an external search and the disclosure is clear.

## Cross-layer example

For `HbA1c`, Experience resolves the alias to A1C; Data supplies ID 6308, D006442, attribution and Spanish mapping; Activation opens the NLM test resource or Spanish record 6309; Optimization uses a synthetic exact-alias test and broad action counts. For a knee symptom phrase, show educational candidates with provenance and preserve the limit that the data cannot diagnose a person.
'''
write('06-four-layer-framework.md','Four-layer framework mapping',body)

body='''## Prioritization basis

Analyst estimates, not measured outcomes. Priority favors existing fields, deterministic behavior, bounded UI work, and no new infrastructure or datasets. Effort is relative to a small file-backed POC: Low = bounded transformation/UI rules; Medium = broader interaction/language evaluation; High = models, new data integrations, or clinical governance. Expected impact is potential user value, not a quantitative effect size. “Now” is a recommended scope after approval, not authorization to implement. Privacy and safety review apply across rows.

'''+table(['Opportunity','User problem','Supporting fields','Framework alignment','Impact','Effort','Dependencies','Risks / limitations','Recommendation'],[[name,problem,fields,layers,impact,effort,deps,risks,rec] for name,problem,fields,layers,impact,effort,deps,risks,rec,design in OPPS])
body+='''
## Recommended sequence

**Now:** build the approved small corpus contract, deterministic lexical/alias discovery, vocabulary autocomplete, group/resource filters, curated related links, Spanish switch, attribution, and offline evaluation. Privacy-preserving metric design is part of this scope. Trials/publication links can appear as ordinary resources within category filtering; a dedicated research workflow is Next.

**Next:** assess bounded MeSH overlap, broader bilingual search, resource-specific actions, and user-selected intent controls. **Later:** only consider semantic/hybrid retrieval if a measured lexical gap justifies it; RAG requires a separate evidence, licensing, safety, and governance decision. **Avoid:** provider/specialty claims, diagnosis, triage, or nearby care matching from this corpus alone. A link to a directory is a viable low-effort action; a native directory service is a different project.

No Drupal, Composer dependency, database, content type, Solr, embedding, LLM API, or RAG implementation is included in Phase 0.
'''
write('07-impact-effort-matrix.md','Impact versus effort',body)

SEEDS=[('304','Knee symptom language, ACL/meniscus terms, links and directories'),('3924','Knee arthroplasty alias; explicit knee relationship'),('1250','OA and degenerative joint disease; symptom paraphrase candidate'),('23','Joint inflammation; broad arthritis family'),('5673','Gait Abnormalities; walking phrase and knee edge'),('417','Injury-language contrast; shared musculoskeletal group'),('157','Backache/Lumbago/Low Back Pain; back journey'),('1532','Explicit spine neighbor and controlled term'),('284','Intervertebral Disc Displacement MeSH versus lay title'),('6308','HbA1c variants, no outgoing related edge, Spanish mapping'),('6121','Blood sugar synonym; diabetes/A1C relationships'),('4','DM/Diabetes mellitus; highly connected hub'),('5930','Sugar Diabetes ambiguity; D003924 overlap'),('6661','D003924 with prevention & control qualifier'),('5784','Insulin Resistance index term; A1C summary target'),('273','Vascular Headache ambiguity; headache journey'),('3157','Shared ambiguous alias; explicit headache neighbor'),('24','Hypersensitivity; Allergic Rhinitis ambiguity'),('4746','Allergic Rhinitis; explicit allergy neighbor'),('1256','Food-specific aliases; narrower allergy intent'),('6257','Safety-boundary example; no diagnostic action'),('1433','Provider-selection education and directory link-outs'),('486','Facility selection and directory gap'),('192','Clinical-trial education versus live trial data')]
SPANISH=[R[i]['mapped'][0]['id'] for i in ('304','157','6308','4','273','24')]
IDS=[i for i,_ in SEEDS]+SPANISH
selected=[R[i] for i in IDS]
metrics=dict(topics=len(IDS),english=len(SEEDS),spanish=len(SPANISH),sites=sum(len(t['sites']) for t in selected),unique_site_urls=len({s['url'] for t in selected for s in t['sites']}),english_with_alias=sum(bool(R[i]['aliases']) for i,_ in SEEDS),english_with_mesh=sum(bool(R[i]['mesh']) for i,_ in SEEDS),english_with_qualifier=sum(any(m['qualifiers'] for m in R[i]['mesh']) for i,_ in SEEDS),english_with_spanish_mapping=sum(bool(R[i]['mapped']) for i,_ in SEEDS))
(OUT/'minimal-selection.json').write_text(json.dumps(dict(status='Proposed selection only; not an imported corpus',source_sha256=P['source']['sha256'],attribution='Source: MedlinePlus, National Library of Medicine.',metrics=metrics,topics=[dict(id=t['id'],title=t['title'],language=t['language'],url=t['url'],rationale=dict(SEEDS).get(t['id'],'Source-mapped Spanish counterpart for a core journey')) for t in selected]),ensure_ascii=False,indent=2)+'\n')
body=f'''## Proposed scope: 30 actual records, 24 English + 6 Spanish

This is a **recommended selection manifest**, not a content import or implemented application. Thirty records cover five requested medical discovery areas, provider-navigation limits, and trial education while giving six local bilingual pairs. Fewer records would remove either distinct ambiguity cases (headache/allergy/diabetes), a qualified MeSH overlap, the provider data-gap demonstration, or a core journey’s Spanish counterpart. This is a reasoned representative minimum for these demonstrations, not a mathematically optimized set or a statistically representative healthcare sample. A full 24-pair bilingual sample would require 48 records, exceeding the target; the other 18 mappings should remain source link-outs.

The selection has **{metrics['sites']} site assignments**, **{metrics['unique_site_urls']} distinct site URLs**, {metrics['english_with_alias']} English records with aliases, {metrics['english_with_mesh']} with MeSH descriptors, and {metrics['english_with_qualifier']} with qualifiers. All 24 English records have Spanish mappings, but only six counterparts are proposed for local storage. Some relationships point outside the sample: preserve the target ID/title/URL as external navigation, rather than pretend the sample is closed.

## English selection

'''+table(['ID','Exact title / canonical URL','Why selected'],[[i,topic(i),why] for i,why in SEEDS])
body+='\n## Spanish selection\n\n'+table(['ID','Exact title / canonical URL','English partner'],[[i,topic(i),topic(R[i]['mapped'][0]['id'])] for i in SPANISH])
body+='''
The machine-readable [selection manifest](minimal-selection.json) preserves IDs, exact titles, canonical URLs, language, rationale, source hash, and attribution. It contains no external resource bodies and does not duplicate the full dataset. `dataset-profile.json` remains full-corpus analysis evidence and should not be shipped as the POC corpus.

## Demonstrations and acceptance criteria proposed for approval

'''+table(['Capability','Real evidence','Expected demonstration'],[
('Exact/synonym lookup','6308 HbA1C/HbA1c; 157 Backache/Lumbago','Same canonical topic from source alias; original display case'),('Ambiguity','273/3157 Vascular Headache; 24/4746 Allergic Rhinitis; 4/5930 Sugar Diabetes','Show multiple labeled candidates'),('Related navigation','304 → 3924; 157 → 1532/284; 273 ↔ 3157','Show why link exists; keep source edge direction'),('Group browse','304/1250/157 share group 10','Browse musculoskeletal content without clinical-equivalence claim'),('MeSH overlap with qualifier','5930/6661 D003924; 6661 Q000517','Separate inferred descriptor association from curated related edge'),('External resources','Education, Clinical Trials, Journal Articles, Find an Expert','Open original URL with source organization and external-action label'),('Language switch','Six English/Spanish pairs above','Navigate reciprocal explicit mapping without translation model'),('Potential semantic use case','304 pain/difficulty walking; 5673 Gait Abnormalities; 1250 movement terms','Compare lexical baseline on synthetic paraphrases before approving semantic retrieval')])
body+='''
## Proposed local fields versus external links

'''+table(['Store locally after implementation approval','Keep external'],[
('Selected source IDs, titles, languages, canonical URLs and source hash/date','Full MedlinePlus topic page at canonical URL'),('Selected topic summaries as original HTML plus sanitized/plain variants; source meta-desc and attribution','Third-party article/encyclopedia bodies, images, video, PDFs, and lab/encyclopedia content linked from summary'),('also-called, see-reference, group IDs/labels/URLs, descriptor and qualifier IDs/labels','Full MeSH hierarchy and definitions not present in XML'),('Explicit related/language mappings and resolved summary hrefs; out-of-sample target stubs','Out-of-sample topic bodies and 18 unselected Spanish counterparts'),('Site title, URL, category, organization, standard-description, optional mapped URL','Live trials, publications returned by search links, provider/facility directory results'),('date-created and snapshot date-generated, clearly differentiated','Last-reviewed/updated dates not present; external destination freshness')])
body+='''
Recommended initial payload: only the 30 selected records and source-listed resource metadata; cap the number shown initially in the UI, while preserving all selected-record links in the proposed local data. No separate directory, trial, article, or provider entities should be invented. Maintain `Source: MedlinePlus, National Library of Medicine.` and per-resource organization attribution; verify launch-specific attribution and rights before public reuse. No extraction/import of this proposed subset has been implemented beyond its ID/title/URL manifest.
'''
write('08-minimal-dataset.md','Minimal viable dataset recommendation',body)

body='''## Verified dataset limitations

'''+table(['Question / capability','What the XML supplies','What is absent'],[
('What condition do I have?','General topic summaries and vocabulary','Personal history, examination, validated differential/triage model'),('What should I do for my symptoms?','Source educational summaries and external resources','Individualized medical assessment or safe care instructions for this person'),('What does my A1C result mean?','General A1C educational record and lab-test link','Patient result, clinical context, reviewed individualized interpretation'),('What doctor should I see?','Provider-selection education and condition-linked directories','Validated symptom-to-specialty mapping/referral rules'),('Find a nearby doctor or hospital','External directory link titles and URLs','Structured individual physician/hospital directory records, addresses, coordinates, distance, insurance, availability, booking'),('Are there current trials for me?','ClinicalTrials.gov query URLs','Trial entities, inclusion/exclusion criteria, recruiting status, patient eligibility'),('What do these scientific articles conclude?','PubMed query URLs in Journal Articles','Article bodies, complete article-level metadata or evidence synthesis'),('Is a link working now?','Original URL strings','Availability/redirect/content verification; explicitly out of scope'),('Is content medically current?','Snapshot generation date and topic creation dates','Per-topic last-reviewed/revised date or medical review evidence'),('Can every resource be read in Spanish?','1,016 topic pairs and optional mapped site URLs','Complete resource translation parity; local MeSH on Spanish records')])
body+='''
**Directory conclusion:** the schema and all records were inspected for structure. The corpus consists of topic records and resource links, not individual providers or hospital listings. Resource metadata includes actual directory destinations (AAHKS, AAAAI, ChoosePT, Medicare Care Compare, etc.), but a resource title or organization label is not a provider entity. It cannot support native geographic lookup. External service area, eligibility, access, and availability were not checked.

## Are additional datasets necessary?

**No** for the recommended educational discovery POC: the current snapshot supports aliases, facets, attributed summaries, related navigation, source research links, and Spanish switching. A small manually reviewed synthetic evaluation set and approved UI safety copy are needed; these are project artifacts, not downloaded health datasets.

Additional data becomes genuinely necessary only if the product scope changes: provider/geographic data for a native near-me locator; live registry data for trial matching; licensed article/full-text evidence for document-level answers; reviewed bilingual terminology for broad cross-language semantic equivalence; or clinical governance and validated protocols for any care-routing feature. These are separate decisions and were not downloaded or integrated.

## Source attribution and rights

NLM requests attribution for XML reuse. See [MedlinePlus XML files](https://medlineplus.gov/xml.html), accessed 2026-11-24. Source summaries are identified as public-domain content, while some linked or hosted vendor content, including encyclopedia material and images, has separate restrictions. See [Using MedlinePlus content](https://medlineplus.gov/about/using/usingcontent/), accessed 2026-11-24. Preserve `Source: MedlinePlus, National Library of Medicine.` plus canonical source URLs and resource organization labels.

This is source-policy research, not legal clearance. Verify the intended public POC’s display, branding, attribution, and rights with the content owner before release. Do not copy external bodies, vendor encyclopedia material, images, logos, PDFs, videos, or article text merely because their URLs occur in XML. Link-outs avoid copying those bodies but still require accurate labeling; source listing is not endorsement of this POC. No external body content was fetched during this analysis.

## Freshness and operational risks

The root reports `11/24/2026 02:30:05`; its timezone is not specified. The filename and generated date agree. `date-created` describes first publication, not last medical review. The local snapshot is immutable evidence, not proof that every summary or linked resource is current. A future update design should compare IDs, summaries, metadata, removed links, and mappings between snapshots, preserve source versions, and record retrieval time separately from source generation/review time. No refresh automation was installed.

Exact URLs with search parameters or fragments can be semantically meaningful; aggressive deduplication can alter destinations. Links can redirect, expire, change language or rights, or return different trials/articles over time. URL syntax checks do not establish availability. External navigation can also disclose health interests to destination sites; avoid forwarding raw user queries or query-bearing referrers by default.

## Medical safety and insufficient evidence

Source vocabulary supports information discovery, not clinical decision-making. Alias overlap does not make two conditions equivalent; group/category/organization overlap is especially weak clinical evidence. A symptom phrase must not be converted into a diagnosis or specialty referral. Existing summary wording can be displayed with clear source attribution after review, but assembling selected snippets into new clinical instructions can remove context.

If retrieved material does not answer a question, say what is available and what remains unknown. Do not generate missing facts about care, providers, trial eligibility, medications, or a person’s test results. Future RAG would need evidence sufficiency checks, versioned citations, abstention, clinical oversight, rights verification, and privacy review; its utility and safety are unproven in this phase. No model calls or answer-generation pipeline were used.

## Evidence limitations and open approvals

The referenced DTD could not be retrieved, so formal required/optional declarations and DTD validation are unresolved. The NLM XML guide is documented as a fallback, separate from observed field coverage. HTMLParser and URL checks are heuristic. No external availability, resource-body quality, clinical correctness, user relevance, accessibility, or performance test was conducted. Suggested impact/effort ratings, cluster labels, and ranking behavior are recommendations, not observed product performance.

Before implementation, approve the 30-record scope, intended audience/geography, language behavior, attribution/display policy, safety and privacy defaults, and evaluation criteria. Before public release, assign content/clinical review and confirm rights, freshness, and external-navigation expectations. Phase 0 is complete as discovery; implementation remains unapproved.
'''
write('09-data-gaps-and-risks.md','Data gaps and risks',body)

body=f'''## Key discoveries

- **2,033 topics:** 1,017 English and 1,016 Spanish. English has **31,393 resource assignments**, **20,383 distinct URLs**, and **44 medical groups**.
- English MeSH descriptors cover **1,015/1,017 topics (99.80%)**; Spanish topics have none. **1,016/1,017 English topics (99.90%)** map to Spanish, with reciprocal mappings.
- **3,316 English curated related-topic links** plus summary hyperlinks support lightweight related discovery with plain files/maps; a graph database is unnecessary for this POC.
- **109 English aliases map to multiple topics.** HbA1c case variants support easy exact matching; Vascular Headache and Allergic Rhinitis require choices.
- Quality exceptions include one absent Spanish summary, one empty meta-description, and two malformed summary hrefs. Ten topic-shaped summary hrefs lack an exact snapshot target (redirects untested). All related/mapped ID references resolve. DTD retrieval failed; formal DTD validation remains unresolved.
- The dataset has **directory link-outs, not structured provider or hospital listings**. Trial/publication resources are primarily navigation/search links, not live results or patient matching.

## Top five high-impact, low-effort opportunities

1. Lexical search with boosted topic titles and source aliases, including exact HbA1c/Backache lookup.
2. Vocabulary autocomplete and labeled choices for ambiguous aliases.
3. Medical-group and resource-category filters for education, research, and external navigation.
4. Curated related-topic discovery, with clear labels for summary-linked or inferred associations.
5. Spanish switching through explicit source mappings, preserving resource-level translation limits.

## Recommended minimal POC scope

Approve **30 actual records: 24 English topics + 6 Spanish counterparts**, selected in [08](08-minimal-dataset.md) and [the manifest](minimal-selection.json). They cover knee/back pain, diabetes/A1C, headaches, allergies, trial education, and provider-selection limits; together they have **{metrics['sites']} resource assignments**. Use plain files, deterministic matching, attribution, topic/resource facets, related navigation, and link-outs. Keep external bodies external. No AI, new infrastructure, or additional datasets are needed for this scope. Native near-me lookup, care advice, and trial eligibility are outside it.

## Four-layer alignment

| Experience | Data | Activation | Optimization |
| --- | --- | --- | --- |
| Find familiar terms, disambiguate, browse, switch language | Source IDs/URLs, aliases, MeSH, groups, resources, typed relationships and provenance | Read education, explore related topics, open research/directory links, read Spanish | Offline relevance tests; aggregate zero-result, latency, and action metrics without raw health queries or topic-level tracking by default |

## Open questions and decisions requiring approval

1. Approve the 30-record selection and the five experiences above as the implementation boundary?
2. Who is the audience and geography, and who reviews clinical terminology, safety wording, attribution, and public reuse?
3. Accept Spanish switching for six local pairs, with other translations linked externally?
4. Approve synthetic offline evaluation and privacy defaults; defer raw-query/session analytics, semantic search, hybrid retrieval, and RAG?
5. Resolve the DTD retrieval/validation gap and agree a source freshness policy before production use.

**Status:** discovery artifacts and reproducible standard-library scripts delivered. Original XML preserved by SHA-256. No application, search engine, database, Drupal, Composer packages, embeddings, LLM APIs, or RAG implementation. Awaiting approval before implementation.

Start with [dataset evidence](01-dataset-profile.md), [the priority matrix](07-impact-effort-matrix.md), and [journey evidence](05-user-journeys.md). Machine-readable full-corpus results: [dataset-profile.json](dataset-profile.json). Reproduction commands and measurement definitions are in [01](01-dataset-profile.md).
'''
write('EXECUTIVE-SUMMARY.md','Healthcare Search Experience — discovery summary',body)
print('Rendered 10 discovery reports and minimal-selection.json')
