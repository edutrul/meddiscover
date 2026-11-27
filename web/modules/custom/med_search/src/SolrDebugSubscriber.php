<?php

namespace Drupal\med_search;

use Drupal\search_api_solr\Event\PostConvertedQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Captures search parameters for the current request's developer panel.
 */
final class SolrDebugSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [PostConvertedQueryEvent::class => ['capture', -100]];
  }

  /**
   * Records only display-safe search parameters on the originating query.
   */
  public function capture(PostConvertedQueryEvent $event): void {
    $query = $event->getSearchApiQuery();
    if (!$query->getOption('med_solr_debug')) {
      return;
    }
    $solarium = $event->getSolariumQuery();
    $request = $solarium->getRequestBuilder()->build($solarium);
    $public_keys = array_flip(['q', 'qf', 'pf', 'mm', 'fq', 'defType', 'sort', 'start', 'rows']);
    $query->setOption('med_solr_debug_params', array_intersect_key($request->getParams(), $public_keys));
  }

}
