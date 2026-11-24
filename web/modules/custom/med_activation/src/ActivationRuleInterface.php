<?php

namespace Drupal\med_activation;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * A deterministic rule turning a resource category into a call to action.
 */
interface ActivationRuleInterface extends ConfigEntityInterface {

  /**
   * MedlinePlus resource category name, for example "Clinical Trials".
   */
  public function getCategory(): string;

  /**
   * Visible call-to-action label, for example "Explore Clinical Trials".
   */
  public function getCtaLabel(): string;

  /**
   * Display priority; lower values come first.
   */
  public function getWeight(): int;

  /**
   * Search-context keywords that move this rule ahead of others.
   *
   * @return string[]
   *   Keywords.
   */
  public function getKeywords(): array;

  /**
   * Whether only resources naming their providing organization qualify.
   */
  public function requiresOrganization(): bool;

}
