<?php

/**
 * @file
 * Main parsing functionality.
 *
 * It's called ParserClassic because some day Barotrauma may make significant
 *   changes into their structure. I would like to make architecture when we
 *   will have backward compatibility with code which is active right now
 *   (Summer 2022).
 */

namespace Barotraumix\Generator\Parser;

use Barotraumix\Generator\Core;
use Barotraumix\Generator\Entity\BaroEntity;
use Barotraumix\Generator\Entity\Property\NameImmutable;
use Barotraumix\Generator\Entity\Property\Value;
use Barotraumix\Generator\Services\Services;
use Barotraumix\Generator\Services\ServicesHolder;
use SimpleXMLElement;

/**
 * Class ParserClassic.
 */
class ParserClassic implements ParserInterface {

  /**
   * Extra utilities.
   */
  use ServicesHolder;
  use Value;

  /**
   * @var SimpleXMLElement $xmlParser - XML Parser object.
   */
  protected SimpleXMLElement $xmlParser;

  /**
   * @var string $file - Game-like path to the file which we are trying to
   *   parse.
   */
  protected string $file;

  /**
   * Parsed data.
   *
   * @var array
   */
  protected array $data;

  /**
   * Class constructor.
   *
   * @param string $file
   *  Game-like path to file which we are going to parse.
   * @param Services $services
   *  Extra services for parsing game data.
   */
  public function __construct(string $file, Services $services) {
    // Init variables.
    $this->file = $file;
    $this->setServices($services);

    // Prepare parser.
    $path = $services->pathPrepare($file);
    $content = file_get_contents($path);
    if ($content === FALSE) {
      Core::error("Unable to read content of the file: $file");
    }
    $this->xmlParser = new SimpleXMLElement($content);
  }

  /**
   * @inheritDoc
   */
  public function doParse(): array|NULL {
    // Return cached.
    if (isset($this->data)) {
      return $this->data;
    }
    // Parse data.
    $this->xmlParser->rewind();
    $data = $this->parseNode($this->xmlParser);
    $this->data = [];
    if (!empty($data)) {
      // Prepare data for processing.
      if ($data instanceof BaroEntity) {
        $data = [$data];
      }
      // Process entities.
      // @todo: Test the case when <Items> is inside of <Override>.
      foreach ($data as $datum) {
        if ($datum instanceof BaroEntity) {
          $this->services()->processTree($datum);
          $this->data[$datum->id()] = $datum;
        }
        else {
          Core::error('Unexpected data retrieved. This case needs to be reported.');
        }
      }
    }
    else {
      return NULL;
    }
    return $this->data;
  }

  /**
   * Method to parse SimpleXMLElements recursively.
   *
   * @param SimpleXMLElement $XMLElement - Node to parse.
   * @param BaroEntity|NULL $parent - Parent entity.
   *
   * @return BaroEntity|array|string
   */
  protected function parseNode(SimpleXMLElement $XMLElement, BaroEntity $parent = NULL): BaroEntity|array|string {
    // Process name and attributes.
    $name = $this->normalizeTagName($XMLElement->getName());
    // Prepare attributes.
    $attributes = (array) $XMLElement->attributes();
    $attributes = !empty($attributes['@attributes']) ? $attributes['@attributes'] : [];
    // Attempt to create an entity.
    $type = $this->getEntityType($name);
    if (!isset($parent) && isset($type)) {
      $node = new BaroEntity($name, $attributes, $type);
    }
    // Create sub-element instead.
    else {
      // Some entities should be ignored.
      $node = NULL;
      if (!in_array($name, $this->entitiesToIgnore())) {
        // Validate unrecognized entity types.
        if (!isset($parent)) {
          Core::error("Unrecognized entity type for tag: '$name' in file: '$this->file'");
        }
        // Create sub-element.
        $node = new BaroEntity($name, $attributes, $parent);
      }
    }
    // Process children.
    $children = [];
    $data = (array) $XMLElement;
    unset($data['@attributes']);
    foreach (array_keys($data) as $childName) {
      foreach ($XMLElement->$childName as $childXMLElement) {
        // Parse and process child.
        $child = $this->parseNode($childXMLElement, $node);
        if (isset($node)) {
          // Validate for error.
          if (is_array($child)) {
            Core::error('Child entity has been ignored unexpectedly. This case needs to be reported.');
            continue;
          }
          if ($child instanceof BaroEntity) {
            $node->addChild($child);
          }
          if (is_scalar($child)) {
            // @todo: Figure on how to import string value instead of child XML tag.
            unset($child);
          }
        }
        // Append to array.
        if (isset($child)) {
          $children[] = $child;
        }
      }
    }
    // Avoid unnecessary arrays.
    if (!isset($node) && count($children) == 1) {
      // @todo: Test the case when <Items> is inside of <Override>.
      $children = reset($children);
    }
    return $node ?? $children;
  }

  /**
   * Method to normalize tag name.
   *
   * @param string $name - XML tag name.
   *
   * @return string
   */
  protected function normalizeTagName(string $name): string {
    // Look for appropriate name in mapping.
    if ($this->services()->mappingTags()->has($name)) {
      // @todo: Refactor settings file.
      $name = $this->services()->mappingTags()->get($name);
    }
    // Return normalized value.
    return strval($name);
  }

  /**
   * Method to get entity type from XML tag name.
   *
   * @param string $name - XML tag name.
   *
   * @return string|NULL
   */
  protected function getEntityType(string $name): string|NULL {
    $mappingEntities = $this->services()->mappingEntities()->array();
    return $mappingEntities[$name] ?? NULL;
  }

  /**
   * Return array of entity XML tag names to ignore (as a root entity).
   *
   * Some entities should be ignored.
   * Their XML tags will be generated automatically by framework.
   * Example tag names: Items, Override etc...
   *
   * @return array
   */
  protected function entitiesToIgnore(): array {
    static $entities;
    if (!isset($entities)) {
      $mappingEntities = $this->services()->mappingEntities()->array();
      $mappingEntities = array_unique(array_values($mappingEntities));
      // Prepare list of wrappers to ignore.
      foreach ($mappingEntities as $mappingEntity) {
        // Will generate names like "Items" for elements like "Item" etc...
        $entities[] = $mappingEntity . 's';
      }
      // This is a special wrapper which needs to be ignored too.
      $entities[] = 'Override';
    }
    return $entities;
  }

}
