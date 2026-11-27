# ADR 003: Local embeddings and exact pgvector retrieval in Drupal

- Date: 2026-11-24
- Status: Implemented; pending review
- Approved scope: Phase 03, issue #5. No lexical/hybrid search, RAG, recommendations or analytics.

## Context

MVP 1 requires a Drupal form that turns a natural-language query into semantic results from the 24 imported English Health Topics. The PLAN requires PostgreSQL with pgvector inside the Drupal database, exact retrieval (no HNSW/IVFFlat), no separate vector database and no real patient data.

## Decision

### Provider and model

Use a local [Ollama](https://ollama.com) server with `nomic-embed-text:v1.5`, pinned by its manifest digest `0a109f422b47e3a30ba2b10eca18548e944e8a23073ee3f3e947efcf3c45e59f`.

- Measured output: 768 finite dimensions with a nonzero norm.
- Context: 2,048 tokens requested with `truncate: false`. The longest topic input embedded without truncation, so oversize input fails loudly instead of silently losing text.
- The model uses task prefixes: `search_document:` for topic inputs and `search_query:` for queries.
- Local inference sends no query or corpus text to a third party and needs no API key.

The previously installed local model (`gemma3:4b`) is a completion/vision model and does not serve embeddings, so it was not used.

### Drupal integration

A small `med_search` module contains:

- `OllamaEmbedder`: a Guzzle client for `/api/tags` and `/api/embed`. It checks the model name and digest before each use, validates the vector's dimensions and values, and replaces provider exceptions with generic messages so query text is never logged.
- `SemanticSearch`: builds the deterministic topic input (`topic-v1`), indexes topics and runs the exact cosine query.
- `SemanticSearchForm`: a Form API page at `/search/semantic` using the `access content` permission and the active Olivero theme.

Drupal AI and its [Ollama Provider](https://www.drupal.org/project/ai_provider_ollama) remain candidates for embedding generation. The initial implementation used the direct adapter while the integrations were prerelease. In a live HTTP comparison with Ollama 0.40.18, the endpoint used by Ollama Provider 1.2.0-rc3 reproduced the same cosine distance for a short query. For a longer input made from three MedlinePlus summaries, `/v1/embeddings` returned HTTP 200 and reported 2,048 prompt tokens. The direct `/api/embed` request with `truncate: false` and a 2,048-token context returned HTTP 400. This compared endpoints directly.

Keep the direct adapter until strict handling of oversized input is available through the provider and the integration can preserve model-digest and vector validation. Track strict mode in [Drupal issue #3612033](https://git.drupalcode.org/project/ai_provider_ollama/-/work_items/3612033) and adoption in [medDiscover issue #17](https://github.com/edutrul/meddiscover/issues/17). Before switching, exercise the provider end to end in Drupal with both inputs.

### Storage

`med_topic_embeddings` (created by `hook_schema()` with a pgvector `vector` column) stores one row per topic, model digest, slot and input version. The table holds the model name, digest, dimensions, SHA-256 content hash, the exact input text and the generation time. Database constraints enforce `vector_dims(embedding) = dimensions` and a cascading foreign key to `node.nid`. No topic content is duplicated outside this reproducible input record.

The topic input concatenates title, meta description, plain-text summary, also-called and see-reference terms, and MeSH descriptor labels. Adding the alias/MeSH metadata improved calibration similarities for vocabulary queries such as “What is HbA1c?” (0.652 → 0.682). It is controlled by `include_metadata`.

### Indexing and freshness

`scripts/search/index.php` takes a lock and verifies the pinned model. It embeds only topics whose input hash, dimensions or model digest changed, then upserts on the unique key. Reruns create no duplicates; an unchanged corpus reports `generated: 0, skipped: 24`.

At query time, a row whose stored hash no longer matches the topic's current input is ignored until it is reindexed. The same applies to topics that are unpublished, non-English or inaccessible to the user, so stale vectors are never shown.

### Retrieval

The query is a bound-parameter SQL statement:
`1 - (embedding <=> CAST(:vector AS vector))`, filtered by model digest, dimensions and input version and ordered by similarity. Without an ANN index, PostgreSQL computes the exact cosine distance for every row. Freshness and access filters run before the result limit, so filtered rows cannot hide valid lower-ranked topics.

Defaults (`med_search.settings`): threshold `0.62`, limit `5` (allowed range 1–20). Queries must be 1–500 characters.

### Threshold

The 0.62 threshold was selected from calibration queries only. In calibration, the lowest top-ranked expected topic scored 0.682 and the highest out-of-domain query scored 0.553. Held-out and out-of-corpus scope-probe queries were then run unchanged. See [semantic evaluation](../evaluation/semantic-search.md).

## Privacy

The form disables form caching, uses POST and keeps results in temporary form state only. Provider errors are replaced before they reach Drupal logging. `scripts/search/check_privacy.php` confirms that a synthetic test query is absent from `cache_form` and `watchdog`. Evaluation uses only synthetic queries.

## Alternatives and consequences

- **Hosted embedding APIs** would add credentials, cost and third-party data transfer for no measured benefit on 24 topics.
- **Approximate indexes** (HNSW/IVFFlat) are out of scope; exact search over 24 rows takes about 1–2 ms.
- **Solr dense vectors / a separate vector database** violate the single-database requirement.
- **Chunking** is unnecessary because every topic fits the model context. Revisit it if longer documents are added.

Search requires a reachable Ollama server with the pinned digest. If the provider is missing or changed, the form shows a generic “temporarily unavailable” message and never falls back to stale or mismatched vectors. Changing the model or input format requires a new digest or `INPUT_VERSION` and a full reindex. Old rows remain until they are explicitly removed.
