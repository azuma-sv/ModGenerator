<?php

/**
 * @file
 * Abstract class which needs to be inherited by other BaroEntities.
 */

namespace Barotraumix\Generator\BaroEntity\Entity;

use Barotraumix\Generator\BaroEntity\Property\ServicesHolder;
use Barotraumix\Generator\BaroEntity\Base;
use Barotraumix\Generator\Services;
use Barotraumix\Generator\Core;

/**
 * Abstract class BaseEntity.
 */
abstract class BaseEntity extends Base {

  /**
   * Use ability to handle fields for entities.
   */
  use ServicesHolder;

  /**
   * Class constructor.
   */
  public function __construct() {
  }

  /**
   * Static factory method.
   *
   * @param Base $baroEntity
   *  Data object.
   * @param Services $services
   *  Services object.
   *
   * @return static
   */
  public static function createFrom(Base $baroEntity, Services $services):static {
    $baseEntity = new static();
    $baseEntity->setServices($services);
    $baseEntity->importData($baroEntity);
    return $baseEntity;
  }

  /**
   * Create child element in face of proper class.
   *
   * @return NULL|bool|Base.
   */
  public function createChild(Base $child):NULL|bool|Base {
      return Entity::createFrom($child, $this->services());
  }

  /**
   * Method to import data for this object from another baro entity.
   *
   * @param Base $baroEntity
   *
   * @return void
   */
  protected function importData(Base $baroEntity):void {
    // Move data to our object.
    $this->setName($baroEntity->getName());
    $this->setParent($baroEntity->getParent());
    $this->setAttributes($baroEntity->getAttributes());
    // We will use something one: child value or array of children objects.
    if ($baroEntity->hasValue()) {
      $this->setValue($baroEntity->getValue());
    }
    elseif ($baroEntity->hasChildren()) {
      foreach ($baroEntity->getChildren() as $child) {
        // Skipp unsupported assets for easier development process.
        if ($this->getName() == 'ContentPackage' && !in_array($child->getName(), Core::__TYPES)) {
          // @todo: Remove later.
          continue;
        }
        $newChild = $this->createChild($child);
        if ($newChild instanceof BaseEntity) {
            $this->addChild($newChild);
        }
      }
    }
  }

}
