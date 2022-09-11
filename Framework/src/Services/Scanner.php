<?php

/**
 * @file
 * Contains a functionality to scan Barotrauma source files.
 *
 * Mostly this class helps to determine and create proper parsers to explore
 *   source files of barotrauma and mods.
 */

namespace Barotraumix\Framework\Services;

use Barotraumix\Framework\Entity\Property\ID;
use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Compiler\Context;
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
   * @return Context
   */
  public function scanContext(): Context {
    $context = Core::context($this->id());
    // Import content packages and their assets.
    $contentPackages = $this->contentPackages();
    foreach ($contentPackages as $contentPackage) {
      $context[] = $contentPackage;
      // Import Item assets.
      $assets = $contentPackage->childrenByNames('Item');
      // @todo: Import other types of assets.
      foreach ($assets as $asset) {
        if (!$asset->hasAttribute('file') || !$file = $asset->attribute('file')) {
          continue;
        }
        /** @var \Barotraumix\Framework\Entity\RootEntity $entity */
        foreach ($this->createParser($file)->content() as $entity) {
          $context[] = $entity;
          // $this->scanAttributesWithFiles($entity, $attributesWithFiles);
        }
      }
    }
    return $context;
  }

  /**
   * Helps to build tags mapping.
   *
   * @todo: Refactor.
   *
   * @return void
   */
  public function tagsMapping(): void {
//    $mappingEntities = Services::$mappingEntities->array();
//    $items = [];
//    foreach ($assets as $asset) {
      // Skip broken assets.
//      if (!$asset->hasAttribute('file')) {
//        continue;
//      }
      // Parse items.
//      $file = $asset->attribute('file');
//      $parser = $this->createParser($file);
//      $parser->doParse();
//      $data = $parser->doParse();
//      $this->services()->processTree($data);
//      if (!$data->hasChildren()) {
//        // Skip file.
//        continue;
//      }
//      // Check if we have only single child.
//      if ($data->type() == 'Item') {
//        $children = [$data];
//      }
//      else {
//        $children = $data->children();
//      }
//      // Throw error.
//      if (!isset($children)) {
//        Core::error('Unable to get children of the entity.');
//        return $items;
//      }
//      // Check each child.
//      foreach ($children as $child) {
//        if ($child->type() == 'Item') {
//
//          $items[] = BaroEntity::createFrom($child, $this->services());
//        }
//        else {
//          // Can be used to add new values to YAML.
//          // $mappingEntities->set($child->getName(), 'Item');
//          Core::error('This case needs attention. Name of the element is: ' . $child->getName());
//        }
//      }
//    }
    // This item should never be a part of this mapping.
//    $mappingEntities->delete('Items');
    // Save to YAML.
//    $mappingEntities->save();
    // Return parsed items.
//    return $items;
  }

  /**
   * Method to create parser object for specific file.
   *
   * @param string|NULL $file - Path to the file for parsing.
   * It uses path system like game XML files. You don't need to use URI or real
   *   path. Just type something like:
   * "Content/ContentPackages/Vanilla.xml" to parse game content package.
   *
   * @return XMLParser
   */
  public function createParser(string $file = NULL): XMLParser {
    // Default file path.
    if (!isset($file)) {
      $file = API::pathContentPackage($this->id);
    }
    // Check for existing parser.
    if (isset($this->parsers[$file])) {
      return $this->parsers[$file];
    }
    // Remove %ModDir%/ in mod files.
    $string = '%ModDir%/';
    $file = str_ireplace($string, '', $file);
    // Create parser and store it in cache.
    $parser = new XMLParser($file, $this->id);
    $this->parsers[$file] = $parser;
    return $parser;
  }

  /**
   * Method to scan for attributes which contain files (recursively).
   *
   * This function is created for debugging purpose, it shouldn't be used in a framework.
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
