<?php

/**
 * @file
 * Main parsing functionality.
 */

namespace Barotraumix\Framework\Services;

use Barotraumix\Framework\Entity\RootEntity;
use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Entity\Element;
use Barotraumix\Framework\Core;
use SimpleXMLElement;

/**
 * Class XMLParser.
 */
class XMLParser {

  /**
   * @var string - Application ID which is getting parsed.
   */
  protected string $id;

  /**
   * @var string - Game-like path to the file which we are trying to parse.
   */
  protected string $file;

  /**
   * @var array - Parsed data.
   */
  protected array $data = [];

  /**
   * Class constructor.
   *
   * @param string $file
   *  Game-like path to file which we are going to parse.
   * @param string $id - Application ID which is getting parsed.
   */
  public function __construct(string $file, string $id = API::APP_ID) {
    // Init variables.
    $this->id = $id;
    $this->file = $file;
    // Prepare parser.
    $path = API::getPath($file, $id);
    $content = file_get_contents($path);
    if ($content === FALSE) {
      API::error("Unable to read content of the file: $file");
    }
    $data = $this->parseNode(new SimpleXMLElement($content));
    $this->data = is_array($data) ? $data : [$data];
  }

  /**
   * Method to get parsed data.
   *
   * @return array<RootEntity>
   */
  public function content(): array {
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
    $name = Core::services()->normalizeTagName($XMLElement->getName());
    // Prepare attributes.
    $attributes = (array) $XMLElement->attributes();
    $attributes = !empty($attributes['@attributes']) ? $attributes['@attributes'] : [];
    // Attempt to create an entity.
    $type = Core::services()->tagNameToType($name);
    if (!isset($parent) && isset($type)) {
      $node = new RootEntity($name, $attributes, $this->id, $this->file);
    }
    // Create sub-element instead.
    else {
      // Some entities should be ignored.
      $node = NULL;
      if (!in_array($name, $this->entitiesToIgnore())) {
        // Validate unrecognized entity types.
        if (!isset($parent)) {
          API::error("Unrecognized entity type for tag: '$name' in file: '$this->file'");
        }
        // Create sub-element.
        $node = new Element($name, $attributes, $parent);
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
            API::error('Child entity has been ignored unexpectedly. This case needs to be reported.');
            continue;
          }
          if ($child instanceof Element) {
            $node->addChild($child);
          }
          if (is_scalar($child)) {
            // @todo: Figure on how to import string value instead of child XML tag.
            unset($child);
          }
        }
        // Append to array.
        if (isset($child)) {
          $children[$child->id()] = $child;
        }
      }
    }
    // Lock parsed entities.
    if (isset($node)) {
      if ($node instanceof RootEntity) {
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
      $mappingEntities = Core::services()->mappingEntities->array();
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
