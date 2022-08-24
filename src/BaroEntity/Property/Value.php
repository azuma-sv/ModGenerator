<?php

/**
 * @file
 * Trait to handle value.
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
   * Check object value.
   *
   * @return bool
   */
  public function hasValue():bool {
    return isset($this->value);
  }

  /**
   * Get object value.
   *
   * @return mixed
   */
  public function getValue():mixed {
    return $this->value;
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
