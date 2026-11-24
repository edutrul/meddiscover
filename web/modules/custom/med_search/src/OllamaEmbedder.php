<?php

namespace Drupal\med_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Local embedding adapter with pinned model identity and strict vector checks.
 */
class OllamaEmbedder {

  public function __construct(
    private ClientInterface $client,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Reject mutable model-tag changes before using existing vectors.
   */
  public function verifyModel(): void {
    $config = $this->configFactory->get('med_search.settings');
    try {
      $response = $this->client->request('GET', $config->get('endpoint') . '/api/tags', [
        'connect_timeout' => 2,
        'timeout' => 5,
      ]);
      $data = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      foreach ($data['models'] ?? [] as $model) {
        if (($model['name'] ?? '') === $config->get('model') && ($model['digest'] ?? '') === $config->get('model_digest')) {
          return;
        }
      }
    }
    catch (\Throwable) {
      throw new \RuntimeException('Embedding provider is unavailable.');
    }
    throw new \RuntimeException('The pinned embedding model is unavailable or has changed.');
  }

  /**
   * Embeds without truncation and never logs input or response bodies.
   */
  public function embed(string $text): array {
    $config = $this->configFactory->get('med_search.settings');
    try {
      $response = $this->client->request('POST', $config->get('endpoint') . '/api/embed', [
        'connect_timeout' => 2,
        'timeout' => 30,
        'json' => [
          'model' => $config->get('model'),
          'input' => $text,
          'truncate' => FALSE,
          'options' => ['num_ctx' => (int) $config->get('context_length')],
        ],
      ]);
      $data = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\Throwable) {
      // Do not retain an exception containing submitted health-query text.
      throw new \RuntimeException('Embedding generation failed; please try again later.');
    }
    if (($data['model'] ?? '') !== $config->get('model') || count($data['embeddings'] ?? []) !== 1 || !is_array($data['embeddings'][0] ?? NULL)) {
      throw new \RuntimeException('Embedding response does not match the requested model or input.');
    }
    $vector = $data['embeddings'][0];
    self::validateVector($vector, (int) $config->get('dimensions'));
    return $vector;
  }

  /**
   * Validate provider output before serializing it into a bound SQL parameter.
   */
  public static function validateVector(array $vector, int $dimensions): void {
    if (count($vector) !== $dimensions || !array_is_list($vector)) {
      throw new \RuntimeException('Embedding dimensions differ from the configured model.');
    }
    $norm = 0.0;
    foreach ($vector as $number) {
      if ((!is_float($number) && !is_int($number)) || !is_finite((float) $number)) {
        throw new \RuntimeException('Embedding contains invalid numeric values.');
      }
      $norm += $number * $number;
    }
    if (!is_finite($norm) || $norm <= 0) {
      throw new \RuntimeException('Embedding must have a finite nonzero norm.');
    }
  }

}
