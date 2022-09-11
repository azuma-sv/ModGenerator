<?php
/**
 * @file
 * API general settings.
 */

namespace Barotraumix\Framework\Services;

/**
 * Class definition.
 */
class API {

  /**
   * @const BAROTRAUMA_APP_ID - Contains barotrauma app ID in steam.
   */
  const APP_ID = 1026340;

  /**
   * @const BAROTRAUMA_APP_NAME - Barotrauma app name.
   */
  const APP_NAME = 'Barotrauma';

  /**
   * @const string - Primary mod file name.
   */
  const MOD_FILE = 'filelist.yml';

  /**
   * @const array - Array of attribute names which might contain files.
   */
  const ATTRIBUTE_FILES = ['file', 'texture', 'vineatlas', 'decayatlas'];

  /**
   * High level function to get path to Barotrauma or Workshop mods.
   *
   * @param string $path - Specified path.
   * @param string|int $id - Application ID to browse.
   *
   * @return string
   */
  public static function getPath(string $path, string|int $id = API::APP_ID): string {
    if ($id == API::APP_ID) {
      return static::pathGame($path);
    }
    return static::pathWorkshop($path, $id);
  }

  /**
   * Path to the game.
   *
   * @param string $path - Relative sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathGame(string $path = ''): string {
    static $root;
    if (!isset($root)) {
      if (empty($_ENV['BMF_PATH_GAME'])) {
        API::error('Environment variable BMF_PATH_GAME is not defined.');
      }
      $root = $_ENV['BMF_PATH_GAME'];
    }
    return empty($path) ? $root : "$root/$path";
  }

  /**
   * Path to the mods from workshop.
   *
   * @param string $path - Relative sub-path to attach (optional).
   * @param string|int $id - Application ID to use.
   *
   * @return string
   */
  public static function pathWorkshop(string $path = '', string|int $id = ''): string {
    static $root;
    if (!isset($root)) {
      if (empty($_ENV['BMF_PATH_WORKSHOP'])) {
        API::error('Environment variable BMF_PATH_WORKSHOP is not defined.');
      }
      $root = $_ENV['BMF_PATH_WORKSHOP'];
    }
    $path = empty($id) ? $path : "$id/$path";
    return empty($path) ? $root : "$root/$path";
  }

  /**
   * Path to the source files of mods to compile.
   *
   * @param string $path - Relative sub-path to attach (optional).
   * @param string $mod - Mod name.
   *
   * @return string
   */
  public static function pathInput(string $path = '', string $mod = ''): string {
    static $root;
    if (!isset($root)) {
      if (empty($_ENV['BMF_PATH_INPUT'])) {
        API::error('Environment variable BMF_PATH_INPUT is not defined.');
      }
      $root = $_ENV['BMF_PATH_INPUT'];
    }
    $path = empty($mod) ? $path : "$mod/$path";
    return empty($path) ? $root : "$root/$path";
  }

  /**
   * Path to export generated mod.
   *
   * @param string $path - Relative sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathOutput(string $path = ''): string {
    static $root;
    if (!isset($root)) {
      if (empty($_ENV['BMF_PATH_INPUT'])) {
        API::error('Environment variable BMF_PATH_INPUT is not defined.');
      }
      $root = $_ENV['BMF_PATH_OUTPUT'];
    }
    return empty($path) ? $root : "$root/$path";
  }

  /**
   * Path to framework sources.
   *
   * @param string $path - Relative path (optional).
   *
   * @return string
   */
  public static function pathFramework(string $path = ''): string {
    static $root;
    if (!isset($root)) {
      if (empty($_ENV['BMF_PATH_FRAMEWORK'])) {
        API::error('Environment variable BMF_PATH_FRAMEWORK is not defined.');
      }
      $root = $_ENV['BMF_PATH_FRAMEWORK'];
    }
    return empty($path) ? $root : "$root/$path";
  }

  /**
   * Returns game-like path to content package file.
   *
   * @param string $id - Application ID.
   *
   * @return string
   */
  public static function pathContentPackage(string|int $id): string {
    $isGame = API::APP_ID == $id;
    $package = $isGame ? 'Vanilla' : 'filelist';
    return $isGame ? "Content/ContentPackages/$package.xml" : "$package.xml";
  }

  /**
   * Get path to directory from path to the file.
   *
   * @param string $filePath - Path to the file.
   *
   * @return string
   */
  public static function getFileDirectory(string $filePath): string {
    $path = explode('/', $filePath);
    array_pop($path);
    return implode('/', $path);
  }

  /**
   * Method to prepare directory by path.
   *
   * @param string $path - Absolute path to directory.
   * @param bool $isFilePath - Indicates that current path is a path to the file.
   *
   * @return bool
   */
  public static function prepareDirectory(string $path, bool $isFilePath = FALSE): bool {
    // Process file path.
    if ($isFilePath) {
      $path = static::getFileDirectory($path);
    }
    // Create directory.
    $status = is_dir($path) && !is_link($path);
    if (!$status) {
      $status = mkdir($path, 0777, TRUE);
    }
    return $status;
  }

  /**
   * Removes directory and it's content recursively.
   *
   * @param string $path - Path to directory.
   *
   * @return bool
   */
  public static function removeDirectory(string $path): bool {
    if (is_dir($path)) {
      foreach (scandir($path) as $object) {
        if ($object != '.' && $object != '..') {
          $full = "$path/$object";
          if (is_dir($full)) {
            static::removeDirectory($full);
          }
          else {
            if (!unlink($full)) {
              return FALSE;
            }
          }
        }
      }
      if (!rmdir($path)) {
        return FALSE;
      }
    }
    return TRUE;
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