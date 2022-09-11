<?php

/**
 * @file
 * Container for services and variables which might help when we parse game
 *   source files.
 */

namespace Barotraumix\Framework\Services;

use DOMDocument;

/**
 * Class definition.
 */
class Services {

  /**
   * @var Settings - Mapping for entities with incorrect tag name.
   */
  public Settings $mappingEntities;

  /**
   * @var Settings - Mapping for tags with incorrect tag name case.
   */
  public Settings $mappingTags;

  /**
   * Class constructor.
   */
  public function __construct() {
    // Process additional settings.
    $this->mappingEntities = new Settings(API::pathFramework('src/mapping.entity.yml'));
    $this->mappingTags = new Settings(API::pathFramework('src/mapping.tags.yml'));
  }

  /**
   * Method to normalize tag name.
   *
   * @param string $name - XML tag name to normalize.
   *
   * @return string
   */
  public function normalizeTagName(string $name): string {
    // Look for appropriate name in mapping.
    if ($this->mappingTags->has($name)) {
      // @todo: Refactor settings file.
      $name = $this->mappingTags->get($name);
    }
    // Return normalized value.
    return strval($name);
  }

  /**
   * Method to convert tag name to type by a mapping.
   *
   * @param string $name - XML tag name.
   *
   * @return string|NULL
   */
  public function tagNameToType(string $name): string|NULL {
    $map = $this->mappingEntities;
    return $map->has($name) ? $map->get($name) : NULL;
  }

  /**
   * Create empty DOM object.
   *
   * @return DOMDocument
   */
  public function dom(): DOMDocument {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    return $dom;
  }

}
