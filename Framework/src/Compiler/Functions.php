<?php

/**
 * @file
 * Helper service to handle functionality related with functions.
 *
 * @todo: I should make it look better some day. Improve OOP structure...
 */

namespace Barotraumix\Framework\Compiler;

use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Entity\Element;
use Barotraumix\Framework\Entity\RootEntity;
use Barotraumix\Framework\Services\API;

/**
 * Class definition.
 */
class Functions {

  /**
   * Check if string is a function.
   *
   * @param string $string - String to check.
   *
   * @return bool
   */
  public static function isFn(string $string): bool {
    return mb_substr(trim($string), 0, 1) == '$';
  }

  /**
   * Method to execute function.
   *
   * @param Compiler $compiler - Compiler service.
   * @param string $string - String to execute.
   * @param mixed $value - Value to execute.
   * @param Context|NULL $context - Context array (or nothing).
   *
   * @return void
   */
  public static function function(Compiler $compiler, string $string, mixed $value, Context $context = NULL): void {
    $arguments = Parser::explode('|', $string);
    // Get function name.
    $function = mb_substr(reset($arguments), 1);
    // Unset function name from list of arguments.
    unset($arguments[0]);
    switch ($function) {
      case 'debug':
        static::fnDebug($compiler, $string, $arguments, $value, $context);
        break;

      case 'import':
        static::fnImport($compiler, $string, $arguments, $value);
        break;

      case 'create':
        static::fnCreate($compiler, $string, $arguments, $value, $context);
        break;

      case 'clone':
        static::fnClone($compiler, $string, $arguments, $value, $context);
        break;

      case 'remove':
        static::fnRemove($compiler, $string, $arguments, $value, $context);
        break;

      case 'file-set':
        // Set file to export.
        static::fnFileSet($compiler, $string, $arguments, $value, $context);
        break;

      case 'asset-add':
        // Set file to export.
        static::fnAssetAdd($compiler, $string, $arguments, $value, $context);
        break;
    }
  }

  /**
   * Method to print debug message in console.
   *
   * @param Compiler $compiler - Compiler service.
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments array.
   * @param array|string $value - Function value.
   * @param Context|NULL $context - Context (or nothing).
   *
   * @return void
   */
  protected static function fnDebug(Compiler $compiler, string $command, array $arguments, array|string $value, Context $context = NULL): void {
    // Validate filter rule.
    if (!Parser::isQuery($value)) {
      API::error('Wrong value format for $debug command. Invalid query: ' . $value);
    }
    // Prepare variables.
    $message = reset($arguments);
    $filtered = $compiler->filter($value, $context);
    $messages = [];
    // Prepare array.
    if (!$filtered->isEmpty()) {
      foreach ($filtered as $key => $item) {
        if ($item instanceof BaroEntity) {
          $messages[$key] = $item->debug();
        }
        else {
          $messages[] = $item;
        }
      }
    }
    // Print data.
    if (!empty($message)) {
      API::debug('>>>>>>>>>>>>>>>>>>>>> ' . $message);
    }
    API::debug('DEBUG START: --------------------------------');
    API::debug(print_r($messages, TRUE));
    API::debug('DEBUG FINISH: --------------------------------');
    unset($command);
  }

  /**
   * Method to execute import command.
   *
   * @example: $import|GAME_VERSION|Barotrauma:
   *   ContentPackage@name="Vanilla">gameversion
   *
   * @param Compiler $compiler - Compiler service.
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments array.
   * @param array|string $value - Function value.
   *
   * @return void
   */
  protected static function fnImport(Compiler $compiler, string $command, array $arguments, array|string $value): void {
    // Validate value.
    if (!Parser::isQuery($value)) {
      API::error('Wrong value format for command: ' . $command);
    }
    // Validate variable name.
    $variableName = reset($arguments);
    if (empty($variableName)) {
      API::error('Unable to import data, because variable name is not set in a command: ' . $command);
    }
    $variableName = Parser::applyVariables($variableName, $compiler->database());
    // Get context name.
    $contextName = next($arguments);
    $contextName = empty($contextName) ? NULL : $contextName;
    // Prepare variables.
    $results = $compiler->query($value, $contextName);
    if ($results->isEmpty()) {
      API::error('Unable to query anything by a given rule: ' . $command);
    }
    // Set imported value.
    if ($results->count() == 1) {
      $result = $results->current();
    }
    else {
      $result = $results->array();
    }
    // Validate variable name.
    // @todo: Single validation rule.
    preg_match('/^[\dA-Z_>]*/', trim($variableName), $matches);
    $token = reset($matches);
    if (empty($token) || $token != $variableName) {
      API::error('Wrong variable name syntax: ' . $variableName . ' - in a command: ' . $command);
    }
    // Import variable.
    $keys = explode('>', $token);
    $compiler->database()->variableAdd($keys, $result);
  }

  /**
   * Method to create elements in database.
   *
   * @example: $create:Item: ...
   * @example: $create:Decal|Structure: ...
   * @example: $create:DecorativeSprite: ...
   * @example: $create:DecorativeSprite|3: ...
   *
   * @todo: Improve API.
   *
   * @param Compiler $compiler - Compiler service.
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments array.
   * @param array|string $value - Function value.
   * @param Context|NULL $context - Context (or nothing).
   *
   * @return void
   */
  protected static function fnCreate(Compiler $compiler, string $command, array $arguments, array|string $value, Context $context = NULL): void {
    // Validate filter rule.
    if (!is_array($value)) {
      API::error('Wrong value format for $create command. Command: ' . $command);
    }
    $name = reset($arguments);
    if (empty($name)) {
      API::error('Unable to create entity without tag name. Command: ' . $command);
    }
    $orderOrType = next($arguments);
    // Wrap to array if necessary.
    if (is_string(key($value))) {
      $value = [$value];
    }

    // Create each element.
    foreach ($value as $element) {
      // Root entity.
      if ($context instanceof ContextRoot) {
        if (empty($orderOrType)) {
          $orderOrType = API::getTypeByName($name);
          if (!isset($orderOrType)) {
            API::error("Unable to determine entity type (content package asset type) by XML tag name '$name'. Please provide entity type in a function in this way: \$create|$name|EntityType:. For example: \$create|Decal|Structure:");
          }
        }
        // Create entity.
        $entity = new RootEntity($name, [], $orderOrType, '', 'new.' . $orderOrType);
        $entity->breakLock();
        $context->add($entity);
        // Import child elements and attributes.
        $entityContext = new Context();
        $entityContext->add($entity);
        $compiler->execute($element, $entityContext);
      }
      else {
        $orderSet = !empty($orderOrType);
        // Create this element for every item.
        $entitiesContext = new Context();
        foreach ($context as $entity) {
          $order = $orderSet ? intval($orderOrType) : 0;
          $orderGroup = $orderSet ? NULL : $name;
          // Normal element.
          $child = new Element($name, [], $entity);
          $entity->addChild($child, $order, $orderGroup);
          $entitiesContext->add($entity);
          $orderSet = FALSE;
        }
        $compiler->execute($element, $entitiesContext);
      }
    }
  }

  /**
   * Method to clone elements in database.
   *
   * @example: $clone|Barotrauma|Item@identifier="some-item":
   * @example: $clone|Barotrauma|Item@identifier="some-item"/Price:
   * @example: $clone|Barotrauma|Item@identifier="some-item"/Price|3:
   *
   * @todo: Improve API.
   *
   * @param Compiler $compiler - Compiler service.
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments array.
   * @param array|string $value - Function value.
   * @param Context|NULL $context - Context (or nothing).
   *
   * @return void
   */
  protected static function fnClone(Compiler $compiler, string $command, array $arguments, array|string $value, Context $context = NULL): void {
    // Validate filter rule.
    if (!is_array($value)) {
      API::error('Wrong value format for $clone command. Command: ' . $command);
    }
    // Get context name.
    $contextName = reset($arguments);
    $contextName = empty($contextName) ? NULL : $contextName;
    // Get query.
    $query = next($arguments);
    if (!Parser::isQuery($query)) {
      API::error("Wrong query '$query' format for command: " . $command);
    }
    // Get order.
    $order = intval(next($arguments));
    // Prepare objects for cloning.
    $results = $compiler->query($value, $contextName);
    if ($results->isEmpty()) {
      API::error('Unable to query anything by a given rule: ' . $command);
    }
    if (!$results->isBaroEntity()) {
      API::error('Queried data is not a BaroEntity in a query: ' . $query);
    }
    // Create for every cloned entity.
    /** @var BaroEntity $entityToClone */
    foreach ($results as $entityToClone) {
      // Validate.
      if ($results->isRoot() && !$context instanceof ContextRoot) {
        API::error("You can't clone Root entities in NON-ROOT context");
      }
      // Root entity.
      if ($results->isRoot()) {
        // Create entity.
        $clone = $entityToClone->clone();
        $clone->breakLock();
        $context->add($clone);
        // Import changes for child elements and attributes.
        $clonedEntityContext = new Context();
        $clonedEntityContext->add($clone);
        $compiler->execute($value, $clonedEntityContext);
      }
      else {
        $orderSet = !empty($order) ? $order : 0;
        // Create this element for every item.
        $clonedEntitiesContext = new Context();
        /** @var Element $entity */
        foreach ($context as $entity) {
          $orderGroup = $orderSet ? NULL : $entityToClone->name();
          // Normal element.
          $child = $entityToClone->clone($entity);
          $entity->addChild($child, $orderSet, $orderGroup);
          $clonedEntitiesContext->add($child);
          $orderSet = NULL;
        }
        $compiler->execute($value, $clonedEntitiesContext);
      }
    }
  }

  /**
   * Method to remove element from database.
   *
   * @param Compiler $compiler - Compiler service.
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments array.
   * @param array|string $value - Function value.
   * @param Context|NULL $context - Context (or nothing).
   *
   * @return void
   */
  protected static function fnRemove(Compiler $compiler, string $command, array $arguments, array|string $value, Context $context = NULL): void {
    if (!is_array($value)) {
      $value = [$value];
    }
    unset($command, $arguments);
    // Process each query.
    foreach ($value as $query) {
      // Validate filter rule.
      if (!Parser::isQuery($query)) {
        API::error('Wrong value format for $remove command. Invalid query: ' . $query);
      }
      $filtered = $compiler->filter($query, $context);
      // Remove them all at once.
      if (!$filtered->isEmpty() && $filtered->isBaroEntity()) {
        $filtered->remove($filtered->array());
      }
      else {
        API::notice('Empty results for query: ' . $query);
      }
    }
  }

  /**
   * Method to set specific export file for some entities.
   *
   * @param Compiler $compiler - Compiler service.
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments array.
   * @param array|string $value - Function value.
   * @param Context|NULL $context - Context (or nothing).
   *
   * @return void
   */
  protected static function fnFileSet(Compiler $compiler, string $command, array $arguments, array|string $value, Context $context = NULL): void {
    unset($compiler, $command, $arguments);
    // Process entities.
    if (!$context->isEmpty() && $context->isBaroEntity()) {
      /** @var BaroEntity $entity */
      foreach ($context as $entity) {
        $entity->root()->file($value);
      }
    }
  }

  /**
   * Method to add asset to the content package.
   *
   * @example: $asset-add|EnemySubmarine: Content/Map/EnemySubmarines/DugongPirate.sub
   * @example: $asset-add|EnemySubmarine: %ModDir/My/Custom/Submarine.sub
   *
   * @todo Handle multiple content packages.
   *
   * @param Compiler $compiler - Compiler service.
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments array.
   * @param array|string $value - Function value.
   * @param Context|NULL $context - Context (or nothing).
   *
   * @return void
   */
  protected static function fnAssetAdd(Compiler $compiler, string $command, array $arguments, array|string $value, Context $context = NULL): void {
    // Get asset type.
    $assetType = API::normalizeTagName(reset($arguments));
    // Array in any case.
    if (!is_array($value)) {
      $value = [$value];
    }
    $contentPackage = $compiler->contentPackage();
    foreach ($value as $file) {
      $asset = new Element($assetType, ['file' => $file], $contentPackage);
      $contentPackage->addChild($asset);
    }
  }

}