<?php

/**
 * @file
 * Container for services and variables which might help when we parse game
 *   source files.
 */

namespace Barotraumix\Framework\Services;

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
   * @var Settings - Temporary settings storage.
   */
  public static Settings $temp;

  /**
   * Storage for content of this application.
   */
  public static Database $database;

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
    static::$mappingTags = new Settings('src/mapping.tags.yml');
    static::$mappingEntities = new Settings('src/mapping.entity.yml');
    static::$temp = new Settings('src/temp.yml');
  }

  /**
   * Check if current application is Barotrauma game.
   *
   * @param string $application - Application name.
   *
   * @return bool
   */
  public static function isGame(string $application): bool {
    return Framework::BAROTRAUMA_APP_NAME == $application;
  }

  /**
   * Check if current application is Barotrauma mod.
   *
   * @todo: Maybe remove?
   *
   * @param string $application - Application name.
   *
   * @return bool
   */
  public static function isMod(string $application): bool {
    return !static::isGame($application);
  }

  /**
   * Method to get/set build id by application id.
   *
   * @param string|int $appId - Application ID.
   * @param string|int|NULL $buildId - Build ID to set (optional).
   *
   * @return int|NULL
   */
  public static function buildId(string|int $appId, string|int $buildId = NULL): int|NULL {
    $appId = intval($appId);
    // Set value.
    if (isset($buildId)) {
      static::$temp->set(['applications', $appId], intval($buildId));
      static::$temp->save();
    }
    // Return value.
    if (static::$temp->has(['applications', $appId])) {
      return intval(static::$temp->get(['applications', $appId]));
    }
    // Return nothing.
    return NULL;
  }

  /**
   * Method to get application ID and build ID by application name.
   *
   * @param string $application - Application name.
   *
   * @return array
   */
  public static function applicationIDs(string $application = Framework::BAROTRAUMA_APP_NAME): array {
    // Prepare empty result.
    $ids = ['appId' => NULL, 'buildId' => NULL];
    // Attempt to get app id.
    $id = static::$database->applications($application);
    if (isset($id)) {
      $ids['appId'] = $id;
      // Attempt to get build id.
      $bid = static::buildId($id);
      if (isset($bid)) {
        $ids['buildId'] = $bid;
      }
    }
    return $ids;
  }

  /**
   * Method to normalize tag name.
   *
   * @param string $name - XML tag name to normalize.
   *
   * @return string
   */
  public static function normalizeTagName(string $name): string {
    // Look for appropriate name in mapping.
    if (static::$mappingTags->has($name)) {
      // @todo: Refactor settings file.
      $name = static::$mappingTags->get($name);
    }
    // Return normalized value.
    return strval($name);
  }

  /**
   * Returns game-like path to content package file.
   *
   * @param string $application - Application name.
   *
   * @return string
   */
  public static function gameLikePathToContentPackage(string $application): string {
    $isGame = static::isGame($application);
    $package = $isGame ? 'Vanilla' : 'filelist';
    return $isGame ? "Content/ContentPackages/$package.xml" : "$package.xml";
  }

}
