<?php

namespace Drupal\Tests\med_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\med_search\AiEmbedder;
use Drupal\med_search\AiGenerator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * AiEmbedder and AiGenerator through the real Drupal AI plugin system.
 *
 * The AI module's "echoai" test provider returns neither vectors nor JSON,
 * so it exercises the guarantees medDiscover adds on top of any provider:
 * defaults come from ai.settings, and bad output fails with a generic error
 * that never contains the query.
 */
#[Group('med_search')]
#[RunTestsInSeparateProcesses]
final class AiProviderAdapterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'filter',
    'text',
    'link',
    'datetime',
    'taxonomy',
    'path_alias',
    'migrate',
    'search_api',
    'search_api_solr',
    'key',
    'ai',
    'ai_provider_ollama',
    'ai_test',
    'med_content',
    'med_search',
  ];

  /**
   * The private health question used in every call.
   */
  private const QUERY = 'synthetic private health question';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['ai', 'med_search']);
    // Small synthetic vectors.
    $this->config('med_search.settings')->set('dimensions', 3)->save();
  }

  /**
   * Point the AI module's default providers at the echo test provider.
   */
  private function useEchoProvider(): void {
    $this->config('ai.settings')
      ->set('default_providers.embeddings', ['provider_id' => 'echoai', 'model_id' => 'echo-embed'])
      ->set('default_providers.chat_with_structured_response', ['provider_id' => 'echoai', 'model_id' => 'echo-chat'])
      ->save();
  }

  /**
   * Models come from the AI module defaults; none configured fails clearly.
   */
  public function testDefaultsComeFromAiSettings(): void {
    $embedder = new AiEmbedder($this->container->get('ai.provider'), $this->container->get('config.factory'));
    $generator = new AiGenerator($this->container->get('ai.provider'), $this->container->get('config.factory'));
    $this->assertSame('', $embedder->modelId());
    try {
      $embedder->embedQuery(self::QUERY);
      $this->fail('A missing default provider must fail.');
    }
    catch (\RuntimeException $e) {
      $this->assertStringContainsString('No default embeddings provider', $e->getMessage());
    }

    $this->useEchoProvider();
    $this->assertSame('echo-embed', $embedder->modelId());
    $this->assertSame('echo-chat', $generator->modelId());
    $this->assertSame('search_query: knee pain', $embedder->queryInput('knee pain'));
  }

  /**
   * Output that is not a valid vector or JSON fails without the query.
   */
  public function testInvalidProviderOutputIsRejectedPrivately(): void {
    $this->useEchoProvider();
    $embedder = new AiEmbedder($this->container->get('ai.provider'), $this->container->get('config.factory'));
    $generator = new AiGenerator($this->container->get('ai.provider'), $this->container->get('config.factory'));

    foreach ([
      fn() => $embedder->embedQuery(self::QUERY),
      fn() => $generator->generate('Fixed instructions.', self::QUERY, ['type' => 'object']),
    ] as $call) {
      try {
        $call();
        $this->fail('Invalid provider output must be rejected.');
      }
      catch (\RuntimeException $e) {
        $this->assertStringNotContainsString(self::QUERY, $e->getMessage());
      }
    }
  }

  /**
   * Vector validation: dimensions, numbers, non-zero norm.
   */
  public function testValidateVector(): void {
    AiEmbedder::validateVector([0.6, 0.8, 0.0], 3);
    foreach ([[0, 0, 0], [1, 0], [NAN, 0, 0], ['bad', 0, 1]] as $bad) {
      try {
        AiEmbedder::validateVector($bad, 3);
        $this->fail('Invalid vector accepted.');
      }
      catch (\RuntimeException) {
        $this->addToAssertionCount(1);
      }
    }
  }

}
