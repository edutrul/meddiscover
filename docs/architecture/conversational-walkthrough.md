# From a question to a cited answer

[Back to the diagram index](search-uml.md#index)

## What the visitor sees

```mermaid
flowchart TD
    Q["Visitor asks: What is HbA1c?"]
    Q --> Find["Find a MedlinePlus topic about the question"]
    Find --> A1C["Example match: A1C"]
    A1C --> Read["Give the answer model the question and an excerpt from A1C"]
    Read --> Check{"Does the draft pass the citation and support checks?"}
    Check -->|Yes| Answer["Show the short answer with a link to A1C"]
    Check -->|No| Topics["Keep matching topics available for reading"]
    Find -->|No usable topic| Abstain["Show that there is insufficient evidence"]
```

Think of it as two jobs. The first finds the right reading material. The second uses that material to answer the question. For this example, a visitor can open the A1C citation to read the source directly.

## What the developer sees

```mermaid
sequenceDiagram
    participant C as ConversationalSearch
    participant S as SemanticStrategy and SemanticSearch
    participant O as Drupal AI Ollama provider
    participant V as PostgreSQL and pgvector
    participant G as AiGenerator

    C->>S: retriever()->search(query, limit)
    S->>O: Embedding request with the question
    O-->>S: Query vector
    S->>V: Compare with stored topic vectors
    V-->>S: Topic IDs and cosine scores
    S-->>C: Accepted topics and scores
    alt usable topics
        C->>G: generate(system, question with excerpts, schema)
        G->>O: Chat request for a structured answer
        O-->>G: Draft statements with source IDs
        G-->>C: Parsed output
        C->>C: Verify statements and citations
    else no usable topics
        C->>C: Return an abstained answer
    end
```

The checked-in configuration selects semantic retrieval. The embedding and chat operations use their respective configured models. The diagram follows a deployment with Ollama selected for both.

| Where to pause | What to inspect |
| --- | --- |
| [ConversationalSearch retrieval call](../../web/modules/custom/med_search/src/ConversationalSearch.php#L119) | Step over, then expand `$retrieved['results']`. These are the candidate source topics. |
| [SemanticSearch embedding call](../../web/modules/custom/med_search/src/SemanticSearch.php#L131) | Step over to inspect `$vector`. The SQL compares it with precomputed vectors using pgvector. |
| [ConversationalSearch generation call](../../web/modules/custom/med_search/src/ConversationalSearch.php#L152) | Inspect `$prompt` and `$sources`. The A1C excerpt can appear under source ID `S1`. |
| [AiGenerator provider call](../../web/modules/custom/med_search/src/AiGenerator.php#L77) | Inspect the chat input and configured model before the provider sends the request. |
