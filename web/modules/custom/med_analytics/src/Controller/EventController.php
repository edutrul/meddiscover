<?php

namespace Drupal\med_analytics\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\med_analytics\AnalyticsRecorder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives click beacons. Only allowlisted click events are counted.
 */
class EventController implements ContainerInjectionInterface {

  public function __construct(private AnalyticsRecorder $recorder) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('med_analytics.recorder'));
  }

  /**
   * Count one click; the response never echoes the input.
   */
  public function record(Request $request): Response {
    $event = (string) $request->request->get('event', '');
    $dimension = (string) $request->request->get('dimension', '');
    $accepted = strlen($event) <= 64 && strlen($dimension) <= 64
      && in_array($event, AnalyticsRecorder::CLIENT_EVENTS, TRUE)
      && $this->recorder->record($event, $dimension);
    $response = new Response('', $accepted ? 204 : 400);
    $response->headers->set('Cache-Control', 'no-store');
    return $response;
  }

}
