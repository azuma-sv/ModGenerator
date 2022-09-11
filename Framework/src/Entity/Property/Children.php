<?php

/**
 * @file
 * Trait to handle children.
 */

namespace Barotraumix\Framework\Entity\Property;

use Barotraumix\Framework\Entity\Element;
use Barotraumix\Framework\Services\API;

/**
 * Trait definition.
 */
trait Children {

  /**
   * @var array<Element> - Child entities.
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
   * @return array<Element>
   */
  public function children(): array {
    return $this->children;
  }

  /**
   * Adds array of children to existing entity.
   *
   * @param array<Element> $children - Array of child entities.
   */
  public function addChildren(array $children): void {
    // For validation purpose.
    foreach ($children as $child) {
      $this->addChild($child);
    }
  }

  /**
   * Unset child by id (can use entity).
   *
   * @param Element|array|string $ids - Children IDs (or entity itself).
   *
   * @return void
   */
  public function unsetChildren(Element|array|string $ids): void {
    // Always an array.
    if (!is_array($ids)) {
      $ids = [$ids];
    }
    // Break lock if exists.
    if ($this->isLocked()) {
      $this->breakLock();
    }
    // Remove each child.
    foreach ($ids as $id) {
      if ($id instanceof Element) {
        $id = $id->id();
      }
      unset($this->children[$id]);
    }
  }

  /**
   * Removes children of the entity by its order number or/and it's group.
   *
   * @param string|int|NULL $order - Order number.
   * @param string|NULL $group - Group of elements to remove (impacts order).
   *  Group means a set of elements with same tag name.
   *
   * @return void
   */
  public function unsetChildrenOrder(string|int $order = NULL, string $group = NULL): void {
    // If we had unsuccessful attempt to modify children structure - break
    // lock in any case.
    if ($this->isLocked()) {
      $this->breakLock();
    }
    // Collect group of children to work with.
    $children = isset($group) ? $this->childrenByNames($group) : $this->children;
    if (empty($children)) {
      return ;
    }
    $keys = array_keys($children);
    // Prepare order argument.
    $count = count($keys);
    $order = empty($order) ? NULL : $order;
    $order = $order < 0 ? $count + $order - 1 : $order;
    if (isset($order) && ($order < 0 || $order > $count)) {
      return ;
    }
    // Detect proper way to unset children.
    if (isset($order)) {
      $this->unsetChildren($keys[$order]);
    }
    else {
      $this->unsetChildren($keys);
    }
  }

  /**
   * Append one child to the object.
   *
   * @param Element $entity
   *  Single child entity to add.
   * @param string|int $order - Order number.
   * @param string $group - Group of elements which impacts order.
   */
  public function addChild(Element $entity, string|int $order = 0, string $group = ''):void {
    // Break lock if exists.
    if ($this->isLocked()) {
      $this->breakLock();
    }
    // @todo: Implement.
    unset($order, $group);
    if (!$this->addChildValidate($entity)) {
      API::error($this->addChildErrorMessage());
    }
    $this->children[$entity->id()] = $entity;
  }

  /**
   * Array with list of children names.
   *
   * @return array
   */
  public function childrenNames():array {
    $types = [];
    foreach ($this->children() as $child) {
      $types[$child->name()] = $child->name();
    }
    return $types;
  }

  /**
   * Array with list of children with specific type.
   *
   * @param string|array $names - Children name to grab (or array of name).
   *
   * @return array<Element>
   */
  public function childrenByNames(string|array $names):array {
    // Convert to array in any case.
    if (is_scalar($names)) {
      $names = [$names];
    }
    // Collect children.
    $children = [];
    /** @var Element $child */
    foreach ($this->children() as $index => $child) {
      if (in_array($child->name(), $names)) {
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
    return $child instanceof Element;
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
    static $message = "You can't add a child object if it's not an Element.";
    if (isset($newMessage)) {
      $message = $newMessage;
    }
    return $message;
  }

}
