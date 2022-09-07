<?php

/**
 * @file
 * Trait to handle name which can't be changed.
 */

namespace Barotraumix\Generator\BaroEntity\Property;

use Barotraumix\Generator\Core;

/**
 * Trait definition.
 */
trait NameImmutable {

  /**
   * Inherit normal name trait.
   */
  use Name;

  /**
   * Set name for object.
   *
   * @param string $name
   *  Object name.
   */
  public function setName(string $name): void {
    if (isset($this->name)) {
      Core::error($this->setNameErrorMessage());
    }
    $this->name = $name;
  }

  /**
   * Method to provide error message.
   *
   * Created to override original message, if necessary.
   *
   * @param string|NULL $newMessage
   *  New message to replace original.
   *
   * @return string
   */
  protected function setNameErrorMessage(string $newMessage = NULL): string {
    static $message = "Name of this object can't be changed.";
    if (isset($newMessage)) {
      $message = $newMessage;
    }
    return $message;
  }

}
