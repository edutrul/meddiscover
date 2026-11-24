<?php

namespace Drupal\med_content;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Reads the fixed XML snapshot without network access or external entities.
 */
final class Corpus {

  /**
   * Parsed rows shared by migrations within the current request.
   *
   * @var array
   */
  private static array $cache = [];

  /**
   * Build deterministic source rows; verify integrity before parsing any XML.
   */
  public static function load(string $path, string $sha256, array $selection): array {
    $cache_key = $path . ':' . $sha256 . ':' . implode(',', $selection);
    if (isset(self::$cache[$cache_key])) {
      return self::$cache[$cache_key];
    }
    if (!is_file($path)) {
      throw new \RuntimeException('XML snapshot does not exist.');
    }
    $raw = file_get_contents($path);
    if ($raw === FALSE || hash('sha256', $raw) !== $sha256) {
      throw new \RuntimeException('XML missing or checksum differs from the reviewed snapshot.');
    }
    if (count($selection) !== count(array_unique($selection)) || !$selection) {
      throw new \RuntimeException('Selection must contain unique topic IDs.');
    }
    if (stripos($raw, '<!ENTITY') !== FALSE) {
      throw new \RuntimeException('Entity declarations are not accepted.');
    }
    $previous = libxml_use_internal_errors(TRUE);
    try {
      $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NONET);
      if ($xml === FALSE || $xml->getName() !== 'health-topics') {
        throw new \RuntimeException('Invalid health-topics XML.');
      }
    }
    finally {
      libxml_clear_errors();
      libxml_use_internal_errors($previous);
    }
    $index = $urls = [];
    foreach ($xml->{'health-topic'} as $topic) {
      $id = (string) $topic['id'];
      if (isset($index[$id])) {
        throw new \RuntimeException('Duplicate source topic ID.');
      }
      $index[$id] = $topic;
      $urls[(string) $topic['url']] = $id;
    }
    $date = \DateTimeImmutable::createFromFormat('m/d/Y H:i:s', (string) $xml['date-generated']);
    if (!$date) {
      throw new \RuntimeException('Invalid snapshot date.');
    }
    $rows = array_fill_keys(['groups', 'mesh', 'categories', 'topics', 'resources', 'relationships'], []);
    foreach ($selection as $id) {
      $id = (string) $id;
      $topic = $index[$id] ?? NULL;
      if (!$topic || (string) $topic['language'] !== 'English') {
        throw new \RuntimeException('Selection must contain existing English topics only.');
      }
      $groups = $headings = [];
      foreach ($topic->group as $group) {
        $key = (string) $group['id'];
        $rows['groups'][$key] = [
          'key' => $key,
          'name' => trim((string) $group),
          'source_id' => $key,
          'url' => (string) $group['url'],
        ];
        $groups[] = ['key' => $key];
      }
      foreach ($topic->{'mesh-heading'} as $heading) {
        $descriptor = $heading->descriptor;
        $qualifiers = [];
        foreach ($heading->qualifier as $qualifier) {
          $qualifiers[] = ['id' => (string) $qualifier['id'], 'label' => trim((string) $qualifier)];
        }
        // Preserve descriptor/qualifier pairs rather than independent tags.
        foreach ($qualifiers ?: [['id' => '', 'label' => '']] as $qualifier) {
          $key = (string) $descriptor['id'] . ':' . $qualifier['id'];
          $rows['mesh'][$key] = [
            'key' => $key,
            'source_id' => (string) $descriptor['id'],
            'name' => trim((string) $descriptor) . ($qualifier['label'] ? ' / ' . $qualifier['label'] : ''),
            'descriptor_label' => trim((string) $descriptor),
            'qualifier_id' => $qualifier['id'],
            'qualifier_label' => $qualifier['label'],
          ];
          $headings[] = ['key' => $key];
        }
      }
      $original = (string) $topic->{'full-summary'};
      $safe = self::sanitize($original);
      $created = \DateTimeImmutable::createFromFormat('m/d/Y', (string) $topic['date-created']);
      if (!$created) {
        throw new \RuntimeException('Invalid source creation date.');
      }
      $rows['topics'][] = [
        'key' => $id,
        'source_id' => $id,
        'title' => trim((string) $topic['title']),
        'description' => trim((string) $topic['meta-desc']),
        'summary_raw' => $original,
        'summary_html' => $safe,
        'summary_plain' => self::plain($safe),
        'url' => (string) $topic['url'],
        'source_date' => $created->format('Y-m-d'),
        'snapshot_date' => $date->format('Y-m-d'),
        'attribution' => 'Source: MedlinePlus, National Library of Medicine.',
        'also_called' => self::values($topic->{'also-called'}),
        'see_reference' => self::values($topic->{'see-reference'}),
        'groups' => $groups,
        'mesh' => $headings,
      ];
      $site_ordinal = 0;
      foreach ($topic->site as $site) {
        $categories = [];
        foreach ($site->{'information-category'} as $category) {
          $label = trim((string) $category);
          $key = hash('sha256', $label);
          $rows['categories'][$key] = ['key' => $key, 'name' => $label];
          $categories[] = ['key' => $key];
        }
        $rows['resources'][] = [
          'key' => $id . ':site:' . $site_ordinal++,
          'title' => trim((string) $site['title']),
          'topic' => $id,
          'url' => (string) $site['url'],
          'mapped_url' => (string) $site['language-mapped-url'],
          'categories' => $categories,
          'organizations' => self::values($site->organization),
          'standard_desc' => self::values($site->{'standard-description'}),
        ];
      }
      foreach (['related-topic' => 'curated', 'language-mapped-topic' => 'language_mapping'] as $tag => $type) {
        $ordinal = 0;
        foreach ($topic->{$tag} as $target) {
          $target_id = (string) $target['id'];
          $rows['relationships'][] = self::edge($id, $type, $ordinal++, $target_id,
            trim((string) $target), (string) $target['url'], $selection);
        }
      }
      $doc = Html::load($original);
      $ordinal = 0;
      foreach ($doc->getElementsByTagName('a') as $anchor) {
        $href = $anchor->getAttribute('href');
        $url = self::absoluteUrl($href);
        if ($url === '') {
          continue;
        }
        $canonical = preg_replace('/[?#].*$/', '', $url);
        $target_id = $urls[$canonical] ?? '';
        $target_title = $target_id ? (string) $index[$target_id]['title'] : trim($anchor->textContent);
        $rows['relationships'][] = self::edge($id, 'summary_link', $ordinal++, $target_id,
          $target_title, $url, $selection);
      }
    }
    foreach ($rows as &$collection) {
      $collection = array_values($collection);
    }
    return self::$cache[$cache_key] = $rows;
  }

  /**
   * Strip active markup and all attributes except safe links and titles.
   */
  public static function sanitize(string $html): string {
    $allowed_tags = [
      'p', 'a', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'br',
      'h2', 'h3', 'h4', 'blockquote',
    ];
    $doc = Html::load(Xss::filter($html, $allowed_tags));
    foreach ($doc->getElementsByTagName('*') as $element) {
      foreach (iterator_to_array($element->attributes) as $attribute) {
        if ($element->tagName !== 'a' || !in_array($attribute->name, ['href', 'title'], TRUE)) {
          $element->removeAttribute($attribute->name);
        }
      }
      if ($element->tagName === 'a' && $element->hasAttribute('href')) {
        $url = self::absoluteUrl($element->getAttribute('href'));
        if ($url === '') {
          $element->removeAttribute('href');
        }
        else {
          $element->setAttribute('href', $url);
        }
      }
    }
    return Html::serialize($doc);
  }

  /**
   * Creates the normalized plain-text search representation.
   */
  public static function plain(string $html): string {
    $html = preg_replace('/<\/?(?:p|h[1-6]|li|ul|ol|br|blockquote)(?:\s[^>]*)?\/?>/iu', ' ', $html);
    return trim(preg_replace('/\s+/u', ' ', Html::decodeEntities(strip_tags($html))));
  }

  /**
   * Preserves repeated source values in Drupal field-item structure.
   */
  private static function values(\SimpleXMLElement $elements): array {
    $values = [];
    foreach ($elements as $element) {
      $values[] = ['value' => trim((string) $element)];
    }
    return $values;
  }

  /**
   * Accepts only HTTP(S) links without credentials.
   */
  private static function absoluteUrl(string $url): string {
    if (str_starts_with($url, '//')) {
      return '';
    }
    if (str_starts_with($url, '/')) {
      $url = 'https://medlineplus.gov' . $url;
    }
    $parts = parse_url($url);
    return $parts && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], TRUE)
      && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) ? $url : '';
  }

  /**
   * Builds a typed edge without creating out-of-corpus entity references.
   */
  private static function edge(string $source, string $type, int $ordinal, string $target, string $title, string $url, array $selection): array {
    return [
      'key' => $source . ':' . $type . ':' . $ordinal,
      'title' => mb_substr($source . ' → ' . ($title ?: $url), 0, 255),
      'topic' => $source,
      'target' => in_array($target, array_map('strval', $selection), TRUE) ? $target : '',
      'target_source_id' => $target,
      'target_title' => $title,
      'target_url' => $url,
      'relation_type' => $type,
      'provenance' => 'MedlinePlus XML: ' . $type,
    ];
  }

}
