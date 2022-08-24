<?php

/**
 * @file
 * Trait to handle parent name.
 */

namespace Barotraumix\Generator\BaroEntity\Property;

use Barotraumix\Generator\Core;

/**
 * Trait definition.
 */
trait ParentName {

  /**
   * @var string - Name or title of the object.
   */
  protected string $parent = Core::PARENT_ROOT;

  /**
   * Get object name.
   *
   * @return string
   */
  public function getParent():string {
    return $this->parent;
  }

  /**
   * Set name for object.
   *
   * @param string $parent
   *  Object name.
   */
  public function setParent(string $parent):void {
    $this->parent = $parent;
  }

}
