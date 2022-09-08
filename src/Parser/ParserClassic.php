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

namespace Barotraumix\Framework\Parser;

use Barotraumix\Framework\Services\Framework;
use Barotraumix\Framework\Services\Services;
use Barotraumix\Framework\Entity\BaroEntity;
use SimpleXMLElement;

/**
 * Class ParserClassic.
 */
class ParserClassic implements ParserInterface {

  /**
   * @var SimpleXMLElement $xmlParser - XML Parser object.
   */
  protected SimpleXMLElement $xmlParser;

  /**
   * @var string $file - Game-like path to the file which we are trying to parse.
   */
  protected string $file;

  /**
   * @var string $application - Application name which is getting parsed.
   */
  protected string $application;

  /**
   * @var array - Parsed data.
   */
  protected array $data;

  /**
   * Class constructor.
   *
   * @param string $file
   *  Game-like path to file which we are going to parse.
   * @param string $application - Application name which is getting parsed.
   *  Application name is used as context name for parsed entities in database.
   */
  public function __construct(string $file, string $application = Framework::BAROTRAUMA_APP_NAME) {
    // Init variables.
    $this->file = $file;
    $this->application = $application;
    // Prepare parser.
    $path = Framework::getPath($file, $application);
    $content = file_get_contents($path);
    if ($content === FALSE) {
      Framework::error("Unable to read content of the file: $file");
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
      // @todo: Do I need this processing here?
      // @todo: Test the case when <Items> is inside of <Override>.
      foreach ($data as $datum) {
        if ($datum instanceof BaroEntity) {
          $this->processTree($datum);
          $this->data[$datum->id()] = $datum;
        }
        else {
          Framework::error('Unexpected data retrieved. This case needs to be reported.');
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
    $app = $this->application;
    $file = $this->file;
    // Process name and attributes.
    $name = Services::normalizeTagName($XMLElement->getName());
    // Prepare attributes.
    $attributes = (array) $XMLElement->attributes();
    $attributes = !empty($attributes['@attributes']) ? $attributes['@attributes'] : [];
    // Attempt to create an entity.
    $type = $this->getEntityType($name);
    if (!isset($parent) && isset($type)) {
      $node = new BaroEntity($name, $attributes, $app, $file);
    }
    // Create sub-element instead.
    else {
      // Some entities should be ignored.
      $node = NULL;
      if (!in_array($name, $this->entitiesToIgnore())) {
        // Validate unrecognized entity types.
        if (!isset($parent)) {
          Framework::error("Unrecognized entity type for tag: '$name' in file: '$file'");
        }
        // Create sub-element.
        $node = new BaroEntity($name, $attributes, $app, $file, $parent);
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
            Framework::error('Child entity has been ignored unexpectedly. This case needs to be reported.');
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
    // Lock parsed entities.
    if (isset($node)) {
      if ($node->isRoot()) {
        $node->lock();
      }
    }
    else {
      // Avoid unnecessary arrays.
      if (count($children) == 1) {
        // @todo: Test the case when <Items> is inside of <Override>.
        $children = reset($children);
      }
    }
    return $node ?? $children;
  }

  /**
   * Method to get entity type from XML tag name.
   *
   * @param string $name - XML tag name.
   *
   * @return string|NULL
   */
  protected function getEntityType(string $name): string|NULL {
    $mappingEntities = Services::$mappingEntities->array();
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
      $mappingEntities = Services::$mappingEntities->array();
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

  /**
   * Method to process tree of parsed objects.
   *
   * @param BaroEntity $entity - Parsed entity.
   *
   * @return void
   */
  protected function processTree(BaroEntity $entity):void {
    // Process children recursively.
    if ($entity->hasChildren()) {
      foreach ($entity->children() as $child) {
        $this->processTree($child);
      }
    }
    // Add entity to bank.
    Services::$database->entityAdd($entity, $this->application);
  }

}
