# Data gaps and risks

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

## Verified dataset limitations

| Question / capability | What the XML supplies | What is absent |
| --- | --- | --- |
| What condition do I have? | General topic summaries and vocabulary | Personal history, examination, validated differential/triage model |
| What should I do for my symptoms? | Source educational summaries and external resources | Individualized medical assessment or safe care instructions for this person |
| What does my A1C result mean? | General A1C educational record and lab-test link | Patient result, clinical context, reviewed individualized interpretation |
| What doctor should I see? | Provider-selection education and condition-linked directories | Validated symptom-to-specialty mapping/referral rules |
| Find a nearby doctor or hospital | External directory link titles and URLs | Structured individual physician/hospital directory records, addresses, coordinates, distance, insurance, availability, booking |
| Are there current trials for me? | ClinicalTrials.gov query URLs | Trial entities, inclusion/exclusion criteria, recruiting status, patient eligibility |
| What do these scientific articles conclude? | PubMed query URLs in Journal Articles | Article bodies, complete article-level metadata or evidence synthesis |
| Is a link working now? | Original URL strings | Availability/redirect/content verification; explicitly out of scope |
| Is content medically current? | Snapshot generation date and topic creation dates | Per-topic last-reviewed/revised date or medical review evidence |
| Can every resource be read in Spanish? | 1,016 topic pairs and optional mapped site URLs | Complete resource translation parity; local MeSH on Spanish records |

**Directory conclusion:** the schema and all records were inspected for structure. The corpus consists of topic records and resource links, not individual providers or hospital listings. Resource metadata includes actual directory destinations (AAHKS, AAAAI, ChoosePT, Medicare Care Compare, etc.), but a resource title or organization label is not a provider entity. It cannot support native geographic lookup. External service area, eligibility, access, and availability were not checked.

## Are additional datasets necessary?

**No** for the recommended educational discovery POC: the current snapshot supports aliases, facets, attributed summaries, related navigation, source research links, and Spanish switching. A small manually reviewed synthetic evaluation set and approved UI safety copy are needed; these are project artifacts, not downloaded health datasets.

Additional data becomes genuinely necessary only if the product scope changes: provider/geographic data for a native near-me locator; live registry data for trial matching; licensed article/full-text evidence for document-level answers; reviewed bilingual terminology for broad cross-language semantic equivalence; or clinical governance and validated protocols for any care-routing feature. These are separate decisions and were not downloaded or integrated.

## Source attribution and rights

NLM requests attribution for XML reuse. See [MedlinePlus XML files](https://medlineplus.gov/xml.html), accessed 2026-11-24. Source summaries are identified as public-domain content, while some linked or hosted vendor content, including encyclopedia material and images, has separate restrictions. See [Using MedlinePlus content](https://medlineplus.gov/about/using/usingcontent/), accessed 2026-11-24. Preserve `Source: MedlinePlus, National Library of Medicine.` plus canonical source URLs and resource organization labels.

This is source-policy research, not legal clearance. Verify the intended public POC’s display, branding, attribution, and rights with the content owner before release. Do not copy external bodies, vendor encyclopedia material, images, logos, PDFs, videos, or article text merely because their URLs occur in XML. Link-outs avoid copying those bodies but still require accurate labeling; source listing is not endorsement of this POC. No external body content was fetched during this analysis.

## Freshness and operational risks

The root reports `11/24/2026 02:30:05`; its timezone is not specified. The filename and generated date agree. `date-created` describes first publication, not last medical review. The local snapshot is immutable evidence, not proof that every summary or linked resource is current. A future update design should compare IDs, summaries, metadata, removed links, and mappings between snapshots, preserve source versions, and record retrieval time separately from source generation/review time. No refresh automation was installed.

Exact URLs with search parameters or fragments can be semantically meaningful; aggressive deduplication can alter destinations. Links can redirect, expire, change language or rights, or return different trials/articles over time. URL syntax checks do not establish availability. External navigation can also disclose health interests to destination sites; avoid forwarding raw user queries or query-bearing referrers by default.

## Medical safety and insufficient evidence

Source vocabulary supports information discovery, not clinical decision-making. Alias overlap does not make two conditions equivalent; group/category/organization overlap is especially weak clinical evidence. A symptom phrase must not be converted into a diagnosis or specialty referral. Existing summary wording can be displayed with clear source attribution after review, but assembling selected snippets into new clinical instructions can remove context.

If retrieved material does not answer a question, say what is available and what remains unknown. Do not generate missing facts about care, providers, trial eligibility, medications, or a person’s test results. Future RAG would need evidence sufficiency checks, versioned citations, abstention, clinical oversight, rights verification, and privacy review; its utility and safety are unproven in this phase. No model calls or answer-generation pipeline were used.

## Evidence limitations and open approvals

The referenced DTD could not be retrieved, so formal required/optional declarations and DTD validation are unresolved. The NLM XML guide is documented as a fallback, separate from observed field coverage. HTMLParser and URL checks are heuristic. No external availability, resource-body quality, clinical correctness, user relevance, accessibility, or performance test was conducted. Suggested impact/effort ratings, cluster labels, and ranking behavior are recommendations, not observed product performance.

Before implementation, approve the 30-record scope, intended audience/geography, language behavior, attribution/display policy, safety and privacy defaults, and evaluation criteria. Before public release, assign content/clinical review and confirm rights, freshness, and external-navigation expectations. Phase 0 is complete as discovery; implementation remains unapproved.
