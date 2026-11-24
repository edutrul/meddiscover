# Lexical, semantic and hybrid retrieval: comparative evaluation

Date: 2026-11-24. Design: [ADR 005](../architecture/005-lexical-hybrid-search.md). Query set: [`phase-05-queries.json`](phase-05-queries.json). Script: `ddev drush php:script scripts/search/compare.php`.

## Method

- **Corpus.** All strategies search the same 24 published English Health Topics. Each result is identified by its canonical MedlinePlus topic ID.
- **Queries.** There are 48 synthetic queries in five categories:

  | Category | Queries | What it tests |
  |---|---|---|
  | Exact terminology | 8 | Topic titles |
  | Synonyms | 14 | Alternative terms, see references and lay wording |
  | Ambiguous | 5 | Terms that name several topics |
  | Paraphrases | 11 | Natural-language questions |
  | Unrelated | 10 | Off-topic questions plus two out-of-corpus health topics; the correct answer is no result |

  The 18 Phase 03 queries keep their IDs, and the rest are new. The relevance labels are editorial and reviewed against titles, alternative terms, see references, MeSH labels and descriptions. They are not clinical judgments, and they are not exhaustive: unlabeled topics count as non-relevant.
- **Metrics.** The cut-off is K = 5, the number of results the interface shows. P@5 is relevant results in the top five divided by five, so a query with one relevant topic scores at most 0.20. R@5 and MRR are computed over the same top five. The zero-result rate is a failure for queries with relevant topics and a success for unrelated ones.
- **Latency.** Each query runs three times and the median is kept, after one warm-up query per strategy. Timings are wall-clock inside PHP on the local DDEV stack; semantic includes the query embedding by local Ollama.
- **Settings.** The configured defaults were used throughout: cosine threshold 0.62, `mm=2<75%`, RRF k = 60, and a depth of 20 accepted results per strategy before fusion. The script checks that fusing the depth-20 lexical and semantic lists offline reproduces the hybrid service's ranking for every query.

## Results

| Strategy | Category | Queries | P@1 | P@5 | R@5 | MRR | Zero-result rate |
|---|---|---|---|---|---|---|---|
| lexical | exact | 8 | 1.00 | 0.20 | 1.00 | 1.00 | 0.00 |
| lexical | synonym | 14 | 0.93 | 0.20 | 0.93 | 0.93 | 0.07 |
| lexical | ambiguous | 5 | 1.00 | 0.48 | 1.00 | 1.00 | 0.00 |
| lexical | paraphrase | 11 | 0.45 | 0.13 | 0.38 | 0.45 | 0.45 |
| lexical | unrelated | 10 | – | – | – | – | 0.90 (mean 0.5 results) |
| lexical | all relevant | 38 | 0.82 | 0.22 | 0.79 | 0.82 | 0.16 |
| semantic | exact | 8 | 1.00 | 0.20 | 1.00 | 1.00 | 0.00 |
| semantic | synonym | 14 | 0.93 | 0.21 | 0.93 | 0.93 | 0.00 |
| semantic | ambiguous | 5 | 1.00 | 0.44 | 0.90 | 1.00 | 0.00 |
| semantic | paraphrase | 11 | 0.91 | 0.29 | 0.80 | 0.91 | 0.09 |
| semantic | unrelated | 10 | – | – | – | – | 1.00 (mean 0.0 results) |
| semantic | all relevant | 38 | 0.95 | 0.26 | 0.90 | 0.95 | 0.03 |
| hybrid | exact | 8 | 1.00 | 0.20 | 1.00 | 1.00 | 0.00 |
| hybrid | synonym | 14 | 0.93 | 0.23 | 1.00 | 0.96 | 0.00 |
| hybrid | ambiguous | 5 | 1.00 | 0.48 | 1.00 | 1.00 | 0.00 |
| hybrid | paraphrase | 11 | 0.91 | 0.31 | 0.85 | 0.95 | 0.00 |
| hybrid | unrelated | 10 | – | – | – | – | 0.90 (mean 0.5 results) |
| hybrid | all relevant | 38 | 0.95 | 0.28 | 0.96 | 0.97 | 0.00 |
| hybrid k=1 | all relevant | 38 | 0.95 | 0.28 | 0.96 | 0.97 | 0.00 |
| hybrid k=10 | all relevant | 38 | 0.95 | 0.28 | 0.96 | 0.97 | 0.00 |
| hybrid k=100 | all relevant | 38 | 0.95 | 0.28 | 0.96 | 0.97 | 0.00 |

### Latency

| Strategy | Median ms | p90 ms | Max ms |
|---|---|---|---|
| lexical | 2.3 | 3.0 | 4.9 |
| semantic | 13.4 | 19.9 | 22.1 |
| hybrid | 16.5 | 23.6 | 28.1 |

### k sensitivity

| RRF k | Queries whose top 5 differs from k = 60 | Queries whose first result differs |
|---|---|---|
| 0 | 0 of 48 | 0 |
| 1 | 0 of 48 | 0 |
| 10 | 0 of 48 | 0 |
| 30 | 0 of 48 | 0 |
| 100 | 0 of 48 | 0 |
| 1000 | 0 of 48 | 0 |

No k between 0 and 1000 changed any top-5 ranking. Each list holds only that strategy's accepted results (usually 1–5 topics), and the two lists overlap heavily. In this setting, k changes the order only when a topic ranked high in one list competes with a topic ranked lower in both, and no evaluated query produced that case. k = 60 is kept as the literature default rather than as a tuned value. The evaluation gives no evidence for a different k on this corpus.

## Observed trade-offs

- **Exact names, alternative terms and ambiguous terms.** All three strategies reach P@1 = 1.00 on exact titles. Lexical is as good as semantic on alternative terms. On ambiguous terms it finds every labeled topic (R@5 1.00), against 0.90 for semantic, which missed Migraine for “Vascular headache”. It also names the ambiguity explicitly. Semantic missed “Insulin resistance” (a Prediabetes see reference), and lexical found it.
- **Paraphrases.** Lexical fails on 5 of 11 questions in everyday words (“Why do my joints hurt?”, “Itchy eyes and sneezing every spring”). Semantic handles them (P@1 0.91), but missed “My throat swells after eating peanuts”, which lexical found through “peanut”.
- **Hybrid.** Fusion combines these strengths. It has the best R@5 (0.96) and MRR (0.97) over the 38 relevant queries and is never empty when relevant topics exist. P@1 equals semantic (0.95). Fusion fixed one paraphrase (peanuts) and lost another first place: “How do I pick a good doctor?” ranks How to Prevent Diabetes first, a lexical false match on “how do I”, tied by RRF with the semantic first place and placed ahead by the lexical-first tie-break.
- **Unrelated queries.** Semantic rejected all 10. Lexical and hybrid returned five diabetes topics for “How do I change a car tire?”. The question words “how”, “do” and “I” are not English stopwords in the Solr configuration, so they satisfy minimum-should-match against summaries such as “How to Prevent Diabetes”. Hybrid inherits any lexical false positive, because fusion cannot remove a result that one strategy accepted.
- **Latency.** Lexical takes about 2 ms; semantic about 13 ms, mostly the query embedding; hybrid about 17 ms, running the two sequentially plus under 0.1 ms of fusion (0.016 ms at most). All are well below the cost of rendering a page.

Hybrid is therefore the best default for recall on this corpus, but it is not better everywhere. It is weaker than semantic at rejecting unrelated queries, and its first result can be a lexical false match when the strategies disagree. These results come from a 24-topic corpus and a small labeled set, so differences of one query move a category's rate by 7–20 points.

## Per-query rankings

Top five canonical IDs; relevant IDs in bold; ∅ means no result.

| Query ID | Category | Relevant | Lexical | Semantic | Hybrid |
|---|---|---|---|---|---|
| exact-01 | exact | 1532 | **1532**, 284 | **1532**, 284 | **1532**, 284 |
| exact-02 | exact | 3157 | **3157**, 273 | **3157**, 273 | **3157**, 273 |
| exact-03 | exact | 5784 | **5784**, 6308, 6661, 5930, 4 | **5784**, 6308, 4, 6661, 5930 | **5784**, 6308, 6661, 4, 5930 |
| exact-04 | exact | 192 | **192** | **192** | **192** |
| exact-05 | exact | 417 | **417** | **417**, 284, 304 | **417**, 284, 304 |
| exact-06 | exact | 6308 | **6308**, 5784, 6121, 5930, 4 | **6308** | **6308**, 5784, 6121, 5930, 4 |
| exact-07 | exact | 486 | **486** | **486** | **486** |
| exact-08 | exact | 3924 | **3924**, 304 | **3924**, 304 | **3924**, 304 |
| synonym-01 | synonym | 157 | **157** | **157** | **157** |
| synonym-02 | synonym | 284 | **284** | **284** | **284** |
| synonym-03 | synonym | 4746 | **4746**, 24 | **4746**, 24, 1256, 6257 | **4746**, 24, 1256, 6257 |
| synonym-04 | synonym | 1250 | **1250**, 23, 304, 5673, 417 | **1250**, 23, 304 | **1250**, 23, 304, 5673, 417 |
| synonym-05 | synonym | 6257 | **6257**, 1256 | **6257**, 24, 1256 | **6257**, 1256, 24 |
| synonym-06 | synonym | 6308 | **6308** | **6308**, 6121 | **6308**, 6121 |
| synonym-07 | synonym | 5673 | **5673** | **5673** | **5673** |
| synonym-08 | synonym | 486 | **486**, 3924 | **486** | **486**, 3924 |
| synonym-09 | synonym | 5784 | **5784**, 5930, 6661 | 6661, 4 | 6661, **5784**, 5930, 4 |
| cal-02 | synonym | 6308 | **6308** | **6308** | **6308** |
| cal-03 | synonym | 157 | **157** | **157** | **157** |
| cal-04 | synonym | 6121, 4 | **6121**, 5784, **4** | **6121**, **4**, 5784, 6661, 6308 | **6121**, 5784, **4**, 6661, 6308 |
| cal-06 | synonym | 4746, 24 | ∅ | **4746**, **24**, 1256 | **4746**, **24**, 1256 |
| hold-01 | synonym | 3924 | **3924** | **3924**, 304 | **3924**, 304 |
| ambiguous-01 | ambiguous | 4, 5930 | **4**, **5930**, 6121, 6661, 5784 | **4**, 6121, 5784, 6661, **5930** | **4**, 6121, **5930**, 5784, 6661 |
| ambiguous-02 | ambiguous | 273, 3157 | **273**, **3157** | **273** | **273**, **3157** |
| ambiguous-03 | ambiguous | 24, 4746 | **4746**, **24** | **4746**, **24**, 6257 | **4746**, **24**, 6257 |
| ambiguous-04 | ambiguous | 24, 1256 | **24**, **1256** | **24**, 4746, 6257, **1256** | **24**, **1256**, 4746, 6257 |
| ambiguous-05 | ambiguous | 4, 5930, 6661, 5784 | **4**, **5930**, **6661**, **5784**, 6308 | **4**, **6661**, **5784**, **5930**, 6121 | **4**, **6661**, **5930**, **5784**, 6308 |
| cal-01 | paraphrase | 304, 1250, 5673 | **5673**, 3924, **304** | **304**, **5673**, 3924 | **5673**, **304**, 3924 |
| cal-05 | paraphrase | 23, 1250 | ∅ | **23**, 304, **1250**, 417, 1532 | **23**, 304, **1250**, 417, 1532 |
| hold-02 | paraphrase | 284, 1532 | ∅ | **284**, **1532**, 304 | **284**, **1532**, 304 |
| hold-03 | paraphrase | 6661, 5930 | **5930**, **6661**, 4, 5784, 6121 | **6661**, **5930**, 4, 5784, 6308 | **5930**, **6661**, 4, 5784, 6121 |
| hold-04 | paraphrase | 6257 | **6257**, 1256, 24 | **6257**, 24, 4746, 1256 | **6257**, 24, 1256, 4746 |
| paraphrase-01 | paraphrase | 1532, 284, 157 | ∅ | **157**, **1532** | **157**, **1532** |
| paraphrase-02 | paraphrase | 4746, 24 | ∅ | **4746** | **4746** |
| paraphrase-03 | paraphrase | 1256, 6257 | **1256** | ∅ | **1256** |
| paraphrase-04 | paraphrase | 1433 | 6661, 3157, 23, 3924, 6308 | **1433** | 6661, **1433**, 3157, 23, 3924 |
| paraphrase-05 | paraphrase | 3157, 273 | ∅ | **273**, **3157** | **273**, **3157** |
| paraphrase-06 | paraphrase | 192 | **192** | **192** | **192** |
| cal-07 | unrelated | ∅ | ∅ | ∅ | ∅ |
| cal-08 | unrelated | ∅ | ∅ | ∅ | ∅ |
| cal-09 | unrelated | ∅ | ∅ | ∅ | ∅ |
| cal-10 | unrelated | ∅ | ∅ | ∅ | ∅ |
| hold-05 | unrelated | ∅ | ∅ | ∅ | ∅ |
| hold-06 | unrelated | ∅ | ∅ | ∅ | ∅ |
| scope-01 | unrelated | ∅ | ∅ | ∅ | ∅ |
| scope-02 | unrelated | ∅ | ∅ | ∅ | ∅ |
| unrelated-01 | unrelated | ∅ | ∅ | ∅ | ∅ |
| unrelated-02 | unrelated | ∅ | 6661, 3157, 5930, 6121, 6308 | ∅ | 6661, 3157, 5930, 6121, 6308 |

## Reproduce

```bash
ollama list                                   # nomic-embed-text:v1.5, digest 0a109f422b47…
ddev drush search-api:index med_topics        # 24 items
ddev drush php:script scripts/search/compare.php
```

Latency varies by machine. Rankings are deterministic for a given index, model digest and configuration.
