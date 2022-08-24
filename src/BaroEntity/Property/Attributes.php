<?php

/**
 * @file
 * Trait to handle attributes.
 */

namespace Barotraumix\Generator\BaroEntity\Property;

/**
 * Trait definition.
 */
trait Attributes {

  /**
   * @var array attributes.
   */
  protected array $attributes = [];

  /**
   * Get object attribute.
   *
   * @param string $attribute
   *  Attribute name.
   *
   * @return string
   */
  public function getAttribute(string $attribute):string {
    return $this->attributes[$attribute];
  }

  /**
   * Check if specific attribute exists.
   *
   * @param string $attribute
   *  Attribute name.
   *
   * @return bool
   */
  public function hasAttribute(string $attribute):bool {
    return array_key_exists($attribute, $this->attributes);
  }

  /**
   * Set value for specific attribute.
   *
   * @param string $attribute
   *  Attribute name.
   * @param string $value
   *  Attribute value.
   */
  public function setAttribute(string $attribute, string $value):void {
    $this->attributes[$attribute] = $value;
  }

  /**
   * Get object attributes.
   *
   * @return array
   */
  public function getAttributes():array {
    return $this->attributes;
  }

  /**
   * Set attributes for object.
   *
   * @param array $attributes
   *  Array of attributes.
   */
  public function setAttributes(array $attributes):void {
    // For validation purpose.
    foreach ($attributes as $attribute => $value) {
      $this->setAttribute($attribute, $value);
    }
  }

}
