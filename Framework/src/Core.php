<?php
/**
 * @file
 * Core service class definition.
 */

namespace Barotraumix\Framework;

use Barotraumix\Framework\Compiler\Compiler;
use Barotraumix\Framework\Compiler\Context;
use Barotraumix\Framework\Services\API;
use Barotraumix\Framework\Services\Database;
use Barotraumix\Framework\Services\Scanner;
use Barotraumix\Framework\Services\Services;
use Barotraumix\Framework\Services\SteamCMD;

/**
 * Class definition.
 */
class Core {

  /**
   * @var Core - Core service storage.
   *
   * Helps to avoid complex dependency injection mechanism.
   */
  protected static Core $core;

  /**
   * @var Services - Services wrapper.
   */
  protected Services $services;

  /**
   * Ger framework core service.
   *
   * Helps to avoid complex dependency injection mechanism.
   *
   * @return Core
   */
  public static function get(): Core {
    if (!isset(static::$core)) {
      static::$core = new Core();
    }
    return static::$core;
  }

  /**
   * Get core services' wrapper.
   *
   * Helps to avoid complex dependency injection mechanism.
   *
   * @return Services
   */
  public static function services(): Services {
    return static::get()->services;
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    // Services wrapper.
    $this->services = new Services();
  }

  /**
   * Method to execute framework in console mode.
   *
   * @todo: Implement --help.
   *
   * @return void
   */
  public function console(): void {
    $mods = $this->listMods();
    // Check if we should compile only specific mod.
    foreach ($GLOBALS['argv'] as $value) {
      if (str_starts_with(trim($value), '--mod=')) {
        $arguments = explode('=', $value);
        $mod = $arguments[1];
        if (!in_array($mod, $mods)) {
          API::error("Unable to locate mod: '$mod' ($value)");
        }
        $mods = [$mod];
        break;
      }
    }
    // Validate game sources.
    if (in_array('--force-update', $GLOBALS['argv']) || !$this->scanner()->isValid()) {
      // Install Barotrauma.
      $steam = new SteamCMD();
      $steam->install();
    }
    // Compile every mod.
    foreach ($mods as $mod) {
      $this->compile($mod);
    }
  }

  /**
   * Execute mod compilation.
   *
   * @param string $mod - Mod name to compile.
   *
   * @return void
   */
  public function compile(string $mod): void {
    $this->importDatabase($mod);
    $this->compiler($mod)->doCompile();
  }

  /**
   * Scans and sends to database everything what has been requested by a mod.
   *
   * @param string $mod - Mod name to import.
   *
   * @return void
   */
  public function importDatabase(string $mod): void {
    $database = $this->database($mod);
    foreach ($database->applications() as $id) {
      $context = $this->scanner($id)->scanContext();
      $database->contextAdd($context);
    }
  }

  /**
   * Method to get database for specific mod.
   *
   * @param string $mod - Mod name.
   *
   * @return Database
   */
  public function database(string $mod): Database {
    static $databases = [];
    if (!isset($databases[$mod])) {
      $databases[$mod] = new Database($mod);
    }
    return $databases[$mod];
  }

  /**
   * Get scanner object which will allow us to scan folder of specific app by
   * build id.
   *
   * @param string|int $id - Application ID to scan.
   *
   * @return Scanner
   */
  public function scanner(string|int $id = API::APP_ID): Scanner {
    static $scanners = [];
    if (!isset($scanners[$id])) {
      $scanners[$id] = new Scanner($id);
    }
    return $scanners[$id];
  }

  /**
   * Method to get mod compiler service.
   *
   * @param string $mod - Mod name.
   *
   * @return \Barotraumix\Framework\Compiler\Compiler
   */
  public function compiler(string $mod): Compiler {
    static $compilers;
    // Create builder service.
    if (!isset($compilers[$mod])) {
      $compilers[$mod] = new Compiler($mod);
    }
    return $compilers[$mod];
  }

  /**
   * Method to create or get context from global storage.
   *
   * If $id is omitted - will create unregistered context which is not stored anywhere.
   *
   * @param string $id - Context ID to get or create.
   *
   * @return Context
   */
  public static function context(string $id): Context {
    static $contexts;
    if (isset($contexts[$id])) {
      return $contexts[$id];
    }
    $contexts[$id] = new Context($id);
    return $contexts[$id];
  }

  /**
   * List all mods which are available for compilation.
   *
   * @return array
   */
  public function listMods(): array {
    static $mods;
    // Scan for mods.
    if (!isset($mods)) {
      $mods = [];
      foreach (scandir(API::pathInput()) as $dir) {
        // Skip default objects.
        if ($dir == '.' || $dir == '..') {
          continue;
        }
        // Validate directory.
        if (is_dir(API::pathInput($dir)) && file_exists(API::pathInput(API::MOD_FILE, $dir))) {
          $mods[] = $dir;
        }
      }
    }
    return $mods;
  }

}