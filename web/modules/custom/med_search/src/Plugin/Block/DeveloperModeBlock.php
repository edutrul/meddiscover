<?php

namespace Drupal\med_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Developer mode switch: shows or hides ranking and timing details.
 *
 * The details are rendered only for users with the ranking permission; this
 * switch only toggles their visibility in the browser.
 */
#[Block(
  id: 'med_search_developer_mode',
  admin_label: new TranslatableMarkup('medDiscover developer mode'),
  category: new TranslatableMarkup('medDiscover'),
)]
final class DeveloperModeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'type' => 'button',
        'class' => ['med-dev-toggle'],
        'aria-pressed' => 'false',
        'data-med-dev-toggle' => '',
        'title' => $this->t('Show ranking scores and timings for each search'),
      ],
      '#value' => '<span aria-hidden="true">&lt;/&gt;</span> ' . $this->t('Developer mode'),
      '#attached' => ['library' => ['med_search/developer-mode']],
      '#cache' => ['contexts' => ['user.permissions']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'view med search ranking details');
  }

}
