<?php

namespace Drupal\med_activation\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\med_activation\Entity\ActivationRule;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add and edit form for activation rules.
 */
class ActivationRuleForm extends EntityForm {

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\med_activation\ActivationRuleInterface $rule */
    $rule = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative label'),
      '#default_value' => $rule->label(),
      '#required' => TRUE,
      '#maxlength' => 64,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rule->id(),
      '#machine_name' => ['exists' => [ActivationRule::class, 'load']],
      '#disabled' => !$rule->isNew(),
    ];
    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('MedlinePlus resource category'),
      '#options' => $this->categories(),
      '#default_value' => $rule->getCategory(),
      '#required' => TRUE,
      '#description' => $this->t('Only imported categories are available, so every call to action links to a real source URL.'),
    ];
    $form['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Call-to-action label'),
      '#default_value' => $rule->getCtaLabel(),
      '#required' => TRUE,
      '#maxlength' => 40,
      '#description' => $this->t('Describe the destination, for example “Explore Clinical Trials”. Do not promise outcomes, bookings or clinical advice.'),
    ];
    $form['keywords'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Search-context keywords'),
      '#default_value' => implode("\n", $rule->getKeywords()),
      '#rows' => 4,
      '#description' => $this->t('One per line. When a search contains one of these words, this rule is shown before rules without a match. Matching happens only while the page is built; queries are not stored.'),
    ];
    $form['require_organization'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require organization attribution'),
      '#default_value' => $rule->requiresOrganization(),
      '#description' => $this->t('Only link resources that name the organization providing them.'),
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Display priority'),
      '#default_value' => $rule->getWeight(),
      '#delta' => 50,
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $rule->status(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    if (!isset($this->categories()[$form_state->getValue('category')])) {
      $form_state->setErrorByName('category', $this->t('Choose an imported resource category.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $keywords = preg_split('/\R/', (string) $form_state->getValue('keywords'));
    $form_state->setValue('keywords', array_values(array_unique(array_filter(array_map(fn($k) => mb_strtolower(trim($k)), $keywords)))));
    return parent::buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);
    $this->messenger()->addStatus($this->t('Saved the %label activation rule.', ['%label' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

  /**
   * Imported resource category names, keyed by name.
   */
  private function categories(): array {
    $names = [];
    foreach ($this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'med_resource_categories']) as $term) {
      $names[$term->label()] = $term->label();
    }
    ksort($names);
    return $names;
  }

}
