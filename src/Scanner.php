<?php

/**
 * @file
 * Contains a functionality to scan Barotrauma source files.
 *
 * Mostly this class helps to determine and create proper parsers to explore
 *   source files of barotrauma and mods.
 */

namespace Barotraumix\Generator;

use Barotraumix\Generator\Entity\BaroEntity;
use Barotraumix\Generator\Parser\ParserInterface;
use Barotraumix\Generator\Services\Services;
use Barotraumix\Generator\Services\ServicesHolder;

/**
 * Class Scanner.
 */
class Scanner {

  /**
   * Inject services object.
   */
  use ServicesHolder;

  /**
   * @var int - Application ID.
   */
  protected int $appId;

  /**
   * @var int - Build ID of the application.
   */
  protected int $buildId;

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
   * @param Services $services - Services object.
   */
  public function __construct(Services $services) {
    // Create services object.
    $this->setServices($services);
    // Assign parser class. At current moment we have only one.
    // New Parser might appear when Barotrauma will make significant
    // changes in their files and their structure.
    $this->parserClass = '\Barotraumix\Generator\Parser\ParserClassic';
  }

  /**
   * Returns application ID.
   *
   * @return int
   */
  public function appId(): int {
    return $this->appId;
  }

  /**
   * Returns build id of the application.
   *
   * @return int
   */
  public function buildId(): int {
    return $this->buildId;
  }

  /**
   * Check if current application is Barotrauma game.
   *
   * @return bool
   */
  public function isGame(): bool {
    return Core::BAROTRAUMA_APP_ID == $this->appId();
  }

  /**
   * Check if current application is Barotrauma mod.
   *
   * @return bool
   */
  public function isMod(): bool {
    return !$this->isGame();
  }

  /**
   * Return array of data of the content package.
   *
   * @todo: Test scenario with multiple content packages.
   *
   * @param string|NULL $name - Content package name. Leave blank to get all.
   *
   * @return BaroEntity|array|NULL
   */
  public function contentPackage(string $name = NULL): BaroEntity|array|NULL {
    $contentPackages = $this->createParser()->doParse();
    if (isset($name)) {
      $contentPackages = $contentPackages[$name] ?? NULL;
    }
    return $contentPackages;
  }

  /**
   * Return array of data with items.
   *
   * @todo: Refactor when method isItem is refactored.
   *
   * @return array
   */
  public function items(): array {
    $contentPackage = $this->contentPackage('Vanilla');
    $assets = $contentPackage->childrenByTypes('Item');
    $mappingEntities = $this->services()->mappingEntities();
    $items = [];
    foreach ($assets as $asset) {
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
    $mappingEntities->delete('Items');
    // Save to YAML.
    $mappingEntities->save();
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
   *  At current moment we have only one parser.
   */
  public function createParser(string $file = NULL): ParserInterface {
    // Check for existing parser.
    if (isset($this->parsers[$file])) {
      return $this->parsers[$file];
    }

    // Default file path.
    if (!isset($file)) {
      $file = $this->gameLikePathToContentPackage();
    }
    // Create container for additional data and Drupal services.
    $services = $this->services();
    $path = $services->pathPrepare($file);

    // Ensure that file path is reachable.
    if (!file_exists($path)) {
      $appId = $this->appId();
      $buildId = $this->buildId();
      $msg = "Unable to locate file '$file' of the app: $appId (build id: $buildId)";
      Core::error($msg);
    }

    // Create parser and store it in cache.
    /** @var ParserInterface $parser - At current moment we have only classic parser. */
    $parser = new $this->parserClass($file, $services);
    $this->parsers[$file] = $parser;
    return $parser;
  }

  /**
   * Returns game-like path to content package file.
   *
   * @return null|string
   */
  public function gameLikePathToContentPackage(): null|string {
    $package = $this->primaryContentPackage();
    if ($this->isGame()) {
      $filePath = "Content/ContentPackages/$package.xml";
    }
    else {
      // Path to content package of the mod.
      $filePath = "$package.xml";
    }
    return $filePath;
  }

  /**
   * Returns name of primary content package of this app.
   *
   * @return string
   */
  protected function primaryContentPackage(): string {
    return $this->isGame() ? 'Vanilla' : 'filelist';
  }

}
