# Analytics event contract

`med_analytics` records **daily aggregate counters only**. Every stored row is `(UTC day, event, dimension) → count, total_ms`. There is no timestamp more precise than the day, and no user, session, IP address, cookie, query text, topic, node ID or destination URL. Dimensions come from fixed vocabularies validated by `AnalyticsRecorder::isAllowed()`, so free text cannot be stored even when someone posts it on purpose.

| Event | Dimension values | Source | Meaning |
|---|---|---|---|
| `search_performed` | `semantic`, `lexical`, `hybrid`, `conversational` | server | One search was run |
| `search_no_results` | strategy | server | No topic was shown. Urgent safety responses are excluded. |
| `search_error` | strategy | server | The search backend failed |
| `answer_outcome` | `answered`, `abstained`, `withheld`, `unavailable`, `urgent` | server | Conversational result status |
| `latency` | `{strategy}:{total, embedding, retrieval, enrichment, generation}` | server | Sample count plus a millisecond sum; mean = sum / count |
| `latency_bucket` | `{strategy}:{lt100ms, lt500ms, lt1s, lt5s, lt15s, ge15s}` | server | End-to-end distribution |
| `search_result_clicked` | `{strategy}:{rank1, rank2-3, rank4plus}` | browser | A result title was clicked; rank is bucketed |
| `related_content_clicked` | `explicit`, `inferred` | browser | A related-content link was clicked |
| `external_resource_clicked` | `source`, `citation`, `resources`, `further_reading` | browser | Where an off-site link was clicked, never which one |
| `recommendation_shown` / `recommendation_clicked` | `related_topic` | server / browser | Next-steps related-topic suggestion |
| `cta_shown` / `cta_clicked` | an existing activation rule ID, for example `clinical_trials` | server / browser | Calls to action rendered and followed |

## How it is collected

- **Server events.** `med_search` dispatches `SearchCompletedEvent` once per search request, carrying only the strategy, result count, failure flag, answer status and stage timings. `SearchMetricsSubscriber` turns it into counters.
- **Impressions.** An object-oriented `form_alter` hook runs after the other modules and counts each call to action actually rendered.
- **Clicks.** Tracked links carry `data-med-event` and `data-med-dimension`. A small behavior sends them with `navigator.sendBeacon` as a POST body to `/med-analytics/event`, so neither URLs nor parameters appear in web server access logs.
  - The endpoint accepts only the five click events and returns 204, or 400 for anything else, and never echoes input.
  - Without JavaScript nothing is sent, and links work normally.

## Retention and control

- **Retention.** `med_analytics.settings`: `enabled` (default on), `retention_days` (default 90, range 1–365) and `report_days` (30). Cron deletes rows older than the retention period.
- **Report and permissions.** The report is at `/admin/reports/med-analytics` with the *View medDiscover aggregate metrics* permission. Settings are at `/admin/config/search/med-analytics` (restricted).

## Reading the numbers

- **Clicks are navigation only.** A CTA or directory click is navigation to an educational resource. It is **not** an appointment, a referral, a treatment or a healthcare conversion, and the report says so.
- **Zero-result rate.** It is `search_no_results / search_performed` per strategy. For conversational search, abstentions are a safety feature, not a failure.
- **Small counts.** On a low-traffic site, small counts are noisy and the report shows raw numbers. Nothing per visitor or per topic is retained, so a small count cannot be traced back to a person.

## Known limits

- **The beacon endpoint is open.** It is anonymous by design, so counts can be inflated by anyone posting allowlisted pairs. That is acceptable for aggregate POC metrics; add flood control or a reverse-proxy rate limit before production.
- **Web server access logs.** The DDEV web server's access log records client IP addresses and request paths for every request, which is standard infrastructure logging outside Drupal. Search text is POSTed, so it never appears there; this was checked.
