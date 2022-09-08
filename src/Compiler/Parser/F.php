<?php

/**
 * @file
 * Helper service to handle functionality related with functions.
 */

namespace Barotraumix\Framework\Compiler\Parser;

use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Services\Framework;
use Barotraumix\Framework\Services\Services;

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
   * @param string $string - String to execute.
   * @param mixed $value - Value to execute.
   * @param array|string|NULL $scope - Context array (or nothing).
   *
   * @return void
   */
  public static function function(string $string, mixed $value, array|string &$scope = NULL): void {
    $arguments = Parser::explode('|', $string);
    // Get function name.
    $function = mb_substr(reset($arguments), 1);
    // Unset function name from list of arguments.
    unset($arguments[0]);
    switch ($function) {
      case 'import':
        // Validate value.
        if (!Parser::isQuery($value)) {
          Framework::error('Wrong value format for command: ' . $string);
        }
        static::fnImport($string, $arguments, $value);
        break;

      case 'debug':
        // Validate filter rule.
        if (!Parser::isQuery($value)) {
          Framework::error('Wrong value format for $debug command. Invalid query: ' . $value);
        }
        static::fnDebug($string, $arguments, $value, $scope);
        break;

      case 'remove':
        // Validate filter rule.
        if (!Parser::isQuery($value)) {
          Framework::error('Wrong value format for $remove command. Invalid query: ' . $value);
        }
        static::fnRemove($string, $arguments, $value, $scope);
        break;

      case 'set-file':
        // Set file to export.
        static::fnSetFile($string, $arguments, $value, $scope);
        break;
    }
    Framework::debug('Function call: ' . $function);
  }

  /**
   * Method to parse and run query.
   *
   * @param string $string
   * @param array|string|NULL $context
   * @param bool $clone
   *
   * @return array|string|NULL
   */
  public static function query(string $string, array|string $context = NULL, bool $clone = FALSE): array|string|NULL {
    return Services::$database->query(Parser::query($string), $context, $clone);
  }

  /**
   * Method to execute import command.
   *
   * @param string $command - Command to execute.
   * @param array $variables - Variables to import.
   * @param string $query - Value to import.
   *
   * @return void
   */
  protected static function fnImport(string $command, array $variables, string $query): void {
    // Prepare variables.
    $result = static::query($query);
    if (!isset($result)) {
      Framework::error('Unable to query object by a given rule: ' . $query);
    }
    // Set imported variable.
    foreach ($variables as $variable) {
      // Validate argument name
      if (mb_strpos($variable, '%') !== FALSE) {
        Framework::error('You don\'t need to use "%" sign to define variable with a command: ' . $command);
      }
      // Validate variable name.
      preg_match('/^[\dA-Z_>]*/', trim($variable), $matches);
      $token = reset($matches);
      if (empty($token) || $token != $variable) {
        Framework::error('Wrong variable name syntax: ' . $variable . ' - in a command: ' . $command);
      }
      // Import variable.
      $keys = explode('>', $token);
      Services::$database->addVariable($keys, $result);
    }
  }

  /**
   * Method to print debug message in console.
   *
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments passed to function.
   * @param string $query - Query filter string.
   * @param array|NULL $scope - Context array (or nothing).
   *
   * @return void
   */
  protected static function fnDebug(string $command, array $arguments, string $query, array $scope = NULL): void {
    // Prepare variables.
    $message = reset($arguments);
    $filtered = static::query($query, $scope);
    // Prepare array.
    foreach ($filtered as $key => $item) {
      if ($item instanceof BaroEntity) {
        $filtered[$key] = $item->debug();
      }
    }
    // Print data.
    if (!empty($message)) {
      Framework::debug('>>>>>>>>>>>>>>>>>>>>> ' . $message);
    }
    Framework::debug('DEBUG START: --------------------------------');
    Framework::debug(print_r($filtered, TRUE));
    Framework::debug('DEBUG FINISH: --------------------------------');
    unset($command);
  }

  /**
   * Method to remove element from database.
   *
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments passed to function.
   * @param string $query - Query filter string.
   * @param array|NULL $scope - Context array (or nothing).
   *
   * @return void
   */
  protected static function fnRemove(string $command, array $arguments, string $query, array &$scope = NULL): void {
    unset($command, $arguments);
    Services::$database->entitiesRemove(static::query($query, $scope), $scope);
  }

  /**
   * Method to set specific export file for some entities.
   *
   * @param string $command - Command to execute.
   * @param array $arguments - Arguments passed to function.
   * @param string $value - Filepath to set.
   * @param array|NULL $scope - Context array (or nothing).
   *
   * @return void
   */
  protected static function fnSetFile(string $command, array $arguments, string $value, array $scope = NULL): void {
    unset($command, $arguments);
    // Select if needed.
    if (!isset($scope) || is_string(reset($scope))) {
      $scope = static::query('', $scope);
    }
    // Process entities.
    if (reset($scope) instanceof BaroEntity) {
      /** @var BaroEntity $entity */
      foreach ($scope as $entity) {
        $entity->root()->file($value);
      }
    }
  }

}