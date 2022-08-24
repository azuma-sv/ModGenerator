<?php

/**
 * @file
 * Trait to handle children.
 */

namespace Barotraumix\Generator\BaroEntity\Property;

use Barotraumix\Generator\BaroEntity\Base;
use Barotraumix\Generator\Core;

/**
 * Trait definition.
 */
trait Children {

  /**
   * @var array - Child entities.
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
   * @return array
   */
  public function getChildren():array {
    return $this->children;
  }

  /**
   * Set children for object.
   *
   * @param array $children
   *  Array of child entities.
   */
  public function setChildren(array $children):void {
    // For validation purpose.
    foreach ($children as $child) {
      $this->addChild($child);
    }
  }

  /**
   * Append one child to the object.
   *
   * @param mixed $child
   *  Single child entity to add. Should be an instance of "BaroEntity\Base".
   */
  public function addChild(mixed $child):void {
    if (!$this->addChildValidate($child)) {
      Core::error($this->addChildErrorMessage());
    }
    $this->children[] = $child;
  }

  /**
   * Array with list of children names.
   *
   * @return array
   */
  public function getChildrenTypes():array {
    $types = [];
    /** @var Base $child */
    foreach ($this->getChildren() as $child) {
      $types[$child->getName()] = $child->getName();
    }
    return $types;
  }

    /**
     * Array with list of children with specific name.
     *
     * @param string|array $type - Children type to grab (or array of types).
     *
     * @return array
     */
  public function getChildrenByType(string|array $type):array {
    // Convert to array in any case.
    if (is_scalar($type)) {
      $type = [$type];
    }
    // Collect children.
    $children = [];
    /** @var Base $child */
    foreach ($this->getChildren() as $child) {
      if (in_array($child->getName(), $type)) {
        $children[] = $child;
      }
    }
    return $children;
  }

  /**
   * Validation callback fir child object.
   *
   * @return bool
   *  TRUE if child is valid.
   */
  protected function addChildValidate(mixed $child):bool {
    return $child instanceof Base;
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
