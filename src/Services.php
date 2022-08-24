<?php

/**
 * @file
 * Container for services and variables which might help when we parse game
 *   source files.
 */

namespace Barotraumix\Generator;

use Symfony\Component\Yaml\Yaml;

/**
 * Class definition.
 */
class Services {

  /**
   * @var Core - Core service.
   */
  protected Core $core;

  /**
   * @var int - Application ID.
   */
  protected int $appId;

  /**
   * @var int - Build ID of the application.
   */
  protected int $buildId;

  /**
   * @var array - Mapping for tags with incorrect tag name case.
   */
  protected array $tagsMapping = [];

  /**
   * Class constructor.
   */
  public function __construct(int $appId, int $buildId, Core $core) {
    $this->appId = $appId;
    $this->buildId = $buildId;
    $this->core = $core;
    // Prepare tag mapping.
    $this->tagsMapping = Yaml::parseFile('mapping.tags.yml');
  }

  /**
   * Returns application ID.
   *
   * @return int
   */
  public function appId(): int {
    return $this->appId;
  }

  /**
   * Returns build id of the application.
   *
   * @return int
   */
  public function buildId(): int {
    return $this->buildId;
  }

  /**
   * Check if current application is Barotrauma game.
   *
   * @return bool
   */
  public function isGame(): bool {
    return Core::BAROTRAUMA_APP_ID == $this->appId();
  }

  /**
   * Check if current application is Barotrauma mod.
   *
   * @return bool
   */
  public function isMod(): bool {
    return !$this->isGame();
  }

  /**
   * Method to normalize tag name.
   *
   * @param string $name - XML tag name.
   *
   * @return string
   */
  public function normalizeTag(string $name) {
    // Look for appropriate name in mapping.
    if (isset($this->tagsMapping[$name])) {
      $name = $this->tagsMapping[$name];
    }
    // Return normalized value.
    return $name;
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

}
