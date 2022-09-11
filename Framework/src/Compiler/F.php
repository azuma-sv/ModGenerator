<?php

/**
 * @file
 * Helper service to handle functionality related with functions.
 */

namespace Barotraumix\Framework\Compiler;

use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Services\API;

/**
 * Class definition.
 */
class F {

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
      case 'import':
        static::fnImport($compiler, $string, $arguments, $value);
        break;

      case 'debug':
        static::fnDebug($compiler, $string, $arguments, $value, $context);
        break;

      case 'remove':
        static::fnRemove($compiler, $string, $arguments, $value, $context);
        break;

      case 'set-file':
        // Set file to export.
        static::fnSetFile($compiler, $string, $arguments, $value, $context);
        break;
    }
  }

  /**
   * Method to execute import command.
   *
   * @todo: Ability to use different contexts.
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
    // Prepare variables.
    $results = $compiler->query($value);
    if ($results->isEmpty()) {
      API::error('Unable to query object by a given rule: ' . $command);
    }
    // Set imported variable.
    $result = $results->current();
    foreach ($arguments as $variable) {
      // Validate argument name
      if (mb_strpos($variable, '%') !== FALSE) {
        API::error('You don\'t need to use "%" sign to define variable with a command: ' . $command);
      }
      // Validate variable name.
      preg_match('/^[\dA-Z_>]*/', trim($variable), $matches);
      $token = reset($matches);
      if (empty($token) || $token != $variable) {
        API::error('Wrong variable name syntax: ' . $variable . ' - in a command: ' . $command);
      }
      // Import variable.
      $keys = explode('>', $token);
      if ($results->isScalar()) {
        $compiler->database()->variableAdd($keys, $result);
      }
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
    // Validate filter rule.
    if (!Parser::isQuery($value)) {
      API::error('Wrong value format for $remove command. Invalid query: ' . $value);
    }
    unset($command, $arguments);
    $filtered = $compiler->filter($value, $context);
    // Remove them all at once.
    if (!$filtered->isEmpty()) {
      $filtered->remove($filtered->array());
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
  protected static function fnSetFile(Compiler $compiler, string $command, array $arguments, array|string $value, Context $context = NULL): void {
    unset($compiler, $command, $arguments);
    // Process entities.
    if (!$context->isEmpty() && $context->isRoot() !== NULL) {
      /** @var BaroEntity $entity */
      foreach ($context as $entity) {
        $entity->root()->file($value);
      }
    }
  }

}