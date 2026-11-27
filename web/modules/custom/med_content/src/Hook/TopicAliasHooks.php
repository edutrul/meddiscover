<?php

namespace Drupal\med_content\Hook;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Creates stable, readable aliases for published health topics.
 */
final class TopicAliasHooks {

  public function __construct(
    #[Autowire(service: 'path_alias.manager')]
    private AliasManagerInterface $aliasManager,
    #[Autowire(service: 'transliteration')]
    private TransliterationInterface $transliteration,
    private EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Creates an alias when a published topic has none.
   */
  #[Hook('node_insert')]
  #[Hook('node_update')]
  public function ensureAlias(NodeInterface $node): void {
    if ($node->bundle() !== 'med_health_topic' || !$node->isPublished()
      || !$node->isDefaultRevision() || $node->language()->getId() !== 'en') {
      return;
    }

    $path = '/node/' . $node->id();
    if ($this->aliasManager->getAliasByPath($path, 'en') !== $path) {
      return;
    }

    $text = $this->transliteration->transliterate($node->label(), 'en');
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
    $slug = rtrim(substr($slug, 0, 180), '-') ?: 'topic';
    $alias = '/health-topics/' . $slug;
    $candidate = $alias;
    $suffix = (int) $node->id();
    while ($this->aliasManager->getPathByAlias($candidate, 'en') !== $candidate) {
      $candidate = $alias . '-' . $suffix++;
    }

    $this->entityTypeManager->getStorage('path_alias')
      ->create(['path' => $path, 'alias' => $candidate, 'langcode' => 'en'])
      ->save();
  }

}
