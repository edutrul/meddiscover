# med_search UML diagrams

GitHub renders these Mermaid diagrams in the Markdown **Preview** tab. The diagrams cover the request path and its main services. They omit Drupal infrastructure and administrative classes.

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
