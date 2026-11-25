<?php

namespace Drupal\med_search\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\med_search\SemanticSearch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Refreshes the embedding of the current published topic.
 */
#[QueueWorker(
  id: 'med_topic_embedding',
  title: new TranslatableMarkup('Refresh health topic embeddings'),
  cron: ['time' => 30],
)]
final class TopicEmbedding extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, private SemanticSearch $search) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('med_search.search'));
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    try {
      $this->search->indexTopics((int) $data);
    }
    catch (\RuntimeException) {
      throw new SuspendQueueException('Health topic embedding refresh is temporarily unavailable.');
    }
  }

}
