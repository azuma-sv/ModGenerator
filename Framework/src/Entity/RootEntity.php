<?php

/**
 * @file
 * RootEntity declaration.
 */

namespace Barotraumix\Framework\Entity;

use Barotraumix\Framework\Core;
use Barotraumix\Framework\Services\API;

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
   * @var bool - Override status.
   */
  protected bool $override = FALSE;

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
   * @inheritDoc.
   */
  public function appID(string|int $appID = NULL): string {
    if (isset($appID)) {
      $this->appID = $appID;
    }
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
   * Returns status of the entity.
   *
   * TRUE - If it overrides some other entity.
   * FALSE - Means that this is a completely new entity.
   *
   * @param bool|NULL $override - Set new override status.
   *
   * @return bool
   */
  public function override(bool $override = NULL): bool {
    if (isset($override)) {
      $this->override = $override;
    }
    return $this->override;
  }

  /**
   * Scans current entity for available sprites.
   *
   * @param string|NULL $subFolder - Indicates that file need to be replaced
   *  from original position to another sub-folder.
   * @param Element|NULL $entity - Recursively call scanning for children.
   * @param array $sprites - Storage for sprites.
   *
   * @return array
   */
  public function sprites(string $subFolder = NULL, Element $entity = NULL, array &$sprites = []): array {
    $entity = $entity ?? $this;
    foreach (API::ATTRIBUTE_FILES as $attribute) {
      if ($entity->hasAttribute($attribute)) {
        $value = $entity->attribute($attribute);
        $path = API::getPath(str_ireplace('%ModDir%/', '', $value), $this->appID());
        if (!is_dir($path) && file_exists($path)) {
          // Always keep original value.
          if (!isset($sprites[$path]['ORIGINAL'])) {
            $sprites[$path]['ORIGINAL'] = $value;
          }
          // In the case if we need to replace file in another sub-folder.
          if (isset($subFolder)) {
            if (stripos($value, '%ModDir%') !== FALSE) {
              $value = str_ireplace('%ModDir%', $subFolder, $value);
            }
            else {
              $value = API::APP_NAME . "/$value";
            }
            $entity->setAttribute($attribute, $value);
          }
          // Always keep active value.
          if (!isset($sprites[$path]['ACTIVE'])) {
            $sprites[$path]['ACTIVE'] = $value;
          }
          // @todo: Ability to cut sprites for smaller pieces.
          if ($entity->hasAttribute('sourcerect')) {
            $sprites[$path][$entity->attribute('sourcerect')] = $value;
          }
          else {
            // In the case if we have a full-sized sprite.
            $sprites[$path]['FULL'] = $value;
          }
        }
      }
    }
    if ($entity->hasChildren()) {
      foreach ($entity->children() as $child) {
        $this->sprites($subFolder, $child, $sprites);
      }
    }
    return $sprites;
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
    $cloned = new static($this->name(), $this->attributes(), $this->appID(), $this->file());
    $cloned->override($this->appID() == API::APP_ID || $this->override());
    return $cloned;
  }

}