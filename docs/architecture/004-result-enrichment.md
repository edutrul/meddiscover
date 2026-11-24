# ADR 004: Deterministic result enrichment from imported structured data

- Date: 2026-11-24
- Status: Implemented; pending review
- Approved scope: Phase 04, issue #8. No recommendation ranking, CTAs, lexical/hybrid search or generation.

## Context

Phase 03 returns matching topics only. Phase 02 already imported alternative terms, MedlinePlus groups, MeSH descriptor/qualifier pairs, 331 typed relationships and 882 resource assignments. The PLAN requires enriching results from that data with entity references and structured queries, and no extra vector searches when deterministic relationships provide the answer.

## Decision

`Drupal\med_search\ResultEnricher` (service `med_search.enricher`) takes a matched Health Topic node and returns:

| Section | Source |
|---|---|
| Alternative terms | `field_med_also_called`, `field_med_see_reference` (kept separate) |
| MedlinePlus groups | `field_med_groups` terms, with source URLs |
| MeSH descriptors | `field_med_mesh` terms, descriptor/qualifier pairs |
| Further reading from the summary | `summary_link` relationships whose target is not a MedlinePlus topic (for example lab-test or genetics pages) |
| Related content | see below |
| Healthcare resources | `med_resource` nodes for the topic, grouped by category |

It depends only on the entity type manager. It has no HTTP client, embedder or database vector access, so it cannot make AI calls.

### Related content

Items are added in the PLAN's priority order:

1. **Curated** relationships (“Related topic listed by MedlinePlus”), in source order.
2. **Summary hyperlinks** that resolve to a MedlinePlus topic (“Linked in the topic summary”).
3. **Shared MeSH descriptor**: other imported topics with the same descriptor ID, matched on the descriptor rather than the descriptor/qualifier pair.
4. **Shared MedlinePlus group**.

Sources 1–2 are **explicit**; 3–4 are **inferred** and labeled as such in the interface. At most five inferred topics are added (`INFERRED_LIMIT`), because broad groups such as “Bones, Joints and Muscles” (9 topics) would otherwise overwhelm explicit links. Explicit relationships are never capped.

**Deduplication** uses the target's MedlinePlus source ID, or the normalized URL when there is none. A repeated target keeps its first, highest-priority position, and its provenance list gains each additional reason. For example, Knee Replacement from Knee Injuries shows “Related topic listed by MedlinePlus; Linked in the topic summary; Same MedlinePlus group: Bones, Joints and Muscles”. The topic itself and language mappings are excluded, so Spanish counterparts never appear.

**Links**: imported topics that are published and viewable link to their Drupal page. Out-of-corpus topics link to their original MedlinePlus URL and are marked “(MedlinePlus)”. Inferred candidates must be published English topics.

### Resources

Resources are grouped by category in the order MedlinePlus topic pages use (Start Here, Diagnosis and Tests, … Encyclopedia), with unknown categories last, alphabetically. Within a category, resources keep source order and are deduplicated by normalized URL. A resource with several categories appears under each one. Original URLs and organization names are shown unchanged. No resource is fetched, checked or invented.

### Interface

Each result in the existing form gains three core `details` elements, collapsed by default: **Explore Health Information**, **Related Content (n)** and **Explore Healthcare Resources (n categories)**. Everything renders through core link and item-list elements in Olivero, and source text is escaped.

## Measurements (real corpus, local)

- Enrichment per topic: median 24 ms, p90 41 ms, max 58 ms, measured cold with the entity cache reset for each topic. A three-result search page, including query embedding, returned in about 0.5 s.
- Related items per topic: 2–30 (303 in total), with no duplicates or self-references across all 24 topics.

## Alternatives and consequences

- **Vector similarity for related topics** was rejected: explicit editorial relationships exist, and the PLAN forbids extra vector searches in this case.
- **Views-based blocks** would split the priority, dedupe and provenance logic across configuration and make it harder to test.
- **Caching** is not added yet because the form is uncached for privacy and 24–58 ms per result is acceptable at this scale. With larger corpora, cache `enrich()` per topic, tagged with the topic and its relationship and resource nodes.

Ranking among related items is source order, not personalized or learned; Phase 07 owns recommendation ranking. Group-based inference is coarse, which is why it ranks last and is capped.
