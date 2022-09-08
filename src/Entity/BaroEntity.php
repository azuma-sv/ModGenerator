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
use Barotraumix\Framework\Services\Framework;
use Barotraumix\Framework\Services\Services;
use SimpleXMLElement;

/**
 * Class BaroEntity.
 */
class BaroEntity {

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
   * @var BaroEntity - Parent BaroEntity object.
   */
  protected BaroEntity $parent;

  /**
   * @var string - Source application name.
   */
  protected string $application;

  /**
   * @var string - Path to source file.
   */
  protected string $file;

  /**
   * @var string - Entity type which can be indicated by XML tag name and parent XML tag name.
   */
  protected string $type;

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
   * Class constructor is protected to force usage of Base::createFrom().
   *
   * @param string $name - XML tag name.
   * @param array $attributes - XML attributes as array.
   * @param string $application - Source application.
   * @param string $file - Path to the source file.
   * @param BaroEntity|NULL $parent - Indicates if it's an entity or sub-element.
   *  String - Means that this is a root entity which always has a type.
   *  BaroEntity - Means that this is a sub-element which always has root entity.
   */
  public function __construct(string $name, array $attributes, string $application, string $file, BaroEntity $parent = NULL) {
    // Save main data.
    $this->setName($name);
    $this->addAttributes($attributes);
    // Keep metadata.
    $this->application = $application;
    $this->file = $file;
    // Attach parent entity.
    if (isset($parent)) {
      $this->parent = $parent;
    }
    // Initialize entity type.
    if (!isset($parent)) {
      $mappingEntities = Services::$mappingEntities->array();
      $this->type = $mappingEntities[$name] ?? NULL;
    }
    // Detect entity ID.
    $this->detectIdentifier();
  }

  /**
   * Method to get entity ID.
   *
   * If this entity has no ID - it will return parent entity id.
   *
   * @return string
   */
  public function id(): string {
    if (!$this->hasID()) {
      return $this->root()->id();
    }
    return $this->getID();
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
   * Get source application name.
   *
   * @return string
   */
  public function application(): string {
    return $this->application;
  }

  /**
   * Get source file path.
   *
   * @param string|NULL $file - Filepath to export.
   *
   * @return string
   */
  public function file(string $file = NULL): string {
    // Update file if needed.
    if (isset($file)) {
      $this->file = $file;
    }
    return $this->file;
  }

  /**
   * Check if current entity can be used as normal entity (not a sub-element).
   *
   * @return bool
   */
  public function isEntity(): bool {
    return $this->hasID();
  }

  /**
   * Get parent entity.
   *
   * @return BaroEntity|NULL
   */
  public function parent(): BaroEntity|NULL {
    // Prevent error.
    if ($this->isRoot()) {
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
          Framework::error('Root entity is not a normal entity. This case needs to be reported.');
        }
        return $current;
      }
      $current = $parent;
    }
  }

  /**
   * Method to check if current entity is a root entity.
   *
   * @return bool
   */
  public function isRoot(): bool {
    return !isset($this->parent);
  }

  /**
   * Enables entity lock.
   *
   * @return void
   */
  public function lock(): void {
    if ($this->isRoot()) {
      $this->locked = TRUE;
      return ;
    }
    $this->root()->lock();
  }

  /**
   * Returns TRUE if this entity has been locked.
   *
   * @return bool
   */
  public function isLocked(): bool {
    if ($this->isRoot()) {
      return !empty($this->locked);
    }
    return $this->root()->isLocked();
  }

  /**
   * Returns TRUE if this entity has been modified after being locked.
   *
   * Returns FALSE in all other cases.
   *
   * @return bool
   */
  public function isModified(): bool {
    if ($this->isRoot()) {
      return !isset($this->locked);
    }
    return $this->root()->isModified();
  }

  /**
   * Method to break entity lock.
   *
   * Method BaroEntity::isModified() will start to return TRUE.
   *
   * @return void
   */
  public function breakLock(): void {
    if ($this->isRoot()) {
      $this->locked = NULL;
      return ;
    }
    $this->root()->breakLock();
  }

  /**
   * Method to remove entity in a proper way.
   *
   * Entity will be removed only from active context.
   *
   * @return void
   */
  public function remove(): void {
    $parent = $this->parent();
    $this->removed = TRUE;
    // Remove child reference.
    if (isset($parent) && $parent->hasChildren()) {
      foreach ($parent->children() as $index => $child) {
        if ($child->isRemoved()) {
          $parent->unsetChildren($index);
        }
      }
    }
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
   * Prepares a string for a debugging message.
   *
   * String will contain root entity ID and type, also current entity type.
   * @todo: Include information about application and file.
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
   * Magic method to clone BaroEntity properly.
   *
   * @todo: Cloned sub-elements might have an attempt to generate ID. Test...
   *
   * @param BaroEntity|NULL $parent - Parent entity (optional for root entities).
   *
   * @return BaroEntity
   */
  public function clone(BaroEntity $parent = NULL): BaroEntity {
    // Clone current entity with proper parent.
    $cloned = new BaroEntity($this->name(), $this->attributes(), $this->application(), $this->file(), $parent);
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
   * Just a validation to prevent usage of this method.
   *
   * @return void
   */
  public function __clone(): void {
    Framework::error('BaroEntity need to be cloned with method BaroEntity::clone($parent)');
  }

  /**
   * Converts object to XML string.
   *
   * @todo: Refactor to use more stable approach.
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
   * Verifies or creates identifier for entity.
   *
   * At current moment can be used only for Item or ContentPackage entity types.
   */
  protected function detectIdentifier(): void {
    // If current entity has a parent - it's not a real entity.
    if (!$this->isRoot()) {
      return ;
    }
    // Generate differently for different entities.
    $type = $this->type();
    switch ($type) {

      case 'ContentPackage':
        // Validate attribute.
        if (!$this->hasAttribute('name')) {
          Framework::error('ContentPackage has no name (' . $this->debug() . ').');
        }
        $this->setID($type . '>' . $this->attribute('name'));
        return ;

      case 'Item':
        // Process identifier.
        if ($this->hasAttribute('identifier')) {
          $this->setID($type . '>' . $this->attribute('identifier'));
          return ;
        }
        // Process name identifier.
        if ($this->hasAttribute('nameidentifier')) {
          $nameIdentifier = $this->attribute('nameidentifier');
        }
        // Validate identifier.
        if (empty($nameIdentifier)) {
          Framework::error('Unable to create identifier (' . $this->debug() . ').');
          return ;
        }
        // Generate new identifier for the case if I can't determine it in other way.
        $this->setID($type . '>' . $this->identifier($nameIdentifier));
        return ;
    }
    // Throw error.
    Framework::error('Entity has no ID (' . $this->debug() . ')');
  }

  /**
   * Generated identifier based on some string.
   *
   * @param string $id - Base string to use to generate identifier.
   *
   * @return string
   */
  protected function identifier(string $id): string {
    static $identifiers;
    if (!isset($identifiers[$id])) {
      $identifiers[$id] = 0;
    }
    $identifiers[$id]++;
    return $id . $identifiers[$id];
  }

}
