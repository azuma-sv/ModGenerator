<?php
/**
 * @file
 * Core service class definition.
 */

namespace Barotraumix\Framework;

use Barotraumix\Framework\Compiler\CompilerInterface;
use Barotraumix\Framework\Services\Framework;
use Barotraumix\Framework\Services\Services;
use Barotraumix\Framework\Services\Settings;

/**
 * Class definition.
 */
class Core {

  /**
   * @var Core - Core service storage.
   *
   * Same as $this, but accessible via static variable.
   * Helps to avoid complex dependency injection mechanism.
   */
  public static Core $get;

  /**
   * @var Services - Services wrapper.
   *
   * Static variable which helps to avoid complex dependency injection mechanism.
   */
  public static Services $services;

  /**
   * @var string $compilerClass - Compiler class to use for this framework.
   */
  protected string $compilerClass;

  /**
   * Static way to create this service.
   *
   * Helps to avoid complex dependency injection mechanism.
   *
   * @return Core
   */
  public static function create(): Core {
    static::$get = new Core();
    static::$services = new Services();
    return static::$get;
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    // Assign parser class. At current moment we have only one.
    // New Parser might appear when Barotrauma will make significant
    // changes in their files and their structure.
    $this->compilerClass = '\Barotraumix\Framework\Compiler\CompilerClassic';
  }

  /**
   * Way to grab services' wrapper without complex dependency injection.
   *
   * @return Services
   */
  public static function services(): Services {
    return static::$services;
  }

  /**
   * Method to initialize generator.
   *
   * @todo: Implement --help.
   *
   * @return void
   */
  public function initFramework(): void {
    // Find our mod.
    foreach ($GLOBALS['argv'] as $value) {
      if (str_starts_with(trim($value), '--mod=')) {
        $arguments = explode('=', $value);
        $mod = $arguments[1];
      }
    }
    // Validate if mod exists.
    if (!isset($mod)) {
      Framework::error('Incorrect mod name.');
      exit();
    }
    // Try to find folder.
    $modPath = Framework::pathInput($mod);
    if (!file_exists($modPath)) {
      Framework::error('Unable to find mod folder.');
    }
    // Try to find mod main file.
    $localModFile = Framework::PRIMARY_MOD_FILE;
    $modFile = $modPath . '/' . $localModFile;
    if (!file_exists($modFile)) {
      Framework::error('Unable to find mod main file.');
    }
    // Keep mod data.
    $modData = [];
    $fileSettings = new Settings($modFile);
    $modData[$modFile] = $fileSettings->array();
    // @todo: Create ability to include files in specific order.
    foreach (scandir($modPath) as $file) {
      // Scan only YAML files.
      if ($file != $localModFile && str_ends_with($file, '.yml')) {
        $fileSettings = new Settings($modPath . '/' . $file);
        $modData[$modPath . '/' . $file] = $fileSettings->array();
      }
    }
    Services::$database->modSources($modData);
  }

  /**
   * Method to update the game and obtain build ID for mod generator.
   *
   * @todo: Refactor to use buildId properly.
   *
   * @return void
   */
  public function steamUpdateGameAndMods(): void {
    // Check each application.
    $apps = Services::$database->applications();
    foreach ($apps as $app => $id) {
      // Try to get build ID from temporary storage.
      $buildId = Services::buildId($id);
      // @todo: Remove once integration with mods is implemented.
      if ($app != Framework::BAROTRAUMA_APP_NAME) {
        continue;
      }
      // Force update.
      if (in_array('--force-update', $GLOBALS['argv']) || !isset($buildId)) {
        $buildId = Services::$steam->appInstall($id);
        // Save build ID value.
        if (!empty($buildId)) {
          // Update barotrauma build id.

          Framework::$settings->set(['applications', $app, 'buildId'], $buildId);
          Framework::$settings->save();
        }
        else {
          Framework::error('Unable to update app: ' . $app);
        }
      }
    }
  }

  /**
   * Scans and sends to bank everything what has been requested by local mod.
   *
   * @return void
   */
  public function importSourcesToDatabase(): void {
    // Scanner will automatically send all the data to bank.
    $apps = Services::$database->applications();
    foreach ($apps as $app => $id) {
      // @todo: Remove once mods are integrated.
      if ($app !== Framework::BAROTRAUMA_APP_NAME) {
        continue;
      }
      // Initialize generator.
      $scanner = $this->scan($app);
      // Scan only items at the moment.
      $scanner->items();
    }
  }

  /**
   * Execute mod generation.
   *
   * @return void
   */
  public function compile(): void {
    $this->compiler()->doCompile();
  }

  /**
   * Method to get mod compiler service.
   *
   * @return CompilerInterface
   */
  public function compiler(): CompilerInterface {
    static $compiler;
    // Create builder service.
    if (!isset($compiler)) {
      $compiler = new $this->compilerClass();
    }
    return $compiler;
  }

  /**
   * Get scanner object which will allow us to scan folder of specific app by
   * build id.
   *
   * @param string $application - Application name to scan.
   *
   * @return Scanner
   */
  public function scan(string $application = Framework::BAROTRAUMA_APP_NAME): Scanner {
    // Use static cache.
    static $scanners = [];
    // Attempt to get scanner from cache.
    if (isset($scanners[$application])) {
      return $scanners[$application];
    }
    // Create new scanner.
    $scanner = new Scanner($application);
    $scanners[$application] = $scanner;
    return $scanner;
  }

}