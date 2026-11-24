# medDiscover

Educational healthcare search and discovery POC. Current implementation: **Phase 07 — Activation**. Public pages use core Olivero and administration uses core Claro. Authenticated administrators have the classic core toolbar enhanced by Admin Toolbar dropdowns and Extra Tools. The approved English corpus is migrated with Drupal Migrate API. Semantic search at `/search/semantic` uses a local Ollama embedding model and exact pgvector retrieval; Lexical search at `/search/lexical` uses Apache Solr (DDEV add-on) through Search API, and hybrid search at `/search/hybrid` fuses both rankings with Reciprocal Rank Fusion. Conversational search at `/search/conversational` writes short cited educational answers from the retrieved topics with a local LLM, abstains when the topics do not answer, and answers urgent situations with a fixed safety message. Every result shows explainable **Next steps**: calls to action chosen by configurable rules (`/admin/config/search/med-activation`) that link real MedlinePlus resource URLs. Every result is also enriched with terminology, related topics and healthcare resources from the imported structured data. [Master plan](PLAN.md) · [Agent rules](AGENTS.md) · [Foundation report](docs/delivery/phase-01.md) · [Content schema](docs/architecture/002-content-model.md) · [Migration instructions](docs/migration.md) · [Phase 02 report](docs/delivery/phase-02.md) · [Semantic search setup](docs/search.md) · [Search design](docs/architecture/003-semantic-search.md) · [Phase 03 report](docs/delivery/phase-03.md) · [Enrichment design](docs/architecture/004-result-enrichment.md) · [Phase 04 report](docs/delivery/phase-04.md) · [Lexical and hybrid design](docs/architecture/005-lexical-hybrid-search.md) · [Retrieval comparison](docs/evaluation/hybrid-search.md) · [Phase 05 report](docs/delivery/phase-05.md) · [Conversational design](docs/architecture/006-conversational-search.md) · [Conversational evaluation](docs/evaluation/conversational-search.md) · [Phase 06 report](docs/delivery/phase-06.md) · [Activation design](docs/architecture/007-activation.md) · [Phase 07 report](docs/delivery/phase-07.md)

Source: MedlinePlus, National Library of Medicine.

## Requirements

Docker with sufficient available memory, DDEV 1.25.2 or later, Git. Tested locally on macOS arm64. DDEV provides PHP 8.4, Composer and PostgreSQL 17; the database image installs `postgresql-17-pgvector`. The `ddev/ddev-solr` add-on (committed under `.ddev`) runs Solr 9.11 in SolrCloud mode.

## Fresh installation

```sh
git clone https://github.com/edutrul/meddiscover.git
cd meddiscover
# Before Phase 02 merges, use: git switch med-3-content-migration
ddev start
ddev composer install --no-interaction
ddev drush site:install --existing-config --account-name=admin --account-mail=admin@example.invalid -y
ddev drush solr-upload-conf med_solr --numShards=1   # creates the Solr collection from exported config
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
