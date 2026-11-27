<?php

namespace Drupal\med_search\Drush\Commands;

use Drupal\med_search\EmbedderInterface;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides terminal commands for medDiscover search.
 */
final class MedSearchCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    #[Autowire(service: 'med_search.embedder')]
    private readonly EmbedderInterface $embedder,
  ) {
    parent::__construct();
  }

  /**
   * Prints the query vector the semantic search would use.
   */
  #[CLI\Command(name: 'med-search:embed-query')]
  #[CLI\Argument(name: 'query', description: 'Text to embed as a search query.')]
  #[CLI\Usage(name: 'drush med-search:embed-query "knee pain when walking"', description: 'Print one query vector as JSON.')]
  public function embedQuery(string $query): void {
    $query = trim($query);
    if ($query === '' || mb_strlen($query) > 500) {
      throw new \InvalidArgumentException('Enter a query between 1 and 500 characters.');
    }
    $this->output()->writeln(json_encode($this->embedder->embedQuery($query), JSON_THROW_ON_ERROR));
  }

}
