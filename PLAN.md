# medDiscover — Master Implementation Plan

> Intelligent Healthcare Search & Discovery  
> A Four-Layer Approach to Intelligent Healthcare Experiences

**Project Key:** MED  
**Machine Name:** med  
**Project Status:** Data Discovery Completed  
**Development Approach:** Incremental, Evidence-Driven, Backend-First  
**Repository:** https://github.com/edutrul/meddiscover

---

## 1. Project Overview

medDiscover is an intelligent healthcare search and discovery proof of concept built with Drupal, PostgreSQL, pgvector, Apache Solr, and AI-assisted retrieval technologies.

The project demonstrates how structured healthcare information can be transformed into an actionable, measurable, and continuously optimized digital experience.

The project follows a four-layer framework:

1. Experience
2. Data
3. Activation
4. Optimization

Search is not the end goal. It is the entry point to a measurable, actionable, and continuously optimized digital healthcare experience.

### 1.1 Primary Objectives

- Demonstrate semantic search using PostgreSQL and pgvector.
- Compare lexical, semantic, and hybrid retrieval strategies.
- Leverage existing healthcare data relationships.
- Enrich search results using structured metadata.
- Provide contextual recommendations and relevant healthcare resources.
- Implement grounded conversational search.
- Demonstrate measurable user engagement and activation.
- Evaluate alternative search providers.
- Avoid unnecessary infrastructure and complexity.

### 1.2 Development Philosophy

- Data before technology.
- Functionality before visual design.
- Evidence before assumptions.
- Deterministic logic before AI when appropriate.
- Simple architecture before distributed architecture.
- Drupal Core and contributed modules before custom implementations.
- Incremental delivery before large implementations.
- Measurable outcomes before premature optimization.

---

## 2. Project Configuration

| Property | Value |
|---|---|
| Product name | medDiscover |
| Project key | MED |
| Machine name | med |
| GitHub owner | edutrul |
| GitHub repository | meddiscover |
| Repository visibility | Public |
| Local directory | meddiscover/ |
| Default branch | main |
| Development environment | DDEV |
| CMS | Drupal 11 |
| Database | PostgreSQL |
| Vector extension | pgvector |
| Lexical search | Apache Solr |
| Hybrid ranking | Reciprocal Rank Fusion |
| Project language | English |
| Custom module prefix | med_ |

All source code, documentation, GitHub Issues, Pull Requests, commit messages, interface labels, and technical discussions recorded in the repository must be written in English.

---

## 3. Scope Definition

### 3.1 Initial Scope

The initial corpus consists of 24 English-language health topics selected during Data Discovery.

The application will progressively support:

- Basic semantic search.
- Structured search result enrichment.
- Lexical search.
- Hybrid search.
- Conversational search.
- Related content recommendations.
- Contextual healthcare resources.
- Contextual calls to action.
- Search evaluation.
- Privacy-preserving analytics.

### 3.2 Out of Initial Scope

- Multilingual search.
- Spanish content ingestion.
- Real patient records.
- Electronic health records.
- Patient authentication.
- Clinical diagnosis.
- Personalized medical advice.
- Appointment booking integrations.
- Native physician directories.
- Native hospital directories.
- Customer Data Platforms.
- Graph databases.
- Separate vector databases.
- Microservices.
- Advanced frontend styling.
- Production-scale infrastructure.
- HNSW and IVFFlat indexes.

Spanish language mappings may be preserved as source metadata for future enhancements.

---

## 4. GitHub Project Management

### 4.1 Project Management Strategy

Use native GitHub functionality:

- Milestones represent implementation phases.
- Issues represent individual tasks.
- Branches represent implementation work.
- Pull Requests represent reviewable changes.
- Labels classify work.

Do not introduce GitHub Projects, external project management platforms, or additional workflow complexity unless explicitly approved.

### 4.2 Milestones

Create the following milestones:

| Milestone | Name |
|---|---|
| Phase 00 | Data Discovery |
| Phase 01 | Project Foundation |
| Phase 02 | Content Modeling & Migration |
| Phase 03 | Basic Semantic Search |
| Phase 04 | Search Enrichment |
| Phase 05 | Lexical & Hybrid Search |
| Phase 06 | Conversational AI |
| Phase 07 | Activation |
| Phase 08 | Optimization |
| Phase 09 | UI/UX Enhancement |
| Phase 10 | External Search Providers |
| Phase 11 | Delivery |

Phase 00 has already been completed locally. Record the existing deliverables before closing its milestone.

Create all milestones initially.

Create individual implementation issues only for the currently approved phase.

### 4.3 Labels

Maintain a minimal label set.

| Label | Purpose |
|---|---|
| feature | New functionality |
| bug | Defects and regressions |
| infrastructure | Environment and infrastructure |
| documentation | Documentation and governance |
| testing | Automated testing and evaluation |
| research | Technical investigation |
| blocked | Unresolved dependencies |

Do not create separate phase labels because milestones already provide phase classification.

Avoid redundant labels.

### 4.4 GitHub Issue Requirements

Every task must have a GitHub Issue before implementation begins.

Each issue must contain:

- Clear title.
- Context.
- Objective.
- Scope.
- Acceptance criteria.
- Dependencies, when applicable.
- Out-of-scope items, when applicable.
- Validation requirements.

Assign the appropriate milestone and labels.

Use GitHub-generated issue numbers.

Do not invent issue numbers.

### 4.5 Issue Template

    ## Context

    Explain why this task is necessary.

    ## Objective

    Describe the expected outcome.

    ## Scope

    Describe what must be implemented.

    ## Acceptance Criteria

    - [ ] Requirement 1
    - [ ] Requirement 2
    - [ ] Requirement 3

    ## Dependencies

    List dependencies or blockers.

    ## Out of Scope

    Describe excluded functionality.

    ## Validation

    Describe how the implementation must be verified.

### 4.6 Branch Naming Convention

Every implementation branch must follow:

    med-{github_issue_number}-{short-title}

Examples:

    med-1-initialize-project
    med-2-configure-ddev
    med-3-enable-pgvector
    med-12-import-health-topics
    med-18-implement-semantic-search

These numbers are examples only.

Use lowercase letters and hyphens.

### 4.7 Pull Request Convention

Pull Request titles must follow:

    MED-{github_issue_number}: Short descriptive title

Example:

    MED-18: Implement semantic search

Every implementation PR must reference its corresponding GitHub Issue.

Use:

    Closes #18

Only use closing keywords when the PR fully satisfies the issue acceptance criteria.

### 4.8 Pull Request Template

    ## Summary

    Describe the implementation.

    ## Related Issue

    Closes #ISSUE_NUMBER

    ## Changes

    - Change 1
    - Change 2

    ## Validation

    - [ ] Acceptance criteria satisfied
    - [ ] Relevant tests passed
    - [ ] Code quality checks passed
    - [ ] Documentation updated
    - [ ] No secrets committed

    ## Risks and Limitations

    Describe known limitations.

    ## Testing Instructions

    Provide reproducible validation steps.

### 4.9 Development Workflow

For every implementation task:

1. Identify the approved milestone.
2. Create or select a GitHub Issue.
3. Review its acceptance criteria.
4. Synchronize with the main branch.
5. Create the issue-specific branch.
6. Implement the required changes.
7. Run relevant tests and quality checks.
8. Update documentation.
9. Commit changes.
10. Push the branch.
11. Create a Pull Request.
12. Request review.
13. Merge only after approval and successful required checks.
14. Verify that the issue is closed.

Research tasks that do not modify the repository do not require a Pull Request.

Never commit directly to main after repository initialization.

---

## 5. AI Agent Governance

Codex and Claude Code must follow the same development rules.

### 5.1 Shared Instructions

Create AGENTS.md as the primary source of agent instructions.

Create CLAUDE.md referencing AGENTS.md.

Do not maintain conflicting instructions across multiple files.

### 5.2 Agent Responsibilities

Agents must:

- Read PLAN.md before starting work.
- Read AGENTS.md before making changes.
- Identify the current approved phase.
- Inspect existing repository state.
- Review existing documentation.
- Check existing GitHub Issues before creating duplicates.
- Verify dependencies and compatibility.
- Follow the issue and branch conventions.
- Implement only the approved scope.
- Validate all changes.
- Report actual results.
- Document blockers and assumptions.
- Stop at phase completion.

### 5.3 Agent Restrictions

Agents must not:

- Advance to another phase without explicit approval.
- Implement unapproved functionality.
- Introduce unnecessary dependencies.
- Replace working architecture without justification.
- Fabricate test results.
- Fabricate search relevance scores.
- Fabricate healthcare information.
- Commit credentials or API keys.
- Commit sensitive information.
- Automatically merge Pull Requests without authorization.
- Publish source datasets without verifying redistribution rights.
- Delete existing discovery documentation.
- Overwrite unrelated user changes.
- Execute destructive operations without approval.

### 5.4 Phase Completion Report

At the end of each phase, provide:

- Completed GitHub Issues.
- Created Pull Requests.
- Implementation summary.
- Validation results.
- Architecture decisions.
- Known limitations.
- Remaining blockers.
- Recommended next phase.

Wait for explicit approval before proceeding.

---

## 6. Repository Structure

The proposed repository structure is:

    meddiscover/
    ├── .ddev/
    ├── .github/
    │   ├── ISSUE_TEMPLATE/
    │   ├── workflows/
    │   └── pull_request_template.md
    ├── config/
    │   └── sync/
    ├── data/
    │   └── raw/
    ├── docs/
    │   ├── discovery/
    │   ├── architecture/
    │   ├── evaluation/
    │   └── delivery/
    ├── scripts/
    │   └── discovery/
    ├── web/
    │   └── modules/
    │       └── custom/
    ├── AGENTS.md
    ├── CLAUDE.md
    ├── CONTRIBUTING.md
    ├── PLAN.md
    ├── README.md
    ├── composer.json
    ├── composer.lock
    └── .gitignore

The final structure must follow Drupal and DDEV conventions.

Do not create empty modules or unnecessary directories merely to match this proposal.

### 6.1 Existing Discovery Assets

The existing discovery project is located at:

    the original local discovery workspace

Inspect and preserve its relevant assets:

    data/raw/
    docs/discovery/
    scripts/discovery/

The new project directory is:

    meddiscover/

Copy required assets without deleting or modifying the original discovery project.

Keep large generated analysis files and raw source data out of the public repository until publication requirements are verified.

---

# Phase 00 — Data Discovery

**Status: Completed locally**

## Objective

Understand the healthcare dataset before implementing the application.

## Source Dataset

MedlinePlus, National Library of Medicine.

    data/raw/mplus_topics_2026-11-24.xml

Source:

    https://medlineplus.gov/xml/mplus_topics_2026-11-24.xml

## Discovery Results

The existing discovery analysis reported:

- 2,033 health topics.
- 1,017 English health topics.
- 1,016 Spanish health topics.
- 52,562 resource assignments.
- 33,042 unique resource URLs.
- 6,289 curated topic relationships.
- 14,893 relationships through summary hyperlinks.
- 44 medical groups.
- Approximately 99.80% English MeSH coverage.
- No duplicate topic IDs.

The XML was confirmed to be well-formed.

Formal DTD validation and external URL availability checks remain outstanding.

## Existing Deliverables

    docs/discovery/01-dataset-profile.md
    docs/discovery/02-data-quality.md
    docs/discovery/03-relationships.md
    docs/discovery/04-search-opportunities.md
    docs/discovery/05-user-journeys.md
    docs/discovery/06-four-layer-framework.md
    docs/discovery/07-impact-effort-matrix.md
    docs/discovery/08-minimal-dataset.md
    docs/discovery/09-data-gaps-and-risks.md
    docs/discovery/EXECUTIVE-SUMMARY.md
    docs/discovery/minimal-selection.json

Preserve the original discovery reports.

Do not rewrite historical findings to reflect subsequent scope changes.

## Approved Corpus

The original proposed selection contains 30 topics:

- 24 English topics.
- 6 Spanish topics.

The approved implementation scope is English-only.

Import only the 24 English records.

Recalculate resource counts for the English-only selection rather than reusing statistics from the original bilingual selection.

Preserve the original selection manifest and document the implementation-specific selection separately.

### Selected Topics

| Source ID | Health Topic |
|---|---|
| 304 | Knee Injuries and Disorders |
| 3924 | Knee Replacement |
| 1250 | Osteoarthritis |
| 23 | Arthritis |
| 5673 | Walking Problems |
| 417 | Sprains and Strains |
| 157 | Back Pain |
| 1532 | Sciatica |
| 284 | Herniated Disk |
| 6308 | A1C |
| 6121 | Blood Glucose |
| 4 | Diabetes |
| 5930 | Diabetes Type 2 |
| 6661 | How to Prevent Diabetes |
| 5784 | Prediabetes |
| 273 | Headache |
| 3157 | Migraine |
| 24 | Allergy |
| 4746 | Hay Fever |
| 1256 | Food Allergy |
| 6257 | Anaphylaxis |
| 1433 | Choosing a Doctor or Health Care Service |
| 486 | Health Facilities |
| 192 | Clinical Trials |

## Acceptance Criteria

- [x] Dataset analyzed.
- [x] Relationships identified.
- [x] Search opportunities identified.
- [x] User journeys documented.
- [x] Minimal corpus proposed.
- [x] Conceptual relational model established.
- [ ] Discovery assets preserved in the new project.
- [ ] English-only implementation selection documented.
- [ ] Discovery milestone recorded and closed.

---

# Phase 01 — Project Foundation

## Objective

Establish a reproducible Drupal development environment and lightweight project governance.

## Phase 01.1 — Repository Initialization

- Inspect the target local directory.
- Verify GitHub authentication and repository permissions.
- Verify whether edutrul/meddiscover already exists.
- Initialize the public repository if it does not exist.
- Establish main as the default branch.
- Create the initial project documentation.
- Preserve existing discovery assets.
- Configure .gitignore.
- Create milestones.
- Configure the minimal label set.
- Create issue and Pull Request templates.

The initial repository bootstrap may require a minimal initial commit before the issue-based PR workflow becomes available.

Document this exception.

## Phase 01.2 — DDEV

- Verify the installed DDEV version.
- Verify Docker availability.
- Initialize the DDEV project.
- Configure the project name as meddiscover.
- Configure the Drupal document root.
- Select a supported PHP version.
- Select a supported PostgreSQL version.
- Document local startup instructions.

## Phase 01.3 — Drupal 11

- Install Drupal 11 using the recommended Composer project structure.
- Install Drush.
- Configure PostgreSQL as Drupal's primary database.
- Verify Drupal installation.
- Verify administrative access.
- Configure reproducible configuration management.
- Export initial Drupal configuration.

Use a minimal Drupal installation profile appropriate for the project.

## Phase 01.4 — PostgreSQL and pgvector

- Configure pgvector within the same PostgreSQL database used by Drupal.
- Verify compatibility with the selected PostgreSQL version.
- Configure the extension through reproducible DDEV infrastructure.
- Enable the vector extension.
- Verify vector data types.
- Execute a basic cosine-distance query.
- Document the configuration.

Do not introduce a separate vector database.

Do not configure HNSW or IVFFlat.

## Phase 01.5 — Code Quality and CI

Establish lightweight quality checks appropriate for Drupal.

Evaluate:

- PHP_CodeSniffer.
- Drupal coding standards.
- PHPStan.
- PHPUnit.
- Composer validation.
- GitHub Actions.

Introduce only the checks required for the current implementation.

## Acceptance Criteria

- [ ] Public GitHub repository exists.
- [ ] Governance documentation exists.
- [ ] GitHub milestones and labels exist.
- [ ] DDEV starts successfully.
- [ ] Drupal 11 is operational.
- [ ] Drupal uses PostgreSQL.
- [ ] pgvector is enabled in the same database.
- [ ] A basic vector-distance query succeeds.
- [ ] Initial Drupal configuration is exported.
- [ ] Relevant quality checks pass.
- [ ] No credentials or restricted data are committed.

## Out of Scope

- Apache Solr.
- Embedding generation.
- Semantic search.
- Custom frontend.
- RAG.
- External search providers.

---

# Phase 02 — Content Modeling & Migration

## Objective

Create a structured healthcare content foundation using Drupal entities and migrate the approved English-language corpus.

## Phase 02.1 — Conceptual Data Model

The conceptual relational model includes:

| Concept | Relationship |
|---|---|
| Health Topic | Primary entity |
| Topic Alias | One-to-many |
| Topic Embedding | One-to-many |
| Topic Resource | One-to-many |
| Topic Relationship | Many-to-many through relationship records |
| Medical Group | Many-to-many |
| MeSH Descriptor | Many-to-many |
| Resource Category | Many-to-many |

The conceptual tables are:

    health_topics
    topic_aliases
    topic_embeddings
    topic_relationships
    topic_resources
    medical_groups
    topic_groups
    mesh_descriptors
    topic_mesh
    resource_categories
    topic_resource_categories

These names describe the conceptual data model.

Do not manually duplicate Drupal-managed content into these tables.

Translate the model into Drupal entities, fields, taxonomies, entity references, and appropriate structured storage.

## Phase 02.2 — Drupal Content Modeling

Evaluate Drupal Core and contributed capabilities before creating custom entities.

Preserve the following information:

### Health Topic

- Original MedlinePlus source ID.
- Title.
- Meta description.
- Full summary.
- Sanitized HTML summary.
- Plain-text search representation.
- Language.
- Canonical source URL.
- Source creation date.
- Dataset snapshot date.
- Source attribution.

### Alternative Terminology

- also-called.
- see-reference.
- Original term.
- Term type.

Do not treat every alternative term as an unambiguous synonym.

### Medical Groups

- Original group ID.
- Group label.
- Source URL.

### MeSH

- Descriptor ID.
- Descriptor label.
- Qualifier ID, when available.
- Qualifier label, when available.

### Relationships

- Source topic.
- Target topic.
- Relationship type.
- Relationship provenance.
- Target title and URL when the target is outside the selected corpus.

Distinguish:

- Explicit related-topic relationships.
- Summary hyperlinks.
- Language mappings.
- Inferred associations.

Do not represent inferred relationships as clinically verified associations.

### External Resources

- Resource title.
- Resource URL.
- Resource categories.
- Organizations.
- Standard descriptions.
- Language-mapped URL, when available.

Preserve multiple resource categories and organizations where provided.

## Phase 02.3 — Module Evaluation

Evaluate relevant Drupal modules, including:

- Migrate.
- Migrate Plus.
- Migrate Tools.
- Drupal AI ecosystem modules.
- Key or equivalent credential management.

Verify current compatibility, maintenance status, and actual requirements before installation.

Do not install modules merely because they appear in this plan.

## Phase 02.4 — Data Extraction

- Read the original MedlinePlus XML.
- Verify its checksum against the discovery manifest.
- Select the 24 approved English topic IDs.
- Extract required metadata.
- Preserve original identifiers.
- Normalize textual values.
- Sanitize HTML.
- Preserve source URLs.
- Generate a reproducible migration input if necessary.

Use the original XML as the source of truth.

## Phase 02.5 — Data Migration

Use Drupal Migrate API.

Recommended migration sequence:

1. Medical groups.
2. MeSH descriptors and qualifiers.
3. Resource categories.
4. Health topics.
5. Alternative terminology.
6. External resource metadata.
7. Topic relationships.

Adjust the sequence according to the approved Drupal entity model.

Resolve references after their target entities exist.

Preserve out-of-corpus relationships as external references rather than creating invalid entity references.

Support repeatable and idempotent migrations.

## Phase 02.6 — Data Validation

Validate:

- Imported topic counts.
- Source ID uniqueness.
- Metadata completeness.
- Resource assignments.
- Entity references.
- External relationship targets.
- HTML sanitization.
- English-only scope.
- Migration repeatability.

## Acceptance Criteria

- [ ] Drupal content model documented.
- [ ] All 24 English topics imported.
- [ ] Original source IDs preserved.
- [ ] Alternative terminology preserved.
- [ ] Medical groups and MeSH metadata preserved.
- [ ] External resources preserved.
- [ ] Explicit relationships preserved.
- [ ] Out-of-corpus references handled correctly.
- [ ] HTML sanitized.
- [ ] Migration can be rerun without duplicates.
- [ ] Imported content is manageable through Drupal.
- [ ] Validation results documented.

## Out of Scope

- Embedding generation.
- Search interfaces.
- Search result enrichment.
- Recommendation engine.
- Custom visual design.

---

# Phase 03 — Basic Semantic Search

**MVP 1**

## Objective

Deliver the first functional search experience directly in Drupal using PostgreSQL and pgvector.

The user must be able to enter a natural-language query and receive actual semantic search results.

## Phase 03.1 — Embedding Provider Evaluation

Evaluate suitable embedding models and Drupal integrations.

Consider:

- Embedding quality.
- Model dimensions.
- Cost.
- Privacy.
- Availability.
- Drupal compatibility.
- PostgreSQL integration.
- Reproducibility.

Prefer existing Drupal integrations when they satisfy the requirements.

Document the selected model and its dimensions.

Do not assume a specific model or vector dimension before evaluation.

## Phase 03.2 — Embedding Content Preparation

Create a deterministic textual representation for each health topic.

Start with:

- Title.
- Meta description.
- Plain-text summary.

Evaluate whether aliases and MeSH terms should be included in the embedding input.

Document the exact input construction.

Start with one embedding per health topic unless model input limits or evaluation results justify chunking.

The storage design may support multiple embeddings per topic for future use.

## Phase 03.3 — Embedding Generation

- Generate embeddings for the approved corpus.
- Associate embeddings with Drupal content.
- Preserve model identity.
- Preserve vector dimensions.
- Preserve content hashes.
- Support regeneration when source content changes.
- Avoid generating duplicate embeddings.
- Protect provider credentials.

## Phase 03.4 — Vector Storage

Store vectors in the same PostgreSQL database used by Drupal.

The conceptual embedding record contains:

- Embedding ID.
- Health topic reference.
- Embedded content or reproducible content reference.
- Embedding vector.
- Model identifier.
- Content hash.
- Generation timestamp.

Use the selected Drupal integration's storage model when appropriate.

## Phase 03.5 — Semantic Retrieval

Implement a Drupal search service.

Requirements:

- Accept a natural-language query.
- Generate its embedding using the same compatible model.
- Calculate cosine distance.
- Retrieve nearest neighbors.
- Return associated health topics.
- Support a configurable result limit.
- Support a configurable relevance threshold.
- Return actual similarity values.
- Handle empty results.
- Handle provider failures.

Use exact nearest-neighbor search.

Do not create approximate vector indexes.

Conceptual query:

    SELECT
        topic_id,
        1 - (embedding <=> :query_vector) AS similarity
    FROM topic_embeddings
    ORDER BY embedding <=> :query_vector
    LIMIT 5;

Adapt the query to the actual storage schema and apply parameter binding.

Calibrate the relevance threshold using evaluation queries rather than choosing an arbitrary value.

## Phase 03.6 — Basic Drupal Search Interface

Create a minimal functional Drupal page.

Proposed route:

    /search/semantic

Use:

- Drupal Form API.
- Drupal services.
- Drupal render arrays or basic Twig.
- The existing Drupal theme.

The page must contain:

- Product name.
- Search text field.
- Search button.
- Search results.
- Result title.
- Basic description.
- Source attribution.
- Actual similarity score, when useful for technical demonstration.
- Empty-result state.
- Error state.

Conceptual interface:

    medDiscover

    [ My knees hurt when I walk        ] [ Search ]

    Search Results

    Knee Injuries and Disorders
    Educational information about knee injuries.

    Osteoarthritis
    Information about degenerative joint disease.

Do not implement a custom theme.

Do not implement advanced styling.

Do not display related content or healthcare resource recommendations yet.

## Phase 03.7 — Initial Evaluation

Use synthetic evaluation queries such as:

    My knees hurt when I walk
    What is HbA1c?
    What is backache?
    Information about blood sugar
    Why do my joints hurt?
    Information about seasonal allergies

Include unrelated queries to evaluate the empty-result behavior.

Record:

- Returned topic IDs.
- Result rankings.
- Similarity values.
- Retrieval latency.
- Embedding generation latency.
- Expected relevant topics.
- Observed failures.

Do not fabricate relevance scores or claim clinical correctness.

## Acceptance Criteria

- [ ] Embedding provider selected and documented.
- [ ] All 24 topics have valid embeddings.
- [ ] Embeddings are stored in PostgreSQL.
- [ ] Query embeddings are generated correctly.
- [ ] Exact vector retrieval works.
- [ ] Drupal search service works.
- [ ] Search form works in the browser.
- [ ] Search results display actual imported content.
- [ ] Similarity values are calculated rather than fabricated.
- [ ] Empty-result behavior is implemented.
- [ ] Provider failures are handled.
- [ ] Initial evaluation is documented.
- [ ] Relevant automated tests pass.

## MVP 1 Completion Definition

A user can open the Drupal website, enter a natural-language healthcare query, submit the form, and receive actual pgvector search results.

Nothing more is required for MVP 1.

## Out of Scope

- Apache Solr.
- Hybrid search.
- Generative AI.
- RAG.
- Related content sections.
- Healthcare resource sections.
- Dynamic CTAs.
- Recommendation engine.
- Advanced styling.
- Analytics dashboard.

---

# Phase 04 — Search Enrichment

**MVP 2**

## Objective

Transform basic semantic search results into enriched healthcare discovery results using existing structured data.

## Phase 04.1 — Result Enrichment Service

After retrieving a health topic, use its identifier to retrieve additional structured information.

Enrichment sources:

- Alternative terminology.
- Medical groups.
- MeSH descriptors.
- Explicit related topics.
- Summary hyperlinks.
- External healthcare resources.

Use Drupal entity references and structured queries.

Do not perform additional vector searches when deterministic relationships already provide the required information.

## Phase 04.2 — Related Content

Implement related content retrieval.

Prioritize:

1. Explicit related-topic relationships.
2. Resolved summary hyperlinks.
3. Shared MeSH descriptors.
4. Shared medical groups, when appropriate.

Clearly distinguish explicit relationships from inferred associations.

Deduplicate related results.

Preserve relationship provenance.

## Phase 04.3 — Healthcare Resources

Retrieve existing external resources associated with the selected topic.

Examples:

- Patient Handouts.
- Learn More.
- Find an Expert.
- Clinical Trials.
- Journal Articles.
- Diagnosis and Tests.

Preserve original URLs and organization attribution.

Do not invent resource availability.

## Phase 04.4 — Enriched Drupal Interface

Extend the existing semantic search page with:

- Explore Health Information.
- Related Content.
- Explore Healthcare Resources.

Use the existing Drupal theme.

Keep visual styling minimal.

Dedicated recommendation ranking and dynamic business CTAs belong to Phase 07.

## Acceptance Criteria

- [ ] Search results include relevant structured metadata.
- [ ] Related topics are retrieved through existing relationships.
- [ ] Relationship provenance is preserved.
- [ ] External healthcare resources are displayed.
- [ ] Out-of-corpus topics link to their original sources.
- [ ] Duplicate recommendations are prevented.
- [ ] No unnecessary LLM calls are introduced.
- [ ] Enrichment behavior is covered by tests.

---

# Phase 05 — Lexical & Hybrid Search

**MVP 3**

## Objective

Introduce lexical retrieval and combine it with semantic retrieval.

## Phase 05.1 — Apache Solr

- Configure Solr using DDEV.
- Verify Drupal Search API compatibility.
- Configure Search API Solr.
- Configure English-language analysis.
- Establish reproducible Solr configuration.

## Phase 05.2 — Lexical Search

Index:

- Health topic titles.
- Meta descriptions.
- Plain-text summaries.
- Alternative terminology.
- MeSH labels.
- Relevant medical groups.

Configure:

- Field boosting.
- Exact term matching.
- Alias matching.
- Ambiguity handling.
- Appropriate tokenization.

Proposed route:

    /search/lexical

## Phase 05.3 — Hybrid Search

Implement a retrieval strategy that combines Solr and pgvector.

Use canonical topic identifiers to reconcile results.

Implement Reciprocal Rank Fusion.

Conceptual formula:

    RRF(document) = SUM(1 / (k + rank))

Document and evaluate the selected value of k.

Do not combine raw Solr scores and cosine similarity values without a justified normalization strategy.

Proposed route:

    /search/hybrid

## Phase 05.4 — Shared Search Architecture

Provide consistent result structures across:

- Semantic retrieval.
- Lexical retrieval.
- Hybrid retrieval.

Reuse the enrichment service.

Avoid duplicating content rendering logic.

## Phase 05.5 — Comparative Evaluation

Evaluate all retrieval strategies against the same corpus and reviewed query set.

Include:

- Exact terminology.
- Synonyms.
- Ambiguous terminology.
- Natural-language paraphrases.
- Unrelated queries.

Measure:

- Precision at K.
- Recall at K, where relevance judgments are available.
- Mean Reciprocal Rank.
- Zero-result rate.
- Retrieval latency.

Document observed trade-offs.

Do not assume hybrid retrieval is always superior.

## Acceptance Criteria

- [ ] Solr runs through DDEV.
- [ ] Lexical search works.
- [ ] Semantic search remains functional.
- [ ] Hybrid search works.
- [ ] RRF is tested.
- [ ] Results are deduplicated.
- [ ] Independent Drupal search routes exist.
- [ ] All strategies use the same corpus.
- [ ] Comparative evaluation is documented.

---

# Phase 06 — Conversational AI

**MVP 4**

## Objective

Provide grounded conversational healthcare discovery using retrieved information.

## Phase 06.1 — LLM Provider Evaluation

Evaluate compatible LLM providers and Drupal integrations.

Consider:

- Cost.
- Privacy.
- Latency.
- Grounding capabilities.
- Structured output.
- Drupal compatibility.

Keep provider credentials outside version control.

## Phase 06.2 — Retrieval-Augmented Generation

Implement a RAG pipeline:

1. Receive user query.
2. Retrieve relevant health topics.
3. Evaluate evidence sufficiency.
4. Prepare grounded context.
5. Generate an educational response.
6. Return citations and source links.
7. Attach relevant structured results.

Use the evaluated retrieval strategy.

Do not assume hybrid retrieval is the best option without supporting results.

## Phase 06.3 — Grounding and Safety

Requirements:

- Use retrieved source information.
- Preserve source attribution.
- Provide verifiable citations.
- Avoid unsupported clinical claims.
- Avoid diagnosis.
- Avoid personalized treatment recommendations.
- Abstain when evidence is insufficient.
- Do not generate invented physicians or facilities.
- Treat retrieved content as data, not executable instructions.

For potentially urgent medical situations, provide an appropriate safety-oriented response rather than relying on similarity ranking.

## Phase 06.4 — Conversational Drupal Interface

Proposed route:

    /search/conversational

Provide:

- Natural-language input.
- Generated educational response.
- Source citations.
- Supporting health topics.
- Clear limitations.

Keep the interface functional and minimally styled.

## Acceptance Criteria

- [ ] Grounded conversational search works.
- [ ] Responses include source references.
- [ ] Unsupported queries trigger abstention.
- [ ] No fabricated citations.
- [ ] No unsupported medical diagnosis.
- [ ] Provider failures are handled.
- [ ] Safety and grounding behavior are evaluated.

---

# Phase 07 — Activation

## Objective

Transform discovered healthcare information into meaningful user actions.

## Phase 07.1 — Recommendation Engine

Implement deterministic recommendation rules using:

- Curated relationships.
- Resource categories.
- MeSH descriptors.
- Medical groups.
- Search context.

Separate content relevance from clinical recommendations.

Explain why a recommendation is displayed.

## Phase 07.2 — Contextual Calls to Action

Generate contextual CTAs using available resource metadata.

Examples:

| Resource Category | CTA |
|---|---|
| Find an Expert | Explore Directory |
| Clinical Trials | Explore Clinical Trials |
| Patient Handouts | Read Patient Resources |
| Journal Articles | Explore Research |
| Learn More | Learn More |

Use source-provided URLs.

Do not introduce fictional providers or booking functionality.

Do not create commercial targeting based on sensitive health queries.

## Phase 07.3 — Business Rules

Create configurable activation rules.

Rules should determine:

- Eligible resource categories.
- CTA labels.
- Display priority.
- Maximum recommendations.
- Required attribution.
- Appropriate external-navigation behavior.

Prefer deterministic business rules before introducing AI-based personalization.

## Acceptance Criteria

- [ ] Contextual recommendations work.
- [ ] Dynamic CTAs work.
- [ ] Recommendations are explainable.
- [ ] CTA destinations use actual source URLs.
- [ ] External navigation is clearly identified.
- [ ] No clinical or commercial claims are invented.
- [ ] Activation rules are tested.

---

# Phase 08 — Optimization

## Objective

Measure and improve the effectiveness of search and activation.

## Phase 08.1 — Search Analytics

Design privacy-preserving analytics.

Potential events:

    search_performed
    search_no_results
    search_result_clicked
    related_content_clicked
    recommendation_shown
    recommendation_clicked
    external_resource_clicked
    cta_clicked

Do not store raw health-related search queries by default.

Avoid collecting identifiers, topic-level histories, destination URLs, or other information that could reveal individual health interests.

Use aggregate metrics and appropriate retention controls.

## Phase 08.2 — Search Performance

Measure:

- Embedding generation latency.
- Retrieval latency.
- Enrichment latency.
- LLM response latency.
- End-to-end response time.
- Error rates.

## Phase 08.3 — Search Quality

Maintain a reviewed synthetic evaluation dataset.

Track:

- Precision at K.
- Recall at K.
- Mean Reciprocal Rank.
- Zero-result rate.
- Ranking regressions.

## Phase 08.4 — Activation Metrics

Measure aggregate:

- Recommendation impressions.
- Recommendation engagement.
- CTA impressions.
- CTA clicks.
- External resource engagement.

Do not represent external directory clicks as completed appointments or healthcare conversions.

## Phase 08.5 — Continuous Improvement

Use evaluation results to identify:

- Ranking improvements.
- Metadata improvements.
- Relevance threshold adjustments.
- Resource prioritization.
- Search experience improvements.

## Acceptance Criteria

- [ ] Analytics event contract documented.
- [ ] Privacy-preserving metrics implemented.
- [ ] Search performance measured.
- [ ] Search quality evaluated.
- [ ] Activation engagement measured.
- [ ] No sensitive query logging by default.
- [ ] Optimization recommendations supported by evidence.

---

# Phase 09 — UI/UX Enhancement

## Objective

Improve the visual experience after core functionality has been validated.

## Scope

- Product branding.
- Search page styling.
- Responsive layouts.
- Accessible search components.
- Result cards.
- Related content components.
- Healthcare resource components.
- CTA components.
- Conversational interface improvements.
- Analytics visualization.
- Loading states.
- Error states.
- Empty-result states.

## Design Principles

- Accessibility.
- Clarity.
- Consistency.
- Mobile responsiveness.
- Progressive enhancement.
- Minimal JavaScript.
- Drupal-native rendering where practical.

Evaluate Drupal Single Directory Components when appropriate.

Do not redesign working backend services merely to support visual changes.

## Acceptance Criteria

- [ ] Responsive interface.
- [ ] Accessible search experience.
- [ ] Consistent visual components.
- [ ] Clear source attribution.
- [ ] Functional recommendations and CTAs.
- [ ] Search functionality remains unchanged.
- [ ] No performance regressions.

---

# Phase 10 — External Search Providers

## Objective

Demonstrate provider flexibility and evaluate alternative search technologies.

## Phase 10.1 — Provider Abstraction

Evaluate the existing search service architecture.

Introduce a shared provider contract when justified by multiple implementations.

Potential providers:

- PostgreSQL pgvector.
- Apache Solr.
- Hybrid retrieval.
- Algolia.
- SearchStax.
- Jullie.

Maintain a consistent result contract.

Avoid unnecessary abstraction before multiple providers require it.

## Phase 10.2 — Algolia

- Verify sandbox or developer account availability.
- Review current capabilities.
- Index the approved corpus.
- Implement retrieval.
- Compare results and developer experience.
- Document limitations and costs.

## Phase 10.3 — SearchStax

Subject to account access.

- Verify developer or sandbox access.
- Evaluate available APIs.
- Evaluate Drupal integration.
- Compare against the existing Solr implementation.

## Phase 10.4 — Jullie

Subject to account access.

- Verify product access.
- Review integration capabilities.
- Evaluate the approved corpus.
- Document results and limitations.

## Acceptance Criteria

- [ ] Available external providers evaluated.
- [ ] Provider capabilities documented.
- [ ] Integration costs documented.
- [ ] Search results compared where practical.
- [ ] Existing search functionality remains operational.
- [ ] Unavailable providers are documented as blocked.

Unavailable external providers must not block delivery of the core application.

---

# Phase 11 — Delivery

## Objective

Deliver a reproducible and demonstrable healthcare search proof of concept.

## Phase 11.1 — Documentation

Complete:

- README.md.
- Installation instructions.
- Architecture documentation.
- Data model documentation.
- Migration instructions.
- Search strategy documentation.
- Evaluation reports.
- Known limitations.
- Security and privacy considerations.
- Future enhancements.

## Phase 11.2 — Four-Layer Framework Presentation

### Experience

- Semantic search.
- Lexical search.
- Hybrid search.
- Conversational search.
- Enriched healthcare discovery.

### Data

- MedlinePlus.
- Drupal.
- PostgreSQL.
- pgvector.
- Apache Solr.
- Structured relationships.
- MeSH metadata.

### Activation

- Related content.
- Contextual recommendations.
- Healthcare resource discovery.
- Dynamic CTAs.

### Optimization

- Search quality evaluation.
- Performance metrics.
- Engagement metrics.
- Continuous improvement.

## Phase 11.3 — Demonstration Scenarios

Prepare reproducible scenarios:

1. Exact medical terminology: HbA1c.
2. Alternative terminology: Backache.
3. Ambiguous terminology: Vascular Headache.
4. Natural-language search: My knees hurt when I walk.
5. Conversational discovery: I have knee pain. What should I do?
6. Research discovery: Clinical trials for diabetes.
7. Healthcare resources: Explore a doctor directory.
8. Comparative retrieval: Lexical vs. Semantic vs. Hybrid.
9. Activation: Contextual resource CTA.
10. Optimization: Search performance and relevance metrics.

## Phase 11.4 — Loom Demonstration

Record a concise demonstration covering:

- Business problem.
- Data Discovery findings.
- Architecture decisions.
- Semantic search.
- Enriched results.
- Hybrid retrieval.
- Conversational search.
- Activation.
- Optimization.
- Four-layer framework alignment.

Clearly distinguish implemented functionality from future enhancements.

## Acceptance Criteria

- [ ] Repository documentation complete.
- [ ] Installation reproducible.
- [ ] Demonstration scenarios validated.
- [ ] Architecture documented.
- [ ] Evaluation results available.
- [ ] Known limitations documented.
- [ ] Loom demonstration recorded.
- [ ] Public repository contains no credentials or unauthorized data.

---

## 7. Target Architecture

    MedlinePlus XML
          |
          v
    Drupal Migrate API
          |
          v
    Drupal 11
          |
          v
    PostgreSQL
    ├── Drupal content
    ├── Metadata
    ├── Relationships
    ├── External resources
    └── pgvector embeddings
          |
          +----------------------+
          |                      |
          v                      v
    Semantic Search         Apache Solr
       pgvector             Lexical Search
          |                      |
          +----------+-----------+
                     |
                     v
                Hybrid Search
                     RRF
                     |
                     v
             Result Enrichment
                     |
                     v
             Conversational AI
                     |
                     v
                 Drupal UI
                     |
          +----------+-----------+
          |                      |
          v                      v
      Activation            Optimization
    Recommendations          Analytics
    Contextual CTAs          Evaluation

The diagram represents the target architecture.

Components must be introduced incrementally according to their approved phases.

---

## 8. MVP Delivery Strategy

| MVP | Milestone | Deliverable |
|---|---|---|
| MVP 1 | Phase 03 | Basic Drupal semantic search |
| MVP 2 | Phase 04 | Enriched search results |
| MVP 3 | Phase 05 | Lexical and hybrid search |
| MVP 4 | Phase 06 | Grounded conversational search |
| MVP 5 | Phases 07–08 | Activation and optimization |
| MVP 6 | Phase 09 | Enhanced visual experience |

External search provider integrations are supplementary.

Each MVP must be independently functional.

Do not postpone working functionality until the complete target architecture is implemented.

---

## 9. Security, Privacy and Data Governance

Because this repository is public and the project concerns healthcare information:

- Never store real patient data.
- Never commit API credentials.
- Never expose provider secrets.
- Never publish private interview materials.
- Never publish prospective client information.
- Verify MedlinePlus redistribution requirements.
- Preserve source attribution.
- Verify rights for selected summaries and external content.
- Do not download or reproduce third-party resource bodies without authorization.
- Sanitize imported HTML.
- Validate external URLs before public delivery.
- Use parameterized database queries.
- Protect against prompt injection in retrieved content.
- Avoid sensitive health-query logging.
- Clearly identify the application as an educational discovery POC.
- Do not present the application as a medical diagnosis system.

Required source attribution:

    Source: MedlinePlus, National Library of Medicine.

---

## 10. Architecture Decision Records

Document important technical decisions under:

    docs/architecture/

Recommended ADR topics:

- Drupal as the central CMS.
- PostgreSQL as the primary database.
- pgvector within the Drupal database.
- English-only initial corpus.
- Exact vector retrieval.
- Embedding model selection.
- Drupal content modeling.
- Migration architecture.
- Solr lexical retrieval.
- Reciprocal Rank Fusion.
- Deterministic recommendations.
- Privacy-preserving analytics.
- External search provider integration.

Each ADR should include:

- Context.
- Decision.
- Alternatives.
- Rationale.
- Consequences.
- Status.

Create ADRs when decisions are made.

Do not generate speculative ADRs for unapproved technologies.

---

## 11. Definition of Done

An implementation issue is complete when:

- Its acceptance criteria are satisfied.
- Implementation matches the approved scope.
- Relevant tests pass.
- Relevant code quality checks pass.
- Documentation is updated.
- Configuration changes are exported when applicable.
- No credentials or sensitive data are introduced.
- A corresponding Pull Request exists when repository changes are required.
- Required review and approval are completed.
- The GitHub Issue is closed appropriately.

A phase is complete when:

- Its required issues are completed.
- Its acceptance criteria are satisfied.
- Its deliverables are reproducible.
- Known limitations are documented.
- A phase completion report is provided.
- The project owner approves phase completion.

---

## 12. Initial Agent Execution Instructions

When starting work on this project:

1. Read this entire plan.
2. Inspect the existing discovery project at:

       the original local discovery workspace

3. Inspect the intended project directory:

       meddiscover/

4. Verify GitHub authentication and access.
5. Verify whether the target repository already exists.
6. Inspect the existing discovery documentation.
7. Identify files that must be preserved.
8. Identify files that must remain local or excluded from version control.
9. Verify dataset redistribution requirements before publication.
10. Identify available local development tools.
11. Report any blockers.
12. Bootstrap the approved project governance and repository.
13. Create the GitHub milestones and minimal labels.
14. Record Phase 00 completion.
15. Create actionable GitHub Issues for Phase 01 only.
16. Implement Phase 01 using the established issue, branch, and PR workflow.
17. Validate the environment.
18. Provide the Phase 01 completion report.
19. Stop and wait for explicit approval.

Do not implement Phase 02 or subsequent phases until authorized.

The immediate implementation target is Phase 01 — Project Foundation.

The first product delivery target is Phase 03 — Basic Semantic Search.

---

## 13. Project Success Statement

medDiscover demonstrates that intelligent healthcare experiences do not require AI at every step.

Structured data, deterministic relationships, semantic retrieval, lexical search, grounded AI, contextual activation, and measurable optimization can work together within a practical Drupal-based architecture.

The project prioritizes delivering functional value incrementally while maintaining architectural clarity, reproducibility, and responsible use of healthcare information.

**Build the foundation. Validate the search. Enrich the experience. Activate meaningful actions. Measure the outcomes.**