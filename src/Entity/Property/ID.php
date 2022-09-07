<?php

/**
 * @file
 * Trait to handle ID.
 */

namespace Barotraumix\Generator\Entity\Property;

use Barotraumix\Generator\Core;

/**
 * Trait definition.
 */
trait ID {

  /**
   * @var string - ID of the object.
   */
  protected string $id;

  /**
   * Get object ID.
   *
   * @return string|NULL
   */
  public function id(): string|NULL {
    return $this->id;
  }

  /**
   * Check ID of the object.
   *
   * @return bool
   */
  public function hasID(): bool {
    return isset($this->id);
  }

  /**
   * Set ID for object.
   *
   * @param string $id - Object ID.
   */
  public function setID(string $id): void {
    // Validate ability to set ID.
    if ($this->hasID()) {
      Core::error('Once ID was set - it can not be changed.');
    }
    $this->id = $id;
  }

}
