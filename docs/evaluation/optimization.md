# Phase 08 optimization evaluation

## Decision rule (written and committed before measuring)

A candidate search change is **adopted** only if, on the held-out set [`phase-08-holdout-queries.json`](phase-08-holdout-queries.json) and for every strategy it affects:

1. Mean reciprocal rank (MRR@5) over positive queries does not drop by more than 0.02.
2. The share of unrelated queries that correctly return nothing does not decrease.
3. At least one improves: that share of unrelated queries returning nothing, the positive-query zero-result rate, or MRR@5.

The Phase 05 development set (`phase-05-queries.json`) is reported too, but it does not decide: settings were originally chosen on queries that overlap it.

Candidate 1 (#12): add English question and function words to the Solr English stopword list (`search_api_solr.solr_field_type.text_en_7_0_0`, `text_files.stopwords`), so that they no longer count toward minimum-should-match. The words: how, what, why, when, where, which, who, whom, whose, do, does, did, i, me, my, mine, we, us, our, you, your, can, could, should, would, may, might, must, am, has, have, had, been, being, about, tell, please.

## Results

### Baseline and candidate on the held-out set (35 queries, decides)

| Strategy | Configuration | P@1 | R@5 | MRR@5 | Zero-result (positive) | Unrelated → nothing |
|---|---|---|---|---|---|---|
| lexical | before | 0.520 | 0.500 | 0.520 | 0.400 | 9/10 |
| lexical | candidate 1b | 0.800 | 0.800 | 0.820 | 0.160 | 8/10 |
| semantic | before | 0.880 | 0.900 | 0.900 | 0.040 | 10/10 |
| semantic | candidate 1b | 0.880 | 0.900 | 0.900 | 0.040 | 10/10 |
| hybrid | before | 0.840 | 0.900 | 0.880 | 0.040 | 9/10 |
| hybrid | candidate 1b | 0.960 | 0.980 | 0.980 | 0.000 | 8/10 |

### Development set (Phase 05, 48 queries, reported only)

| Strategy | Configuration | P@1 | R@5 | MRR@5 | Zero-result (positive) | Unrelated → nothing |
|---|---|---|---|---|---|---|
| lexical | before | 0.816 | 0.794 | 0.816 | 0.158 | 9/10 |
| lexical | candidate 1b | 0.947 | 0.882 | 0.947 | 0.000 | 9/10 |
| semantic | before | 0.947 | 0.904 | 0.947 | 0.026 | 10/10 |
| semantic | candidate 1b | 0.947 | 0.904 | 0.947 | 0.026 | 10/10 |
| hybrid | before | 0.947 | 0.956 | 0.974 | 0.000 | 9/10 |
| hybrid | candidate 1b | 0.921 | 0.969 | 0.961 | 0.000 | 9/10 |

### What happened

1. **Candidate 1, as recorded: stopwords only.** Lexical search got much worse on the held-out set (MRR@5 0.52 → 0.24, positive zero-result rate 0.40 → 0.76). Debugging showed the cause: Search API Solr also searches the language-independent (`und`) fields, which do not share the English stopword list. Words such as “what” stayed as required EDisMax clauses and could only match empty fields. **Rejected.**
2. **Candidate 1b: stopwords plus English-only query fields** (`third_party_settings.search_api_solr.multilingual.include_language_independent: false` on the English-only index). This revision was made after seeing the held-out results, so the held-out set was used twice for this candidate.
   - **Relevant questions improved a lot.** Lexical MRR@5 rose from 0.52 to 0.82 and its positive zero-result rate fell from 0.40 to 0.16. Hybrid MRR@5 rose from 0.88 to 0.98, and hybrid then beat semantic (0.90) on the held-out set.
   - **Rule 2 failed for lexical and hybrid.** Unrelated questions returning nothing went from 9/10 to 8/10. “What is the best way to learn guitar?” now matches two topics on “best”, “way” and “learn”. “How do I change a car tire?” dropped from five false results to one (“change” plus “tired”), but was still not empty.
3. **Decision: not adopted.** Under the rule recorded before measuring, candidate 1b is **not adopted**. The configuration was restored, and `quality.php` confirms no regressions against the baselines. The trade-off is presented to the owner: much better recall for questions against one more unrelated false positive in ten.
4. **Recommended next step.** Tune the minimum-should-match setting for the shorter post-stopword queries on the development set only (for example, requiring all content words up to three). Then check it once on a **new** held-out set with more unrelated questions, because this one has now been seen. Issue #12 stays open with these findings.

## Stage latency (real stack)

Real corpus (24 topics), local DDEV on the host machine, 48 retrieval queries (Phase 05 set) and 10 conversational queries (Phase 06 answerable set); one warm-up per strategy. Milliseconds.

| Strategy | Stage | Samples | Median | p90 | Max |
|---|---|---|---|---|---|
| lexical | retrieval | 48 | 2.5 | 5.0 | 14.2 |
| lexical | enrichment | 33 | 13.0 | 36.4 | 63.8 |
| lexical | activation | 33 | 0.1 | 0.4 | 1.6 |
| lexical | end-to-end | 48 | 11.6 | 39.2 | 67.7 |
| lexical | errors | 0 | 0 of 48 queries | | |
| semantic | embedding | 48 | 14.6 | 21.9 | 28.6 |
| semantic | retrieval | 48 | 1.1 | 2.3 | 3.0 |
| semantic | enrichment | 37 | 9.7 | 30.5 | 41.4 |
| semantic | activation | 37 | 0.2 | 0.3 | 0.4 |
| semantic | end-to-end | 48 | 27.5 | 48.4 | 57.7 |
| semantic | errors | 0 | 0 of 48 queries | | |
| hybrid | embedding | 48 | 16.7 | 24.3 | 30.2 |
| hybrid | retrieval | 48 | 3.7 | 6.0 | 15.6 |
| hybrid | enrichment | 39 | 13.5 | 37.4 | 45.4 |
| hybrid | activation | 39 | 0.2 | 0.4 | 0.6 |
| hybrid | end-to-end | 48 | 33.7 | 64.5 | 70.5 |
| hybrid | errors | 0 | 0 of 48 queries | | |
| conversational | embedding | 10 | 13.5 | 22.4 | 24.5 |
| conversational | retrieval | 10 | 2.2 | 3.4 | 4.3 |
| conversational | generation | 10 | 11667.5 | 15030.6 | 16676.8 |
| conversational | enrichment | 10 | 30.5 | 45.5 | 52.1 |
| conversational | activation | 10 | 0.4 | 0.5 | 0.5 |
| conversational | end-to-end | 10 | 11712.2 | 15097.7 | 16739.8 |
| conversational | errors | 0 | 0 of 10 queries | | |

**Findings.**
- **The LLM dominates.** Generation is 99.6 % of conversational time (median 11.7 s). The non-generative modes finish within 70 ms end-to-end at p90, and their enrichment (10–30 ms) costs more than retrieval (1–4 ms).
- **Recommendations, in order of evidence:**
  1. Show progress while the answer is generated (HTMX loading state, Phase 09).
  2. Offer the faster model (qwen3:8b, about 40 % less generation time but measured lower grounding; see the Phase 06 evaluation) only with owner sign-off.
  3. Stream the answer.
  4. Cache enrichment per topic only if the corpus grows; it is not worth it at 24 topics.
- **Errors.** None in 154 benchmark searches.
