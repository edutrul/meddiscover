# ADR 002: Drupal core content entities and Migrate API

- Date: 2026-11-24
- Status: Implemented; pending review
- Approved scope: Phase 02, issue #3. No embeddings or search.

## Context

Import the fixed 24-topic English selection while preserving the MedlinePlus source metadata, resource assignments and typed relationships. Drupal must own content storage and administration. Historical discovery recommendations remain unchanged.

## Decision

Use three node bundles (`med_health_topic`, `med_resource`, `med_relationship`) and three vocabularies (`med_groups`, `med_mesh`, `med_resource_categories`). Core fields and entity references express the conceptual model. No parallel custom content tables or custom entity types are created. Migrate owns its standard ID-map/message tables.

Aliases are two separate multivalue fields on a topic: also-called and see-reference. They retain source term type without assuming clinical equivalence. MeSH terms represent descriptor/qualifier pairs; a descriptor without a qualifier is its own heading. This avoids losing which qualifier belongs to which descriptor. Organizations and standard descriptions remain separate multivalue source strings; they do not require independent taxonomies for this corpus.

Resource nodes are per-topic **assignments**, not unique destination pages. A resource URL can recur with different categories/organizations/context. Relationship nodes keep source topic, type and provenance plus external target metadata. An entity reference is populated only for an imported English target; there are no stubs or Spanish imports. Summary links, curated related topics and language mappings remain distinguishable. No inferred associations are generated.

Core entity form/view displays and Views administration make records manageable. Raw summary HTML and migration keys are hidden from public display; raw markup is stored as an unformatted long string. The safe summary is sanitized on extraction and rendered through an additional restricted text format. No external resource bodies are fetched.

## Field schema

All imported entities have `field_med_key`: a stable source key used by migration/readback. Core node `title` and `langcode` hold labels and English language. Entity IDs are Drupal-generated.

### Health Topic — `med_health_topic`

| Field | Type / cardinality |
|---|---|
| `field_med_source_id` | String / one |
| `field_med_description` | Unformatted long string / one |
| `field_med_summary_raw` | Unformatted long string / one; hidden from display |
| `field_med_summary_html` | Formatted long text / one; `med_source_html` format |
| `field_med_summary_plain` | Unformatted long string / one; hidden from display |
| `field_med_source_url` | External link / one |
| `field_med_source_date` | Date / one; source date normalized to ISO |
| `field_med_snapshot_date` | Date / one |
| `field_med_attribution` | String / one |
| `field_med_also_called` | String / many |
| `field_med_see_reference` | String / many |
| `field_med_groups` | Term reference to `med_groups` / many |
| `field_med_mesh` | Term reference to `med_mesh` / many |

### Resource Assignment — `med_resource`

| Field | Type / cardinality |
|---|---|
| `field_med_topic` | Health Topic reference / one |
| `field_med_url` | External link / one |
| `field_med_mapped_url` | External link / optional |
| `field_med_categories` | Term reference to `med_resource_categories` / many |
| `field_med_organizations` | Unformatted long string / many |
| `field_med_standard_desc` | Unformatted long string / many |

### Topic Relationship — `med_relationship`

| Field | Type / cardinality |
|---|---|
| `field_med_topic` | Source Health Topic reference / one |
| `field_med_target` | In-corpus Health Topic reference / optional |
| `field_med_relation_type` | String: `curated`, `summary_link`, `language_mapping` |
| `field_med_provenance` | String naming the source relationship class |
| `field_med_target_source_id` | String / optional for unresolved external summary links |
| `field_med_target_title` | String / one |
| `field_med_target_url` | External link / one |

### Taxonomy terms

- **`med_groups`**: core `name`, `field_med_key`, `field_med_source_id`, `field_med_source_url`.
- **`med_mesh`**: core `name`, `field_med_key`, `field_med_source_id` (descriptor ID), `field_med_descriptor_label`, `field_med_qualifier_id`, `field_med_qualifier_label`.
- **`med_resource_categories`**: core `name`, `field_med_key` (SHA256 of the exact category label; the source has no category ID).

## Module evaluation

Core Migrate (installed Drupal 11.4.13) supplies entity destinations, lookup/sub-process pipelines, dependency ordering and durable ID maps. Installed Drush 13.8.1 already provides migration import/status/messages/rollback commands. A small `med_content` source adapter is justified by the fixed checksum, approved-ID selection, HTML handling and cross-topic reference resolution; all destinations and process plugins remain core.

[Migrate Plus](https://www.drupal.org/project/migrate_plus) offers XML parsers, config entities and extra process plugins. None is required by the fixed offline adapter, so it is not installed. [Migrate Tools](https://www.drupal.org/project/migrate_tools) 6.1.5 advertises Drupal 11 compatibility, but its additional UI/commands are unnecessary for this phase because Drush supplies the required commands. [Drupal AI](https://www.drupal.org/project/ai) and [Key](https://www.drupal.org/project/key) were reviewed for their provider/credential capabilities and deferred: no provider calls or credentials are needed in Phase 02. Re-evaluate concrete provider compatibility in Phase 03 before installing them.

## Quality checks

Custom runtime PHP is checked against Drupal coding standards using PHPCS and drupal/coder 8.3.31, now added as development-only Composer dependencies. Synthetic integration assertions and independent full-corpus verification exercise behavior. PHPStan and a PHPUnit dependency remain deferred; no claim is made that procedural tests provide their static-analysis or framework coverage.

## Alternatives and consequences

Custom tables/entities increase maintenance and duplicate Drupal infrastructure. Paragraphs/entity-reference revisions add dependencies without a demonstrated need for 24 topics. A single JSON metadata field would conceal useful relationships from Drupal forms and reference validation. Nodes make resource/relationship records editable but add roughly 1,200 administrative records; this is acceptable for the POC and can be filtered through the content list. Standard Views installed by core are retained; no custom frontend or recommendation rendering is added.

Migrate source keys and ID maps must be retained. Default reruns skip unchanged records; `--update` deliberately overwrites source-owned fields and can supersede manual edits. This is a fixed-snapshot importer, not a live synchronization/deletion engine. Resource identity uses topic ID plus snapshot site ordinal; resource ordering changes require a reviewed new-snapshot strategy. Original raw XML and full generated/readback evidence remain outside Git.
