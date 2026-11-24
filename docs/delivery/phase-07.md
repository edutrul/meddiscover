# Phase 07 implementation report

Date: 2026-11-24. Approved scope: Activation. The owner authorized this phase and pre-authorized merge once checks pass.

## Issue and review status

- Issue #23: https://github.com/edutrul/meddiscover/issues/23
- The PR link and CI status are recorded in the PR.
- Prerequisite verified: Phase 06 PR #22 merged; issues #21 and #14 closed.

## Implementation

Design: [ADR 007](../architecture/007-activation.md).

- **`med_activation` module**, separate from search, so the Activation layer stays independent.
- **`med_activation_rule` config entity** (`#[ConfigEntityType]`):
  - Six exported default rules.
  - Drag-and-drop admin list with add, edit and delete forms.
  - Category chosen from imported terms, with schema constraints.
- **`med_activation.settings`**: maximum CTAs, external link behavior and the related-topic switch, edited through a `#config_target` settings form.
- **`ActivationEngine`** (dependency-injected, reuses `ResultEnricher::resources()`): deterministic CTAs with source URLs, organization filters, search-context keyword priority, limits, one explicit related topic and a reason for every item.
- **`ActivationBuilder`**: a Next steps block with a non-clinical notice, attribution, the reason, and external-site wording inside the link text (`rel="noopener noreferrer"`; a new-tab option is announced).
- **`ActivationHooks`**: an autowired OOP `form_alter` hook on all four search forms. `SearchForm` exposes each result's topic and enrichment for reuse.
- **Tests and reporting**: `scripts/activation/test_activation.php` (CI) and `scripts/activation/report.php` (real corpus). The module is added to PHPCS.

## Acceptance criteria

| Criterion | Evidence |
|---|---|
| Contextual recommendations work | Search words move the matching rule first (“doctor” → Explore Directory, “research” → Explore Research, “printable” → Read Patient Resources); tested and shown on the real corpus. |
| Dynamic CTAs work | CTAs depend on each topic's resources, enabled rules, maximum and context; changing a rule's label or priority changes the output (tested). |
| Recommendations are explainable | Every CTA shows “Why: …” with its category count, matching keyword or MedlinePlus relationship. |
| CTA destinations use actual source URLs | 96 of 96 real-corpus CTAs link to URLs found in the topic's imported resources or relationships; the test fails if a topic URL is substituted. |
| External navigation is clearly identified | “(external site: host)” is inside the link text; `rel="noopener noreferrer"`; a new-tab option is announced; internal topic links carry neither (tested and checked over HTTP). |
| No clinical or commercial claims are invented | Labels describe destinations only; the fixed notice says the links are not medical recommendations; no booking, no invented providers, directory CTAs require a named organization, and queries are not stored. |
| Activation rules are tested | The contract test covers ordering, limits, disabled rules, attribution, validation, rendering and the hook. Six deliberately broken versions each made it fail. |

## Real-corpus report

Topics: 24. CTAs checked: 96. URLs not found in the topic's imported resources or relationships: 0. Distinct destination hosts: 23. CTAs per topic: min 4, median 4, max 4.

| Call to action (default page, no search context) | Topics |
|---|---|
| related topic | 24 |
| Read Patient Resources | 22 |
| Explore Directory | 21 |
| Start Here | 20 |
| Explore Research | 4 |
| Explore Clinical Trials | 3 |
| Learn More | 2 |

| Synthetic search | Top result | Next steps (in order) |
|---|---|---|
| What is back pain? | Back Pain | Start Here (“what is”) → Read Patient Resources → Explore Directory → Explore: Back Injuries |
| Are there clinical trials for migraine? | Clinical Trials | Start Here → Read Patient Resources → Explore Directory → Explore: Understanding Medical Research |
| Find a doctor for knee pain | Knee Injuries and Disorders | Explore Directory (“doctor”) → Start Here → Read Patient Resources → Explore: Knee Replacement |
| Latest research on type 2 diabetes | Diabetes Type 2 | Explore Research (“research”) → Start Here → Read Patient Resources → Explore: A1C |
| Printable guide for hay fever | Hay Fever | Read Patient Resources (“printable”) → Start Here → Explore Directory → Explore: Allergy |
| Back pain | Back Pain | Start Here → Read Patient Resources → Explore Directory → Explore: Back Injuries |

## Validation

- **Clean install from exported configuration, in CI order.** `config:status` shows no differences, and all six contract tests pass: migration fixture, semantic, enrichment, hybrid, conversation and activation.
- **Admin pages as administrator.** The rule list (drag-and-drop), the settings page and the edit form return HTTP 200; anonymous users get 403.
- **Search pages over HTTP.** Semantic and conversational pages render Next steps with external labels, source URLs and reasons.
- **Privacy and code quality.** The privacy check passes, PHPCS passes, and `check_publication.py` passes.

## Limitations

- **Unbalanced defaults.** The three common categories usually fill the default three slots; trials, research and Learn More need matching search words.
- **Context only within a topic.** Keywords reorder only a topic's own categories. “Clinical trials for migraine” retrieves the *Clinical Trials* topic, which has no Clinical Trials category, so no trial CTA appears.
- **Simple matching.** Keyword matching is whole-word only, without stemming; the first resource represents its category.
- **Interaction design.** Button styling is Olivero's default. The visual design, HTMX loading states and a Starterkit theme with components are Phase 09.

## Next step

Merge after checks pass and confirm that issue #23 is closed. Recommended next phase: Phase 08 — Optimization, only after explicit authorization.
