<?php

namespace Drupal\med_search\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;
use Drupal\med_search\ConversationalSearch;
use Drupal\med_search\GeneratorInterface;
use Drupal\med_search\SearchStrategyRegistry;
use Drupal\med_search\StrategyBrand;
use Drupal\med_search\UrgencyDetector;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lets reviewers run the same query through each retrieval strategy.
 */
final class CompareSearchForm extends FormBase {

  use BuildsSearchAnswer;

  private const BASELINE_QUESTION = 'What is HbA1c?';
  private const BASELINE_INSTRUCTIONS = 'Answer the fixed educational question briefly in plain language, using only your general knowledge. Give a definition. Do not give personal advice, treatment, or citations. If unsure, return an empty answer.';

  /**
   * Shared routing step for the technical breakdown.
   */
  private const TECH_ROUTING = <<<'TWIG'
<li><h4>Routing: question or topic?</h4>
<p>Before any search, the homepage checks urgent wording, then tests the input against one regular expression (<code>SearchForm::QUESTION_PATTERN</code>, case-insensitive):</p>
<pre><code>{{ pattern }}</code></pre>
<p>It matches a trailing <code>?</code>, a leading question word (what, why, when, where, who, which, how) followed by another word, or a leading auxiliary followed by a pronoun or article (“is the”, “can I”, “do you”…).
{% if reason %}<strong>“{{ query }}” {{ reason }} → Ask (RAG).</strong>{% else %}<strong>“{{ query }}” matches none of the three alternatives → Hybrid (Keyword + Semantic).</strong>{% endif %}
 No model is involved in this decision, so it is instant and predictable; the trade-off is that a question written without a question word (“HbA1c normal range”) is treated as a topic search.</p></li>
TWIG;

  /**
   * Shared Semantic step for the technical breakdown.
   */
  private const TECH_SEMANTIC = <<<'TWIG'
<p><strong>1. Embed the query.</strong> Drupal sends <code>{{ embedding_input }}</code> to Ollama’s <code>/api/embed</code> with model <code>{{ embedding_model }}</code>. The <code>search_query:</code> prefix is required by this model (topics were embedded with <code>search_document:</code>). The response is a vector of <strong>{{ dimensions }}</strong> numbers{% if vector_head %} (first values: <code>[{{ vector_head }}, …]</code>){% endif %}. Time: {{ embedding_ms }}. The model digest is pinned, so query vectors and the stored topic vectors always come from the same model.</p>
<p><strong>2. Compare with pgvector.</strong> Each of the 24 topics has one stored vector (title, description, summary, alternative names and MeSH terms). PostgreSQL computes <code>similarity = 1 − (embedding &lt;=&gt; query)</code>, where <code>&lt;=&gt;</code> is pgvector’s cosine distance, for every topic: an exact scan, no approximate index, which is fine at this size. Topics below <strong>{{ threshold }}</strong> are dropped. Time: {{ semantic_ms }}.</p>
{% if semantic %}<table><thead><tr><th>Rank</th><th>Topic</th><th>Cosine similarity</th><th>Cosine distance</th></tr></thead><tbody>
{% for row in semantic %}<tr><td>{{ row.rank }}</td><td>{{ row.title }}</td><td>{{ row.cosine }}</td><td>{{ row.distance }}</td></tr>{% endfor %}
</tbody></table>{% else %}<p><em>No topic reached the {{ threshold }} cutoff.</em></p>{% endif %}
TWIG;

  /**
   * Hybrid path: routing, Solr, pgvector, RRF.
   */
  private const TECH_HYBRID = self::TECH_HEAD . self::TECH_ROUTING . <<<'TWIG'
<li><h4>Keyword (Lexical): Apache Solr</h4>
<p>Drupal’s Search API Solr sends an <strong>EDisMax</strong> query. The keywords are escaped and sent as <code>q = {{ solr_q }}</code>; Solr’s English analyzer lowercases, removes stopwords and stems them (“problems” → “problem”).</p>
<p><strong>Must-match rule <code>mm = {{ solr_mm }}</code>:</strong> {% if solr_mm_all %}with up to {{ solr_mm_all }} terms every term is required; with more, 75% of them (rounded down).{% else %}Solr’s minimum-should-match setting for this query.{% endif %} This blocks topics that share only one stray word.</p>
<p><strong>Scoring:</strong> BM25 (term frequency × rarity, normalized by field length), multiplied by field boosts: title 13 (exact/unstemmed title 21), also-called names 8 (unstemmed 13), MeSH 5, description 3, see-also references 3, summary 1, groups 1. A phrase boost (<code>pf</code>) rewards the whole query appearing as a phrase in the exact title (×21), an alternative name (×13) or a see-also reference (×5). Scores are only comparable within one search. Time: {{ lexical_ms }}.</p>
{% if lexical %}<table><thead><tr><th>Rank</th><th>Topic</th><th>Solr score</th></tr></thead><tbody>
{% for row in lexical %}<tr><td>{{ row.rank }}</td><td>{{ row.title }}</td><td>{{ row.score }}</td></tr>{% endfor %}
</tbody></table>{% else %}<p><em>No topic satisfied the must-match rule.</em></p>{% endif %}</li>
<li><h4>Semantic: Ollama embeddings + pgvector</h4>
TWIG . self::TECH_SEMANTIC . <<<'TWIG'
</li>
<li><h4>Reciprocal Rank Fusion (RRF), k = {{ k }}</h4>
<p>Solr scores (unbounded BM25) and cosine similarities (between −1 and 1) are on different scales, so adding them would be meaningless. RRF uses only <strong>positions</strong>: from each list (up to {{ depth }} candidates each), a topic at rank <em>r</em> earns <code>1 / ({{ k }} + r)</code> points; a topic missing from a list earns 0 from it. The points are summed and sorted. Ties go to the topic found by more lists, then the better single rank, then Keyword first.</p>
<p><strong>Why k = {{ k }}:</strong> a large constant makes neighbouring ranks worth almost the same (1/61 vs 1/62), so <em>appearing in both lists</em> matters more than being first in one. It is the standard value from the original RRF paper (Cormack, Clarke and Büttcher, 2009); on our 48 test searches any k from 0 to 1000 produced the same top 5. Fusion time: {{ fusion_ms }}.</p>
{% if rrf %}<table><thead><tr><th>Final rank</th><th>Topic</th><th>Keyword rank</th><th>Semantic rank</th><th>Keyword points</th><th>Semantic points</th><th>RRF total</th></tr></thead><tbody>
{% for row in rrf %}<tr><td>{{ row.rank }}</td><td>{{ row.title }}</td><td>{{ row.lexical_rank }}</td><td>{{ row.semantic_rank }}</td><td>{{ row.lexical_points }}</td><td>{{ row.semantic_points }}</td><td><strong>{{ row.total }}</strong></td></tr>{% endfor %}
</tbody></table>
<p>The top 5 by RRF total are exactly the list the homepage shows.</p>{% else %}<p><em>Neither list had a topic, so there is nothing to fuse: the homepage says “No topics matched closely enough.”</em></p>{% endif %}</li>
</ol>
TWIG;

  /**
   * Ask path: routing, retrieval, generation, verification.
   */
  private const TECH_RAG = self::TECH_HEAD . self::TECH_ROUTING . <<<'TWIG'
<li><h4>Retrieve sources (the “R” in RAG): Semantic search</h4>
TWIG . self::TECH_SEMANTIC . <<<'TWIG'
<p><strong>Why Semantic, not Hybrid:</strong> everything retrieved here is handed to the language model, so precision matters more than completeness. Keyword search counts question words such as “how”, “do” and “I” toward its must-match rule, which can pull in unrelated topics; in our tests Semantic rejected all 10 unrelated questions.</p>
<p><strong>Evidence check:</strong> if no topic reaches {{ threshold }}, the pipeline stops here and says the collection has no matching topic. The writing model is never called.</p></li>
<li><h4>Augment the prompt and generate (the “AG”)</h4>
<p>The top {{ rag_sources }} topics become labeled sources of up to {{ rag_chars }} characters each (title, description, plain summary). Angle brackets in source text are neutralized, so a source cannot open or close a source block, and the system prompt says sources are data, never instructions.</p>
{% if answer_sources %}<table><thead><tr><th>Label</th><th>Source topic</th></tr></thead><tbody>
{% for row in answer_sources %}<tr><td>{{ row.label }}</td><td>{{ row.title }}</td></tr>{% endfor %}
</tbody></table>{% endif %}
<p>Ollama <code>/api/chat</code> with model <code>{{ generation_model }}</code>, temperature 0, fixed seed, context {{ generation_ctx }} tokens, at most {{ generation_max }} output tokens. The output must follow a <strong>JSON schema</strong>: <code>{answerable, statements: [{text, sources}]}</code>, where <code>sources</code> is an enum of the labels above, so the model cannot even write a citation to a source it was not given. Time: {{ generation_ms }}.</p></li>
<li><h4>Verify before showing</h4>
<p>Drupal checks the structure and every citation label; rejects text that diagnoses (“you likely have…”), gives doses (“500 mg”), names contacts or links; and drops any sentence whose content words overlap less than 60% with its cited source. If the model says the sources do not answer the question, or nothing survives, the page abstains.</p>
{% if answer_status %}<p><strong>This answer:</strong> status <code>{{ answer_status }}{% if answer_reason %} ({{ answer_reason }}){% endif %}</code>, {{ answer_statements }} statement(s) shown, {{ answer_dropped }} dropped as unsupported.</p>{% endif %}
<p>Times for this answer: embedding {{ answer_embedding_ms }}, retrieval {{ answer_retrieval_ms }}, generation {{ generation_ms }}. Generation dominates: retrieval takes milliseconds, writing takes seconds on the demo server’s CPU.</p></li>
</ol>
TWIG;

  /**
   * Urgent path.
   */
  private const TECH_URGENT = self::TECH_HEAD . <<<'TWIG'
<li><h4>Urgent wording comes first</h4>
<p><code>UrgencyDetector</code> runs before routing and before any search. It matches fixed patterns for emergency (“chest pain”, “can’t breathe”, “throat swelling”, unresponsiveness, poisoning, stroke signs…) and crisis wording (self-harm, overdose). This input matched the <strong>{{ urgent }}</strong> group, so the homepage shows a fixed safety message (911 / 988) and never calls the language model. Patterns favour recall: a false alarm only adds a safety message. Misspellings (“chess pain”) are not caught.</p></li>
</ol>
TWIG;

  /**
   * Opening of every technical breakdown.
   */
  private const TECH_HEAD = '<ol class="med-tech-steps">';

  /**
   * Flow diagram of the homepage decision; the taken path is highlighted.
   */
  private const FLOW = <<<'TWIG'
<p class="med-flow__intro">Every homepage search goes through two checks, in this order. Your search, <strong>“{{ query }}”</strong>, took the <span class="med-flow__you">highlighted</span> path.</p>
<ol class="med-flow" role="list">
  <li class="med-flow__node med-flow__node--input is-taken">Your words</li>
  <li class="med-flow__step{{ path == 'urgent' ? ' is-taken' }}">
    <div class="med-flow__check is-taken"><span class="med-flow__q">1. Urgent wording? (an emergency or a crisis)</span><span class="med-flow__how">fixed phrases such as “chest pain”, “can’t breathe”, “throat swelling”, “passed out”, stroke signs, “overdose” or self-harm words</span></div>
    <div class="med-flow__branch{{ path == 'urgent' ? ' is-taken' }}"><span class="med-flow__yes">yes</span>
      <div class="med-flow__node med-flow__node--urgent"><strong class="med-flow__title"><img src="{{ images }}urgent.svg" alt="" width="20" height="20"> Safety message</strong><span>“Call 911 now” (or 988 for a crisis). Shown first, instead of search results or an AI answer.</span></div>
    </div>
  </li>
  <li class="med-flow__no{{ path != 'urgent' ? ' is-taken' }}">no</li>
  <li class="med-flow__step{{ path == 'ask' ? ' is-taken' }}">
    <div class="med-flow__check{{ path != 'urgent' ? ' is-taken' }}"><span class="med-flow__q">2. A question?</span><span class="med-flow__how">ends with “?”, or starts with what / why / how…, or “is the”, “can I”, “do you”…</span></div>
    <div class="med-flow__branch{{ path == 'ask' ? ' is-taken' }}"><span class="med-flow__yes">yes</span>
      <div class="med-flow__node med-flow__node--ask"><strong class="med-flow__title"><img src="{{ images }}rag.svg" alt="" width="20" height="20"> Ask (RAG)</strong>
        <ol class="med-flow__pipe">
          <li><img src="{{ images }}postgresql.svg" alt="" width="18" height="18"> Semantic search (pgvector) picks the 3 closest topics</li>
          <li><span class="med-flow__ai" aria-hidden="true">✦</span> Ollama writes an answer from those topics only</li>
          <li><span class="med-flow__ai" aria-hidden="true">✓</span> Every sentence must cite a source, or it is dropped</li>
        </ol>
        <span class="med-flow__out">→ a short answer with citations</span>
      </div>
    </div>
  </li>
  <li class="med-flow__no{{ path == 'hybrid' ? ' is-taken' }}">no</li>
  <li class="med-flow__node med-flow__node--hybrid{{ path == 'hybrid' ? ' is-taken' }}"><strong class="med-flow__title"><img src="{{ images }}solr.png" alt="" width="20" height="20"><img src="{{ images }}postgresql.svg" alt="" width="20" height="20"> Hybrid (Keyword + Semantic)</strong>
    <div class="med-flow__lists">
      <span><img src="{{ images }}solr.png" alt="" width="18" height="18"> Keyword list (Apache Solr)</span>
      <span class="med-flow__plus" aria-hidden="true">+</span>
      <span><img src="{{ images }}postgresql.svg" alt="" width="18" height="18"> Semantic list (pgvector)</span>
    </div>
    <span class="med-flow__merge">↓ merged by RRF (k = 60): topics high in both lists rise to the top</span>
    <span class="med-flow__out">→ a ranked list of health topics</span>
  </li>
</ol>
<p class="med-flow__note"><strong>Good to know:</strong> check 2 is a simple word pattern, not AI, so it is instant and predictable. It misses some questions: “what’s HbA1c”, “explain HbA1c” and “should a diabetic eat rice” currently go to Hybrid. RRF never decides the path; it only orders Hybrid’s topics.</p>
TWIG;

  public function __construct(
    private SearchStrategyRegistry $strategies,
    private UrgencyDetector $urgency,
    private GeneratorInterface $generator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('med_search.strategies'), $container->get('med_search.urgency'), $container->get('med_search.generator'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_compare_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form_state->disableCache();
    $form['#cache']['max-age'] = 0;
    $form['#attributes']['data-med-search'] = 'compare';
    $form['#attributes']['class'][] = 'med-compare';
    $form['#attached']['library'][] = 'med_search/search';
    (new Htmx())->post(Url::fromRoute('med_search.compare'))->history()
      ->onlyMainContent()->select('form[data-med-search="compare"]')
      ->target('this')->swap('outerHTML')->indicator('#med-compare-loading')
      ->disabledElt('find [type="submit"]')->applyTo($form);

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query to compare'),
      '#description' => $this->t('Use a demo query or enter your own words without personal details.'),
      '#maxlength' => 500,
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Compare')];
    $form['examples'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-examples']],
    ];
    $form['examples']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['med-examples__label']],
      '#value' => $this->t('Try one of these searches'),
    ];
    foreach (['HbA1c', 'Backache', 'Knee pain when walking', 'What is HbA1c?'] as $index => $example) {
      $form['examples'][$index] = [
        '#type' => 'submit',
        '#name' => 'compare_example_' . $index,
        '#value' => $example,
        '#med_example' => $example,
      ];
    }

    $form['loading'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => [
        'id' => 'med-compare-loading',
        'class' => ['med-loading'],
        'role' => 'status',
        // Questions also get an answer, which takes much longer.
        'data-med-question-pattern' => SearchForm::QUESTION_PATTERN,
        'data-med-loading-answer' => (string) $this->t('Running the three searches and writing a cited answer from MedlinePlus… this can take 10–40 seconds.'),
        'data-med-loading-search' => (string) $this->t('Running all three searches…'),
      ],
      '#value' => $this->t('Running all three searches…'),
    ];

    $response = $form_state->getTemporaryValue('comparison');
    if ($response === NULL) {
      return $form;
    }
    if ($example = $form_state->getTriggeringElement()['#med_example'] ?? NULL) {
      $form['#attributes']['data-med-public-example'] = $example;
    }
    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Comparison results'),
      '#attributes' => ['tabindex' => '-1', 'data-med-results-heading' => ''],
    ];
    $query = trim((string) $form_state->getValue('query'));
    $config = $this->config('med_search.settings');
    $retrieval_id = $config->get('rag_strategy');
    $retrieval = match ($retrieval_id) {
      'lexical' => $this->t('Keyword (Lexical)'),
      'hybrid' => $this->t('Hybrid'),
      default => $this->t('Semantic'),
    };
    $urgent = $this->urgency->detect($query);
    $uses_hybrid = SearchForm::usesHomepageHybrid($query, $urgent);
    $uses_conversation = $urgent === NULL && !$uses_hybrid;
    // With no topic at all, the homepage would show its "no topics" message,
    // so no card earns the ribbon.
    $nothing_found = $uses_hybrid && empty($response['hybrid']['results']);
    $ribbon_hybrid = $uses_hybrid && !$nothing_found;
    // The main point of this page: what the homepage would answer, and why.
    $hybrid_results = $response['hybrid']['results'] ?? [];
    $form['verdict'] = $this->verdict($query, $urgent, $uses_conversation, $nothing_found, $hybrid_results, $retrieval);
    if ($this->currentUser()->hasPermission('view med search ranking details')) {
      $form['verdict']['technical'] = $this->technicalBreakdown(
        $query,
        $urgent,
        $uses_conversation,
        $response,
        $form_state->getTemporaryValue('comparison_answer'),
      );
    }
    if (!$uses_conversation) {
      $form['evidence_heading'] = $this->evidenceHeading();
    }
    $form['ranking'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-comparison-grid']],
    ];
    $can_debug = $this->currentUser()->hasPermission('view med search ranking details');
    $methods = [
      'lexical' => $this->t('Keyword (Lexical)'),
      'semantic' => $this->t('Semantic'),
      'hybrid' => $this->t('Hybrid (Keyword + Semantic)'),
    ];
    foreach ($methods as $id => $label) {
      $column = [
        '#type' => 'html_tag',
        '#tag' => 'section',
        '#attributes' => $ribbon_hybrid && $id === 'hybrid' ? $this->homepageCard() : [],
        'corner' => $ribbon_hybrid && $id === 'hybrid' ? $this->cornerRibbon() : [],
        'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $label],
        'ribbon' => $uses_conversation && $id === $retrieval_id
          ? $this->homepageRibbon($this->t('Finds sources for the answer'))
          : [],
        'brand' => StrategyBrand::build($id),
        'about' => ['#plain_text' => $this->t('@description', ['@description' => SearchForm::STRATEGIES[$id][1]])],
      ];
      if ($id === 'semantic') {
        $column['threshold'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Similarity cutoff: @threshold. Hybrid applies it to Semantic matches only.', [
            '@threshold' => (string) $config->get('threshold'),
          ]),
        ];
      }
      $ranking_help = match ($id) {
        'lexical' => $this->t('Solr scores matches across indexed fields. Strongly weighted fields, such as the title, can raise a topic’s position. Use the score to compare topics within this query.'),
        'semantic' => $this->t('Cosine similarity measures how close the query and topic vectors are: 1 minus cosine distance. A higher score ranks first among topics that pass the cutoff.'),
        'hybrid' => $this->t('Reciprocal Rank Fusion (RRF) adds points from each list. First place earns 1/(@k + 1), second earns 1/(@k + 2), and an absent topic earns zero from that list. A topic ranked first in Keyword and second in Semantic earns both amounts. The highest total ranks first.', [
          '@k' => (int) $config->get('hybrid_rrf_k'),
        ]),
      };
      $column['ranking_help'] = SearchForm::metricHelp($this->t('How ranking works'), $ranking_help);
      if ($ribbon_hybrid && $id === 'hybrid') {
        $column['why'] = $this->whyThisOne($this->t('For topic searches the homepage wants the most complete list. Keyword catches exact names and aliases such as “HbA1c”, and Semantic catches related meaning. In our tests, Hybrid found the most relevant topics: 96% of them, against 90% for Semantic and 79% for Keyword.'));
      }
      $result = $response[$id];
      if ($result === NULL || !$result['results']) {
        $column['status'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $result === NULL ? $this->t('This strategy is temporarily unavailable.') : $this->t('No matching topics.'),
        ];
      }
      else {
        $strategy = $this->strategies->get($id);
        $column['topics'] = ['#theme' => 'item_list', '#list_type' => 'ol', '#items' => []];
        foreach ($result['results'] as $item) {
          $entry = ['topic' => $item['node']->toLink()->toRenderable()];
          if ($can_debug) {
            $entry['score'] = [
              '#type' => 'container',
              '#attributes' => ['class' => ['med-debug']],
              'value' => ['#plain_text' => $strategy->describeScore($item)],
            ];
          }
          $column['topics']['#items'][] = $entry;
        }
        if ($can_debug) {
          $column['timing'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#attributes' => ['class' => ['med-debug']],
            '#value' => $this->t('Elapsed server time: @ms ms', ['@ms' => number_format($result['comparison_ms'], 1)]),
          ];
        }
      }
      if ($id === 'semantic' && $can_debug && isset($result['embedding_debug'])) {
        $debug = $result['embedding_debug'];
        $debug['pgvector_results'] = array_map(static fn(array $item): array => [
          'title' => $item['node']->label(),
          'cosine_similarity' => $item['score'],
        ], $result['results']);
        $column['embedding_debug'] = $this->debugCode($this->t('Ollama embedding and pgvector results'), $debug);
      }
      if ($id === 'hybrid' && $can_debug && isset($result['hybrid_debug'])) {
        $column['hybrid_debug'] = $this->debugCode($this->t('Hybrid rankings and RRF scores'), $result['hybrid_debug']);
      }
      if ($id === 'lexical' && $can_debug && isset($result['solr_debug'])) {
        $column['solr_debug'] = $this->debugCode($this->t('Solr query and results'), $result['solr_debug']);
      }
      foreach ($result['notices'] ?? [] as $index => $notice) {
        $column['notice_' . $index] = ['#plain_text' => $notice];
      }
      $form['ranking'][$id] = $column;
    }
    if ($uses_conversation) {
      $answer = $form_state->getTemporaryValue('comparison_answer');
      $form['conversation'] = [
        '#type' => 'container',
        '#attributes' => $this->homepageCard(['med-conversation-card']),
        'corner' => $this->cornerRibbon(),
        'heading' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $this->t('Ask (Conversational)')],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('@strategy search finds the topics. Ollama reads excerpts from those topics and writes an answer with citations.', ['@strategy' => $retrieval]),
        ],
        'why' => $this->whyThisOne($this->t('A question needs an answer, not a list, and every source found is handed to the AI, so precision matters more than completeness. In our tests, Semantic search ignored all 10 unrelated questions, while Keyword, and Hybrid through it, can be misled by words like “how do I”. Semantic sources also produced the best answers.')),
      ];

      if ($answer !== NULL) {
        $form['conversation']['outputs'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['med-answer-comparison']],
        ];
        if ($query === self::BASELINE_QUESTION) {
          $form['conversation']['outputs']['baseline'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['med-answer']],
            'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $this->t('Without search')],
            'note' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => $this->t('The same model answers from memory, without MedlinePlus excerpts or citations.'),
            ],
            'text' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              'value' => ['#plain_text' => $answer['baseline'] ?? $this->t('The model did not return a usable answer without search.')],
            ],
          ];
          if ($can_debug && isset($answer['baseline'])) {
            $form['conversation']['outputs']['baseline']['prompt'] = $this->debugCode($this->t('Ollama prompt without search'), [
              'model' => $config->get('generation_model'),
              'system' => self::BASELINE_INSTRUCTIONS,
              'user' => self::BASELINE_QUESTION,
            ]);
          }
        }
        $form['conversation']['outputs']['grounded'] = [
          '#type' => 'container',
          'heading' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $this->t('With search (RAG)')],
          'answer' => $this->buildAnswer($answer['answer'], $answer['results']),
        ];
        if ($can_debug && isset($answer['generation_debug'])) {
          $form['conversation']['outputs']['grounded']['prompt'] = $this->debugCode($this->t('Ollama prompt with retrieved sources'), $answer['generation_debug']);
        }
      }
      // Questions get an answer first. The three rankings for the same
      // question follow: Semantic's list is what the answer was written from.
      $grid = $form['ranking'];
      unset($form['ranking']);
      $form['ranking_heading'] = $this->evidenceHeading();
      $form['ranking_note'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => ['class' => ['med-generate-hint']],
        '#value' => $this->t('The homepage does not show these lists for questions. The answer above was written from the @strategy list.', ['@strategy' => $retrieval]),
      ];
      $form['ranking'] = $grid;
    }
    return $form;
  }

  /**
   * Developer mode: how the homepage computes the verdict, with live values.
   *
   * Explains every stage of the chosen path (routing, Solr, pgvector, RRF,
   * or retrieval, prompt and verification for RAG) using this search's own
   * scores, ranks, settings and timings.
   */
  private function technicalBreakdown(string $query, ?string $urgent, bool $uses_conversation, array $response, ?array $answer): array {
    $config = $this->config('med_search.settings');
    $ms = static fn($value): string => $value === NULL ? '–' : number_format((float) $value, 1) . ' ms';
    $lexical = $response['lexical'] ?? NULL;
    $semantic = $response['semantic'] ?? NULL;
    $hybrid = $response['hybrid'] ?? NULL;
    $solr = $lexical['solr_debug']['parameters'] ?? [];
    $embedding = $semantic['embedding_debug'] ?? [];
    $context = [
      'query' => $query,
      'pattern' => SearchForm::QUESTION_PATTERN,
      'reason' => SearchForm::questionReason($query),
      'urgent' => $urgent,
      'solr_q' => $solr['q'] ?? '',
      'solr_mm' => $mm = $solr['mm'] ?? (string) $config->get('lexical_min_match'),
      // "N<75%": up to N terms all are required; above N, 75% of them.
      'solr_mm_all' => preg_match('/^(\d+)<75%$/', $mm, $m) ? (int) $m[1] : NULL,
      'solr_pf' => $solr['pf'] ?? '',
      'lexical' => array_map(static fn(array $row, int $i): array => [
        'rank' => $i + 1,
        'title' => $row['node']->label(),
        'score' => number_format((float) $row['score'], 2),
      ], $lexical['results'] ?? [], array_keys($lexical['results'] ?? [])),
      'lexical_ms' => $ms($lexical['timings']['lexical_ms'] ?? NULL),
      'embedding_model' => $embedding['model'] ?? (string) $config->get('model'),
      'embedding_input' => $embedding['input'] ?? 'search_query: ' . $query,
      'dimensions' => $embedding['response']['dimensions'] ?? (int) $config->get('dimensions'),
      'vector_head' => isset($embedding['response']['embedding']) ? implode(', ', array_map(static fn($v): string => number_format((float) $v, 4), array_slice($embedding['response']['embedding'], 0, 4))) : NULL,
      'threshold' => (string) $config->get('threshold'),
      'semantic' => array_map(static fn(array $row, int $i): array => [
        'rank' => $i + 1,
        'title' => $row['node']->label(),
        'cosine' => number_format((float) $row['score'], 4),
        'distance' => number_format(1 - (float) $row['score'], 4),
      ], $semantic['results'] ?? [], array_keys($semantic['results'] ?? [])),
      'embedding_ms' => $ms($semantic['timings']['embedding_ms'] ?? NULL),
      'semantic_ms' => $ms($semantic['timings']['semantic_ms'] ?? NULL),
      'k' => (int) $config->get('hybrid_rrf_k'),
      'depth' => (int) $config->get('hybrid_depth'),
      'rrf' => array_map(static fn(array $row, int $i): array => [
        'rank' => $i + 1,
        'title' => $row['title'],
        'lexical_rank' => $row['ranks']['lexical'] ?? '–',
        'semantic_rank' => $row['ranks']['semantic'] ?? '–',
        'lexical_points' => isset($row['points_by_list']['lexical']) ? number_format($row['points_by_list']['lexical'], 5) : '0',
        'semantic_points' => isset($row['points_by_list']['semantic']) ? number_format($row['points_by_list']['semantic'], 5) : '0',
        'total' => number_format($row['rrf_score'], 5),
      ], $hybrid['hybrid_debug']['results'] ?? [], array_keys($hybrid['hybrid_debug']['results'] ?? [])),
      'fusion_ms' => $ms($hybrid['timings']['fusion_ms'] ?? NULL),
      'rag_sources' => (int) $config->get('rag_sources'),
      'rag_chars' => (int) $config->get('rag_source_chars'),
      'generation_model' => (string) $config->get('generation_model'),
      'generation_ctx' => (int) $config->get('generation_context_length'),
      'generation_max' => (int) $config->get('generation_max_tokens'),
      'answer_status' => $answer['answer']['status'] ?? NULL,
      'answer_reason' => $answer['answer']['reason'] ?? NULL,
      'answer_statements' => count($answer['answer']['statements'] ?? []),
      'answer_dropped' => $answer['answer']['dropped'] ?? 0,
      // Source labels follow retrieval order (the answer lists cited topics
      // first, which is not the order the model saw).
      'answer_sources' => $answer === NULL ? [] : array_map(static fn(array $row, int $i): array => [
        'label' => 'S' . ($i + 1),
        'title' => $row['node']->label(),
      ], $sources = array_slice($semantic['results'] ?? [], 0, (int) $config->get('rag_sources')), array_keys($sources)),
      'answer_embedding_ms' => $ms($answer['timings']['embedding_ms'] ?? NULL),
      'answer_retrieval_ms' => $ms($answer['timings']['semantic_ms'] ?? NULL),
      'generation_ms' => $ms($answer['timings']['generation_ms'] ?? NULL),
    ];
    return [
      '#type' => 'details',
      '#title' => $this->t('How the homepage computes this (technical)'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['med-debug', 'med-verdict-tech']],
      'body' => [
        '#type' => 'inline_template',
        '#template' => $urgent !== NULL ? self::TECH_URGENT : ($uses_conversation ? self::TECH_RAG : self::TECH_HYBRID),
        '#context' => $context,
      ],
    ];
  }

  /**
   * Heading that presents the method cards as the evidence for the verdict.
   */
  private function evidenceHeading(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#attributes' => ['class' => ['med-compare-subheading']],
      '#value' => $this->t('The evidence: how each method handles this search'),
    ];
  }

  /**
   * What the homepage would answer for this input, and the rule behind it.
   */
  private function verdict(string $query, ?string $urgent, bool $uses_conversation, bool $nothing_found, array $hybrid_results, $retrieval): array {
    $quoted = '“' . $query . '”';
    $topic_rule = $this->t('@query has no “?” and does not start with a question word, so it counts as a topic search.', ['@query' => $quoted]);
    [$label, $get, $why] = match (TRUE) {
      $urgent !== NULL => [
        $this->t('Urgent-safety message'),
        $this->t('Emergency guidance only: call 911 or your local emergency number. No topic list and no AI answer.'),
        $this->t('@query contains urgent wording, which is checked before anything else.', ['@query' => $quoted]),
      ],
      $uses_conversation => [
        $this->t('Ask (RAG)'),
        $this->t('A short answer with citations, written from the top topics of @strategy search. It is shown below.', ['@strategy' => $retrieval]),
        $this->t('@query @reason, so it counts as a question.', [
          '@query' => $quoted,
          '@reason' => SearchForm::questionReason($query),
        ]),
      ],
      $nothing_found => [
        $this->t('No result'),
        $this->t('“No topics matched closely enough.” None of the three methods found a topic. Check the spelling or try other words.'),
        $this->t('@rule The homepage would run Hybrid, but Hybrid found nothing.', ['@rule' => $topic_rule]),
      ],
      default => [
        $this->t('Hybrid (Keyword + Semantic)'),
        $this->t('A ranked list of health topics:'),
        $topic_rule,
      ],
    };
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['med-verdict', $uses_conversation ? 'med-verdict--ask' : 'med-verdict--hybrid'],
      ],
      'kicker' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => ['class' => ['med-verdict__kicker']],
        '#value' => $this->t('The homepage would answer with'),
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => ['class' => ['med-verdict__label']],
        '#value' => $label,
      ],
      'get' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        'title' => ['#type' => 'html_tag', '#tag' => 'strong', '#value' => $this->t("You'd get:")],
        'text' => ['#plain_text' => ' ' . $get],
      ],
    ];
    if ($urgent === NULL && !$uses_conversation && $hybrid_results) {
      $build['topics'] = ['#theme' => 'item_list', '#list_type' => 'ol', '#items' => []];
      foreach ($hybrid_results as $item) {
        $build['topics']['#items'][] = $item['node']->toLink()->toRenderable();
      }
    }
    $build['why'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      'title' => ['#type' => 'html_tag', '#tag' => 'strong', '#value' => $this->t('Why:')],
      'text' => ['#plain_text' => ' ' . $why],
    ];
    $build['rule'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['class' => ['med-verdict__rule']],
      'text' => ['#plain_text' => $this->t('The rule: a question → Ask (RAG) · anything else → Hybrid (Keyword + Semantic) · urgent wording → safety message first.') . ' '],
      'open' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => [
          'type' => 'button',
          'class' => ['med-flow-open'],
          'data-med-flow-open' => TRUE,
          'aria-haspopup' => 'dialog',
        ],
        '#value' => $this->t('See the flow diagram'),
      ],
    ];
    $title_id = Html::getUniqueId('med-flow-title');
    $build['flow'] = [
      '#type' => 'html_tag',
      '#tag' => 'dialog',
      '#attributes' => ['class' => ['med-strategy-dialog', 'med-flow-dialog'], 'aria-labelledby' => $title_id],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#attributes' => ['id' => $title_id],
        '#value' => $this->t('How the homepage decides'),
      ],
      'body' => [
        '#type' => 'inline_template',
        '#template' => self::FLOW,
        '#context' => [
          'query' => $query,
          'path' => $urgent !== NULL ? 'urgent' : ($uses_conversation ? 'ask' : 'hybrid'),
          'images' => StrategyBrand::imagePath(),
        ],
      ],
      'close' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => ['type' => 'button', 'data-med-flow-close' => TRUE],
        '#value' => $this->t('Close'),
      ],
    ];
    return $build;
  }

  /**
   * Formats request details as escaped JSON.
   */
  private function debugCode($label, array $data): array {
    return [
      '#type' => 'details',
      '#title' => $label,
      '#attributes' => ['class' => ['med-debug', 'med-solr-debug']],
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        'json' => [
          '#type' => 'html_tag',
          '#tag' => 'code',
          'value' => ['#plain_text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR)],
        ],
      ],
    ];
  }

  /**
   * Explains why the homepage prefers this strategy for the query.
   */
  private function whyThisOne($text): array {
    $help = SearchForm::metricHelp($this->t('Why this one?'), $text);
    $help['#attributes']['class'][] = 'med-why-this-one';
    return $help;
  }

  /**
   * Attributes for the card the homepage would use: ribbon and emphasis.
   */
  private function homepageCard(array $classes = []): array {
    return [
      'class' => array_merge($classes, ['med-homepage-card']),
      'aria-description' => (string) $this->t('The homepage uses this search for your query.'),
    ];
  }

  /**
   * Diagonal corner ribbon in its own clipping box, so popups are not cut.
   */
  private function cornerRibbon(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['med-corner-ribbon'], 'aria-hidden' => 'true'],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('Homepage'),
      ],
    ];
  }

  /**
   * Marks the strategy's role in the homepage experience.
   */
  private function homepageRibbon($label): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['med-homepage-ribbon']],
      '#value' => $label,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $query = trim((string) ($form_state->getTriggeringElement()['#med_example'] ?? $form_state->getValue('query')));
    if ($query === '' || mb_strlen($query) > 500) {
      $form_state->setErrorByName('query', $this->t('Enter a query between 1 and 500 characters.'));
    }
    $form_state->setValue('query', $query);
    $input = $form_state->getUserInput();
    $input['query'] = $query;
    $form_state->setUserInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $response = [];
    foreach (['lexical', 'semantic', 'hybrid'] as $id) {
      $start = hrtime(TRUE);
      try {
        $strategy = $this->strategies->get($id);
        $query = (string) $form_state->getValue('query');
        $can_debug = $this->currentUser()->hasPermission('view med search ranking details');
        $result = $id === 'lexical' ? $strategy->search($query, 5) : $strategy->search($query, 5, $can_debug);
        $result['comparison_ms'] = (hrtime(TRUE) - $start) / 1e6;
        $response[$id] = $result;
      }
      catch (\RuntimeException) {
        $response[$id] = NULL;
      }
    }
    $query = (string) $form_state->getValue('query');
    // Questions are answered automatically, as on the homepage.
    if ($this->urgency->detect($query) === NULL
      && !SearchForm::usesHomepageHybrid($query, NULL)) {
      try {
        $answer = $this->strategies->get('conversational')->search($query, NULL, $this->currentUser()->hasPermission('view med search ranking details'));
      }
      catch (\RuntimeException) {
        $answer = ['answer' => ['status' => 'unavailable'], 'results' => []];
      }
      // The source-free comparison runs only for the fixed educational demo.
      if ($query === self::BASELINE_QUESTION) {
        try {
          $baseline = $this->generator->generate(self::BASELINE_INSTRUCTIONS, self::BASELINE_QUESTION, [
            'type' => 'object',
            'properties' => ['answer' => ['type' => 'string']],
            'required' => ['answer'],
          ]);
          $text = trim(is_string($baseline['answer'] ?? NULL) ? $baseline['answer'] : '');
          if ($text !== '' && mb_strlen($text) <= 600 && !preg_match('/\b(?:you|your)\b/i', $text)) {
            $safe = TRUE;
            foreach (ConversationalSearch::UNSAFE_PATTERNS as $pattern) {
              $safe = $safe && !preg_match($pattern, $text);
            }
            if ($safe) {
              $answer['baseline'] = $text;
            }
          }
        }
        catch (\RuntimeException) {
          // The cited answer remains available if the baseline fails.
        }
      }
      $form_state->setTemporaryValue('comparison_answer', $answer);
    }
    $form_state->setTemporaryValue('comparison', $response)->disableCache();
    $form_state->setRebuild();
  }

}
