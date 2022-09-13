<?php
/**
 * @file
 * Context is used to store a set of data (or a single value).
 *
 * @todo: Might need some refactoring.
 */

namespace Barotraumix\Framework\Compiler;

use Barotraumix\Framework\Entity\Element;
use Barotraumix\Framework\Entity\Property\ID;
use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Entity\RootEntity;
use Barotraumix\Framework\Services\API;
use ArrayAccess;
use Countable;
use Iterator;

/**
 * Class definition.
 */
class Context implements Iterator, ArrayAccess, Countable {

  /**
   * Context may have an ID.
   */
  use ID;

  /**
   * @var array - Storage for context data.
   */
  protected array $storage = [];

  /**
   * @var string|NULL - Type of contained data.
   */
  protected string|NULL $dataType = NULL;

  /**
   * @var bool|null - If context contains BaroEntities - we should know if they
   *   are root entities.
   */
  protected bool|NULL $isRoot = NULL;

  /**
   * @var int - Current position of iterator.
   */
  protected int $position = 0;

  /**
   * Returns raw array with context data.
   *
   * @return array
   */
  public function array(): array {
    return $this->storage;
  }

  /**
   * Method to query for entities in a context.
   *
   * @param array $conditions - Search conditions.
   *
   * @return Context
   */
  public function query(array $conditions): Context {
    $context = new Context();
    $context->addMultiple($this->filter($conditions), TRUE);
    return $context;
  }

  /**
   * Method to add multiple elements to context.
   *
   * @param array $items - Array of values to add.
   * @param bool $ignoreKeys - Should ignore keys of $items array?
   *
   * @return void
   */
  public function addMultiple(array $items, bool $ignoreKeys = FALSE): void {
    // Add each value.
    foreach ($items as $index => $item) {
      $key = $ignoreKeys ? NULL : $index;
      $this->add($item, $key);
    }
  }

  /**
   * Method to add new data to the context.
   *
   * @param mixed $value - Value to add.
   * @param string|int|NULL $key - Key to use for this value.
   *
   * @return void
   */
  public function add(mixed $value, string|int $key = NULL): void {
    $this->validateValue($value);
    if ($value instanceof BaroEntity) {
      // Message about replaced entity.
      if (isset($this->storage[$value->id()])) {
        $entity = $this->storage[$value->id()];
        $msg = 'Entity "' . $entity->name() . '" with ID "' . $entity->id();
        $msg .= '" has been replaced by entity "' . $value->name();
        $msg .= '" with ID "' . $value->id() . '" in a context: ' . $this->id();
        API::notice($msg);
      }
      // Don't need to use $key for BaroEntity.
      if (isset($key) && $value->id() != $key) {
        API::error('Attempt to assign wrong key for BaroEntity in a context ' . $this->id());
      }
      $this->storage[$value->id()] = $value;
    }
    else {
      if (isset($key)) {
        $this->storage[$key] = $value;
      }
      else {
        $this->storage[] = $value;
      }
    }
  }

  /**
   * Removes element from context.
   *
   * @param BaroEntity|array|string|int $indexes - Element index or BaroEntity
   *   (or array of them).
   *
   * @return void
   */
  public function remove(BaroEntity|array|string|int $indexes): void {
    // Always an array.
    if (!is_array($indexes)) {
      $indexes = [$indexes];
    }
    // Check each item.
    foreach ($indexes as $index) {
      // Mark entity as removed.
      if ($index instanceof BaroEntity) {
        $index->remove();
        $index = $index->id();
      }
      // Remove entity from context.
      unset($this->storage[$index]);
    }
  }

  /**
   * Indicates that context contain objects.
   *
   * @return bool
   */
  public function isBaroEntity(): bool {
    return $this->dataType() == 'object';
  }

  /**
   * Indicates that context contains arrays.
   *
   * @return bool
   */
  public function isArray(): bool {
    return $this->dataType() == 'array';
  }

  /**
   * Indicates that context contains scalar values.
   *
   * @return bool
   */
  public function isScalar(): bool {
    return $this->dataType() == 'scalar';
  }

  /**
   * Indicates that context is empty.
   *
   * @return bool
   */
  public function isEmpty(): bool {
    return empty($this->storage);
  }

  /**
   * Returns string with type of contained data.
   *
   * Available variants: object, array or scalar.
   * NULL - Means that data type is not defined (context is empty).
   *
   * @return string|NULL
   */
  public function dataType(): string|NULL {
    if (isset($this->dataType)) {
      return $this->dataType;
    }
    return NULL;
  }

  /**
   * Indicates that context contains root BaroEntities.
   *
   * FALSE - Not a root entities.
   * NULL - Not a BaroEntities at all.
   *
   * @return bool|NULL
   */
  public function isRoot(): bool|NULL {
    return $this->isRoot;
  }

  /**
   * Implements Iterator interface.
   *
   * Returns value for current position of Iterator.
   *
   * @return mixed
   */
  public function current(): mixed {
    $keys = array_keys($this->storage);
    $key = $keys[$this->position] ?? NULL;
    return $this->storage[$key] ?? NULL;
  }

  /**
   * Implements Iterator interface.
   *
   * Move iterator to next position.
   *
   * @return void
   */
  public function next(): void {
    ++$this->position;
  }

  /**
   * Implements Iterator interface.
   *
   * Return key of current position of iterator.
   *
   * @return string|int
   */
  public function key(): string|int {
    $keys = array_keys($this->storage);
    return $keys[$this->position];
  }

  /**
   * Implements Iterator interface.
   *
   * Check if current position of iterator is valid.
   *
   * @return bool
   */
  public function valid(): bool {
    $keys = array_keys($this->storage);
    return array_key_exists($this->position, $keys);
  }

  /**
   * Implements Iterator interface.
   *
   * Move iterator to beginning.
   *
   * @return void
   */
  public function rewind(): void {
    $this->position = 0;
  }

  /**
   * Implements ArrayAccess interface.
   *
   * Check if array key exists.
   *
   * @param mixed $offset - Array key.
   *
   * @return bool
   */
  public function offsetExists(mixed $offset): bool {
    return array_key_exists($offset, $this->storage);
  }

  /**
   * Implements ArrayAccess interface.
   *
   * Get value by array key.
   *
   * @param mixed $offset - Array key.
   *
   * @return mixed
   */
  public function offsetGet(mixed $offset): mixed {
    return $this->storage[$offset] ?? NULL;
  }

  /**
   * Implements ArrayAccess interface.
   *
   * Set value for specific array key.
   *
   * @param mixed $offset - Array key.
   * @param mixed $value - Array Value.
   *
   * @return void
   */
  public function offsetSet(mixed $offset, mixed $value): void {
    $this->add($value, $offset);
  }

  /**
   * Implements ArrayAccess interface.
   *
   * Unset specific array value by its key.
   *
   * @param mixed $offset - Array key.
   *
   * @return void
   */
  public function offsetUnset(mixed $offset): void {
    $this->remove($offset);
  }

  /**
   * Implements Countable interface.
   *
   * Count objects in array.
   *
   * @return int
   */
  public function count(): int {
    return count($this->storage);
  }

  /**
   * Method to detect value type.
   *
   * @param mixed $value - Value to use.
   *
   * @return string|NULL
   */
  protected function detectType(mixed $value): string|NULL {
    $type = gettype($value);
    if (in_array($type, ['object', 'array'])) {
      return $type;
    }
    else {
      if ($type == 'NULL') {
        return NULL;
      }
      else {
        // We will assume that eny other value is scalar.
        return 'scalar';
      }
    }
  }

  /**
   * Value validation callback. May throw an error.
   *
   * Validates if added value may be stored in this context.
   *
   * @param mixed $value - Value to validate.
   *
   * @return void
   */
  protected function validateValue(mixed $value): void {
    $dataType = $this->detectType($value);
    if ($dataType == 'object' && !$value instanceof BaroEntity) {
      API::error("Context can handle only BaroEntity objects.");
    }
    if ($this->isEmpty()) {
      $this->dataType = $dataType;
      if ($value instanceof BaroEntity) {
        $this->isRoot = $value instanceof RootEntity;
      }
    }
    else {
      $currentType = $this->dataType();
      if ($currentType != $dataType) {
        API::error("Attempt to insert data of type '$dataType' into context which contain data of type '$currentType'.");
      }
      // Validate BaroEntities.
      if ($value instanceof BaroEntity) {
        // Validate root status.
        if ($this->isRoot() !== $value instanceof RootEntity) {
          if ($this->isRoot()) {
            API::error("Attempt to insert NON-ROOT entity into the context which contains ONLY ROOT entities.");
          }
          else {
            API::error("Attempt to insert ROOT entity into the context which contains ONLY NON-ROOT entities.");
          }
        }
      }
    }
  }

  /**
   * Method to query for entities in a context.
   *
   * @param array $conditions - Search conditions.
   *
   * @return array
   */
  protected function filter(array $conditions): array {
    // Init variables.
    $data = [];
    $depth = 0;
    $depthToReturn = NULL;
    $collection = [[]];
    $condition = $conditions;
    if (!$this->isBaroEntity() || $this->isEmpty()) {
      return $data;
    }
    // Walk through all conditions.
    while (TRUE) {
      // Apply filter conditions to current scope.
      if (empty($depth)) {
        $filtered = $this->entityFilter($this->storage, $condition);
        if (!empty($filtered)) {
          // Store every entity.
          /** @var RootEntity $entity */
          foreach ($filtered as $entity) {
            $collection[$depth][$entity->id()][$entity->id()] = $entity;
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
                  $collection[$depth][$entity->root()->id()][$entityFiltered->id()] = $entityFiltered;
                }
              }
            }
          }
        }
      }
      // Skip filtration if we have no elements in context.
      if (empty($collection[$depth])) {
        return $data;
      }

      // Filter previous steps from entities which do not match to child conditions.
      if (!empty($depth)) {
        $step = $depth - 1;
        $break = FALSE;
        while (!$break) {
          // Clear previous steps.
          foreach ($collection[$step] as $id => $scope) {
            if (!isset($collection[$depth][$id])) {
              unset($collection[$step][$id]);
            }
          }
          // Break cleaning.
          if (empty($step)) {
            $break = TRUE;
          }
          // Move to beginning.
          $step--;
        }
      }

      // Return results if there is no nex sub-query.
      if (empty($condition['child'])) {
        // Prepare results and return them.
        $filtered = isset($depthToReturn) ? $collection[$depthToReturn] : $collection[$depth];
        foreach ($filtered as $scope) {
          foreach ($scope as $entity) {
            $data[] = $entity;
          }
        }
        return $data;
      }

      // Apply operator (for "/" - just skip).
      switch ($condition['child_operator']) {
        // In case if we should return objects from previous step.
        case '<':
          // No not override existing destination.
          if (!isset($depthToReturn)) {
            $depthToReturn = $depth;
          }
          break;

        // In case if we have a return statement.
        case '>':
          // Prepare results.
          $attribute = $condition['child']['entity'];
          foreach ($collection[$depth] as $scope) {
            /** @var BaroEntity $entity */
            foreach ($scope as $entity) {
              // Return attribute if possible.
              if ($entity->hasAttribute($attribute)) {
                $data[] = $entity->attribute($attribute);
              }
            }
          }
          return $data;

        // Create new elements.
        case '~':
          $name = API::normalizeTagName($condition['child']['entity']);
          if (empty($depth) && $this instanceof ContextRoot) {
            // Detect entity type.
            if (!empty($condition['child']['type'])) {
              $type = $condition['child']['type'];
            }
            else {
              $type = API::getTypeByName($name);
              if (empty($type)) {
                API::error("Unable to determine entity type (content package asset type) by XML tag name '$name'. Please provide entity type in a query in this way: ~$name(EntityType). For example: ~Decal(Structure)");
              }
            }
            // Root entity.
            $entity = new RootEntity($name, [], $type, '', 'new.' . $name);
            $entity->breakLock();
            $this->add($entity);
            $data[] = $entity;
          }
          else {
            // Create child entity for all entities found by a query.
            foreach ($collection[$depth] as $scope) {
              /** @var BaroEntity $entity */
              foreach ($scope as $entity) {
                // Normal element.
                $child = new Element($name, [], $entity);
                $entity->addChild($child, $condition['child']['order'], $name);
                $data[] = $child;
              }
            }
          }
          return $data;

      }
      // Switch to next step.
      $condition = $condition['child'];
      $depth++;
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
  protected function entityFilter(array $list, array $condition): array {
    $results = [];
    $nameCondition = $condition['entity'];
    $typeCondition = $condition['type'];
    // Check each item.
    /** @var BaroEntity $entity */
    foreach ($list as $entity) {
      $name = $entity->name();
      // Skip entities which do not match to our type.
      if (!empty($nameCondition) && $name != $nameCondition) {
        continue;
      }
      // Additionally check entity type condition.
      if (!empty($typeCondition) && $entity instanceof RootEntity && $entity->type() != $typeCondition) {
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
      $order = $order > 0 ? $order - 1 : count($results) + $order;
      // Validate order existence.
      $keys = array_keys($results);
      $results = !isset($keys[$order]) ? [] : [$results[$keys[$order]]];
    }
    return $results;
  }

  /**
   * Verify if given entity matches to provided conditions.
   *
   * @param BaroEntity $entity - Entity to check.
   * @param array $conditions - Attribute conditions to apply.
   *
   * @return bool
   */
  protected function entityMatch(BaroEntity $entity, array $conditions = []): bool {
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
        continue;
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
   * @todo: Improve strict comparison "" in conjunction with: *, ^, $.
   *
   * @param BaroEntity $entity - Given entity.
   * @param array $condition - Given condition of specific attribute.
   *
   * @return bool
   */
  protected function entityCondition(BaroEntity $entity, array $condition): bool {
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

}