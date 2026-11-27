<?php

namespace Drupal\med_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Structured answers through the AI module's default structured-chat provider.
 *
 * Uses the "chat_with_structured_response" operation type: the JSON schema is
 * sent as a structured-output constraint, so citation labels are limited to
 * the provided sources at decoding time. Deterministic settings (temperature
 * 0, fixed seed), a pinned Ollama model digest and a "stop" finish reason are
 * required; anything else is a generic failure that never contains the
 * prompt.
 */
final class AiGenerator implements GeneratorInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * The AI module operation type used for answers.
   */
  public const MED_OPERATION = 'chat_with_structured_response';

  public function __construct(
    private AiProviderPluginManager $providers,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function modelId(): string {
    return (string) ($this->providers->getDefaultProviderForOperationType(self::MED_OPERATION)['model_id'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function generate(string $system, string $user, array $schema): array {
    $defaults = $this->providers->getDefaultProviderForOperationType(self::MED_OPERATION);
    if (empty($defaults['provider_id']) || empty($defaults['model_id'])) {
      throw new \RuntimeException('No default structured chat provider is configured.');
    }
    $config = $this->configFactory->get('med_search.settings');
    try {
      $provider = $this->providers->createInstance($defaults['provider_id']);
      if ($defaults['provider_id'] === 'ollama') {
        $this->verifyDigest($provider->getControlClient()->getModels(), $defaults['model_id'], (string) $config->get('generation_model_digest'));
      }
      $provider->setConfiguration([
        'temperature' => 0,
        'seed' => 7,
        // Constrained decoding can loop on whitespace; cap the output and
        // treat truncation as a failure below.
        'max_tokens' => (int) $config->get('generation_max_tokens'),
      ]);
      $provider->setChatSystemRole($system);
      $input = new ChatInput([new ChatMessage('user', $user)]);
      $input->setChatStructuredJsonSchema([
        'name' => 'med_answer',
        'schema' => $schema,
      ]);
      $output = $provider->chat($input, $defaults['model_id'], ['med_search']);
      // Drupal 11 renders web requests inside a Fiber, where the AI module
      // consumes a stream and rebuilds the message: the raw output is then a
      // stream object. A truncated answer still fails below, as invalid JSON.
      $raw = $output->getRawOutput();
      if (is_array($raw) && ($raw['choices'][0]['finish_reason'] ?? 'stop') !== 'stop') {
        throw new \RuntimeException('Truncated answer.');
      }
      $decoded = json_decode($output->getNormalized()->getText(), TRUE, 64, JSON_THROW_ON_ERROR);
    }
    catch (\Throwable $e) {
      // Class only: messages can contain the prompt.
      $this->logger?->warning('Answer generation failed: @class.', ['@class' => $e::class]);
      throw new \RuntimeException('Answer generation is unavailable.');
    }
    if (!is_array($decoded)) {
      throw new \RuntimeException('Answer generation returned an invalid structure.');
    }
    return $decoded;
  }

  /**
   * Fail unless Ollama serves the pinned model digest.
   */
  private function verifyDigest(array $models, string $model, string $digest): void {
    foreach ($models['models'] ?? [] as $candidate) {
      if (($candidate['name'] ?? '') === $model && ($candidate['digest'] ?? '') === $digest) {
        return;
      }
    }
    throw new \RuntimeException('The pinned answer model is unavailable or has changed.');
  }

}
