<?php

namespace Drupal\med_activation;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Lists activation rules in display priority, with drag-and-drop ordering.
 */
class ActivationRuleListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'med_activation_rule_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'label' => $this->t('Rule'),
      'category' => $this->t('Resource category'),
      'cta' => $this->t('Call to action'),
      'status' => $this->t('Status'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\med_activation\ActivationRuleInterface $entity */
    return [
      'label' => $entity->label(),
      'category' => ['#plain_text' => $entity->getCategory()],
      'cta' => ['#plain_text' => $entity->getCtaLabel()],
      'status' => ['#plain_text' => $entity->status() ? $this->t('Enabled') : $this->t('Disabled')],
    ] + parent::buildRow($entity);
  }

}
