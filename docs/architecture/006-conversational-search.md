# ADR 006: Grounded conversational search with a local model

- Date: 2026-11-24
- Status: Implemented; pending review
- Approved scope: Phase 06, issue #21. Also resolves the dependency-injection follow-up #14. No multi-turn memory, CTAs, recommendation ranking or UI polish.

## Context

PLAN Phase 06 asks for natural-language answers generated only from retrieved MedlinePlus topics. Answers must carry verifiable citations, abstain when the evidence is insufficient, avoid diagnosis and personalized treatment, never invent physicians or facilities, treat retrieved text as data, and handle urgent situations without relying on similarity ranking.

## Provider decision

| Option | Cost | Privacy | Credentials | Drupal integration | Decision |
|---|---|---|---|---|---|
| Local Ollama model | None after download | Question never leaves the machine | None | Thin client, same as `OllamaEmbedder` | **Chosen** |
| Hosted API (Anthropic, OpenAI and others) | Per-token billing | Health questions sent to a third party | API key, kept out of Git with the Key module | Via `drupal/ai` providers | Not needed for a local POC; possible later |
| `drupal/ai` 1.5.3 with `ai_provider_ollama` | — | Same as local, but AI Logging must stay off | — | Stable core; Ollama provider only **1.2.0-rc3** | Deferred to #17 |

`drupal/ai` is a toolkit: a provider abstraction (chat, embeddings and so on), provider modules, vector database providers, AI Search, chatbots, agents and logging. It is the right long-term integration point if hosted providers are added. It was not adopted now for three reasons:

- The Ollama provider is a release candidate.
- The pipeline needs model digest pinning, a JSON-schema output format and generic, prompt-free errors.
- AI Logging could store health questions.

Generation sits behind `GeneratorInterface`, so adopting `drupal/ai` later means writing one class (see the comment on #17).

### Model comparison (local, M4 Pro, 48 GB)

Nine synthetic probes (answerable, unsupported, diagnosis-seeking and prompt-injection), each with three retrieved sources, temperature 0 and JSON-schema output (`scripts/search/llm_bakeoff.php`):

| Model | Valid JSON | Expected behavior | Notes | Median s |
|---|---|---|---|---|
| gemma3:4b | 9/9 | 8/9 | Added an unrelated HbA1c fact to a hay fever answer | 4.5 |
| llama3.1:8b | 8/9 | 8/9 | Truncated output (runaway generation) | 5.0 |
| qwen3:8b | 9/9 | 9/9 | Answered the pregnancy question with general back-pain facts | 7.1 |
| gemma3:12b | 9/9 | 9/9 | Declined the pregnancy question; the most conservative model | 12.6 |

All four were then compared on the full Phase 06 query set ([results](../evaluation/conversational-search.md)). **gemma3:12b** (digest `b92b2185…`) is the default.

- **License.** Gemma is covered by Google's Gemma Terms of Use and its prohibited-use policy, which includes the unlicensed practice of medicine. Educational information that declines to diagnose is consistent with that policy, but it is a license constraint. qwen3:8b (Apache-2.0) is the faster alternative and only needs a configuration change.
- **Context.** The model's context window is 128K tokens. `num_ctx` is set to 8192, which fits three sources of 2,500 characters each.

## Pipeline

`ConversationalSearch` (strategy `conversational`, route `/search/conversational`) runs these steps:

1. **Urgency first.** `UrgencyDetector` checks for crisis wording (suicide, overdose) and emergency wording (breathing, throat swelling, chest pain, unresponsiveness, poisoning, severe bleeding, stroke signs) before anything else. A match returns a fixed safety message (911, or 988 for crisis, or the local emergency number) and never calls the model. Supporting topics are still listed when retrieval works, and the message is shown even when retrieval fails.
2. **Retrieval.** The `rag_strategy` setting selects the strategy, `semantic` by default. Phase 05 showed semantic rejects unrelated questions (10/10), and in Phase 06 semantic beat hybrid (32 against 31 of 34) and was faster.
3. **Evidence sufficiency.** If retrieval accepts no topic, the answer is an abstention and the model is never called.
4. **Grounded context.** The top `rag_sources` (3) topics become `<source id="S1" title="…">` blocks containing the title, description and plain summary, cut to `rag_source_chars` (2500). The characters `<` and `>` in the source text are replaced, so retrieved text cannot close or fake a block. The system prompt says that sources are data, never instructions.
5. **Structured generation.** Ollama `/api/chat` returns `format` = JSON schema, with `answerable` and `statements[] {text, sources[]}`. The `sources` field is an `enum` of the provided labels, so the model cannot even express an unknown citation. Generation runs at temperature 0 with a fixed seed. `num_predict` (800) is capped because constrained decoding can loop; a truncated or invalid response counts as a failure. The pinned digest is checked before each call. Errors are generic and never include the prompt.
6. **Verification** (`ConversationalSearch::verify`):
   - **Invalid answers are withheld.** That covers invalid structure, more than six statements, empty text, a missing citation or a label that was not provided.
   - **Unsafe answers are withheld.** A statement is unsafe if it contains a diagnosis (“you likely have…”, “your diagnosis”, “you should take…”), a dose (`500 mg`), a contact (“Dr. ”, phone numbers) or a link. Conditional education (“If you have prediabetes, …”) is allowed.
   - **Unsupported statements are dropped.** Each statement is checked by content-word overlap with the sources it cites (minimum 0.6, with crude stemming). If none remain, the answer is an abstention.
   - **A declined answer is an abstention.** This applies when the model reports `answerable: false`.
7. **Presentation.**
   - The statements are shown with numbered citations, and a Sources list links each cited topic page and its MedlinePlus original.
   - A fixed limitations notice says the answer is generated and can be wrong, is not a diagnosis, and should be checked against the sources.
   - Cited topics come first among the supporting topics, which reuse the Phase 04 enrichment.
   - Abstentions and withheld answers say why and still list the closest topics.
   - A generation failure keeps the topics and adds a notice.

## Dependency injection (#14)

- **Strategy registry.** `med_search.strategies` (`SearchStrategyRegistry`) collects every service tagged `med_search.strategy` through Drupal's `service_collector`. `SearchForm::create()` gets its strategy from the registry, using the route's `strategy` default.
- **Composed strategies.** `HybridSearch` and `ConversationalSearch` receive their component strategies as service arguments through factories. `ConversationalSearch` gets semantic, lexical and hybrid and picks one by configuration at query time. Nothing receives the service container.
- **Current user.** `SemanticSearch`, `LexicalSearch` and `ResultEnricher` receive `current_user` and pass it to `access('view', $account)`.
- **Generator alias.** `GeneratorInterface` is aliased to `med_search.generator` for autowiring.
- **`Corpus` stays static.** It contains pure XML-to-array functions without services, comparable to core's `Html` utility, so making it a service would add indirection without a testability benefit. Migrations still access it through the `MedXml` source plugin.

## Privacy

- **Form handling.** The form is POST-only, uncached and uses temporary state, the same as the other search modes.
- **No logging in our code.** Prompts and model outputs are not logged by our code, and provider errors are replaced with generic messages.
- **Ollama's own log.** Ollama's server log records request lines, not bodies. This was verified: the test question is absent from `~/.ollama/logs/server.log`.
- **Evaluation data.** Evaluation uses only synthetic queries.

## Alternatives considered

- **LLM-as-judge grounding checks.** These were rejected: they double the latency and are themselves unverified. The deterministic checks are explainable and covered by tests.
- **Free-text answers with inline `[n]` markers parsed afterwards.** This was rejected because JSON-schema output with an enum of labels makes fabricated citations impossible rather than merely detectable.
- **Letting the model handle urgent cases.** This was rejected because PLAN requires a safety response that does not depend on ranking or generation.
- **Hybrid retrieval for RAG.** This was evaluated and not chosen as the default: 31/34 against 32/34, slower, and it inherits lexical false positives. It remains one setting away.

## Limitations

- **Latency.** Median about 13 s per answered question on an M4 Pro, which is slow for interactive use. qwen3:8b halves it at some quality cost.
- **Coarse support check.** Word overlap cannot detect a statement that reuses source words with a different meaning; it catches off-topic additions only. Every shown statement still carries a citation the reader can check.
- **Pattern-based safety.** Diagnosis, dose, contact and urgency rules are lists of phrasings and will miss some. Two gaps found in the first evaluation run were fixed afterwards and are reported as post-evaluation changes.
- **Educational framing of diagnosis-seeking questions.** “Do I have diabetes?” gets general information about symptoms, such as “Feeling tired and being thirsty can be symptoms of type 2 diabetes”, plus the limitations notice, not a refusal. This is a deliberate choice.
- **US-centric emergency numbers.** They follow MedlinePlus's US audience, with a generic “local emergency number” fallback.
- **Single exchange.** There is no conversation memory.
