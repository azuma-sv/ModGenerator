<?php

/**
 * @file
 * Trait to handle object value.
 */

namespace Barotraumix\Framework\Entity\Property;

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
    // Break lock if exists.
    if ($this->isLocked()) {
      $this->breakLock();
    }
    $this->value = $value;
  }

}
