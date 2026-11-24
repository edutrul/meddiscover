# Semantic search setup

Phase 03 search uses a local Ollama embedding model and exact pgvector retrieval in the Drupal PostgreSQL database. Design: [ADR 003](architecture/003-semantic-search.md).

## Prerequisites

1. Phase 02 corpus imported (see [migration](migration.md)).
2. [Ollama](https://ollama.com) running on the host, reachable from DDEV at `http://host.docker.internal:11434`.
3. The pinned embedding model:

   ```bash
   ollama pull nomic-embed-text:v1.5
   ollama list   # ID must start with 0a109f422b47
   ```

The endpoint, model, digest, dimensions, threshold and result limit live in `config/sync/med_search.settings.yml`. If the local model digest differs, search and indexing stop with a provider error instead of mixing vectors from different models.

## Generate embeddings

```bash
ddev drush php:script scripts/search/index.php
```

The first run reports `generated: 24`. Later runs re-embed only changed topics; an unchanged corpus reports `skipped: 24`.

## Use

Open <https://meddiscover.ddev.site/search/semantic> and enter an educational health question, for example “My knees hurt when I walk”. Results show the topic title, description, cosine similarity and MedlinePlus attribution. If no topic reaches the threshold, the page says so.

## Verify

```bash
ddev drush php:script scripts/search/test.php           # synthetic vectors; no provider needed
ddev drush php:script scripts/search/evaluate.php       # real model; synthetic queries
ddev drush php:script scripts/search/check_privacy.php  # after submitting "What is backache?" in the form
```

`test.php` checks vector validation, exact cosine ranking, thresholds and limits, freshness, regeneration, stable identity, provider failures and that no approximate index exists. CI runs it against a clean installation.
