/**
 * @file
 * Developer mode switch. Remembers only on/off; never stores search text.
 */
((Drupal, once) => {
  const KEY = 'medDiscoverDeveloperMode';
  const read = () => {
    try {
      return window.localStorage.getItem(KEY) === '1';
    }
    catch (e) {
      return false;
    }
  };
  const apply = (on) => {
    document.body.classList.toggle('med-dev-mode', on);
    document.querySelectorAll('[data-med-dev-toggle]').forEach((button) => {
      button.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  };
  Drupal.behaviors.medDeveloperMode = {
    attach(context) {
      once('med-metric-dismiss', 'body', document).forEach((body) => {
        const selector = '.med-metric-help';
        const close = (help) => {
          help.open = false;
          help.classList.add('is-dismissed');
        };
        body.addEventListener('click', (event) => {
          const openStrategy = event.target.closest('[data-med-strategy-open]');
          if (openStrategy) {
            openStrategy.closest('tr').querySelector('dialog').showModal();
          }
          if (event.target.closest('[data-med-strategy-close]')) {
            event.target.closest('dialog').close();
          }
          const active = event.target.closest(selector);
          body.querySelectorAll(selector + '[open]').forEach((help) => {
            if (help !== active) {
              close(help);
            }
          });
          if (active && event.target.closest('summary')) {
            active.classList.toggle('is-dismissed', active.open);
          }
        });
        body.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            body.querySelectorAll(selector + '[open], ' + selector + ':hover, ' + selector + ':focus-within').forEach(close);
          }
        });
        for (const type of ['pointerout', 'focusout']) {
          body.addEventListener(type, (event) => {
            const help = event.target.closest(selector);
            if (help && !help.contains(event.relatedTarget)) {
              help.classList.remove('is-dismissed');
            }
          });
        }
      });
      once('med-dev-toggle', '[data-med-dev-toggle]', context).forEach((button) => {
        apply(read());
        button.addEventListener('click', () => {
          const on = !document.body.classList.contains('med-dev-mode');
          try {
            window.localStorage.setItem(KEY, on ? '1' : '0');
          }
          catch (e) {
            // Private mode: the switch still works for this page.
          }
          apply(on);
        });
      });
    },
  };
})(Drupal, once);
