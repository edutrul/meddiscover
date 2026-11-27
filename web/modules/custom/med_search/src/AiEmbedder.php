<?php

namespace Drupal\med_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ai\AiProviderPluginManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Embeddings through the Drupal AI module's default embeddings provider.
 *
 * The provider and model are configured in the AI module (/admin/config/ai),
 * so the backend can change without code changes. On top of the provider,
 * this class keeps three guarantees semantic search depends on:
 *
 * - Strict input: "truncate" is FALSE, so an over-long input fails instead of
 *   being silently cut (see patches/ai_provider_ollama-strict-embeddings).
 * - Pinned model: with Ollama, the model digest must match the one the stored
 *   topic vectors were made with.
 * - Validated vectors: the expected dimensions, finite numbers, non-zero.
 */
final class AiEmbedder implements EmbedderInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Vectors computed during this request, keyed by input text.
   *
   * @var array<string, float[]>
   */
  private array $vectors = [];

  public function __construct(
    private AiProviderPluginManager $providers,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function modelId(): string {
    return (string) ($this->defaults()['model_id'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function verifyModel(): void {
    $defaults = $this->defaults();
    if (!$defaults) {
      throw new \RuntimeException('No default embeddings provider is configured.');
    }
    // Digests are an Ollama concept; other providers version models by name.
    if ($defaults['provider_id'] !== 'ollama') {
      return;
    }
    try {
      $models = $this->providers->createInstance('ollama')->getControlClient()->getModels();
    }
    catch (\Throwable $e) {
      $this->logger?->warning('Embedding model check failed: @class.', ['@class' => $e::class]);
      throw new \RuntimeException('Embedding provider is unavailable.');
    }
    $digest = $this->configFactory->get('med_search.settings')->get('model_digest');
    foreach ($models['models'] ?? [] as $model) {
      if (($model['name'] ?? '') === $defaults['model_id'] && ($model['digest'] ?? '') === $digest) {
        return;
      }
    }
    throw new \RuntimeException('The pinned embedding model is unavailable or has changed.');
  }

  /**
   * {@inheritdoc}
   */
  public function queryInput(string $query): string {
    // nomic-embed-text expects task prefixes; topics use "search_document: ".
    return 'search_query: ' . $query;
  }

  /**
   * {@inheritdoc}
   */
  public function embedQuery(string $query): array {
    $this->verifyModel();
    return $this->embed($this->queryInput($query));
  }

  /**
   * {@inheritdoc}
   */
  public function embed(string $text): array {
    // Semantic, Hybrid and Conversational may embed the same query in one
    // request; reuse the vector instead of asking the provider again.
    if (isset($this->vectors[$text])) {
      return $this->vectors[$text];
    }
    $defaults = $this->defaults();
    if (!$defaults) {
      throw new \RuntimeException('No default embeddings provider is configured.');
    }
    $config = $this->configFactory->get('med_search.settings');
    try {
      $provider = $this->providers->createInstance($defaults['provider_id']);
      $provider->setConfiguration([
        'truncate' => FALSE,
        'num_ctx' => (int) $config->get('context_length'),
      ]);
      $vector = $provider->embeddings($text, $defaults['model_id'], ['med_search'])->getNormalized();
    }
    catch (\Throwable $e) {
      // Class only: messages can contain the query.
      $this->logger?->warning('Embedding request failed: @class.', ['@class' => $e::class]);
      throw new \RuntimeException('Embedding generation failed; please try again later.');
    }
    if (!is_array($vector)) {
      throw new \RuntimeException('Embedding response does not contain a vector.');
    }
    self::validateVector($vector, (int) $config->get('dimensions'));
    return $this->vectors[$text] = $vector;
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

  /**
   * The AI module's default provider and model for embeddings.
   *
   * @return array{provider_id: string, model_id: string}|null
   *   The defaults, or NULL when none are configured.
   */
  private function defaults(): ?array {
    $defaults = $this->providers->getDefaultProviderForOperationType('embeddings');
    return !empty($defaults['provider_id']) && !empty($defaults['model_id']) ? $defaults : NULL;
  }

}
