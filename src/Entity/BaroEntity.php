<?php

/**
 * @file
 * Class to handle barotrauma entities.
 */

namespace Barotraumix\Generator\Entity;

use Barotraumix\Generator\Core;
use Barotraumix\Generator\Entity\Property\Attributes;
use Barotraumix\Generator\Entity\Property\Children;
use Barotraumix\Generator\Entity\Property\Value;
use Barotraumix\Generator\Entity\Property\Name;

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
   * @param BaroEntity|string $parentOrType - Indicates if it's an entity or sub-element.
   *  String - Means that this is a root entity which always has a type.
   *  BaroEntity - Means that this is a sub-element which always has root entity.
   */
  public function __construct(string $name, array $attributes, BaroEntity|string $parentOrType) {
    $this->setName($name);
    $this->addAttributes($attributes);
    if (is_scalar($parentOrType)) {
      $this->type = strval($parentOrType);
    }
    elseif ($parentOrType instanceof BaroEntity) {
      $this->parent = $parentOrType;
    }
    else {
      Core::error('Attempt to create an entity has been failed.');
    }
  }

  /**
   * Get parent entity.
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
   * Get root entity.
   *
   * Return root entity of current entity. May return itself.
   *
   * @return BaroEntity
   */
  public function root(): BaroEntity {
    $current = $this;
    while (TRUE) {
      $parent = $current->parent();
      if (!isset($parent)) {
        // Validate entity.
        if (!$current->isEntity()) {
          Core::error('Root entity is not a normal entity. This case needs to be reported.');
        }
        return $current;
      }
      $current = $parent;
    }
  }

  /**
   * Check if current entity can be used as normal entity (not a sub-element).
   *
   * @return bool
   */
  public function isEntity(): bool {
    return isset($this->type);
  }

  /**
   * Get object type.
   *
   * Will return tag name in the case if it's a sub-element.
   *
   * @return string
   */
  public function type(): string {
    // Prevent error.
    if (!isset($this->type)) {
      return $this->name();
    }
    return $this->type;
  }

  /**
   * Method to get entity ID.
   *
   * If this entity has no ID - it will look for parent entity and return it id.
   * @todo: I need to use ID trait instead of this method.
   *
   * @return string|NULL
   */
  public function id(): string|NULL {
    $root = $this->root();
    // Validate for error.
    if (!$root->isEntity()) {
      Core::error('Framework error: Current entity has no ID');
    }
    // @todo: I need to store entity ID inside of the BaroEntity (not in XML).
    return $this->attribute($this->idAttribute());
  }

  /**
   * Prepares a string for a debugging message.
   *
   * String will contain root entity ID and type, also current entity type.
   *
   * @return string
   */
  public function debug(): string {
    $id = $this->id();
    $type = $this->type();
    if (!$this->isEntity()) {
      $root = $this->root();
      $id = $root->id();
      $rootType = $root->type();
      return "Sub-element of type: '$type' owned by a root entity with ID: '$id' of type: '$rootType'";
    }
    return "Root entity with ID: '$id' of type: '$type'";
  }

  /**
   * Just a validation to prevent usage of this method.
   *
   * @return void
   */
  public function __clone(): void {
    // @todo: Find out a way to use this method.
    Core::error('BaroEntity need to be cloned with method BaroEntity::clone($parent)');
  }

  /**
   * Magic method to clone BaroEntity properly.
   *
   * @param BaroEntity|NULL $parent - Parent entity (optional for root entities).
   *
   * @return BaroEntity
   */
  public function clone(BaroEntity $parent = NULL): BaroEntity {
    // Clone current entity with proper parent.
    $cloned = new BaroEntity($this->name(), $this->attributes(), $parent);
    // In case if we have children - clone them too.
    if ($this->hasChildren()) {
      foreach ($this->children() as $child) {
        $cloned->addChild($child->clone($cloned));
      }
    }
    return $cloned;
  }

  /**
   * Method to get attribute name which identifies this entity.
   *
   * @todo: Remove. ID should be stored inside of a BaroEntity.
   *
   * @return string|NULL
   */
  protected function idAttribute(): string|NULL {
    // Mapping between entity and its ID attribute.
    // @todo: Move to settings?
    static $mappingIdAttribute = [
      'ContentPackage' => 'name',
      'Item' => 'identifier',
    ];
    // Provide ID if exists.
    if ($this->isEntity()) {
      if (isset($mappingIdAttribute[$this->type()])) {
        return $mappingIdAttribute[$this->type()];
      }
      else {
        Core::error('Unable to find entity ID attribute');
      }
    }
    return NULL;
  }

}
