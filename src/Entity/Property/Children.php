<?php

/**
 * @file
 * Trait to handle children.
 */

namespace Barotraumix\Framework\Entity\Property;

use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Services\Framework;

/**
 * Trait definition.
 */
trait Children {

  /**
   * @var array<BaroEntity> - Child entities.
   */
  protected array $children = [];

  /**
   * Check children.
   *
   * @return bool
   */
  public function hasChildren(): bool {
    return !empty($this->children);
  }

  /**
   * Get children.
   *
   * @return array<BaroEntity>
   */
  public function children(): array {
    return $this->children;
  }

  /**
   * Adds array of children to existing entity.
   *
   * @param array<BaroEntity> $children - Array of child entities.
   */
  public function addChildren(array $children): void {
    // For validation purpose.
    foreach ($children as $child) {
      $this->addChild($child);
    }
  }

  /**
   * Removes children of the entity.
   *
   * @param string|int|NULL $order - Order number.
   * @param string|NULL $group - Group of elements to remove (impacts order).
   *
   * @return void
   */
  public function unsetChildren(string|int $order = NULL, string $group = NULL): void {
    // Break lock if exists.
    if ($this->isLocked()) {
      $this->breakLock();
    }
    // If group exists.
    if (isset($order) && isset($group)) {
      $children = $this->childrenByTypes($group);
      $keys = array_keys($children);
      $order = $keys[$order] ?? NULL;
    }
    // Unset proper child.
    if (isset($order)) {
      unset($this->children[$order]);
    }
    else {
      $this->children = [];
    }
  }

  /**
   * Append one child to the object.
   *
   * @param BaroEntity $entity
   *  Single child entity to add.
   * @param string|int $order - Order number.
   * @param string $group - Group of elements which impacts order.
   */
  public function addChild(BaroEntity $entity, string|int $order = 0, string $group = ''):void {
    // Break lock if exists.
    if ($this->isLocked()) {
      $this->breakLock();
    }
    // @todo: Implement.
    unset($order, $group);
    if (!$this->addChildValidate($entity)) {
      Framework::error($this->addChildErrorMessage());
    }
    $this->children[] = $entity;
  }

  /**
   * Array with list of children names.
   *
   * @return array
   */
  public function childrenTypes():array {
    $types = [];
    foreach ($this->children() as $child) {
      $types[$child->type()] = $child->type();
    }
    return $types;
  }

  /**
   * Array with list of children with specific type.
   *
   * @param string|array $types - Children type to grab (or array of types).
   *
   * @return array<BaroEntity>
   */
  public function childrenByTypes(string|array $types):array {
    // Convert to array in any case.
    if (is_scalar($types)) {
      $types = [$types];
    }
    // Collect children.
    $children = [];
    foreach ($this->children() as $index => $child) {
      if (in_array($child->type(), $types)) {
        $children[$index] = $child;
      }
    }
    return $children;
  }

  /**
   * Validation callback for child object.
   *
   * @return bool
   *  TRUE if child is valid.
   */
  protected function addChildValidate(mixed $child):bool {
    return $child instanceof BaroEntity;
  }

  /**
   * Method to provide error message.
   *
   * Created to override original message, if necessary.
   *
   * @param string|NULL $newMessage
   *  New message to replace original.
   *
   * @return string
   */
  protected function addChildErrorMessage(string $newMessage = NULL):string {
    static $message = "You can't add a child object if it's not a BaroEntity.";
    if (isset($newMessage)) {
      $message = $newMessage;
    }
    return $message;
  }

}
