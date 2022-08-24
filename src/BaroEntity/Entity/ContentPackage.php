<?php

/**
 * @file
 * Class to manipulate with Barotraumix entity ContentPackage.
 */

namespace Barotraumix\Generator\BaroEntity\Entity;

use Barotraumix\Generator\BaroEntity\Base;

/**
 * Class ContentPackage.
 */
class ContentPackage extends BaseEntity {

  /**
   * Method to create child entities of proper type.
   *
   * @param Base $child - XMLData to use for child entity.
   *
   * @return Asset
   */
  public function createChild(Base $child):Asset {
    return Asset::createFrom($child, $this->services());
  }

}
