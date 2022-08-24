<?php

/**
 * @file
 * Contains a functionality to scan Barotrauma source files.
 *
 * Mostly this class helps to determine and create proper parsers to explore
 *   source files of barotrauma.
 */

namespace Barotraumix\Generator;

use Barotraumix\Generator\BaroEntity\Entity\Asset;
use Barotraumix\Generator\BaroEntity\Entity\BaseEntity;
use Barotraumix\Generator\BaroEntity\Entity\ContentPackage;
use Barotraumix\Generator\BaroEntity\Entity\Item;
use Barotraumix\Generator\BaroEntity\Property\ServicesHolder;
use Barotraumix\Generator\BaroEntity\SanitizedXMLData;
use Barotraumix\Generator\Parser\ParserInterface;

/**
 * Class Scanner.
 */
class Scanner {

  /**
   * Inject services object.
   */
  use ServicesHolder;

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
   * @param int $appId - ID of the app in Steam.
   * @param int $buildId - Build id of the app in Steam.
   */
  public function __construct(int $appId, int $buildId, Core $core) {
    // Create services object.
    $this->setServices(new Services($appId, $buildId, $core));
    // Assign parser class. At current moment we have only one.
    // New Parser might appear when Barotrauma will make significant
    // changes in their files and their structure.
    $this->parserClass = '\Barotraumix\Generator\Parser\ParserClassic';
  }

  /**
   * Return array of data of the content package.
   *
   * @return BaseEntity
   */
  public function contentPackage(): BaseEntity {
    // Create BaroEntity.
    return ContentPackage::createFrom($this->createParser()
      ->sanitizedXMLData(), $this->services());
  }

  /**
   * Return array of data with items.
   *
   * @todo: Implement for mods as well.
   *
   * @return array
   */
  public function items(): array {
    $contentPackage = $this->contentPackage();
    $assets = $contentPackage->getChildrenByType($this->services()->getItemTypes());
    $mappingEntities = $this->services()->mappingEntities();
    $items = [];
    /** @var Asset $asset */
    foreach ($assets as $asset) {
      $file = $asset->getAttribute('file');
      $parser = $this->createParser($file);
      $data = $parser->sanitizedXMLData();
      /** @var SanitizedXMLData $child */
      if (!$data->hasChildren()) {
        // Skip file.
        continue;
      }
      // Check if we have only single child.
      $name = $data->getName();
      $pseudoName = $name;
      if ($mappingEntities->has($pseudoName)) {
        $pseudoName = $mappingEntities->get($pseudoName);
      }
      // Prepare children array.
      if ($pseudoName == 'Item') {
        $children = [$data];
      }
      if ($pseudoName == 'Items') {
        $children = $data->getChildren();
      }
      // Throw error.
      if (!isset($children)) {
        Core::error('Unable to get children of the entity.');
        return $items;
      }
      // Check each child.
      foreach ($children as $child) {
        if ($this->services()->isItem($child)) {
          $items[] = Item::createFrom($child, $this->services());
        }
        else {
          // Can be used to add new values to YAML.
          // $mappingEntities->set($child->getName(), 'Item');
          Core::error('This case needs attention. Name of the element is: ' . $child->getName());
        }
      }
    }
    // This item should never be a part of this mapping.
    $mappingEntities->delete('Items');
    // Save to YAML.
    $mappingEntities->save();
    // Return parsed items.
    return $items;
  }

  /**
   * Get array of application assets.
   *
   * @return array
   */
  public function assets(): array {
    return $this->contentPackage()->getChildren();
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
      $appId = $services->appId();
      $buildId = $services->buildId();
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
    if ($this->services()->isGame()) {
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
    return $this->services()->isGame() ? 'Vanilla' : 'filelist';
  }

}
