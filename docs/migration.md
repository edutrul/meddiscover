# Phase 02 migration

## Source and publication boundary

Use the exact local `data/raw/mplus_topics_2026-11-24.xml` snapshot with SHA256 `b71e4ae9d979fe8061ddb0651326fa78a9db93d5c0d7f8bd55b8fdfee0140e93`. The 24 approved English IDs are in `docs/discovery/implementation-selection.json` and the migration definitions. The source adapter rejects missing/changed XML, entity declarations, duplicate topic IDs and non-English selections. XML parsing does not fetch the external DTD or destination URLs.

Source: MedlinePlus, National Library of Medicine. See [publication assessment](discovery/PRESERVATION.md). Raw XML, exhaustive analysis and migration readback remain ignored. Only handcrafted synthetic XML is committed for CI. Do not reproduce external resource bodies or licensed images.

## Install or update

Fresh site: follow README to install the exported configuration. For an existing Phase 01 site, back up the database, check out this branch, run `ddev composer install`, `ddev drush updatedb -y`, `ddev drush config:import -y` and `ddev drush cache:rebuild`. Configuration import enables the custom module and required core modules. Do not rerun site installation on an existing database.

```sh
rtk ddev drush migrate:status --tag=med
rtk ddev drush migrate:import --tag=med --execute-dependencies
rtk proxy python3 scripts/migration/verify.py
```

Dependencies order groups/MeSH/categories before topics, then resources and relationships. Aliases migrate with their parent topic. There are six source migrations. The adapter verifies the checksum before data is read; Migrate uses core SQL ID maps and tracks source-row changes. No approximate vector indexes or embeddings are created.

`verify.py` independently parses XML with Python, reads all stored Drupal fields, compares metadata/counts/multiple values/references, validates safe HTML, reruns migrations and compares IDs and fields. It writes only aggregate evidence to ignored `.local/phase-02-verification.json`. The raw readback script prints source content: keep its output local.

Do not delete migration map tables or manually alter `field_med_key`. A normal rerun is idempotent. An explicit `--update` refreshes source-owned fields and should be reviewed if editors have changed them. Deleted destination entities, source deletions and arbitrary new snapshots are outside this fixed-snapshot workflow.

Core Drush also provides `migrate:messages` for errors. A rollback removes imported entities and can remove manual edits; use only deliberately with a backup, in reverse dependency order (relationships/resources, topics, taxonomies). Never use it as a routine retry.

## Administration

- `/admin/content`: filter by Health Topic, Resource Assignment or Topic Relationship; edit individual nodes.
- `/admin/structure/types`: inspect content types and fields.
- `/admin/structure/taxonomy`: inspect Medical Groups, MeSH Headings and Resource Categories.

The original summary is an unformatted field hidden from public display. Sanitized summary uses the restricted `med_source_html` format. Only administrators have full configuration access; no new broad role permissions are granted. Out-of-corpus relationship references are empty and their source title/URL remain available.

## CI

Foundation CI installs a clean database from exported configuration, verifies a no-diff export, then runs `scripts/migration/test_fixture.php`. The fixture test refuses nonempty imported content, uses the real source/process/entity destination plugins with a synthetic two-topic selection, checks checksum/language/entity rejection, malicious markup, qualifiers, external references, repeated resource URLs and idempotence. Source-count dependency checks are disabled only in the fixture test because production counts refer to 24 topics; its import order is explicit. The full production dependency chain is validated locally against the original XML.

`scripts/migration/build_model.php` documents the one-time development construction of the exported field configuration. Fresh installations should use configuration sync; the script is not a recurring content migration or an installation hook.
