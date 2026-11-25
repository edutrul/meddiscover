# ADR 008: Privacy-preserving metrics and evidence-gated tuning

- Date: 2026-11-24
- Status: Implemented; pending review
- Approved scope: Phase 08, issue #25. Also reports on follow-ups #12 and #13. No third-party analytics, per-user or per-topic history, A/B infrastructure or visual redesign.

## Context

The Optimization layer must measure search and activation without turning health questions into a dataset. PLAN Phase 08 forbids storing raw queries by default and collecting identifiers, topic-level histories or destination URLs. It also requires evidence for any improvement and forbids presenting directory clicks as conversions.

## Decision

### Aggregate-only analytics (`med_analytics`)

- **Storage.** One table, `med_analytics_daily (day, event, dimension, count, total_ms)`, created with `hook_schema()`. There are no identifier columns, so no code path can store one.
- **Contract.** It is documented in [docs/analytics.md](../analytics.md). Every pair is validated against fixed vocabularies: strategies, rank buckets, outcome names, link areas and **existing** activation rule IDs. Free text is rejected, including text posted directly to the endpoint.
- **Decoupled collection:**
  - `med_search` dispatches `SearchCompletedEvent`, which by construction holds no query, topic or URL. `med_analytics` subscribes, and `med_search` does not depend on analytics.
  - An object-oriented `form_alter` hook with `Order::Last` counts CTA and recommendation impressions after `med_activation` has added them.
  - Links declare `data-med-event` and `data-med-dimension`. These attributes are inert unless `med_analytics` attaches its beacon behavior.
- **Clicks.** `navigator.sendBeacon` sends a POST body, so nothing appears in URLs or access logs. The endpoint is POST-only, uses a narrower client allowlist (click events only), returns 204 or 400 and never echoes input.
- **Retention.** The default is 90 days, purged on cron; the setting can be turned off, and schema constraints validate it.
- **Report.** `/admin/reports/med-analytics` shows the zero-result rate, error rate, mean latency per stage, latency buckets, conversational outcomes, CTA click-through, related-topic recommendations and resource engagement. Its wording says clicks are navigation, not appointments or outcomes.

### Performance

- **Live aggregates.** Each search records `latency` sums per strategy and stage and `latency_bucket` counts for end-to-end time. Means and distributions come without storing individual timings.
- **Offline benchmark.** `scripts/optimization/benchmark.php` measures embedding, retrieval, enrichment, activation, generation and end-to-end time, with median, p90 and max, on the real stack.

### Quality with a regression gate

- **Metrics and baselines.** `scripts/optimization/quality.php` computes P@1, P@5, R@5, MRR@5, the positive zero-result rate and the share of unrelated queries returning nothing for any reviewed query set. It saves a JSON baseline (`docs/evaluation/baselines/`).
- **Regression check.** With `baseline=…` it fails when a strategy's MRR@5 or recall drops by more than 0.02, when unrelated queries start returning results, or when any positive query loses rank.
- **Held-out set.** A new held-out set (35 queries) was committed **before** any measurement, and the decision rule was committed next.

### Tuning outcome

Two candidates for #12 failed the decision rule recorded before measuring, so nothing was adopted ([details](../evaluation/optimization.md)):

- **Stopwords only.** This made lexical search worse, because the `und` query fields lack the stopwords.
- **Stopwords plus English-only fields.** This raised lexical MRR@5 from 0.52 to 0.82 and hybrid from 0.88 to 0.98 on held-out questions. It also let one more unrelated question in ten return results.

The configuration was restored and verified with the regression gate. The trade-off is left to the owner, and the next experiment is described.

## Alternatives considered

- **Google Analytics, Matomo or other third-party tags.** These send page URLs, IP-derived data and identifiers to a processor, and need consent management. They were rejected for health searches.
- **Drupal's Statistics module or per-node counters.** These are topic-level histories that PLAN forbids.
- **Logging raw queries for relevance tuning.** Forbidden by default. Synthetic reviewed sets are used instead.
- **A redirect endpoint for click tracking.** The destination would appear in the URL and the logs. Beacons are used instead.
- **Storing individual latencies for exact percentiles.** Sums and buckets give the needed signal without per-request records.

## Limitations

- **Inflatable counts.** The anonymous beacon endpoint can be used to inflate counts; add flood control or rate limiting before production.
- **Approximate percentiles.** Live percentiles come from buckets; exact percentiles come from the offline benchmark.
- **Weakened held-out set.** The held-out set has now been used for two candidates; the next tuning round needs a fresh one.
- **No live traffic.** The report was exercised with synthetic HTTP traffic only.
