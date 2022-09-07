<?php

/**
 * @file
 * Trait to handle attributes.
 */

namespace Barotraumix\Generator\Entity\Property;

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
   * @param string $attribute - Attribute name.
   *
   * @return string
   */
  public function attribute(string $attribute): string {
    return $this->attributes[$attribute];
  }

  /**
   * Check if specific attribute exists.
   *
   * @param string $attribute - Attribute name.
   *
   * @return bool
   */
  public function hasAttribute(string $attribute): bool {
    return array_key_exists($attribute, $this->attributes);
  }

  /**
   * Set value for specific attribute.
   *
   * @param string $attribute - Attribute name.
   * @param string $value - Attribute value.
   */
  public function setAttribute(string $attribute, string $value): void {
    $this->attributes[mb_strtolower($attribute)] = $value;
  }

  /**
   * Get object attributes.
   *
   * @return array
   */
  public function attributes(): array {
    return $this->attributes;
  }

  /**
   * Adds additional attributes to object.
   *
   * Overwrites if attribute exist.
   *
   * @param array $attributes - Array of attributes.
   */
  public function addAttributes(array $attributes): void {
    // For validation purpose.
    foreach ($attributes as $attribute => $value) {
      $this->setAttribute($attribute, $value);
    }
  }

  /**
   * Removes attributes from object.
   *
   * @param array|NULL $attributes - Array of attribute keys.
   */
  public function unsetAttributes(array $attributes = NULL): void {
    if (!isset($attributes)) {
      // Wipe all attributes.
      $this->attributes = [];
    }
    else {
      // Remove specific attributes.
      foreach ($attributes as $attribute) {
        unset($this->attributes[$attribute]);
      }
    }
  }

}
