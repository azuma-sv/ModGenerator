<?php

/**
 * @file
 * Container for services and variables which might help when we parse game
 *   source files.
 */

namespace Barotraumix\Generator;

use Barotraumix\Generator\BaroEntity\Base;

/**
 * Class definition.
 */
class Services {

  /**
   * @var Core - Core service.
   */
  protected Core $core;

  /**
   * @var int - Application ID.
   */
  protected int $appId;

  /**
   * @var int - Build ID of the application.
   */
  protected int $buildId;

  /**
   * @var Settings - Mapping for tags with incorrect tag name case.
   */
  protected Settings $mappingTags;

  /**
   * @var Settings - Mapping for entities with incorrect tag name.
   */
  protected Settings $mappingEntities;

  /**
   * @var array - Storage for parsed entities and their children.
   */
  protected array $storage = [];

  /**
   * Class constructor.
   */
  public function __construct(int $appId, int $buildId, Core $core) {
    $this->appId = $appId;
    $this->buildId = $buildId;
    $this->core = $core;
    $this->mappingTags = new Settings('mapping.tags.yml');
    $this->mappingEntities = new Settings('mapping.entity.yml');
  }

  /**
   * Returns application ID.
   *
   * @return int
   */
  public function appId(): int {
    return $this->appId;
  }

  /**
   * Returns build id of the application.
   *
   * @return int
   */
  public function buildId(): int {
    return $this->buildId;
  }

  /**
   * Check if current application is Barotrauma game.
   *
   * @return bool
   */
  public function isGame(): bool {
    return Core::BAROTRAUMA_APP_ID == $this->appId();
  }

  /**
   * Check if current application is Barotrauma mod.
   *
   * @return bool
   */
  public function isMod(): bool {
    return !$this->isGame();
  }

  /**
   * Method to validate if current entity is an item entity.
   *
   * @param string|Base $entity - Entity to check.
   * @param string|Base $parent - Parent entity to check.
   *
   * @return bool
   */
  public function isItem(Base $entity): bool {
    $name = $entity->getName();
    $parent = $entity->getParent();
    $mappingEntities = $this->mappingEntities;
    return in_array($parent, ['Items', Core::PARENT_ROOT]) && ($name == 'Item' || $mappingEntities->has($name) && $mappingEntities->get($name) == 'Item');
  }

  /**
   * Game has a bunch of objects which should be considered as item.
   *
   * @return array
   */
  public function getItemTypes(): array {
    return array_keys($this->mappingEntities->settings(), 'Item');
  }

  /**
   * Method to return raw array with tags mapping.
   *
   * @return Settings
   */
  public function mappingTags(): Settings {
    return $this->mappingTags;
  }

  /**
   * Method to return raw array with entities mapping.
   *
   * @return Settings
   */
  public function mappingEntities(): Settings {
    return $this->mappingEntities;
  }

  /**
   * Method to normalize tag name.
   *
   * @param string $name - XML tag name.
   *
   * @return string
   */
  public function normalizeTag(string $name): string {
    // Look for appropriate name in mapping.
    if ($this->mappingTags->has($name)) {
      $name = $this->mappingTags->get($name);
    }
    // Return normalized value.
    return strval($name);
  }

  /**
   * Method to prepare real path by relative path.
   *
   * @param $path - Relative path (relative to project root).
   *
   * @return string
   */
  public function pathPrepare($path): string {
    return $this->core->pathGame() . '/' . $this->appId() . '/' . $this->buildId() . '/' . $path;
  }

  /**
   * Get storage with parsed entities.
   *
   * @return array
   */
  public function storage(): array {
    return $this->storage;
  }

  /**
   * Method to set data into storage.
   *
   * @param string $entityType - Section name.
   * @param string $entityId - Item identifier to store.
   * @param Base $entity - Entity to store.
   *
   * @todo: I am not sure about $parent variable.
   *
   * @return void
   */
  public function storageSet(string $entityType, string $entityId, Base $entity): void {
    if (isset($this->storage['entities'][$entityType][$entityId])) {
      // Throw error.
      Core::error('Attempt to replace existing entity with ID: ' . $entityId);
    }
    // Save new entity.
    $this->storage['entities'][$entityType][$entityId] = $entity;
  }

  /**
   * Method to process parsed object.
   *
   * @param Base $entity - Parsed entity.
   * @param string $parent - Parent entity name.
   *
   * @return void
   */
  public function process(Base $entity):void {
    $name = $entity->getName();
    $parent = $entity->getParent();
    $attributes = $entity->getAttributes();
    // Add entity to list.
    if ($this->isItem($entity, $parent)) {
      $this->processIdentifier($entity);
      $this->storageSet('Item', $entity->getAttribute('identifier'), $entity);
    }
    // Set tag data.
    if (!isset($this->storage['tags'][$name])) {
      $this->storage['tags'][$name] = [];
      $this->storage['tags'][$name]['count'] = 0;
      $this->storage['tags'][$name]['parents'] = [];
    }
    $tag = &$this->storage['tags'][$name];
    $tag['count']++;
    $tag['parents'][$parent] = $parent;
    // Process children.
    foreach ($attributes as $key => $value) {
      // Set attribute data.
      if (!isset($this->storage['attributes'][$key])) {
        $this->storage['attributes'][$key] = [];
        $this->storage['attributes'][$key]['count'] = 0;
        $this->storage['attributes'][$key]['parents'] = [];
      }
      $attribute = &$this->storage['attributes'][$key];
      $attribute['count']++;
      $attribute['parents'][$name] = $name;
    }
  }

  /**
   * Verifies or creates identifier for entity.
   *
   * At current moment can be used only for items.
   *
   * @param Base $entity
   *
   * @return void
   */
  protected function processIdentifier(Base $entity): void {
    // Init variables.
    $identifier = NULL;
    $nameIdentifier = NULL;
    // Process identifier.
    if ($entity->hasAttribute('identifier')) {
      $identifier = $entity->getAttribute('identifier');
      $identifier = !empty($identifier) ? $identifier : NULL;
    }
    // Success.
    if (!empty($identifier)) {
      return ;
    }
    // Process name identifier.
    if ($entity->hasAttribute('nameidentifier')) {
      $nameIdentifier = $entity->getAttribute('nameidentifier');
      $nameIdentifier = !empty($nameIdentifier) ? $nameIdentifier : NULL;
    }
    // Success.
    if (empty($nameIdentifier)) {
      Core::error('Unable to create identifier.');
    }
    // Generate new identifier for the case if I can't determine it in other way.
    $entity->setAttribute('identifier', $this->identifier($nameIdentifier));
  }

  /**
   * Generated identifier based on some string.
   *
   * @param string $id - Base string to use to generate identifier.
   *
   * @return string
   */
  protected function identifier(string $id): string {
    // Static storage with identifiers.
    static $identifiers;
    // Validate string.
    if (empty($id)) {
      Core::error('String ID can\'t be empty.');
    }
    // Attempt to create one.
    if (!isset($identifiers[$id])) {
      $identifiers[$id] = 0;
    }
    // Generate new value.
    $identifiers[$id]++;
    $identifier = $id . $identifiers[$id];
    Core::notice('New identifier has been created: ' . $identifier);
    return $identifier;
  }

}
