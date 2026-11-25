# Search setup

medDiscover has three search modes over the same 24 English Health Topics, sharing one form, result structure and enrichment:

| Route | Strategy | Design |
|---|---|---|
| `/search/semantic` | Local Ollama embeddings and exact pgvector cosine search | [ADR 003](architecture/003-semantic-search.md) |
| `/search/lexical` | Apache Solr through Search API, with field boosts and exact-name matching | [ADR 005](architecture/005-lexical-hybrid-search.md) |
| `/search/hybrid` | Reciprocal Rank Fusion of the lexical and semantic rankings | [ADR 005](architecture/005-lexical-hybrid-search.md) |
| `/search/conversational` | Short cited educational answer from retrieved topics (local LLM), with abstention and urgent-situation handling | [ADR 006](architecture/006-conversational-search.md) |

Each page links to the other modes, so the same question can be compared across them. With JavaScript, the form submits in place (core HTMX) with a loading indicator; without it, the page reloads as before ([ADR 009](architecture/009-search-ui.md)). The comparison results are in [docs/evaluation/hybrid-search.md](evaluation/hybrid-search.md).

## Prerequisites

1. Phase 02 corpus imported (see [migration](migration.md)).
2. [Ollama](https://ollama.com) running on the host, reachable from DDEV at `http://host.docker.internal:11434`.
3. The pinned embedding model:

   ```bash
   ollama pull nomic-embed-text:v1.5
   ollama list   # ID must start with 0a109f422b47
   ```

The endpoint, model, digest, dimensions, threshold and result limit live in `config/sync/med_search.settings.yml`. If the local model digest differs, search and indexing stop with a provider error instead of mixing vectors from different models.

## Solr (lexical and hybrid)

`ddev start` also starts Solr 9.11 (the `ddev/ddev-solr` add-on, pinned in `.ddev/docker-compose.solr-meddiscover.yaml`). Create the collection once per environment from the exported Search API configuration, then index the topics:

```bash
ddev drush solr-upload-conf med_solr --numShards=1   # once; also after Search API Solr upgrades
ddev drush search-api:index med_topics               # 24 items; later edits are indexed on save
ddev drush search-api:status                         # med_topics 100%
```

The Solr admin UI opens with `ddev solr-admin` (local default user `solr`, password `SolrRocks`; these are the add-on's public development defaults, injected by `settings.php` only under DDEV). Solr logs only warnings and has no request log, so query text is never written to Solr logs.

## Conversational search model

Conversational search uses a local Ollama chat model, pinned by digest like the embedding model:

```bash
ollama pull gemma3:12b
ollama list   # ID must start with b92b218582c5
```

Settings in `med_search.settings`:

| Setting | Default | Meaning |
|---|---|---|
| `generation_model`, `generation_model_digest` | `gemma3:12b`, `b92b2185…` | Model tag and pinned digest |
| `rag_strategy` | `semantic` | Retrieval strategy: `semantic`, `lexical` or `hybrid` |
| `rag_sources` | 3 | Topics given to the model |
| `rag_source_chars` | 2500 | Characters given per topic |
| `generation_max_tokens` | 800 | Output token cap |
| `generation_timeout` | 90 s | Request timeout |

To use the faster Apache-2.0 alternative, `ollama pull qwen3:8b` and set both `generation_model` and `generation_model_digest`. If the model is missing or its digest differs, the page lists the matching topics and says that no answer could be generated.

## Generate embeddings

```bash
ddev drush php:script scripts/search/index.php
```

The first run reports `generated: 24`. Later runs re-embed only changed topics; an unchanged corpus reports `skipped: 24`.

## Use

Open <https://meddiscover.ddev.site/search/semantic>, `/search/lexical` or `/search/hybrid` and enter an educational health question, for example “My knees hurt when I walk”. Results show the topic title, description, a score explanation and MedlinePlus attribution:

- **Semantic** shows the cosine similarity. Topics below the 0.62 threshold are not shown.
- **Lexical** shows the Solr score, which is only comparable within one search. Short queries must match every word, and longer ones must match 75 % of them (`mm=2<75%`). A query that exactly names a topic or one of its alternative terms ranks that topic first. If the name belongs to several topics (for example “Sugar diabetes”), the page says so and lists them.
- **Hybrid** shows the RRF score and the topic's rank in each strategy (k = 60). If Solr or Ollama is unavailable, hybrid uses the remaining strategy and says so.

- **Conversational** shows a short educational answer. Each statement carries a numbered citation, and a Sources list links the topic pages and MedlinePlus. If no topic matches, or the topics do not answer the question, it says so and writes no answer. Urgent wording, such as chest pain or trouble breathing, gets an emergency message (911, or 988 for crisis) without calling the model. Answers take about 5–20 seconds.

If nothing matches, the page says so.

Each result also shows **Next steps** (`med_activation`, [ADR 007](architecture/007-activation.md)): up to three calls to action such as *Explore Directory* or *Explore Clinical Trials*, plus one related topic. Each one links the first MedlinePlus-listed resource of its category with its source URL, organization and a reason. Words in the search (for example “doctor”, “trial” or “research”) move the matching action first. Administrators manage the rules at `/admin/config/search/med-activation`.

Each result has three collapsible sections, built from imported data without extra AI calls ([ADR 004](architecture/004-result-enrichment.md)):

- **Explore Health Information**: alternative terms, MedlinePlus groups, MeSH descriptors and non-topic links from the summary.
- **Related Content**: explicit MedlinePlus relationships first, then up to five inferred topics sharing a MeSH descriptor or group. Each item says why it is listed.
- **Explore Healthcare Resources**: external resources grouped by category, with original URLs and organizations.

## Verify

```bash
ddev drush php:script scripts/search/test.php           # synthetic vectors; no provider needed
ddev drush php:script scripts/search/test_enrichment.php # synthetic fixture enrichment
ddev drush php:script scripts/search/evaluate.php       # real model; synthetic queries
ddev drush php:script scripts/search/test_hybrid.php     # synthetic fixture: RRF, lexical, hybrid (needs Solr)
ddev drush php:script scripts/search/compare.php         # real corpus, Solr and model; comparison tables
ddev drush php:script scripts/search/test_conversation.php     # synthetic fixture; stub retrieval and model
ddev drush php:script scripts/search/evaluate_conversation.php # real corpus and model; grounding and safety
ddev drush php:script scripts/search/llm_bakeoff.php           # compare installed local chat models
ddev drush php:script scripts/activation/test_activation.php   # synthetic fixture; activation rules and CTAs
ddev drush php:script scripts/activation/report.php            # real corpus; CTA coverage and URL fidelity
ddev drush php:script scripts/analytics/test_analytics.php     # synthetic fixture; aggregate analytics contract
ddev drush php:script scripts/optimization/quality.php queries=docs/evaluation/phase-08-holdout-queries.json baseline=docs/evaluation/baselines/holdout-before.json
ddev drush php:script scripts/optimization/benchmark.php       # real stack; stage latency
ddev drush php:script scripts/ui/test_ui.php                   # synthetic fixture; theme, components, HTMX and states
ddev drush php:script scripts/search/check_privacy.php  # after submitting "What is backache?" in the forms
ddev exec -s solr grep -rlc "backache" /var/solr/logs || echo "no query text in Solr logs"
```

`test_hybrid.php` checks RRF arithmetic and tie-breaks, title, alias and exact-name matching, ambiguity notices, minimum-should-match, query escaping, unpublished exclusion, hybrid fusion with a stub semantic strategy, deduplication, limits and degradation when a strategy fails. It refuses to run against the real corpus.

`test.php` checks vector validation, exact cosine ranking, thresholds and limits, freshness, regeneration, stable identity, provider failures and that no approximate index exists. CI runs it against a clean installation.
