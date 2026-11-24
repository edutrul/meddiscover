/**
 * @file
 * Reports link clicks as aggregate events; never sends URLs or text.
 */
((Drupal, drupalSettings, once) => {
  Drupal.behaviors.medAnalytics = {
    attach(context) {
      const endpoint = drupalSettings.medAnalytics?.endpoint;
      if (!endpoint || !navigator.sendBeacon) {
        return;
      }
      once('med-analytics', 'a[data-med-event]', context).forEach((link) => {
        link.addEventListener('click', () => {
          const data = new FormData();
          data.append('event', link.dataset.medEvent);
          data.append('dimension', link.dataset.medDimension || '');
          navigator.sendBeacon(endpoint, data);
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
