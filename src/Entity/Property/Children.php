<?php

/**
 * @file
 * Trait to handle children.
 */

namespace Barotraumix\Generator\BaroEntity\Property;

use Barotraumix\Generator\BaroEntity\BaroEntity;
use Barotraumix\Generator\Core;

/**
 * Trait definition.
 */
trait Children {

  /**
   * @var array<BaroEntity> - Child entities.
   */
  protected array $children;

  /**
   * Check children.
   *
   * @return bool
   */
  public function hasChildren():bool {
    return !empty($this->children);
  }

  /**
   * Get children.
   *
   * @return array<BaroEntity>
   */
  public function children():array {
    return $this->children;
  }

  /**
   * Adds array of children to existing entity.
   *
   * @param array<BaroEntity> $children
   *   Array of child entities.
   */
  public function addChildren(array $children):void {
    $this->children = [];
    // For validation purpose.
    foreach ($children as $child) {
      $this->addChild($child);
    }
  }

  /**
   * Append one child to the object.
   *
   * @param BaroEntity $entity
   *  Single child entity to add.
   */
  public function addChild(BaroEntity $entity):void {
    if (!$this->addChildValidate($entity)) {
      Core::error($this->addChildErrorMessage());
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
      // Use child type if possible. Use XML tag name otherwise.
      if ($child->type()) {
        $types[$child->type()] = $child->type();
      }
      else {
        $types[$child->getName()] = $child->getName();
      }
    }
    return $types;
  }

    /**
     * Array with list of children with specific name.
     *
     * @param string|array $types - Children type to grab (or array of types).
     *
     * @return array
     */
  public function childrenByTypes(string|array $types):array {
    // Convert to array in any case.
    if (is_scalar($types)) {
      $types = [$types];
    }
    // Collect children.
    $children = [];
    foreach ($this->children() as $child) {
      if ($child->type()) {
        if (in_array($child->type(), $types)) {
          $children[] = $child;
        }
      }
      else {
        if (in_array($child->name(), $types)) {
          $children[] = $child;
        }
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
