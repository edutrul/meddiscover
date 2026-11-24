# ADR 007: Rule-based, explainable activation

- Date: 2026-11-24
- Status: Implemented; pending review
- Approved scope: Phase 07, issue #23. No booking, fictional providers, AI personalization, tracking, analytics or visual redesign.

## Context

Phases 03–06 find, enrich and explain topics. PLAN Phase 07 (the four-layer framework's **Activation** layer) turns discovery into next actions:

- Calls to action (CTAs) come from resource metadata and use source URLs.
- Configurable business rules decide categories, labels, priority, maximums, attribution and external-link behavior.
- Every recommendation is explainable.
- Content relevance is kept separate from clinical recommendations.
- Nothing is commercially targeted from health queries.

## Decision

### A separate module

Activation lives in `med_activation`, which depends on `med_search`; nothing depends on it. An object-oriented hook (`#[Hook('form_alter')]` in `ActivationHooks`, autowired) adds a **Next steps** block to every result on the four search routes. `SearchForm` exposes each result's topic and its already computed enrichment (`#med_topic`, `#med_enrichment`), so activation reuses the Phase 04 resource grouping without querying twice.

### Business rules as configuration entities

`med_activation_rule` is a config entity declared with the `#[ConfigEntityType]` attribute. Rules are exported in `config/sync` and managed at *Configuration → Search and metadata → medDiscover activation rules*:

- **Admin pages.** The rule list is drag-and-drop ordered. The add, edit and delete forms use core's admin route provider and require the restricted `administer med activation` permission.
- **Category choice.** A rule's category is chosen from the imported `med_resource_categories` terms, so a rule cannot point at a category that does not exist.

| Rule property | Purpose |
|---|---|
| `category` | MedlinePlus resource category, for example Clinical Trials |
| `cta_label` | Visible label, for example Explore Clinical Trials |
| `weight` | Display priority |
| `keywords` | Search-context words that move the rule first |
| `require_organization` | Only link resources that name their provider |
| `status` | Enabled or disabled |

`med_activation.settings` holds `max_ctas` (3), `external_behavior` (`same_window`, the accessible default, or `new_window`) and `include_related_topic`. The settings form uses `#config_target`, and schema constraints (`Range`, `Choice`, `NotBlank`) validate both settings and rules.

Default rules, which include the PLAN examples:

| Priority | Category | CTA | Keywords (examples) | Organization required |
|---|---|---|---|---|
| 0 | Start Here | Start Here | what is, overview, basics | no |
| 1 | Patient Handouts | Read Patient Resources | handout, printable, guide | no |
| 2 | Find an Expert | Explore Directory | doctor, specialist, physician | **yes** |
| 3 | Clinical Trials | Explore Clinical Trials | trial, study, volunteer | no |
| 4 | Journal Articles | Explore Research | research, journal, evidence | no |
| 5 | Learn More | Learn More | more information, details | no |

Directory CTAs require a named organization so a user always knows who runs a provider directory.

### Recommendation engine

`ActivationEngine::recommend($topic, $query, $enrichment)` is a deterministic, dependency-injected service:

1. **Resource CTAs.** For each enabled rule, it takes the topic's own resources in that category (in MedlinePlus source order), filtered by organization when required. The CTA links the **first resource's source URL** and states how many resources exist. It never builds, rewrites or guesses URLs.
2. **Search context.** A rule whose keyword appears in the query (whole words, case- and punctuation-insensitive) moves ahead of rules without a match. The query is used only while building the page, never stored or logged.
3. **Limit.** At most `max_ctas` resource CTAs.
4. **Related topic.** Optionally, the first *explicit* MedlinePlus relationship (a curated or summary link) is suggested as the next topic. It links locally when the topic was imported, otherwise to MedlinePlus.
5. **Explanation.** Every item carries a plain-text reason, for example “Your search mentions ‘trials’. MedlinePlus lists 1 ‘Clinical Trials’ resource for Back Pain.” or “Related to Back Pain. Related topic listed by MedlinePlus.”

The PLAN lists five signals. Curated relationships, resource categories and search context drive Next steps. MeSH descriptors and medical groups already drive the labeled inferred related content from Phase 04; they are not repeated as CTAs, so that inferred links are never presented as next actions.

### Presentation and safety

`ActivationBuilder` renders the block:

- **Notice.** A fixed notice reads “Educational resources chosen by fixed rules from MedlinePlus data. They are not medical recommendations.”
- **CTA links.** Each CTA is a link styled as a button.
- **Attribution and reason.** Each CTA shows the resource title, “provided by {organization}, listed by MedlinePlus” and its reason.
- **External links.** The notice is part of the link text (for example “Explore Directory (external site: findadoctor.aahks.net)”, adding “opens in a new tab” when configured), so assistive technology announces it with the link. External links use `rel="noopener noreferrer"`; internal topic links have neither.

No CTA promises outcomes, appointments or clinical benefit, there is no booking, and no provider is invented. Labels describe destinations only.

## Validation summary

- **Contract test.** `scripts/activation/test_activation.php` covers ordering, URL fidelity, attribution, reasons, keyword context, limits, disabled rules, organization requirements, schema validation, rendering and the hook. It runs in CI, and six deliberately broken versions each made it fail.
- **Real-corpus report.** `scripts/activation/report.php`, over 24 topics:
  - 96 CTAs, all URLs found in the topic's imported resources or relationships.
  - 23 destination hosts.
  - 4 CTAs per topic: 3 resource CTAs plus 1 related topic.
  - Synthetic context queries move Directory (“doctor”), Research (“research”) and Patient Resources (“printable”) first.

## Alternatives considered

- **ECA or Rules modules.** These model event, condition and action workflows. Display selection is a pure function of data and configuration, so a config entity with a service is simpler, typed and testable.
- **Views blocks per category.** These cannot rank by search context or explain themselves, and would spread the rules across many displays.
- **AI personalization.** Out of scope; PLAN prefers deterministic rules first.
- **One CTA per resource.** Too many links (up to 13 handouts per topic). The CTA links the first resource and the count points to the full list in *Explore Healthcare Resources*.

## Limitations

- **Unbalanced defaults.** Three common categories (Start Here, Patient Handouts, Find an Expert) usually fill the default three slots. Clinical Trials, Journal Articles and Learn More appear only with matching search wording or when the common ones are missing (3, 4 and 2 topics by default).
- **Context only within a topic.** Keywords reorder a topic's own resources only. “Clinical trials for migraine” matches the *Clinical Trials* topic, which has no “Clinical Trials” resource category, so no trial CTA appears. Nothing is invented to fill the gap.
- **Simple matching.** Keyword matching is plain whole-word matching, without stemming or synonyms.
- **First resource only.** The first resource in source order represents its category; no quality ranking is applied.
