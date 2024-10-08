<?php

/**
 * @file
 * RootEntity declaration.
 */

namespace Barotraumix\Framework\Entity;

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
   * @param string $type - Entity type.
   * @param string|int $id - Source application ID.
   * @param string $file - Path to the source file.
   */
  public function __construct(string $name, array $attributes, string $type, string|int $id, string $file) {
    // Keep metadata.
    $this->type = $type;
    $this->appID = $id;
    $this->file = $file;
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
   * Get object type (Asset type).
   *
   * @return string
   */
  public function type(): string {
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
   * @todo: Think on how it may be refactored.
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
          if (isset($subFolder) && stripos($value, '%ModDir%') !== FALSE) {
            $value = str_ireplace('%ModDir%', $subFolder, $value);
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
    $name = $this->name();
    $type = $this->type();
    return "Root entity '$name' of type: '$type'";
  }

  /**
   * @inheritDoc.
   */
  protected function createClone(BaroEntity $parent = NULL): static {
    if (isset($parent)) {
      API::error('Root entity cannot have parent.');
    }
    $cloned = new static($this->name(), $this->attributes(), $this->type(), $this->appID(), $this->file());
    // @todo: Find out the way on how to determine if we override or steal something from other mod (application).
    $override = TRUE;
    $cloned->override($this->appID() == API::APP_ID || $override);
    return $cloned;
  }

}