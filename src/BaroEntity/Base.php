<?php

/**
 * @file
 * Base class for BaroEntities.
 */

namespace Barotraumix\Generator\BaroEntity;

use Barotraumix\Generator\BaroEntity\Property\ParentName;
use Barotraumix\Generator\BaroEntity\Property\Attributes;
use Barotraumix\Generator\BaroEntity\Property\Children;
use Barotraumix\Generator\BaroEntity\Property\Value;
use Barotraumix\Generator\BaroEntity\Property\Name;

/**
 * Abstract class definition.
 */
abstract class Base {

  /**
   * Inherit multiple base properties for this entity.
   */
  use Name;
  use Value;
  use Children;
  use Attributes;
  use ParentName;

  /**
   * Returns object value or children dependently on what is present.
   *
   * @return string|array|NULL
   */
  public function getChildData():string|array|NULL {
    if ($this->hasValue()) {
      return $this->getValue();
    }
    if ($this->hasChildren()) {
      return $this->getChildren();
    }
    return NULL;
  }

  /**
   * Set child data dependently on what is present.
   *
   * @param string|array $childData
   *  Data to set.
   *
   * @return void
   */
  public function setChildData(string|array $childData):void {
    if (is_scalar($childData)) {
      $this->setValue($childData);
    }
    if (is_array($childData)) {
      $this->setChildren($childData);
    }
  }

}
