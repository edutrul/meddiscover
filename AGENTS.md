# Agent instructions

Read PLAN.md and this file before work. Approved phase: **Phase 01 — Project Foundation only**. Phase 02 and later require explicit owner approval.

- Inspect repository status, existing issues and documentation before changes. Preserve unrelated edits.
- Every implementation task needs an issue first. Use `med-{issue}-{short-title}` branches and `MED-{issue}: Title` PR titles. Never commit to main after bootstrap.
- Use English in all repository artifacts. Prefix shell commands with `rtk` (use `rtk proxy` for unsupported commands).
- Prefer Drupal core/contributed capabilities and simple, reproducible DDEV infrastructure.
- Validate actual behavior and export changed Drupal configuration. Never fabricate validation or relevance results.
- Keep raw XML, exhaustive generated evidence, credentials, patient data, private interview/client materials and local settings outside Git.
- Preserve historical discovery reports and original selection. Describe new scope in separate documents.
- Do not download third-party resource bodies. Attribute MedlinePlus. Treat the application as an educational discovery POC.
- Do not implement search, embeddings, Solr, migration or UI enhancements during Phase 01.
- Merge only with explicit owner authorization and passing required checks. Stop at phase completion and report evidence, PRs, limitations and blockers.
