<?php

/**
 * @file
 * Class to handle barotrauma entities.
 */

namespace Barotraumix\Generator\BaroEntity;

use Barotraumix\Generator\BaroEntity\Property\ServicesHolder;
use Barotraumix\Generator\BaroEntity\Property\Attributes;
use Barotraumix\Generator\BaroEntity\Property\Children;
use Barotraumix\Generator\BaroEntity\Property\Value;
use Barotraumix\Generator\BaroEntity\Property\Name;
use Barotraumix\Generator\Services;

/**
 * Class BaroEntity.
 */
class BaroEntity {

  /**
   * Inherit multiple base properties for this entity.
   */
  // XML tag name.
  use Name;
  // XML tag value. Used for multilingual strings.
  use Value;
  // Child XML elements of current XML element.
  use Children;
  // XML Attributes.
  use Attributes;
  // Extra services to use in this class. ps. Improvised dependency injection.
  use ServicesHolder;

  /**
   * @var BaroEntity - Parent BaroEntity object.
   */
  protected BaroEntity $parent;

  /**
   * @var string - Entity type which can be indicated by XML tag name and parent XML tag name.
   */
  protected string $type;

  /**
   * Class constructor.
   *
   * Class constructor is protected to force usage of Base::createFrom().
   *
   * @param string $name - XML tag name.
   * @param array $attributes - XML attributes as array.
   * @param Services $services - Extra services to use in this class.
   */
  public function __construct(string $name, array $attributes, Services $services) {
    $this->setName($name);
    $this->addAttributes($attributes);
    $this->setServices($services);
  }

  /**
   * Get object name.
   *
   * @return BaroEntity|NULL
   */
  public function parent(): BaroEntity|NULL {
    // Prevent error.
    if (!isset($this->parent)) {
      return NULL;
    }
    return $this->parent;
  }

  /**
   * Get object type.
   *
   * @return string|NULL
   */
  public function type(): string|NULL {
    // Prevent error.
    if (!isset($this->type)) {
      return NULL;
    }
    return $this->type;
  }

  /**
   * Method to connect this object with its parent.
   *
   * @param BaroEntity $parent
   *
   * @return void
   */
  public function setParent(BaroEntity $parent): void {
    $this->parent = $parent;
    // We can initialize object type only if it has been parented.
    $this->initializeType();
  }

  /**
   * This method will detect and set a type of current entity.
   * @return void
   */
  protected function initializeType(): void {
    // @todo: Implement.
  }

}
