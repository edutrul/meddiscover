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

## Why 24 health topics?

The proof of concept uses 24 English topics to exercise distinct search and navigation cases with a small corpus that can be inspected by hand. Examples include ambiguous aliases, shared MeSH descriptors and qualifiers, curated related topics, and links to patient resources. Spanish counterparts remain source links in the implemented import. See the [selection rationale](discovery/08-minimal-dataset.md).

The [pinned XML snapshot](discovery/01-dataset-profile.md) contains 1,017 English topics and 2,033 topics across both languages. The 24 imported topics represent **2.36% of English topics** (24 ÷ 1,017) and **1.18% of all topics** (24 ÷ 2,033). These percentages count Health Topics only.

## Add topics from the same snapshot

1. Find the English `<health-topic>` in the pinned XML and copy its `id`. Keep the file path and checksum as they are for this snapshot.
2. Append the ID to `source.selection` in all six YAML definitions under `web/modules/custom/med_content/migrations/`. Update `docs/discovery/implementation-selection.json` and review baseline assertions and migration dependency checks that assume 24 topics before running verification.
3. Rebuild the cache, then import with the existing migration maps.

   ```sh
   ddev drush cache:rebuild
   ddev drush migrate:import --tag=med --execute-dependencies
   ddev drush migrate:status --tag=med
   ```

4. Check the new topic and its taxonomy, resource, and relationship references. If an existing relationship pointed to the new topic externally, check that it now references the imported node. Run the verification script with the revised baseline.
5. Index the expanded corpus in Solr and refresh its semantic embeddings. Verify that the new topic appears in both search modes. See [search setup](search.md) and the [semantic indexer](architecture/003-semantic-search.md).

## Administration

The default Health Topic edit form exposes two import fields as text inputs. **Migration identifier** stores the row key, and **MedlinePlus ID** stores the upstream topic ID. Both are `304` for Knee Injuries and Disorders because a topic uses its upstream ID as its row key. The Drupal node ID and the migration map are stored separately. Treat these form fields as import metadata and leave their values intact during editorial changes.

- `/admin/content`: filter by Health Topic, Resource Assignment or Topic Relationship; edit individual nodes.
- `/admin/structure/types`: inspect content types and fields.
- `/admin/structure/taxonomy`: inspect Medical Groups, MeSH Headings and Resource Categories.

The original summary is an unformatted field hidden from public display. Sanitized summary uses the restricted `med_source_html` format. Only administrators have full configuration access; no new broad role permissions are granted. Out-of-corpus relationship references are empty and their source title/URL remain available.

Relationship labels read `Source topic → Target [Curated link | Summary hyperlink | External language mapping]`. External summary links append the destination path so identical anchor text stays distinguishable, and language mappings read “Spanish counterpart”. The original target title is preserved in `field_med_target_title`. Run `ddev drush php:script scripts/migration/audit.php` to validate every imported entity and fail on ambiguous labels or duplicate edges.

## CI

Foundation CI installs a clean database from exported configuration, verifies a no-diff export, then runs `scripts/migration/test_fixture.php`. The fixture test refuses nonempty imported content, uses the real source/process/entity destination plugins with a synthetic two-topic selection, checks checksum/language/entity rejection, malicious markup, qualifiers, external references, repeated resource URLs and idempotence. Source-count dependency checks are disabled only in the fixture test because production counts refer to 24 topics; its import order is explicit. The full production dependency chain is validated locally against the original XML.

`scripts/migration/build_model.php` documents the one-time development construction of the exported field configuration. Fresh installations should use configuration sync; the script is not a recurring content migration or an installation hook.
