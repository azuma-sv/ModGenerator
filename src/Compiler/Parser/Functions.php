<?php

/**
 * @file
 * Helper service to handle functionality related with functions.
 */

namespace Barotraumix\Generator\Compiler\Parser;

use Barotraumix\Generator\Compiler\CompilerInterface;
use Barotraumix\Generator\Core;
use Barotraumix\Generator\Services\Database;

/**
 * Class definition.
 */
class Functions {

  /**
   * @var Database - Storage bank.
   */
  protected Database $bank;

  /**
   * @var CompilerInterface - Compiler service.
   */
  protected CompilerInterface $builder;

  /**
   * @var Parser - Parser service.
   */
  protected Parser $parser;

  /**
   * Object constructor.
   *
   * @param Database $bank - Storage for variables and context.
   */
  public function __construct(Database $bank, CompilerInterface $builder, Parser $parser) {
    $this->bank = $bank;
    $this->builder = $builder;
    $this->parser = $parser;
  }

  /**
   * Check if command is a function.
   *
   * @param string $command - Command to check.
   *
   * @return bool
   */
  public function isFunction(string $command): bool {
    return mb_substr(trim($command), 0, 1) == '$';
  }

  /**
   * Method to execute function.
   *
   * @param string $command - Unparsed command.
   * @param mixed $value - Passed data.
   * @param array|NULL $context - Context array (or nothing).
   *
   * @return void
   */
  public function function(string $command, mixed $value, array $context = NULL): void {
    $arguments = $this->parser->explode('|', $command);
    // Get function name.
    $function = mb_substr(reset($arguments), 1);
    // Unset function name from list of arguments.
    unset($arguments[0]);
    switch ($function) {
      case 'import':
        // Validate value.
        if (!$this->parser->isQuery($value)) {
          Core::error('Wrong value format for command: ' . $command);
        }
        $this->fnImport($command, $arguments, $value);
        break;

      case 'debug':
        // Validate filter rule.
        if (!$this->parser->isQuery($value)) {
          Core::error('Wrong value format for $debug command. Invalid query: ' . $command);
        }
        $this->fnDebug($command, $value, $arguments, $context);
        break;
    }
    Core::debug('Function call: ' . $function);
  }

  /**
   * Method to execute import command.
   *
   * @param string $command - Command to execute.
   * @param array $variables - Variables to import.
   * @param string $value - Value to import.
   *
   * @return void
   */
  protected function fnImport(string $command, array $variables, string $value): void {
    // Prepare variables.
    $query = $this->parser->query($value);
    $result = $this->bank->query($query);
    if (!isset($result)) {
      Core::error('Unable to query object by a given rule: ' . $value);
    }
    // Set imported variable.
    foreach ($variables as $variable) {
      // Validate argument name
      if (mb_strpos($variable, '%') !== FALSE) {
        Core::error('You don\'t need to use "%" sign to define variable with a command: ' . $command);
      }
      // Validate variable name.
      preg_match('/^[\dA-Z_>]*/', trim($variable), $matches);
      $token = reset($matches);
      if (empty($token) || $token != $variable) {
        Core::error('Wrong variable name syntax: ' . $variable . ' - in a command: ' . $command);
      }
      // Import variable.
      $keys = explode('>', $token);
      $this->bank->addVariable($keys, $result);
    }
  }

  /**
   * Method to print debug message in console.
   *
   * @param string $command - Command to execute.
   * @param string $filter - Query filter string.
   * @param array|NULL $context - Context array (or nothing).
   *
   * @return void
   */
  protected function fnDebug(string $command, string $filter, array $message = [], array $context = NULL): void {
    // Prepare variables.
    $message = reset($message);
    $query = $this->parser->query($filter);
    $filtered = $this->bank->query($query, $context);
    if (!empty($message)) {
      Core::debug('>>>>>>>>>>>>>>>>>>>>> ' . $message);
    }
    Core::debug('DEBUG START: --------------------------------');
    Core::debug(print_r($filtered, TRUE));
    Core::debug('DEBUG FINISH: --------------------------------');
    unset($command);
  }

}