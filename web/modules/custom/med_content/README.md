# medDiscover Content

Provides the content model for MedlinePlus health information and imports it with the core Migrate API. The source is a pinned, checksum-verified MedlinePlus XML snapshot. The other medDiscover modules search, enrich and recommend this content.

For details, see the [content model](../../../../docs/architecture/002-content-model.md) and the [migration instructions](../../../../docs/migration.md).

## Table of contents

- Content model
- Migrations
- Requirements
- Installation
- Usage
- Maintainers

## Content model

| Entity | Bundle | Holds |
|---|---|---|
| Node | `med_health_topic` | A health topic: title, description, plain summary, alternative names ("also called"), see-also references, source URL, MeSH terms and groups |
| Node | `med_resource` | A resource listed by MedlinePlus (handouts, clinical trials, experts…), with its URL, organizations and category |
| Node | `med_relationship` | A typed link between topics: curated related topic, summary hyperlink or language mapping |
| Taxonomy | `med_groups` | MedlinePlus groups, such as "Diabetes Mellitus" |
| Taxonomy | `med_mesh` | MeSH descriptors and qualifiers |
| Taxonomy | `med_resource_categories` | Resource categories, such as "Patient Handouts" |

Bundles and fields ship as exported configuration in `config/sync`.

## Migrations

Six migrations, all tagged `med`, read the XML through the `med_xml` source plugin (`Plugin\migrate\source\MedXml`):

`med_groups`, `med_mesh`, `med_categories` → `med_topics` → `med_resources`, `med_relationships`

- **Checksum first:** the source plugin refuses to read a file whose checksum differs from the reviewed one.
- **Explicit selection:** the approved list of 24 English topic IDs, the same in all six definitions.
- **Idempotent:** a rerun uses the core SQL ID maps.

`Corpus` holds the pure XML-to-array functions. They are static on purpose: no services, comparable to core's `Html` utility. `Hook\TopicAliasHooks` gives each published topic a stable, readable path alias.

## Requirements

- Drupal 11 core: node, taxonomy, text, link, datetime, path_alias, migrate, plus field_ui and views_ui for administration.
- The pinned MedlinePlus XML snapshot, stored locally. Raw XML is not committed; see `docs/discovery`.

## Installation

The module is enabled by the exported configuration (`drush site:install --existing-config`); see the repository README.

## Usage

```sh
ddev drush migrate:status --tag=med
ddev drush migrate:import --tag=med --execute-dependencies
ddev drush php:script scripts/migration/audit.php   # validate every imported entity
```

Do not delete the migration map tables. A rollback removes imported entities, so use it only deliberately, with a backup.

To add topics from the same snapshot, see "Add topics from the same snapshot" in the [migration instructions](../../../../docs/migration.md).

## Maintainers

- Eduardo Telaya — https://github.com/edutrul
