<?php

/**
 * @file
 * Contains a functionality to scan Barotrauma source files.
 *
 * Mostly this class helps to determine and create proper parsers to explore
 *   source files of barotrauma and mods.
 */

namespace Barotraumix\Framework\Services;

use Barotraumix\Framework\Compiler\ContextRoot;
use Barotraumix\Framework\Entity\Property\ID;
use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Entity\Element;
use Barotraumix\Framework\Core;

/**
 * Class Scanner.
 */
class Scanner {

  /**
   * Application id as ID
   */
  use ID;

  /**
   * @var array $parsers - Array with parsers (static cache).
   */
  protected array $parsers = [];

  /**
   * Class constructor.
   *
   * @param string|int $id - Application ID to scan.
   */
  public function __construct(string|int $id = API::APP_ID) {
    $this->setID($id);
  }

  /**
   * Check if this scanner is able to scan something.
   *
   * Invalid without content package.
   *
   * @return bool
   */
  public function isValid(): bool {
    return file_exists(API::getPath(API::pathContentPackage($this->id), $this->id));
  }

  /**
   * Return array of content packages.
   *
   * @return array
   */
  public function contentPackages(): array {
    return $this->createParser()->content();
  }

  /**
   * Builds and returns context object for this scanner.
   *
   * @param bool $translations - Indicates that we should scan translations.
   *
   * @return ContextRoot
   */
  public function scanContext(bool $translations = FALSE): ContextRoot {
    $context = Core::context($this->id());
    if (!$context->isEmpty()) {
      return $context;
    }
    // Ignore some packages from parsing, because they have non-XML data.
    $ignore = ['EnemySubmarine', 'Outpost', 'BeaconStation', 'Wreck', 'Submarine', 'OutpostModule'];
    if (!$translations) {
      $ignore[] = 'Text';
      $ignore[] = 'NPCConversations';
    }
    // Import content packages and their assets.
    $contentPackages = $this->contentPackages();
    /** @var \Barotraumix\Framework\Entity\RootEntity $contentPackage */
    foreach ($contentPackages as $contentPackage) {
      $context[] = $contentPackage;
      // Do nothing without assets.
      if (!$contentPackage->hasChildren()) {
        continue;
      }
      // Import assets.
      /** @var Element $asset */
      foreach ($contentPackage->children() as $asset) {
        // Skip assets with non-XML content.
        if (in_array($asset->name(), $ignore) || !($parser = $this->createParser($asset))) {
          continue;
        }
        /** @var \Barotraumix\Framework\Entity\RootEntity $entity */
        foreach ($parser->content() as $entity) {
          if (!in_array($asset->name(), ['Text', 'NPCConversations'])) {
            $context[] = $entity;
            // May help to provide additional attributes which contain files.
            // Should be used only during development of Modding Framework.
            // $this->scanAttributesWithFiles($entity, $attributesWithFiles);
          }
          else {
            // Handle Text and NPCConversations in another way.
            if ($translations) {
              Core::translationAdd($entity);
            }
          }
        }
      }
    }
    return $context;
  }

  /**
   * Method to create parser object for specific file.
   *
   * @param Element|NULL $entity - BaroEntity of the asset.
   *
   * @return XMLParser|NULL
   */
  public function createParser(Element $entity = NULL): XMLParser|NULL {
    // Default file path.
    if (!isset($entity)) {
      $file = API::pathContentPackage($this->id);
    }
    else {
      $file = $entity->attribute('file');
    }
    // Check for existing parser.
    if (isset($this->parsers[$file])) {
      return $this->parsers[$file];
    }
    // Remove %ModDir%/ in mod files.
    $string = '%ModDir%/';
    $file = str_ireplace($string, '', $file);
    // Create parser and store it in cache.
    $type = isset($entity) ? $entity->name() : 'ContentPackage';
    $wrapper = API::getMainWrapper($type);
    if (!isset($wrapper)) {
      // Do not use parser for entities without wrapper (Non XML entities).
      return NULL;
    }
    $parser = new XMLParser($file, $type, $this->id);
    $this->parsers[$file] = $parser;
    return $parser;
  }

  /**
   * Method to scan for attributes which contain files (recursively).
   *
   * This function is created for debugging purpose, it shouldn't be used in a
   * framework.
   *
   * @param BaroEntity $entity - Entity ty scan.
   * @param array|NULL $attributes - Attributes storage.
   */
  protected function scanAttributesWithFiles(BaroEntity $entity, array &$attributes = NULL): void {
    $attributes = $attributes ?? [];
    // Check attributes for available files.
    foreach ($entity->attributes() as $attribute => $value) {
      $path = API::getPath(str_ireplace('%ModDir%/', '', $value), $this->id());
      if (!is_dir($path) && file_exists($path) && !in_array($attribute, $attributes)) {
        $attributes[] = $attribute;
      }
    }
    // Check for files in child entities.
    if ($entity->hasChildren()) {
      foreach ($entity->children() as $child) {
        $this->scanAttributesWithFiles($child, $attributes);
      }
    }
  }

}
