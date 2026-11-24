<?php

namespace Drupal\med_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Local Ollama chat model with a pinned digest and JSON-schema output.
 */
class OllamaGenerator implements GeneratorInterface {

  public function __construct(
    private ClientInterface $client,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function generate(string $system, string $user, array $schema): array {
    $config = $this->configFactory->get('med_search.settings');
    $endpoint = $config->get('endpoint');
    $model = $config->get('generation_model');
    try {
      $tags = $this->client->request('GET', $endpoint . '/api/tags', ['connect_timeout' => 2, 'timeout' => 5]);
      $tags = json_decode((string) $tags->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      $pinned = FALSE;
      foreach ($tags['models'] ?? [] as $candidate) {
        $pinned = $pinned || (($candidate['name'] ?? '') === $model && ($candidate['digest'] ?? '') === $config->get('generation_model_digest'));
      }
      if (!$pinned) {
        throw new \RuntimeException();
      }
      $response = $this->client->request('POST', $endpoint . '/api/chat', [
        'connect_timeout' => 2,
        'timeout' => (int) $config->get('generation_timeout'),
        'json' => [
          'model' => $model,
          'stream' => FALSE,
          'think' => FALSE,
          'format' => $schema,
          'options' => [
            'temperature' => 0,
            'seed' => 7,
            'num_ctx' => (int) $config->get('generation_context_length'),
            // Constrained decoding can loop on whitespace; cap it and treat
            // truncation as a failure.
            'num_predict' => (int) $config->get('generation_max_tokens'),
          ],
          'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
          ],
        ],
      ]);
      $data = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
      if (($data['done_reason'] ?? '') !== 'stop' || ($data['model'] ?? '') !== $model) {
        throw new \RuntimeException();
      }
      $output = json_decode($data['message']['content'] ?? '', TRUE, 64, JSON_THROW_ON_ERROR);
    }
    catch (\Throwable) {
      // Never keep exceptions that may contain the prompt.
      throw new \RuntimeException('Answer generation is unavailable.');
    }
    if (!is_array($output)) {
      throw new \RuntimeException('Answer generation returned an invalid structure.');
    }
    return $output;
  }

}
