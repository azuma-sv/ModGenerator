<?php

/**
 * @file
 * Store XML data for transition between parser and BaroEntity.
 *
 * Also, this object transforms and sanitize that which is holds.
 */

namespace Barotraumix\Generator\BaroEntity;

/**
 * Abstract class BaseEntity.
 */
final class SanitizedXMLData extends Base {

  /**
   * Object constructor.
   *
   * @param string $name
   *  Name of the node.
   * @param array $attributes
   *  Array of attributes.
   * @param string|array|NULL $childData
   *  Child data (string or array of children).
   */
  public function __construct(string $name, array $attributes = [], string|array $childData = NULL) {
    // Process object data.
    $this->setName($name);
    $this->setAttributes($attributes);
    $this->addChildErrorMessage('Children of SanitizedXMLData object should be an object of the same class');
    if (isset($childData)) {
      if (is_array($childData)) {
        $this->setChildren($childData);
      }
      else {
        $this->setValue($childData);
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function setAttribute(string $attribute, mixed $value): void {
    // Convert all values to scalar.
    parent::setAttribute(mb_strtolower($attribute), strval($value));
  }

  /**
   * @inheritDoc
   */
  protected function addChildValidate(mixed $child):bool {
    return $child instanceof SanitizedXMLData;
  }

}
