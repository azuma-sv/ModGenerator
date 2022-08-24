<?php
/**
 * @file
 * Class to handle settings stored in YAML files.
 */

namespace Barotraumix\Generator;

use Symfony\Component\Yaml\Yaml;

/**
 * Class definition.
 */
class Settings {

  /**
   * @var string - Path to file to store settings.
   */
  protected string $file;

  /**
   * @var string - Sorting options.
   */
  protected mixed $sort;

  /**
   * @var array - Array of settings.
   */
  protected array $settings = [];

  /**
   * Class constructor.
   *
   * @param string $file - Path to file which will store settings.
   * @param mixed $sort - Sorting parameter.
   */
  public function __construct(string $file, mixed $sort = NULL) {
    $this->file = $file;
    $this->sort = $sort;
    $data = Yaml::parseFile($file);
    $this->settings = is_array($data) ? $data : [];
  }

  /**
   * Method to store some data into settings.
   *
   * @param string $key - Settings key.
   * @param mixed $value - Settings value.
   *
   * @return void
   */
  public function set(string $key, mixed $value):void {
    // Save value to settings array.
    $this->settings[$key] = $value;
  }

  /**
   * Ensure that setting key exists in array.
   *
   * @param string $key - Settings key.
   *
   * @return bool
   */
  public function has(string $key):bool {
    return array_key_exists($key, $this->settings);
  }

  /**
   * Return value from settings array.
   *
   * @param string $key - Settings key.
   *
   * @return mixed
   */
  public function get(string $key):mixed {
    return $this->settings[$key];
  }

  /**
   * Method to delete variable from settings.
   *
   * @param string $key - Key to delete.
   *
   * @return void
   */
  public function delete(string $key): void {
    unset($this->settings[$key]);
  }

  /**
   * Return raw array with settings.
   *
   * @return array
   */
  public function settings(): array {
    return $this->settings;
  }

  /**
   * Method to save current array of settings to file.
   *
   * @return void
   */
  public function save():void {
    // Default sort.
    // @todo: Implement more ways to sort.
    if (!isset($this->sort)) {
      ksort($this->settings, SORT_STRING | SORT_FLAG_CASE);
    }
    // Save data to file.
    file_put_contents($this->file, Yaml::dump($this->settings));
  }

}