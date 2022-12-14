<?php

/**
 * @file
 * Main parsing functionality.
 */

namespace Barotraumix\Framework\Services;

use Barotraumix\Framework\Entity\Property\ID;
use Barotraumix\Framework\Entity\RootEntity;
use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Entity\Element;
use SimpleXMLElement;

/**
 * Class XMLParser.
 */
class XMLParser {

  /**
   * Use ID.
   */
  use ID;

  /**
   * @var string - Game-like path to the file which we are trying to parse.
   */
  protected string $file;

  /**
   * @var string - Type of the entities to parse.
   */
  protected string $type;

  /**
   * @var array - Parsed data.
   */
  protected array $data = [];

  /**
   * @var bool - Override status.
   */
  protected bool $override = FALSE;

  /**
   * Class constructor.
   *
   * @param string $file
   *  Game-like path to file which we are going to parse.
   * @param string $id - Application ID which is getting parsed.
   */
  public function __construct(string $file, string $type, string $id = API::APP_ID) {
    // Init variables.
    $this->file = mb_substr($file, 0 , mb_strlen($file) - 4);
    $this->type = $type;
    $this->setID($id);
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
    $name = API::normalizeTagName($XMLElement->getName());
    // Prepare attributes.
    $attributes = (array) $XMLElement->attributes();
    $attributes = !empty($attributes['@attributes']) ? $attributes['@attributes'] : [];
    // Content of this file overrides original entities.
    if (!isset($parent) && $name == 'Override') {
      $this->override = TRUE;
    }
    // Attempt to create an entity.
    $ignoredWrappers = API::getIgnoredWrappers($this->type);
    if (!isset($parent) && !in_array($name, $ignoredWrappers)) {
      // Will will put all entities in sub-folder of current application by default.
      $node = new RootEntity($name, $attributes, $this->type, $this->id(), $this->id() . '/' . $this->file);
    }
    // Create sub-element instead.
    else {
      $node = NULL;
      if (isset($parent)) {
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
        $node->override($this->override);
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

}
