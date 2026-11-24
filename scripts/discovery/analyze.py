#!/usr/bin/env python3
"""Offline, standard-library-only profiling; never resolves the external DTD or URLs."""
import argparse
from collections import Counter, defaultdict
from hashlib import sha256
from html.parser import HTMLParser
import itertools
import json
from pathlib import Path
import re
import statistics
import unicodedata
from urllib.parse import urlsplit, urljoin, urldefrag
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / 'data/raw/mplus_topics_2026-11-24.xml'
OUT = ROOT / 'docs/discovery'

def norm(s):
    return ' '.join(unicodedata.normalize('NFKC', s).casefold().split())

def stats(xs):
    xs = sorted(xs)
    if not xs:
        return {'count': 0}
    def pct(p):
        k = (len(xs)-1)*p
        i = int(k)
        return round(xs[i] + (xs[min(i+1,len(xs)-1)]-xs[i])*(k-i), 2)
    return dict(count=len(xs), min=xs[0], max=xs[-1], mean=round(statistics.mean(xs),2),
                median=statistics.median(xs), p10=pct(.1), p25=pct(.25), p75=pct(.75), p90=pct(.9), p95=pct(.95))

class Summary(HTMLParser):
    def __init__(self, text):
        super().__init__(convert_charrefs=True)
        self.parts, self.links, self.tags, self.attrs = [], [], Counter(), Counter()
        self.feed(text)
        self.close()
    def handle_starttag(self, tag, attrs):
        self.tags[tag] += 1
        for key, value in attrs:
            self.attrs[f'{tag}@{key}'] += 1
            if tag == 'a' and key == 'href':
                self.links.append(value or '')
        if tag in ('p','li','br','h1','h2','h3','h4'):
            self.parts.append(' ')
    def handle_data(self, data):
        self.parts.append(data)
    @property
    def text(self):
        return ' '.join(''.join(self.parts).split())

def url_issues(url):
    issues = []
    if not url or url != url.strip(): issues.append('empty or surrounding whitespace')
    if re.search(r'[\s<>"{}|\\^`\x00-\x1f]', url): issues.append('unescaped whitespace/control/unsafe character')
    if re.search(r'%(?![0-9a-fA-F]{2})', url): issues.append('invalid percent escape')
    try:
        u = urlsplit(url)
        if u.scheme not in ('http','https'): issues.append('non HTTP(S) scheme')
        if not u.hostname: issues.append('missing hostname')
        if u.hostname and ('.' not in u.hostname or re.search(r'[^\w.\-:\[\]]',u.hostname)): issues.append('suspicious hostname')
        _ = u.port
        if u.username or u.password: issues.append('userinfo present')
    except ValueError:
        issues.append('URL parser error')
    return issues

def main():
    raw = SOURCE.read_bytes()
    root = ET.fromstring(raw)
    topics = list(root)
    byid = {t.get('id'): t for t in topics}
    byurl = {urldefrag(t.get('url'))[0]: t.get('id') for t in topics}
    labels = {t.get('id'): {'id':t.get('id'),'title':t.get('title'),'language':t.get('language'),'url':t.get('url')} for t in topics}
    paths, attrs = defaultdict(list), defaultdict(list)
    empties, url_records = [], []
    def walk(el, path, tid=None):
        if el.tag == 'health-topic': tid = el.get('id')
        paths[path].append((tid,el))
        if len(el)==0 and not (el.text or '').strip(): empties.append({'topic':tid,'path':path,'kind':'empty leaf'})
        for key,value in el.attrib.items():
            attrs[path+'/@'+key].append((tid,value))
            if not value.strip(): empties.append({'topic':tid,'path':path+'/@'+key,'kind':'empty attribute'})
            if 'url' in key:
                url_records.append({'topic':tid,'path':path+'/@'+key,'url':value})
        for ch in el: walk(ch,path+'/'+ch.tag,tid)
    walk(root,root.tag)
    summaries = {t.get('id'):Summary(t.findtext('full-summary','')) for t in topics}
    edges = []
    broken, mismatches = [], []
    for t in topics:
        tid=t.get('id')
        for tag in ('related-topic','language-mapped-topic'):
            for e in t.findall(tag):
                target=e.get('id')
                edges.append({'source':tid,'target':target,'type':tag,'provenance':'explicit'})
                if target not in byid: broken.append({'source':tid,'type':tag,'target':target})
                else:
                    dest=byid[target]
                    if e.get('url')!=dest.get('url') or norm(e.text or '')!=norm(dest.get('title','')) or (tag=='language-mapped-topic' and e.get('language')!=dest.get('language')):
                        mismatches.append({'source':tid,'type':tag,'target':target,'reference':dict(e.attrib),'text':e.text})
        for href in summaries[tid].links:
            resolved=urljoin(t.get('url'),href)
            url_records.append({'topic':tid,'path':'full-summary/a/@href','url':href,'resolved':resolved})
            target=byurl.get(urldefrag(resolved)[0])
            if target: edges.append({'source':tid,'target':target,'type':'summary-link','provenance':'explicit hyperlink; topic resolution by canonical URL'})
    def duplicates(entries):
        d=defaultdict(list)
        for key,value in entries: d[key].append(value)
        return {k:v for k,v in sorted(d.items()) if len(v)>1}
    aliases=[(t.get('language'),t.get('id'),e.tag,e.text or '') for t in topics for e in t if e.tag in ('also-called','see-reference')]
    alias_groups=defaultdict(list)
    for lang,tid,tag,value in aliases: alias_groups[lang+'|'+norm(value)].append({'topic':tid,'field':tag,'value':value})
    alias_collisions={k:v for k,v in alias_groups.items() if len({x['topic'] for x in v})>1}
    casing={k:v for k,v in alias_groups.items() if len({x['value'] for x in v})>1 and len({x['value'].strip() for x in v})>1}
    # Include title-to-alias ambiguity separately, with language boundaries.
    terms=defaultdict(set)
    for t in topics:
        for text in [t.get('title')]+[e.text or '' for e in t if e.tag in ('also-called','see-reference')]: terms[t.get('language')+'|'+norm(text)].add(t.get('id'))
    term_collisions={k:sorted(v) for k,v in terms.items() if len(v)>1}
    invalid=[]
    for row in url_records:
        issues=url_issues(row.get('resolved',row['url']))
        if issues: invalid.append({**row,'issues':issues})
    scopes={lang:[t for t in topics if t.get('language')==lang] for lang in sorted({t.get('language') for t in topics})}
    scopes={'All':topics,**scopes}
    profile={}
    for scope,ts in scopes.items():
        ids={t.get('id') for t in ts}
        n=len(ts)
        fields={}
        for path,entries in paths.items():
            if not path.startswith('health-topics/health-topic/'): continue
            counts=Counter(tid for tid,e in entries if tid in ids)
            values=Counter((e.text or '').strip() for tid,e in entries if tid in ids and len(e)==0)
            fields[path.removeprefix('health-topics/health-topic/')]=dict(topics_present=len(counts),coverage_pct=round(100*len(counts)/n,2),occurrences=sum(counts.values()),per_topic=stats([counts[i] for i in ids]),unique_leaf_values=len(values))
        attribute_profile={}
        for path,entries in attrs.items():
            if not path.startswith('health-topics/health-topic'): continue
            selected=[(tid,v) for tid,v in entries if tid in ids]
            owner=path.split('/@')[0]
            denominator=sum(1 for tid,e in paths[owner] if tid in ids)
            vs=Counter(v for _,v in selected)
            attribute_profile[path.removeprefix('health-topics/health-topic')]=dict(occurrences=len(selected),parent_elements=denominator,coverage_pct=round(100*len(selected)/denominator,2) if denominator else None,topics_present=len({tid for tid,_ in selected}),unique_values=len(vs))
        resources=[(t.get('id'),s) for t in ts for s in t.findall('site')]
        urls=Counter(s.get('url','') for _,s in resources)
        domains=Counter(urlsplit(u).hostname or '' for u in urls.elements())
        def catalog(tag,resource=False):
            items=defaultdict(lambda:{'occurrences':0,'topic_ids':set(),'resource_keys':set(),'ids':set(),'urls':set()})
            for t in ts:
                for ix,owner in enumerate(t.findall('site') if resource else [t]):
                    for e in owner.findall(tag):
                        v=(e.text or '').strip(); row=items[v]
                        row['occurrences']+=1; row['topic_ids'].add(t.get('id')); row['resource_keys'].add((t.get('id'),ix))
                        if e.get('id'): row['ids'].add(e.get('id'))
                        if e.get('url'): row['urls'].add(e.get('url'))
            return {v:dict(occurrences=x['occurrences'],topic_count=len(x['topic_ids']),resource_count=len(x['resource_keys']) if resource else None,topic_ids=sorted(x['topic_ids']),ids=sorted(x['ids']),urls=sorted(x['urls'])) for v,x in sorted(items.items())}
        inventory={}
        for path,entries in paths.items():
            vals=Counter((e.text or '').strip() for tid,e in entries if tid in ids and len(e)==0)
            if vals: inventory[path.removeprefix('health-topics/health-topic/')]=dict(sorted(vals.items()))
        for path,entries in attrs.items():
            vs=Counter(v for tid,v in entries if tid in ids)
            if vs: inventory[path.removeprefix('health-topics/health-topic')]=dict(sorted(vs.items()))
        profile[scope]=dict(topics=n,fields=fields,attributes=attribute_profile,unique_values=inventory,
            summary_lengths={unit:stats([len(summaries[t.get('id')].text.split()) if unit=='plain_words' else len(summaries[t.get('id')].text) if unit=='plain_characters' else len(t.findtext('full-summary','')) for t in ts]) for unit in ('plain_words','plain_characters','decoded_html_characters')},
            sites=dict(occurrences=len(resources),unique_urls=len(urls),outside_medlineplus_occurrences=sum(v for k,v in domains.items() if k not in ('medlineplus.gov','www.medlineplus.gov')),outside_medlineplus_unique_urls=sum(urlsplit(u).hostname not in ('medlineplus.gov','www.medlineplus.gov') for u in urls),outside_medlineplus_unique_domains=sum(k not in ('medlineplus.gov','www.medlineplus.gov') for k in domains),unique_domains=len(domains),domains=dict(domains.most_common()),urls=dict(urls)),
            summary_hyperlinks=dict(occurrences=sum(len(summaries[i].links) for i in ids),topics_present=sum(bool(summaries[i].links) for i in ids),unique_resolved_urls=len({urljoin(byid[i].get('url'),href) for i in ids for href in summaries[i].links}),outside_medlineplus_occurrences=sum(urlsplit(urljoin(byid[i].get('url'),href)).hostname not in ('medlineplus.gov','www.medlineplus.gov') for i in ids for href in summaries[i].links)),
            groups=catalog('group'),categories=catalog('information-category',True),organizations=catalog('organization',True),
            missing_mesh=[t.get('id') for t in ts if t.find('mesh-heading/descriptor') is None],
            missing_related=[t.get('id') for t in ts if t.find('related-topic') is None],
            mesh_descriptor_ids=sorted({e.get('id') for t in ts for e in t.findall('mesh-heading/descriptor')}),
            mesh_qualifier_ids=sorted({e.get('id') for t in ts for e in t.findall('mesh-heading/qualifier')}))
    shared={}
    for kind,path,attr in [('mesh','mesh-heading/descriptor','id'),('group','group','id'),('resource','site','url')]:
        memberships=defaultdict(set)
        for t in scopes['English']:
            for e in t.findall(path): memberships[e.get(attr)].add(t.get('id'))
        pairs=defaultdict(list)
        for key,ids in memberships.items():
            for a,b in itertools.combinations(sorted(ids),2): pairs[a+'|'+b].append(key)
        shared[kind]=dict(shared_keys={k:sorted(v) for k,v in sorted(memberships.items()) if len(v)>1},pairs=dict(sorted(pairs.items())),pair_count=len(pairs))
    def graph(types):
        ids={t.get('id') for t in scopes['English']}
        adj={i:set() for i in ids}; outgoing=Counter(); incoming=Counter()
        for e in edges:
            a,b=e['source'],e['target']
            if e['type'] in types and a in ids and b in ids:
                outgoing[a]+=1; incoming[b]+=1
                if a!=b: adj[a].add(b); adj[b].add(a)
        seen=set(); components=[]
        for i in sorted(ids):
            if i in seen: continue
            todo=[i]; comp=[]; seen.add(i)
            while todo:
                a=todo.pop(); comp.append(a)
                for b in sorted(adj[a]-seen): seen.add(b); todo.append(b)
            components.append(sorted(comp))
        return dict(degrees=[dict(**labels[i],neighbors=len(adj[i]),outgoing=outgoing[i],incoming=incoming[i]) for i in sorted(ids,key=lambda i:(-len(adj[i]),i))],isolated=[i for i in sorted(ids) if not adj[i]],components=sorted(components,key=lambda c:(-len(c),c)))
    mapped={(e['source'],e['target']) for e in edges if e['type']=='language-mapped-topic'}
    unresolved_summary=[]
    for row in url_records:
        if row['path']!='full-summary/a/@href': continue
        u=urlsplit(row['resolved'])
        if u.hostname in ('medlineplus.gov','www.medlineplus.gov') and urldefrag(row['resolved'])[0] not in byurl:
            unresolved_summary.append(row)
    data=dict(source=dict(path=str(SOURCE.relative_to(ROOT)),bytes=len(raw),sha256=sha256(raw).hexdigest(),root_attributes=root.attrib,doctype=re.search(r'<!DOCTYPE[^>]+>',raw.decode()).group(),dtd_status='Referenced remote DTD unavailable; no DTD validation performed; ElementTree well-formedness only'),
        xml_inventory={path:dict(occurrences=len(entries),attributes=sorted({k for _,e in entries for k in e.attrib})) for path,entries in paths.items()},
        definitions=dict(coverage='Topics with >=1 occurrence / topics in scope; attribute coverage uses owner-element denominator',resource='A site occurrence is a topic-resource assignment, not a distinct document. External resource often means site; off-host counts reported separately.',normalization='NFKC + casefold + whitespace collapse; exact raw URL comparisons; no URL availability checks',percentiles='Linear interpolation at (n-1)*p; repeating counts include zero topics',domains='Lowercase hostname, retaining subdomains; not registrable domains',inference='Shared descriptor/group/resource projections are undirected candidate associations, not clinical assertions'),
        scopes=profile,topic_index=labels,quality=dict(empty_values=empties,missing_full_summary=[t.get('id') for t in topics if t.find('full-summary') is None],
        summary_markup_anomalies=[dict(topic=tid,tags=dict(s.tags),attributes=dict(s.attrs)) for tid,s in summaries.items() if set(s.tags)&{'topic','ph3','p.','style'} or 'a@hre' in s.attrs],
        invalid_urls=invalid,broken_id_references=broken,reference_metadata_mismatches=mismatches,
        duplicate_ids=duplicates((t.get('id'),t.get('title')) for t in topics),
        duplicate_topic_urls=duplicates((t.get('url'),t.get('id')) for t in topics),
        duplicate_titles_exact=duplicates((t.get('language')+'|'+t.get('title'),t.get('id')) for t in topics),
        duplicate_titles_normalized=duplicates((t.get('language')+'|'+norm(t.get('title')),t.get('id')) for t in topics),
        duplicate_titles_across_languages=duplicates((t.get('title'),dict(topic=t.get('id'),language=t.get('language'))) for t in topics),
        duplicate_aliases_exact=duplicates((lang+'|'+value,dict(topic=tid,field=tag)) for lang,tid,tag,value in aliases),
        duplicate_aliases_within_topic=duplicates((lang+'|'+tid+'|'+norm(value),dict(field=tag,value=value)) for lang,tid,tag,value in aliases),
        duplicate_aliases_within_same_field=duplicates((lang+'|'+tid+'|'+tag+'|'+norm(value),value) for lang,tid,tag,value in aliases),
        duplicate_aliases={k:v for k,v in alias_groups.items() if len(v)>1},cross_topic_alias_collisions=alias_collisions,term_collisions=term_collisions,capitalization_variants=casing,
        duplicate_site_urls_within_topic={t.get('id'):{k:v for k,v in c.items() if v>1} for t in topics if (c:=Counter(s.get('url') for s in t.findall('site'))) and any(v>1 for v in c.values())},
        summary_markup_topics=sum(bool(x.tags) for x in summaries.values()),summary_html_tags=dict(sum((x.tags for x in summaries.values()),Counter())),summary_html_attributes=dict(sum((x.attrs for x in summaries.values()),Counter())),
        unresolved_summary_links=unresolved_summary,
        unmatched_topic_shaped_summary_links=[x for x in unresolved_summary if re.fullmatch(r'/(?:spanish/)?[^/]+\.html', urlsplit(x['resolved']).path)]),
        relationships=dict(edges=edges,counts=dict(Counter(e['type'] for e in edges)),mapping_nonreciprocal=[list(e) for e in sorted(mapped) if (e[1],e[0]) not in mapped],english_related_graph=graph({'related-topic'}),english_related_and_summary_graph=graph({'related-topic','summary-link'}),english_shared=shared),
        records={t.get('id'):dict(**labels[t.get('id')],attributes=dict(t.attrib),aliases=[dict(field=e.tag,value=e.text) for e in t if e.tag in ('also-called','see-reference')],groups=[dict(**e.attrib,value=e.text) for e in t.findall('group')],mesh=[dict(descriptor=dict(e.find('descriptor').attrib,value=e.findtext('descriptor')),qualifiers=[dict(**q.attrib,value=q.text) for q in e.findall('qualifier')]) for e in t.findall('mesh-heading')],related=[dict(**e.attrib,value=e.text) for e in t.findall('related-topic')],mapped=[dict(**e.attrib,value=e.text) for e in t.findall('language-mapped-topic')],primary_institute=[dict(**e.attrib,value=e.text) for e in t.findall('primary-institute')],summary_text=summaries[t.get('id')].text,summary_links=summaries[t.get('id')].links,sites=[dict(**s.attrib,categories=[e.text for e in s.findall('information-category')],organizations=[e.text for e in s.findall('organization')],descriptions=[e.text for e in s.findall('standard-description')]) for s in t.findall('site')]) for t in topics})
    OUT.mkdir(parents=True,exist_ok=True)
    (OUT/'dataset-profile.json').write_text(json.dumps(data,ensure_ascii=False,indent=2)+'\n')
    print(json.dumps({'source':data['source'],'languages':{k:v['topics'] for k,v in profile.items()},'elements':list(paths),'edge_counts':data['relationships']['counts'],'quality':{k:len(v) if isinstance(v,(list,dict)) else v for k,v in data['quality'].items()}},indent=2))

if __name__=='__main__': main()
