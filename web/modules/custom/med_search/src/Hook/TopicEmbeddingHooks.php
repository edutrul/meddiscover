<?php

namespace Drupal\med_search\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Queue\QueueFactory;
use Drupal\med_search\SemanticSearch;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Queues embedding work when a published topic's searchable text changes.
 */
final class TopicEmbeddingHooks {

  public function __construct(
    #[Autowire(service: 'med_search.search')]
    private SemanticSearch $search,
    #[Autowire(service: 'queue')]
    private QueueFactory $queue,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_insert() and hook_ENTITY_TYPE_update().
   */
  #[Hook('node_insert')]
  #[Hook('node_update')]
  public function queueEmbedding(NodeInterface $node): void {
    if ($node->bundle() !== 'med_health_topic' || !$node->isPublished()
      || !$node->isDefaultRevision() || $node->language()->getId() !== 'en') {
      return;
    }
    // Skip when the embedding input did not change (for example, an alias or
    // a field that is not embedded).
    $original = $node->getOriginal();
    if ($original && $original->isPublished() && $this->search->input($original) === $this->search->input($node)) {
      return;
    }
    $this->queue->get('med_topic_embedding')->createItem((int) $node->id());
  }

}
