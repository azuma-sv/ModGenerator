<?php
/**
 * @file
 * Core service class definition.
 */

namespace Barotraumix\Generator;

use Barotraumix\Generator\Compiler\CompilerClassic;
use Barotraumix\Generator\Compiler\CompilerInterface;
use Barotraumix\Generator\Services\Database;
use Barotraumix\Generator\Services\Framework;
use Barotraumix\Generator\Services\Services;
use Barotraumix\Generator\Services\Settings;

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
   * @var array Array with scanners.
   */
  protected array $scanners;

  /**
   * Mod builder service.
   */
  protected CompilerInterface $builder;

  /**
   * Storages for different mods.
   */
  protected Database $bank;

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
    $modPath = $this->pathInput($mod);
    if (!file_exists($modPath)) {
      Framework::error('Unable to find mod folder.');
    }
    // Try to find mod main file.
    $localModFile = 'filelist.mod.yml';
    $modFile = $modPath . '/' . $localModFile;
    if (!file_exists($modFile)) {
      Framework::error('Unable to find mod main file.');
    }
    // Keep mod data.
    $modData = [];
    $fileSettings = new Settings($modFile);
    $modData[$modFile] = $fileSettings->settings();
    foreach (scandir($modPath) as $file) {
      // Scan only YAML files.
      if ($file != $localModFile && str_ends_with($file, '.yml')) {
        $fileSettings = new Settings($modPath . '/' . $file);
        $modData[$modPath . '/' . $file] = $fileSettings->settings();
      }
    }
    $this->bank()->modData($modData);
  }

  /**
   * Method to update the game and obtain build ID for mod generator.
   *
   * @return void
   */
  public function steamUpdateGameAndMods(): void {
    // Check each application.
    $apps = $this->bank()->applicationsOrder();
    foreach ($apps as $app => $ids) {
      $buildId = NULL;
      // Try to get build ID from settings.
      if (!empty($ids['buildId'])) {
        $buildId = $ids['buildId'];
      }

      // Force update.
      if (in_array('--force-update', $GLOBALS['argv']) || !isset($buildId)) {
        $buildId = $this->steam->appInstall($ids['appId']);
        // Save build ID value.
        if (!empty($buildId)) {
          // Update barotrauma build id.
          $this->settings->set(['applications', $app, 'buildId'], $buildId);
          $this->settings->save();
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
    $apps = $this->bank()->applicationsOrder();
    foreach ($apps as $app => $ids) {
      // @todo: Remove once mods are integrated.
      if ($app !== Core::BAROTRAUMA_APP_NAME) {
        continue;
      }
      // Initialize generator.
      $scanner = $this->scan($ids['appId'], $ids['buildId']);
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
    $this->builder()->doBuild();
  }

  /**
   * Method to mod builder service.
   *
   * @return CompilerInterface
   */
  public function builder():CompilerInterface {
    // Create builder service.
    if (!isset($this->builder)) {
      $this->builder = new CompilerClassic($this->pathOutput(), $this->bank());
    }
    return $this->builder;
  }

  /**
   * Get scanner object which will allow us to scan folder of specific app by
   * build id.
   *
   * @param int $appId - ID of the app in Steam.
   * @param int $buildId - Build id of the app in Steam.
   *
   * @return Scanner
   */
  public function scan(int $appId, int $buildId): Scanner {
    // Use static cache.
    static $scanners = [];
    // Attempt to get scanner from cache.
    if (isset($scanners[$appId][$buildId])) {
      return $scanners[$appId][$buildId];
    }
    // Create new scanner.
    $scanner = new Scanner(new Services($appId, $buildId, $this));
    $scanners[$appId][$buildId] = $scanner;
    return $scanner;
  }

}