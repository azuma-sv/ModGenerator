<?php

/**
 * @file
 * Class to store all objects used in process of mod generation.
 */

namespace Barotraumix\Generator\Services;

use Barotraumix\Generator\Compiler\CompilerInterface;
use Barotraumix\Generator\Core;
use Barotraumix\Generator\Entity\BaroEntity;
use Barotraumix\Generator\Entity\Property\NestedArray;

/**
 * Class definition.
 */
class Database {

  /**
   * @var Core - Core service.
   */
  protected Core $core;

  /**
   * @var array - Object storage.
   */
  protected array $storage = [
    'contexts' => [],
    'variables' => [
      'global' => [],
      CompilerInterface::CONTEXT_SELF => [],
    ],
  ];

  /**
   * Method to store mod data.
   *
   * @param array|NULL $modData - Data of parsed mod source.
   *
   * @return array
   */
  public function modData(array $modData = NULL): array {
    static $data = [];
    if (isset($modData)) {
      // Process mod's order.
      reset($modData);
      $primary = key($modData);
      if (empty($modData[$primary]['order'])) {
        $modData[$primary]['order'] = [];
      }
      // Force game to be in the beginning of the file.
      $modData[$primary]['order'] = array_unique($modData[$primary]['order'] + [Core::BAROTRAUMA_APP_NAME => Core::BAROTRAUMA_APP_NAME]);

      // Set data.
      $data = $modData;
    }
    return $data;
  }

  /**
   * Method to get applications order (and a list).
   *
   * @return array
   */
  public function applicationsOrder(): array {
    // Prepare order array.
    $order = [];
    $data = $this->modData();
    $primary = reset($data);
    foreach ($primary['order'] as $app) {
      $ids = $this->core->settings()->get(['applications', $app]);
      if (empty($ids['appId'])) {
        Core::error('Unable to find application ID for app: "' . $app . '". Check key "applications" in settings.yml file.');
      }
      $order[$app] = $ids;
    }
    if (empty($order)) {
      Core::error('No applications to process. Check key "applications" in settings.yml file.');
    }
    return $order;
  }

  /**
   * Add entity to specific context (overrides existing value).
   *
   * @param BaroEntity $entity - Entity to add.
   * @param string $context - Context name (machine name of application).
   *
   * @return void
   */
  public function addEntity(BaroEntity $entity, string $context): void {
    // Create context storage.
    if (!isset($this->storage['contexts'][$context])) {
      $this->storage['contexts'][$context] = [];
    }
    $context = &$this->storage['contexts'][$context];
    // Process normal entity.
    if ($entity->isEntity()) {
      // Notify user about this action.
      if (isset($context['entities'][$entity->id()])) {
        $entityOld = $context['entities'][$entity->id()];
        Core::notice('Entity of type "' . $entityOld->type() . '" with ID "' . $entityOld->id() . '" has been replaced by entity of type "' . $entity->type() . '" with ID "' . $entity->id() . '"');
      }
      // Set new entity.
      $context['entities'][$entity->id()] = $entity;
    }
    // Process entity tags.
    if ($entity->hasAttribute('tags')) {
      $tags = $entity->attribute('tags');
      $tags = explode(',', $tags);
      foreach ($tags as $tag) {
        if (!empty($tag)) {
          $context['entity_metadata']['tags'][$tag] = $tag;
        }
      }
    }
    // Process entity categories.
    if ($entity->hasAttribute('category')) {
      $categories = $entity->attribute('category');
      $categories = explode(',', $categories);
      foreach ($categories as $category) {
        if (!empty($category)) {
          $context['entity_metadata']['categories'][$category] = $category;
        }
      }
    }
    // Process sub element.
    $name = $entity->name();
    $parent = $entity->parent();
    if (isset($parent)) {
      $parent = $parent->name();
    }
    else {
      $parent = 'ROOT';
    }
    // Set tag data.
    if (!isset($context['tags'][$name])) {
      $context['tags'][$name] = [];
      $context['tags'][$name]['count'] = 0;
      $context['tags'][$name]['parents'] = [];
    }
    $tag = &$context['tags'][$name];
    $tag['count']++;
    $tag['parents'][$parent] = $parent;
    // Process children.
    $attributes = $entity->attributes();
    foreach ($attributes as $key => $value) {
      // Set attribute data.
      if (!isset($context['attributes'][$key])) {
        $context['attributes'][$key] = [];
        $context['attributes'][$key]['count'] = 0;
        $context['attributes'][$key]['parents'] = [];
      }
      $attribute = &$context['attributes'][$key];
      $attribute['count']++;
      $attribute['parents'][$name] = $name;
    }
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
  public function addVariable(string|array $keys, mixed $variable, bool $isGlobal = FALSE): void {
    if (is_scalar($keys)) {
      $keys = [$keys];
    }
    $key = $isGlobal ? 'global' : CompilerInterface::CONTEXT_SELF;
    NestedArray::setValue($this->storage['variables'][$key], $keys, $variable);
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
  public function addVariables(array|string $variables, bool $isGlobal = FALSE): void {
    $key = $isGlobal ? 'global' : CompilerInterface::CONTEXT_SELF;
    $source = $this->storage['variables'][$key];
    $destination = NestedArray::mergeDeep($source, $variables);
    $this->storage['variables'][$key] = $destination;
  }

  /**
   * Return requested variable from local or global scope.
   *
   * @param string|array $keys - Settings key.
   *
   * @return mixed
   */
  public function variable(string|array $keys): mixed {
    $key_exists = FALSE;
    // Should be an array in any case.
    if (is_scalar($keys)) {
      $keys = [$keys];
    }
    $variables = $this->storage['variables'];
    $variable = NestedArray::getValue($variables[CompilerInterface::CONTEXT_SELF], $keys, $key_exists);
    // Return if key exists.
    if ($key_exists) {
      return $variable;
    }
    // Attempt to use global scope.
    return NestedArray::getValue($variables['global'], $keys);
  }

  /**
   * Method to query for entities in a context.
   *
   * @param array $conditions - Search conditions.
   * @param array|string|NULL $context - Array with context scope, or name of
   *   the context, or NULL to use EVERYTHING.
   * @param bool $clone - Indicates if we should clone entity to our new
   *   context. Does nothing if variable $context contains a list of
   *   BaroEntities.
   *
   * @return array|string|NULL
   */
  public function query(array $conditions, array|string $context = NULL, bool $clone = FALSE): array|string|NULL {
    // Init variables.
    $collection = [];
    $collection[0] = [];
    $depth = 0;
    $condition = $conditions;
    $destination = NULL;
    // Walk through all conditions.
    while (TRUE) {
      // Apply filter conditions.
      if (empty($depth)) {
        if (!isset($context) || is_scalar(reset($context))) {
          // Filter entire context scope for first time.
          $filtered = $this->entityFilterContext($condition, $context, $clone);
        }
        else {
          // Filter listed entities.
          $filtered = $this->entityFilter($context, $condition);
        }
        if (!empty($filtered)) {
          // Store every entity.
          /** @var BaroEntity $entity */
          foreach ($filtered as $entity) {
            $collection[$depth][$this->getRootID($entity)][] = $entity;
          }
        }
      }
      else {
        foreach ($collection[$depth - 1] as $scope) {
          /** @var BaroEntity $entity */
          foreach ($scope as $entity) {
            if ($entity->hasChildren()) {
              // Apply filter for every entity separately.
              $filtered = $this->entityFilter($entity->children(), $condition);
              if (!empty($filtered)) {
                // Store every entity.
                /** @var BaroEntity $entity */
                foreach ($filtered as $entityFiltered) {
                  $collection[$depth][$this->getRootID($entity)][] = $entityFiltered;
                }
              }
            }
          }
        }
      }
      // Skip filtration if we have no elements in context.
      if (empty($collection[$depth])) {
        return [];
      }
      // Filter previous steps.
      if (!empty($depth)) {
        $step = $depth - 1;
        $break = FALSE;
        while (!$break) {
          // Clean previous steps.
          foreach ($collection[$step] as $id => $scope) {
            if (!isset($collection[$depth][$id])) {
              unset($collection[$step][$id]);
            }
          }
          // This is a very weird case which needs attention.
          if (count($collection[$depth]) !== count($collection[$step])) {
            // @todo: Remove in future.
            Core::error('Incorrect query cleaning method. Needs admin attention.');
          }
          // Break cleaning.
          if (empty($step)) {
            $break = TRUE;
          }
          // Move to beginning.
          $step--;
        }
      }
      // Check next condition.
      if (empty($condition['child'])) {
        // Prepare results and return them.
        $filtered = isset($destination) ? $collection[$destination] : $collection[$depth];
        $collection = [];
        foreach ($filtered as $scope) {
          foreach ($scope as $entity) {
            $collection[] = $entity;
          }
        }
        return $collection;
      }
      // Switch to another step.
      $operator = $condition['child_operator'];
      $condition = $condition['child'];
      $depth++;
      // In case if we should return objects from previous step.
      if ($operator == '<') {
        $destination = $depth - 1;
      }
      // In case if we have a return statement.
      if ($operator == '>') {
        // No entities found.
        if (empty($collection[$depth - 1])) {
          return NULL;
        }
        // Prepare results.
        $results = [];
        foreach ($collection[$depth - 1] as $scope) {
          /** @var BaroEntity $entity */
          foreach ($scope as $entity) {
            // Return attribute if possible.
            $attribute = $condition['entity'];
            if ($entity->hasAttribute($attribute)) {
              $results[] = $entity->attribute($attribute);
            }
          }
        }
        return $results;
      }
    }
  }

  /**
   * Filter list of entities by a specific condition.
   *
   * @param array $list - Array of entities.
   * @param array $condition - Condition array.
   *
   * @return array
   */
  public function entityFilter(array $list, array $condition): array {
    $results = [];
    $type = $condition['entity'];
    // Check each item.
    /** @var BaroEntity $entity */
    foreach ($list as $entity) {
      // Skip entities which do not match to our type.
      if (!empty($type) && $entity->type() != $type) {
        continue;
      }
      // Check attribute conditions.
      if (!$this->entityMatch($entity, $condition['attributes'])) {
        continue;
      }
      // Add entity.
      $results[] = $entity;
    }
    // Check every nth element from set of matched.
    if (!empty($condition['order']) && !empty($results)) {
      $order = $condition['order'];
      // Process order value (we can use negate value).
      if ($order > 0) {
        $order += -1;
      }
      else {
        $order = count($results) + $order;
      }
      // Validate order existence.
      $keys = array_keys($results);
      if (!isset($keys[$order])) {
        return [];
      }
      else {
        // Limit results to a single element.
        $results = [$results[$keys[$order]]];
      }
    }
    return $results;
  }

  /**
   * Filter list of entities by a specific condition.
   *
   * @param array $condition - Condition array.
   * @param array|string|NULL $contexts - Array with context scope, or name of
   *   the context, or NULL to use EVERYTHING.
   * @param bool $clone - Indicates if we should clone entity to our new
   *   context.
   *
   * @return array
   */
  public function entityFilterContext(array $condition, array|string $contexts = NULL, bool $clone = FALSE): array {
    $entities = [];
    // Provide contexts list by default.
    if (!isset($contexts)) {
      // We need to use context in opposite order.
      $contexts = array_keys([CompilerInterface::CONTEXT_SELF => CompilerInterface::CONTEXT_SELF] + $this->applicationsOrder());
    }
    // Always an array.
    if (is_scalar($contexts)) {
      $contexts = [$contexts];
    }
    // Prepare storage for new entities.
    $storage = &$this->storage['contexts'];
    if (empty($storage[CompilerInterface::CONTEXT_SELF])) {
      $storage[CompilerInterface::CONTEXT_SELF] = [];
    }
    if (empty($storage[CompilerInterface::CONTEXT_SELF]['entities'])) {
      $storage[CompilerInterface::CONTEXT_SELF]['entities'] = [];
    }
    $storageSelf = &$storage[CompilerInterface::CONTEXT_SELF]['entities'];
    foreach ($contexts as $context) {
      // Nothing to search.
      if (empty($storage[$context]) || empty($storage[$context]['entities'])) {
        continue;
      }
      // Filter each entity.
      $filtered = $this->entityFilter($storage[$context]['entities'], $condition);
      if (empty($filtered)) {
        continue;
      }
      /** @var BaroEntity $entity */
      foreach ($filtered as $entity) {
        // Clone entity if needed.
        $filteredEntity = $entity;
        if ($clone && $context != CompilerInterface::CONTEXT_SELF) {
          // Validate if entity has been replaced.
          if (isset($storageSelf[$entity->id()])) {
            $entityOld = $storageSelf[$entity->id()];
            $msg = 'Entity of type "' . $entityOld->type() . '" with ID "' . $entityOld->id() . '" has been replaced by entity of type "';
            $msg .= $entity->type() . '" with ID "' . $entity->id() . '" (in a context: "' . CompilerInterface::CONTEXT_SELF . '")';
            Core::notice($msg);
          }
          // Clone entity and put it into storage.
          $filteredEntity = $entity->clone();
          $storageSelf[$entity->id()] = $filteredEntity;
        }
        $entities[$entity->id()] = $filteredEntity;
      }
    }
    return $entities;
  }

  /**
   * Verify if given entity matches to provided conditions.
   *
   * @param BaroEntity $entity - Entity to check.
   * @param array $conditions - Attribute conditions to apply.
   *
   * @return bool
   */
  public function entityMatch(BaroEntity $entity, array $conditions = []): bool {
    $check = NULL;
    // Check OR conditions.
    foreach ($conditions as $or) {
      // Check AND conditions.
      if (isset($or['and'])) {
        foreach ($or['and'] as $and) {
          // Check condition.
          $condition = $this->entityCondition($entity, $and);
          // Set condition.
          if (!isset($check)) {
            $check = $condition;
          }
          // Apply condition.
          $check = $check && $condition;
        }
      }
      // Check condition.
      $condition = $this->entityCondition($entity, $or);
      // Set condition.
      if (!isset($check)) {
        $check = $condition;
      }
      // Apply condition.
      $check = $check || $condition;
    }
    return !isset($check) ? TRUE : $check;
  }

  /**
   * Check if given entity match to a single condition.
   *
   * @param BaroEntity $entity - Given entity.
   * @param array $condition - Given condition of specific attribute.
   *
   * @return bool
   */
  public function entityCondition(BaroEntity $entity, array $condition): bool {
    // Get condition parameters.
    [$attribute, $comparison, $value] = array_values($condition);
    $needles = is_array($value) ? $value : [$value];
    // Get attribute value. We never use haystack as array for strict comparison operators.
    $haystack = $entity->hasAttribute($attribute) ? $entity->attribute($attribute) : NULL;
    $haystack = in_array($comparison, ['==', '!=', '*=', '^=', '$=']) ? $haystack : explode(',', $haystack);
    // Check in different way for different operator.
    $check = NULL;
    foreach ($needles as $needle) {
      // For details @see Parser::queryAttribute()
      switch ($comparison) {
        // Equality comparison.
        case '+=':
          foreach ($haystack as $item) {
            $compared = $item == $needle;
            $check = isset($check) ? $check && $compared : $compared;
          }
          break;
        case '?=':
          foreach ($haystack as $item) {
            $compared = $item == $needle;
            $check = isset($check) ? $check || $compared : $compared;
          }
          break;
        case '==':
          $check = $haystack == $needle;
          break;

        // Inequality comparison
        case '+!':
          foreach ($haystack as $item) {
            $compared = $item != $needle;
            $check = isset($check) ? $check && $compared : $compared;
          }
          break;
        case '?!':
          foreach ($haystack as $item) {
            $compared = $item != $needle;
            $check = isset($check) ? $check || $compared : $compared;
          }
          break;
        case '!=':
          $check = $haystack != $needle;
          break;

        // "Contain" check.
        case '+*':
          foreach ($haystack as $item) {
            $compared = boolval(mb_strpos($item, $needle));
            $check = isset($check) ? $check && $compared : $compared;
          }
          break;
        case '?*':
          foreach ($haystack as $item) {
            $compared = boolval(mb_strpos($item, $needle));
            $check = isset($check) ? $check || $compared : $compared;
          }
          break;
        case '*=':
          $compared = boolval(mb_strpos($haystack, $needle));
          $check = $compared;
          break;

        // "Starts with" check.
        case '+^':
          foreach ($haystack as $item) {
            $compared = str_starts_with($item, $needle);
            $check = isset($check) ? $check && $compared : $compared;
          }
          break;
        case '?^':
          foreach ($haystack as $item) {
            $compared = str_starts_with($item, $needle);
            $check = isset($check) ? $check || $compared : $compared;
          }
          break;
        case '^=':
          $compared = str_starts_with($haystack, $needle);
          $check = $compared;
          break;

        // "Ends with" check.
        case '+$':
          foreach ($haystack as $item) {
            $compared = str_ends_with($item, $needle);
            $check = isset($check) ? $check && $compared : $compared;
          }
          break;
        case '?$':
          foreach ($haystack as $item) {
            $compared = str_ends_with($item, $needle);
            $check = isset($check) ? $check || $compared : $compared;
          }
          break;
        case '$=':
          $compared = str_ends_with($haystack, $needle);
          $check = $compared;
          break;

        // Looks like some kind of issue, but let's keep default value.
        default:
          $check = FALSE;
      }
    }
    return $check;
  }

  /**
   * Return an array of available entity types.
   *
   * @return array
   */
  public function entityTypes(): array {
    $contexts = array_keys($this->applicationsOrder());
    // Check each layer of context.
    $entityTypes = [];
    foreach ($contexts as $context) {
      $storage = &$this->storage['contexts'][$context];
      if (!empty($storage['entities'])) {
        $entityTypes = array_merge($entityTypes, array_keys($storage['entities']));
      }
    }
    return array_unique($entityTypes);
  }

  /**
   * Return an ID of parent entity.
   *
   * @todo: Remove.
   *
   * @param \Barotraumix\Generator\Entity\BaroEntity $entity
   *
   * @return string
   */
  protected function getRootID(BaroEntity $entity): string {
    $current = $entity;
    while (TRUE) {
      if ($current->isEntity()) {
        return $current->id();
      }
      $current = $current->parent();
      if (!$current instanceof BaroEntity) {
        Core::error('This case needs attention. Unable to find root id.');
      }
    }
  }

  /**
   * Will merge all available contexts into single array.
   *
   * @param string|array|NULL $contexts - Context name or their list (or
   *   nothing).
   *
   * @return array
   */
  protected function getMergedContext(string|array $contexts = NULL): array {
    $mergedData = [];
    // Provide contexts list by default.
    if (!isset($contexts)) {
      // We need to use context in opposite order.
      // @todo: Test if this has a proper order.
      $contexts = array_keys(array_reverse($this->applicationsOrder(), TRUE));
    }
    // Always an array.
    if (is_scalar($contexts)) {
      $contexts = [$contexts];
    }
    // Check each layer.
    $contextStorage = $this->storage['contexts'];
    foreach ($contexts as $context) {
      // Nothing to search.
      if (empty($contextStorage[$context]) || empty($contextStorage[$context]['entities'])) {
        continue;
      }
      $storage = $contextStorage[$context]['entities'];
      // Merge layers.
      $mergedData = array_merge($storage, $mergedData);
    }
    return $mergedData;
  }

}