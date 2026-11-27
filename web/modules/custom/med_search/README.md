# medDiscover Search

Searches the 24 imported MedlinePlus health topics in four ways and explains how each result was found:

- **Keyword (Lexical):** Apache Solr through Search API, using EDisMax with field and phrase boosts.
- **Semantic:** local Ollama embeddings compared with exact pgvector cosine similarity.
- **Hybrid (Keyword + Semantic):** both rankings merged with Reciprocal Rank Fusion (RRF, k = 60).
- **Ask (Conversational):** retrieval-augmented generation (RAG). Short, cited educational answers written by a local LLM. It abstains when the sources do not answer, and urgent wording gets a fixed safety message first.

Results are enriched with terminology, related topics and healthcare resources from the imported structured data.

For the design and the evidence behind it, see
[semantic search](../../../../docs/architecture/003-semantic-search.md),
[lexical and hybrid](../../../../docs/architecture/005-lexical-hybrid-search.md),
[conversational search](../../../../docs/architecture/006-conversational-search.md) and
[search UI](../../../../docs/architecture/009-search-ui.md).

## Table of contents

- Pages
- How it works
- Code map
- Requirements
- Installation
- Configuration
- Developer mode
- Extending: add a strategy
- Testing
- Maintainers

## Pages

| Path | What it does |
|---|---|
| `/search` (front page) | One box. `HomepageRouter` sends urgent wording to a safety message, questions to Ask (RAG) and anything else to Hybrid. |
| `/search/lexical` | Keyword (Lexical) only |
| `/search/semantic` | Semantic only |
| `/search/hybrid` | Hybrid only |
| `/search/conversational` | Ask (RAG) only |
| `/search/compare` | Runs all three rankings side by side and says what the homepage would answer and why. Developer mode adds a step-by-step technical breakdown, and a flow diagram shows the decision. |
| `/how-it-was-built` | Overview of the build |

## How it works

All five search routes use one form class, `Form\SearchForm`. Each route sets a `strategy` default, and the front page also sets `automatic_strategy: true`:

```yaml
med_search.lexical:
  path: '/search/lexical'
  defaults:
    _form: '\Drupal\med_search\Form\SearchForm'
    strategy: lexical
```

Drupal's `FormController` passes route defaults to `buildForm()` by parameter name:

```php
public function buildForm(array $form, FormStateInterface $form_state, string $strategy = 'semantic', bool $automatic_strategy = FALSE): array {
  $search = $this->strategies->get($strategy);
```

`SearchStrategyRegistry` collects every service tagged `med_search.strategy`, using Drupal's `service_collector`. Each strategy implements `SearchStrategyInterface` and returns the same response shape (`results`, `timings`, `notices`), so the form never depends on how a ranking was produced.

The homepage rule lives in one service, `HomepageRouter`. It is deterministic and uses no model:

```
input → urgent wording?  → safety message (911 / 988), no search, no AI
      → a question?      → Ask (RAG): Semantic finds 3 sources, Ollama writes, every sentence must cite
      → otherwise        → Hybrid: Solr list + pgvector list → RRF → ranked topics
```

A question is input that ends with `?`, starts with a question word (what, why, how…) or starts with an opener such as "is the" or "can I". The trade-off: a question written without a question word ("HbA1c normal range") is treated as a topic search.

## Code map

| Class | Role |
|---|---|
| `SearchStrategyInterface`, `SearchStrategyRegistry` | Strategy contract and tagged-service registry |
| `LexicalSearch` | Solr EDisMax query, `mm` rule, phrase boosts, exact-name promotion |
| `SemanticSearch`, `SemanticStrategy`, `OllamaEmbedder` | Query embedding and exact pgvector similarity with a 0.62 cutoff |
| `HybridSearch` | RRF: `1 / (k + rank)` per list, summed; ranks only, never raw scores |
| `ConversationalSearch`, `OllamaGenerator`, `UrgencyDetector` | RAG pipeline: urgency, retrieval, evidence check, JSON-schema generation, citation and safety verification |
| `HomepageRouter` | The homepage decision: urgent, Ask or Hybrid |
| `ResultEnricher` | Terminology, related topics and resources from structured data (no AI) |
| `Form\SearchForm`, `Form\CompareSearchForm` | The search and Compare pages (form flow only) |
| `Render\SearchResultBuilder`, `Render\SearchDebugPanel` | Result cards and enrichment; the Developer mode panel |
| `Event\SearchCompletedEvent` | Dispatched once per search for `med_analytics`; never carries the query text |
| `Plugin\QueueWorker\TopicEmbedding` | Re-embeds a topic when its published text changes |
| `Hook\SearchThemeHooks` | `hook_theme` for the result card and the Compare templates |

## Requirements

- Drupal 11 with PostgreSQL and the `vector` (pgvector) extension.
- Modules: `med_content`, `search_api` and `search_api_solr`, with the `med_solr` server and the `med_topics` index from the exported configuration.
- [Ollama](https://ollama.com), reachable from the web container, with the embedding model `nomic-embed-text:v1.5` and a generation model (`gemma3:12b` by default).

## Installation

The module is enabled by the exported configuration (`drush site:install --existing-config`); see the repository README. Then:

```sh
ddev drush solr-upload-conf med_solr --numShards=1   # create the Solr collection
ddev drush search-api:index med_topics               # index the topics
ddev drush queue:run med_topic_embedding             # embed the topics
```

## Configuration

`med_search.settings` (`config/install/med_search.settings.yml`, with a schema):

| Key | Default | Meaning |
|---|---|---|
| `endpoint` | `http://host.docker.internal:11434` | Ollama URL |
| `model`, `model_digest`, `dimensions` | `nomic-embed-text:v1.5`, pinned digest, 768 | Embedding model. The digest is pinned, so query and topic vectors always match. |
| `threshold` | 0.62 | Minimum cosine similarity for Semantic |
| `lexical_min_match` | `2<75%` | Solr minimum-should-match |
| `hybrid_depth`, `hybrid_rrf_k` | 20, 60 | Candidates per list and the RRF constant |
| `generation_model`, `generation_*` | `gemma3:12b`, context 8192, 800 tokens, 90 s | Answer model |
| `rag_strategy`, `rag_sources`, `rag_source_chars` | `semantic`, 3, 2500 | Retrieval used for answers, and how much source text the model sees |

Production overrides the endpoint and uses a smaller generation model (`meddiscover-qwen:0.5b`) to fit a CPU-only server.

## Developer mode

Users with the **View search ranking details** permission (`view med search ranking details`) see Solr scores, cosine similarities, RRF points, stage timings, models and the Solr and Ollama requests. On this demo that permission is granted to visitors, and the **Developer mode** block (`Plugin\Block\DeveloperModeBlock`) toggles the view.

```sh
ddev drush med-search:embed-query "knee pain when walking"   # print the query vector as JSON
```

## Extending: add a strategy

1. Implement `SearchStrategyInterface`: `id()`, `label()`, `description()`, `search()` and `describeScore()`.
2. Register it as a service tagged `med_search.strategy`.
3. Add a route with `_form: '\Drupal\med_search\Form\SearchForm'` and `strategy: your_id`.

No form changes are needed: the registry, the form and the Developer mode panel pick it up.

## Testing

PHPUnit (Unit and Kernel), configured in the root `phpunit.xml.dist`:

```sh
ddev exec vendor/bin/phpunit                     # all suites
ddev exec vendor/bin/phpunit --testsuite unit    # RRF, homepage routing, query and answer rules
ddev exec vendor/bin/phpunit --testsuite kernel  # real container: tagged strategies, routes, services
```

Contract tests also run in CI against a synthetic two-topic fixture:

```sh
ddev drush php:script scripts/search/test.php
ddev drush php:script scripts/search/test_enrichment.php
ddev drush php:script scripts/search/test_hybrid.php
ddev drush php:script scripts/search/test_conversation.php
ddev drush php:script scripts/ui/test_ui.php
```

They refuse to run against the real corpus. See `.github/workflows/foundation.yml` for the full sequence.

## Maintainers

- Eduardo Telaya — https://github.com/edutrul
