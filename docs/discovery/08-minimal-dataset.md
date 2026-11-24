# Minimal viable dataset recommendation

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

## Proposed scope: 30 actual records, 24 English + 6 Spanish

This is a **recommended selection manifest**, not a content import or implemented application. Thirty records cover five requested medical discovery areas, provider-navigation limits, and trial education while giving six local bilingual pairs. Fewer records would remove either distinct ambiguity cases (headache/allergy/diabetes), a qualified MeSH overlap, the provider data-gap demonstration, or a core journey’s Spanish counterpart. This is a reasoned representative minimum for these demonstrations, not a mathematically optimized set or a statistically representative healthcare sample. A full 24-pair bilingual sample would require 48 records, exceeding the target; the other 18 mappings should remain source link-outs.

The selection has **1122 site assignments**, **981 distinct site URLs**, 21 English records with aliases, 24 with MeSH descriptors, and 1 with qualifiers. All 24 English records have Spanish mappings, but only six counterparts are proposed for local storage. Some relationships point outside the sample: preserve the target ID/title/URL as external navigation, rather than pretend the sample is closed.

## English selection

| ID | Exact title / canonical URL | Why selected |
| --- | --- | --- |
| 304 | [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304) | Knee symptom language, ACL/meniscus terms, links and directories |
| 3924 | [Knee Replacement](<https://medlineplus.gov/kneereplacement.html>) (ID 3924) | Knee arthroplasty alias; explicit knee relationship |
| 1250 | [Osteoarthritis](<https://medlineplus.gov/osteoarthritis.html>) (ID 1250) | OA and degenerative joint disease; symptom paraphrase candidate |
| 23 | [Arthritis](<https://medlineplus.gov/arthritis.html>) (ID 23) | Joint inflammation; broad arthritis family |
| 5673 | [Walking Problems](<https://medlineplus.gov/walkingproblems.html>) (ID 5673) | Gait Abnormalities; walking phrase and knee edge |
| 417 | [Sprains and Strains](<https://medlineplus.gov/sprainsandstrains.html>) (ID 417) | Injury-language contrast; shared musculoskeletal group |
| 157 | [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157) | Backache/Lumbago/Low Back Pain; back journey |
| 1532 | [Sciatica](<https://medlineplus.gov/sciatica.html>) (ID 1532) | Explicit spine neighbor and controlled term |
| 284 | [Herniated Disk](<https://medlineplus.gov/herniateddisk.html>) (ID 284) | Intervertebral Disc Displacement MeSH versus lay title |
| 6308 | [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308) | HbA1c variants, no outgoing related edge, Spanish mapping |
| 6121 | [Blood Glucose](<https://medlineplus.gov/bloodglucose.html>) (ID 6121) | Blood sugar synonym; diabetes/A1C relationships |
| 4 | [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4) | DM/Diabetes mellitus; highly connected hub |
| 5930 | [Diabetes Type 2](<https://medlineplus.gov/diabetestype2.html>) (ID 5930) | Sugar Diabetes ambiguity; D003924 overlap |
| 6661 | [How to Prevent Diabetes](<https://medlineplus.gov/howtopreventdiabetes.html>) (ID 6661) | D003924 with prevention & control qualifier |
| 5784 | [Prediabetes](<https://medlineplus.gov/prediabetes.html>) (ID 5784) | Insulin Resistance index term; A1C summary target |
| 273 | [Headache](<https://medlineplus.gov/headache.html>) (ID 273) | Vascular Headache ambiguity; headache journey |
| 3157 | [Migraine](<https://medlineplus.gov/migraine.html>) (ID 3157) | Shared ambiguous alias; explicit headache neighbor |
| 24 | [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24) | Hypersensitivity; Allergic Rhinitis ambiguity |
| 4746 | [Hay Fever](<https://medlineplus.gov/hayfever.html>) (ID 4746) | Allergic Rhinitis; explicit allergy neighbor |
| 1256 | [Food Allergy](<https://medlineplus.gov/foodallergy.html>) (ID 1256) | Food-specific aliases; narrower allergy intent |
| 6257 | [Anaphylaxis](<https://medlineplus.gov/anaphylaxis.html>) (ID 6257) | Safety-boundary example; no diagnostic action |
| 1433 | [Choosing a Doctor or Health Care Service](<https://medlineplus.gov/choosingadoctororhealthcareservice.html>) (ID 1433) | Provider-selection education and directory link-outs |
| 486 | [Health Facilities](<https://medlineplus.gov/healthfacilities.html>) (ID 486) | Facility selection and directory gap |
| 192 | [Clinical Trials](<https://medlineplus.gov/clinicaltrials.html>) (ID 192) | Clinical-trial education versus live trial data |

## Spanish selection

| ID | Exact title / canonical URL | English partner |
| --- | --- | --- |
| 1994 | [Lesiones y enfermedades de la rodilla](<https://medlineplus.gov/spanish/kneeinjuriesanddisorders.html>) (ID 1994) | [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304) |
| 1766 | [Dolor de espalda](<https://medlineplus.gov/spanish/backpain.html>) (ID 1766) | [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157) |
| 6309 | [Prueba de hemoglobina glicosilada (HbA1c)](<https://medlineplus.gov/spanish/a1c.html>) (ID 6309) | [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308) |
| 1851 | [Diabetes](<https://medlineplus.gov/spanish/diabetes.html>) (ID 1851) | [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4) |
| 1933 | [Dolor de cabeza](<https://medlineplus.gov/spanish/headache.html>) (ID 1933) | [Headache](<https://medlineplus.gov/headache.html>) (ID 273) |
| 1734 | [Alergia](<https://medlineplus.gov/spanish/allergy.html>) (ID 1734) | [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24) |

The machine-readable [selection manifest](minimal-selection.json) preserves IDs, exact titles, canonical URLs, language, rationale, source hash, and attribution. It contains no external resource bodies and does not duplicate the full dataset. `dataset-profile.json` remains full-corpus analysis evidence and should not be shipped as the POC corpus.

## Demonstrations and acceptance criteria proposed for approval

| Capability | Real evidence | Expected demonstration |
| --- | --- | --- |
| Exact/synonym lookup | 6308 HbA1C/HbA1c; 157 Backache/Lumbago | Same canonical topic from source alias; original display case |
| Ambiguity | 273/3157 Vascular Headache; 24/4746 Allergic Rhinitis; 4/5930 Sugar Diabetes | Show multiple labeled candidates |
| Related navigation | 304 → 3924; 157 → 1532/284; 273 ↔ 3157 | Show why link exists; keep source edge direction |
| Group browse | 304/1250/157 share group 10 | Browse musculoskeletal content without clinical-equivalence claim |
| MeSH overlap with qualifier | 5930/6661 D003924; 6661 Q000517 | Separate inferred descriptor association from curated related edge |
| External resources | Education, Clinical Trials, Journal Articles, Find an Expert | Open original URL with source organization and external-action label |
| Language switch | Six English/Spanish pairs above | Navigate reciprocal explicit mapping without translation model |
| Potential semantic use case | 304 pain/difficulty walking; 5673 Gait Abnormalities; 1250 movement terms | Compare lexical baseline on synthetic paraphrases before approving semantic retrieval |

## Proposed local fields versus external links

| Store locally after implementation approval | Keep external |
| --- | --- |
| Selected source IDs, titles, languages, canonical URLs and source hash/date | Full MedlinePlus topic page at canonical URL |
| Selected topic summaries as original HTML plus sanitized/plain variants; source meta-desc and attribution | Third-party article/encyclopedia bodies, images, video, PDFs, and lab/encyclopedia content linked from summary |
| also-called, see-reference, group IDs/labels/URLs, descriptor and qualifier IDs/labels | Full MeSH hierarchy and definitions not present in XML |
| Explicit related/language mappings and resolved summary hrefs; out-of-sample target stubs | Out-of-sample topic bodies and 18 unselected Spanish counterparts |
| Site title, URL, category, organization, standard-description, optional mapped URL | Live trials, publications returned by search links, provider/facility directory results |
| date-created and snapshot date-generated, clearly differentiated | Last-reviewed/updated dates not present; external destination freshness |

Recommended initial payload: only the 30 selected records and source-listed resource metadata; cap the number shown initially in the UI, while preserving all selected-record links in the proposed local data. No separate directory, trial, article, or provider entities should be invented. Maintain `Source: MedlinePlus, National Library of Medicine.` and per-resource organization attribution; verify launch-specific attribution and rights before public reuse. No extraction/import of this proposed subset has been implemented beyond its ID/title/URL manifest.
