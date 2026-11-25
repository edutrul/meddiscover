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
