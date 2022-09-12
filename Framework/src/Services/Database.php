<?php

/**
 * @file
 * Class to store all objects used in process of mod generation.
 */

namespace Barotraumix\Framework\Services;

use Barotraumix\Framework\Entity\Property\NestedArray;
use Barotraumix\Framework\Entity\Property\ID;
use Barotraumix\Framework\Compiler\Context;
use Symfony\Component\Yaml\Yaml;

/**
 * Class definition.
 */
class Database {

  /**
   * Reference to the context of current mod.
   */
  const CONTEXT = 'SELF';

  /**
   * Use ID.
   */
  use ID;

  /**
   * @var array<Context> - Storage for contexts which are available for current
   *   DB.
   */
  protected array $storage = [];

  /**
   * @var Context - Active context.
   */
  protected Context $context;

  /**
   * @var array - Primary mod file data.
   */
  protected array $modData;

  /**
   * @var array - Global variables storage.
   */
  protected array $variables = [];

  /**
   * @var array - Local variables storage.
   */
  protected array $variablesLocal = [];

  /**
   * Class constructor.
   *
   * @param string $mod - Owner mod name.
   */
  public function __construct(string $mod) {
    $this->setID($mod);
    $filePath = API::pathInput(API::MOD_FILE, $mod);
    $this->modData = $this->prepareModData(Yaml::parseFile($filePath));
  }

  /**
   * Method to get mod source data.
   *
   * @return array
   */
  public function modData(): array {
    return $this->modData;
  }

  /**
   * Method to get applications order (and a list).
   *
   * @param string|bool|null $application - May be a string or NULL.
   * NULL - Return all applications in order used for database import phase.
   * string - Single application name to return its ID.
   *
   * @return array|int|NULL
   */
  public function applications(string|bool $application = NULL): array|int|NULL {
    // Return single value if necessary.
    if (is_string($application)) {
      return $this->modData['workshop'][$application] ?? NULL;
    }
    // Return normal list otherwise.
    return array_reverse($this->modData['workshop'], TRUE);
  }

  /**
   * Method to return list of available context names except active one.
   *
   * @param string|null $appID - Application ID will get converted to context name.
   *
   * @return array|string|NULL
   */
  public function contextNames(string $appID = NULL): array|string|NULL {
    $names = [];
    $applications = array_flip($this->applications());
    foreach (array_keys($this->storage) as $id) {
      if (isset($applications[$id])) {
        $names[$id] = $applications[$id];
      }
    }
    // In case if we should convert application ID to application name.
    if (isset($appID)) {
      return $names[$appID] ?? NULL;
    }
    return $names;
  }

  /**
   * Adds a context to database.
   *
   * Added context will be available during mod compilation.
   *
   * @param Context $context - Context object.
   *
   * @return void
   */
  public function contextAdd(Context $context): void {
    if (!$context->hasID()) {
      API::error('Unable to add context without ID to database.');
    }
    $this->storage[$context->id()] = $context;
  }

  /**
   * Method to get specific context.
   *
   * @param string|NULL $name - Context name to get.
   *  If name is not added - active context will be used.
   *
   * @return Context|array|NULL
   */
  public function context(string $name = NULL): Context|array|NULL {
    $name = isset($name) ? $this->applications($name) : NULL;
    if (isset($name)) {
      return $this->storage[$name] ?? NULL;
    }
    // Creates active context.
    if (!isset($this->context)) {
      $this->createActiveContext();
    }
    return $this->context;
  }

  /**
   * Method to add single variable to global or local scope.
   *
   * @param string|array $keys - Array of keys as path to variable (or string).
   * @param mixed $variable - Array of variables to add. Might be a single
   *   string.
   * @param bool $isGlobal - Indicates if scope is global.
   *
   * @return void
   */
  public function variableAdd(string|array $keys, mixed $variable, bool $isGlobal = FALSE): void {
    if (is_scalar($keys)) {
      $keys = [$keys];
    }
    $key = $isGlobal ? 'variables' : 'variablesLocal';
    NestedArray::setValue($this->$key, $keys, $variable);
  }

  /**
   * Method to add array with variables to global or local scope.
   *
   * @param array|string $variables - Array of variables to add. Might be a
   *   single string.
   * @param bool $isGlobal - Indicates if scope is global.
   *
   * @return void
   */
  public function variableAddMultiple(array|string $variables, bool $isGlobal = FALSE): void {
    $key = $isGlobal ? 'variables' : 'variablesLocal';
    $source = $this->$key;
    $destination = NestedArray::mergeDeep($source, $variables);
    $this->$key = $destination;
  }

  /**
   * Return requested variable from local or global scope.
   *
   * @param string|array $keys - Settings key.
   * @param bool $global - Indicates that we should pick a global variable.
   *
   * @return mixed
   */
  public function variableGet(string|array $keys, bool $global = FALSE): mixed {
    $key_exists = FALSE;
    // Should be an array in any case.
    if (is_scalar($keys)) {
      $keys = [$keys];
    }
    // Try local variables first.
    if (!$global) {
      $variable = NestedArray::getValue($this->variablesLocal, $keys, $key_exists);
      // Return if key exists.
      if ($key_exists) {
        return $variable;
      }
    }
    // Attempt to use global scope.
    return NestedArray::getValue($this->variables, $keys);
  }

  /**
   * Wipes local variables.
   *
   * Need to be used before every new included file.
   *
   * @return void
   */
  public function variablesResetLocal(): void {
    $this->variablesLocal = [];
  }

  /**
   * Method to query the database.
   *
   * @param array|NULL $query - Query array. NULL to query EVERYTHING.
   * @param string|NULL $contextName - Name of the context to query.
   *  NULL - Will query an active context.
   *
   * @return mixed
   */
  public function query(array $query = NULL, string $contextName = NULL): Context {
    $context = $this->context($contextName);
    if (!$context) {
      API::error("Context '$contextName' doesn't exist in database: " . $this->id());
    }
    return $context->query($query);
  }

  /**
   * Method to create active context.
   *
   * Active context - is a combination of all existing contexts.
   */
  protected function createActiveContext(): void {
    $this->context = new Context(static::CONTEXT);
    foreach (array_reverse($this->contextNames(), TRUE) as $contextName) {
      $context = $this->context($contextName);
      /** @var \Barotraumix\Framework\Entity\RootEntity $entity */
      foreach ($context as $entity) {
        if (!$this->context->offsetExists($entity->id())) {
          $this->context[] = $entity->clone();
        }
      }
    }
  }

  /**
   * Prepares array with mod data.
   *
   * @param array $data - Array containing mod data.
   *
   * @return array
   */
  protected function prepareModData(array $data): array {
    if (empty($data['workshop'])) {
      $data['workshop'] = [];
    }
    // Force game to be in the end of the file.
    unset($data['workshop'][API::APP_NAME]);
    $data['workshop'][API::APP_NAME] = API::APP_ID;
    return $data;
  }

}