<?php
/**
 * @file
 * Framework general settings.
 */

namespace Barotraumix\Generator\Services;

/**
 * Class definition.
 */
class Framework {

  /**
   * @const BAROTRAUMA_APP_ID - Contains barotrauma app ID in steam.
   */
  const BAROTRAUMA_APP_ID = 1026340;

  /**
   * @const BAROTRAUMA_APP_NAME - Barotrauma app name.
   */
  const BAROTRAUMA_APP_NAME = 'Barotrauma';

  /**
   * @var Settings - Settings storage.
   */
  public static Settings $settings;

  /**
   * Class constructor.
   */
  public function __construct() {
    static::$settings = new Settings('settings.yml');
  }

  /**
   * Returns an array of applications and their IDs.
   *
   * @return array
   */
  public static function applications(): array {
    return static::$settings->get('applications');
  }

  /**
   * Path to the game.
   *
   * @param string $path - Sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathGame(string $path = ''): string {
    return static::pathPrepare(static::$settings->get('files')['game'] . "/$path");
  }

  /**
   * Path to the mods from workshop.
   *
   * @param string $path - Sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathWorkshop(string $path = ''): string {
    return static::pathPrepare(static::$settings->get('files')['workshop'] . "/$path");
  }

  /**
   * Path to the source files of mods to compile.
   *
   * @param string $path - Sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathInput(string $path = ''): string {
    return static::pathPrepare(static::$settings->get('files')['input'] . "/$path");
  }

  /**
   * Path to export generated mod.
   *
   * @param string $path - Sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathOutput(string $path = ''): string {
    return static::pathPrepare(static::$settings->get('files')['output'] . "/$path");
  }

  /**
   * Method to prepare directory by path.
   *
   * @param string $path - Absolute path to directory.
   *
   * @return bool
   */
  public static function prepareDirectory(string $path): bool {
    $status = file_exists($path);
    if (!$status) {
      $status = mkdir($path, 0777, TRUE);
    }
    return $status;
  }

  /**
   * Method to prepare real path by relative path.
   *
   * @param $path - Relative path (relative to project root).
   *
   * @return string
   */
  public static function pathPrepare($path): string {
    return BASE_PATH . '/' . $path;
  }

  /**
   * Error message.
   *
   * @param $msg - Message to throw.
   */
  public static function error($msg): void {
    // Debugging information.
    $backtrace = debug_backtrace();
    $debug = reset($backtrace);
    print $debug['file'] . " :: " . $debug['line'] . "\r\n";
    // Log error.
    static::log($msg, 0);
    exit();
  }

  /**
   * Notice message.
   *
   * @param $msg - Message to throw.
   *
   * @return void
   */
  public static function notice($msg): void {
    static::log($msg, 1);
  }

  /**
   * Info message.
   *
   * @param $msg - Message to throw.
   *
   * @return void
   */
  public static function info($msg): void {
    static::log($msg, 2);
  }

  /**
   * Debug message.
   *
   * @param $msg - Message to throw.
   *
   * @return void
   */
  public static function debug($msg): void {
    static::log($msg, 3);
  }

  /**
   * Function to send message to console.
   *
   * @param $msg - Message to throw.
   * @param $level - 0 is error, 1 is notice, 2 info log, 3 is hidden debugging.
   *
   * @return void
   */
  public static function log($msg, $level): void {
    // @todo: Implement layers of logging.
    unset($level);
    // Null.
    if (!isset($msg)) {
      $msg = $GLOBALS;
    }
    // Object.
    if (is_object($msg)) {
      $msg = (array) $msg;
    }
    // Array.
    if (is_array($msg)) {
      $msg = print_r($msg, TRUE);
    }
    // String.
    $msg = strval($msg);
    // Print.
    print $msg;
    print "\r\n";
  }

}