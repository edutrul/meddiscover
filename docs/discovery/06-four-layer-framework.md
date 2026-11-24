# Four-layer framework mapping

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

## Experience

**Recommendation:** begin with title/alias/summary lexical discovery, source vocabulary autocomplete, group browsing, resource-category filters, labeled related-topic navigation, and explicit Spanish switching. Use ambiguous terms as opportunities to show clear choices rather than hide alternatives. Preserve canonical source links and distinguish topic-level results from linked resources. Existing summaries and source resource titles support knee, back, diabetes, headache, and allergy exploration without authored medical answers.

## Data

**Verified assets:** 2,033 topic records, 44 group IDs, 52,562 topic-resource assignments, exact aliases/index references, controlled MeSH IDs with limited qualifiers, primary-institute attribution, resource organizations/categories/descriptions, explicit related and language mappings, and embedded summary links. English has 31,393 site assignments and 1,015 MeSH-covered topics. All explicit related/mapped ID references resolve. No structured provider or hospital directory exists. Spanish records have no MeSH assignments.

**Proposed local representation:** source topic ID, title, canonical URL, language, dates, source description and summary, aliases, group/descriptor/qualifier IDs, typed edges, site metadata, and snapshot hash. Store original values plus separate normalized retrieval fields. External document bodies remain external. Keep inherited translation/MeSH metadata and inferred relationships labeled. This is a proposed contract; no content types or database were created.

## Activation

| User action | Source fields | Boundary |
| --- | --- | --- |
| Read trusted medical information | full-summary; @url; primary-institute | Attribution and canonical source; no personalized answer |
| Explore related topics | related-topic; summary href | Label curated versus summary-linked versus inferred |
| Access patient education | site; Patient Handouts / Start Here / Learn More | External document rights and availability are separate |
| Discover clinical trials | site + Clinical Trials | Open existing search links; no eligibility or enrollment claim |
| Access scientific publications | site + Journal Articles | Often PubMed queries, not publication bodies or citations stored as articles |
| Navigate healthcare resources | site + Find an Expert / organization | General organization may not be a directory; no native near-me claim |
| Switch to Spanish | language-mapped-topic; site @language-mapped-url | Topic mapping does not guarantee resource parity |

## Optimization

All measures below are proposed; no analytics collection was installed and no baseline measurements were fabricated. Start with an offline, clinician/content-owner-reviewed set of synthetic queries and expected topic IDs. Separate small-subset POC results from full-corpus claims.

| Measure | Definition / method | Privacy-preserving default |
| --- | --- | --- |
| Search relevance | Offline Recall@5 / graded nDCG@5 or reciprocal rank on reviewed synthetic cases | Retain synthetic query IDs and judgments only; no real user terms |
| Zero-result rate | Zero-result searches / all searches in an aggregate interval | Counts only; no query string, result IDs, or session linkage |
| Search latency | P50/P95 time from submitted search to rendered results | Aggregate durations only; no search URL, request body, or topic labels |
| Result CTR | Search result clicks / result impressions or search submissions; choose one denominator and name it | Broad event counts only; avoid item IDs/rank histories tied to a person |
| External-resource CTR | Outbound clicks / resource-link impressions | Aggregate action counts; do not retain destination URLs or referrers |
| Related-topic engagement | Related-topic clicks / related-topic section impressions | Counts only; topic IDs and graph paths can reveal health interests |
| Spanish switch use | Language-switch clicks / eligible detail views | Aggregate counts; no cross-session language/health profile |
| Query reformulation | Repeated search before a click; definition requires session context | Offline moderated synthetic tasks first; real-session measurement off by default |
| Task completion | Participant reaches the expected education resource / completes task | Consenting task study using synthetic scenarios, not personal health details |

If any analytics is later approved, keep raw health-related queries out of logs, telemetry, error traces, session replay, analytics URLs, browser-history sharing, and third-party requests by default. Query hashing is not sufficient protection against dictionary reversal. Topic IDs, result titles, destination URLs, and persistent user/session identifiers also reveal health interests. Use coarse aggregation, minimal retention, access controls, and small-cell suppression. Aggregate figures alone still require privacy review if groups are small. Avoid query-bearing referrers on outbound links; do not forward query text to external services unless the user explicitly initiates an external search and the disclosure is clear.

## Cross-layer example

For `HbA1c`, Experience resolves the alias to A1C; Data supplies ID 6308, D006442, attribution and Spanish mapping; Activation opens the NLM test resource or Spanish record 6309; Optimization uses a synthetic exact-alias test and broad action counts. For a knee symptom phrase, show educational candidates with provenance and preserve the limit that the data cannot diagnose a person.
