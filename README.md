# medDiscover

Educational healthcare search and discovery POC. Current implementation: **Phase 01 — Project Foundation**. Public pages use core Olivero and administration uses core Claro. Authenticated administrators have the classic core toolbar enhanced by Admin Toolbar dropdowns and Extra Tools. Content migration and search are not implemented. [Master plan](PLAN.md) · [Agent rules](AGENTS.md) · [Foundation report](docs/delivery/phase-01.md)

Source: MedlinePlus, National Library of Medicine.

## Requirements

Docker with sufficient available memory, DDEV 1.25.2 or later, Git. Tested locally on macOS arm64. DDEV provides PHP 8.4, Composer and PostgreSQL 17; the database image installs `postgresql-17-pgvector`.

## Fresh installation

```sh
git clone https://github.com/edutrul/meddiscover.git
cd meddiscover
# Until the foundation PR is merged, check out med-1-project-foundation.
ddev start
ddev composer install --no-interaction
ddev drush site:install --existing-config --account-name=admin --account-mail=admin@example.invalid -y
ddev drush --uri=https://meddiscover.ddev.site uli
```

The installer creates a local administrator password; do not share its output. The last command generates a short-lived login URL. Open https://meddiscover.ddev.site. This is a local development environment, not a production deployment. No fixed administrator password is committed.

`site:install` is for a fresh database and is destructive if repeated against an existing site. For ordinary startup use `ddev start`; stop only this project with `ddev stop`. Generated settings/credentials and files stay ignored. `settings.php` only includes local DDEV settings and defines the shared configuration path. Non-DDEV deployment requires separate environment configuration and is outside Phase 01.

## Validation and configuration

```sh
ddev composer validate --strict
ddev composer audit
ddev drush status
ddev drush php:script scripts/foundation/check.php
ddev drush config:status
python3 scripts/foundation/check_publication.py
```

DDEV post-start hooks idempotently enable `pg_trgm` and `vector` in Drupal's `db` database and fail startup on error. Export intentional configuration changes with `ddev drush config:export -y`, review and commit `config/sync`. CI installs a clean database from exported configuration and verifies a no-diff export, database behavior and login-page HTTP response.

## Discovery evidence

Historical reports, original selection and scripts are preserved under `docs/discovery` and `scripts/discovery`. The separately approved English corpus has **24 topics, 882 resource assignments and 744 unique resource URLs**. No topics have been imported into Drupal.

Raw XML and exhaustive generated analysis remain local and excluded from Git. See [preservation and reuse assessment](docs/discovery/PRESERVATION.md). Historical verification requires the exact XML snapshot and locally generated `dataset-profile.json`; it cannot run from a public clone alone. It does not validate the external DTD or destination-page availability.

## Workflow

Use GitHub milestones and issues, issue-specific branches and reviewed PRs. See [CONTRIBUTING.md](CONTRIBUTING.md). The minimal bootstrap commit established main with README, supplied PLAN and ignore rules before issue-based implementation was possible. No further direct commits to main are allowed. Owner approval is required for merging and for Phase 02.
