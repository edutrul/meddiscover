# Phase 08 optimization evaluation

## Decision rule (written and committed before measuring)

A candidate search change is **adopted** only if, on the held-out set [`phase-08-holdout-queries.json`](phase-08-holdout-queries.json) and for every strategy it affects:

1. Mean reciprocal rank (MRR@5) over positive queries does not drop by more than 0.02.
2. The share of unrelated queries that correctly return nothing does not decrease.
3. At least one improves: that share of unrelated queries returning nothing, the positive-query zero-result rate, or MRR@5.

The Phase 05 development set (`phase-05-queries.json`) is reported too, but it does not decide: settings were originally chosen on queries that overlap it.

Candidate 1 (#12): add English question and function words to the Solr English stopword list (`search_api_solr.solr_field_type.text_en_7_0_0`, `text_files.stopwords`), so that they no longer count toward minimum-should-match. The words: how, what, why, when, where, which, who, whom, whose, do, does, did, i, me, my, mine, we, us, our, you, your, can, could, should, would, may, might, must, am, has, have, had, been, being, about, tell, please.
