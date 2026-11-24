# Semantic search evaluation

Date: 2026-11-24. Model `nomic-embed-text:v1.5` (digest `0a109f422b47…`), 768 dimensions, topic input `topic-v1` with metadata, threshold 0.62, limit 5.

Queries and expected topics are in [semantic-queries.json](semantic-queries.json). They are synthetic editorial expectations, not clinical relevance judgments. The threshold was chosen from the calibration split only; the holdout and scope probes were run afterwards without changes. Reproduce with `ddev drush php:script scripts/search/evaluate.php`.

## Results

| ID | Split | Query | Top result (similarity) | Expected found in results | Results returned |
|---|---|---|---|---:|---:|
| cal-01 | calibration | My knees hurt when I walk | Knee Injuries and Disorders (0.743) | 2/3 | 3 |
| cal-02 | calibration | What is HbA1c? | A1C (0.682) | 1/1 | 1 |
| cal-03 | calibration | What is backache? | Back Pain (0.711) | 1/1 | 1 |
| cal-04 | calibration | Information about blood sugar | Blood Glucose (0.799) | 2/2 | 5 |
| cal-05 | calibration | Why do my joints hurt? | Arthritis (0.754) | 2/2 | 5 |
| cal-06 | calibration | Information about seasonal allergies | Hay Fever (0.728) | 2/2 | 3 |
| cal-07 | calibration | How do I repair a bicycle chain? | — (best 0.553) | — | 0 |
| cal-08 | calibration | Explain JavaScript closures | — (best 0.478) | — | 0 |
| cal-09 | calibration | What is the capital of France? | — (best 0.452) | — | 0 |
| cal-10 | calibration | How do I bake sourdough bread? | — (best 0.469) | — | 0 |
| hold-01 | holdout | What is knee arthroplasty? | Knee Replacement (0.790) | 1/1 | 2 |
| hold-02 | holdout | A herniated disk causing shooting pain down my leg | Herniated Disk (0.734) | 2/2 | 3 |
| hold-03 | holdout | Can I prevent type 2 diabetes? | How to Prevent Diabetes (0.849) | 2/2 | 5 |
| hold-04 | holdout | A severe allergic reaction called anaphylaxis | Anaphylaxis (0.866) | 1/1 | 4 |
| hold-05 | holdout | How can I configure a Linux firewall? | — (best 0.423) | — | 0 |
| hold-06 | holdout | How do telescopes detect distant galaxies? | — (best 0.502) | — | 0 |
| scope-01 | scope probe | Information about cataract surgery | — (best 0.548) | — | 0 |
| scope-02 | scope probe | Ear infection in a toddler | — (best 0.530) | — | 0 |

## Observations

- Every positive query (6 calibration, 4 holdout) ranked an expected topic first.
- Every out-of-domain and out-of-corpus query returned no results. The highest such similarity was 0.553, compared with 0.682 for the weakest correct top result. The margin is narrow, and the threshold would need recalibration for a larger corpus.
- In cal-01, Osteoarthritis (0.603) fell just below the threshold, so recall is not perfect.
- Broad queries return closely related neighbors above the threshold. For example, “Information about blood sugar” returns Prediabetes, How to Prevent Diabetes and A1C after the expected topics. These are related topics, not errors.
- Latency (local, warm): median 10.4 ms to embed a query and 1.4 ms for exact retrieval over 24 rows. The first, cold query took about 38 ms for each step.
- Adding alias and MeSH metadata to the topic input raised vocabulary-query similarity (HbA1c 0.652 → 0.682) with no measured loss elsewhere.

## Limits

This 18-query set shows that retrieval behaves as intended. It does not measure general search quality, clinical correctness or performance at larger scale. Phase 08 is the place for broader evaluation.
