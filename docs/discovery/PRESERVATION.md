# Discovery preservation and approved implementation scope

The historical reports and scripts already existed in the medDiscover checkout on 2026-11-24. The original local discovery workspace was unavailable. No source project was modified or deleted. Reports retain their historical bilingual recommendations; PLAN.md now governs implementation.

The original 30-topic manifest is preserved unchanged. `implementation-selection.json` records the 24 English topics separately, with recalculated counts from the original XML. This is selection evidence, not a content migration.

The raw XML and exhaustive `dataset-profile.json` (approximately 84 MB of generated evidence) remain local and ignored. `verification.json` is generated local evidence. Public-clone readers must obtain the original snapshot and regenerate evidence before running historical verification. Source hash is recorded in the selection manifest. Formal DTD validation and external URL availability checks remain outstanding.

## Publication assessment

Reviewed 2026-11-24:

- https://medlineplus.gov/xml.html explicitly permits downloading/using XML data and requires attribution.
- https://medlineplus.gov/about/using/usingcontent/ identifies health-topic summaries as public domain, while other licensed MedlinePlus content has different restrictions.
- Resource URLs and metadata do not grant rights to reproduce destination page bodies, images, encyclopedia or licensed material. None is downloaded here.

Public artifacts contain analysis, scripts and selection metadata. Raw XML stays excluded even after this policy review. Reassess rights and sanitize HTML before Phase 02 publication.

Source: MedlinePlus, National Library of Medicine.
