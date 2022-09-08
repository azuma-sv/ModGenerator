<?php
/**
 * @file
 * Framework general settings.
 */

namespace Barotraumix\Framework\Services;

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
   * @const string - Contains a string to reference name of the mod which we are generating.
   */
  const CONTEXT = 'CONTEXT';

  /**
   * @const string - Primary mod file name.
   */
  const PRIMARY_MOD_FILE = 'filelist.mod.yml';

  /**
   * @var Settings - Settings storage.
   */
  public static Settings $settings;

  /**
   * Method to get path to specific file or folder of specific application.
   *
   * @param string $file - File or folder to search.
   * @param string $application - Application name.
   * @param bool $validate - Ensure that file or folder exists.
   *
   * @return string
   */
  public static function getPath(string $file, string $application = Framework::BAROTRAUMA_APP_NAME, bool $validate = TRUE): string {
    // Prepare path.
    [$appId, $buildId] = array_values(Services::applicationIDs($application));
    if (Services::isGame($application)) {
      $path = Framework::pathGame("$appId/$buildId/$file");
    }
    else {
      // @todo: Implement once mods are integrated.
      $path = Framework::pathWorkshop("$appId/$buildId/$file");
    }
    // Ensure that file or folder path is reachable.
    if ($validate && !file_exists($path)) {
      $msg = "Unable to locate file or folder '$file' of the app: '$application'";
      Framework::error($msg);
    }
    return $path;
  }

  /**
   * Path to the game.
   *
   * @param string $path - Sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathGame(string $path = ''): string {
    static $game;
    if (!isset($game)) {
      $game = $_ENV['PATH_GAME'] ?? Framework::BAROTRAUMA_APP_NAME;
    }
    return static::pathPrepare("$game/$path");
  }

  /**
   * Path to the mods from workshop.
   *
   * @param string $path - Sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathWorkshop(string $path = ''): string {
    static $workshop;
    if (!isset($workshop)) {
      $workshop = $_ENV['PATH_WORKSHOP'] ?? 'WorkShop';
    }
    return static::pathPrepare("$workshop/$path");
  }

  /**
   * Path to the source files of mods to compile.
   *
   * @param string $path - Sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathInput(string $path = ''): string {
    static $input;
    if (!isset($input)) {
      $input = $_ENV['PATH_INPUT'] ?? 'ModSources';
    }
    return static::pathPrepare("$input/$path");
  }

  /**
   * Path to export generated mod.
   *
   * @param string $path - Sub-path to attach (optional).
   *
   * @return string
   */
  public static function pathOutput(string $path = ''): string {
    static $output;
    if (!isset($output)) {
      $output = $_ENV['PATH_OUTPUT'] ?? 'LocalMods';
    }
    return static::pathPrepare("$output/$path");
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
      $pathParts = explode('/', $path);
      if (count($pathParts) > 1) {
        array_pop($pathParts);
        $path = implode('/', $pathParts);
      }
    }
    // Create directory.
    $status = is_dir($path) && !is_link($path);
    if (!$status) {
      $status = mkdir($path, 0777, TRUE);
    }
    return $status;
  }

  /**
   * Removes directory and it's content.
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
          if (is_dir($full) && !is_link($full)) {
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