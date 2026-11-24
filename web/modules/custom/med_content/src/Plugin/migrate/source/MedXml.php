<?php

namespace Drupal\med_content\Plugin\migrate\source;

use Drupal\med_content\Corpus;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Local XML source, with a required reviewed checksum and explicit selection.
 */
#[MigrateSource('med_xml')]
final class MedXml extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return ['key' => 'Stable source identifier'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return ['key' => ['type' => 'string', 'max_length' => 128]];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator(): \Iterator {
    $path = dirname(DRUPAL_ROOT) . '/' . $this->configuration['path'];
    $rows = Corpus::load($path, $this->configuration['sha256'], $this->configuration['selection']);
    return new \ArrayIterator($rows[$this->configuration['collection']]);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return 'Checksum-verified local MedlinePlus snapshot';
  }

}
