# medDiscover Activation

Adds explainable **Next steps** to every search result: calls to action chosen by configurable rules. Each one links to a real resource that MedlinePlus lists for that topic, such as patient handouts, clinical trials or experts. Each call to action says why it is shown, credits the organization that provides the resource and is never a medical recommendation.

For the design, see [activation](../../../../docs/architecture/007-activation.md).

## Table of contents

- How it works
- Requirements
- Installation
- Configuration
- Maintainers

## How it works

- **Rules are config entities** (`Entity\ActivationRule`, `med_activation.rule.*`). Each rule has:
  - a resource category, such as "Clinical Trials";
  - a call-to-action label, such as "Explore Clinical Trials";
  - a weight (priority);
  - optional keywords;
  - whether the resource must name an organization.
- **`ActivationEngine`** picks the calls to action for a topic:
  - Only categories that topic actually has resources for are used, in rule priority order.
  - When the search mentions one of a rule's keywords, matching whole words regardless of case and punctuation, that rule moves first and its reason names the keyword.
  - It can add one related topic, taken from an explicit MedlinePlus relationship.
- **`ActivationBuilder`** renders the calls to action. External links show their destination and use `rel="noopener noreferrer"`.
- **`Hook\ActivationHooks`** adds Next steps to the search form (`med_search`) through an object-oriented `form_alter` hook. It reuses the enrichment already computed for each result.

Six rules ship by default, in this order: Start here, Patient handouts, Find an expert, Clinical trials, Journal articles, Learn more.

## Requirements

- `med_search`, and with it `med_content`.

## Installation

The module is enabled by the exported configuration; see the repository README.

## Configuration

| Path | What you can change | Permission |
|---|---|---|
| `/admin/config/search/med-activation` | Add, edit, reorder or disable rules | Administer activation rules |
| `/admin/config/search/med-activation/settings` | Maximum calls to action per topic, whether to add a related topic, and whether external links open in a new tab | Administer activation rules |

Rule and settings values are validated by the configuration schema (`config/schema/med_activation.schema.yml`).

## Maintainers

- Eduardo Telaya — https://github.com/edutrul
