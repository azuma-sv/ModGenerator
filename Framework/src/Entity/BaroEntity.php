<?php

/**
 * @file
 * Class to handle barotrauma entities.
 */

namespace Barotraumix\Framework\Entity;

use Barotraumix\Framework\Entity\Property\Attributes;
use Barotraumix\Framework\Entity\Property\Children;
use Barotraumix\Framework\Entity\Property\Value;
use Barotraumix\Framework\Entity\Property\Name;
use Barotraumix\Framework\Entity\Property\ID;
use Barotraumix\Framework\Services\API;

/**
 * Class BaroEntity.
 */
abstract class BaroEntity {

  /**
   * Inherit multiple base properties for this entity.
   */
  // Entity ID.
  use ID;
  // XML tag name.
  use Name;
  // XML tag value. Used for multilingual strings.
  use Value;
  // Child XML elements of current XML element.
  use Children;
  // XML Attributes.
  use Attributes;

  /**
   * @var bool - Indicates if this entity is locked from internal modifications.
   */
  protected bool|NULL $locked = FALSE;

  /**
   * @var bool - Variable which indicates if this element was removed.
   */
  public bool $removed = FALSE;

  /**
   * Class constructor.
   *
   * @param string $name - XML tag name.
   * @param array $attributes - XML attributes as array.
   */
  public function __construct(string $name, array $attributes) {
    $this->setID(spl_object_id($this));
    // Save main data.
    $this->setName($name);
    $this->addAttributes($attributes);
  }

  /**
   * Method to mark entity as removed.
   *
   * @return void
   */
  public function remove(): void {
    $this->removed = TRUE;
    // Mark child entities as removed.
    if ($this->hasChildren()) {
      foreach ($this->children() as $child) {
        $child->remove();
      }
    }
  }

  /**
   * Method to indicate that this item has been removed.
   *
   * @return bool
   */
  public function isRemoved(): bool {
    return $this->removed;
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
    $cloned = $this->create($parent);
    $cloned->id = NULL;
    $cloned->setID($this->id());
    // In case if we have children - clone them too.
    if ($this->hasChildren()) {
      foreach ($this->children() as $child) {
        $cloned->addChild($child->clone($cloned));
      }
    }
    // Lock cloned entity.
    $cloned->lock();
    // @todo: Clone entity value?
    return $cloned;
  }

  /**
   * Static factory method to create new instances of current class.
   *
   * @param BaroEntity|NULL $parent - Parent entity (optional for root entities).
   */
  public function create(BaroEntity $parent = NULL): static {
    unset($parent);
    return new static($this->name(), $this->attributes(), $this->appID(), $this->file());
  }

  /**
   * Just a validation to prevent usage of this method.
   *
   * @return void
   */
  public function __clone(): void {
    API::error('BaroEntity need to be cloned with method BaroEntity::clone($parent)');
  }

  /**
   * Converts object to XML string.
   *
   * @todo: This shit needs refactoring, some day...
   *
   * @return string
   */
  public function toXML(): string {
    // Get value if possible.
    $children = $this->hasValue() ? $this->value() : '';
    // Prepare children.
    if ($this->hasChildren()) {
      foreach ($this->children() as $child) {
        $children .= $child->toXML();
      }
    }
    // Convert attributes.
    $attributes = '';
    foreach ($this->attributes() as $attribute => $value) {
      // Convert boolean values to words.
      if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
      }
      // Append attribute.
      $attributes .= " $attribute=\"$value\"";
    }
    $tag = $this->name();
    if (empty($children)) {
      return "<$tag$attributes/>";
    }
    else {
      return "<$tag$attributes>$children</$tag>";
    }
  }

  /**
   * Get source application name.
   *
   * @param string|int|NULL - $appID - Ability to set another app id.
   *
   * @return string
   */
  abstract public function appID(string|int $appID = NULL): string;

  /**
   * Get source file path.
   *
   * @param string|NULL $file - Filepath to export.
   *
   * @return string
   */
  abstract public function file(string $file = NULL): string;

  /**
   * Get root entity.
   *
   * Return root entity of current entity. May return itself.
   *
   * @return RootEntity
   */
  abstract public function root(): RootEntity;

  /**
   * Enables entity lock.
   *
   * @return void
   */
  abstract public function lock(): void;

  /**
   * Returns TRUE if this entity has been locked.
   *
   * @return bool
   */
  abstract public function isLocked(): bool;

  /**
   * Method to break entity lock.
   *
   * Method BaroEntity::isModified() will start to return TRUE.
   *
   * @return void
   */
  abstract public function breakLock(): void;

  /**
   * Returns TRUE if this entity has been modified after being locked.
   *
   * Returns FALSE in all other cases.
   *
   * @return bool
   */
  abstract public function isModified(): bool;

  /**
   * Prepares a string for a debugging message.
   *
   * String will contain root entity ID and type, also current entity type.
   * @todo: Include information about application and file.
   *
   * @return string
   */
  abstract public function debug(): string;

}
