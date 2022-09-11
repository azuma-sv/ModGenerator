<?php

/**
 * @file
 * Trait to handle ID.
 */

namespace Barotraumix\Framework\Entity\Property;

use Barotraumix\Framework\Services\API;

/**
 * Trait definition.
 */
trait ID {

  /**
   * @var string|NULL - ID of the object.
   */
  protected string|NULL $id;

  /**
   * Get object ID.
   *
   * @return string|null
   */
  public function id(): string|null {
    return $this->hasID() ? $this->id : NULL;
  }

  /**
   * Check ID of the object.
   *
   * @return bool
   */
  public function hasID(): bool {
    return !empty($this->id);
  }

  /**
   * Set ID for object.
   *
   * @param string $id - Object ID.
   */
  public function setID(string $id): void {
    // Validate ability to set ID.
    if ($this->hasID()) {
      $oldID = $this->id();
      API::notice("ID of the entity has been changed from '$oldID' to '$id'");
    }
    $this->id = $id;
  }

}
