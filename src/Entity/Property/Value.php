<?php

/**
 * @file
 * Trait to handle object value.
 */

namespace Barotraumix\Generator\BaroEntity\Property;

/**
 * Trait definition.
 */
trait Value {

  /**
   * @var mixed - Main object value.
   */
  protected mixed $value;

  /**
   * Get object value.
   *
   * @return mixed
   */
  public function value(): mixed {
    // Prevent error.
    if (!isset($this->value)) {
      return NULL;
    }
    return $this->value;
  }

  /**
   * Check object value.
   *
   * @return bool
   */
  public function hasValue(): bool {
    return isset($this->value);
  }

  /**
   * Set value for object.
   *
   * @param mixed $value
   *  Object value.
   */
  public function setValue(mixed $value):void {
    $this->value = $value;
  }

}
