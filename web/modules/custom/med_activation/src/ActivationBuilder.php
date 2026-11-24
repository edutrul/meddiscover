<?php

namespace Drupal\med_activation;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Renders calls to action with explicit reasons and external-link labels.
 */
class ActivationBuilder {

  use StringTranslationTrait;

  public function __construct(private RequestStack $requestStack) {}

  /**
   * Build the "Next steps" block for one result.
   *
   * @param array $items
   *   Output of ActivationEngine::recommend().
   * @param string $external_behavior
   *   Either same_window or new_window.
   */
  public function build(array $items, string $external_behavior): array {
    if (!$items) {
      return [];
    }
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['med-next-steps']],
      'heading' => ['#type' => 'html_tag', '#tag' => 'h4', '#value' => $this->t('Next steps')],
      'note' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Educational resources chosen by fixed rules from MedlinePlus data. They are not medical recommendations.'),
      ],
    ];
    $list = [];
    foreach ($items as $item) {
      $host = $this->externalHost($item['url']);
      $attributes = ['class' => ['button', 'button--small']];
      $suffix = '';
      if ($item['node'] === NULL && $host !== NULL) {
        $attributes['rel'] = 'noopener noreferrer';
        $attributes['data-med-external'] = $host;
        $suffix = $external_behavior === 'new_window'
          ? (string) $this->t('(external site: @host, opens in a new tab)', ['@host' => $host])
          : (string) $this->t('(external site: @host)', ['@host' => $host]);
        if ($external_behavior === 'new_window') {
          $attributes['target'] = '_blank';
        }
      }
      $attribution = $item['organizations']
        ? $this->t('@title — provided by @orgs, listed by MedlinePlus.', [
          '@title' => $item['title'],
          '@orgs' => implode(', ', $item['organizations']),
        ])
        : $this->t('@title — listed by MedlinePlus.', ['@title' => $item['title']]);
      $list[] = [
        'link' => [
          '#type' => 'link',
          // The external-site notice is part of the link text, so it is
          // announced together with the link by assistive technology.
          '#title' => $suffix === '' ? $item['label'] : [
            '#markup' => Html::escape($item['label']) . ' <span class="med-external">' . Html::escape($suffix) . '</span>',
          ],
          '#url' => $item['node'] ? $item['node']->toUrl() : Url::fromUri($item['url']),
          '#attributes' => $attributes,
        ],
        'details' => [
          '#type' => 'html_tag',
          '#tag' => 'small',
          '#value' => $this->t('@attribution Why: @reason', [
            '@attribution' => $item['type'] === 'resource' ? $attribution : '',
            '@reason' => $item['reason'],
          ]),
          '#prefix' => '<br>',
        ],
      ];
    }
    $build['list'] = ['#theme' => 'item_list', '#items' => $list];
    return $build;
  }

  /**
   * Host of an off-site URL, or NULL for this site.
   */
  public function externalHost(string $url): ?string {
    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
      return NULL;
    }
    $current = $this->requestStack->getCurrentRequest()?->getHost();
    return strcasecmp($host, (string) $current) === 0 ? NULL : preg_replace('/^www\./i', '', strtolower($host));
  }

}
