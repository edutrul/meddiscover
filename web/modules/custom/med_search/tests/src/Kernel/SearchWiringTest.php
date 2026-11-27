<?php

namespace Drupal\Tests\med_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\med_search\HomepageRouter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;

/**
 * The real container: tagged strategies, routes and shared services.
 */
#[Group('med_search')]
#[RunTestsInSeparateProcesses]
final class SearchWiringTest extends KernelTestBase {

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
    'med_content',
    'med_search',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['med_search']);
  }

  /**
   * The service collector registers every tagged strategy, self-described.
   */
  public function testRegistryCollectsTaggedStrategies(): void {
    $registry = $this->container->get('med_search.strategies');
    $this->assertEqualsCanonicalizing(['semantic', 'lexical', 'hybrid', 'conversational'], $registry->ids());
    $this->assertSame('Hybrid (Keyword + Semantic)', (string) $registry->get('hybrid')->label());
    $this->assertSame('Ask (Conversational)', (string) $registry->get('conversational')->label());
    $this->assertNotSame('', (string) $registry->get('lexical')->description());
    $this->expectException(\InvalidArgumentException::class);
    $registry->get('unknown');
  }

  /**
   * Each search route reuses SearchForm and selects a strategy by default.
   */
  public function testRoutesSelectStrategies(): void {
    $routes = $this->container->get('router.route_provider');
    foreach (['lexical', 'semantic', 'hybrid', 'conversational'] as $id) {
      $route = $routes->getRouteByName('med_search.' . $id);
      $this->assertSame('/search/' . $id, $route->getPath());
      $this->assertSame('\Drupal\med_search\Form\SearchForm', $route->getDefault('_form'));
      $this->assertSame($id, $route->getDefault('strategy'));
    }
    $home = $routes->getRouteByName('med_search.home');
    $this->assertSame('/search', $home->getPath());
    $this->assertTrue($home->getDefault('automatic_strategy'));
  }

  /**
   * Shared services resolve from the container and work together.
   */
  public function testSharedServices(): void {
    $router = $this->container->get('med_search.homepage_router');
    $this->assertSame(HomepageRouter::MED_HYBRID, $router->route('knee pain'));
    $this->assertSame(HomepageRouter::MED_ASK, $router->route('What is HbA1c?'));
    $this->assertStringEndsWith('/med_search/images/', $this->container->get('med_search.strategy_brand')->imagePath());
    $this->assertInstanceOf(LoggerInterface::class, $this->container->get('logger.channel.med_search'));
    $this->assertSame(0.62, (float) $this->config('med_search.settings')->get('threshold'));
  }

}
