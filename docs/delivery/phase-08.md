# Phase 08 implementation report

Date: 2026-11-24. Approved scope: Optimization. The owner authorized this phase and pre-authorized merge once checks pass.

## Issue and review status

- Issue #25: https://github.com/edutrul/meddiscover/issues/25
- Findings were added to the follow-ups #12 (question words) and #13 (held-out validation), which stay open.
- The PR link and CI status are recorded in the PR.
- Prerequisite verified: Phase 07 PR #24 merged; issue #23 closed.

## Implementation

Design: [ADR 008](../architecture/008-optimization.md). Contract: [docs/analytics.md](../analytics.md). Evidence: [docs/evaluation/optimization.md](../evaluation/optimization.md).

- **`med_analytics` module:**
  - Daily aggregate table and allowlisted recorder.
  - `SearchCompletedEvent` subscriber.
  - `Order::Last` impression hook and cron retention.
  - POST-only beacon endpoint with a progressive-enhancement script.
  - Admin report and `#config_target` settings with schema constraints.
- **`med_search`:** dispatches `SearchCompletedEvent` with stage timings and marks links with inert `data-med-*` attributes. **`med_activation`** marks CTA links the same way.
- **Scripts:**
  - `scripts/optimization/quality.php`: metrics, baselines and the regression gate.
  - `scripts/optimization/benchmark.php`: stage latency.
  - `scripts/analytics/test_analytics.php`: CI contract test.
- **Evaluation data:** the held-out set `docs/evaluation/phase-08-holdout-queries.json`, committed before measuring, plus the baselines in `docs/evaluation/baselines/`.

## Acceptance criteria

| Criterion | Evidence |
|---|---|
| Analytics event contract documented | [docs/analytics.md](../analytics.md): events, dimension vocabularies, sources, retention and interpretation. |
| Privacy-preserving metrics implemented | Daily counters with no identifier, query, topic or URL columns; an allowlist rejects free text, URLs, topic IDs and unknown rules (tested). Beacons are POST bodies, and the access log shows only the endpoint path. |
| Search performance measured | Live latency sums and buckets per strategy and stage, plus the real-stack benchmark: non-generative modes under 70 ms at p90; conversational is 99.6 % generation (median 11.7 s); 0 errors in 154 searches. |
| Search quality evaluated | `quality.php` on the development set (48) and the new held-out set (35): P@1, P@5, R@5, MRR@5, zero-result rate and per-query rank regressions against stored baselines. |
| Activation engagement measured | CTA and related-topic impressions and clicks per rule, with click-through rate in the report. The wording says clicks are not appointments or conversions. |
| No sensitive query logging by default | No query text in the analytics table, watchdog or web server access log after real form submissions; search text is POSTed. Verified with the privacy check and SQL scans. |
| Optimization recommendations supported by evidence | Two #12 candidates were measured against the pre-recorded rule and not adopted, with the trade-off quantified. Latency recommendations follow from the stage benchmark. |

## Validation

- **Clean install from exported configuration, in CI order.** `config:status` shows no differences, and all seven contract tests pass: migration fixture, semantic, enrichment, hybrid, conversation, activation and analytics.
- **Mutation checks.** The analytics test fails for an allowlist bypass, a disabled purge, urgent responses counted as zero results, missing impressions, and server-only events accepted from browsers.
- **HTTP on all four routes.** Pages return 200 with tracked links, and the beacon settings and behavior are delivered in the aggregated JS. The endpoint returns 204 for allowed clicks and 400 for free text, server-only events and unknown rules; GET returns 405. The report and settings return 200 for an administrator and 403 for anonymous users.
- **Code quality.** PHPCS (now covering `med_analytics`) and `check_publication.py` pass.

## Limitations

- **Inflatable counts.** The open beacon endpoint can be used to inflate counts; add rate limiting before production.
- **Weakened held-out set.** It has been used twice; future tuning needs a fresh set.
- **Lexical question handling.** It remains at the Phase 05 level until the owner chooses between recall and one more unrelated false positive (#12).
- **Conversational latency.** About 12 s, addressed by UI progress (Phase 09) or a model change (owner decision).

## Next step

Merge after checks pass and confirm that #25 is closed. Recommended next phase: Phase 09 — UI/UX Enhancement (HTMX in core, a Starterkit theme with components, loading states), only after explicit authorization.
