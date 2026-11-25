/**
 * @file
 * Search form enhancements around core HTMX: focus and error state.
 *
 * The form works without JavaScript; this only improves the swapped version.
 */
((Drupal, once) => {
  Drupal.behaviors.medSearch = {
    attach(context) {
      once('med-search', 'form[data-med-search]', context).forEach((form) => {
        const error = form.querySelector('[data-med-search-error]');
        // Show the chosen example in the box immediately, before the
        // request returns.
        const box = form.querySelector('input[name="query"]');
        const buttons = form.querySelectorAll('.med-examples__button');
        const select = (value) => {
          buttons.forEach((button) => {
            const on = value !== '' && button.value.toLowerCase() === value.trim().toLowerCase();
            button.classList.toggle('is-selected', on);
            if (on) {
              button.setAttribute('aria-current', 'true');
            }
            else {
              button.removeAttribute('aria-current');
            }
          });
        };
        buttons.forEach((button) => {
          button.addEventListener('click', () => {
            if (box) {
              box.value = button.value;
            }
            select(button.value);
          });
        });
        // Typing something else clears the selected example.
        if (box) {
          box.addEventListener('input', () => select(box.value));
        }
        const suggestions = form.querySelector('[data-med-suggestions]');
        if (box && suggestions) {
          const phrases = [
            ...Array.from(buttons, (button) => button.value),
            ...JSON.parse(suggestions.dataset.medSuggestions),
          ];
          const submit = form.querySelector('input[type="submit"]:not(.med-examples__button)');
          const close = () => {
            suggestions.hidden = true;
          };
          box.addEventListener('input', () => {
            const query = box.value.trim().toLowerCase();
            suggestions.replaceChildren();
            const matched = new Set();
            const prefixMatches = [];
            const otherMatches = [];
            if (query.length >= 2) {
              phrases.forEach((title) => {
                const normalized = title.toLowerCase();
                if (matched.has(normalized)) {
                  return;
                }
                matched.add(normalized);
                if (normalized.startsWith(query)) {
                  prefixMatches.push(title);
                }
                else if (normalized.includes(query)) {
                  otherMatches.push(title);
                }
              });
            }
            const matches = [...prefixMatches, ...otherMatches].slice(0, 5);
            matches.forEach((title) => {
              const button = document.createElement('button');
              button.type = 'button';
              button.textContent = title;
              button.addEventListener('click', () => {
                box.value = title;
                select(title);
                close();
                submit.click();
              });
              suggestions.append(button);
            });
            suggestions.hidden = matches.length === 0;
          });
          form.addEventListener('keydown', (event) => {
            if (suggestions.hidden) {
              return;
            }
            if (event.key === 'Escape' && (event.target === box || suggestions.contains(event.target))) {
              event.preventDefault();
              box.focus();
              close();
            }
            const options = Array.from(suggestions.querySelectorAll('button'));
            const index = options.indexOf(event.target);
            if (event.target !== box && index === -1) {
              return;
            }
            if (event.key === 'ArrowDown') {
              event.preventDefault();
              options[Math.min(index + 1, options.length - 1)]?.focus();
            }
            if (event.key === 'ArrowUp') {
              event.preventDefault();
              if (index === -1) {
                options.at(-1)?.focus();
              }
              else if (index === 0) {
                box.focus();
              }
              else {
                options[index - 1].focus();
              }
            }
          });
          form.addEventListener('focusout', (event) => {
            if (event.relatedTarget !== box && !suggestions.contains(event.relatedTarget)) {
              close();
            }
          });
          form.addEventListener('submit', close);
        }
        const loading = form.querySelector('[data-med-question-pattern]');
        const questionPattern = loading && new RegExp(loading.dataset.medQuestionPattern, 'iu');
        form.addEventListener('htmx:beforeRequest', () => {
          if (loading && box) {
            const answer = questionPattern.test(box.value.trim());
            loading.dataset.medLoadingMode = answer ? 'answer' : 'search';
            loading.textContent = answer ? Drupal.t('Writing a cited answer from MedlinePlus…') :
              Drupal.t('Searching MedlinePlus topics…');
          }
          if (error) {
            error.hidden = true;
          }
        });
        const fail = () => {
          if (error) {
            error.hidden = false;
            error.focus();
          }
        };
        form.addEventListener('htmx:responseError', fail);
        form.addEventListener('htmx:sendError', fail);
        form.addEventListener('htmx:timeout', fail);
      });
      // After a swap, move focus to the new results so screen readers and
      // keyboard users land on them.
      once('med-search-focus', '[data-med-results-heading]', context).forEach((heading) => {
        if (context !== document) {
          heading.focus();
        }
      });
    },
  };
})(Drupal, once);
