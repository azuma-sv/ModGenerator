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

use Barotraumix\Generator\BaroEntity\Property\ServicesHolder;
use Barotraumix\Generator\BaroEntity\Property\NameImmutable;
use Barotraumix\Generator\BaroEntity\Property\Value;
use Barotraumix\Generator\BaroEntity\SanitizedXMLData;
use Barotraumix\Generator\Core;
use Barotraumix\Generator\Services;
use SimpleXMLElement;

/**
 * Class ParserClassic.
 */
class ParserClassic implements ParserInterface {

  /**
   * Extra utilities.
   */
  use ServicesHolder;
  use NameImmutable;
  use Value;

  /**
   * @var SimpleXMLElement $xmlParser - XML Parser object.
   */
  protected SimpleXMLElement $xmlParser;

  /**
   * @var SanitizedXMLData $data - Storage for parsed data.
   */
  protected SanitizedXMLData $data;

  /**
   * @var string $file - Game-like path to the file which we are trying to
   *   parse.
   */
  protected string $file;

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
    $this->setServices($services);
    $this->file = $file;
    $path = $services->pathPrepare($file);

    // Prepare parser.
    $content = file_get_contents($path);
    if ($content === FALSE) {
      Core::error("Unable to read content of the file: $file");
    }
    $this->xmlParser = new SimpleXMLElement($content);
    $this->processNameAutomatically();
  }

  /**
   * @inheritDoc
   */
  public function sanitizedXMLData(): SanitizedXMLData {
    // Return cached.
    if ($this->hasValue()) {
      return $this->getValue();
    }
    // Prepare data.
    $this->parseFile();
    return $this->getValue();
  }

  /**
   * Method to parse content package data.
   *
   * @return SanitizedXMLData
   */
  protected function parseFile(): SanitizedXMLData {
    // Check cache.
    if ($this->hasValue()) {
      return $this->getValue();
    }
    // Parse data.
    $this->xmlParser->rewind();
    $this->setValue($this->parseNode($this->xmlParser));
    return $this->getValue();
  }

  /**
   * Method to parse SimpleXMLElements recursively.
   *
   * @param SimpleXMLElement $simpleXMLElement
   * @param string $parent
   *
   * @return SanitizedXMLData
   */
  protected function parseNode(SimpleXMLElement $simpleXMLElement, string $parent = Core::PARENT_ROOT): SanitizedXMLData {
    // Process name and attributes.
    $name = $this->processName($simpleXMLElement);
    $attributes = $this->processAttributes($simpleXMLElement);
    $children = [];
    // Process children.
    $data = (array) $simpleXMLElement;
    unset($data['@attributes']);
    if (!empty($data)) {
      foreach (array_keys($data) as $child) {
        foreach ($simpleXMLElement->$child as $childSimpleXMLElement) {
          $children[] = $this->parseNode($childSimpleXMLElement, $name);
        }
      }
    }
    // Return parsed node.
    $node = new SanitizedXMLData($name, $attributes, $children);
    $node->setParent($parent);
    $this->services()->process($node);
//    $this->statistic($node, $parent);
    return $node;
  }

  /**
   * Get name of the tag.
   *
   * @param SimpleXMLElement $simpleXMLElement
   *
   * @return string
   */
  protected function processName(SimpleXMLElement $simpleXMLElement): string {
    return $this->services()->normalizeTag($simpleXMLElement->getName());
  }

  /**
   * Get attributes of the tag.
   *
   * @param SimpleXMLElement $simpleXMLElement
   *
   * @return array
   */
  protected function processAttributes(SimpleXMLElement $simpleXMLElement): array {
    $attributes = (array) $simpleXMLElement->attributes();
    return !empty($attributes['@attributes']) ? $attributes['@attributes'] : [];
  }

  /**
   * This method will assign parser name automatically, if it's possible.
   *
   * @return void
   * @todo: Figure out on how to use parser name value.
   *
   */
  protected function processNameAutomatically(): void {
    // Check if we already have a name.
    if ($this->hasName()) {
      return;
    }
    // Get name from file.
    $name = $this->xmlParser->getName();
    // Check if this is a content package.
    if ($name == 'ContentPackage') {
      $this->setName($name);
    }
  }

  /**
   * Method to collect and store statistic about parsed data.
   *
   * @todo: Refactor or remove.
   *
   * @param SanitizedXMLData $sanitizedXMLData - Parsed data.
   * @param string $parent - Parent element name.
   *
   * @return void
   */
  protected function statistic(SanitizedXMLData $sanitizedXMLData, string $parent): void {
    $file = $this->file;
    $name = $sanitizedXMLData->getName();
    $children = $sanitizedXMLData->hasChildren() ? $sanitizedXMLData->getChildren() : [];
    // Prepare data storage.
    if (!isset($GLOBALS['barotrauma'])) {
      $GLOBALS['barotrauma'] = [];
    }
    // Define file statistic storage.
    if (!isset($GLOBALS['barotrauma']['tags'])) {
      $GLOBALS['barotrauma']['tags'] = [];
    }
    if (!isset($GLOBALS['barotrauma']['attributes'])) {
      $GLOBALS['barotrauma']['attributes'] = [];
    }

    // Prepare storage.
    $storageTags = &$GLOBALS['barotrauma']['tags'];
    $storageAttributes = &$GLOBALS['barotrauma']['attributes'];
    if (!isset($storageTags[$name])) {
      $storageTags[$name] = [];
      $storageTags[$name]['count'] = 0;
      $storageTags[$name]['parents'] = [];
      $storageTags[$name]['children'] = [];
      $storageTags[$name]['attributes'] = [];
      $storageTags[$name]['files'] = [];
    }

    // Process children.
    /** @var SanitizedXMLData $child */
    foreach ($children as $child) {
      // Calculate children.
      $childName = $child->getName();
      if (!isset($storageTags[$name]['children'][$childName])) {
        $storageTags[$name]['children'][$childName] = 0;
      }
      $storageTags[$name]['children'][$childName]++;
    }

    // Calculate instances.
    $storageTags[$name]['count']++;
    if (!isset($storageTags[$name]['files'][$file])) {
      $storageTags[$name]['files'][$file] = 0;
    }
    $storageTags[$name]['files'][$file]++;
    if (!isset($storageTags[$name]['parents'][$parent])) {
      $storageTags[$name]['parents'][$parent] = 0;
    }
    $storageTags[$name]['parents'][$parent]++;

    // Statistic for attributes.
    foreach ($sanitizedXMLData->getAttributes() as $attribute => $value) {
      // Add information for tag.
      if (!isset($storageTags[$name]['attributes'][$attribute])) {
        $storageTags[$name]['attributes'][$attribute] = [];
        $storageTags[$name]['attributes'][$attribute]['count'] = 0;
        $storageTags[$name]['attributes'][$attribute]['values'] = [];
      }
      $storageTags[$name]['attributes'][$attribute]['count']++;
      // Record value and count instances.
      if (!isset($storageTags[$name]['attributes'][$attribute]['values'][$value])) {
        $storageTags[$name]['attributes'][$attribute]['values'][$value] = 0;
      }
      $storageTags[$name]['attributes'][$attribute]['values'][$value]++;

      // Prepare attribute storage.
      if (!isset($storageAttributes[$attribute])) {
        $storageAttributes[$attribute] = [];
        $storageAttributes[$attribute]['count'] = 0;
        $storageAttributes[$attribute]['parents'] = [];
        $storageAttributes[$attribute]['values'] = [];
      }
      $storageAttributes[$attribute]['count']++;
      // Record value and count instances.
      if (!isset($storageAttributes[$attribute]['values'][$value])) {
        $storageAttributes[$attribute]['values'][$value] = 0;
      }
      $storageAttributes[$attribute]['values'][$value]++;
      if (!isset($storageAttributes[$attribute]['parents'][$name])) {
        $storageAttributes[$attribute]['parents'][$name] = 0;
      }
      $storageAttributes[$attribute]['parents'][$name]++;
    }
  }

}
