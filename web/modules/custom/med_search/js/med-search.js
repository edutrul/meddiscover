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
        form.addEventListener('htmx:beforeRequest', () => {
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
