# Healthcare Search Experience — discovery summary

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

## Key discoveries

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

Approve **30 actual records: 24 English topics + 6 Spanish counterparts**, selected in [08](08-minimal-dataset.md) and [the manifest](minimal-selection.json). They cover knee/back pain, diabetes/A1C, headaches, allergies, trial education, and provider-selection limits; together they have **1122 resource assignments**. Use plain files, deterministic matching, attribution, topic/resource facets, related navigation, and link-outs. Keep external bodies external. No AI, new infrastructure, or additional datasets are needed for this scope. Native near-me lookup, care advice, and trial eligibility are outside it.

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
