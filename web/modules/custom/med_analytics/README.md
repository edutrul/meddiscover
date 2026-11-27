# medDiscover Analytics

Privacy-preserving, **aggregate-only** metrics for search and Next steps: search volume, searches with no results, errors, answer outcomes, latency per pipeline stage, and engagement with result links and calls to action.

It never stores query text, topics, node IDs, URLs, IP addresses, cookies, sessions or user IDs. Every stored row is `(UTC day, event, dimension) → count, total_ms`.

For the full event list, see the [analytics contract](../../../../docs/analytics.md).

## Table of contents

- How it works
- Requirements
- Installation
- Configuration
- Maintainers

## How it works

- **Server events:** `med_search` dispatches `SearchCompletedEvent` once per search. It carries only the strategy, the result count, a failure flag, the answer status and the stage timings. `EventSubscriber\SearchMetricsSubscriber` turns it into counters.
- **Impressions:** `Hook\AnalyticsHooks` runs as the last `form_alter` on the search and Compare forms and counts each call to action actually rendered.
- **Clicks:** tracked links carry `data-med-event` and `data-med-dimension`. The `beacon` library sends them with `navigator.sendBeacon` as a POST body to `/med-analytics/event`, so nothing appears in access-log URLs. The endpoint accepts only known click events, returns 204 (400 otherwise) and never echoes input.
- **Fixed vocabularies:** `AnalyticsRecorder::isAllowed()` validates every dimension, so free text can never be stored, even when someone posts it on purpose.

Without JavaScript nothing is sent, and links work normally.

## Requirements

- `med_search`.

## Installation

The module is enabled by the exported configuration; see the repository README.

## Configuration

| Path | Purpose | Permission |
|---|---|---|
| `/admin/reports/med-analytics` | Report: daily totals, zero-result rate, latency, engagement | View medDiscover aggregate metrics |
| `/admin/config/search/med-analytics` | `enabled` (default on), `retention_days` (default 90, range 1–365), `report_days` (30) | Administer medDiscover analytics |

Cron deletes rows older than the retention period.

## Maintainers

- Eduardo Telaya — https://github.com/edutrul
