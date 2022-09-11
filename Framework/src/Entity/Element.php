<?php

/**
 * @file
 * RootEntity declaration.
 */

namespace Barotraumix\Framework\Entity;

/**
 * Class definition
 */
class Element extends BaroEntity {

  /**
   * @var BaroEntity - Parent BaroEntity object.
   */
  protected BaroEntity $parent;

  /**
   * @var BaroEntity - Variable which is used to properly clone an element.
   */
  protected BaroEntity $parentCloned;

  /**
   * @inheritDoc
   *
   * @param BaroEntity $parent - Attached parent.
   *  String - Means that this is a root entity which always has a type.
   *  BaroEntity - Means that this is a sub-element which always has root entity.
   */
  public function __construct(string $name, array $attributes, BaroEntity $parent) {
    // Attach parent entity first.
    $this->parent = $parent;
    parent::__construct($name, $attributes);
  }

  /**
   * @inheritDoc.
   */
  public function appID(string|int $appID = NULL): string {
    return $this->root()->appID($appID);
  }

  /**
   * Get source file path.
   *
   * @param string|NULL $file - Filepath to export.
   *
   * @return string
   */
  public function file(string $file = NULL): string {
    return $this->root()->file($file);
  }

  /**
   * Get parent entity.
   *
   * @return BaroEntity
   */
  public function parent(): BaroEntity {
    return $this->parent;
  }

  /**
   * Get root entity.
   *
   * Return root entity of current entity. May return itself.
   *
   * @return RootEntity
   */
  public function root(): RootEntity {
    $current = $this;
    while (TRUE) {
      $parent = $current->parent();
      if ($parent instanceof RootEntity) {
        return $parent;
      }
      $current = $parent;
    }
  }

  /**
   * @inheritDoc.
   */
  public function lock(): void {
    $this->root()->lock();
  }

  /**
   * @inheritDoc.
   */
  public function isLocked(): bool {
    return $this->root()->isLocked();
  }

  /**
   * @inheritDoc.
   */
  public function breakLock(): void {
    $this->root()->breakLock();
  }

  /**
   * @inheritDoc.
   */
  public function isModified(): bool {
    return $this->root()->isModified();
  }

  /**
   * @inheritDoc.
   */
  public function remove(): void {
    parent::remove();
    $this->parent()->unsetChildren($this->id());
  }

  /**
   * @inheritDoc.
   */
  public function debug(): string {
    $id = $this->id();
    $name = $this->name();
    $root = $this->root();
    $rootID = $root->id();
    $rootType = $root->type();
    return "Sub-element: '$name' with ID: '$id' owned by a root entity: '$rootType' with ID: '$rootID'";
  }

  /**
   * @inheritDoc.
   */
  public function create(BaroEntity $parent = NULL): static {
    return new static($this->name(), $this->attributes(), $parent);
  }

}