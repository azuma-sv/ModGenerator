<?php

/**
 * @file
 * Container for services and variables which might help when we parse game
 *   source files.
 */

namespace Barotraumix\Generator\Services;

use Barotraumix\Generator\Core;
use Barotraumix\Generator\Entity\BaroEntity;

/**
 * Class definition.
 */
class Services {

  /**
   * @var Framework - Barotrauma modding framework service.
   */
  public static Framework $framework;

  /**
   * @var SteamCMD SteamCMD connector service.
   */
  public static  SteamCMD $steam;

  /**
   * @var Settings - Mapping for tags with incorrect tag name case.
   */
  public static Settings $mappingTags;

  /**
   * @var Settings - Mapping for entities with incorrect tag name.
   */
  public static Settings $mappingEntities;

  /**
   * Storage for content of this application.
   */
  public static  Database $database;

  /**
   * Class constructor.
   */
  public function __construct() {
    // Initialize framework utilities.
    static::$framework = new Framework();
    // Create Steam connector service and storage.
    static::$steam = new SteamCMD();
    // Initialize database for objects.
    static::$database = new Database();
    // Process additional settings.
    static::$mappingTags = new Settings('mapping.tags.yml');
    static::$mappingEntities = new Settings('mapping.entity.yml');
  }

  /**
   * Method to get framework service object.
   *
   * @return Core
   */
  public static function framework(): Framework {
    return static::$framework;
  }

  /**
   * Method to get data storage.
   *
   * @return Database
   */
  public function bank(): Database {
    return $this->bank;
  }

  /**
   * Method to return raw array with tags mapping.
   *
   * @return Settings
   */
  public static function mappingTags(): Settings {
    return static::$mappingTags;
  }

  /**
   * Method to return raw array with entities mapping.
   *
   * @return Settings
   */
  public static function mappingEntities(): Settings {
    return static::$mappingEntities;
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
