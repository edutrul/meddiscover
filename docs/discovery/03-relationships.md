# Relationship analysis

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

## Relationship semantics

| Field | Explicitly supplied | Permitted inference / limit |
| --- | --- | --- |
| also-called | Term attached to a topic | Synonym retrieval; no separate topic edge |
| see-reference | Index entry attached to a topic | Search redirect cue; collisions prevent one-to-one equivalence |
| group | Topic membership with ID/name/URL | Shared-group association; broad navigation, not clinical relation |
| mesh-heading / descriptor / qualifier | Controlled descriptor and optional qualifier | Same descriptor gives candidate association; keep qualifier context |
| related-topic | Directed curated topic ID/title/URL | Safe to label “Related topic”; reciprocity not assumed |
| language-mapped-topic | Directed equivalent topic mapping | Spanish switch; equivalence does not mean word-for-word translation |
| other-language | Language name/vernacular and URL | Link-out only; does not add full topic records in 52 languages |
| primary-institute | Assigned institute name/URL | Provenance/ownership context; no clinician referral |
| site | Topic-to-resource assignment | Shared URL gives resource overlap, not clinical equivalence |
| information-category | Resource presentation/category label | Intent grouping only; sharing category gives no clinical relation |
| organization | Resource producer label | Attribution facet; sharing producer gives no clinical relation |
| summary hyperlinks | Author-supplied hyperlinks in prose | Canonical URL lookup resolves topic targets; context is not a typed clinical claim |

## Explicit edges across the full corpus

| Edge type | Directed occurrences |
| --- | --- |
| language-mapped-topic | 2032 |
| summary-link | 14893 |
| related-topic | 6289 |

Summary edges count anchor occurrences whose resolved URL exactly matches a local canonical topic URL after fragment removal; repeated anchors can create repeated edges. Unmatched hyperlinks remain external/non-topic nodes. Related and language mappings use source IDs. No equivalence or cause/treatment relation is inferred from an anchor alone.

## English graph connectivity

An undirected projection is used only for neighborhood and weak-component measurement; source edges remain directed. Degree counts distinct English neighbors across incoming and outgoing edges, excludes self-neighbors, and does not count aliases, language mappings, broad groups, or resources. Incoming/outgoing columns below count edge occurrences.


### Curated related-topic only

22 weak components; largest has 995 topics; 20 topics have zero distinct English neighbors.

| Topic | Distinct neighbors | Outgoing | Incoming |
| --- | --- | --- | --- |
| [Genetic Disorders](<https://medlineplus.gov/geneticdisorders.html>) (ID 33) | 39 | 37 | 6 |
| [Bacterial Infections](<https://medlineplus.gov/bacterialinfections.html>) (ID 158) | 37 | 34 | 7 |
| [Viral Infections](<https://medlineplus.gov/viralinfections.html>) (ID 454) | 36 | 34 | 9 |
| [Nutrition](<https://medlineplus.gov/nutrition.html>) (ID 36) | 28 | 22 | 18 |
| [Heart Diseases](<https://medlineplus.gov/heartdiseases.html>) (ID 277) | 23 | 16 | 15 |
| [Eye Diseases](<https://medlineplus.gov/eyediseases.html>) (ID 247) | 21 | 21 | 12 |
| [Pregnancy](<https://medlineplus.gov/pregnancy.html>) (ID 368) | 21 | 19 | 8 |
| [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4) | 21 | 19 | 17 |
| [Childhood Vaccines](<https://medlineplus.gov/childhoodvaccines.html>) (ID 1342) | 20 | 16 | 12 |
| [Mental Disorders](<https://medlineplus.gov/mentaldisorders.html>) (ID 5676) | 20 | 20 | 7 |

Isolated under this definition: [Abortion](<https://medlineplus.gov/abortion.html>) (ID 122); [Appendicitis](<https://medlineplus.gov/appendicitis.html>) (ID 1223); [Thymus Cancer](<https://medlineplus.gov/thymuscancer.html>) (ID 1245); [Piercing and Tattoos](<https://medlineplus.gov/piercingandtattoos.html>) (ID 1283); [Critical Care](<https://medlineplus.gov/criticalcare.html>) (ID 1352); [Anatomy](<https://medlineplus.gov/anatomy.html>) (ID 138); [Caffeine](<https://medlineplus.gov/caffeine.html>) (ID 1456); [Cloning](<https://medlineplus.gov/cloning.html>) (ID 1606); [Cerebral Palsy](<https://medlineplus.gov/cerebralpalsy.html>) (ID 180); [Fistulas](<https://medlineplus.gov/fistulas.html>) (ID 3054); [Pressure Sores](<https://medlineplus.gov/pressuresores.html>) (ID 371); [Dual Diagnosis](<https://medlineplus.gov/dualdiagnosis.html>) (ID 4270); [Bone Grafts](<https://medlineplus.gov/bonegrafts.html>) (ID 4274); [Endoscopy](<https://medlineplus.gov/endoscopy.html>) (ID 4513); [Pemphigus](<https://medlineplus.gov/pemphigus.html>) (ID 4948); [Fever](<https://medlineplus.gov/fever.html>) (ID 511); [Climate Change](<https://medlineplus.gov/climatechange.html>) (ID 5144); [Medical Ethics](<https://medlineplus.gov/medicalethics.html>) (ID 5526); [Biopsy](<https://medlineplus.gov/biopsy.html>) (ID 5921); [Hiccups](<https://medlineplus.gov/hiccups.html>) (ID 6084).

### Curated related-topic plus resolved summary hyperlinks

3 weak components; largest has 1015 topics; 2 topics have zero distinct English neighbors.

| Topic | Distinct neighbors | Outgoing | Incoming |
| --- | --- | --- | --- |
| [Fatigue](<https://medlineplus.gov/fatigue.html>) (ID 5324) | 100 | 31 | 77 |
| [Smoking](<https://medlineplus.gov/smoking.html>) (ID 108) | 91 | 42 | 74 |
| [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4) | 88 | 39 | 93 |
| [Heart Diseases](<https://medlineplus.gov/heartdiseases.html>) (ID 277) | 86 | 43 | 82 |
| [Diagnostic Imaging](<https://medlineplus.gov/diagnosticimaging.html>) (ID 1104) | 84 | 5 | 87 |
| [Stroke](<https://medlineplus.gov/stroke.html>) (ID 8) | 81 | 32 | 74 |
| [Obesity](<https://medlineplus.gov/obesity.html>) (ID 61) | 79 | 19 | 80 |
| [Breathing Problems](<https://medlineplus.gov/breathingproblems.html>) (ID 3077) | 74 | 17 | 70 |
| [Fever](<https://medlineplus.gov/fever.html>) (ID 511) | 73 | 7 | 68 |
| [Nausea and Vomiting](<https://medlineplus.gov/nauseaandvomiting.html>) (ID 489) | 68 | 11 | 64 |

Isolated under this definition: [Anatomy](<https://medlineplus.gov/anatomy.html>) (ID 138); [Hiccups](<https://medlineplus.gov/hiccups.html>) (ID 6084).

179 English topics have no **outgoing** related-topic, but only 20 have no incoming or outgoing curated neighbor. After adding summary topic hyperlinks, only Anatomy (138) and Hiccups (6084) remain isolated. They still have resource/group relationships; “isolated” is graph-definition dependent. Full degree lists/components are in JSON.

## Inferred associations, English topics only

| Shared key | Keys shared by >=2 topics | Distinct undirected topic pairs | Meaning |
| --- | --- | --- | --- |
| mesh | 66 | 175 | Shared descriptor ID, qualifiers not collapsed into equivalence |
| group | 44 | 48937 | Broad browse membership |
| resource | 5331 | 48563 | Exact same resource URL |

Pairs can overlap across signals. They are not added to the explicit edge counts. Shared descriptor analysis uses descriptor IDs, not spelling, and reports descriptor-only overlap; a qualified heading such as “prevention & control” must retain that qualifier when displayed. Resource overlap includes common institute homepages, which can dominate pair counts. Do not present all shared-resource/group pairs as clinical recommendations.

Real examples:

- Diabetes Type 2 (5930) and How to Prevent Diabetes (6661) share descriptor D003924. Topic 6661 adds qualifier Q000517 (`prevention & control`). This supports a labeled association, not interchangeability.
- Diabetes (4), Diabetes in Children and Teens (6026), and Diabetic Heart Disease (6011) share D003920.
- Knee Injuries and Disorders (304), Osteoarthritis (1250), and Back Pain (157) share group 10, Bones, Joints and Muscles. This is a broad browse grouping.
- Back Pain (157) and Walking Problems (5673) both link to `https://www.choosept.com/find-a-pt`. The evidence is shared directory access, not shared diagnosis.
- A1C (6308) has no outgoing related-topic, but its summary links to Diabetes Type 2 (5930), Prediabetes (5784), and Blood Glucose (6121). Diabetes (4) explicitly points to A1C. Direction matters.

## English–Spanish mappings

There are **1,016 distinct English–Spanish pairs**, represented by 2,032 reciprocal directed mappings. All targets and labels resolve; zero nonreciprocal mappings. Medical Ethics (5526) is the only English topic without a mapping. No Spanish record carries MeSH; any MeSH bridge is derived through the explicit mapping and must record its English provenance. English site assignments have 19,150 mapped-resource URLs (61.00%); topic translation does not guarantee every resource is translated. Other-language links support additional link-outs but do not constitute local multilingual corpora.

| English record | Spanish record |
| --- | --- |
| [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304) | [Lesiones y enfermedades de la rodilla](<https://medlineplus.gov/spanish/kneeinjuriesanddisorders.html>) (ID 1994) |
| [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157) | [Dolor de espalda](<https://medlineplus.gov/spanish/backpain.html>) (ID 1766) |
| [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308) | [Prueba de hemoglobina glicosilada (HbA1c)](<https://medlineplus.gov/spanish/a1c.html>) (ID 6309) |
| [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4) | [Diabetes](<https://medlineplus.gov/spanish/diabetes.html>) (ID 1851) |
| [Headache](<https://medlineplus.gov/headache.html>) (ID 273) | [Dolor de cabeza](<https://medlineplus.gov/spanish/headache.html>) (ID 1933) |
| [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24) | [Alergia](<https://medlineplus.gov/spanish/allergy.html>) (ID 1734) |

## Potential clusters for exploration

These are analyst-selected thematic clusters based on actual edges and metadata, not a community-detection result, clinical ontology, or validated care pathway:

| Cluster | Seed records | Evidence and boundary |
| --- | --- | --- |
| Knee and movement | [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304); [Knee Replacement](<https://medlineplus.gov/kneereplacement.html>) (ID 3924); [Walking Problems](<https://medlineplus.gov/walkingproblems.html>) (ID 5673); [Osteoarthritis](<https://medlineplus.gov/osteoarthritis.html>) (ID 1250); [Arthritis](<https://medlineplus.gov/arthritis.html>) (ID 23) | 304 → 3924 explicit related; 5673 → 304 explicit related; 304 summary → 1250; group 10 shared |
| Back and spine | [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157); [Sciatica](<https://medlineplus.gov/sciatica.html>) (ID 1532); [Herniated Disk](<https://medlineplus.gov/herniateddisk.html>) (ID 284) | 157 explicitly relates to Sciatica and Herniated Disk; group overlap adds broad browse context |
| Diabetes and testing | [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4); [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308); [Blood Glucose](<https://medlineplus.gov/bloodglucose.html>) (ID 6121); [Diabetes Type 2](<https://medlineplus.gov/diabetestype2.html>) (ID 5930); [Prediabetes](<https://medlineplus.gov/prediabetes.html>) (ID 5784); [How to Prevent Diabetes](<https://medlineplus.gov/howtopreventdiabetes.html>) (ID 6661) | Diabetes related edges; A1C summary links; D003924 shared by type 2/prevention |
| Headache discovery | [Headache](<https://medlineplus.gov/headache.html>) (ID 273); [Migraine](<https://medlineplus.gov/migraine.html>) (ID 3157) | Reciprocal curated edges; Vascular Headache alias collision |
| Allergy discovery | [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24); [Hay Fever](<https://medlineplus.gov/hayfever.html>) (ID 4746); [Food Allergy](<https://medlineplus.gov/foodallergy.html>) (ID 1256); [Anaphylaxis](<https://medlineplus.gov/anaphylaxis.html>) (ID 6257) | Allergy related edges; Allergic Rhinitis alias collision; summary anchors |

## Lightweight knowledge graph feasibility — recommendation

**Yes, for a bounded discovery experience.** Plain files and in-memory maps can represent topics, terms, groups, descriptors/qualifiers, organizations, and resources, with typed adjacency lists. A topic-ID map, canonical-URL map, term-to-topic multimap, and descriptor/group/resource reverse maps are sufficient. Keep explicit `related-topic`, `language-mapped-topic`, and `summary-link` edges separate from inferred `shared-descriptor`, `shared-group`, and `shared-resource` candidates; store source field and snapshot hash on every derivation. Cap expansion at one hop initially and label why each neighbor appears.

A graph database adds no demonstrated value to the proposed 30-record POC. Broad group/resource projections are dense: compute bounded neighbors when needed rather than pre-materializing every possible topic pair in a future UI payload. The discovery JSON stores these pair inventories for audit only. No graph database or graph UI was implemented. Typed clinical inference, MeSH ancestry, physician networks, and treatment recommendations are unsupported by these edges.
