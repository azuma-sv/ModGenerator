<?php
/**
 * @file
 * Framework general settings.
 */

namespace Barotraumix\Generator\Services;

/**
 * Class definition.
 */
class SettingsFramework {

  /**
   * @var Settings - Settings storage.
   */
  protected Settings $settings;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->settings = new Settings('settings.yml');
  }

  /**
   * Get raw settings object.
   *
   * @return Settings
   */
  public function raw(): Settings {
    return $this->settings;
  }

  /**
   * Get raw array with settings.
   *
   * @return array
   */
  public function array(): array {
    return $this->settings->array();
  }

  /**
   * Path to the game.
   */
  public function pathGame(string $path = ''): string {
    return $this->pathPrepare($this->settings->get('files')['game'] . "/$path");
  }

  /**
   * Method to prepare real path by relative path.
   *
   * @param $path - Relative path (relative to project root).
   *
   * @return string
   */
  public function pathPrepare($path): string {
    return BASE_PATH . '/' . $path;
  }

}