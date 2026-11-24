# Search opportunity analysis

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

## Recommended search foundation

The existing structure supports a useful lexical experience before AI or new infrastructure. This is a design assessment, not a measured retrieval benchmark: no search engine was implemented. Effort refers to a bounded file-backed POC with a small UI, not production integration, clinical approval, accessibility certification, or ongoing operations. Low means a few bounded parsing/UI rules; Medium adds significant language or interaction evaluation; High introduces model systems, new datasets, or governance. Impact is an analyst estimate and needs user validation.

English has 925 also-called assignments on 507 topics and 1,553 see-reference assignments on 750 topics. Descriptor labels cover 1,015 topics; every topic has a title, summary, and resources. This gives multiple complementary retrieval fields. Do not assume corpus coverage establishes ranking quality.

## Opportunity-by-opportunity assessment


### Lexical search with field boosting

**User problem:** Find the right topic despite noisy prose. **Fields:** @title; @meta-desc; full-summary plain text; site @title. **Expected value:** High. **Effort:** Low. **Priority:** Now.

Boost title and exact alias, then descriptor, description, summary; resource titles lower. No engine chosen.

**Additional data / dependencies:** None beyond normalization and a reviewed evaluation set. **Limits:** Long summaries and resource titles can swamp topic names; ranking must be tested.

### Exact and synonym matching

**User problem:** HbA1c, Backache, and other known terms are hard to find. **Fields:** @title; also-called; see-reference; descriptor. **Expected value:** High. **Effort:** Low. **Priority:** Now.

Normalize case/whitespace; preserve source label; return choices on collisions.

**Additional data / dependencies:** Language-aware term multimap; collision handling. **Limits:** Aliases are not universally equivalent; 109 English collision keys.

### Autocomplete and curated query suggestions

**User problem:** Users do not know the dataset vocabulary. **Fields:** @title; also-called; see-reference. **Expected value:** High. **Effort:** Low. **Priority:** Now.

Suggest existing labels with canonical topic context; no generated or behavior-derived phrases.

**Additional data / dependencies:** Deduplicated dictionary; accessible keyboard behavior. **Limits:** Avoid symptom-to-diagnosis completions and popular-query logging.

### Faceted resource discovery

**User problem:** Users need education, trials, or publications quickly. **Fields:** group; site / information-category; organization; standard-description. **Expected value:** High. **Effort:** Low. **Priority:** Now.

Start with medical group, resource category, and language. Keep producer optional.

**Additional data / dependencies:** Separate topic vs resource facets; exclusion of NON-DISPLAY label. **Limits:** Resource categories are mixed intent/audience/format; counts must reflect assignments.

### Related topics and Spanish switch

**User problem:** Users need adjacent information and preferred language. **Fields:** related-topic; language-mapped-topic; summary href. **Expected value:** High. **Effort:** Low. **Priority:** Now.

Use curated related topics first and explicit translation mapping; offer summary-linked topics with label.

**Additional data / dependencies:** ID/URL resolution; explicit edge labels; selected Spanish records. **Limits:** One topic lacks Spanish mapping; anchor context is untyped.

### Clinical trial and publication link-outs

**User problem:** Find existing research resources without copying them. **Fields:** site @url/@title; information-category; organization. **Expected value:** High. **Effort:** Low. **Priority:** Next.

Expose existing Clinical Trials and Journal Articles links in topic detail; no trial matching.

**Additional data / dependencies:** Topic selected; external-navigation labels. **Limits:** Search links are not trial enrollments, eligibility decisions, article records, or live results.

### Shared MeSH recommendations

**User problem:** Find topics with a specific controlled concept. **Fields:** descriptor @id; qualifier @id; mesh-heading. **Expected value:** Medium. **Effort:** Low. **Priority:** Next.

Show bounded “Shares MeSH descriptor” links separately from curated relations.

**Additional data / dependencies:** Reverse descriptor map; reason labels; qualifier preservation. **Limits:** Only 175 English pairs; no MeSH hierarchy; Spanish inheritance must be explicit.

### Shared group/resource exploration

**User problem:** Browse beyond explicit related topics. **Fields:** group @id; site @url. **Expected value:** Medium. **Effort:** Low. **Priority:** Next.

Use group browse and “also links to” labels; do not call these clinical similarities.

**Additional data / dependencies:** Bounded candidate lists; resource URL identity. **Limits:** Broad groups and institute homepages create irrelevant associations.

### Multilingual search

**User problem:** Discover translated topic using English or Spanish label. **Fields:** language-mapped-topic; Spanish @title / aliases / summary; site @language-mapped-url. **Expected value:** High. **Effort:** Medium. **Priority:** Next.

Start with Spanish switching; bilingual label lookup can follow without machine translation.

**Additional data / dependencies:** Explicit language control; bilingual evaluation; separate analyzers. **Limits:** No Spanish MeSH; content parity and resource parity differ.

### Search intent detection

**User problem:** Distinguish learning from trials or provider navigation. **Fields:** information-category; site @title; topic labels. **Expected value:** Medium. **Effort:** Medium. **Priority:** Next.

Prefer visible user-selected resource intents; rules may suggest filters, never care routing.

**Additional data / dependencies:** Reviewed lexical intent rules or user-selected intent. **Limits:** Dataset has no labeled queries or validated symptom/triage intents.

### Privacy-preserving optimization

**User problem:** Find relevance failures without retaining health queries. **Fields:** Offline evaluation cases; aggregate UI events. **Expected value:** High. **Effort:** Low. **Priority:** Now.

Use synthetic queries offline; collect only broad aggregate metrics without query/topic identifiers by default.

**Additional data / dependencies:** Reviewed metrics schema; suppression and retention controls. **Limits:** Topic IDs, destination URLs, and stable identifiers can disclose health interests too.

### Semantic search

**User problem:** Match “my knees hurt when I walk” to relevant wording. **Fields:** full-summary plain text; @title; aliases; descriptor. **Expected value:** Medium. **Effort:** High. **Priority:** Later.

Compare against lexical baseline only if paraphrase failures remain. No embeddings generated.

**Additional data / dependencies:** Embedding model/runtime and relevance/safety evaluation, not approved. **Limits:** Benefit unmeasured; false symptom-to-diagnosis similarity; model privacy.

### Hybrid search

**User problem:** Improve recall while keeping exact medical terms. **Fields:** Lexical fields plus future semantic representations. **Expected value:** Medium. **Effort:** High. **Priority:** Later.

Require measured incremental value over lexical search; no engine implemented.

**Additional data / dependencies:** Both retrieval systems; score fusion; benchmark. **Limits:** Additional cost and opaque ranking without demonstrated improvement.

### Future grounded RAG

**User problem:** Answer supported educational questions with citations. **Fields:** Attributable summaries; source IDs/URLs; metadata; relationships. **Expected value:** Medium. **Effort:** High. **Priority:** Later.

Treat summary-grounded education as research only; external linked bodies are not locally available.

**Additional data / dependencies:** Rights, versioned retrieval, safe abstention, clinical review, model approval. **Limits:** Insufficient evidence, stale links, hallucinations, personalized medical advice.

### Provider locator or clinical routing

**User problem:** Find nearby providers or pick a specialty for symptoms. **Fields:** Only site link-outs; no provider entities. **Expected value:** High. **Effort:** High. **Priority:** Avoid.

Avoid claiming native near-me search or automated specialty advice from this corpus.

**Additional data / dependencies:** Verified directory/geographic/availability data and care governance. **Limits:** No local providers, coordinates, insurance, appointments, or patient context.

## Field use and initial ranking hypothesis

Use language as a hard scope unless the user opts into bilingual search. Keep normalized title/aliases as exact keys and tokenizable text separately. Rank exact title/alias matches before partial titles, descriptors, descriptions, and summary text. Use source resource titles as a lower-weight recall signal; category/organization should filter or contextualize rather than dominate clinical term relevance. These are qualitative hypotheses: no numeric boost values or relevance improvements have been established.

Autocomplete should display the matched alias and destination topic together; show both Migraine and Headache for `Vascular Headache`. Query suggestions should come from the vocabulary, not from logging users’ health queries. Preserve `HbA1c` case in display while matching case-insensitively. Plural handling (`knees` → `knee`) and a small reviewed lay-term dictionary could improve lexical recall, but they are new authored configuration and need review; the XML does not explicitly define `hurt` as a synonym of `pain`.

Semantic discovery may help paraphrases, yet Knee Injuries and Disorders (304) already says “pain and difficulty walking” in its summary. A lexical baseline indexing summaries and resource titles may be sufficient for this example. Evaluate broad symptom phrasing without declaring a diagnosis. Hybrid and RAG value remains hypothetical until a baseline shows a real gap.

## Proposed offline relevance cases, not executed retrieval tests

| Synthetic query | Evidence-based expected destination / behavior |
| --- | --- |
| HbA1c | [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308) through source aliases |
| Backache | [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157) through also-called |
| Vascular Headache | Both [Headache](<https://medlineplus.gov/headache.html>) (ID 273); [Migraine](<https://medlineplus.gov/migraine.html>) (ID 3157); explain ambiguity |
| Allergic Rhinitis | Both [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24); [Hay Fever](<https://medlineplus.gov/hayfever.html>) (ID 4746) |
| knees hurt when I walk | [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304) as educational candidate; optionally [Walking Problems](<https://medlineplus.gov/walkingproblems.html>) (ID 5673); no diagnostic inference |
| diabetes clinical trials | [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4) with Clinical Trials resources |
| dolor de cabeza | [Dolor de cabeza](<https://medlineplus.gov/spanish/headache.html>) (ID 1933) in Spanish scope; mapped English optional |
| doctor near me | Explain native directory gap; offer source-listed directory links |

Acceptable outcomes and graded relevance require content-owner review. The 30-record subset is intentionally demonstrative, so its relevance/zero-result measures cannot be generalized to all healthcare queries. No term logs, popularity data, click histories, or patient data are necessary for the initial POC.
