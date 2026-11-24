# Data quality

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

## Findings and interpretation

| Check | Count | Definition / interpretation |
| --- | --- | --- |
| Duplicate topic IDs | 0 | Exact, all languages |
| Duplicate canonical topic URLs | 0 | Exact URL |
| Duplicate titles | 0 | Exact within language |
| Duplicate normalized titles | 0 | NFKC/casefold/whitespace, within language |
| Repeated normalized alias keys | 811 | Includes repeat across also-called and see-reference |
| Aliases assigned to multiple topics | 159 | 109 English; 50 Spanish; normalized within language |
| Alias capitalization-variant keys | 258 | 256 English; 2 Spanish; editorial differences, not necessarily defects |
| Duplicate site URLs within a topic | 0 | Exact URL; cross-topic reuse analyzed separately |
| Empty leaf/attribute values | 1 | Empty meta-desc only |
| Missing full-summary | 1 | Spanish ID 1900 |
| URL occurrences flagged syntactically | 568 | 566 other-language fragment strings + 2 summary links |
| Broken related/mapped ID references | 0 | Both languages; all targets found |
| Reference URL/title/language mismatches | 0 | Referenced target metadata compared |
| Summary HTML present | 2032 | Of 2,033; one summary absent |
| Summaries with selected markup anomalies | 9 | Heuristic tag/attribute checks; not HTML validation |

The XML is well-formed; no claim of DTD-validity or HTML-validity is made. Element presence is not equivalent to nonempty content or clinical completeness. All issue instances are retained under `quality` in the JSON, including topic IDs and paths.

There are **50 exact title strings repeated across languages**, such as Alcohol (1558 English / 1732 Spanish) and Anemia (139 English / 1743 Spanish). These are legitimate bilingual labels, not same-language duplicate topics. The full cross-language title inventory is preserved separately in JSON.

## Missing, empty, and optional metadata

| Issue | Real records / evidence | Design consequence |
| --- | --- | --- |
| Empty meta-desc | [Insomnia](<https://medlineplus.gov/insomnia.html>) (ID 6055) | Fall back to an attributed summary excerpt; do not invent description |
| Missing full-summary | [Problemas sexuales en las mujeres](<https://medlineplus.gov/spanish/sexualproblemsinwomen.html>) (ID 1900) | Render explicit unavailable state; preserve source link |
| No English MeSH descriptor | [Acute Flaccid Myelitis](<https://medlineplus.gov/acuteflaccidmyelitis.html>) (ID 7207); [Gluten Sensitivity](<https://medlineplus.gov/glutensensitivity.html>) (ID 6244) | Do not require MeSH for inclusion |
| No Spanish MeSH descriptors | 1,016 / 1,016 topics | Join via verified language mapping if using English MeSH; label inherited metadata |
| No outgoing related-topic | 179 English / 227 Spanish | Absence does not mean no incoming edges or no relevant topic |
| No primary-institute | 209 English topics | Do not require an institute label |
| No language mapping | [Medical Ethics](<https://medlineplus.gov/medicalethics.html>) (ID 5526) | Disable Spanish switch for this record |

Resource child coverage, with **site assignments as denominator**:

| Scope | Site field | Present | Absent | Coverage | Min/site | Max/site | Mean/site |
| --- | --- | --- | --- | --- | --- | --- | --- |
| All | categories | 52562 | 0 | 100.00% | 1 | 3 | 1.55 |
| All | organizations | 51070 | 1492 | 97.16% | 0 | 2 | 0.99 |
| All | descriptions | 17109 | 35453 | 32.55% | 0 | 4 | 0.34 |
| English | categories | 31393 | 0 | 100.00% | 1 | 3 | 1.12 |
| English | organizations | 30150 | 1243 | 96.04% | 0 | 2 | 0.97 |
| English | descriptions | 11316 | 20077 | 36.05% | 0 | 4 | 0.37 |
| Spanish | categories | 21169 | 0 | 100.00% | 1 | 3 | 2.17 |
| Spanish | organizations | 20920 | 249 | 98.82% | 0 | 2 | 1.0 |
| Spanish | descriptions | 5793 | 15376 | 27.37% | 0 | 4 | 0.29 |

All observed topic and site attributes and topic-level absence rates are enumerated in [01](01-dataset-profile.md). Optional missing aliases, resource descriptions, institute, or qualifiers are not treated as errors. `date-created` is present but is not a revision date. No unexpected empty literal leaf nodes were found.

Although meta-desc attribute presence is 100%, nonempty coverage is 1,016/1,017 English topics (99.90%) and 2,032/2,033 overall (99.95%); Spanish descriptions are all nonempty. All other observed literal leaf/attribute values are nonempty. The missing Spanish summary is an absence, separate from an empty value.

## Duplicate URLs and aliases

| Alias duplicate definition | Repeated keys |
| --- | --- |
| Exact language + alias text (all topics/alias fields) | 552 |
| Normalized alias repeated within a topic (across alias fields) | 668 |
| Normalized alias repeated within the same topic and field | 0 |


| Scope | Distinct site URLs reused across topics | Assignments beyond first occurrence |
| --- | --- | --- |
| All | 9456 | 19520 |
| English | 5331 | 11010 |
| Spanish | 4124 | 8508 |

Repeated site URLs across topics are expected reusable resources; retain topic-resource assignments and optionally deduplicate resource entities by exact URL. Query strings/fragments can be meaningful, so no aggressive canonicalization was used.

An alias is a retrieval cue, not an equivalence proof. The 811 repeated normalized alias keys include within-topic repeats and cross-topic collisions, not 811 duplicate topics. The 159 title-plus-alias ambiguity keys happen to match the alias-only collision count in this snapshot. No cross-language duplicate is automatically an error.

Representative collisions and capitalization variants:

| Normalized alias | Topics | Handling |
| --- | --- | --- |
| English\|allergic rhinitis | [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24); [Hay Fever](<https://medlineplus.gov/hayfever.html>) (ID 4746) | Offer multiple labeled results; no one-to-one rewrite |
| English\|vascular headache | [Headache](<https://medlineplus.gov/headache.html>) (ID 273); [Migraine](<https://medlineplus.gov/migraine.html>) (ID 3157) | Offer multiple labeled results; no one-to-one rewrite |
| English\|sugar diabetes | [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4); [Diabetes Type 2](<https://medlineplus.gov/diabetestype2.html>) (ID 5930) | Offer multiple labeled results; no one-to-one rewrite |
| English\|art | [HIV Medicines](<https://medlineplus.gov/hivmedicines.html>) (ID 4287); [Assisted Reproductive Technology](<https://medlineplus.gov/assistedreproductivetechnology.html>) (ID 6242) | Offer multiple labeled results; no one-to-one rewrite |
| English\|stomach ache | [Abdominal Pain](<https://medlineplus.gov/abdominalpain.html>) (ID 3061); [Stomach Disorders](<https://medlineplus.gov/stomachdisorders.html>) (ID 420) | Offer multiple labeled results; no one-to-one rewrite |
| English\|neuropathy | [Peripheral Nerve Disorders](<https://medlineplus.gov/peripheralnervedisorders.html>) (ID 1095); [Diabetic Nerve Problems](<https://medlineplus.gov/diabeticnerveproblems.html>) (ID 1465) | Offer multiple labeled results; no one-to-one rewrite |

A1C (6308) uses `HbA1C` in also-called and `HbA1c` in see-reference. Preserve original display strings and use case-insensitive matching. The same normalization handles `Blood sugar` / `Blood Sugar` on Blood Glucose (6121). Do not rewrite scientific acronym casing globally. Accent removal, plural handling, and stemming were not applied in these statistics.

## URL syntax and internal integrity

| Path / issue | Occurrences |
| --- | --- |
| health-topics/health-topic/other-language/@url | 566 |
| full-summary/a/@href | 2 |

These are **syntax warnings**, not failed HTTP checks. Most are spaces in other-language fragment identifiers that browsers may repair. The check flags whitespace/control/unsafe characters, malformed percent escapes, non-HTTP(S) schemes, missing/suspicious hostnames, parser errors, invalid ports, and userinfo. It is a conservative heuristic, not exhaustive RFC/IRI validation; Unicode hostnames and unencoded URL characters can require additional handling. Original URLs are preserved.

| Topic | Summary href | Issue |
| --- | --- | --- |
| [Acute Flaccid Myelitis](<https://medlineplus.gov/acuteflaccidmyelitis.html>) (ID 7207) | https://medlineplus.gov/lab-tests/electromyography-emg-and-nerve-conduction-studies/  | empty or surrounding whitespace; unescaped whitespace/control/unsafe character |
| [Diabetic Heart Disease](<https://medlineplus.gov/diabeticheartdisease.html>) (ID 6011) | https://medlineplus.gov/lab-tests/cholesterol-levels/"" | unescaped whitespace/control/unsafe character |

All 6,289 related-topic and 2,032 language-mapped-topic ID references resolve, with matching target URLs, normalized titles, and mapping language. Summary URLs resolve against the source topic URL, with fragments removed only for local topic lookup. **735 MedlinePlus summary href occurrences do not resolve to a topic record**: many are lab tests, encyclopedia, or genetics pages outside this topic corpus. They are not proven broken links. The full list is in JSON; link availability, redirect targets, and fragment existence were not tested.

### Topic-shaped summary targets absent from the snapshot

| Source topic | Original href |
| --- | --- |
| [HIV and Infections](<https://medlineplus.gov/hivandinfections.html>) (ID 1503) | https://medlineplus.gov/hivaidsmedicines.html |
| [Medicinas para la diabetes](<https://medlineplus.gov/spanish/diabetesmedicines.html>) (ID 4936) | ../spanish/bloodsugar.html |
| [Opioid Overdose](<https://medlineplus.gov/opioidoverdose.html>) (ID 7109) | opioidmisuseandaddiction.html |
| [Opioid Use Disorder (OUD) Treatment](<https://medlineplus.gov/opioidusedisorderoudtreatment.html>) (ID 7129) | opioidmisuseandaddiction.html |
| [Pregnancy and Opioids](<https://medlineplus.gov/pregnancyandopioids.html>) (ID 7131) | opioidmisuseandaddiction.html |
| [Pregnancy and Opioids](<https://medlineplus.gov/pregnancyandopioids.html>) (ID 7131) | opioidmisuseandaddictiontreatment.html |
| [Pregnancy and Substance Use](<https://medlineplus.gov/pregnancyandsubstanceuse.html>) (ID 1385) | marijuana.html |
| [Safe Opioid Use](<https://medlineplus.gov/safeopioiduse.html>) (ID 7127) | opioidmisuseandaddiction.html |
| [Salud sexual del adolescente](<https://medlineplus.gov/spanish/teensexualhealth.html>) (ID 2186) | ../spanish/sexuallytransmitteddiseases.html |
| [VIH y las infecciones](<https://medlineplus.gov/spanish/hivandinfections.html>) (ID 1727) | https://medlineplus.gov/spanish/hivaidsmedicines.html |


These 10 href occurrences match the usual topic URL path shape but have no exact canonical target in this snapshot. They are candidate stale topic references, potentially old slugs or redirects. Redirects were not followed, so their availability and intended replacement are unknown. Preserve the original href and flag for source-owner review; do not automatically map an old slug by guessing. Other unmatched internal hrefs chiefly target non-topic services.

## HTML inside summaries

The XML stores escaped HTML, which becomes a string after parsing. 2,032 summaries contain markup; the missing summary is Spanish 1900. Strip HTML for indexing and sanitize with an explicit allowlist before any future display; do not blindly render source HTML. These are design recommendations only.

| Observed HTML tag | Occurrences |
| --- | --- |
| p | 13894 |
| a | 15651 |
| ul | 4810 |
| li | 22333 |
| h3 | 4657 |
| strong | 3893 |
| br | 137 |
| em | 127 |
| b | 52 |
| table | 35 |
| thead | 40 |
| tr | 210 |
| th | 82 |
| tbody | 40 |
| td | 406 |
| u | 6 |
| topic | 5 |
| h4 | 16 |
| ph3 | 1 |
| i | 32 |
| span | 1 |
| img | 8 |
| h2 | 4 |
| p. | 1 |
| style | 1 |

Selected anomalies (heuristic):

| Topic | Flagged tags / attributes |
| --- | --- |
| [Bone Density](<https://medlineplus.gov/bonedensity.html>) (ID 5935) | ; a@hre |
| [Cáncer oral](<https://medlineplus.gov/spanish/oralcancer.html>) (ID 2062) | topic |
| [Diabetes tipo 1](<https://medlineplus.gov/spanish/diabetestype1.html>) (ID 1985) | ph3 |
| [Hiperglucemia](<https://medlineplus.gov/spanish/hyperglycemia.html>) (ID 6126) | topic |
| [Home Care Services](<https://medlineplus.gov/homecareservices.html>) (ID 1659) | p. |
| [Infecciones por estreptococo](<https://medlineplus.gov/spanish/streptococcalinfections.html>) (ID 2174) | topic |
| [Leucodistrofias](<https://medlineplus.gov/spanish/leukodystrophies.html>) (ID 2282) | topic |
| [Niveles de colesterol: Lo que usted debe saber](<https://medlineplus.gov/spanish/cholesterollevelswhatyouneedtoknow.html>) (ID 6782) | style |
| [Tratamiento del trastorno por consumo de opioides](<https://medlineplus.gov/spanish/opioidusedisorderoudtreatment.html>) (ID 7130) | topic |

Examples include nonstandard `<topic>`, `<ph3>`, `<p.>`, an `a@hre` typo, and inline style content. HTMLParser is tolerant: balanced tags, browser rendering, malicious payload absence, and complete HTML semantics were not certified. The JSON records all observed HTML tags and attribute keys. Eight image tags are not permission to reproduce images. No executable UI was created.

## Ambiguous terminology and review priorities

`Vascular Headache` maps to Headache and Migraine; `Allergic Rhinitis` maps to Allergy and Hay Fever; `Sugar Diabetes` maps to Diabetes and Diabetes Type 2. `ART` maps to two unrelated topic labels (see collision inventory); acronyms such as `OA`, `RA`, `DM`, and `DI` can also be ambiguous outside this corpus even when locally unique. These latter ambiguity risks are editorial judgments, not measured clinical ambiguity scores. “Knee pain” is a symptom expression, not a diagnosis or a unique disease alias.

Recommended order: preserve provenance; handle the absent summary and empty description; normalize matching while preserving display; expose collisions as choices; sanitize summary display; review the two malformed summary hrefs; then review multilingual fragments. Do not silently change the original XML. Clinical terminology and user-facing safety text need qualified review before public release.
