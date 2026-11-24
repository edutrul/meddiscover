# ADR 001: Drupal foundation on DDEV PostgreSQL

- Status: Implemented; pending owner review
- Date: 2026-11-24

## Context

Phase 01 requires reproducible Drupal 11 infrastructure with vectors in the CMS database. No content migration, embeddings or search is approved yet.

## Decision

Use the Drupal recommended Composer project, Drupal 11.4.13 (locked), Drush 13.8.1, the core minimal profile, PHP 8.4 and PostgreSQL 17 in DDEV 1.25.2+. Install pgvector through `dbimage_extra_packages` and enable vector/pg_trgm with a failing-on-error post-start SQL hook. Store exported Drupal configuration in `config/sync` and keep environment credentials/generated code outside Git. Preserve the English-only 24-topic implementation manifest independently of historical discovery.

## Alternatives

MariaDB with a separate vector database increases infrastructure and conflicts with the approved same-database requirement. Drupal standard profile installs unnecessary content features. A custom container/database service duplicates DDEV management. PostgreSQL 16 is supported, while 17 is also compatible and available locally. No approximate vector indexes are needed.

## Rationale

Drupal's PostgreSQL requirements specify PostgreSQL 16+ and pg_trgm. PHP 8.4 is compatible with the locked Drupal dependencies. Package-managed pgvector extends the DDEV database without another service. Composer lock and exported configuration support reproducible installation.

References: https://www.drupal.org/docs/getting-started/system-requirements/database-server-requirements ; https://docs.ddev.com/en/stable/users/extend/customizing-images/

## Consequences

The Debian package revision and DDEV image patch contents can evolve; Composer application dependencies are locked, but this is not a fully byte-identical infrastructure build. Verify extension behavior on updates. A clean separate database was successfully installed from configuration. No embeddings, content entities or search code were added.

## Quality tools considered

Use Composer strict validation/audit, PHP syntax checks and Drupal/database/configuration smoke assertions now, plus GitHub Actions. Defer PHPCS/Drupal standards, PHPStan and PHPUnit dependencies until custom Drupal application code needs them. The current check script exercises real PostgreSQL behavior without introducing a test framework. Audit results are time-sensitive, not a security guarantee.
