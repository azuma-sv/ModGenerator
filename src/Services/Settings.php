<?php
/**
 * @file
 * Class to handle settings stored in YAML files.
 */

namespace Barotraumix\Generator\Services;

use Barotraumix\Generator\Entity\Property\NestedArray;
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
   * @param string $file - Path to file (relative to project root) with settings.
   * @param mixed $sort - Sorting parameter.
   */
  public function __construct(string $file, mixed $sort = NULL) {
    // @todo: Refactor sorting behavior.
    $this->sort = $sort;
    $this->file = BASE_PATH . '/' . $file;
    $data = Yaml::parseFile($this->file);
    $this->settings = is_array($data) ? $data : [];
  }

  /**
   * Method to store some data into settings.
   *
   * @param string|array $keys - Settings key.
   * @param mixed $value - Settings value.
   *
   * @return void
   */
  public function set(string|array $keys, mixed $value): void {
    // Should be an array in any case.
    if (is_scalar($keys)) {
      $keys = [$keys];
    }
    // Set value.
    NestedArray::setValue($this->settings, $keys, $value);
  }

  /**
   * Ensure that setting key exists in array.
   *
   * @param string|array $keys - Settings key.
   *
   * @return bool
   */
  public function has(string|array $keys): bool {
    // Should be an array in any case.
    if (is_scalar($keys)) {
      $keys = [$keys];
    }
    return NestedArray::keyExists($this->settings, $keys);
  }

  /**
   * Return value from settings array.
   *
   * @param string|array $keys - Settings key.
   *
   * @return mixed
   */
  public function get(string|array $keys): mixed {
    // Should be an array in any case.
    if (is_scalar($keys)) {
      $keys = [$keys];
    }
    return NestedArray::getValue($this->settings, $keys);
  }

  /**
   * Method to unset variable from settings.
   *
   * @param string|array $keys - Key to delete.
   *
   * @return void
   */
  public function unset(string|array $keys): void {
    // Should be an array in any case.
    if (is_scalar($keys)) {
      $keys = [$keys];
    }
    NestedArray::unsetValue($this->settings, $keys);
  }

  /**
   * Return raw array with settings (without reference).
   *
   * @return array
   */
  public function array(): array {
    return $this->settings;
  }

  /**
   * Method to save current array of settings to file.
   *
   * @return void
   */
  public function save(): void {
    // Default sort.
    if (!isset($this->sort)) {
      ksort($this->settings, SORT_STRING | SORT_FLAG_CASE);
    }
    // Save data to file.
    file_put_contents($this->file, Yaml::dump($this->settings));
  }

}