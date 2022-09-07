<?php

/**
 * @file
 * Container for services and variables which might help when we parse game
 *   source files.
 */

namespace Barotraumix\Generator;

use Barotraumix\Generator\Entity\BaroEntity;

/**
 * Class definition.
 */
class Services {

  /**
   * @var Core - Core service.
   */
  protected Core $core;

  /**
   * @var Settings - Mapping for tags with incorrect tag name case.
   */
  protected Settings $mappingTags;

  /**
   * @var Settings - Mapping for entities with incorrect tag name.
   */
  protected Settings $mappingEntities;

  /**
   * Storage for content of this application.
   */
  protected Bank $bank;

  /**
   * Class constructor.
   */
  public function __construct(Core $core) {
    $this->core = $core;
    $this->mappingTags = new Settings('mapping.tags.yml');
    $this->mappingEntities = new Settings('mapping.entity.yml');
    $this->bank = $core->bank();
    // Detect context name.
    $applications = $core->settings()->get('applications');
    foreach ($applications as $context => $data) {
      if ($data['appId'] == $appId && $data['buildId'] == $buildId) {
        $this->context = $context;
        break;
      }
    }
    // Throw error.
    if (!isset($this->context)) {
      Core::error("Unable to find context for this application. (app: $appId, build: $buildId)");
    }
  }

  /**
   * Method to expose Core service for public use.
   *
   * @return Core
   */
  public function core(): Core {
    return $this->core;
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
   * Method to process tree of parsed objects.
   *
   * @param BaroEntity $entity - Parsed entity.
   *
   * @return void
   */
  public function processTree(BaroEntity $entity):void {
    // Additionally process items.
    if ($entity->isEntity() && $entity->type() == 'Item') {
      $this->processIdentifier($entity);
    }
    // Process children recursively.
    if ($entity->hasChildren()) {
      foreach ($entity->children() as $child) {
        $this->processTree($child);
      }
    }
    // Add entity to bank.
    $this->bank->addEntity($entity, $this->context);
  }

  /**
   * Verifies or creates identifier for entity.
   *
   * At current moment can be used only for items.
   *
   * @todo: Identifier should be stored in BaroEntity but not reflected in XML.
   *
   * @param BaroEntity $entity
   *
   * @return void
   */
  protected function processIdentifier(BaroEntity $entity): void {
    // Init variables.
    $identifier = NULL;
    $nameIdentifier = NULL;
    // Process identifier.
    if ($entity->hasAttribute('identifier')) {
      $identifier = $entity->attribute('identifier');
      $identifier = !empty($identifier) ? $identifier : NULL;
    }
    // Success.
    if (!empty($identifier)) {
      return ;
    }
    // Process name identifier.
    if ($entity->hasAttribute('nameidentifier')) {
      $nameIdentifier = $entity->attribute('nameidentifier');
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
