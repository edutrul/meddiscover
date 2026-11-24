<?php

namespace Drupal\med_activation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\med_activation\ActivationRuleInterface;
use Drupal\med_activation\ActivationRuleListBuilder;
use Drupal\med_activation\Form\ActivationRuleForm;

/**
 * Activation rule configuration entity.
 */
#[ConfigEntityType(
  id: 'med_activation_rule',
  label: new TranslatableMarkup('Activation rule'),
  label_collection: new TranslatableMarkup('Activation rules'),
  label_singular: new TranslatableMarkup('activation rule'),
  label_plural: new TranslatableMarkup('activation rules'),
  config_prefix: 'rule',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'status' => 'status',
    'weight' => 'weight',
  ],
  handlers: [
    'list_builder' => ActivationRuleListBuilder::class,
    'form' => [
      'add' => ActivationRuleForm::class,
      'edit' => ActivationRuleForm::class,
      'delete' => EntityDeleteForm::class,
    ],
    'route_provider' => ['html' => AdminHtmlRouteProvider::class],
  ],
  links: [
    'collection' => '/admin/config/search/med-activation',
    'add-form' => '/admin/config/search/med-activation/add',
    'edit-form' => '/admin/config/search/med-activation/{med_activation_rule}',
    'delete-form' => '/admin/config/search/med-activation/{med_activation_rule}/delete',
  ],
  admin_permission: 'administer med activation',
  label_count: [
    'singular' => '@count activation rule',
    'plural' => '@count activation rules',
  ],
  config_export: [
    'id',
    'label',
    'weight',
    'category',
    'cta_label',
    'keywords',
    'require_organization',
  ],
)]
class ActivationRule extends ConfigEntityBase implements ActivationRuleInterface {

  /**
   * Machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * Administrative label.
   *
   * @var string
   */
  protected $label;

  /**
   * Display priority.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * MedlinePlus resource category name.
   *
   * @var string
   */
  protected $category = '';

  /**
   * Call-to-action label.
   *
   * @var string
   */
  protected $cta_label = '';

  /**
   * Search-context keywords.
   *
   * @var string[]
   */
  protected $keywords = [];

  /**
   * Whether the providing organization must be named.
   *
   * @var bool
   */
  protected $require_organization = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getCategory(): string {
    return (string) $this->category;
  }

  /**
   * {@inheritdoc}
   */
  public function getCtaLabel(): string {
    return (string) $this->cta_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return (int) $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeywords(): array {
    return array_values((array) $this->keywords);
  }

  /**
   * {@inheritdoc}
   */
  public function requiresOrganization(): bool {
    return (bool) $this->require_organization;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    return [$a->getWeight(), $a->id()] <=> [$b->getWeight(), $b->id()];
  }

}
