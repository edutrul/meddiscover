/**
 * @file
 * Per-document search history. Query and results stay in page memory.
 */
((Drupal, once) => {
  const selector = 'form[data-med-search]';
  const snapshots = new Map();
  const tracked = new WeakSet();
  const pageKey = crypto.getRandomValues(new Uint32Array(2)).join('-');
  let next = 0;
  let initial;
  let initialUrl;

  function copy(form) {
    const snapshot = form.cloneNode(true);
    snapshot.removeAttribute('data-once');
    snapshot.querySelectorAll('[data-once]').forEach((element) => element.removeAttribute('data-once'));
    return snapshot;
  }

  function applyView(form, view, topic = 'all') {
    const results = form.querySelector('.med-results');
    if (!results) {
      return;
    }
    const filter = results.querySelector('.med-result-filter');
    if (filter) {
      filter.hidden = false;
      filter.querySelectorAll('[data-med-result-view]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.medResultView === view));
      });
    }
    const topicResults = results.querySelector('[data-med-topic-results]');
    if (topicResults) {
      topicResults.querySelector('.med-topic-facet').hidden = view === 'answer';
      topicResults.querySelectorAll('[data-med-topic-filter]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.medTopicFilter === topic));
      });
    }
    // Urgent notices and search errors are always visible.
    results.querySelectorAll('.med-answer:not(.med-answer--urgent)').forEach((answer) => {
      answer.hidden = view === 'topics';
    });
    let matching = 0;
    results.querySelectorAll('.result-card').forEach((card) => {
      const groups = (card.dataset.medTopicGroups ?? '').split(' ');
      const matches = topic === 'all' || groups.includes(topic);
      matching += Number(matches);
      card.hidden = view === 'answer' || !matches;
    });
    results.querySelector('[data-med-results-heading]').textContent = Drupal.formatPlural(matching, '1 health topic', '@count health topics');
    results.querySelectorAll('[data-med-topic-heading]').forEach((heading) => {
      heading.hidden = view === 'answer';
    });
  }

  function remember(form, view = 'all', topic = 'all') {
    const id = `${pageKey}-${++next}`;
    snapshots.set(id, { form: copy(form), view, topic });
    if (snapshots.size > 16) {
      snapshots.delete(snapshots.keys().next().value);
    }
    const url = new URL(initialUrl);
    url.searchParams.set('results', id);
    if (view !== 'all') {
      url.searchParams.set('view', view);
    }
    history.pushState({ medSearch: id }, '', url);
  }

  Drupal.behaviors.medSearchHistory = {
    attach(context) {
      if (context !== document) {
        return;
      }
      once('med-search-history', selector, context).forEach((form) => {
        applyView(form, 'all');
        initial = copy(form);
        const url = new URL(location.href);
        if (url.searchParams.has('results')) {
          url.searchParams.delete('results');
          url.searchParams.delete('view');
          history.replaceState(null, '', url);
        }
        initialUrl = url;
        tracked.add(form);
        document.addEventListener('htmx:afterSettle', () => {
          const current = document.querySelector(selector);
          if (current && !tracked.has(current) && current.querySelector('[data-med-results-heading]')) {
            tracked.add(current);
            applyView(current, 'all');
            remember(current);
          }
        });
        document.addEventListener('click', (event) => {
          const button = event.target.closest('[data-med-result-view], [data-med-topic-filter]');
          if (!button || button.getAttribute('aria-pressed') === 'true') {
            return;
          }
          const current = button.closest(selector);
          const selectedView = current.querySelector('[data-med-result-view][aria-pressed="true"]');
          const selectedTopic = current.querySelector('[data-med-topic-filter][aria-pressed="true"]');
          const view = button.dataset.medResultView ?? selectedView?.dataset.medResultView ?? 'all';
          const topic = button.dataset.medTopicFilter ?? selectedTopic?.dataset.medTopicFilter ?? 'all';
          applyView(current, view, topic);
          remember(current, view, topic);
        });
        window.addEventListener('popstate', (event) => {
          const current = document.querySelector(selector);
          if (!current) {
            return;
          }
          window.htmx?.trigger(current, 'htmx:abort');
          const snapshot = snapshots.get(event.state?.medSearch);
          const restored = (snapshot?.form ?? initial).cloneNode(true);
          tracked.add(restored);
          applyView(restored, snapshot?.view ?? 'all', snapshot?.topic ?? 'all');
          Drupal.detachBehaviors(current);
          current.replaceWith(restored);
          window.htmx?.process(restored);
          Drupal.attachBehaviors(restored.parentElement);
          if (!snapshot && event.state?.medSearch) {
            history.replaceState(null, '', initialUrl);
          }
        });
      });
    },
  };
})(Drupal, once);
