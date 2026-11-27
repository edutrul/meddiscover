# medDiscover

An educational healthcare search and discovery proof of concept built on **Drupal 11**. It searches MedlinePlus health topics with keyword, semantic, hybrid and conversational (RAG) search, explains how every result was found and suggests trusted next steps. Answers and embeddings come from local models (Ollama), and no query text is ever stored.

**Live demo:** https://meddiscover.eduardotelaya.com · Source: MedlinePlus, National Library of Medicine.

## What you can try

| Page | What it shows |
|---|---|
| [`/search`](https://meddiscover.eduardotelaya.com/search) (front page) | One search box. Topic phrases ("knee pain") get a ranked **Hybrid** list; questions ("What is HbA1c?") get a short **cited answer**; urgent wording ("chest pain right now") gets a safety message first. |
| `/search/lexical` · `/search/semantic` · `/search/hybrid` · `/search/conversational` | Each search method on its own |
| [`/search/compare`](https://meddiscover.eduardotelaya.com/search/compare) | All three rankings side by side, what the homepage would answer and why, a flow diagram, and (in Developer mode) a step-by-step technical breakdown with live Solr scores, cosine similarities and RRF points |
| `/how-it-was-built` | Build overview |

How the front page decides (one deterministic rule, no model):

```
input → urgent wording?  → safety message (911 / 988), no search, no AI
      → a question?      → Ask (RAG): Semantic finds 3 sources, Ollama writes, every sentence must cite
      → otherwise        → Hybrid: Solr list + pgvector list → Reciprocal Rank Fusion → ranked topics
```

## Architecture

| Part | Technology | README |
|---|---|---|
| Content model and import | Nodes and taxonomies; core Migrate API from a checksum-verified MedlinePlus XML snapshot (24 English topics) | [med_content](web/modules/custom/med_content/README.md) |
| Search | Apache Solr (Search API, EDisMax) · Ollama `nomic-embed-text` + PostgreSQL pgvector · RRF fusion · RAG with a local LLM, JSON-schema output and citation verification | [med_search](web/modules/custom/med_search/README.md) |
| Next steps | Rule-based calls to action (config entities) linking real MedlinePlus resources | [med_activation](web/modules/custom/med_activation/README.md) |
| Metrics | Aggregate-only daily counters; no queries, URLs or identifiers | [med_analytics](web/modules/custom/med_analytics/README.md) |
| Front end | Custom Starterkit theme, Single Directory Components, core HTMX; Claro for admin | [meddiscover theme](web/themes/custom/meddiscover/README.md) |

## Documentation

- **Plan and rules:** [master plan](PLAN.md) · [agent rules](AGENTS.md) · [contributing](CONTRIBUTING.md)
- **Design:**
  - [foundation](docs/architecture/001-foundation.md)
  - [content model](docs/architecture/002-content-model.md)
  - [semantic search](docs/architecture/003-semantic-search.md)
  - [enrichment](docs/architecture/004-result-enrichment.md)
  - [lexical and hybrid](docs/architecture/005-lexical-hybrid-search.md)
  - [conversational](docs/architecture/006-conversational-search.md)
  - [activation](docs/architecture/007-activation.md)
  - [optimization](docs/architecture/008-optimization.md)
  - [search UI](docs/architecture/009-search-ui.md)
- **How-to:** [migration](docs/migration.md) · [search setup](docs/search.md) · [analytics contract](docs/analytics.md)
- **Evidence:**
  - [semantic search](docs/evaluation/semantic-search.md)
  - [retrieval comparison](docs/evaluation/hybrid-search.md)
  - [conversational](docs/evaluation/conversational-search.md)
  - [optimization](docs/evaluation/optimization.md)
- **Delivery reports:** phases [01](docs/delivery/phase-01.md) · [02](docs/delivery/phase-02.md) · [03](docs/delivery/phase-03.md) · [04](docs/delivery/phase-04.md) · [05](docs/delivery/phase-05.md) · [06](docs/delivery/phase-06.md) · [07](docs/delivery/phase-07.md) · [08](docs/delivery/phase-08.md) · [09](docs/delivery/phase-09.md)

## Requirements

Docker with sufficient available memory, DDEV 1.25.2 or later, Git. Tested locally on macOS arm64. DDEV provides PHP 8.4, Composer and PostgreSQL 17; the database image installs `postgresql-17-pgvector`. The `ddev/ddev-solr` add-on (committed under `.ddev`) runs Solr 9.11 in SolrCloud mode. Search and answers also need [Ollama](https://ollama.com) reachable from the web container, with `nomic-embed-text:v1.5` and a generation model (`gemma3:12b` by default); see the [med_search README](web/modules/custom/med_search/README.md).

## Fresh installation

```sh
git clone https://github.com/edutrul/meddiscover.git
cd meddiscover
ddev start
ddev composer install --no-interaction
ddev drush site:install --existing-config --account-name=admin --account-mail=admin@example.invalid -y
ddev drush solr-upload-conf med_solr --numShards=1   # creates the Solr collection from exported config
ddev drush migrate:import --tag=med --execute-dependencies   # needs the local XML snapshot; see docs/migration.md
ddev drush search-api:index med_topics
ddev drush queue:run med_topic_embedding
ddev drush --uri=https://meddiscover.ddev.site uli
```

The installer creates a local administrator password; do not share its output. The last command generates a short-lived login URL. Open https://meddiscover.ddev.site. This is a local development environment, not a production deployment. No fixed administrator password is committed.

`site:install` is for a fresh database and is destructive if repeated against an existing site. For ordinary startup use `ddev start`; stop only this project with `ddev stop`. Generated settings/credentials and files stay ignored. `settings.php` only includes local DDEV settings and defines the shared configuration path. Non-DDEV deployment requires separate environment configuration and is outside Phase 01.

## Validation and configuration

```sh
ddev composer validate --strict
ddev composer audit
ddev exec vendor/bin/phpcs
ddev drush status
ddev drush php:script scripts/foundation/check.php
ddev drush config:status
python3 scripts/foundation/check_publication.py
```

DDEV post-start hooks idempotently enable `pg_trgm` and `vector` in Drupal's `db` database and fail startup on error. Export intentional configuration changes with `ddev drush config:export -y`, review and commit `config/sync`. CI installs a clean database from exported configuration and verifies a no-diff export, database behavior and login-page HTTP response.

## Discovery evidence

Historical reports, original selection and scripts are preserved under `docs/discovery` and `scripts/discovery`. The separately approved English corpus has **24 topics, 882 resource assignments and 744 unique resource URLs**. The local Drupal database contains all 24 approved topics, their resource assignments and typed relationships. A fresh clone needs the original XML and the documented migration command to populate content.

Raw XML and exhaustive generated analysis remain local and excluded from Git. See [preservation and reuse assessment](docs/discovery/PRESERVATION.md). Historical verification requires the exact XML snapshot and locally generated `dataset-profile.json`; it cannot run from a public clone alone. It does not validate the external DTD or destination-page availability.

## Workflow

Use GitHub milestones and issues, issue-specific branches and reviewed PRs. See [CONTRIBUTING.md](CONTRIBUTING.md). The minimal bootstrap commit established main with README, supplied PLAN and ignore rules before issue-based implementation was possible. No further direct commits to main are allowed. Owner approval is required for merging and for each new phase.
