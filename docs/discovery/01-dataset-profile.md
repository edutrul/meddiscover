# Dataset profile

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

The full local XML was parsed offline with Python standard library `xml.etree.ElementTree`, `html.parser`, `urllib.parse`, `statistics`, and counters/sets. No resource website was crawled or checked for availability. Only NLM schema/usage documentation was consulted online; those are metadata references, not additional datasets.

Source: `data/raw/mplus_topics_2026-11-24.xml`; **30,156,344 bytes** (30.16 MB / 28.76 MiB). SHA-256: `b71e4ae9d979fe8061ddb0651326fa78a9db93d5c0d7f8bd55b8fdfee0140e93`. The root declares 2,033 records; parsed count agrees. The XML is well-formed. External DTD resolution is disabled by the parser.

Reproduce from the project root:

```sh
python3 scripts/discovery/analyze.py
python3 scripts/discovery/report.py
python3 scripts/discovery/verify.py
```

`dataset-profile.json` is a discovery evidence artifact, not a database or the proposed POC payload. It includes all per-language unique leaf/attribute values and counts, exact resource URLs and hostnames, record evidence, relationship edges, inferred pair projections, and all flagged issues. No search engine, embeddings, LLM calls, or database was created. Full-summary values in that inventory are XML-decoded HTML strings; the record evidence contains extracted plain text.

## Counts and definitions

| Scope | Topics | Site assignments | Unique site URLs | Off-MedlinePlus site assignments | Unique site hostnames |
| --- | --- | --- | --- | --- | --- |
| All | 2033 | 52562 | 33042 | 33256 | 689 |
| English | 1017 | 31393 | 20383 | 20970 | 635 |
| Spanish | 1016 | 21169 | 12661 | 12286 | 214 |

A `site` is an external-resource link assignment on a topic, even when it points to another MedlinePlus service. Off-MedlinePlus means hostname other than `medlineplus.gov` or `www.medlineplus.gov`. Counts of assignments, distinct URLs, and distinct documents are different: identical URLs can be reused by many topics, while different URLs can lead to the same document. Domains below mean hostnames retaining subdomains, not registrable domains. Exact URL identity preserves case, query string, trailing slash, and fragment. Unique counts across languages are not additive.

Coverage is the percentage of topics containing at least one occurrence of a field. Attribute coverage uses the number of its owning XML elements as denominator. Repeating-element statistics include zero occurrences for all topics in each scope; nested occurrences are counted per topic. `unique_leaf_values=0` for containers means not applicable, not empty content. Optionality is separate from coverage.

Strictly off-MedlinePlus resource identities:

| Scope | Off-host unique URLs | Off-host unique hostnames |
| --- | --- | --- |
| All | 22295 | 687 |
| English | 14412 | 633 |
| Spanish | 7885 | 212 |


Embedded summary hyperlinks are a separate asset, not included in site-resource totals:

| Scope | Topics with href | Href occurrences | Unique resolved href URLs | Off-MedlinePlus href occurrences |
| --- | --- | --- | --- | --- |
| All | 1775 | 15649 | 2190 | 21 |
| English | 892 | 7941 | 1125 | 11 |
| Spanish | 883 | 7708 | 1072 | 10 |


## XML vocabulary: every observed element and attribute

| Element path | Occurrences, all languages | Observed attributes |
| --- | --- | --- |
| health-topics | 1 | date-generated, total |
| health-topics/health-topic | 2033 | date-created, id, language, meta-desc, title, url |
| health-topics/health-topic/also-called | 2040 | None |
| health-topics/health-topic/full-summary | 2032 | None |
| health-topics/health-topic/group | 3651 | id, url |
| health-topics/health-topic/language-mapped-topic | 2032 | id, language, url |
| health-topics/health-topic/mesh-heading | 1358 | None |
| health-topics/health-topic/mesh-heading/descriptor | 1358 | id |
| health-topics/health-topic/other-language | 4885 | url, vernacular-name |
| health-topics/health-topic/primary-institute | 1619 | url |
| health-topics/health-topic/see-reference | 3180 | None |
| health-topics/health-topic/site | 52562 | language-mapped-url, title, url |
| health-topics/health-topic/site/information-category | 81333 | None |
| health-topics/health-topic/site/organization | 51844 | None |
| health-topics/health-topic/site/standard-description | 17796 | None |
| health-topics/health-topic/related-topic | 6289 | id, url |
| health-topics/health-topic/mesh-heading/qualifier | 39 | id |

### Required versus optional: documentation evidence and DTD limitation

The DOCTYPE references [mplus_topics.dtd](https://medlineplus.gov/xml/mplus_topics.dtd). No DTD exists in this workspace; direct retrieval failed because shell networking could not resolve the host and the web reader could not open this DTD. **DTD cardinalities, attribute declarations, order constraints, and formal validation remain unverified.** The table uses the [NLM XML description](https://medlineplus.gov/xmldescription.html), accessed 2026-11-24, as a documentation fallback. It must not be represented as a parsed DTD.

| Field | Published guide expectation; not DTD validation |
| --- | --- |
| full-summary | One per topic |
| group; site | At least one each per topic |
| also-called; see-reference; related-topic; other-language | Optional, repeating |
| language-mapped-topic | Optional, maximum one |
| primary-institute | Optional |
| mesh-heading / descriptor | Heading may repeat; contains descriptor |
| qualifier | Observed in XML; requirement not verified |
| site / organization; standard-description | Can repeat; organization may be absent |
| site / information-category | Can repeat |
| site @language-mapped-url | Optional |
| All other XML attributes | Observed below; required/default status not verified |

### Actual attribute coverage and cardinality

Cells show occurrences / percentage of owner elements. Root attributes `total` and `date-generated` each occur once (100%); their values are in source metadata.

| Attribute relative to health-topic | All | English | Spanish |
| --- | --- | --- | --- |
| /@meta-desc | 2033 / 100.0% | 1017 / 100.0% | 1016 / 100.0% |
| /@title | 2033 / 100.0% | 1017 / 100.0% | 1016 / 100.0% |
| /@url | 2033 / 100.0% | 1017 / 100.0% | 1016 / 100.0% |
| /@id | 2033 / 100.0% | 1017 / 100.0% | 1016 / 100.0% |
| /@language | 2033 / 100.0% | 1017 / 100.0% | 1016 / 100.0% |
| /@date-created | 2033 / 100.0% | 1017 / 100.0% | 1016 / 100.0% |
| /group/@url | 3651 / 100.0% | 1827 / 100.0% | 1824 / 100.0% |
| /group/@id | 3651 / 100.0% | 1827 / 100.0% | 1824 / 100.0% |
| /language-mapped-topic/@url | 2032 / 100.0% | 1016 / 100.0% | 1016 / 100.0% |
| /language-mapped-topic/@id | 2032 / 100.0% | 1016 / 100.0% | 1016 / 100.0% |
| /language-mapped-topic/@language | 2032 / 100.0% | 1016 / 100.0% | 1016 / 100.0% |
| /mesh-heading/descriptor/@id | 1358 / 100.0% | 1358 / 100.0% | 0 / N/A (no owner elements) |
| /other-language/@vernacular-name | 4885 / 100.0% | 4885 / 100.0% | 0 / N/A (no owner elements) |
| /other-language/@url | 4885 / 100.0% | 4885 / 100.0% | 0 / N/A (no owner elements) |
| /primary-institute/@url | 1619 / 100.0% | 808 / 100.0% | 811 / 100.0% |
| /site/@title | 52562 / 100.0% | 31393 / 100.0% | 21169 / 100.0% |
| /site/@url | 52562 / 100.0% | 31393 / 100.0% | 21169 / 100.0% |
| /site/@language-mapped-url | 38268 / 72.81% | 19150 / 61.0% | 19118 / 90.31% |
| /related-topic/@url | 6289 / 100.0% | 3316 / 100.0% | 2973 / 100.0% |
| /related-topic/@id | 6289 / 100.0% | 3316 / 100.0% | 2973 / 100.0% |
| /mesh-heading/qualifier/@id | 39 / 100.0% | 39 / 100.0% | 0 / N/A (no owner elements) |

## Field coverage and repetition — All

| Field | Topics present | Coverage | Occurrences | Min/topic | Max/topic | Mean/topic | Median/topic | Unique leaf values |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| also-called | 1108 | 54.50% | 2040 | 0 | 16 | 1.0 | 1 | 2013 |
| full-summary | 2032 | 99.95% | 2032 | 0 | 1 | 1.0 | 1 | 2032 |
| group | 2033 | 100.00% | 3651 | 1 | 14 | 1.8 | 2 | 88 |
| language-mapped-topic | 2032 | 99.95% | 2032 | 0 | 1 | 1.0 | 1 | 1982 |
| mesh-heading | 1015 | 49.93% | 1358 | 0 | 6 | 0.67 | 0 | 0 |
| mesh-heading/descriptor | 1015 | 49.93% | 1358 | 0 | 6 | 0.67 | 0 | 1250 |
| other-language | 1016 | 49.98% | 4885 | 0 | 46 | 2.4 | 0 | 52 |
| primary-institute | 1619 | 79.64% | 1619 | 0 | 1 | 0.8 | 1 | 50 |
| see-reference | 1453 | 71.47% | 3180 | 0 | 10 | 1.56 | 1 | 2984 |
| site | 2033 | 100.00% | 52562 | 2 | 178 | 25.85 | 21 | 0 |
| site/information-category | 2033 | 100.00% | 81333 | 4 | 250 | 40.01 | 32 | 60 |
| site/organization | 2033 | 100.00% | 51844 | 2 | 175 | 25.5 | 20 | 635 |
| site/standard-description | 1910 | 93.95% | 17796 | 0 | 103 | 8.75 | 6 | 11 |
| related-topic | 1627 | 80.03% | 6289 | 0 | 37 | 3.09 | 2 | 1875 |
| mesh-heading/qualifier | 28 | 1.38% | 39 | 0 | 3 | 0.02 | 0 | 15 |

## Field coverage and repetition — English

| Field | Topics present | Coverage | Occurrences | Min/topic | Max/topic | Mean/topic | Median/topic | Unique leaf values |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| also-called | 507 | 49.85% | 925 | 0 | 12 | 0.91 | 0 | 921 |
| full-summary | 1017 | 100.00% | 1017 | 1 | 1 | 1 | 1 | 1017 |
| group | 1017 | 100.00% | 1827 | 1 | 14 | 1.8 | 2 | 44 |
| language-mapped-topic | 1016 | 99.90% | 1016 | 0 | 1 | 1.0 | 1 | 1016 |
| mesh-heading | 1015 | 99.80% | 1358 | 0 | 6 | 1.34 | 1 | 0 |
| mesh-heading/descriptor | 1015 | 99.80% | 1358 | 0 | 6 | 1.34 | 1 | 1250 |
| other-language | 1016 | 99.90% | 4885 | 0 | 46 | 4.8 | 1 | 52 |
| primary-institute | 808 | 79.45% | 808 | 0 | 1 | 0.79 | 1 | 25 |
| see-reference | 750 | 73.75% | 1553 | 0 | 9 | 1.53 | 1 | 1423 |
| site | 1017 | 100.00% | 31393 | 4 | 178 | 30.87 | 25 | 0 |
| site/information-category | 1017 | 100.00% | 35312 | 4 | 195 | 34.72 | 29 | 31 |
| site/organization | 1017 | 100.00% | 30582 | 3 | 175 | 30.07 | 25 | 469 |
| site/standard-description | 1000 | 98.33% | 11667 | 0 | 103 | 11.47 | 8 | 7 |
| related-topic | 838 | 82.40% | 3316 | 0 | 37 | 3.26 | 2 | 976 |
| mesh-heading/qualifier | 28 | 2.75% | 39 | 0 | 3 | 0.04 | 0 | 15 |

## Field coverage and repetition — Spanish

| Field | Topics present | Coverage | Occurrences | Min/topic | Max/topic | Mean/topic | Median/topic | Unique leaf values |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| also-called | 601 | 59.15% | 1115 | 0 | 16 | 1.1 | 1.0 | 1111 |
| full-summary | 1015 | 99.90% | 1015 | 0 | 1 | 1.0 | 1.0 | 1015 |
| group | 1016 | 100.00% | 1824 | 1 | 14 | 1.8 | 2.0 | 44 |
| language-mapped-topic | 1016 | 100.00% | 1016 | 1 | 1 | 1 | 1.0 | 1016 |
| mesh-heading | 0 | 0.00% | 0 | 0 | 0 | 0 | 0.0 | 0 |
| mesh-heading/descriptor | 0 | 0.00% | 0 | 0 | 0 | 0 | 0.0 | 0 |
| other-language | 0 | 0.00% | 0 | 0 | 0 | 0 | 0.0 | 0 |
| primary-institute | 811 | 79.82% | 811 | 0 | 1 | 0.8 | 1.0 | 25 |
| see-reference | 703 | 69.19% | 1627 | 0 | 10 | 1.6 | 1.0 | 1586 |
| site | 1016 | 100.00% | 21169 | 2 | 118 | 20.84 | 17.0 | 0 |
| site/information-category | 1016 | 100.00% | 46021 | 4 | 250 | 45.3 | 36.0 | 30 |
| site/organization | 1016 | 100.00% | 21262 | 2 | 118 | 20.93 | 17.0 | 171 |
| site/standard-description | 910 | 89.57% | 6129 | 0 | 68 | 6.03 | 4.0 | 7 |
| related-topic | 789 | 77.66% | 2973 | 0 | 37 | 2.93 | 2.0 | 945 |
| mesh-heading/qualifier | 0 | 0.00% | 0 | 0 | 0 | 0 | 0.0 | 0 |

## Full-summary lengths

HTML is decoded once by the XML parser and interpreted with HTMLParser; plain text collapses whitespace. Word counts split on whitespace, not a medical tokenizer. The single absent Spanish summary counts as length zero. Percentiles use linear interpolation at `(n-1)*p`.

| Scope / unit | Min | P10 | P25 | Median | Mean | P75 | P90 | P95 | Max |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| All / plain_words | 0 | 115.0 | 143.0 | 210 | 366.22 | 579.0 | 825.8 | 948.8 | 1393 |
| All / plain_characters | 0 | 700.2 | 881.0 | 1282 | 2250.63 | 3557.0 | 5094.6 | 5832.8 | 8376 |
| All / decoded_html_characters | 0 | 857.0 | 1093.0 | 1591 | 2992.68 | 4832.0 | 6810.4 | 8001.0 | 12986 |
| English / plain_words | 33 | 109.0 | 135.0 | 194 | 346.7 | 554.0 | 787.6 | 903.0 | 1211 |
| English / plain_characters | 185 | 657.0 | 819.0 | 1165 | 2079.76 | 3306.0 | 4710.2 | 5481.8 | 7198 |
| English / decoded_html_characters | 193 | 795.6 | 1026.0 | 1451 | 2792.61 | 4548.0 | 6412.2 | 7406.0 | 11230 |
| Spanish / plain_words | 0 | 122.0 | 154.0 | 222.0 | 385.76 | 612.0 | 858.0 | 991.25 | 1393 |
| Spanish / plain_characters | 0 | 753.0 | 966.0 | 1362.0 | 2421.67 | 3885.75 | 5406.5 | 6264.25 | 8376 |
| Spanish / decoded_html_characters | 0 | 928.5 | 1188.0 | 1693.0 | 3192.95 | 5225.0 | 7229.0 | 8281.75 | 12986 |

## MeSH descriptors and qualifiers

| Scope | Topics with descriptor | Coverage | Descriptor assignments | Distinct descriptor IDs | Topics with qualifier | Qualifier assignments | Distinct qualifier IDs |
| --- | --- | --- | --- | --- | --- | --- | --- |
| All | 1015 | 49.93% | 1358 | 1250 | 28 | 39 | 15 |
| English | 1015 | 99.8% | 1358 | 1250 | 28 | 39 | 15 |
| Spanish | 0 | 0.0% | 0 | 0 | 0 | 0 | 0 |

All qualifier values (English only):

| Qualifier | Assignments |
| --- | --- |
| adverse effects | 1 |
| blood | 1 |
| complications | 1 |
| diet therapy | 1 |
| drug therapy | 6 |
| injuries | 5 |
| physiopathology | 1 |
| prevention & control | 8 |
| psychology | 2 |
| rehabilitation | 2 |
| standards | 2 |
| surgery | 2 |
| therapeutic use | 3 |
| therapy | 3 |
| toxicity | 1 |

## All medical groups and topic counts

Labels are language-specific; 88 labels represent 44 group IDs. Exact IDs, URLs, and topic memberships are retained in JSON. Counts represent distinct topics per label.

| Group label | English topics | Spanish topics |
| --- | --- | --- |
| Alimentos y nutrición | 0 | 57 |
| Aptitud física y ejercicio | 0 | 9 |
| Asuntos de salud sexual | 0 | 22 |
| Asuntos de seguridad | 0 | 22 |
| Asuntos personales de salud | 0 | 12 |
| Asuntos sociales y familiares | 0 | 21 |
| Bienestar y estilo de vida | 0 | 32 |
| Blood, Heart and Circulation | 93 | 0 |
| Bones, Joints and Muscles | 78 | 0 |
| Brain and Nerves | 102 | 0 |
| Cancers | 66 | 0 |
| Cerebro y nervios | 0 | 102 |
| Children and Teenagers | 77 | 0 |
| Cirugía y rehabilitación | 0 | 25 |
| Complementary and Alternative Therapies | 7 | 0 |
| Consumo de sustancias y sus trastornos | 0 | 27 |
| Cánceres | 0 | 66 |
| Desastres | 0 | 14 |
| Diabetes Mellitus | 19 | 0 |
| Diabetes mellitus | 0 | 19 |
| Diagnostic Tests and Screenings | 18 | 0 |
| Digestive System | 79 | 0 |
| Disasters | 14 | 0 |
| Drug Therapy | 21 | 0 |
| Ear, Nose and Throat | 37 | 0 |
| Embarazo y reproducción | 0 | 40 |
| Endocrine System | 51 | 0 |
| Envenenamientos, toxicología y salud ambiental | 0 | 27 |
| Eyes and Vision | 26 | 0 |
| Female Reproductive System | 69 | 0 |
| Fitness and Exercise | 9 | 0 |
| Food and Nutrition | 57 | 0 |
| Genetics/Birth Defects | 60 | 0 |
| Genética y defectos congénitos | 0 | 60 |
| Grupos especiales | 0 | 8 |
| Health System | 28 | 0 |
| Hombres | 0 | 20 |
| Huesos, articulaciones y músculos | 0 | 78 |
| Immune System | 58 | 0 |
| Infecciones | 0 | 126 |
| Infections | 126 | 0 |
| Injuries and Wounds | 56 | 0 |
| Kidneys and Urinary System | 25 | 0 |
| Lesiones y heridas | 0 | 56 |
| Lungs and Breathing | 51 | 0 |
| Male Reproductive System | 25 | 0 |
| Men | 20 | 0 |
| Mental Health and Behavior | 43 | 0 |
| Metabolic Problems | 27 | 0 |
| Mouth and Teeth | 31 | 0 |
| Mujeres | 0 | 60 |
| Niños y adolescentes | 0 | 77 |
| Ojos y visión | 0 | 26 |
| Older Adults | 43 | 0 |
| Oído, nariz y garganta | 0 | 37 |
| Personal Health Issues | 13 | 0 |
| Personas mayores | 0 | 43 |
| Piel, cabello y uñas | 0 | 63 |
| Poisoning, Toxicology, Environmental Health | 27 | 0 |
| Population Groups | 8 | 0 |
| Pregnancy and Reproduction | 40 | 0 |
| Problemas del metabolismo | 0 | 27 |
| Pruebas diagnósticas y exámenes de detección | 0 | 18 |
| Pulmón y vías respiratorias | 0 | 51 |
| Riñones y sistema urinario | 0 | 25 |
| Safety Issues | 22 | 0 |
| Salud mental y conducta | 0 | 43 |
| Salud oral y dental | 0 | 31 |
| Sangre, corazón y circulación | 0 | 93 |
| Sexual Health Issues | 22 | 0 |
| Sistema digestivo | 0 | 79 |
| Sistema endocrino | 0 | 51 |
| Sistema inmunitario | 0 | 58 |
| Sistema reproductor femenino | 0 | 69 |
| Sistema reproductor masculino | 0 | 25 |
| Sistemas de salud | 0 | 27 |
| Skin, Hair and Nails | 63 | 0 |
| Social/Family Issues | 22 | 0 |
| Substance Use and Disorders | 27 | 0 |
| Surgery and Rehabilitation | 25 | 0 |
| Symptoms | 38 | 0 |
| Síntomas | 0 | 38 |
| Terapias complementarias y alternativas | 0 | 7 |
| Transplantation and Donation | 12 | 0 |
| Transplantes y donaciones | 0 | 12 |
| Tratamiento con medicamentos | 0 | 21 |
| Wellness and Lifestyle | 32 | 0 |
| Women | 60 | 0 |

## All information categories and resource counts

Counts are distinct site assignments carrying each label; a multi-category resource contributes to each category. Totals exceed resource counts. `NON-DISPLAY (Patient Handout)` is source metadata and should be excluded from public display pending policy review.

| Category label | English resources | Spanish resources |
| --- | --- | --- |
| Adolescentes | 0 | 435 |
| Adultos | 0 | 11 |
| Adults | 14 | 0 |
| Asuntos específicos | 0 | 4580 |
| Asuntos relacionados | 0 | 2386 |
| Children | 1315 | 0 |
| Clinical Trials | 1577 | 0 |
| Comience aquí | 0 | 2089 |
| Diagnosis and Tests | 1930 | 0 |
| Diagnóstico y exámenes | 0 | 3133 |
| Encyclopedia | 6848 | 0 |
| Especialistas | 0 | 577 |
| Estadísticas e investigación | 0 | 240 |
| Estudios clínicos | 0 | 11 |
| Finance and Policy | 2 | 0 |
| Find an Expert | 2386 | 0 |
| Folletos para el paciente | 0 | 13 |
| Genetics | 1833 | 0 |
| Genética | 0 | 28 |
| Girls | 1 | 0 |
| Health Check Tools | 82 | 0 |
| Herramientas para evaluar la salud | 0 | 23 |
| Hombres | 0 | 65 |
| Images | 319 | 0 |
| Imágenes | 0 | 294 |
| Información de referencia | 0 | 301 |
| Interactivos y videos | 0 | 197 |
| Journal Articles | 1007 | 0 |
| Learn More | 961 | 0 |
| Living With | 626 | 0 |
| Men | 57 | 0 |
| Mujeres | 0 | 222 |
| Más información | 0 | 1325 |
| NIH MedlinePlus Magazine | 233 | 0 |
| NIH Revista MedlinePlus Salud | 0 | 241 |
| NON-DISPLAY (Patient Handout) | 12 | 4244 |
| Niñas | 0 | 18 |
| Niños | 0 | 13 |
| Niños/as | 0 | 1373 |
| Older Adults | 143 | 0 |
| Patient Handouts | 3728 | 0 |
| Personas mayores | 0 | 111 |
| Prevención y factores de riesgo | 0 | 478 |
| Prevention and Risk Factors | 629 | 0 |
| Pruebe su conocimiento | 0 | 83 |
| Reference Desk | 353 | 0 |
| Related Issues | 2189 | 0 |
| Spanish/Español | 0 | 21169 |
| Specifics | 3587 | 0 |
| Start Here | 2263 | 0 |
| Statistics and Research | 639 | 0 |
| Symptoms | 183 | 0 |
| Síntomas | 0 | 280 |
| Teenagers | 419 | 0 |
| Test Your Knowledge | 94 | 0 |
| Tratamientos y terapias | 0 | 1265 |
| Treatments and Therapies | 1398 | 0 |
| Videos and Tutorials | 215 | 0 |
| Viviendo con... | 0 | 816 |
| Women | 269 | 0 |

## All organizations and resource counts

Each resource can credit several organizations. Missing organization does not imply an untrusted resource. Exact labels are preserved without entity merging.

| Organization label | English resources | Spanish resources |
| --- | --- | --- |
| AGS Health in Aging Foundation | 62 | 0 |
| ALS Association | 15 | 0 |
| Academia Americana de Medicina Oral | 0 | 3 |
| Academia Americana de Oftalmología | 0 | 180 |
| Academia Americana de Pediatría | 0 | 460 |
| Academia Estadounidense de Alergias, Asma e Inmunología | 0 | 74 |
| Academia Estadounidense de Cirujanos Ortopédicos | 0 | 20 |
| Academia Estadounidense de Neurología | 0 | 1 |
| Academy of General Dentistry | 3 | 0 |
| Academy of Nutrition and Dietetics | 63 | 0 |
| Acoustic Neuroma Association | 6 | 0 |
| Administración Estadounidense para la Prevención de Incendios | 0 | 4 |
| Administración General de Servicios de los EE.UU | 0 | 2 |
| Administración Nacional de Aeronáutica y del Espacio | 0 | 4 |
| Administración Nacional de Seguridad del Tráfico en las Carreteras | 0 | 7 |
| Administración de Alimentos y Medicamentos | 0 | 245 |
| Administración de Control de Drogas | 0 | 8 |
| Administración de Seguridad y Salud Ocupacional | 0 | 5 |
| Administración de Seguro Social | 0 | 10 |
| Administración de Servicios de la Salud Mental y Abuso de Sustancias | 0 | 53 |
| Administración de Servicios y Recursos de la Salud | 0 | 11 |
| Administración para la Niñez y las Familias | 0 | 1 |
| Administration for Children and Families | 3 | 0 |
| Administration for Community Living | 10 | 0 |
| Administration for Strategic Preparedness and Response | 4 | 0 |
| Adult Congenital Heart Association | 1 | 0 |
| Agencia Federal para el Manejo de Emergencias | 0 | 20 |
| Agencia de Protección Ambiental | 0 | 102 |
| Agencia de la Salud Maternal y Niños | 0 | 1 |
| Agencia para Sustancias Tóxicas y el Registro de Enfermedades | 0 | 7 |
| Agencia para la Investigación y Calidad de la Atención Médica | 0 | 36 |
| Agency for Healthcare Research and Quality | 32 | 0 |
| Agency for Toxic Substances and Disease Registry | 5 | 0 |
| Alianza Familiar de Personas que Cuidan Enfermos | 0 | 18 |
| Alianza Genética | 0 | 3 |
| Alianza Nacional de Enfermedades Mentales | 0 | 6 |
| Alianza Nacional de la Salud Hispana | 0 | 2 |
| Alliance for Radiation Safety in Pediatric Imaging | 3 | 0 |
| Alliance for the Prudent Use of Antibiotics | 3 | 0 |
| Alport Syndrome Foundation | 1 | 0 |
| Alzheimer's Association | 29 | 0 |
| America's Blood Centers | 2 | 0 |
| America's Poison Centers | 1 | 0 |
| American Academy of Allergy, Asthma, and Immunology | 94 | 0 |
| American Academy of Audiology | 5 | 0 |
| American Academy of Child and Adolescent Psychiatry | 54 | 0 |
| American Academy of Dental Sleep Medicine | 2 | 0 |
| American Academy of Dermatology | 125 | 0 |
| American Academy of Facial Plastic and Reconstructive Surgery | 4 | 0 |
| American Academy of Family Physicians | 298 | 0 |
| American Academy of Maxillofacial Prosthetics | 1 | 0 |
| American Academy of Neurology | 5 | 0 |
| American Academy of Ophthalmology | 137 | 0 |
| American Academy of Oral Medicine | 4 | 0 |
| American Academy of Orthopaedic Surgeons | 160 | 0 |
| American Academy of Otolaryngology--Head and Neck Surgery | 75 | 0 |
| American Academy of Pediatric Dentistry | 1 | 0 |
| American Academy of Pediatrics | 321 | 0 |
| American Academy of Periodontology | 10 | 0 |
| American Academy of Physical Medicine and Rehabilitation | 18 | 0 |
| American Academy of Sleep Medicine | 11 | 0 |
| American Association for Marriage and Family Therapy | 2 | 0 |
| American Association for Pediatric Ophthalmology and Strabismus | 105 | 0 |
| American Association for the Advancement of Science | 1 | 0 |
| American Association of Hip and Knee Surgeons | 4 | 0 |
| American Association of Oral and Maxillofacial Surgeons | 17 | 0 |
| American Association of Orthodontists | 2 | 0 |
| American Association of Psychiatric Pharmacists | 18 | 0 |
| American Brain Foundation | 3 | 0 |
| American Brain Tumor Association | 9 | 0 |
| American Burn Association | 1 | 0 |
| American Cancer Society | 645 | 0 |
| American Chiropractic Association | 2 | 0 |
| American Chronic Pain Association | 1 | 0 |
| American Cleft Palate-Craniofacial Association | 6 | 0 |
| American College of Cardiology | 1 | 0 |
| American College of Emergency Physicians | 17 | 0 |
| American College of Foot and Ankle Surgeons | 79 | 0 |
| American College of Gastroenterology | 66 | 0 |
| American College of Nurse-Midwives | 3 | 0 |
| American College of Obstetricians and Gynecologists | 119 | 0 |
| American College of Physicians | 13 | 0 |
| American College of Radiology | 393 | 0 |
| American College of Rheumatology | 85 | 0 |
| American College of Sports Medicine | 2 | 0 |
| American College of Surgeons | 4 | 0 |
| American Diabetes Association | 53 | 0 |
| American Epilepsy Society | 2 | 0 |
| American Foundation for the Blind | 9 | 0 |
| American Geriatrics Society | 6 | 0 |
| American Heart Association | 289 | 0 |
| American Hospital Association | 1 | 0 |
| American Kidney Fund | 12 | 0 |
| American Liver Foundation | 25 | 0 |
| American Lung Association | 109 | 0 |
| American Lyme Disease Foundation | 3 | 0 |
| American Massage Therapy Association | 3 | 0 |
| American Medical Association | 5 | 0 |
| American Migraine Foundation | 5 | 0 |
| American Occupational Therapy Association | 2 | 0 |
| American Optometric Association | 3 | 0 |
| American Orthopaedic Foot and Ankle Society | 47 | 0 |
| American Osteopathic Association | 5 | 0 |
| American Partnership for Eosinophilic Disorders | 5 | 0 |
| American Physical Therapy Association | 7 | 0 |
| American Podiatric Medical Association | 19 | 0 |
| American Porphyria Foundation | 7 | 0 |
| American Psychiatric Association | 17 | 0 |
| American Psychological Association | 36 | 0 |
| American Public Health Association | 2 | 0 |
| American Red Cross | 41 | 0 |
| American Rhinologic Society | 4 | 0 |
| American Sexual Health Association | 1 | 0 |
| American Society for Dermatologic Surgery | 36 | 0 |
| American Society for Gastrointestinal Endoscopy | 19 | 0 |
| American Society for Metabolic and Bariatric Surgery | 7 | 0 |
| American Society for Parenteral & Enteral Nutrition | 3 | 0 |
| American Society for Radiation Oncology | 2 | 0 |
| American Society for Reproductive Medicine | 26 | 0 |
| American Society for Surgery of the Hand | 17 | 0 |
| American Society of Anesthesiologists | 17 | 0 |
| American Society of Colon and Rectal Surgeons | 31 | 0 |
| American Society of Gene & Cell Therapy | 3 | 0 |
| American Society of Health-System Pharmacists | 16 | 0 |
| American Society of Hematology | 15 | 0 |
| American Society of Human Genetics | 1 | 0 |
| American Society of Nuclear Cardiology | 1 | 0 |
| American Society of Ophthalmic Plastic and Reconstructive Surgery | 2 | 0 |
| American Society of Plastic Surgeons | 3 | 0 |
| American Society of Retina Specialists | 3 | 0 |
| American Society of Transplantation | 3 | 0 |
| American Speech-Language-Hearing Association | 1 | 0 |
| American Stroke Association | 37 | 0 |
| American Syringomyelia & Chiari Alliance Project | 2 | 0 |
| American Thoracic Society | 27 | 0 |
| American Thyroid Association | 29 | 0 |
| American Tinnitus Association | 5 | 0 |
| American Urogynecologic Society | 5 | 0 |
| American Veterinary Medical Association | 12 | 0 |
| Amputee Coalition | 16 | 0 |
| Amyloidosis Foundation | 1 | 0 |
| Anxiety and Depression Association of America | 7 | 0 |
| Aplastic Anemia and MDS International Foundation | 8 | 0 |
| Apoyo Internacional para el Posparto | 0 | 2 |
| Arthritis Foundation | 27 | 0 |
| Asociación Americana de Fisioterapia | 0 | 31 |
| Asociación Americana de Medicina Veterinaria | 0 | 7 |
| Asociación Americana de la Diabetes | 0 | 1 |
| Asociación Americana de la Tiroides | 0 | 12 |
| Asociación Americana del Corazón | 0 | 57 |
| Asociación Dental de California | 0 | 8 |
| Asociación Estadounidense de Psicología | 0 | 39 |
| Asociación Estadounidense de Salud Pública | 0 | 1 |
| Asociación Estadounidense del Habla, Lenguaje y Audición | 0 | 8 |
| Asociación Médica Americana | 0 | 1 |
| Asociación Nacional de Protección contra Incendios | 0 | 2 |
| Asociación Norteamericana de Apraxia del Habla Infantil | 0 | 1 |
| Asociación Norteamericana de Gastroenterología, Hepatología y Nutrición Pediátricas | 0 | 17 |
| Asociación Psiquiátrica Americana | 0 | 56 |
| Asociación de Alzheimer | 0 | 16 |
| Asociación de Hidrocefalia | 0 | 1 |
| Asociación de la Distrofia Muscular | 0 | 6 |
| Asociación para el Síndrome de Tourette | 0 | 5 |
| Association for Frontotemporal Degeneration | 1 | 0 |
| Association for the Advancement of Blood & Biotherapies | 3 | 0 |
| Association of American Medical Colleges | 1 | 0 |
| Association of Diabetes Care and Education Specialists | 9 | 0 |
| Asthma and Allergy Foundation of America | 6 | 0 |
| Ataxia-Telangiectasia Children's Project | 1 | 0 |
| Autism Society of America Foundation | 1 | 0 |
| Autism Speaks | 1 | 0 |
| Autoimmune Association | 4 | 0 |
| Batten Disease Support, Research, and Advocacy Association | 2 | 0 |
| Biblioteca Nacional de Medicina | 0 | 1498 |
| Blood & Marrow Transplant Information Network | 2 | 0 |
| Blood Cancer United | 46 | 47 |
| Bone Health and Osteoporosis Foundation | 6 | 0 |
| Boston Children's Hospital | 51 | 0 |
| Brain Aneurysm Foundation | 5 | 0 |
| Brainline | 1 | 0 |
| Breakthrough T1D | 3 | 1 |
| BrightFocus Foundation | 7 | 0 |
| Bureau of Labor Statistics | 46 | 0 |
| Bureau of the Census | 2 | 0 |
| Burn Institute | 1 | 0 |
| C&S Patient Education Foundation | 5 | 0 |
| Campaign for Tobacco-Free Kids | 2 | 0 |
| Campaña Nacional de Safe Kids | 0 | 1 |
| CancerCare | 0 | 3 |
| Carcinoid Cancer Foundation | 1 | 0 |
| Caregiver Action Network | 2 | 0 |
| Celiac Disease Foundation | 11 | 0 |
| Center for Construction Research and Training | 1 | 0 |
| Center for Hearing and Communication | 2 | 0 |
| Center for Nutrition Policy and Promotion | 4 | 0 |
| Center for Parent Information and Resources | 6 | 0 |
| Center for Women Veterans | 1 | 0 |
| Center to Advance Palliative Care | 4 | 0 |
| Centers for Disease Control and Prevention | 1604 | 0 |
| Centers for Medicare & Medicaid Services | 61 | 0 |
| Centro Clínico de los NIH | 0 | 1 |
| Centro Coordinador Nacional de Información sobre Enfermedades de los Riñones y de las Vías Urinarias | 0 | 7 |
| Centro Nacional de Estadísticas de la Salud | 0 | 7 |
| Centro Nacional de Información de Pesticidas | 0 | 9 |
| Centro Nacional de Recursos de Salud Oral Materna e Infantil | 0 | 1 |
| Centro Nacional de Salud Complementaria e Integral | 0 | 27 |
| Centro Nacional para el TEPT | 0 | 19 |
| Centro de Ciencias de la Salud, Universidad de Utah | 0 | 2 |
| Centro de Información sobre Enfermedades Genéticas y Raras | 0 | 149 |
| Centro de Investigación y Capacitación en Construcción | 0 | 3 |
| Centro de Recursos Informativos para Padres | 0 | 9 |
| Centro de Recursos para la Parálisis de la Fundación Christopher y Dana Reeve | 0 | 2 |
| Centros de Servicios de Medicare y Medicaid | 0 | 46 |
| Centros para el Control y la Prevención de Enfermedades | 0 | 628 |
| Childhood Apraxia of Speech Association of North America | 1 | 0 |
| Children and Adults with Attention-Deficit/Hyperactivity Disorder | 4 | 0 |
| Children's Bureau | 8 | 0 |
| Children's Cardiomyopathy Foundation | 1 | 0 |
| Children's Craniofacial Association | 8 | 0 |
| Children's Hemiplegia and Stroke Association | 3 | 0 |
| Children's Hospital St. Louis | 1 | 0 |
| Children's Hospital and Health System, Inc. | 3 | 0 |
| Children's Hospital of Philadelphia | 1 | 0 |
| Children's Hospitals and Clinics of Minnesota | 3 | 0 |
| Children's National Hospital | 2 | 0 |
| Children's Oncology Group | 1 | 0 |
| Children's Tumor Foundation | 6 | 0 |
| Christopher and Dana Reeve Foundation National Paralysis Resource Center | 17 | 0 |
| Coalición de Acción para la Inmunización | 0 | 30 |
| Colegio Americano de Cirujanos del Pie y Tobillo | 0 | 93 |
| Colegio Americano de Gastroenterología | 0 | 16 |
| Colegio Americano de Médicos de Emergencia | 0 | 4 |
| Colegio Americano de Obstetras y Ginecólogos | 0 | 1 |
| Colegio Americano de Radiología | 0 | 362 |
| Colegio Estadounidense de Reumatología | 0 | 61 |
| Colegio estadounidense de cirujanos | 0 | 2 |
| College of American Pathologists | 5 | 0 |
| Colorectal Cancer Coalition | 1 | 0 |
| Comisión  de Seguridad de los Productos para el Consumidor | 0 | 10 |
| Comisión Federal de Comercio | 0 | 19 |
| Comisión Federal de Comunicaciones | 0 | 10 |
| Commission for the Accreditation of Birth Centers | 1 | 0 |
| Compassionate Friends | 2 | 0 |
| Complex Regional Pain Syndrome Association | 1 | 0 |
| Consumer Financial Protection Bureau | 1 | 0 |
| Consumer Product Safety Commission | 14 | 0 |
| Cooley's Anemia Foundation | 2 | 0 |
| Crohn's & Colitis Foundation of America | 44 | 0 |
| Cruz Roja Americana | 0 | 9 |
| Cure SMA | 10 | 0 |
| Cystic Fibrosis Foundation | 22 | 0 |
| Defense Health Agency | 8 | 0 |
| Departamento de Agricultura de Estados Unidos | 0 | 2 |
| Departamento de Agricultura, Servicio de Inocuidad e Inspección de los Alimentos | 0 | 3 |
| Departamento de Asuntos de los Veteranos | 0 | 4 |
| Departamento de Justicia de Estados Unidos | 0 | 1 |
| Departamento de Justicia de Estados Unidos, Oficina para Víctimas del Crimen | 0 | 1 |
| Departamento de Salud del Estado de Nueva York | 0 | 14 |
| Departamento de Salud del Estado de Washington | 0 | 1 |
| Departamento de Salud y Servicios Humanos | 0 | 45 |
| Departamento de Salud y Servicios Humanos, Oficina de Salud de las Minorías | 0 | 1 |
| Departamento de Salud y Servicios para la Familia de Wisconsin | 0 | 1 |
| Departamento de Seguridad Nacional | 0 | 33 |
| Departamento de Trabajo | 0 | 1 |
| Department of Agriculture | 10 | 0 |
| Department of Agriculture, Food Safety and Inspection Service | 28 | 0 |
| Department of Defense | 2 | 0 |
| Department of Education | 3 | 0 |
| Department of Health and Human Services | 74 | 0 |
| Department of Health and Human Services, Office for Civil Rights | 6 | 0 |
| Department of Health and Human Services, Office of Infectious Disease and HIV/AIDS Policy | 3 | 0 |
| Department of Health and Human Services, Office of Minority Health | 1 | 0 |
| Department of Health and Human Services, Office on Women's Health | 172 | 0 |
| Department of Homeland Security | 30 | 0 |
| Department of Housing and Urban Development | 1 | 0 |
| Department of Justice | 3 | 0 |
| Department of Justice, Office on Violence Against Women | 3 | 0 |
| Department of Labor | 1 | 0 |
| Department of State, Bureau of Consular Affairs | 1 | 0 |
| Department of Veterans Affairs | 72 | 0 |
| Department of the Interior | 3 | 0 |
| Departmento de Salud de Minnesota | 0 | 2 |
| Depression and Bipolar Support Alliance | 11 | 0 |
| Divers Alert Network | 3 | 0 |
| Done Vida | 0 | 1 |
| Drug Enforcement Administration | 32 | 0 |
| Dystonia Medical Research Foundation | 17 | 0 |
| Enciclopedia Médica | 0 | 7397 |
| Endocrine Society | 61 | 0 |
| Environmental Protection Agency | 158 | 0 |
| Epilepsy Foundation | 1 | 0 |
| Eunice Kennedy Shriver National Institute of Child Health and Human Development | 314 | 0 |
| Extensión Cooperativa de Virginia | 0 | 1 |
| FacioScapuloHumeral Muscular Dystrophy Society | 1 | 0 |
| Family Caregiver Alliance | 12 | 0 |
| Federación Mundial de Hemofilia | 0 | 10 |
| Federal Bureau of Investigation | 2 | 0 |
| Federal Communications Commission | 9 | 0 |
| Federal Emergency Management Agency | 19 | 0 |
| Federal Interagency Forum on Child and Family Statistics | 4 | 0 |
| Federal Trade Commission | 9 | 0 |
| Federation of State Medical Boards | 1 | 0 |
| Fisher Center for Alzheimer's Research Foundation | 1 | 0 |
| Fondo Estadounidense sobre los Riñones | 0 | 8 |
| Fondo de Investigación de Anemia de Fanconi, Inc. | 0 | 1 |
| Food Allergy Research & Education | 19 | 0 |
| Food and Drug Administration | 400 | 0 |
| Food and Nutrition Administration | 6 | 0 |
| Food and Nutrition Information Center | 21 | 0 |
| Foundation Fighting Blindness | 6 | 0 |
| Foundation for Inherited Arrhythmias | 1 | 0 |
| Foundation for PSP/CBD and Related Brain Diseases | 1 | 0 |
| Foundation for Peripheral Neuropathy | 4 | 0 |
| Friedreich's Ataxia Research Alliance | 5 | 0 |
| Fundación Americana de la Tartamudez | 0 | 7 |
| Fundación Americana del Hígado | 0 | 12 |
| Fundación Bright Focus | 0 | 5 |
| Fundación Estadounidense contra el Lupus | 0 | 16 |
| Fundación Hepatitis B | 0 | 2 |
| Fundación Internacional del Síndrome de Guillain-Barré | 0 | 1 |
| Fundación Internacional para los Trastornos Gastrointestinales | 0 | 10 |
| Fundación Mayo para la Educación y la Investigación Médica | 0 | 1622 |
| Fundación Nacional de la Cefalea | 0 | 27 |
| Fundación Nacional del Páncreas | 0 | 1 |
| Fundación Nacional del Riñón | 0 | 16 |
| Fundación Nemours | 0 | 1960 |
| Fundación Susan G. Komen Contra el Cáncer del Seno | 0 | 2 |
| Fundación contra el Cáncer de Piel | 0 | 2 |
| Fundación de Niños con Defectos de Nacimiento March of Dimes | 0 | 192 |
| Fundación de la Artritis | 0 | 2 |
| Fundación del Síndrome de Barth | 0 | 2 |
| Fundación para el Parkinson | 0 | 20 |
| Fundación para el Síndrome de Marfan | 0 | 7 |
| Fundación para la Investigación del Glaucoma | 0 | 6 |
| Fundación para la Osteogénesis Imperfecta | 0 | 1 |
| GBS/CIDP Foundation International | 2 | 0 |
| GO2 Foundation for Lung Cancer | 1 | 0 |
| General Services Administration | 3 | 0 |
| Genetic Science Learning Center | 2 | 0 |
| Genetic and Rare Diseases Information Center | 67 | 0 |
| Glaucoma Research Foundation | 10 | 0 |
| Gluten Intolerance Group | 17 | 0 |
| Greater Than AIDS | 1 | 0 |
| Grupo de Intolerancia al Gluten | 0 | 4 |
| HIV.gov | 33 | 0 |
| Harvard Medical School | 55 | 0 |
| Harvard School of Public Health | 20 | 0 |
| Hastings Center for Bioethics | 2 | 0 |
| Health Canada | 1 | 0 |
| Health Physics Society | 6 | 0 |
| Health Resources and Services Administration | 55 | 0 |
| Heart Rhythm Society | 15 | 0 |
| Hepatitis B Foundation | 4 | 0 |
| Hill Foundation for Families Living with Disabilities | 4 | 0 |
| Hospice Foundation of America | 2 | 0 |
| Hospital de Investigación sobre Niños St. Jude | 0 | 6 |
| Hospital de Niños de Boston | 0 | 74 |
| Hospitales y Clínicas de Niños de Minnesota | 0 | 1 |
| Huntington's Disease Society of America | 5 | 0 |
| Hydrocephalus Association | 7 | 0 |
| Immune Deficiency Foundation | 2 | 0 |
| Immunization Action Coalition | 22 | 0 |
| Indian Health Service | 1 | 0 |
| Instituto Nacional de Alergias y Enfermedades Infecciosas | 0 | 2 |
| Instituto Nacional de Artritis y Enfermedades Musculoesqueléticas y de la Piel | 0 | 112 |
| Instituto Nacional de Imágenes Biomédicas y Bioingeniería | 0 | 8 |
| Instituto Nacional de Investigación Dental y Craneofacial | 0 | 64 |
| Instituto Nacional de Investigación del Genoma Humano | 0 | 30 |
| Instituto Nacional de Salud Infantil y Desarrollo Humano | 0 | 336 |
| Instituto Nacional de Trastornos Neurológicos y Accidentes Cerebrovasculares | 0 | 72 |
| Instituto Nacional de la Diabetes y las Enfermedades Digestivas y Renales | 0 | 381 |
| Instituto Nacional de la Salud Mental | 0 | 101 |
| Instituto Nacional de la Seguridad y la Salud Ocupacional | 0 | 40 |
| Instituto Nacional de la Sordera y otros Trastornos de la Comunicación | 0 | 62 |
| Instituto Nacional de las Ciencias de Salud Ambiental | 0 | 5 |
| Instituto Nacional del Corazón, los Pulmones y la Sangre | 0 | 261 |
| Instituto Nacional del Cáncer | 0 | 1037 |
| Instituto Nacional del Ojo | 0 | 91 |
| Instituto Nacional sobre el Abuso de Alcohol y Alcoholismo | 0 | 35 |
| Instituto Nacional sobre el Abuso de Drogas | 0 | 78 |
| Instituto Nacional sobre el Envejecimiento | 0 | 142 |
| Instituto del Corazón de Texas | 0 | 176 |
| Institutos Nacionales de la Salud | 0 | 370 |
| International Essential Tremor Foundation | 3 | 0 |
| International Foundation for Gastrointestinal Disorders | 52 | 0 |
| International Myeloma Foundation | 2 | 0 |
| International OCD Foundation | 4 | 0 |
| International Osteoporosis Foundation | 1 | 0 |
| International Rett Syndrome Foundation | 2 | 0 |
| International Society for Magnetic Resonance in Medicine | 1 | 0 |
| International Society for Stem Cell Research | 5 | 0 |
| Intracranial Hypertension Research Foundation | 2 | 0 |
| Jeffrey Modell Foundation | 2 | 0 |
| Johns Hopkins Bloomberg School of Public Health | 3 | 0 |
| Johns Hopkins Medicine | 53 | 0 |
| Johns Hopkins University, Department of Pathology | 9 | 0 |
| Johns Hopkins Vasculitis Center | 19 | 0 |
| Joint Commission | 7 | 0 |
| Joslin Diabetes Center | 1 | 0 |
| KFF | 5 | 0 |
| Kaiser Permanente | 1 | 0 |
| Kennedy's Disease Association | 1 | 0 |
| Lewy Body Dementia Association | 8 | 0 |
| Life Options Rehabilitation Program | 4 | 0 |
| Living Beyond Breast Cancer | 4 | 0 |
| Lupus Foundation of America | 18 | 0 |
| Lymphatic Education & Research Network | 2 | 0 |
| Lymphoma Research Foundation | 13 | 0 |
| March of Dimes Foundation | 103 | 0 |
| Marfan Foundation | 11 | 0 |
| Massachusetts Institute of Technology | 1 | 0 |
| Maternal and Child Health Bureau | 1 | 0 |
| Mayo Foundation for Medical Education and Research | 1563 | 0 |
| Meals on Wheels America | 2 | 0 |
| Medical Encyclopedia | 7190 | 0 |
| Memorial Sloan Kettering Cancer Center | 4 | 0 |
| Menopause Society | 2 | 0 |
| Mental Health America | 23 | 0 |
| Merck & Co., Inc. | 198 | 301 |
| Minnesota Department of Health | 1 | 0 |
| Multiple Myeloma Research Foundation | 1 | 0 |
| Muscular Dystrophy Association | 34 | 0 |
| Myasthenia Gravis Foundation of America | 3 | 0 |
| Más que SIDA | 0 | 1 |
| NAMI | 21 | 0 |
| NMDP | 19 | 16 |
| National Academies | 1 | 0 |
| National Adult Day Services Association | 1 | 0 |
| National Alliance for Care at Home | 6 | 0 |
| National Alopecia Areata Foundation | 1 | 0 |
| National Association of County and City Health Officials | 1 | 0 |
| National Association of Free and Charitable Clinics | 1 | 0 |
| National Blood Clot Alliance | 1 | 0 |
| National Brain Tumor Society | 2 | 0 |
| National Cancer Institute | 1003 | 0 |
| National Cancer Institute, Tobacco Control Research Branch | 16 | 0 |
| National Center for Biotechnology Information | 6 | 0 |
| National Center for Complementary and Integrative Health | 256 | 0 |
| National Center for Health Statistics | 94 | 0 |
| National Center for Missing and Exploited Children | 1 | 0 |
| National Center for PTSD | 28 | 0 |
| National Center on Sleep Disorders Research | 3 | 0 |
| National Certification Commission for Acupuncture and Oriental Medicine | 4 | 0 |
| National Child Traumatic Stress Network | 2 | 0 |
| National Consumer Voice for Quality Long-Term Care | 4 | 0 |
| National Council on Aging | 10 | 0 |
| National Crime Prevention Council | 1 | 0 |
| National Down Syndrome Society | 13 | 0 |
| National Eating Disorders Association | 6 | 0 |
| National Eye Institute | 153 | 0 |
| National Farm Medicine Center | 2 | 0 |
| National Fire Protection Association | 3 | 0 |
| National Foundation for Infectious Diseases | 10 | 0 |
| National Fragile X Foundation | 5 | 0 |
| National Headache Foundation | 3 | 0 |
| National Heart, Lung, and Blood Institute | 429 | 0 |
| National Highway Traffic Safety Administration | 9 | 0 |
| National Human Genome Research Institute | 82 | 0 |
| National Institute for Occupational Safety and Health | 59 | 0 |
| National Institute of Allergy and Infectious Diseases | 165 | 0 |
| National Institute of Arthritis and Musculoskeletal and Skin Diseases | 168 | 0 |
| National Institute of Biomedical Imaging and Bioengineering | 7 | 0 |
| National Institute of Dental and Craniofacial Research | 76 | 0 |
| National Institute of Diabetes and Digestive and Kidney Diseases | 595 | 0 |
| National Institute of Environmental Health Sciences | 68 | 0 |
| National Institute of General Medical Sciences | 18 | 0 |
| National Institute of Mental Health | 156 | 0 |
| National Institute of Neurological Disorders and Stroke | 340 | 0 |
| National Institute of Nursing Research | 6 | 0 |
| National Institute on Aging | 204 | 0 |
| National Institute on Alcohol Abuse and Alcoholism | 59 | 0 |
| National Institute on Deafness and Other Communication Disorders | 107 | 0 |
| National Institute on Drug Abuse | 86 | 0 |
| National Institute on Minority Health and Health Disparities | 3 | 0 |
| National Institutes of Health | 1817 | 0 |
| National Institutes of Health, Clinical Center | 5 | 0 |
| National Institutes of Health, Office of AIDS Research | 85 | 0 |
| National Institutes of Health, Office of Dietary Supplements | 59 | 0 |
| National Institutes of Health, Office of Research Services, Division of Occupational Health and Safety. | 3 | 0 |
| National Institutes of Health, Office of Research on Women's Health | 1 | 0 |
| National Interagency Fire Center | 1 | 0 |
| National Jewish Health | 28 | 0 |
| National Kidney Disease Education Program | 7 | 0 |
| National Kidney Foundation | 67 | 0 |
| National Kidney and Urologic Diseases Information Clearinghouse | 5 | 0 |
| National Library of Medicine | 3250 | 0 |
| National Lymphedema Network | 1 | 0 |
| National MPS Society | 1 | 0 |
| National Maternal and Child Oral Health Resource Center | 1 | 0 |
| National Multiple Sclerosis Society | 29 | 0 |
| National Oceanic and Atmospheric Administration | 7 | 0 |
| National Organization for Rare Disorders | 7 | 0 |
| National Pancreas Foundation | 8 | 0 |
| National Psoriasis Foundation | 1 | 0 |
| National Resource Center on Psychiatric Advance Directives | 2 | 0 |
| National Rosacea Society | 8 | 0 |
| National Runaway Safeline | 3 | 0 |
| National Safety Council | 3 | 0 |
| National Scleroderma Foundation | 8 | 0 |
| National Society of Genetic Counselors | 7 | 0 |
| National Spasmodic Torticollis Association | 2 | 0 |
| National Urea Cycle Disorders Foundation | 2 | 0 |
| National Vulvodynia Association | 5 | 0 |
| National Weather Service | 2 | 0 |
| Nemours Foundation | 1785 | 0 |
| Nevus Outreach, Inc. | 5 | 0 |
| Niños y Adultos con Trastorno de Deficiencia de Atención/Hiperactividad | 0 | 1 |
| Nobel Foundation | 1 | 0 |
| North American Society for Pediatric Gastroenterology, Hepatology and Nutrition | 16 | 0 |
| North American Spine Society | 32 | 0 |
| Obesity Medicine Association | 5 | 0 |
| Occupational Safety and Health Administration | 51 | 0 |
| Office of Disability Employment Policy | 7 | 0 |
| Office of Disease Prevention and Health Promotion | 35 | 0 |
| Office of the National Coordinator for Health Information Technology | 5 | 0 |
| Oficina de Investigación del SIDA de los Institutos Nacionales de la Salud | 0 | 92 |
| Oficina de Prevención de Enfermedades y Promoción de la Salud | 0 | 28 |
| Oficina de Suplementos Dietéticos de los NIH | 0 | 37 |
| Oficina para la Salud de la Mujer en el Departamento de Salud y Servicios Humanos de los EE. UU | 0 | 1 |
| Ohio State University Medical Center | 2 | 0 |
| Oklahoma State University, Department of Environmental Health and Safety | 1 | 0 |
| Oley Foundation | 1 | 0 |
| Oral Cancer Foundation | 1 | 0 |
| Organización Mundial de la  Salud | 0 | 45 |
| Organización Nacional Judía de la Salud | 0 | 12 |
| Organización Nacional de Enfermedades Raras | 0 | 3 |
| Organización Panamericana de la Salud | 0 | 6 |
| Organización de Especialistas de Información Teratológica | 0 | 99 |
| Organization of Teratology Information Specialists | 84 | 0 |
| Osteogenesis Imperfecta Foundation | 12 | 0 |
| Oxalosis and Hyperoxaluria Foundation | 3 | 0 |
| PKD Foundation | 1 | 0 |
| Pan American Health Organization | 2 | 0 |
| Pancreatic Cancer Action Network | 4 | 0 |
| Parent Project for Muscular Dystrophy Research | 5 | 0 |
| Parkinson's Foundation | 33 | 0 |
| Partnership for Healthy Mouths, Healthy Lives | 1 | 0 |
| Partnership to End Addiction | 14 | 0 |
| Pennsylvania State University, College of Agricultural Sciences | 1 | 0 |
| Phoenix Society for Burn Survivors | 1 | 0 |
| PleasePrEPMe | 1 | 0 |
| Post-Polio Health International including International Ventilator Users Network | 3 | 0 |
| Postpartum Support International | 8 | 0 |
| Prader-Willi Syndrome Association | 1 | 0 |
| Prevent Blindness | 11 | 0 |
| Prevent Cancer Foundation | 1 | 0 |
| Previniendo la Ceguera | 0 | 1 |
| Progeria Research Foundation, Inc. | 1 | 0 |
| Programa Nacional de Educación sobre Enfermedad Renal | 0 | 9 |
| Programa de Rehabilitación de Life Options | 0 | 7 |
| Prostate Cancer Foundation | 4 | 0 |
| Pulmonary Fibrosis Foundation | 4 | 0 |
| Radiological Society of North America | 393 | 0 |
| Red Nacional para el Estrés Traumático Infantil | 0 | 9 |
| Researching COVID to Enhance Recovery | 2 | 0 |
| Restless Legs Syndrome Foundation | 2 | 0 |
| Rural Health Information Hub | 15 | 0 |
| Rural Information Center | 1 | 0 |
| SAG Fundación para la Salud en Envejecimiento | 0 | 3 |
| Safe Kids Worldwide | 6 | 0 |
| Sarcoma Foundation of America | 1 | 0 |
| Scarring Alopecia Foundation | 1 | 0 |
| Scientific Registry of Transplant Recipients | 7 | 0 |
| Scleroderma Research Foundation | 2 | 0 |
| Scoliosis Research Society | 7 | 0 |
| Servicio Geológico de los Estados Unidos | 0 | 9 |
| Sickle Cell Disease Association of America | 1 | 0 |
| Sjogren's Foundation | 10 | 0 |
| Skin Cancer Foundation | 3 | 0 |
| Social Security Administration | 9 | 0 |
| Sociedad  Estadounidense de Cirujanos  Plásticos | 0 | 5 |
| Sociedad Americana Contra el Cáncer | 0 | 493 |
| Sociedad Americana para Endoscopia Gastrointestinal | 0 | 20 |
| Sociedad Estadounidense de Cirujanos de Colon y Recto | 0 | 4 |
| Sociedad Estadounidense de Farmacéuticos del Sistema de Salud | 0 | 5 |
| Sociedad Estadounidense de la Columna Vertebral | 0 | 5 |
| Sociedad Nacional de Consejeros Genéticos | 0 | 7 |
| Sociedad Nacional de Esclerosis Múltiple | 0 | 2 |
| Sociedad Torácica Estadounidense | 0 | 12 |
| Sociedad de Radiología de Norteamérica | 0 | 466 |
| Society for Neuroscience | 3 | 0 |
| Society for Vascular Surgery | 1 | 0 |
| Society of American Gastrointestinal and Endoscopic Surgeons | 18 | 0 |
| Society of Interventional Radiology | 8 | 0 |
| Society of NeuroInterventional Surgery | 2 | 0 |
| Society of Thoracic Surgeons | 3 | 0 |
| Spina Bifida Association of America | 6 | 0 |
| Spinal Muscular Atrophy Foundation | 1 | 0 |
| Spondylitis Association of America | 8 | 0 |
| St. Jude Children's Research Hospital | 1 | 0 |
| Stuttering Foundation of America | 4 | 0 |
| Substance Abuse and Mental Health Services Administration | 78 | 0 |
| Surgeon General | 3 | 0 |
| Susan G. Komen for the Cure | 12 | 0 |
| TSC Alliance | 4 | 0 |
| Texas Children's Hospital | 1 | 0 |
| Texas Heart Institute | 66 | 0 |
| Tourette Association of America | 7 | 0 |
| Transportation Security Administration | 2 | 0 |
| Turner Syndrome Society of the United States | 2 | 0 |
| U.S. Geological Survey | 9 | 0 |
| U.S. Preventive Services Task Force | 3 | 0 |
| USAging | 12 | 0 |
| United Leukodystrophy Foundation | 1 | 0 |
| United Mitochondrial Disease Foundation | 3 | 0 |
| United Network for Organ Sharing | 6 | 0 |
| United States Fire Administration | 7 | 0 |
| Universidad de Washington, Facultad de Medicina | 0 | 3 |
| University of Alabama at Birmingham | 2 | 0 |
| University of Maryland, School of Medicine | 1 | 0 |
| University of Massachusetts at Lowell, Toxics Use Reduction Institute | 2 | 0 |
| University of Michigan Depression Center | 3 | 0 |
| University of Michigan Health System | 1 | 0 |
| University of Minnesota Extension | 1 | 0 |
| University of Oklahoma Health Sciences Center | 1 | 0 |
| University of Pittsburgh | 1 | 0 |
| University of South Florida | 1 | 0 |
| University of Texas M.D. Anderson Cancer Center | 1 | 0 |
| University of Utah, Health Sciences Center | 2 | 0 |
| University of Washington | 1 | 0 |
| University of Washington, Department of Medicine | 1 | 0 |
| University of Washington, Department of Rehabilitation Medicine | 5 | 0 |
| University of Washington, School of Medicine | 2 | 0 |
| VHL Alliance | 2 | 0 |
| Vasculitis Foundation | 5 | 0 |
| Vestibular Disorders Association | 25 | 0 |
| Veterans Health Administration | 3 | 0 |
| Virginia Cooperative Extension | 1 | 0 |
| Virtual Pediatric Hospital | 1 | 0 |
| VisualDX | 281 | 0 |
| Well Project | 1 | 1 |
| Wilson Disease Association | 6 | 0 |
| World Federation of Hemophilia | 5 | 0 |
| World Health Organization | 46 | 0 |
| Zero to Three | 3 | 0 |

## All resource hostnames

| Hostname | All assignments | English assignments | Spanish assignments |
| --- | --- | --- | --- |
| 988lifeline.org | 6 | 3 | 3 |
| aadsm.org | 2 | 2 | 0 |
| aafa.org | 6 | 6 | 0 |
| aaoinfo.org | 2 | 2 | 0 |
| aapos.org | 10 | 10 | 0 |
| aapp.org | 18 | 18 | 0 |
| aasm.org | 3 | 3 | 0 |
| aboutconstipation.org | 4 | 4 | 0 |
| aboutgimotility.org | 10 | 10 | 0 |
| aboutibs.org | 14 | 14 | 0 |
| aboutincontinence.org | 4 | 4 | 0 |
| aboutkidsgi.org | 2 | 2 | 0 |
| acf.gov | 3 | 3 | 0 |
| acl.gov | 10 | 10 | 0 |
| acpacares.org | 6 | 6 | 0 |
| acsm.org | 2 | 2 | 0 |
| adaa.org | 3 | 3 | 0 |
| admissions.usf.edu | 1 | 1 | 0 |
| alcoholtreatment.niaaa.nih.gov | 2 | 2 | 0 |
| aldf.com | 3 | 3 | 0 |
| allergist.aaaai.org | 14 | 14 | 0 |
| allianceforcareathome.org | 6 | 6 | 0 |
| americanmigrainefoundation.org | 5 | 5 | 0 |
| americasblood.org | 2 | 2 | 0 |
| amputee-coalition.org | 16 | 16 | 0 |
| ams.midwife.org | 2 | 2 | 0 |
| anausa.org | 6 | 6 | 0 |
| apfed.org | 5 | 5 | 0 |
| apps.cff.org | 1 | 1 | 0 |
| apua.org | 3 | 3 | 0 |
| asap.org | 2 | 2 | 0 |
| askjan.org | 6 | 6 | 0 |
| asmbs.org | 6 | 6 | 0 |
| asmbs.users.membersuite.com | 1 | 1 | 0 |
| assets.ctfassets.net | 2 | 2 | 0 |
| ata.org | 1 | 1 | 0 |
| atcp.org | 1 | 1 | 0 |
| autismsociety.org | 2 | 2 | 0 |
| autoimmune.org | 4 | 4 | 0 |
| barthsyndrome.org | 2 | 0 | 2 |
| bdsrafoundation.org | 2 | 2 | 0 |
| birthcenteraccreditation.org | 1 | 1 | 0 |
| bloodcancerunited.org | 56 | 46 | 10 |
| bloodstemcell.hrsa.gov | 7 | 7 | 0 |
| bmtinfonet.org | 2 | 2 | 0 |
| braillebug.org | 1 | 1 | 0 |
| braintumor.org | 2 | 2 | 0 |
| burninstitute.org | 1 | 1 | 0 |
| canceratlas.cancer.org | 2 | 2 | 0 |
| cancercontrol.cancer.gov | 2 | 2 | 0 |
| cap.mil | 1 | 1 | 0 |
| ccakids.org | 2 | 2 | 0 |
| ccr.cancer.gov | 1 | 1 | 0 |
| ccrisktool.cancer.gov | 1 | 1 | 0 |
| cde.ucr.cjis.gov | 1 | 1 | 0 |
| cdn.prod.website-files.com | 6 | 6 | 0 |
| cdn.sanity.io | 3 | 3 | 0 |
| cdn.tobaccofreekids.org | 2 | 2 | 0 |
| cdn.ymaws.com | 1 | 1 | 0 |
| ceal.nih.gov | 1 | 0 | 1 |
| celiac.org | 11 | 11 | 0 |
| centerforhealthsecurity.org | 1 | 1 | 0 |
| chadd.org | 5 | 4 | 1 |
| chasa.org | 3 | 3 | 0 |
| childrensoncologygroup.org | 1 | 1 | 0 |
| childrenswi.org | 3 | 3 | 0 |
| clinicalinfo.hiv.gov | 35 | 17 | 18 |
| clinicaltrials.gov | 1571 | 1571 | 0 |
| composer.publications.aap.org | 1 | 1 | 0 |
| consumer.ftc.gov | 8 | 8 | 0 |
| consumidor.ftc.gov | 19 | 0 | 19 |
| content.nfpa.org | 1 | 1 | 0 |
| cpr.heart.org | 4 | 4 | 0 |
| crashstats.nhtsa.dot.gov | 3 | 3 | 0 |
| crps.org | 1 | 1 | 0 |
| curesarcoma.org | 1 | 1 | 0 |
| cwlibrary.childwelfare.gov | 4 | 4 | 0 |
| dan.org | 3 | 3 | 0 |
| data.cms.gov | 1 | 1 | 0 |
| department.va.gov | 4 | 4 | 0 |
| depressioncenter.org | 3 | 3 | 0 |
| depts.washington.edu | 3 | 0 | 3 |
| diabetes.org | 46 | 46 | 0 |
| diabetesfoodhub.org | 2 | 2 | 0 |
| digitalassets.jointcommission.org | 3 | 3 | 0 |
| digitalpubs.ext.vt.edu | 1 | 1 | 0 |
| doctorsthatdo.osteopathic.org | 3 | 3 | 0 |
| document.airnow.gov | 2 | 2 | 0 |
| doh.wa.gov | 1 | 0 | 1 |
| doihaveprediabetes.org | 4 | 2 | 2 |
| donatelife.net | 1 | 0 | 1 |
| downloads.aap.org | 4 | 0 | 4 |
| downsyndrome.nih.gov | 1 | 1 | 0 |
| drugfree.org | 14 | 14 | 0 |
| ds-connect.org | 1 | 1 | 0 |
| dsid.od.nih.gov | 1 | 1 | 0 |
| dsld.od.nih.gov | 4 | 4 | 0 |
| dystonia-foundation.org | 17 | 17 | 0 |
| ebusiness.avma.org | 6 | 0 | 6 |
| educationalgames.nobelprize.org | 1 | 1 | 0 |
| effectivehealthcare.ahrq.gov | 3 | 0 | 3 |
| ehs.okstate.edu | 1 | 1 | 0 |
| elearning.wfh.org | 8 | 4 | 4 |
| engage.aapos.org | 1 | 1 | 0 |
| es.medicare.gov | 35 | 0 | 35 |
| es.postpartum.net | 2 | 0 | 2 |
| espanol.airnow.gov | 1 | 0 | 1 |
| espanol.arthritis.org | 2 | 0 | 2 |
| espanol.epa.gov | 82 | 0 | 82 |
| espanol.nichd.nih.gov | 329 | 0 | 329 |
| espanol.stopbullying.gov | 1 | 0 | 1 |
| essentialtremor.org | 2 | 2 | 0 |
| extension.psu.edu | 1 | 1 | 0 |
| extension.umn.edu | 1 | 1 | 0 |
| facingdisability.com | 4 | 4 | 0 |
| familydoctor.org | 297 | 297 | 0 |
| fascrs.org | 35 | 31 | 4 |
| find-a-derm.aad.org | 31 | 31 | 0 |
| find-doctor.ama-assn.org | 3 | 3 | 0 |
| findadoctor.aahks.net | 4 | 4 | 0 |
| findageneticcounselor.nsgc.org | 6 | 6 | 0 |
| findahealthcenter.hrsa.gov | 5 | 5 | 0 |
| findtreatment.gov | 76 | 38 | 38 |
| findyourtherapist.adaa.org | 4 | 4 | 0 |
| fitkit.childrenshospital.org | 1 | 1 | 0 |
| fragilex.org | 5 | 5 | 0 |
| getpalliativecare.org | 4 | 4 | 0 |
| gettested.cdc.gov | 9 | 0 | 9 |
| gi.org | 78 | 63 | 15 |
| gikids.org | 33 | 16 | 17 |
| glaucoma.org | 16 | 10 | 6 |
| gluten.org | 21 | 17 | 4 |
| go2.org | 1 | 1 | 0 |
| gulfstudy.nih.gov | 1 | 0 | 1 |
| handsdownbetter.org | 1 | 1 | 0 |
| hcup-us.ahrq.gov | 1 | 1 | 0 |
| hdsa.org | 5 | 5 | 0 |
| headaches.org | 30 | 3 | 27 |
| headstart.gov | 1 | 0 | 1 |
| health.gov | 1 | 1 | 0 |
| health.mil | 4 | 4 | 0 |
| health.osu.edu | 2 | 2 | 0 |
| healthcare.utah.edu | 4 | 2 | 2 |
| healthit.gov | 5 | 5 | 0 |
| healthy.kaiserpermanente.org | 1 | 1 | 0 |
| hivinfo.nih.gov | 121 | 56 | 65 |
| hospicefoundation.org | 2 | 2 | 0 |
| hps.org | 6 | 6 | 0 |
| hpvroundtable.org | 1 | 1 | 0 |
| hsph.harvard.edu | 4 | 4 | 0 |
| identifythesigns.org | 1 | 1 | 0 |
| iffgd.org | 28 | 18 | 10 |
| ihrfoundation.org | 2 | 2 | 0 |
| imf-d8-prod.s3.us-west-1.wasabisys.com | 2 | 2 | 0 |
| iocdf.org | 4 | 4 | 0 |
| iris.who.int | 1 | 1 | 0 |
| joslin.org | 1 | 1 | 0 |
| kennedysdisease.org | 1 | 1 | 0 |
| kidney.instilled.com | 2 | 2 | 0 |
| kidshealth.org | 3744 | 1784 | 1960 |
| lbda.org | 8 | 8 | 0 |
| learn.genetics.utah.edu | 2 | 2 | 0 |
| library.samhsa.gov | 18 | 9 | 9 |
| lifeoptions.org | 11 | 4 | 7 |
| live-the-marfan-foundation-site.pantheonsite.io | 8 | 1 | 7 |
| liverfoundation.org | 37 | 25 | 12 |
| llsorg.widen.net | 37 | 0 | 37 |
| locator.apa.org | 14 | 14 | 0 |
| lowvision.preventblindness.org | 3 | 3 | 0 |
| lymphaticnetwork.org | 2 | 2 | 0 |
| lymphnet.org | 1 | 1 | 0 |
| lymphoma.org | 13 | 13 | 0 |
| madeforthismoment.asahq.org | 17 | 17 | 0 |
| magazine.medlineplus.gov | 474 | 233 | 241 |
| marfan.org | 10 | 10 | 0 |
| mchb.hrsa.gov | 2 | 1 | 1 |
| media.wix.com | 1 | 1 | 0 |
| medlineplus.gov | 19304 | 10422 | 8882 |
| members.aapmr.org | 17 | 17 | 0 |
| members.apma.org | 1 | 1 | 0 |
| members.audiology.org | 5 | 5 | 0 |
| menopause.org | 2 | 2 | 0 |
| mhanational.org | 21 | 21 | 0 |
| midwife.org | 1 | 1 | 0 |
| millionhearts.hhs.gov | 2 | 2 | 0 |
| minorityhealth.hhs.gov | 1 | 1 | 0 |
| mothertobaby.org | 183 | 84 | 99 |
| mpssociety.org | 1 | 1 | 0 |
| my.aesnet.org | 2 | 2 | 0 |
| my.rheumatology.org | 21 | 21 | 0 |
| myasthenia.org | 3 | 3 | 0 |
| myoms.org | 15 | 15 | 0 |
| nacersano.marchofdimes.org | 190 | 0 | 190 |
| nafcclinics.org | 1 | 1 | 0 |
| nces.ed.gov | 2 | 2 | 0 |
| ndss.org | 13 | 13 | 0 |
| nepis.epa.gov | 14 | 0 | 14 |
| newbornscreening.hrsa.gov | 5 | 5 | 0 |
| news.mit.edu | 1 | 1 | 0 |
| news.va.gov | 1 | 1 | 0 |
| newsinhealth.nih.gov | 233 | 233 | 0 |
| newsnetwork.mayoclinic.org | 7 | 7 | 0 |
| newsroom.uw.edu | 1 | 1 | 0 |
| nf01.diabeteseducator.org | 5 | 5 | 0 |
| nida.nih.gov | 163 | 86 | 77 |
| nigms.nih.gov | 15 | 15 | 0 |
| noiseawareness.org | 2 | 2 | 0 |
| npic.orst.edu | 9 | 0 | 9 |
| nrc-pad.org | 2 | 2 | 0 |
| nucdf.org | 2 | 2 | 0 |
| nutritioncare.org | 3 | 3 | 0 |
| nutritionsource.hsph.harvard.edu | 8 | 8 | 0 |
| obesitymedicine.org | 5 | 5 | 0 |
| oceanservice.noaa.gov | 1 | 1 | 0 |
| odphp.health.gov | 75 | 41 | 34 |
| ods.od.nih.gov | 91 | 54 | 37 |
| ohf.org | 3 | 3 | 0 |
| oif.org | 13 | 12 | 1 |
| ojjdp.ojp.gov | 1 | 1 | 0 |
| onsafety.cpsc.gov | 1 | 0 | 1 |
| oralcancerfoundation.org | 1 | 1 | 0 |
| order.nia.nih.gov | 6 | 5 | 1 |
| ors.od.nih.gov | 3 | 3 | 0 |
| orwh.od.nih.gov | 1 | 1 | 0 |
| osteopathic.org | 2 | 2 | 0 |
| ovc.ojp.gov | 1 | 0 | 1 |
| pancan.org | 4 | 4 | 0 |
| pancreasfoundation.org | 7 | 7 | 0 |
| pathology.jhu.edu | 9 | 9 | 0 |
| patiented.uofmhealth.org | 1 | 1 | 0 |
| patienteducation.asgct.org | 3 | 3 | 0 |
| pkdcure.org | 1 | 1 | 0 |
| pleaseprepme.org | 1 | 1 | 0 |
| poisonhelp.hrsa.gov | 7 | 4 | 3 |
| pollen.aaaai.org | 1 | 1 | 0 |
| porphyriafoundation.org | 7 | 7 | 0 |
| post-polio.org | 3 | 3 | 0 |
| postpartum.net | 8 | 8 | 0 |
| preventblindness.org | 9 | 8 | 1 |
| preventcancer.org | 1 | 1 | 0 |
| primaryimmune.org | 2 | 2 | 0 |
| professional.diabetes.org | 5 | 5 | 0 |
| progressreport.cancer.gov | 1 | 1 | 0 |
| publications.aap.org | 1 | 1 | 0 |
| publichealth.jhu.edu | 2 | 2 | 0 |
| pubmed.ncbi.nlm.nih.gov | 1007 | 1007 | 0 |
| pubs.usgs.gov | 2 | 0 | 2 |
| rarediseases.info.nih.gov | 216 | 67 | 149 |
| rarediseases.org | 9 | 6 | 3 |
| recovercovid.org | 1 | 1 | 0 |
| res.cloudinary.com | 1 | 1 | 0 |
| response.restoration.noaa.gov | 1 | 1 | 0 |
| rethinkingdrinking.niaaa.nih.gov | 9 | 9 | 0 |
| rheumatology.org | 124 | 63 | 61 |
| rightasrain.uwmedicine.org | 1 | 1 | 0 |
| riseandshine.childrensnational.org | 1 | 1 | 0 |
| s3.gi.org | 4 | 3 | 1 |
| safetosleep.nichd.nih.gov | 9 | 9 | 0 |
| salud.nih.gov | 366 | 0 | 366 |
| scarringalopecia.org | 1 | 1 | 0 |
| scholar.google.com | 4 | 4 | 0 |
| sci.washington.edu | 5 | 5 | 0 |
| scleroderma.org | 8 | 8 | 0 |
| screening.mhanational.org | 2 | 2 | 0 |
| search.amtamassage.org | 3 | 3 | 0 |
| secure.aao.org | 41 | 20 | 21 |
| seer.cancer.gov | 33 | 33 | 0 |
| sicklecelldisease.org | 1 | 1 | 0 |
| sisterstudy.niehs.nih.gov | 1 | 1 | 0 |
| sites.jamanetwork.com | 1 | 0 | 1 |
| sites.uab.edu | 1 | 1 | 0 |
| sjogrens.org | 10 | 10 | 0 |
| skinsight.com | 281 | 281 | 0 |
| sleepeducation.org | 8 | 8 | 0 |
| smafoundation.org | 1 | 1 | 0 |
| smartmoves.curesma.org | 2 | 2 | 0 |
| smokefree.gov | 15 | 15 | 0 |
| snisonline.org | 2 | 2 | 0 |
| sp.aboutgeneticcounselors.com | 1 | 0 | 1 |
| sp.findageneticcounselor.nsgc.org | 6 | 0 | 6 |
| spaceplace.nasa.gov | 4 | 0 | 4 |
| spondylitis.org | 8 | 8 | 0 |
| sprc.org | 1 | 1 | 0 |
| srfcure.org | 2 | 2 | 0 |
| srtr.hrsa.gov | 7 | 7 | 0 |
| stemcells.nih.gov | 2 | 2 | 0 |
| studies.recovercovid.org | 1 | 1 | 0 |
| supportorgs.cancer.gov | 40 | 0 | 40 |
| surveillance.cancer.gov | 1 | 1 | 0 |
| teen.smokefree.gov | 1 | 1 | 0 |
| telehealth.hhs.gov | 5 | 5 | 0 |
| telehealth.va.gov | 1 | 1 | 0 |
| thalassemia.org | 2 | 2 | 0 |
| theconsumervoice.org | 4 | 4 | 0 |
| themmrf.org | 1 | 1 | 0 |
| therealcost.betobaccofree.hhs.gov | 1 | 1 | 0 |
| thewholeu.uw.edu | 1 | 1 | 0 |
| together.stjude.org | 1 | 1 | 0 |
| tools.niehs.nih.gov | 1 | 0 | 1 |
| tourette.org | 12 | 7 | 5 |
| training.seer.cancer.gov | 41 | 41 | 0 |
| travel.state.gov | 1 | 1 | 0 |
| treatments-es.hhs.gov | 1 | 0 | 1 |
| treatments.hhs.gov | 1 | 1 | 0 |
| ulf.org | 1 | 1 | 0 |
| umdf.org | 3 | 3 | 0 |
| unos.org | 5 | 5 | 0 |
| upbeat.org | 15 | 15 | 0 |
| usdss.cdc.gov | 2 | 2 | 0 |
| vaers.hhs.gov | 8 | 4 | 4 |
| vascular.org | 1 | 1 | 0 |
| vasculitisfoundation.org | 6 | 6 | 0 |
| vestibular.org | 25 | 25 | 0 |
| veteranshealthlibrary.va.gov | 1 | 1 | 0 |
| vhl.org | 2 | 2 | 0 |
| watchlearnlive.heart.org | 2 | 2 | 0 |
| water.usgs.gov | 7 | 0 | 7 |
| wfh.org | 2 | 0 | 2 |
| wicbreastfeeding.fna.usda.gov | 4 | 4 | 0 |
| wicworks.fna.usda.gov | 2 | 2 | 0 |
| wilsondisease.org | 6 | 6 | 0 |
| wisqars.cdc.gov | 1 | 1 | 0 |
| womenshealth.gov | 172 | 171 | 1 |
| www.1800runaway.org | 3 | 3 | 0 |
| www.2min2x.org | 1 | 1 | 0 |
| www.aaaai.org | 153 | 79 | 74 |
| www.aaas.org | 1 | 1 | 0 |
| www.aabb.org | 3 | 3 | 0 |
| www.aacap.org | 54 | 54 | 0 |
| www.aad.org | 92 | 92 | 0 |
| www.aafp.org | 1 | 1 | 0 |
| www.aafprs.org | 4 | 4 | 0 |
| www.aamc.org | 1 | 1 | 0 |
| www.aamds.org | 8 | 8 | 0 |
| www.aamft.org | 2 | 2 | 0 |
| www.aan.com | 3 | 3 | 0 |
| www.aao.org | 276 | 117 | 159 |
| www.aaom.com | 9 | 6 | 3 |
| www.aapd.org | 1 | 1 | 0 |
| www.aapmr.org | 1 | 1 | 0 |
| www.aapos.org | 94 | 94 | 0 |
| www.aboutgeneticcounselors.com | 1 | 1 | 0 |
| www.aboutstemcells.org | 5 | 5 | 0 |
| www.abta.org | 9 | 9 | 0 |
| www.acatoday.org | 1 | 1 | 0 |
| www.accessdata.fda.gov | 5 | 4 | 1 |
| www.acep.org | 6 | 6 | 0 |
| www.achaheart.org | 1 | 1 | 0 |
| www.acog.org | 120 | 119 | 1 |
| www.acpanow.com | 1 | 1 | 0 |
| www.acponline.org | 13 | 13 | 0 |
| www.ada.gov | 1 | 1 | 0 |
| www.adces.org | 4 | 4 | 0 |
| www.afb.org | 8 | 8 | 0 |
| www.agd.org | 3 | 3 | 0 |
| www.aha.org | 1 | 1 | 0 |
| www.ahajournals.org | 10 | 10 | 0 |
| www.ahrq.gov | 63 | 31 | 32 |
| www.airnow.gov | 7 | 5 | 2 |
| www.alportsyndrome.org | 1 | 1 | 0 |
| www.als.org | 15 | 15 | 0 |
| www.alz.org | 45 | 29 | 16 |
| www.alzheimers.gov | 44 | 22 | 22 |
| www.alzinfo.org | 1 | 1 | 0 |
| www.ama-assn.org | 2 | 2 | 0 |
| www.ameriburn.org | 1 | 1 | 0 |
| www.american-rhinologic.org | 4 | 4 | 0 |
| www.americanbrainfoundation.org | 3 | 3 | 0 |
| www.americangeriatrics.org | 2 | 2 | 0 |
| www.amyloidosis.org | 1 | 1 | 0 |
| www.anchorit.gov | 2 | 1 | 1 |
| www.animatedpancreaspatient.com | 1 | 1 | 0 |
| www.aoa.org | 3 | 3 | 0 |
| www.aofas.org | 6 | 6 | 0 |
| www.aota.org | 1 | 1 | 0 |
| www.apa.org | 114 | 22 | 92 |
| www.apha.org | 3 | 2 | 1 |
| www.apma.org | 18 | 18 | 0 |
| www.apraxia-kids.org | 2 | 1 | 1 |
| www.arthritis.org | 27 | 27 | 0 |
| www.asds.net | 36 | 36 | 0 |
| www.asge.org | 39 | 19 | 20 |
| www.asha.org | 8 | 0 | 8 |
| www.ashasexualhealth.org | 1 | 1 | 0 |
| www.ashg.org | 1 | 1 | 0 |
| www.asnc.org | 1 | 1 | 0 |
| www.asoprs.org | 2 | 2 | 0 |
| www.aspr.gov | 3 | 3 | 0 |
| www.asrm.org | 1 | 1 | 0 |
| www.asrs.org | 3 | 3 | 0 |
| www.assh.org | 17 | 17 | 0 |
| www.ata.org | 4 | 4 | 0 |
| www.atsdr.cdc.gov | 10 | 0 | 10 |
| www.autismspeaks.org | 1 | 1 | 0 |
| www.avma.org | 12 | 12 | 0 |
| www.bafound.org | 5 | 5 | 0 |
| www.bls.gov | 46 | 46 | 0 |
| www.bonehealthandosteoporosis.org | 6 | 6 | 0 |
| www.brainandlife.org | 2 | 1 | 1 |
| www.brainfacts.org | 3 | 3 | 0 |
| www.brainhealth.com | 1 | 1 | 0 |
| www.brainline.org | 1 | 1 | 0 |
| www.breakthrought1d.org | 4 | 3 | 1 |
| www.brightfocus.org | 12 | 7 | 5 |
| www.bu.edu | 1 | 1 | 0 |
| www.canada.ca | 1 | 1 | 0 |
| www.cancer.gov | 1916 | 920 | 996 |
| www.cancer.org | 1136 | 643 | 493 |
| www.cancercare.org | 3 | 0 | 3 |
| www.cap.org | 2 | 2 | 0 |
| www.carcinoid.org | 1 | 1 | 0 |
| www.cardiosmart.org | 1 | 1 | 0 |
| www.caregiver.org | 30 | 12 | 18 |
| www.caregiver.va.gov | 2 | 2 | 0 |
| www.caregiveraction.org | 2 | 2 | 0 |
| www.cc.nih.gov | 4 | 3 | 1 |
| www.cda.org | 8 | 0 | 8 |
| www.cdc.gov | 2386 | 1741 | 645 |
| www.cdtorticollis.org | 2 | 2 | 0 |
| www.census.gov | 2 | 2 | 0 |
| www.cff.org | 21 | 21 | 0 |
| www.childrenscardiomyopathy.org | 1 | 1 | 0 |
| www.childrenshospital.org | 2 | 2 | 0 |
| www.childrensmn.org | 4 | 3 | 1 |
| www.childstats.gov | 4 | 4 | 0 |
| www.childwelfare.gov | 4 | 4 | 0 |
| www.choosept.com | 7 | 7 | 0 |
| www.chop.edu | 1 | 1 | 0 |
| www.christopherreeve.org | 19 | 17 | 2 |
| www.cms.gov | 15 | 10 | 5 |
| www.collegedrinkingprevention.gov | 6 | 6 | 0 |
| www.compassionatefriends.org | 2 | 2 | 0 |
| www.conquerchiari.org | 5 | 5 | 0 |
| www.consumerfinance.gov | 1 | 1 | 0 |
| www.cpsc.gov | 20 | 12 | 8 |
| www.cpwr.com | 4 | 1 | 3 |
| www.crohnscolitisfoundation.org | 44 | 44 | 0 |
| www.ctf.org | 6 | 6 | 0 |
| www.cuidadodesalud.gov | 16 | 0 | 16 |
| www.curefa.org | 5 | 5 | 0 |
| www.curesma.org | 8 | 8 | 0 |
| www.dbsalliance.org | 11 | 11 | 0 |
| www.dea.gov | 18 | 18 | 0 |
| www.dhs.gov | 2 | 2 | 0 |
| www.dhs.wisconsin.gov | 1 | 0 | 1 |
| www.dietaryguidelines.gov | 1 | 1 | 0 |
| www.disasterassistance.gov | 18 | 9 | 9 |
| www.docinfo.org | 1 | 1 | 0 |
| www.dol.gov | 3 | 2 | 1 |
| www.eatright.org | 63 | 63 | 0 |
| www.emergencyphysicians.org | 15 | 11 | 4 |
| www.empoweredtoserve.org | 1 | 1 | 0 |
| www.endocrine.org | 61 | 61 | 0 |
| www.enthealth.org | 62 | 62 | 0 |
| www.entnet.org | 13 | 13 | 0 |
| www.epa.gov | 152 | 151 | 1 |
| www.epilepsy.com | 1 | 1 | 0 |
| www.epilepsy.va.gov | 1 | 1 | 0 |
| www.facs.org | 6 | 4 | 2 |
| www.fanconi.org | 1 | 0 | 1 |
| www.fbi.gov | 1 | 1 | 0 |
| www.fcc.gov | 18 | 9 | 9 |
| www.fda.gov | 636 | 396 | 240 |
| www.fema.gov | 23 | 16 | 7 |
| www.fiacardiac.org | 1 | 1 | 0 |
| www.fightingblindness.org | 6 | 6 | 0 |
| www.fna.usda.gov | 6 | 6 | 0 |
| www.fns.usda.gov | 3 | 3 | 0 |
| www.foodallergy.org | 19 | 19 | 0 |
| www.foodsafety.gov | 11 | 11 | 0 |
| www.footcaremd.org | 41 | 41 | 0 |
| www.foothealthfacts.org | 172 | 79 | 93 |
| www.foundationforpn.org | 3 | 3 | 0 |
| www.fs.usda.gov | 1 | 1 | 0 |
| www.fshdsociety.org | 1 | 1 | 0 |
| www.fsis.usda.gov | 31 | 28 | 3 |
| www.ftc.gov | 2 | 1 | 1 |
| www.gbs-cidp.org | 3 | 2 | 1 |
| www.genome.gov | 112 | 82 | 30 |
| www.getsmartaboutdrugs.gov | 20 | 12 | 8 |
| www.goredforwomen.org | 6 | 3 | 3 |
| www.greaterthan.org | 2 | 1 | 1 |
| www.harvardhealth.org | 54 | 54 | 0 |
| www.health.mil | 2 | 2 | 0 |
| www.health.ny.gov | 14 | 0 | 14 |
| www.health.state.mn.us | 3 | 1 | 2 |
| www.healthcare.gov | 4 | 4 | 0 |
| www.healthinaging.org | 69 | 66 | 3 |
| www.healthychildren.org | 775 | 318 | 457 |
| www.heart.org | 284 | 264 | 20 |
| www.hematology.org | 15 | 15 | 0 |
| www.hepatitis.va.gov | 15 | 15 | 0 |
| www.hepb.org | 6 | 4 | 2 |
| www.hhs.gov | 27 | 27 | 0 |
| www.hiv.gov | 25 | 16 | 9 |
| www.hiv.va.gov | 3 | 3 | 0 |
| www.hopkinsarthritis.org | 2 | 2 | 0 |
| www.hopkinsmedicine.org | 50 | 50 | 0 |
| www.hopkinsvasculitis.org | 19 | 19 | 0 |
| www.hrsa.gov | 26 | 23 | 3 |
| www.hsph.harvard.edu | 9 | 9 | 0 |
| www.hudexchange.info | 1 | 1 | 0 |
| www.hydroassoc.org | 8 | 7 | 1 |
| www.ihs.gov | 1 | 1 | 0 |
| www.imagegently.org | 3 | 3 | 0 |
| www.immunize.org | 56 | 17 | 39 |
| www.info4pi.org | 1 | 1 | 0 |
| www.ismrm.org | 1 | 1 | 0 |
| www.jointcommission.org | 4 | 4 | 0 |
| www.justice.gov | 5 | 4 | 1 |
| www.justthinktwice.gov | 2 | 2 | 0 |
| www.kff.org | 5 | 5 | 0 |
| www.kidney.org | 81 | 65 | 16 |
| www.kidneyfund.org | 20 | 12 | 8 |
| www.komen.org | 14 | 12 | 2 |
| www.lbbc.org | 4 | 4 | 0 |
| www.lung.org | 109 | 109 | 0 |
| www.lupus.org | 34 | 18 | 16 |
| www.maketheconnection.net | 1 | 1 | 0 |
| www.marchofdimes.org | 103 | 103 | 0 |
| www.marshfieldresearch.org | 2 | 2 | 0 |
| www.maxillofacialprosthetics.org | 1 | 1 | 0 |
| www.mayoclinic.org | 3175 | 1557 | 1618 |
| www.mchoralhealth.org | 2 | 1 | 1 |
| www.mda.org | 40 | 34 | 6 |
| www.mdanderson.org | 1 | 1 | 0 |
| www.mealsonwheelsamerica.org | 2 | 2 | 0 |
| www.medicaid.gov | 4 | 4 | 0 |
| www.medicare.gov | 43 | 42 | 1 |
| www.medlineplus.gov | 2 | 1 | 1 |
| www.medschool.pitt.edu | 1 | 1 | 0 |
| www.medschool.umaryland.edu | 1 | 1 | 0 |
| www.mentalhealth.va.gov | 5 | 5 | 0 |
| www.merckmanuals.com | 471 | 198 | 273 |
| www.militaryonesource.mil | 1 | 1 | 0 |
| www.mirecc.va.gov | 2 | 2 | 0 |
| www.missingkids.org | 1 | 1 | 0 |
| www.msdmanuals.com | 31 | 1 | 30 |
| www.mskcc.org | 4 | 4 | 0 |
| www.mvp.va.gov | 1 | 1 | 0 |
| www.myast.org | 3 | 3 | 0 |
| www.naaf.org | 1 | 1 | 0 |
| www.naccho.org | 1 | 1 | 0 |
| www.nadsa.org | 1 | 1 | 0 |
| www.nal.usda.gov | 21 | 21 | 0 |
| www.nami.org | 27 | 21 | 6 |
| www.nationalacademies.org | 1 | 1 | 0 |
| www.nationaleatingdisorders.org | 6 | 6 | 0 |
| www.nationaljewish.org | 40 | 28 | 12 |
| www.nationalmssociety.org | 26 | 26 | 0 |
| www.ncbi.nlm.nih.gov | 11 | 8 | 3 |
| www.nccaom.org | 4 | 4 | 0 |
| www.nccih.nih.gov | 283 | 256 | 27 |
| www.ncoa.org | 10 | 10 | 0 |
| www.ncpc.org | 1 | 1 | 0 |
| www.nctsn.org | 11 | 2 | 9 |
| www.neefusa.org | 1 | 1 | 0 |
| www.nei.nih.gov | 241 | 150 | 91 |
| www.nemours.org | 1 | 1 | 0 |
| www.neuropt.org | 31 | 0 | 31 |
| www.nevus.org | 5 | 5 | 0 |
| www.nfid.org | 10 | 10 | 0 |
| www.nfpa.org | 4 | 2 | 2 |
| www.nhlbi.nih.gov | 692 | 431 | 261 |
| www.nhtsa.gov | 13 | 6 | 7 |
| www.nia.nih.gov | 328 | 189 | 139 |
| www.niaaa.nih.gov | 77 | 42 | 35 |
| www.niaid.nih.gov | 167 | 165 | 2 |
| www.niams.nih.gov | 280 | 168 | 112 |
| www.nibib.nih.gov | 15 | 7 | 8 |
| www.nichd.nih.gov | 310 | 303 | 7 |
| www.nidcd.nih.gov | 169 | 107 | 62 |
| www.nidcr.nih.gov | 134 | 71 | 63 |
| www.niddk.nih.gov | 1000 | 605 | 395 |
| www.niehs.nih.gov | 70 | 67 | 3 |
| www.nifc.gov | 1 | 1 | 0 |
| www.nigms.nih.gov | 3 | 3 | 0 |
| www.nih.gov | 13 | 13 | 0 |
| www.nimh.nih.gov | 257 | 156 | 101 |
| www.nimhd.nih.gov | 3 | 3 | 0 |
| www.ninds.nih.gov | 411 | 339 | 72 |
| www.ninr.nih.gov | 6 | 6 | 0 |
| www.nlm.nih.gov | 10 | 7 | 3 |
| www.nmdp.org | 35 | 19 | 16 |
| www.nnlm.gov | 32 | 15 | 17 |
| www.noaa.gov | 1 | 1 | 0 |
| www.nps.gov | 3 | 3 | 0 |
| www.nsc.org | 3 | 3 | 0 |
| www.nuestrasalud.org | 2 | 0 | 2 |
| www.nutrition.gov | 3 | 2 | 1 |
| www.nutrition.va.gov | 1 | 1 | 0 |
| www.nva.org | 5 | 5 | 0 |
| www.organdonor.gov | 14 | 9 | 5 |
| www.orthoinfo.org | 180 | 160 | 20 |
| www.osha.gov | 56 | 51 | 5 |
| www.osteoporosis.foundation | 1 | 1 | 0 |
| www.paho.org | 10 | 2 | 8 |
| www.pancreasanimado.com | 1 | 0 | 1 |
| www.parentcenterhub.org | 15 | 6 | 9 |
| www.parentprojectmd.org | 5 | 5 | 0 |
| www.parkinson.org | 53 | 33 | 20 |
| www.pcf.org | 4 | 4 | 0 |
| www.perio.org | 10 | 10 | 0 |
| www.phoenix-society.org | 1 | 1 | 0 |
| www.plasticsurgery.org | 8 | 3 | 5 |
| www.poisoncenters.org | 1 | 1 | 0 |
| www.prevention.va.gov | 3 | 3 | 0 |
| www.progeriaresearch.org | 1 | 1 | 0 |
| www.prosthetics.va.gov | 2 | 2 | 0 |
| www.psoriasis.org | 1 | 1 | 0 |
| www.psp.org | 1 | 1 | 0 |
| www.psychiatry.org | 18 | 17 | 1 |
| www.ptsd.va.gov | 49 | 30 | 19 |
| www.publichealth.va.gov | 13 | 13 | 0 |
| www.pulmonaryfibrosis.org | 4 | 4 | 0 |
| www.pwsausa.org | 1 | 1 | 0 |
| www.radiologyinfo.org | 888 | 394 | 494 |
| www.ready.gov | 55 | 19 | 36 |
| www.redcross.org | 33 | 26 | 7 |
| www.redcrossblood.org | 10 | 9 | 1 |
| www.reproductivefacts.org | 25 | 25 | 0 |
| www.rettsyndrome.org | 2 | 2 | 0 |
| www.rls.org | 2 | 2 | 0 |
| www.rosacea.org | 8 | 8 | 0 |
| www.rtanswers.org | 2 | 2 | 0 |
| www.ruralhealthinfo.org | 15 | 15 | 0 |
| www.safekids.org | 7 | 6 | 1 |
| www.safemedication.com | 10 | 10 | 0 |
| www.saferproducts.gov | 1 | 1 | 0 |
| www.sages.org | 18 | 18 | 0 |
| www.samhsa.gov | 31 | 28 | 3 |
| www.sirweb.org | 8 | 8 | 0 |
| www.skincancer.org | 5 | 3 | 2 |
| www.spc.noaa.gov | 4 | 4 | 0 |
| www.spinabifidaassociation.org | 6 | 6 | 0 |
| www.spine.org | 37 | 32 | 5 |
| www.srs.org | 7 | 7 | 0 |
| www.ssa.gov | 19 | 9 | 10 |
| www.stjude.org | 6 | 0 | 6 |
| www.stlouischildrens.org | 1 | 1 | 0 |
| www.stopbullying.gov | 9 | 9 | 0 |
| www.stoptheclot.org | 1 | 1 | 0 |
| www.stroke.cindrr.research.va.gov | 1 | 0 | 1 |
| www.stroke.org | 79 | 37 | 42 |
| www.sts.org | 3 | 3 | 0 |
| www.stutteringhelp.org | 4 | 4 | 0 |
| www.tartamudez.org | 7 | 0 | 7 |
| www.texaschildrens.org | 1 | 1 | 0 |
| www.texasheart.org | 242 | 66 | 176 |
| www.theaftd.org | 1 | 1 | 0 |
| www.thehastingscenter.org | 2 | 2 | 0 |
| www.thewellproject.org | 2 | 1 | 1 |
| www.thoracic.org | 39 | 27 | 12 |
| www.thyroid.org | 41 | 29 | 12 |
| www.tricare.mil | 2 | 2 | 0 |
| www.tsa.gov | 2 | 2 | 0 |
| www.tscalliance.org | 4 | 4 | 0 |
| www.turi.org | 2 | 2 | 0 |
| www.turnersyndrome.org | 1 | 1 | 0 |
| www.uab.edu | 1 | 1 | 0 |
| www.un.org | 1 | 1 | 0 |
| www.unos.org | 1 | 1 | 0 |
| www.usa.gov | 5 | 3 | 2 |
| www.usaging.org | 12 | 12 | 0 |
| www.usda.gov | 1 | 1 | 0 |
| www.usfa.fema.gov | 11 | 7 | 4 |
| www.usgs.gov | 9 | 9 | 0 |
| www.uspreventiveservicestaskforce.org | 3 | 3 | 0 |
| www.va.gov | 11 | 11 | 0 |
| www.vaccineinformation.org | 5 | 5 | 0 |
| www.vdacs.virginia.gov | 1 | 0 | 1 |
| www.vetcenter.va.gov | 1 | 0 | 1 |
| www.veteranscrisisline.net | 1 | 1 | 0 |
| www.veteranshealthlibrary.va.gov | 5 | 3 | 2 |
| www.virtualpediatrichospital.org | 1 | 1 | 0 |
| www.vmanyc.org | 1 | 0 | 1 |
| www.voicesforpfd.org | 5 | 5 | 0 |
| www.warrelatedillness.va.gov | 1 | 1 | 0 |
| www.weather.gov | 2 | 2 | 0 |
| www.who.int | 86 | 44 | 42 |
| www.womenshealth.va.gov | 2 | 2 | 0 |
| www.youtube.com | 59 | 37 | 22 |
| www.zerotothree.org | 3 | 3 | 0 |
| www1.wfh.org | 5 | 1 | 4 |
| www3.epa.gov | 2 | 0 | 2 |
| wwwn.cdc.gov | 6 | 6 | 0 |
| wwwnc.cdc.gov | 10 | 8 | 2 |
| youngmenshealthsite.org | 8 | 8 | 0 |
| youngwomenshealth.org | 114 | 40 | 74 |

## Unique values and representative XML

Every field’s exact unique leaf or attribute values and frequencies are in `scopes.<scope>.unique_values`; cardinalities are in `fields` and `attributes`. This includes high-cardinality summaries, titles, dates, URLs, aliases, descriptor IDs, and low-cardinality language/resource labels. `groups`, `categories`, and `organizations` also list topic memberships. Root values are in `source.root_attributes`.

Selected elements below are serialized from original record 6308 (omitted elements are not shown):

```xml
<health-topic meta-desc="A1C is a blood test for type 2 diabetes and prediabetes. It measures your average blood glucose, or blood sugar, levels over the past 3 months. Learn more." title="A1C" url="https://medlineplus.gov/a1c.html" id="6308" language="English" date-created="12/22/2015"><also-called>Glycohemoglobin</also-called>
<also-called>HbA1C</also-called>
<also-called>Hemoglobin A1C test</also-called>
<group url="https://medlineplus.gov/diagnostictestsandscreenings.html" id="25">Diagnostic Tests and Screenings</group>
<group url="https://medlineplus.gov/diabetesmellitus.html" id="45">Diabetes Mellitus</group>
<language-mapped-topic url="https://medlineplus.gov/spanish/a1c.html" id="6309" language="Spanish">Prueba de hemoglobina glicosilada (HbA1c)</language-mapped-topic>
<mesh-heading>
<descriptor id="D006442">Glycated Hemoglobin</descriptor>
</mesh-heading>
<see-reference>HbA1c</see-reference>
<see-reference>Hemoglobin A1c</see-reference>
</health-topic>
```

Additional exact elements serialized from source: a qualified MeSH heading from How to Prevent Diabetes (6661), and a directory resource from Knee Injuries and Disorders (304).

```xml
<mesh-heading>
<descriptor id="D003924">Diabetes Mellitus, Type 2</descriptor>
<qualifier id="Q000517">prevention &amp; control</qualifier>
</mesh-heading>
<site title="Find a Doctor in American Association of Hip and Knee Surgeons" url="https://findadoctor.aahks.net">
<information-category>Find an Expert</information-category>
<organization>American Association of Hip and Knee Surgeons</organization>
</site>
```
