# Phase 06 implementation report

Date: 2026-11-24. Approved scope: Conversational AI (MVP 4). The dependency-injection follow-up #14 was requested by the owner during this phase and is included. Merge was pre-authorized by the owner once checks pass.

## Issue and review status

- Issue #21: https://github.com/edutrul/meddiscover/issues/21 — grounded conversational search.
- Issue #14: https://github.com/edutrul/meddiscover/issues/14 — inject search dependencies.
- The PR link and CI status are recorded in the PR.
- Prerequisite verified: Phase 05 PR #11 merged; issue #10 closed.
- Follow-ups recorded at the owner's request: #12–#20.

## Implementation

Design: [ADR 006](../architecture/006-conversational-search.md). Setup and use: [docs/search.md](../search.md).

- **Provider evaluation.** Local Ollama was chosen (no cost, no credentials, and the question never leaves the machine). `drupal/ai` was reviewed: its core is stable, but its Ollama provider is only 1.2.0-rc3, so it was deferred to #17 behind `GeneratorInterface`. Four local models were compared, and **gemma3:12b** (pinned digest) is the default; qwen3:8b (Apache-2.0) is the faster alternative.
- **`ConversationalSearch` at `/search/conversational`:**
  1. Deterministic urgency check first.
  2. Retrieval (semantic, chosen by evaluation).
  3. Abstention without evidence.
  4. Escaped source blocks treated as data.
  5. JSON-schema output whose citations are limited to the provided labels.
  6. Verification of structure and citations, filtering of diagnosis, dose, contact and link text, and removal of unsupported statements.
  7. Numbered citations with MedlinePlus links, a limitations notice, and supporting topics with enrichment.
- **Dependency injection (#14):**
  - A `med_search.strategies` registry is filled by a `service_collector` from strategies tagged `med_search.strategy`.
  - Composed strategies receive their components as service arguments; no service receives the container.
  - `current_user` is injected for access checks.
  - `Corpus` stays a static pure utility; the reason is documented.
- **Tests and evaluation.**
  - `scripts/search/test_conversation.php`: synthetic contract test with stub retrieval and model, added to CI.
  - `scripts/search/evaluate_conversation.php` and `scripts/search/llm_bakeoff.php`: real-model evaluation.

## Acceptance criteria

| Criterion | Evidence |
|---|---|
| Grounded conversational search works | Answerable questions: 9/10 in the first run (one answer wrongly withheld by the diagnosis filter) and 10/10 in the final run. Hand review and fact spot-checks found no content outside the sources. |
| Responses include source references | Every statement carries a numbered citation; the Sources list links the topic pages and MedlinePlus originals. |
| Unsupported queries trigger abstention | 11/11 unsupported and out-of-corpus queries abstained in every configuration: eight before generation, three declined by the model. |
| No fabricated citations | The schema enum makes unknown labels impossible, and verification rejects them anyway (tested). No configuration cited the injected hostile source. |
| No unsupported medical diagnosis | The diagnosis filter is tested. Diagnosis-seeking questions get general information plus the limitations notice; no shown answer tells the user what they have. |
| Provider failures are handled | Model missing, digest changed, timeout, truncated output or invalid JSON give an “answer could not be generated” notice with topics. A retrieval failure gives an unavailable message, except for urgent queries, which still get the safety message (tested). |
| Safety and grounding behavior are evaluated | [docs/evaluation/conversational-search.md](../evaluation/conversational-search.md): 34 queries, three configurations, a post-fix rerun, injection tests and all answers listed. |

## Validation

- **Clean install from exported configuration, in CI order.** `config:status` shows no differences. The fixture migration, semantic, enrichment, hybrid and conversation contract tests all pass.
- **Mutation checks.** The conversation test fails when any of these is removed: urgency-first handling, abstention without evidence, label checks, unsafe-text filters, source escaping or the support check.
- **HTTP form submissions on `/search/conversational`.**
  - An answer with citations returns HTTP 200 in about 11 s.
  - An abstention (pregnancy question) shows the supporting topics.
  - The urgent message appears in 0.07 s without the model.
  - The out-of-corpus abstention also works.
- **Privacy.** `check_privacy.php` passes. The test questions are absent from Drupal's watchdog and from Ollama's server log, which records request lines only.
- **Code quality.** PHPCS passes.

## Limitations

- **Latency.** About 12 s median per answered question on an M4 Pro (Phase 08 candidate).
- **Post-evaluation fixes.** Two safety-rule gaps were fixed after the first run (conditional wording, unresponsiveness and poisoning phrasing). The 34/34 rerun is therefore not independent; held-out safety queries are tracked in #13.
- **Pattern-based checks.** The safety and support checks are pattern- and overlap-based, and can miss rephrased claims.
- **Scope of the experience.** Single exchange, US emergency numbers with a local-number fallback, and the Gemma license terms apply to the default model.
- **Interaction design.** The full-page POST form gives no loading feedback. HTMX (in Drupal core) and a Starterkit theme with components are planned for Phase 09.

## Next step

Merge after checks pass, confirm that issues #21 and #14 are closed, then Phase 07 — Activation.
