# med_search UML diagrams

GitHub renders these Mermaid diagrams in the Markdown **Preview** tab. The diagrams cover the request path and its main services. They omit Drupal infrastructure and administrative classes.

## Index

1. [End-to-end example](#end-to-end-example)
2. [Homepage routing](#how-the-homepage-chooses-a-search-path)
3. [How Hybrid combines rankings](#how-hybrid-combines-rankings)
4. [Illustrated conversational walkthrough](conversational-walkthrough.md)
5. [How a question becomes a cited answer](#how-a-question-becomes-a-cited-answer)
6. [Drupal content model](#drupal-content-model)
7. [Search classes](#search-classes)
8. [Submit a semantic search](#submit-a-semantic-search)
9. [Retrieve topics with pgvector](#retrieve-topics-with-pgvector)

## End-to-end example

```mermaid
flowchart TD
    XML["MedlinePlus XML snapshot"] --> Import["Drupal Migrate imports selected topics"]
    Import --> Content["Drupal stores topics, taxonomy, resources, and relationships"]
    Content --> Index["Search API indexes topic text in Solr"]
    Content --> Embeddings["Drupal AI embeds topic text for PostgreSQL / pgvector"]
    Query["Visitor types: knee pain when walking"] --> Router["Homepage picks Hybrid for this topic phrase"]
    Router --> Keyword["Solr ranks word matches"]
    Router --> Semantic["Drupal AI embeds the query; pgvector ranks similar topics"]
    Index -.-> Keyword
    Embeddings -.-> Semantic
    Keyword --> Fusion["Reciprocal Rank Fusion merges both lists"]
    Semantic --> Fusion
    Fusion --> Enrich["Drupal loads the ranked topics and their related content"]
    Content -.-> Enrich
    Enrich --> Results["Visitor sees ranked topics, source links, and relevant resources"]
```

Follow **Knee Injuries and Disorders** as an example of a topic in the index. Its title and text can contribute keyword matches, while its stored vector can contribute a semantic match. Hybrid combines the two rankings, and Drupal uses the original topic and its references to build the result card. The exact position depends on the query and the search configuration.

## How the homepage chooses a search path

```mermaid
flowchart TD
    Input["Homepage search"] --> Urgent{"Urgent wording?"}
    Urgent -->|Yes| Safety["Show the fixed safety message"]
    Urgent -->|No| Question{"Does it read like a question?"}
    Question -->|"Topic phrase: knee pain when walking"| Hybrid["Hybrid search"]
    Hybrid --> Solr["Solr ranks keyword matches"]
    Hybrid --> Vector["pgvector ranks semantic matches"]
    Solr --> RRF["Reciprocal Rank Fusion merges both rankings"]
    Vector --> RRF
    RRF --> Topics["Show ranked topics"]
    Question -->|"Question: What is HbA1c?"| Ask["Conversational search"]
    Ask --> Retrieve["Retrieve source topics using the configured strategy"]
    Retrieve --> Evidence{"Usable sources?"}
    Evidence -->|Yes| Generate["Ask the model to write a cited answer"]
    Evidence -->|No| Fallback["Show that an answer is unavailable"]
    Generate --> Validate{"Answer passes validation?"}
    Validate -->|Yes| Answer["Show the answer and its sources"]
    Validate -->|No| Fallback
```

The checked-in configuration uses **Semantic** for conversational retrieval. Hybrid merges positions from the two ranked lists using Reciprocal Rank Fusion (RRF). Conversational search uses retrieved excerpts to generate and check a cited answer.

## How Hybrid combines rankings

```mermaid
flowchart TD
    Query["Search: knee"] --> Solr["Solr: topic A ranks 1st, topic B ranks 3rd"]
    Query --> Vector["pgvector: topic B ranks 2nd"]
    Solr --> Fusion["RRF adds 1 / (60 + rank) for each list where a topic appears"]
    Vector --> Fusion
    Fusion --> A["A: 1/61 = 0.0164"]
    Fusion --> B["B: 1/63 + 1/62 = 0.0320"]
    A --> Output["B ranks above A"]
    B --> Output
```

These positions are illustrative. A topic that ranks well in both lists can outrank the strongest result from just one list.

## How a question becomes a cited answer

```mermaid
flowchart TD
    Question["What is HbA1c?"] --> Embed["Embedding model converts the question to a query vector"]
    Embed --> Retrieve["pgvector compares the query vector with stored topic vectors"]
    Retrieve --> Matches["Retrieval returns matching topics such as A1C"]
    Matches --> Context["Build a prompt with the question and retrieved excerpts"]
    Context --> Generate["The generation model returns statements with source IDs"]
    Generate --> Check{"Do the citations and statements pass verification?"}
    Check -->|Yes| Answer["Show the answer and a link to the A1C source"]
    Check -->|No| Fallback["Show the matching topics without a generated answer"]
```

The retrieval call in [ConversationalSearch::search()](../../web/modules/custom/med_search/src/ConversationalSearch.php#L119) is:

```php
$retrieved = $this->retriever()->search($query, $limit);
```

With `rag_strategy: semantic`, this reaches [SemanticSearch::search()](../../web/modules/custom/med_search/src/SemanticSearch.php#L131). Its `embedQuery($query)` call turns the question into a vector in the same embedding space as the indexed topics, so pgvector can rank them by cosine similarity. The query vector stays inside the retrieval step. `$retrieved['results']` contains the matching topics and scores. The chat model gets the question and excerpts from those topics in a later call.

In this example, the **A1C** topic can become source **S1** in the prompt. The generation model receives its excerpt. The published answer links back to the source. With the checked-in configuration, medDiscover can include up to three accepted topics.

## Drupal content model

```mermaid
erDiagram
    HEALTH_TOPIC }o--o{ MEDICAL_GROUP : belongs_to
    HEALTH_TOPIC }o--o{ MESH_HEADING : indexed_with
    HEALTH_TOPIC ||--o{ RESOURCE_ASSIGNMENT : has
    RESOURCE_ASSIGNMENT }o--o{ RESOURCE_CATEGORY : classified_as
    HEALTH_TOPIC ||--o{ TOPIC_RELATIONSHIP : source_of
    TOPIC_RELATIONSHIP }o--o| HEALTH_TOPIC : points_to_imported_target

    RESOURCE_ASSIGNMENT {
        string title
        string resource_url
    }
    TOPIC_RELATIONSHIP {
        string relationship_type
        string target_source_url
    }
```

Start with **Knee Injuries and Disorders**. It belongs to the **Injuries and Wounds** medical group and has the **Knee Injuries** MeSH heading. The **Anterior knee pain** resource assignment links to the MedlinePlus article. A separate **Topic Relationship** records the curated link to **Knee Replacement**. A relationship keeps the target URL and can also reference the local Drupal topic when that target was imported.

See the [Drupal content model decision](002-content-model.md) for bundle and field names.

## Search classes

```mermaid
classDiagram
    direction TB
    class SearchStrategyInterface {
        <<interface>>
        +id()
        +search(query)
    }
    class SemanticStrategy {
        -SemanticSearch search
        +search(query)
    }
    class SemanticSearch {
        +search(query)
    }
    class EmbedderInterface {
        <<interface>>
        +embedQuery(query)
    }
    class GeneratorInterface {
        <<interface>>
        +generate(system, user, schema)
    }
    SearchForm --> SearchStrategyRegistry : lookup
    CompareSearchForm --> SearchStrategyRegistry : lookup
    SearchForm --> HomepageRouter : automatic routing
    SearchStrategyRegistry --> SearchStrategyInterface : returns
    SearchStrategyInterface <|.. SemanticStrategy
    SearchStrategyInterface <|.. LexicalSearch
    SearchStrategyInterface <|.. HybridSearch
    SearchStrategyInterface <|.. ConversationalSearch
    SemanticStrategy --> SemanticSearch : delegates
    SemanticSearch --> EmbedderInterface : embeds query
    EmbedderInterface <|.. AiEmbedder
    SemanticSearch --> Connection : queries pgvector
    HybridSearch --> LexicalSearch : keyword ranks
    HybridSearch --> SemanticStrategy : semantic ranks
    ConversationalSearch ..> SearchStrategyInterface : configured retriever
    ConversationalSearch --> GeneratorInterface : generates answer
    GeneratorInterface <|.. AiGenerator
```

## Submit a semantic search

```mermaid
sequenceDiagram
    actor Browser
    participant Drupal
    participant Form as SearchForm
    participant State as FormState
    participant Registry as SearchStrategyRegistry
    participant Strategy as SemanticStrategy

    Browser->>Drupal: GET /search/semantic
    Drupal->>Form: buildForm(strategy = semantic)
    Form->>State: Store med_strategy_id
    Drupal-->>Browser: Search form
    Browser->>Drupal: POST query
    Drupal->>Form: submitForm()
    Form->>State: Read query and strategy ID
    Form->>Registry: get(semantic)
    Registry-->>Form: SemanticStrategy
    Form->>Strategy: search(query)
    Strategy-->>Form: Search response
    Form->>State: setTemporaryValue(med_response, response)
    Form->>State: setRebuild(true)
    Drupal->>Form: Rebuild form
    Form->>State: getTemporaryValue(med_response)
    State-->>Form: Search response
    Drupal-->>Browser: Render results
```

At [the search call](../../web/modules/custom/med_search/src/Form/SearchForm.php#L475), PHP evaluates `$strategy->search($query)` first. Drupal then stores the returned response in temporary form state for the rebuild.

## Retrieve topics with pgvector

```mermaid
sequenceDiagram
    participant S as SemanticStrategy
    participant R as SemanticSearch
    participant E as AiEmbedder
    participant AI as Drupal AI provider
    participant DB as PostgreSQL + pgvector

    S->>R: search(query)
    R->>E: embedQuery(query)
    E->>AI: Request embedding
    AI-->>E: Query vector
    E-->>R: Query vector
    R->>DB: Compare query vector with stored topic vectors
    Note over R,DB: Cosine similarity = 1 minus cosine distance
    DB-->>R: Candidate topic IDs and scores
    R-->>S: Relevant, accessible topics and timings
    S->>S: Format shared search response
```
