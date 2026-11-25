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
