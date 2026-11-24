# Phase 03 implementation report

Date: 2026-11-24. Approved scope: Basic Semantic Search. The implementation is ready for review. Merge and phase acceptance are pending owner approval. Phase 04 is not authorized.

## Issue and review status

- Issue #5: https://github.com/edutrul/meddiscover/issues/5 — semantic search (MVP 1).
- Issue #6: https://github.com/edutrul/meddiscover/issues/6 — full import audit and relationship-label clarification, requested by the owner during this phase.
- Both are delivered in one Phase 03 PR. Its link and CI status are recorded in the PR.
- Prerequisite verified: Phase 02 PR #4 merged and issue #3 closed.

## Implementation

- **Provider:** local Ollama `nomic-embed-text:v1.5`, pinned by digest, 768 dimensions, no truncation. See [ADR 003](../architecture/003-semantic-search.md).
- **Storage:** `med_topic_embeddings` in Drupal PostgreSQL with a pgvector column. It records the model, digest, dimensions, input version, SHA-256 content hash and the exact input. Database constraints check dimensions and cascade on topic deletion.
- **Indexing:** `scripts/search/index.php`. All 24 English topics are embedded; an unchanged rerun skips all 24.
- **Retrieval:** bound-parameter exact cosine search with no ANN index. Access, freshness and language are filtered before the limit. Defaults: threshold 0.62, limit 5.
- **Interface:** `/search/semantic` Form API page in Olivero. It shows the title link, description, cosine similarity and MedlinePlus attribution, plus clear messages for no results and provider unavailability. An educational, non-diagnostic notice is shown.
- **Privacy:** POST only, form caching disabled, temporary state only, and provider exceptions replaced so query text is never logged.

Setup and usage: [docs/search.md](../search.md).

## Import audit (issue #6)

The owner's screenshots showed repeated relationship titles and Spanish text. `scripts/migration/audit.php` validated all **1,309** imported entities (24 topics, 882 resources, 331 relationships and 72 terms) under administrator permissions, alongside the independent XML verification (`scripts/migration/verify.py`).

- **English only.** All 24 Health Topics are English. The 24 Spanish titles were external language-mapping metadata pointing to Spanish MedlinePlus pages, not imported Spanish topics. They have no in-corpus reference.
- **No duplicate imports.** There are zero repeated source/type/target edges.
- **57 ambiguous title groups.** These were distinct records with the same visible title: the same target reached by different link types, or identical anchor text pointing to different external pages. The labels used the numeric source ID and raw anchor text.
- **Fix:** labels are now `Source topic → Target [Curated link | Summary hyperlink | External language mapping]`. External summary links add the destination path, and language mappings read “Spanish counterpart”. The original target title remains in `field_med_target_title`, relabeled “Target source title (original language)”.
- **Preservation:** a full readback before and after the update matched for every node/term ID, field, source value and reference, with only relationship labels changed. The audit now fails if ambiguous titles or duplicate edges reappear, and the synthetic fixture asserts English language-mapping labels.

## Validation

| Check | Result |
|---|---|
| Provider probe: dimensions, finiteness, no truncation of longest topic | Pass |
| All 24 topics embedded; rerun `generated 0, skipped 24` | Pass |
| `scripts/search/test.php` (synthetic vectors: ranking, thresholds/limits, freshness, regeneration, identity, provider failures, no ANN index) | Pass, on a clean install from config after the synthetic fixture migration (the script refuses to run against the real corpus) |
| `scripts/migration/test_fixture.php` on a clean install | Pass |
| Clean install from exported config, then `config:status` | No differences |
| Evaluation, 18 synthetic queries ([results](../evaluation/semantic-search.md)) | All 10 positive queries ranked an expected topic first; all 8 negative/out-of-corpus queries returned nothing |
| Browser: form submission, real results with scores, empty state, provider-failure message (provider stopped and restored) | Pass |
| `scripts/search/check_privacy.php` after a synthetic form query | Query absent from `cache_form` and `watchdog` |
| `scripts/migration/audit.php`, `scripts/migration/verify.py` | Pass |
| PHPCS Drupal standards (now including `.install`/`.module` files) | Pass |

The temporary clean-install database and site directory are local only and excluded from Git.

## Limitations

- Search needs a local Ollama server with the pinned digest. CI uses synthetic vectors and does not run the real model.
- The 0.62 threshold is calibrated on 24 topics and a small query set; the margin over out-of-domain queries is about 0.13. Recalibrate when the corpus grows.
- One embedding per topic, with no chunking; every current topic fits the context window.
- No clinical-correctness or general quality claims; Phase 08 covers broader evaluation.
- Changing the model or input format requires a new digest or input version and a full reindex. Old rows are not pruned automatically.

## Next step

The owner reviews the Phase 03 PR after checks pass, then approves the merge and confirms that issues #5 and #6 are closed. Recommended next phase: Phase 04 — Search Enrichment, only after explicit authorization.
