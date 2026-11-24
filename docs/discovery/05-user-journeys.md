# Healthcare user journeys

Phase 0 · snapshot generated 11/24/2026 02:30:05 · analysis date 2026-11-24.

## Scope and safety boundary

These are discovery journeys based on local source records, not medical advice or generated answers. Listed external URLs and organizations are copied from XML; no destination was opened to verify availability, clinical content, service area, or current trial status. “Potential action” means an educational/navigation action. Candidate topics must not be interpreted as diagnoses, specialty referrals, or treatment recommendations.

## Query-by-query evidence

### “I have knee pain. What should I do?”

**Relevant records:** [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304); [Osteoarthritis](<https://medlineplus.gov/osteoarthritis.html>) (ID 1250); [Arthritis](<https://medlineplus.gov/arthritis.html>) (ID 23).

**Matching vocabulary:** Knee/pain terms in summary and site titles; ACL Injuries, Meniscus, Patella are source index terms, not general knee-pain diagnoses.

**Relationships:** 304 → Knee Replacement (3924), Leg Injuries and Disorders; 304 summary links to Osteoarthritis.

**Resources and potential actions:** Read source education and Start Here resources; explore related topics. Source-specific resource URLs and categories for these records appear in the evidence cards below.

**Missing information:** No symptom history, examination, triage protocol, personal treatment plan, or diagnostic confidence.

**Discovery assessment:** Lexical title+summary+resource search can find knee material; semantic benefit for conversational wording is unproven. Return education, not a “what to do” medical answer.

### “My knees hurt when I walk.”

**Relevant records:** [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304); [Walking Problems](<https://medlineplus.gov/walkingproblems.html>) (ID 5673); [Osteoarthritis](<https://medlineplus.gov/osteoarthritis.html>) (ID 1250).

**Matching vocabulary:** 304 summary contains “pain and difficulty walking”; 5673 has Gait Abnormalities; 1250 has OA/Osteoarthrosis. “Hurt” is not an explicit pain alias.

**Relationships:** Walking Problems explicitly points to Knee Injuries and Disorders; knee summary links to Osteoarthritis; shared group 10 is browse context.

**Resources and potential actions:** Explore knee and movement education; open source-selected resources. Source-specific resource URLs and categories for these records appear in the evidence cards below.

**Missing information:** No way to determine the cause of an individual’s walking-related pain.

**Discovery assessment:** Lexical plural normalization and summary indexing may suffice; semantic search is a Later experiment, with broad candidate presentation and no diagnosis.

### “What is HbA1c?”

**Relevant records:** [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308); [Blood Glucose](<https://medlineplus.gov/bloodglucose.html>) (ID 6121).

**Matching vocabulary:** HbA1C/HbA1c, Glycohemoglobin, Hemoglobin A1C test, Hemoglobin A1c; descriptor Glycated Hemoglobin.

**Relationships:** A1C has no outgoing related-topic; summary links to type 2 diabetes, prediabetes, blood glucose; incoming Diabetes → A1C.

**Resources and potential actions:** Open attributed A1C overview, NLM lab-test link, NIDDK education; switch to Spanish. Source-specific resource URLs and categories for these records appear in the evidence cards below.

**Missing information:** No individual lab interpretation, reference-range customization, or clinician advice.

**Discovery assessment:** Case-insensitive exact alias matching directly solves this; semantics not needed.

### “Where can I learn more about diabetes?”

**Relevant records:** [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4); [Diabetes Type 2](<https://medlineplus.gov/diabetestype2.html>) (ID 5930); [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308); [Prediabetes](<https://medlineplus.gov/prediabetes.html>) (ID 5784).

**Matching vocabulary:** DM, Diabetes mellitus, Sugar Diabetes; Sugar Diabetes also appears on type 2 topic.

**Relationships:** Diabetes has 19 outgoing related topics including A1C, Blood Glucose, both diabetes types, complications and diet.

**Resources and potential actions:** Read Start Here and patient education resources; narrow to tests, living with, or research. Source-specific resource URLs and categories for these records appear in the evidence cards below.

**Missing information:** No patient diagnosis, care-plan tracking, patient measurements, or treatment personalization.

**Discovery assessment:** Lexical and faceted search are sufficient starting points; broad diabetes query should retain type distinctions.

### “Are there clinical trials related to my condition?”

**Relevant records:** [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4); [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304); [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157); [Headache](<https://medlineplus.gov/headache.html>) (ID 273); [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24); [Clinical Trials](<https://medlineplus.gov/clinicaltrials.html>) (ID 192).

**Matching vocabulary:** Condition topic labels plus Clinical Trials resource category; trial titles contain condition terminology.

**Relationships:** Topic → site assignments; Clinical Trials (192) supplies general education, not a validated eligibility relationship.

**Resources and potential actions:** Open the condition’s existing ClinicalTrials.gov search link; read trial education. Source-specific resource URLs and categories for these records appear in the evidence cards below.

**Missing information:** No actual local trial records, current enrollment status, eligibility, geography, contact details, or matching algorithm.

**Discovery assessment:** Lexical topic lookup plus user-selected Clinical Trials category; semantics unnecessary for explicit trial intent.

### “Can I read this information in Spanish?”

**Relevant records:** [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304); [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157); [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308); [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4); [Headache](<https://medlineplus.gov/headache.html>) (ID 273); [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24).

**Matching vocabulary:** Explicit language-mapped-topic titles: see record evidence and minimal selection.

**Relationships:** Each of these six has a reciprocal Spanish topic mapping; resource translation coverage is separate.

**Resources and potential actions:** Switch topic using source mapping; use mapped resource URL only where supplied. Source-specific resource URLs and categories for these records appear in the evidence cards below.

**Missing information:** Not every resource is bilingual; Medical Ethics lacks a topic equivalent. Other-language links are not locally stored translations.

**Discovery assessment:** No semantic translation needed. Bilingual exact labels can improve discovery later; never synthesize a missing translation.

### “What type of doctor should I see?”

**Relevant records:** [Choosing a Doctor or Health Care Service](<https://medlineplus.gov/choosingadoctororhealthcareservice.html>) (ID 1433); [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304); [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157); [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24).

**Matching vocabulary:** Choosing a Doctor or Health Care Service; specialist-related resource titles such as Find an Allergist or Find a Physical Medicine and Rehabilitation Physician.

**Relationships:** 1433 explicitly relates to Health Facilities and Patient Safety. Condition-to-directory links are editorial resource assignments, not clinical referrals.

**Resources and potential actions:** Read provider-selection education; navigate to source-listed professional organizations/directories with clear labels. Source-specific resource URLs and categories for these records appear in the evidence cards below.

**Missing information:** No validated symptom-to-specialty rules, patient context, referral requirements, credentials assessment, or care urgency.

**Discovery assessment:** Lexical provider-selection terms and source resource titles can expose education. Semantic matching cannot validate a specialty recommendation.

### “Where can I find a doctor near me?”

**Relevant records:** [Choosing a Doctor or Health Care Service](<https://medlineplus.gov/choosingadoctororhealthcareservice.html>) (ID 1433); [Health Facilities](<https://medlineplus.gov/healthfacilities.html>) (ID 486); [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304); [Walking Problems](<https://medlineplus.gov/walkingproblems.html>) (ID 5673); [Hay Fever](<https://medlineplus.gov/hayfever.html>) (ID 4746).

**Matching vocabulary:** Directory/navigation words occur in site titles; near-me location fields do not exist.

**Relationships:** Topic → directory links; shared directory links do not prove a provider relationship.

**Resources and potential actions:** Open Medicare Care Compare, AAHKS Find a Doctor, AAAAI Find an Allergist, or ChoosePT where relevant to selected topic. Source-specific resource URLs and categories for these records appear in the evidence cards below.

**Missing information:** No provider/hospital entities with addresses, coordinates, distance, availability, insurance, or appointment booking. Geographic applicability of each directory is unverified.

**Discovery assessment:** Lexical navigation can find links; neither lexical nor semantic search supplies missing local directory data.

## Back pain, headaches, and allergies: additional inspected journeys

| Journey | Real records and vocabulary | Source relationships | Useful discovery / limit |
| --- | --- | --- | --- |
| Backache / low back pain | [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157); [Sciatica](<https://medlineplus.gov/sciatica.html>) (ID 1532); [Herniated Disk](<https://medlineplus.gov/herniateddisk.html>) (ID 284); Backache, Lumbago, Low Back Pain | Back Pain → Sciatica and Herniated Disk explicit related-topic | Education, trials link and resource navigation; no cause diagnosis or recommended intervention |
| Headaches / Vascular Headache | [Headache](<https://medlineplus.gov/headache.html>) (ID 273); [Migraine](<https://medlineplus.gov/migraine.html>) (ID 3157); Cluster Headache, Tension Headache, Vascular Headache | Headache ↔ Migraine explicit; Vascular Headache belongs to both aliases | Display disambiguation and educational choices; do not classify headache subtype |
| Allergies / allergic rhinitis | [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24); [Hay Fever](<https://medlineplus.gov/hayfever.html>) (ID 4746); [Food Allergy](<https://medlineplus.gov/foodallergy.html>) (ID 1256); [Anaphylaxis](<https://medlineplus.gov/anaphylaxis.html>) (ID 6257); Hypersensitivity, Allergic Rhinitis, food-specific index terms | Allergy → Hay Fever, Food Allergy, Anaphylaxis explicit | Scope choice and source education; no individual allergy diagnosis or emergency triage |

## Real-record evidence cards

Each card lists exact source vocabulary, curated relationships, all resource-category counts, and representative resource links. Category counts overlap for multi-category resources. Full resource lists and summary text are in `dataset-profile.json` → `records.<id>`. These cards are evidence about content availability, not clinical endorsements.

### Knee Injuries and Disorders — 304

Canonical source: [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304).

Aliases: ACL Injuries (see-reference); Anterior Cruciate Ligament Injury (see-reference); Baker's Cyst (see-reference); Chondromalacia Patellae (see-reference); Meniscus (see-reference); Patella (see-reference).

Groups: Bones, Joints and Muscles (10); Injuries and Wounds (19). MeSH: Anterior Cruciate Ligament Reconstruction (D059549); Knee Injuries (D007718).

Curated related-topic: [Knee Replacement](<https://medlineplus.gov/kneereplacement.html>) (ID 3924); [Leg Injuries and Disorders](<https://medlineplus.gov/leginjuriesanddisorders.html>) (ID 509).

Spanish equivalent: [Lesiones y enfermedades de la rodilla](<https://medlineplus.gov/spanish/kneeinjuriesanddisorders.html>) (ID 1994).

Resources: **60 site assignments**. Categories: Children: 2; Clinical Trials: 6; Diagnosis and Tests: 4; Encyclopedia: 25; Find an Expert: 2; Images: 2; Journal Articles: 1; Living With: 2; Patient Handouts: 13; Specifics: 8; Start Here: 2; Symptoms: 1; Teenagers: 4; Test Your Knowledge: 1; Treatments and Therapies: 4.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Common Knee Injuries](<https://www.orthoinfo.org/bodypart/kneelower-leg/common-knee-injuries/>) | Start Here | American Academy of Orthopaedic Surgeons |
| [ACL reconstruction](<https://medlineplus.gov/ency/article/007208.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Anterior Cruciate Ligament (ACL) Injuries](<https://clinicaltrials.gov/search?cond=%22Anterior+Cruciate+Ligament%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Knee injuries and disorders](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Knee+Injuries%22%5Bmajr%3Anoexp%5D+OR+%22Anterior+Cruciate+Ligament+Reconstruction%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Find a Doctor in American Association of Hip and Knee Surgeons](<https://findadoctor.aahks.net>) | Find an Expert | American Association of Hip and Knee Surgeons |

### Walking Problems — 5673

Canonical source: [Walking Problems](<https://medlineplus.gov/walkingproblems.html>) (ID 5673).

Aliases: Gait Abnormalities (see-reference).

Groups: Bones, Joints and Muscles (10); Brain and Nerves (14). MeSH: Gait Disorders, Neurologic (D020233); Gait Ataxia (D020234); Gait Apraxia (D020235).

Curated related-topic: [Ankle Injuries and Disorders](<https://medlineplus.gov/ankleinjuriesanddisorders.html>) (ID 1612); [Balance Problems](<https://medlineplus.gov/balanceproblems.html>) (ID 5953); [Falls](<https://medlineplus.gov/falls.html>) (ID 1661); [Foot Injuries and Disorders](<https://medlineplus.gov/footinjuriesanddisorders.html>) (ID 508); [Hip Injuries and Disorders](<https://medlineplus.gov/hipinjuriesanddisorders.html>) (ID 287); [Knee Injuries and Disorders](<https://medlineplus.gov/kneeinjuriesanddisorders.html>) (ID 304); [Leg Injuries and Disorders](<https://medlineplus.gov/leginjuriesanddisorders.html>) (ID 509); [Movement Disorders](<https://medlineplus.gov/movementdisorders.html>) (ID 520).

Spanish equivalent: [Problemas para caminar](<https://medlineplus.gov/spanish/walkingproblems.html>) (ID 5674).

Resources: **32 site assignments**. Categories: Children: 8; Clinical Trials: 3; Diagnosis and Tests: 3; Encyclopedia: 1; Find an Expert: 2; Journal Articles: 1; Living With: 2; Older Adults: 1; Patient Handouts: 1; Specifics: 10; Start Here: 1; Teenagers: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Walking abnormalities](<https://medlineplus.gov/ency/article/003199.htm>) | Start Here, Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Gait Apraxia](<https://clinicaltrials.gov/search?cond=%22Gait+Apraxia%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Walking Problems](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Gait+Disorders%2C+Neurologic%22%5Bmajr%3Anoexp%5D+OR+%22Gait+Apraxia%22%5Bmajr%3Anoexp%5D+OR+%22Gait+Ataxia%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Find a Physical Therapist Near You](<https://www.choosept.com/find-a-pt>) | Find an Expert | American Physical Therapy Association |

### Osteoarthritis — 1250

Canonical source: [Osteoarthritis](<https://medlineplus.gov/osteoarthritis.html>) (ID 1250).

Aliases: Degenerative joint disease (also-called); OA (also-called); Osteoarthrosis (also-called); Degenerative Joint Disease (see-reference).

Groups: Older Adults (6); Bones, Joints and Muscles (10). MeSH: Osteoarthritis (D010003).

Curated related-topic: [Arthritis](<https://medlineplus.gov/arthritis.html>) (ID 23); [Rheumatoid Arthritis](<https://medlineplus.gov/rheumatoidarthritis.html>) (ID 1232).

Spanish equivalent: [Osteoartritis](<https://medlineplus.gov/spanish/osteoarthritis.html>) (ID 2065).

Resources: **36 site assignments**. Categories: Clinical Trials: 1; Diagnosis and Tests: 4; Encyclopedia: 7; Find an Expert: 3; Genetics: 1; Journal Articles: 1; Living With: 1; Patient Handouts: 1; Related Issues: 1; Specifics: 4; Start Here: 2; Statistics and Research: 2; Test Your Knowledge: 1; Treatments and Therapies: 8; Videos and Tutorials: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Osteoarthritis](<https://www.nia.nih.gov/health/osteoarthritis/osteoarthritis>) | Start Here | National Institute on Aging |
| [Osteoarthritis](<https://medlineplus.gov/ency/article/000423.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Osteoarthritis](<https://clinicaltrials.gov/search?cond=%22Osteoarthritis%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Osteoarthritis](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Osteoarthritis%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Arthritis Foundation: Local Office Directory](<https://www.arthritis.org/local-offices/>) | Find an Expert | Arthritis Foundation |

### Arthritis — 23

Canonical source: [Arthritis](<https://medlineplus.gov/arthritis.html>) (ID 23).

Aliases: Joint inflammation (also-called).

Groups: Bones, Joints and Muscles (10). MeSH: Arthritis (D001168).

Curated related-topic: [Ankylosing Spondylitis](<https://medlineplus.gov/ankylosingspondylitis.html>) (ID 143); [Gout](<https://medlineplus.gov/gout.html>) (ID 264); [Infectious Arthritis](<https://medlineplus.gov/infectiousarthritis.html>) (ID 5668); [Juvenile Arthritis](<https://medlineplus.gov/juvenilearthritis.html>) (ID 1292); [Osteoarthritis](<https://medlineplus.gov/osteoarthritis.html>) (ID 1250); [Psoriatic Arthritis](<https://medlineplus.gov/psoriaticarthritis.html>) (ID 6159); [Rheumatoid Arthritis](<https://medlineplus.gov/rheumatoidarthritis.html>) (ID 1232).

Spanish equivalent: [Artritis](<https://medlineplus.gov/spanish/arthritis.html>) (ID 1757).

Resources: **45 site assignments**. Categories: Clinical Trials: 1; Diagnosis and Tests: 5; Encyclopedia: 10; Find an Expert: 3; Genetics: 2; Journal Articles: 1; Living With: 3; Patient Handouts: 3; Related Issues: 3; Specifics: 8; Start Here: 3; Statistics and Research: 2; Test Your Knowledge: 1; Treatments and Therapies: 3.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Arthritis](<https://www.mayoclinic.org/diseases-conditions/arthritis/symptoms-causes/syc-20350772?p=1>) | Start Here | Mayo Foundation for Medical Education and Research |
| [Arthritis](<https://medlineplus.gov/ency/article/001243.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Arthritis](<https://clinicaltrials.gov/search?cond=%22Arthritis%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Arthritis](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Arthritis%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Arthritis Foundation: Local Office Directory](<https://www.arthritis.org/local-offices/>) | Find an Expert | Arthritis Foundation |

### Back Pain — 157

Canonical source: [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157).

Aliases: Backache (also-called); Lumbago (also-called); Low Back Pain (see-reference); Lumbago (see-reference); Pain, Back (see-reference).

Groups: Bones, Joints and Muscles (10); Brain and Nerves (14). MeSH: Back Pain (D001416).

Curated related-topic: [Back Injuries](<https://medlineplus.gov/backinjuries.html>) (ID 156); [Guide to Good Posture](<https://medlineplus.gov/guidetogoodposture.html>) (ID 6681); [Herniated Disk](<https://medlineplus.gov/herniateddisk.html>) (ID 284); [Pain](<https://medlineplus.gov/pain.html>) (ID 351); [Sciatica](<https://medlineplus.gov/sciatica.html>) (ID 1532); [Spinal Stenosis](<https://medlineplus.gov/spinalstenosis.html>) (ID 412); [Spine Injuries and Disorders](<https://medlineplus.gov/spineinjuriesanddisorders.html>) (ID 1505).

Spanish equivalent: [Dolor de espalda](<https://medlineplus.gov/spanish/backpain.html>) (ID 1766).

Resources: **49 site assignments**. Categories: Clinical Trials: 1; Diagnosis and Tests: 4; Encyclopedia: 16; Find an Expert: 4; Journal Articles: 1; Living With: 1; NIH MedlinePlus Magazine: 3; Patient Handouts: 11; Prevention and Risk Factors: 3; Related Issues: 1; Start Here: 2; Statistics and Research: 1; Test Your Knowledge: 1; Treatments and Therapies: 8; Videos and Tutorials: 1; Women: 2.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Back Pain](<https://www.mayoclinic.org/diseases-conditions/back-pain/symptoms-causes/syc-20369906?p=1>) | Start Here | Mayo Foundation for Medical Education and Research |
| [Back pain - returning to work](<https://medlineplus.gov/ency/patientinstructions/000517.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Low Back Pain](<https://clinicaltrials.gov/search?cond=%22Low+Back+Pain%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Back Pain](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Back+Pain%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Find a Physical Medicine and Rehabilitation Physician](<https://members.aapmr.org/AAPMR/AAPMR_FINDER.aspx>) | Find an Expert | American Academy of Physical Medicine and Rehabilitation |

### Sciatica — 1532

Canonical source: [Sciatica](<https://medlineplus.gov/sciatica.html>) (ID 1532).

Aliases: None.

Groups: Bones, Joints and Muscles (10); Brain and Nerves (14); Symptoms (31). MeSH: Sciatica (D012585).

Curated related-topic: [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157); [Herniated Disk](<https://medlineplus.gov/herniateddisk.html>) (ID 284); [Peripheral Nerve Disorders](<https://medlineplus.gov/peripheralnervedisorders.html>) (ID 1095).

Spanish equivalent: [Ciática](<https://medlineplus.gov/spanish/sciatica.html>) (ID 2137).

Resources: **14 site assignments**. Categories: Clinical Trials: 1; Diagnosis and Tests: 2; Encyclopedia: 3; Find an Expert: 2; Journal Articles: 1; Patient Handouts: 3; Related Issues: 1; Specifics: 2; Start Here: 1; Treatments and Therapies: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Sciatica](<https://www.mayoclinic.org/diseases-conditions/sciatica/symptoms-causes/syc-20377435?p=1>) | Start Here | Mayo Foundation for Medical Education and Research |
| [MRI and low back pain](<https://medlineplus.gov/ency/article/007493.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Sciatica](<https://clinicaltrials.gov/search?cond=%22Sciatica%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Sciatica](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Sciatica%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Find a Physical Therapist Near You](<https://www.choosept.com/find-a-pt>) | Find an Expert | American Physical Therapy Association |

### Herniated Disk — 284

Canonical source: [Herniated Disk](<https://medlineplus.gov/herniateddisk.html>) (ID 284).

Aliases: Bulging disk (also-called); Compressed disk (also-called); Herniated intervertebral disk (also-called); Herniated nucleus pulposus (also-called); Prolapsed disk (also-called); Ruptured disk (also-called); Slipped disk (also-called); Ruptured Disk (see-reference); Slipped Disk (see-reference).

Groups: Bones, Joints and Muscles (10). MeSH: Intervertebral Disc Displacement (D007405).

Curated related-topic: [Back Pain](<https://medlineplus.gov/backpain.html>) (ID 157).

Spanish equivalent: [Hernia de disco](<https://medlineplus.gov/spanish/herniateddisk.html>) (ID 2153).

Resources: **36 site assignments**. Categories: Clinical Trials: 1; Diagnosis and Tests: 5; Encyclopedia: 15; Find an Expert: 1; Genetics: 1; Images: 3; Journal Articles: 1; Patient Handouts: 3; Reference Desk: 1; Related Issues: 2; Specifics: 3; Start Here: 2; Treatments and Therapies: 4; Videos and Tutorials: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Bulging Disk vs. Herniated Disk: What's the Difference?](<https://www.mayoclinic.org/diseases-conditions/herniated-disk/expert-answers/bulging-disk/faq-20058428?p=1>) | Start Here | Mayo Foundation for Medical Education and Research |
| [Herniated disk](<https://medlineplus.gov/ency/article/000442.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Intervertebral Disc Displacement](<https://clinicaltrials.gov/search?cond=%22Intervertebral+Disk+Displacement%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Herniated Disk](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Intervertebral+Disc+Displacement%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [National Institute of Arthritis and Musculoskeletal and Skin Diseases](<https://www.niams.nih.gov/>) | Find an Expert | National Institute of Arthritis and Musculoskeletal and Skin Diseases |

### A1C — 6308

Canonical source: [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308).

Aliases: Glycohemoglobin (also-called); HbA1C (also-called); Hemoglobin A1C test (also-called); HbA1c (see-reference); Hemoglobin A1c (see-reference).

Groups: Diagnostic Tests and Screenings (25); Diabetes Mellitus (45). MeSH: Glycated Hemoglobin (D006442).

Curated related-topic: None.

Spanish equivalent: [Prueba de hemoglobina glicosilada (HbA1c)](<https://medlineplus.gov/spanish/a1c.html>) (ID 6309).

Resources: **8 site assignments**. Categories: Clinical Trials: 1; Journal Articles: 1; Learn More: 5; Patient Handouts: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [A1C Test and Diabetes](<https://www.niddk.nih.gov/health-information/diagnostic-tests/a1c-test>) | Learn More | National Institute of Diabetes and Digestive and Kidney Diseases |
| [A1C test](<https://medlineplus.gov/ency/article/003640.htm>) | Patient Handouts | Medical Encyclopedia |
| [ClinicalTrials.gov: Hemoglobin A1C](<https://clinicaltrials.gov/search?intr=%22Hemoglobin+A1C%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [A1C](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Glycated+Hemoglobin%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |

### Blood Glucose — 6121

Canonical source: [Blood Glucose](<https://medlineplus.gov/bloodglucose.html>) (ID 6121).

Aliases: Blood sugar (also-called); Blood Sugar (see-reference); Continuous Glucose Monitoring (see-reference); Glucose (see-reference).

Groups: Endocrine System (23); Metabolic Problems (40); Diabetes Mellitus (45). MeSH: Blood Glucose (D001786).

Curated related-topic: [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308); [Carbohydrates](<https://medlineplus.gov/carbohydrates.html>) (ID 3670); [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4); [Diabetes in Children and Teens](<https://medlineplus.gov/diabetesinchildrenandteens.html>) (ID 6026); [Diabetes Medicines](<https://medlineplus.gov/diabetesmedicines.html>) (ID 4935); [Diabetic Diet](<https://medlineplus.gov/diabeticdiet.html>) (ID 3048); [Hyperglycemia](<https://medlineplus.gov/hyperglycemia.html>) (ID 6125); [Hypoglycemia](<https://medlineplus.gov/hypoglycemia.html>) (ID 1264); [Prediabetes](<https://medlineplus.gov/prediabetes.html>) (ID 5784).

Spanish equivalent: [Glucosa en la sangre](<https://medlineplus.gov/spanish/bloodglucose.html>) (ID 6122).

Resources: **38 site assignments**. Categories: Children: 3; Clinical Trials: 2; Diagnosis and Tests: 5; Encyclopedia: 9; Find an Expert: 3; Genetics: 3; Journal Articles: 1; Living With: 5; Patient Handouts: 7; Specifics: 2; Start Here: 3; Teenagers: 2; Test Your Knowledge: 1; Women: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Blood Sugar Testing: Why, When and How](<https://www.mayoclinic.org/diseases-conditions/diabetes/in-depth/blood-sugar/art-20046628?p=1>) | Start Here | Mayo Foundation for Medical Education and Research |
| [A1C test](<https://medlineplus.gov/ency/article/003640.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Blood Glucose](<https://clinicaltrials.gov/search?cond=%22Blood+Glucose%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Blood Glucose](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Blood+Glucose%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Search Diabetes Educator Directory](<https://nf01.diabeteseducator.org/eweb/DynamicPage.aspx?Site=AADE&WebCode=AADEDiaEduDirectory>) | Find an Expert | Association of Diabetes Care and Education Specialists |

### Diabetes — 4

Canonical source: [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4).

Aliases: DM (also-called); Diabetes mellitus (also-called); Diabetes Mellitus (see-reference); Sugar Diabetes (see-reference).

Groups: Older Adults (6); Endocrine System (23); Metabolic Problems (40); Diabetes Mellitus (45). MeSH: Diabetes Mellitus (D003920).

Curated related-topic: [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308); [Blood Glucose](<https://medlineplus.gov/bloodglucose.html>) (ID 6121); [Diabetes and Kidney Disease](<https://medlineplus.gov/diabetesandkidneydisease.html>) (ID 1559); [Diabetes and Pregnancy](<https://medlineplus.gov/diabetesandpregnancy.html>) (ID 1408); [Diabetes Complications](<https://medlineplus.gov/diabetescomplications.html>) (ID 4567); [Diabetes in Children and Teens](<https://medlineplus.gov/diabetesinchildrenandteens.html>) (ID 6026); [Diabetes Medicines](<https://medlineplus.gov/diabetesmedicines.html>) (ID 4935); [Diabetes Type 1](<https://medlineplus.gov/diabetestype1.html>) (ID 1339); [Diabetes Type 2](<https://medlineplus.gov/diabetestype2.html>) (ID 5930); [Diabetic Diet](<https://medlineplus.gov/diabeticdiet.html>) (ID 3048); [Diabetic Eye Problems](<https://medlineplus.gov/diabeticeyeproblems.html>) (ID 1340); [Diabetic Foot](<https://medlineplus.gov/diabeticfoot.html>) (ID 1341); [Diabetic Heart Disease](<https://medlineplus.gov/diabeticheartdisease.html>) (ID 6011); [Diabetic Nerve Problems](<https://medlineplus.gov/diabeticnerveproblems.html>) (ID 1465); [Hyperglycemia](<https://medlineplus.gov/hyperglycemia.html>) (ID 6125); [Hypoglycemia](<https://medlineplus.gov/hypoglycemia.html>) (ID 1264); [Islet Cell Transplantation](<https://medlineplus.gov/isletcelltransplantation.html>) (ID 1511); [Pancreas Transplantation](<https://medlineplus.gov/pancreastransplantation.html>) (ID 352); [Prediabetes](<https://medlineplus.gov/prediabetes.html>) (ID 5784).

Spanish equivalent: [Diabetes](<https://medlineplus.gov/spanish/diabetes.html>) (ID 1851).

Resources: **79 site assignments**. Categories: Clinical Trials: 1; Diagnosis and Tests: 9; Encyclopedia: 20; Find an Expert: 3; Genetics: 11; Journal Articles: 1; Living With: 12; Older Adults: 2; Patient Handouts: 11; Prevention and Risk Factors: 2; Reference Desk: 1; Related Issues: 8; Start Here: 2; Statistics and Research: 4; Test Your Knowledge: 1; Treatments and Therapies: 1; Women: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Managing Diabetes](<https://www.niddk.nih.gov/health-information/diabetes/overview/managing-diabetes>) | Start Here | National Institute of Diabetes and Digestive and Kidney Diseases |
| [A1C test](<https://medlineplus.gov/ency/article/003640.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Diabetes Mellitus](<https://clinicaltrials.gov/search?cond=%22Diabetes+Mellitus%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Diabetes](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Diabetes+Mellitus%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Search Diabetes Educator Directory](<https://nf01.diabeteseducator.org/eweb/DynamicPage.aspx?Site=AADE&WebCode=AADEDiaEduDirectory>) | Find an Expert | Association of Diabetes Care and Education Specialists |

### Diabetes Type 2 — 5930

Canonical source: [Diabetes Type 2](<https://medlineplus.gov/diabetestype2.html>) (ID 5930).

Aliases: Type 2 Diabetes (also-called); Non-Insulin Dependent Diabetes Mellitus (see-reference); Sugar Diabetes (see-reference); Type II Diabetes (see-reference).

Groups: Endocrine System (23); Diabetes Mellitus (45). MeSH: Diabetes Mellitus, Type 2 (D003924).

Curated related-topic: [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308); [Blood Glucose](<https://medlineplus.gov/bloodglucose.html>) (ID 6121); [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4); [Diabetes Complications](<https://medlineplus.gov/diabetescomplications.html>) (ID 4567); [Diabetes in Children and Teens](<https://medlineplus.gov/diabetesinchildrenandteens.html>) (ID 6026); [Diabetes Medicines](<https://medlineplus.gov/diabetesmedicines.html>) (ID 4935); [Diabetes Type 1](<https://medlineplus.gov/diabetestype1.html>) (ID 1339); [Diabetic Diet](<https://medlineplus.gov/diabeticdiet.html>) (ID 3048); [How to Prevent Diabetes](<https://medlineplus.gov/howtopreventdiabetes.html>) (ID 6661); [Prediabetes](<https://medlineplus.gov/prediabetes.html>) (ID 5784).

Spanish equivalent: [Diabetes tipo 2](<https://medlineplus.gov/spanish/diabetestype2.html>) (ID 5931).

Resources: **54 site assignments**. Categories: Clinical Trials: 1; Diagnosis and Tests: 6; Encyclopedia: 7; Find an Expert: 3; Genetics: 2; Health Check Tools: 1; Journal Articles: 1; Living With: 9; Older Adults: 2; Patient Handouts: 5; Prevention and Risk Factors: 2; Related Issues: 7; Specifics: 2; Start Here: 3; Statistics and Research: 3; Test Your Knowledge: 1; Treatments and Therapies: 3; Women: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Type 2 Diabetes](<https://www.mayoclinic.org/diseases-conditions/type-2-diabetes/symptoms-causes/syc-20351193?p=1>) | Start Here | Mayo Foundation for Medical Education and Research |
| [A1C test](<https://medlineplus.gov/ency/article/003640.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Diabetes Mellitus, Type 2](<https://clinicaltrials.gov/search?cond=%22Diabetes+Mellitus,+Type+2%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Diabetes Type 2](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Diabetes+Mellitus%2C+Type+2%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Search Diabetes Educator Directory](<https://nf01.diabeteseducator.org/eweb/DynamicPage.aspx?Site=AADE&WebCode=AADEDiaEduDirectory>) | Find an Expert | Association of Diabetes Care and Education Specialists |

### Prediabetes — 5784

Canonical source: [Prediabetes](<https://medlineplus.gov/prediabetes.html>) (ID 5784).

Aliases: Insulin Resistance (see-reference).

Groups: Endocrine System (23); Diabetes Mellitus (45). MeSH: Prediabetic State (D011236).

Curated related-topic: [A1C](<https://medlineplus.gov/a1c.html>) (ID 6308); [Diabetes](<https://medlineplus.gov/diabetes.html>) (ID 4); [Diabetes in Children and Teens](<https://medlineplus.gov/diabetesinchildrenandteens.html>) (ID 6026); [Diabetes Type 2](<https://medlineplus.gov/diabetestype2.html>) (ID 5930); [How to Prevent Diabetes](<https://medlineplus.gov/howtopreventdiabetes.html>) (ID 6661); [Metabolic Syndrome](<https://medlineplus.gov/metabolicsyndrome.html>) (ID 3528).

Spanish equivalent: [Prediabetes](<https://medlineplus.gov/spanish/prediabetes.html>) (ID 5785).

Resources: **27 site assignments**. Categories: Clinical Trials: 2; Diagnosis and Tests: 5; Encyclopedia: 3; Find an Expert: 2; Health Check Tools: 1; Journal Articles: 1; Living With: 1; Patient Handouts: 3; Prevention and Risk Factors: 3; Related Issues: 4; Start Here: 3; Statistics and Research: 1; Treatments and Therapies: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [About Prediabetes](<https://www.heart.org/en/health-topics/diabetes/about-diabetes/about-prediabetes>) | Start Here | American Heart Association |
| [Blood sugar test](<https://medlineplus.gov/ency/article/003482.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Insulin Resistance](<https://clinicaltrials.gov/search?cond=%22Insulin+Resistance%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Prediabetes](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Prediabetic+State%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [American Diabetes Association](<https://diabetes.org/>) | Find an Expert | American Diabetes Association |

### Headache — 273

Canonical source: [Headache](<https://medlineplus.gov/headache.html>) (ID 273).

Aliases: Cluster Headache (see-reference); Tension Headache (see-reference); Vascular Headache (see-reference).

Groups: Brain and Nerves (14); Symptoms (31). MeSH: Headache (D006261); Headache Disorders (D020773).

Curated related-topic: [Migraine](<https://medlineplus.gov/migraine.html>) (ID 3157).

Spanish equivalent: [Dolor de cabeza](<https://medlineplus.gov/spanish/headache.html>) (ID 1933).

Resources: **37 site assignments**. Categories: Children: 3; Clinical Trials: 5; Diagnosis and Tests: 1; Encyclopedia: 6; Find an Expert: 1; Journal Articles: 1; Living With: 2; Patient Handouts: 5; Prevention and Risk Factors: 1; Specifics: 10; Start Here: 2; Symptoms: 1; Teenagers: 1; Treatments and Therapies: 1; Women: 2.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Headache](<https://www.mayoclinic.org/symptoms/headache/basics/definition/SYM-20050800?p=1>) | Start Here | Mayo Foundation for Medical Education and Research |
| [Cluster headache](<https://medlineplus.gov/ency/article/000786.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Cluster Headache](<https://clinicaltrials.gov/search?cond=%22Cluster+Headache%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Headache](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Headache%22%5Bmajr%3Anoexp%5D+OR+%22Headache+Disorders%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [National Institute of Neurological Disorders and Stroke](<https://www.ninds.nih.gov/>) | Find an Expert | National Institute of Neurological Disorders and Stroke |

### Migraine — 3157

Canonical source: [Migraine](<https://medlineplus.gov/migraine.html>) (ID 3157).

Aliases: Vascular Headache (see-reference).

Groups: Brain and Nerves (14). MeSH: Migraine Disorders (D008881).

Curated related-topic: [Headache](<https://medlineplus.gov/headache.html>) (ID 273).

Spanish equivalent: [Migraña](<https://medlineplus.gov/spanish/migraine.html>) (ID 3158).

Resources: **44 site assignments**. Categories: Children: 1; Clinical Trials: 1; Diagnosis and Tests: 2; Encyclopedia: 3; Find an Expert: 1; Genetics: 4; Journal Articles: 1; Living With: 3; NIH MedlinePlus Magazine: 7; Patient Handouts: 2; Related Issues: 4; Specifics: 3; Start Here: 3; Statistics and Research: 1; Teenagers: 1; Treatments and Therapies: 8; Women: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Headache](<https://www.ninds.nih.gov/health-information/disorders/headache>) | Start Here | National Institute of Neurological Disorders and Stroke |
| [Managing migraines at home](<https://medlineplus.gov/ency/patientinstructions/000420.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Migraine Disorders](<https://clinicaltrials.gov/search?cond=%22Migraine+Disorders%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Migraine](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Migraine+Disorders%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [National Institute of Neurological Disorders and Stroke](<https://www.ninds.nih.gov/>) | Find an Expert | National Institute of Neurological Disorders and Stroke |

### Allergy — 24

Canonical source: [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24).

Aliases: Hypersensitivity (also-called); Allergic Rhinitis (see-reference); Hypersensitivity (see-reference).

Groups: Ear, Nose and Throat (16); Immune System (22). MeSH: Hypersensitivity (D006967).

Curated related-topic: [Anaphylaxis](<https://medlineplus.gov/anaphylaxis.html>) (ID 6257); [Asthma](<https://medlineplus.gov/asthma.html>) (ID 2); [Drug Reactions](<https://medlineplus.gov/drugreactions.html>) (ID 5603); [Food Allergy](<https://medlineplus.gov/foodallergy.html>) (ID 1256); [Hay Fever](<https://medlineplus.gov/hayfever.html>) (ID 4746); [Hives](<https://medlineplus.gov/hives.html>) (ID 289); [Indoor Air Pollution](<https://medlineplus.gov/indoorairpollution.html>) (ID 1537); [Latex Allergy](<https://medlineplus.gov/latexallergy.html>) (ID 1439); [Sinusitis](<https://medlineplus.gov/sinusitis.html>) (ID 404).

Spanish equivalent: [Alergia](<https://medlineplus.gov/spanish/allergy.html>) (ID 1734).

Resources: **75 site assignments**. Categories: Children: 7; Clinical Trials: 2; Diagnosis and Tests: 3; Encyclopedia: 21; Find an Expert: 4; Genetics: 2; Health Check Tools: 1; Patient Handouts: 8; Prevention and Risk Factors: 2; Reference Desk: 1; Related Issues: 7; Specifics: 12; Start Here: 2; Statistics and Research: 1; Teenagers: 1; Test Your Knowledge: 2; Treatments and Therapies: 6; Videos and Tutorials: 2.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Allergic Reactions](<https://www.aaaai.org/tools-for-the-public/conditions-library/allergies/allergic-reactions>) | Start Here | American Academy of Allergy, Asthma, and Immunology |
| [Allergic reactions](<https://medlineplus.gov/ency/article/000005.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Hypersensitivity](<https://clinicaltrials.gov/search?cond=%22Hypersensitivity%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Find an Allergist](<https://allergist.aaaai.org/find/>) | Find an Expert | American Academy of Allergy, Asthma, and Immunology |

### Hay Fever — 4746

Canonical source: [Hay Fever](<https://medlineplus.gov/hayfever.html>) (ID 4746).

Aliases: Pollen allergy (also-called); Allergic Rhinitis (see-reference); Pollen Allergy (see-reference); Seasonal Allergies (see-reference).

Groups: Ear, Nose and Throat (16); Immune System (22). MeSH: Rhinitis, Allergic, Seasonal (D006255).

Curated related-topic: [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24).

Spanish equivalent: [Rinitis alérgica](<https://medlineplus.gov/spanish/hayfever.html>) (ID 4747).

Resources: **34 site assignments**. Categories: Children: 1; Clinical Trials: 2; Diagnosis and Tests: 2; Encyclopedia: 7; Find an Expert: 4; Health Check Tools: 1; Journal Articles: 1; NIH MedlinePlus Magazine: 1; Patient Handouts: 4; Prevention and Risk Factors: 2; Related Issues: 2; Specifics: 1; Start Here: 3; Test Your Knowledge: 1; Treatments and Therapies: 5; Videos and Tutorials: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Allergic Rhinitis (Allergies)](<https://familydoctor.org/condition/allergic-rhinitis-allergies/>) | Start Here | American Academy of Family Physicians |
| [Allergic rhinitis](<https://medlineplus.gov/ency/article/000813.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Rhinitis, Allergic, Perennial](<https://clinicaltrials.gov/search?cond=%22Rhinitis,+Allergic,+Perennial%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Hay Fever](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Rhinitis%2C+Allergic%2C+Seasonal%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Find an Allergist](<https://allergist.aaaai.org/find/>) | Find an Expert | American Academy of Allergy, Asthma, and Immunology |

### Food Allergy — 1256

Canonical source: [Food Allergy](<https://medlineplus.gov/foodallergy.html>) (ID 1256).

Aliases: Allergy, Food (see-reference); Egg Allergy (see-reference); Milk Allergy (see-reference); Nut Allergy (see-reference); Peanut Allergy (see-reference); Shellfish Allergy (see-reference); Wheat Allergy (see-reference).

Groups: Immune System (22); Food and Nutrition (26). MeSH: Food Hypersensitivity (D005512).

Curated related-topic: [Allergy](<https://medlineplus.gov/allergy.html>) (ID 24); [Anaphylaxis](<https://medlineplus.gov/anaphylaxis.html>) (ID 6257); [Celiac Disease](<https://medlineplus.gov/celiacdisease.html>) (ID 897); [Lactose Intolerance](<https://medlineplus.gov/lactoseintolerance.html>) (ID 898).

Spanish equivalent: [Alergia a los alimentos](<https://medlineplus.gov/spanish/foodallergy.html>) (ID 1908).

Resources: **65 site assignments**. Categories: Children: 8; Clinical Trials: 6; Diagnosis and Tests: 3; Encyclopedia: 4; Find an Expert: 2; Health Check Tools: 1; Journal Articles: 1; Living With: 8; NIH MedlinePlus Magazine: 1; Patient Handouts: 4; Related Issues: 2; Specifics: 14; Start Here: 5; Statistics and Research: 4; Teenagers: 3; Test Your Knowledge: 1; Treatments and Therapies: 1; Videos and Tutorials: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Food Allergies](<https://familydoctor.org/condition/food-allergies/>) | Start Here | American Academy of Family Physicians |
| [Allergy testing - skin](<https://medlineplus.gov/ency/article/003519.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [ClinicalTrials.gov: Egg Hypersensitivity](<https://clinicaltrials.gov/search?cond=%22Egg+Hypersensitivity%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Food Allergy](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Food+Hypersensitivity%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Find an Allergist](<https://allergist.aaaai.org/find/>) | Find an Expert | American Academy of Allergy, Asthma, and Immunology |

### Anaphylaxis — 6257

Canonical source: [Anaphylaxis](<https://medlineplus.gov/anaphylaxis.html>) (ID 6257).

Aliases: Anaphylactic shock (also-called).

Groups: Immune System (22). MeSH: Anaphylaxis (D000707).

Curated related-topic: None.

Spanish equivalent: [Anafilaxia](<https://medlineplus.gov/spanish/anaphylaxis.html>) (ID 6258).

Resources: **9 site assignments**. Categories: Clinical Trials: 1; Journal Articles: 1; Learn More: 7.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Anaphylaxis](<https://www.aaaai.org/tools-for-the-public/conditions-library/allergies/anaphylaxis>) | Learn More | American Academy of Allergy, Asthma, and Immunology |
| [ClinicalTrials.gov: Anaphylaxis](<https://clinicaltrials.gov/search?cond=%22Anaphylaxis%22&aggFilters=status:not%20rec>) | Clinical Trials | National Institutes of Health |
| [Anaphylaxis](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Anaphylaxis%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |

### Choosing a Doctor or Health Care Service — 1433

Canonical source: [Choosing a Doctor or Health Care Service](<https://medlineplus.gov/choosingadoctororhealthcareservice.html>) (ID 1433).

Aliases: None.

Groups: Health System (32); Personal Health Issues (41). MeSH: Delivery of Health Care (D003695); Physician-Patient Relations (D010817).

Curated related-topic: [Health Facilities](<https://medlineplus.gov/healthfacilities.html>) (ID 486); [Patient Safety](<https://medlineplus.gov/patientsafety.html>) (ID 616).

Spanish equivalent: [Elección de un doctor o plan médico](<https://medlineplus.gov/spanish/choosingadoctororhealthcareservice.html>) (ID 1820).

Resources: **26 site assignments**. Categories: Children: 1; Encyclopedia: 3; Find an Expert: 7; Health Check Tools: 1; Journal Articles: 1; Older Adults: 1; Patient Handouts: 2; Specifics: 8; Start Here: 3; Statistics and Research: 1; Teenagers: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Choosing a Doctor: Quick Tips](<https://odphp.health.gov/myhealthfinder/doctor-visits/regular-checkups/choosing-doctor-quick-tips>) | Start Here | Office of Disease Prevention and Health Promotion |
| [Choosing a doctor and hospital for your cancer treatment](<https://medlineplus.gov/ency/patientinstructions/000928.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [Choosing a Doctor or Health Care Service](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Delivery+of+Health+Care%22%5Bmajr%3Anoexp%5D+OR+%22Physician-Patient+Relations%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+1+Year%22+%5Bedat%5D+AND+%28patient+education+handout%5Bpt%5D+OR+guideline%5Bpt%5D+OR+clinical+trial%5Bpt%5D%29++NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [DocInfo](<https://www.docinfo.org/#/search/query>) | Find an Expert | Federation of State Medical Boards |

### Health Facilities — 486

Canonical source: [Health Facilities](<https://medlineplus.gov/healthfacilities.html>) (ID 486).

Aliases: Health care facilities (also-called); Hospitals (see-reference).

Groups: Health System (32). MeSH: Health Facilities (D006268); Hospitals (D006761).

Curated related-topic: [Hospice Care](<https://medlineplus.gov/hospicecare.html>) (ID 1355); [Nursing Homes](<https://medlineplus.gov/nursinghomes.html>) (ID 1354); [Patient Safety](<https://medlineplus.gov/patientsafety.html>) (ID 616).

Spanish equivalent: [Centros de salud](<https://medlineplus.gov/spanish/healthfacilities.html>) (ID 1934).

Resources: **18 site assignments**. Categories: Encyclopedia: 7; Find an Expert: 3; Health Check Tools: 1; Journal Articles: 1; Patient Handouts: 5; Related Issues: 2; Specifics: 1; Statistics and Research: 3.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Choosing a skilled nursing and rehabilitation facility](<https://medlineplus.gov/ency/patientinstructions/000436.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [Health Facilities](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Health+Facilities%22%5Bmajr%3Anoexp%5D+OR+%22Hospitals%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+2+Years%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [Find a Clinic](<https://nafcclinics.org/find-clinic/>) | Find an Expert | National Association of Free and Charitable Clinics |

### Clinical Trials — 192

Canonical source: [Clinical Trials](<https://medlineplus.gov/clinicaltrials.html>) (ID 192).

Aliases: None.

Groups: Personal Health Issues (41). MeSH: Clinical Trials as Topic (D002986).

Curated related-topic: [Understanding Medical Research](<https://medlineplus.gov/understandingmedicalresearch.html>) (ID 3894).

Spanish equivalent: [Estudios clínicos](<https://medlineplus.gov/spanish/clinicaltrials.html>) (ID 1826).

Resources: **29 site assignments**. Categories: Children: 2; Encyclopedia: 1; Find an Expert: 1; Journal Articles: 1; NIH MedlinePlus Magazine: 1; Patient Handouts: 2; Reference Desk: 1; Related Issues: 4; Specifics: 11; Start Here: 3; Videos and Tutorials: 2; Women: 1.

| Resource (source URL) | Category | Organization attribution |
| --- | --- | --- |
| [Are Clinical Studies for You?](<https://www.cc.nih.gov/participate/studies>) | Start Here | National Institutes of Health, Clinical Center |
| [A guide to clinical trials for cancer](<https://medlineplus.gov/ency/patientinstructions/000823.htm>) | Patient Handouts, Encyclopedia | Medical Encyclopedia |
| [Clinical Trials](<https://pubmed.ncbi.nlm.nih.gov/?term=%22Clinical+Trials+as+Topic%22%5Bmajr%3Anoexp%5D+AND+humans%5Bmh%5D+AND+english%5Bla%5D+AND+%22last+2+Years%22+%5Bedat%5D+NOT+%28letter%5Bpt%5D+OR+case+reports%5Bpt%5D+OR+editorial%5Bpt%5D+OR+comment%5Bpt%5D%29+AND+free+full+text%5Bsb%5D+>) | Journal Articles | Not supplied |
| [National Institutes of Health](<https://www.nih.gov/>) | Find an Expert | National Institutes of Health |

## Journey design recommendation

Show a topic result and its provenance, then provide explicit actions: Read overview, Explore related topics, View education resources, View research links, or Read in Spanish. Distinguish general organizations from actual directory links inside Find an Expert. An unanswered care question should lead to an honest data-limit explanation, never a fabricated doctor, facility, treatment, trial, or referral. Summary-only retrieval is insufficient to generate comprehensive answers about the linked documents, because their bodies are absent.
