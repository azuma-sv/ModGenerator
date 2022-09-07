<?php

/**
 * @file
 * XML Compiler class.
 */

namespace Barotraumix\Generator\Compiler;

use Barotraumix\Generator\Compiler\Parser\Parser;
use Barotraumix\Generator\Core;
use Barotraumix\Generator\Entity\BaroEntity;
use Barotraumix\Generator\Services\Database;
use SimpleXMLElement;

/**
 * Class definition.
 */
class CompilerClassic implements CompilerInterface {

  /**
   * @var string - Output path.
   */
  protected string $path;

  /**
   * @var Database - Storage bank.
   */
  protected Database $bank;

  /**
   * @var Parser - Mod Generator syntax parser.
   */
  protected Parser $parser;

  /**
   * Class constructor.
   *
   * @param string $path - Output path.
   * @param Database $bank - Storage bank.
   */
  public function __construct(string $path, Database $bank) {
    // Output path.
    $this->path = $path;
    // Storage banks.
    $this->bank = $bank;
    // Mod generator syntax.
    $this->parser = new Parser($this->bank, $this);
  }

  /**
   * Method to trigger mod building.
   *
   * @todo: Refactor.
   *
   * @return bool
   */
  public function doBuild(): bool {
    // Get mod data.
    $modData = $this->bank->modData();
    $settingsPrimary = $this->processDefaultSettings(reset($modData), TRUE);
    // Execute initialization hook.
    // Import variables.
    $this->bank->addVariables($settingsPrimary['global-variables'], TRUE);
    $this->execute($settingsPrimary['execute-on-init-with-no-context']);
    $this->execute($settingsPrimary['execute']);
    // Walk through files.
    foreach ($modData as $file => $settings) {
      // Skip primary settings. They should be in the end.
      // @todo: Remove this check.
      if (str_ends_with($file, Core::LOCAL_MOD_FILE)) {
        continue;
      }
      // Execute mod commands.
      $settings = $this->processDefaultSettings($settings);
      // Import variables.
      $this->bank->addVariables($settings['variables']);
      // Prepare context.
//      $this->context();
      $this->execute($settings['execute']);
    }
    // @todo: Create content package entity.
    // Execute pre-build hook.
    $this->execute($settingsPrimary['execute-on-pre-build-with-no-context']);
    // @todo: Build phase.
    // Prepare directory.
    $path = $this->path . $settingsPrimary['name'];
    if (!file_exists($path) && !mkdir($path, 0777, TRUE)) {
      Core::error('Unable to create mod directory: ' . $path);
    }
    // Save to file.
    $filePath = $path . '/filelist.xml';
    $xml = new SimpleXMLElement("<ContentPackage></ContentPackage>");
    foreach ($settingsPrimary['content-package-attributes'] as $attribute => $value) {
      $xml->addAttribute($attribute, $value);
    }
    file_put_contents($filePath, $xml->asXml());
    return TRUE;
  }

  /**
   * Method to execute commands for specific context (wrap recursive function).
   *
   * @param array $commands - Array of commands to execute.
   * @param array|NULL $context - Context scope.
   *
   * @return void
   */
  public function execute(array $commands, array $context = NULL): void {
    // Init variables.
    $parser = $this->parser;
    $bank = $this->bank;
    $functions = $parser->functions();
    // Check each command.
    foreach ($commands as $rawCommand => $data) {
      // Prepare command variables.
      $command = $parser->applyVariables($rawCommand);
      // Check if this is a function.
      if ($functions->isFunction($command)) {
        $functions->function($command, $data, $context);
        continue;
      }
      // @todo: Implement way to create new element.
      // In case if we need to filter context scope.
      if ($parser->isQuery($command)) {
        // Validate data.
        if (!is_array($data)) {
          Core::notice('Wrong command input. Value is given "' . $data . '", when array is expected. Command: ' . $command);
        }
        // Ensure that objects are cloned.
        $clone = !isset($context) || is_scalar(reset($context));
        // Build our context.
        $query = $parser->query($command);
        $filtered = $bank->query($query, $context, $clone);
        // Skip empty context.
        if (empty($filtered)) {
          Core::notice('Empty context for command: ' . $command);
          continue;
        }
        // Error for wrong context type.
        if (!is_array($filtered)) {
          Core::notice('Wrong context type. Context is not an array for command: ' . $command);
          continue;
        }
        // Run recursive commands.
        $this->execute($data, $filtered);
        continue;
      }
      // If we have bypassed all conditions above - it must be just a tag value.
      // @todo: Implode arrays to comma separated values.
      if (!is_scalar($data)) {
        Core::error('Mod command syntax error. Unable to set value for attribute: ' . $command);
      }
      // If context is empty - apply to EVERYTHING O_O
      if (empty($context)) {
        // Build our context.
        $query = $parser->query('');
        $context = $bank->query($query);
      }
      /** @var BaroEntity $entity */
      foreach ($context as $entity) {
        $entity->setAttribute($command, $data);
      }
    }
  }

//  /**
//   * Check if command is a filter rule.
//   *
//   * @todo: Move to Parser service (as method "!isAttribute").
//   *
//   * @param string $command - Command to check.
//   *
//   * @return bool
//   */
//  protected function isFilter(string $command): bool {
//    preg_match('/^[a-zA-Z\d\-_]*$/', $command, $matches);
//    $attribute = reset($matches);
//    return empty($attribute);
//  }

//  /**
//   * Check if this is a command to create new element.
//   *
//   * @param string $command - Command to check.
//   *
//   * @return bool
//   */
//  protected function startsWithCapitalLetter(string $command): bool {
//    return ctype_upper(mb_substr(trim($command), 0, 1));
//  }

//  /**
//   * Method to execute function.
//   *
//   * @todo: Move to functions service.
//   *
//   * @param string $command - Unparsed command.
//   * @param mixed $value - Passed data.
//   *
//   * @return void
//   */
//  protected function function(string $command, mixed $value): void {
//    $arguments = $this->safeExplode('|', $command);
//    // Get function name.
//    $function = mb_substr(reset($arguments), 1);
//    // Unset function name from list of arguments.
//    unset($arguments[0]);
//    switch ($function) {
//      case 'import':
//        // Validate value.
//        if (!is_scalar($value)) {
//          Core::error('Wrong value format for command: ' . $command);
//        }
//        $this->fnImport($command, $arguments, $value);
//        break;
//    }
////    $this->context()
//    // @todo: implement.
//    unset($value);
//    Core::debug('Function call: ' . $function);
//  }

//  /**
//   * Method to filter context scope with a rule.
//   *
//   * @param string $rule - Filtering rule.
//   * @param array|BaroEntity $scope - Context scope to filter (or single entity).
//   *
//   * @return array
//   */
//  protected function filter(string $rule, array|BaroEntity $scope): array {
//    Core::debug('Filter rule call: ' . $rule);
//    unset($rule, $scope);
//    return [];
//  }

//  /**
//   * Method to create context by specific rule.
//   *
//   * @param $rule - Rule to create context.
//   * @param $existingContext - Existing context if exists.
//   *
//   * @return BaroEntity|array
//   */
//  protected function context($rule, BaroEntity|array $existingContext = NULL): BaroEntity|array {
//    return [];
//  }



//  /**
//   * Prepare array of conditions by rule.
//   *
//   * @param string $rule
//   *
//   * @return array
//   */
//  protected function parseRule(string $rule): array {
//    $query = [];
//    // Split query into sections.
//    $inheritance = $this->explodeByDelimiters('/,<', $rule, TRUE, TRUE);
//    // Process each section.
//    foreach ($inheritance as $index => $section) {
//      // Wrap sub-element search into parent array.
//      if (in_array($section, ['<', '/'])) {
//        $query = [
//          'child' => $query,
//          'child_operator' => $section,
//        ];
//        continue;
//      }
//      // Matches everything.
//      if (empty($section)) {
//        $query = [
//          'entity' => '',
//          'attributes' => [],
//          'order' => 0,
//        ];
//        continue;
//      }
//      // Grab entity name condition.
//      $ruleParts = $this->explodeByDelimiters('|', $section, FALSE);
//      // Validate syntax.
//      if (count($ruleParts) > 2) {
//        Core::error('Invalid command syntax. Single query section can\'t contain more than one separator like "|" in command: ' . $rule);
//      }
//      // Check if it's an entity name.
//      $entityCondition = reset($ruleParts);
//      $isEntity = in_array($entityCondition, $this->bank->entityTypes());
//      // Validate current query section.
//      if (count($ruleParts) == 1) {
//        if (!$isEntity) {
//          $entityCondition = '';
//          $attributeConditions = $entityCondition;
//        }
//        else {
//          $attributeConditions = '';
//        }
//      }
//      else {
//        if (!$isEntity) {
//          Core::error('Invalid command syntax. Unable to find entity of type "' . $entityCondition . '" in a command: ' . $rule);
//          exit();
//        }
//        else {
//          $attributeConditions = next($ruleParts);
//        }
//      }
//      // Add entity condition to Query.
//      $query['entity'] = $entityCondition;
//      // Parse attributes section.
//      if (!empty($attributeConditions)) {
//        // Parse operator conditions.
//        $operatorConditions = $this->explodeByDelimiters('+,?', $section, TRUE);
//        $andConditions = array_keys($operatorConditions, '+');
//        $orConditions = array_keys($operatorConditions, '?');
//        foreach ($operatorConditions as $position => $operatorSection) {
//          // Validate if this is an attribute section.
//          if (in_array($position, $andConditions) || in_array($position, $orConditions)) {
//            continue;
//          }
//          // Check comparison rule.
//          $comparisonConditions = $this->explodeByDelimiters('=,!,*', $operatorSection, TRUE);
//          // Syntax error.
//          if (count($comparison) > 3 || count($comparison) == 2 || count($comparison) == 0) {
//            Core::error('Invalid command syntax. Unable to parse comparison operator "' . $condition . '" in a command: ' . $rule);
//          }
//          // Add attribute name.
//          $query[$index]['attributes'][$key]['name'] = reset($comparison);
//          // Add comparison operator.
//          if (!empty($comparison[2])) {
//            $query[$index]['attributes'][$key]['operator'] = $comparison[2];
//          }
//          // Add comparison value.
//          if (!empty($comparison[3])) {
//            $query[$index]['attributes'][$key]['value'] = $comparison[3];
//          }
//        }
//      }
//    }
//    return $query;
//  }

//  /**
//   * Safe way to transform string to array (takes protection symbols to attention).
//   *
//   * @param string $delimiter - Delimiter to use for explosion.
//   * @param string $string - String to explode.
//   *
//   * @return array
//   */
//  protected function explode(string $delimiter, string $string): array {
//    $positions = $this->findUnprotectedChar($string, $delimiter);
//    if (empty($positions) || (count($positions) === 1 && isset($positions[0]))) {
//      return [$string];
//    }
//    return $this->explodeByPositions($string, $positions);
//  }

//  /**
//   * Explode string by an array of positions.
//   *
//   * @param string|array $delimiters - Array of delimiters.
//   * @param string $string - String to explode.
//   * @param bool $include - Should we include delimiters into exploded array?
//   * @param bool $reverse - Return in reversed order.
//   *
//   * @return array
//   */
//  protected function explodeByDelimiters(string|array $delimiters, string $string, bool $include = FALSE, bool $reverse = FALSE): array {
//    // Always an array.
//    if (is_scalar($delimiters)) {
//      $delimiters = explode(',', $delimiters);
//    }
//    $positions = $this->findUnprotectedChars($delimiters, $string);
//    $exploded = [];
//    $offset = mb_strlen($string);
//    $positions = array_reverse($positions, TRUE);
//    foreach ($positions as $position => $delimiter) {
//      $length = mb_strlen($delimiter);
//      $exploded[$position + $length] = mb_substr($string, $position + $length, abs($position - $offset + $length));
//      $offset = $position;
//      if ($include) {
//        $exploded[$position] = $delimiter;
//      }
//    }
//    $exploded[0] = mb_substr($string, 0, $offset);
//    if (!$reverse) {
//      $exploded = array_reverse($exploded, TRUE);
//    }
//    return $exploded;
//  }

//  /**
//   * Explode string by an array of positions.
//   *
//   * @param string $string - String to explode.
//   * @param array $positions - Array of positions.
//   *
//   * @return array
//   */
//  protected function explodeByPositions(string $string, array $positions): array {
//    if (empty($positions)) {
//      return [$string];
//    }
//    // Explode string manually.
//    $exploded = [];
//    $offset = mb_strlen($string);
//    $positions = array_reverse($positions);
//    foreach ($positions as $position => $delimiter) {
//      $length = mb_strlen($delimiter);
//      $exploded[] = mb_substr($string, $position + $length, abs($position - $offset + $length));
//      $offset = $position;
//    }
//    $exploded[] = mb_substr($string, 0, $offset);
//    return array_reverse($exploded);
//  }

//  /**
//   * Method will return array of positions of all unprotected chars in a string.
//   *
//   * Unprotected means that they are not prepended with slash like "\@".
//   *
//   * @param string $command - Command to analyse.
//   * @param array|string $chars - List of chars (array or comma separated string).
//   *
//   * @return array
//   */
//  protected function findUnprotectedChars(array|string $chars, string $command): array {
//    // Always an array.
//    if (is_scalar($chars)) {
//      $chars = explode(',', $chars);
//    }
//    $occurrences = [];
//    foreach ($chars as $char) {
//      $positions = $this->findUnprotectedChar($char, $command);
//      $occurrences = $occurrences + $positions;
//    }
//    if (!empty($occurrences)) {
//      ksort($occurrences);
//    }
//    return $occurrences;
//  }

//  /**
//   * Method will return array of positions of unprotected char in a string.
//   *
//   * Unprotected means that they are not prepended with slash like "\@".
//   *
//   * @param string $char - Character to analyse.
//   * @param string $command - Command to analyse.
//   *
//   * @return array
//   */
//  protected function findUnprotectedChar(string $char, string $command): array {
//    $scope = $this->removeProtectionChars($command, FALSE);
//    // Look for instances.
//    $offset = 0;
//    $length = mb_strlen($char);
//    $positions = [];
//    while (TRUE) {
//      $position = mb_strpos($scope, $char, $offset);
//      if ($position === FALSE) {
//        break;
//      }
//      $offset = $position + $length;
//      $positions[$position] = $char;
//    }
//    return $positions;
//  }

//  /**
//   * This method will remove all protected letters from command syntax.
//   *
//   * After that - this command will start being unusable as a command, but
//   *   this string will become usable to extract tokens from it etc.
//   *
//   * @param string $value - Value to escape.
//   * @param bool $safe - Save escaped value is for mod output, unsafe is for
//   *   syntax parsing purpose.
//   *
//   * @return string
//   */
//  protected function removeProtectionChars(string $value, bool $safe = TRUE): string {
//    $scope = $value;
//    if (!$safe) {
//      // Remove reserved Barotrauma variables (case insensitive).
//      $reserved = ['%ModDir%'];
//      foreach ($reserved as $text) {
//        $replacement = str_repeat(' ', mb_strlen($text));
//        $scope = str_ireplace($text, $replacement, $scope);
//      }
//    }
//    // Protection char shouldn't work in same manner if its inside brackets: " \ ".
//    $ignorePositions = [];
//    if (!$safe) {
//      $ignoreProtection = FALSE;
//      $previous = '';
//      for ($position = 0; $position <= mb_strlen($scope); $position++) {
//        $char = mb_substr($scope, $position, 1);
//        $toProcess = TRUE;
//        // Disable ignore mode.
//        if ($ignoreProtection && $char == '"' && $previous != '\\') {
//          $ignoreProtection = $toProcess = FALSE;
//        }
//        // Add section to ignore.
//        if ($ignoreProtection) {
//          $ignorePositions[$position] = $char;
//        }
//        // Enable ignore mode.
//        if (!$ignoreProtection && $char == '"' && $previous != '\\' && $toProcess) {
//          $ignoreProtection = TRUE;
//        }
//        $previous = $char;
//      }
//    }
//    // Remove everything what is preceded with protecting slash including slash.
//    $chars = $safe ? '\\' : '\\,|,=,!,*,+,?,/,<';
//    foreach (explode(',', $chars) as $char) {
//      $replacement = $char == '\\' ? '  ' : ' ';
//      $length = $char == '\\' ? 2 : 1;
//      $position = 0;
//      while (TRUE) {
//        $offset = mb_strpos(mb_substr($scope, $position), $char);
//        if ($offset === FALSE) {
//          break;
//        }
//        $position += $offset;
//        if ($safe && $char == '\\') {
//          // Prepare safe replacement.
//          $replacement = mb_substr($scope, $position + 1, 1);
//        }
//        // Prepare replacement for ignored chars.
//        $ignoredReplacement = $replacement;
//        if ($char != '\\' && !isset($ignorePositions[$position])) {
//          $ignoredReplacement = $char;
//        }
//        $scope = substr_replace($scope, $ignoredReplacement, $position, $length);
//        $position++;
//      }
//    }
//    return $scope;
//  }

//  /**
//   * Replace variable tokens with variable values.
//   *
//   * @param string $command - Command to tokenize.
//   *
//   * @return string
//   */
//  protected function importVariables(string $command): string {
//    // Look for possible variables.
//    $scopePositions = $this->findUnprotectedChar('%', $command);
//    if (empty($scopePositions)) {
//      return $command;
//    }
//    // We should also check if we have variable-variables.
//    $positions = array_reverse($scopePositions, TRUE);
//    foreach ($positions as $position => $delimiter) {
//      // Process variable.
//      $scope = mb_substr($command, $position);
//      preg_match('/^%[\dA-Z_>]*/', $scope, $matches);
//      $token = reset($matches);
//      if (empty($token)) {
//        Core::error('Wrong yaml syntax when processing variable in command: ' . $command);
//      }
//      // Leave percent chars as is (if unable to pick it as variable).
//      if ($token == '%') {
//        // Remove from list to mark it as processed.
//        unset($positions[$position]);
//        continue;
//      }
//      // Get variable (skip % sign).
//      $keys = explode('>', mb_substr($token, 1));
//      $variable = $this->bank->variable($keys);
//      // Convert arrays to string.
//      if (is_array($variable)) {
//        $variable = implode(',', $variable);
//      }
//      // Every variable may contain another variable inside it.
//      if (is_scalar($variable)) {
//        // Recursive replacement.
//        $variable = $this->importVariables(strval($variable));
//      }
//      // Apply changes.
//      $length = mb_strlen($token);
//      $command = substr_replace($command, $variable, $position, $length);
//    }
//    return $command;
//  }

  /**
   * Method to process default settings of imported mod file.
   *
   * @param array $settings - Settings array.
   * @param bool $primary - Indicates that it's primary module file.
   *
   * @return array
   */
  protected function processDefaultSettings(array $settings, bool $primary = FALSE): array {
    // In case if it's primary module file.
    if ($primary) {
      if (empty($settings['name'])) {
        Core::error('Module name can\'t be empty');
      }
      // It should be a simple mod by default.
      if (empty($settings['method'])) {
        $settings['method'] = 'default';
      }
      // Validate type.
      if (!in_array($settings['method'], ['default', 'overhaul'])) {
        Core::error('Module method can be only: default or overhaul. Currently we have: ' . $settings['method']);
      }
      // Mod global variables.
      if (!isset($settings['global-variables'])) {
        $settings['global-variables'] = [];
      }
      // Mod version.
      if (!isset($settings['content-package-attributes']['modversion'])) {
        $settings['content-package-attributes']['modversion'] = '1.0.0';
      }
      // Game version.
      if (!isset($settings['content-package-attributes']['gameversion'])) {
        $settings['content-package-attributes']['gameversion'] = '';
      }
      // Core package attribute.
      if (!isset($settings['content-package-attributes']['corepackage'])) {
        $settings['content-package-attributes']['corepackage'] = '';
      }
      // Execute hooks.
      if (!isset($settings['execute-on-init-with-no-context'])) {
        $settings['execute-on-init-with-no-context'] = [];
      }
      if (!isset($settings['execute-on-pre-build-with-no-context'])) {
        $settings['execute-on-pre-build-with-no-context'] = [];
      }
    }
    return $settings;
  }

  /**
   * Method to extract attributes from node.
   *
   * @param array $data - Node array.
   *
   * @return array
   */
  protected function collectAttributes(array $data): array {
    return $data;
  }

}