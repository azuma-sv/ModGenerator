<?php

/**
 * @file
 * Trait to handle name.
 */

namespace Barotraumix\Generator\BaroEntity\Property;

/**
 * Trait definition.
 */
trait Name {

  /**
   * @var string - Name or title of the object.
   */
  protected string $name;

  /**
   * Get object name.
   *
   * @return string|NULL
   */
  public function name(): string|NULL {
    // Prevent error.
    if (!isset($this->name)) {
      return NULL;
    }
    return $this->name;
  }

  /**
   * Check name for object.
   *
   * @return bool
   */
  public function hasName(): bool {
    return isset($this->name);
  }

  /**
   * Set name for object.
   *
   * @param string $name
   *  Object name.
   */
  public function setName(string $name): void {
    $this->name = $name;
  }

}
