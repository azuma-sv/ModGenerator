<?php

/**
 * @file
 * Class to manipulate with Barotraumix item entity.
 */

namespace Barotraumix\Generator\BaroEntity\Entity;

//use Barotraumix\Generator\BaroEntity\Base;
//use Barotraumix\Generator\Core;

/**
 * Class Asset.
 */
class Item extends BaseEntity {
//
//  /**
//   * @inheritDoc
//   */
//  public function createChild(Base $child): null|bool|Base {
//    $newChild = NULL;
//    $name = $child->getName();
//    /** @var BaseEntity $class */
//    $class = 'Barotraumix\Generator\BaroEntity\Entity\\' . $name;
//    switch ($name) {
//
//      case 'PreferredContainer':
//      case 'InventoryIcon':
//      case 'Sprite':
//      case 'Body':
//      case 'IdCard':
//        $newChild = $class::createFrom($child, $this->services());
//        break;
//
//      case 'Price':
//        $newChild = Prices::createFrom($child, $this->services());
//        $newChild->setName('Prices');
//        break;
//
//      default:
//        Core::error('This case needs attention. Item child element is not recognized: ' . $name);
//        break;
//    }
//    return $newChild;
//  }

}
