# Phase 05 implementation report

Date: 2026-11-24. Approved scope: Lexical & Hybrid Search (MVP 3). The implementation is ready for review. Merge and phase acceptance are pending owner approval. Phase 06 is not authorized.

## Issue and review status

- Issue #10: https://github.com/edutrul/meddiscover/issues/10
- The PR link and CI status are recorded in the PR.
- Prerequisite verified: Phase 04 PR #9 merged; issue #8 closed.

## Implementation

Design: [ADR 005](../architecture/005-lexical-hybrid-search.md). Setup and use: [docs/search.md](../search.md).

- **Solr through DDEV.** The `ddev/ddev-solr` add-on 0.9.0 is committed, and Solr 9.11.0 (SolrCloud) is pinned in a compose override that also disables query-text logging.
  - Search API 1.43 and Search API Solr 4.4 provide server `med_solr` and index `med_topics` in exported configuration.
  - The configset is generated from that configuration and uploaded with `drush solr-upload-conf`.
  - Exported configuration contains no credentials.
- **Lexical search** (`LexicalSearch`, `/search/lexical`):
  - Boosted fields: title, alternative terms, MeSH labels, see references, description, summary and groups.
  - English stemming, plus unstemmed copies for exact forms.
  - EDisMax with `mm=2<75%` and phrase boosts.
  - Exact-name promotion and notices for ambiguous names.
- **Hybrid search** (`HybridSearch`, `/search/hybrid`):
  - Reciprocal Rank Fusion (k = 60) over each strategy's accepted results, keyed by canonical MedlinePlus topic ID.
  - Deterministic tie-breaks.
  - Falls back to the remaining strategy when one is unavailable.
- **Shared architecture.**
  - `SearchStrategyInterface`, with one result structure for all strategies.
  - `SemanticStrategy`, which adapts the Phase 03 service without changing it.
  - One `SearchForm` serving all three routes. It reuses the Phase 04 enrichment and links between modes.
- **Tests and evaluation.**
  - `scripts/search/test_hybrid.php`: synthetic contract test, added to CI after a new “Create the Solr collection” step.
  - `scripts/search/compare.php`: comparative evaluation on the real corpus.

## Acceptance criteria

| Criterion | Evidence |
|---|---|
| Solr runs through DDEV | Solr 9.11.0 in SolrCloud mode from the committed add-on and override. CI starts it with `ddev start` and creates the collection from exported config. |
| Lexical search works | `/search/lexical` returns HTTP 200 with ranked, enriched results. The contract test covers title, alias and exact-name matching, ambiguity notices, minimum-should-match, query escaping and unpublished exclusion. Evaluation lexical P@1 is 1.00 on exact titles. |
| Semantic search remains functional | `scripts/search/test.php` passes on a clean install. `/search/semantic` returns HTTP 200. Through the new strategy adapter, all 18 Phase 03 queries give the same top result, similarity (3 decimals) and result count as the [Phase 03 evaluation](../evaluation/semantic-search.md). |
| Hybrid search works | `/search/hybrid` returns HTTP 200. Evaluation: best R@5 (0.96) and MRR (0.97); never empty for relevant queries. |
| RRF is tested | Exact sums of 1/(k + rank), per-strategy ranks, duplicate handling inside a list, tie-breaks (list count, best rank, strategy order), k = 0 and rejection of negative k. Mutation checks confirmed the test fails for a wrong formula, missing tie-breaks, missing minimum-should-match, missing exact-name promotion and missing degradation. |
| Results are deduplicated | Fusion merges by canonical MedlinePlus ID; tested with a semantic stub that returns the same topic twice. |
| Independent Drupal search routes exist | `/search/semantic`, `/search/lexical` and `/search/hybrid`, each with its own form ID and links to the others. |
| All strategies use the same corpus | The 24 published English Health Topics: the Search API datasource is limited to `med_health_topic` in English, and the entity-status processor keeps only published topics. pgvector covers the same topics. Solr and pgvector each report 24. |
| Comparative evaluation is documented | [docs/evaluation/hybrid-search.md](../evaluation/hybrid-search.md): 48 queries in five categories; P@1, P@5, R@5, MRR, zero-result rate, latency, k sensitivity and per-query rankings. |

## Validation

- **Clean install from exported configuration, in CI order.** `check.php`, `config:status` with no differences, configset upload, and the fixture migration, semantic, enrichment and hybrid tests all pass.
- **Real HTTP form submissions on all three routes.**
  - All return HTTP 200 in about 0.05–0.14 s, with the three enrichment sections per result.
  - The ambiguity notice appears for “Sugar diabetes”.
  - Unrelated queries show the empty state.
- **Failure handling, tested by stopping Solr and pointing the embedding endpoint at a closed port.**
  - Each single strategy shows its “temporarily unavailable” message.
  - Hybrid falls back to the remaining strategy and says so.
  - With both down, hybrid reports unavailability.
  - Configuration was restored, with no differences.
- **Privacy.**
  - After the form tests, `check_privacy.php` passes.
  - The Solr log files and container output contain no query text.
  - Before the override, Solr's INFO log and Jetty request log did record query text; this was verified and then fixed.
- **Code quality.** PHPCS passes.

## Limitations

- **Unrelated questions in lexical and hybrid search.** Question words (“how”, “do”, “I”) count toward minimum-should-match, so lexical and hybrid search returned diabetes topics for “How do I change a car tire?”. Semantic rejects it. This was not tuned in this phase, to keep the evaluation honest; query-side stopword handling is a Phase 08 candidate.
- **Optimistic lexical estimate.** `mm` was chosen on development probes that overlap the evaluation queries, so the lexical results are optimistic. Field boosts are editorial.
- **k = 60 has no measured effect here.** No k between 0 and 1000 changed a top-5 ranking on this corpus, so k = 60 remains a literature default rather than a measured choice.
- **Sequential retrieval.** Hybrid runs both retrievers one after the other (median 16.5 ms). Parallel execution was unnecessary at this scale.
- **Solr setup in new environments.** Solr must be running with the uploaded configset. Every new environment needs `solr-upload-conf` once.
- **Coarse metrics.** With 24 topics and 48 queries, one query changes a category's rate by 7–20 points.

## Next step

The owner reviews the PR after checks pass, then approves the merge and confirms that issue #10 is closed. Recommended next phase: Phase 06 — Conversational AI, only after explicit authorization.
