<?php

/**
 * @file
 * RootEntity declaration.
 */

namespace Barotraumix\Framework\Entity;

use Barotraumix\Framework\Core;

/**
 * Class definition
 */
class RootEntity extends BaroEntity {

  /**
   * @var string - Source application ID.
   */
  protected string $appID;

  /**
   * @var string - Game-like path to source file.
   */
  protected string $file;

  /**
   * @var string - Entity type which can be indicated by XML tag name and parent XML tag name.
   */
  protected string $type;

  /**
   * @inheritDoc
   *
   * @param string|int $id - Source application ID.
   * @param string $file - Path to the source file.
   */
  public function __construct(string $name, array $attributes, string|int $id, string $file) {
    // Keep metadata.
    $this->appID = $id;
    $this->file = $file;
    // Initialize entity type.
    if (Core::services()->mappingEntities->has($name)) {
      $this->type = Core::services()->mappingEntities->get($name);
    }
    parent::__construct($name, $attributes);
  }

  /**
   * Get source application name.
   *
   * @return string
   */
  public function appID(): string {
    return $this->appID;
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
   * @inheritDoc.
   */
  public function root(): RootEntity {
    return $this;
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
   * @inheritDoc.
   */
  public function lock(): void {
    $this->locked = TRUE;
  }

  /**
   * @inheritDoc.
   */
  public function isLocked(): bool {
    return !empty($this->locked);
  }

  /**
   * @inheritDoc.
   */
  public function breakLock(): void {
    $this->locked = NULL;
  }

  /**
   * @inheritDoc.
   */
  public function isModified(): bool {
    return !isset($this->locked);
  }

  /**
   * @inheritDoc.
   */
  public function debug(): string {
    $id = $this->id();
    $type = $this->type();
    return "Root entity with ID: '$id' of type: '$type'";
  }

  /**
   * @inheritDoc.
   */
  public function create(BaroEntity $parent = NULL): static {
    return new static($this->name(), $this->attributes(), $this->appID(), $this->file());
  }

}