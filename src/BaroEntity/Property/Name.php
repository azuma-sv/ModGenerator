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
   * @return string
   */
  public function getName():string {
    return $this->name;
  }

  /**
   * Set name for object.
   *
   * @param string $name
   *  Object name.
   */
  public function setName(string $name):void {
    $this->name = $name;
  }

}
