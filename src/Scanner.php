<?php

/**
 * @file
 * Contains a functionality to scan Barotrauma source files.
 *
 * Mostly this class helps to determine and create proper parsers to explore
 *   source files of barotrauma and mods.
 */

namespace Barotraumix\Framework;

use Barotraumix\Framework\Parser\ParserInterface;
use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Services\Framework;
use Barotraumix\Framework\Services\Services;

/**
 * Class Scanner.
 */
class Scanner {

  /**
   * @var string - Application name to scan.
   */
  protected string $application;

  /**
   * @var null|string $parserClass - Class to use as parser for this
   *   application.
   */
  protected null|string $parserClass;

  /**
   * @var array $parsers - Array with parsers (static cache).
   */
  protected array $parsers = [];

  /**
   * Class constructor.
   *
   * @param string $application - Application name to scan.
   */
  public function __construct(string $application = Framework::BAROTRAUMA_APP_NAME) {
    $this->application = $application;
    // Assign parser class. At current moment we have only one.
    // New Parser might appear when Barotrauma will make significant
    // changes in their files and their structure.
    $this->parserClass = '\Barotraumix\Framework\Parser\ParserClassic';
  }

  /**
   * Returns application ID.
   *
   * @return int
   */
  public function appId(): int {
    return Services::$database->applications($this->application);
  }

  /**
   * Returns build id of the application.
   *
   * @return int
   */
  public function buildId(): int {
    return Services::buildId($this->appId());
  }

  /**
   * Return array of data of the content package.
   *
   * @todo: Multiple content packages?
   *
   * @return BaroEntity|NULL
   */
  public function contentPackage(): BaroEntity|NULL {
    $contentPackages = $this->createParser()->doParse();
    return !empty($contentPackages) ? reset($contentPackages) : NULL;
  }

  /**
   * Return array of data with items.
   *
   * @todo: Refactor.
   *
   * @return array
   */
  public function items(): array {
    $contentPackage = $this->contentPackage();
    $assets = $contentPackage->childrenByTypes('Item');
//    $mappingEntities = Services::$mappingEntities->array();
    $items = [];
    foreach ($assets as $asset) {
      // Skip broken assets.
      if (!$asset->hasAttribute('file')) {
        continue;
      }
      // Parse items.
      $file = $asset->attribute('file');
      $parser = $this->createParser($file);
      $parser->doParse();
//      $data = $parser->doParse();
//      $this->services()->processTree($data);
      // @todo: Automatically add data to mapping.entity.yml.
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
    }
    // This item should never be a part of this mapping.
//    $mappingEntities->delete('Items');
    // Save to YAML.
//    $mappingEntities->save();
    // Return parsed items.
    return $items;
  }

  /**
   * Method to create parser object for specific file.
   *
   * @param string|NULL $file - Path to the file for parsing.
   * It uses path system like game XML files. You don't need to use URI or real
   *   path. Just type something like:
   * "Content/ContentPackages/Vanilla.xml" to parse game content package.
   *
   * @return ParserInterface
   */
  public function createParser(string $file = NULL): ParserInterface {
    // Default file path.
    if (!isset($file)) {
      $file = Services::gameLikePathToContentPackage($this->application);
    }
    // Check for existing parser.
    if (isset($this->parsers[$file])) {
      return $this->parsers[$file];
    }

    // Create parser and store it in cache.
    $parser = new $this->parserClass($file, $this->application);
    $this->parsers[$file] = $parser;
    return $parser;
  }

}
