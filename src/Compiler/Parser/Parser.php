<?php

/**
 * @file
 * Parser service which is aimed to help with parsing of mod source files.
 */

namespace Barotraumix\Generator\Compiler\Parser;

use Barotraumix\Generator\Compiler\CompilerInterface;
use Barotraumix\Generator\Core;
use Barotraumix\Generator\Services\Database;

/**
 * Class definition.
 */
class Parser {

  /**
   * @var Database - Storage bank.
   */
  protected Database $bank;

  /**
   * @var Functions - Service to work with functions.
   */
  protected Functions $functions;

  /**
   * Object constructor.
   *
   * @param Database $bank - Storage for variables and context.
   */
  public function __construct(Database $bank, CompilerInterface $builder) {
    $this->bank = $bank;
    $this->functions = new Functions($bank, $builder, $this);
  }

  /**
   * Method to check if current string contains an attribute.
   *
   * @param string $string - String to check.
   *
   * @return bool
   */
  public function isAttribute(string $string): bool {
    // @todo: Test this regexp.
    preg_match('/^[a-zA-Z\d\-_]*$/', $string, $matches);
    $attribute = reset($matches);
    return !empty($attribute);
  }

  /**
   * Method to check if current string contains a query.
   *
   * Everything what is not a function and not an attribute - considered as
   *   filter rule.
   *
   * @param string $string - String to check.
   *
   * @return bool
   */
  public function isQuery(string $string): bool {
    return !$this->functions->isFunction($string) && !$this->isAttribute($string);
  }

  /**
   * Service to work with functions.
   *
   * @return Functions
   */
  public function functions(): Functions {
    return $this->functions;
  }

  /**
   * Method to build query array from filter rule.
   *
   * @param string $string - String to parse as a rule.
   *
   * @return array
   */
  public function query(string $string): array {
    $query = [];
    // Split query into sections.
    $inheritance = $this->explode('/,<,>', $string, TRUE, TRUE);
    // Process each section.
    foreach ($inheritance as $section) {
      // Wrap sub-element search into parent array.
      if (in_array($section, ['<', '/', '>'])) {
        $query = [
          'child' => $query,
          'child_operator' => $section,
        ];
        continue;
      }
      // Combine query.
      $querySection = $this->queryElement($section);
      $query = $querySection + $query;
    }
    return $query;
  }

  /**
   * Method to parse single section of filter rule.
   *
   * Section is a filter part which is located between letters like /, < or >.
   * @see Parser::query().
   *
   * @param string $string - Rule part string.
   *
   * @return array
   */
  protected function queryElement(string $string): array {
    $query = [];
    // Grab entity name condition.
    $ruleParts = $this->explode('@', $string);
    // Matches everything.
    if (empty($ruleParts)) {
      return [
        'entity' => '',
        'attributes' => [],
        'order' => 0,
      ];
    }
    // Validate syntax.
    if (count($ruleParts) > 2) {
      Core::error('Invalid command syntax. Single query section can\'t contain more than one separator like "@" in command: ' . $string);
    }
    // @todo: Parse order.
    $query['order'] = 0;
    // Normal section.
    if (count($ruleParts) == 2) {
      $query['entity'] = reset($ruleParts);
      $query['attributes'] = $this->queryAttributes(next($ruleParts));
      return $query;
    }
    // In case if this code is executed - we have a section with only one part.
    // And we need to figure what is in it? Condition for element or attribute?
    $rule = reset($ruleParts);
    $boolOperators = $this->explode('=,!,*,^,$', $rule);
    if (count($boolOperators) > 1) {
      $query['entity'] = '';
      $query['attributes'] = $this->queryAttributes($rule);
    }
    else {
      $query['entity'] = $rule;
      $query['attributes'] = [];
    }
    return $query;
  }

  protected function queryAttributes(string $string): array {
    $query = [];
    // Parse operator conditions.
    $operatorConditions = $this->explode('+,?', $string, TRUE);
    // Convert query parts to array of conditions.
    foreach ($operatorConditions as $position => $operatorSection) {
      // Skip operators first.
      if (in_array($operatorSection, ['+', '?'])) {
        continue;
      }
      $operatorConditions[$position] = $this->queryAttribute($operatorSection);
    }
    // Process AND conditions first, because they have higher priority.
    $last = NULL;
    foreach ($operatorConditions as $position => $operatorSection) {
      // Skip everything what is not an AND condition.
      if ($operatorSection != '+') {
        // Break join sequence.
        if ($operatorSection == '?') {
          $join = NULL;
        }
        // Add new element.
        if (isset($join)) {
          // Add new condition.
          $operatorConditions[$join]['and'][] = $operatorSection;
          // Unset processed elements.
          unset($operatorConditions[$position]);
        }
        // Set last element.
        $last = $position;
        continue;
      }
      // Parse error.
      if (!isset($last, $operatorConditions[$last]) || $operatorConditions[$last] == '?') {
        Core::error('Invalid command syntax. Unable to parse comparison operator: "' . $string);
      }
      // Join AND operators.
      if (!isset($join)) {
        $join = $position;
        // Set query parameters.
        $operatorConditions[$join] = ['and' => [$operatorConditions[$last]]];
        // Unset processed elements.
        unset($operatorConditions[$last]);
      }
    }
    // Process OR conditions.
    foreach ($operatorConditions as $operatorSection) {
      // Add new element.
      if ($operatorSection == '?') {
        continue;
      }
      // Add new condition.
      $query[] = $operatorSection;
    }
    return $query;
  }

  protected function queryAttribute(string $string): array {
    // Check comparison rule.
    $comparisonArguments = $this->explode('=,!,*,^,$', $string, TRUE);
    // Syntax error.
    if (count($comparisonArguments) != 3) {
      Core::error('Invalid command syntax. Unable to parse comparison operator: "' . $string);
    }
    // Process conditions.
    [$attribute, $comparison, $value] = array_values($comparisonArguments);
    // Parse additional conditions (in case if we should convert value to array).
    preg_match('/^".*"]"$/', $value, $matches);
    $strict = reset($matches);
    if (empty($strict)) {
      // Array AND/OR condition.
      preg_match('/^\[.*\]$/', $value, $matches);
      $isArray = reset($matches);
      // Cut off letters like: [$value].
      $value = $isArray ? mb_substr($value, 1, mb_strlen($value) - 2) : $value;
      // Set proper comparison operator.
      $comparison = $isArray ? '+' . $comparison : '?' . $comparison;
      $isArray = $isArray || mb_strpos($value, ',');
      // We should convert this value to array.
      if ($isArray) {
        $value = explode(',', $value);
      }
    }
    else {
      // Cut off letters like: "$value".
      $value = mb_substr($value, 1, mb_strlen($value) - 2);
      $comparison .= '=';
    }
    return [
      'attribute' => mb_strtolower($attribute),
      'operator' => $comparison,
      'value' => $value,
    ];
  }

  /**
   * This method works in same way as PHP native, but safer for our syntax.
   *
   * @param array|string $delimiters - Delimiters to use for explosion.
   *   For details @see Parser::findSyntaxLetter();.
   *   If you want to explode comma separated values - use native PHP function.
   * @param string $string - String to explode.
   * @param bool $include - Should we include delimiters and their position
   *   into exploded array?
   * @param bool $reverse - Return array with reversed order.
   *
   * @return array
   */
  public function explode(array|string $delimiters, string $string, bool $include = FALSE, bool $reverse = FALSE): array {
    // Always an array.
    if (is_scalar($delimiters)) {
      $delimiters = explode(',', $delimiters);
    }
    $positions = $this->findSyntaxLetters($delimiters, $string);
    $exploded = [];
    $offset = mb_strlen($string);
    $positions = array_reverse($positions, TRUE);
    foreach ($positions as $position => $delimiter) {
      $length = mb_strlen($delimiter);
      $exploded[$position + $length] = mb_substr($string, $position + $length, abs($position - $offset + $length));
      $offset = $position;
      if ($include) {
        $exploded[$position] = $delimiter;
      }
    }
    $key = isset($exploded[0]) ? '' : 0;
    $exploded[$key] = mb_substr($string, 0, $offset);
    if (!$reverse) {
      $exploded = array_reverse($exploded, TRUE);
    }
    return $exploded;
  }

  /**
   * Returns array of positions of all unprotected syntax letters of a string.
   *
   * For details @see Parser::findSyntaxLetter();.
   *
   * @param array|string $letters - List of chars. Can be an array.
   *   Also, can be a string with comma-separated values (or single value).
   * @param string $string - Command to analyse.
   *
   * @return array
   */
  public function findSyntaxLetters(array|string $letters, string $string): array {
    // Always an array.
    if (is_scalar($letters)) {
      $letters = explode(',', $letters);
    }
    // Find occurrences.
    $occurrences = [];
    foreach ($letters as $letter) {
      $positions = $this->findSyntaxLetter($letter, $string);
      $occurrences += $positions;
    }
    // Sort results and return.
    if (!empty($occurrences)) {
      ksort($occurrences);
    }
    return $occurrences;
  }

  /**
   * Returns an array of positions of unprotected syntax letters in a string.
   *
   * Unprotected means that they are not prepended with slash like "\/".
   *
   * @param string $letter - Letter to analyse.
   *   Should be one of: @ = ! * + ? / < > \
   *   Syntax like: [], {}, "", '' - should be parsed with regular expressions.
   * @param string $string - Command to analyse.
   *
   * @return array
   */
  public function findSyntaxLetter(string $letter, string $string): array {
    $scope = $this->highlightSyntax($string);
    // Look for instances.
    $offset = 0;
    $length = mb_strlen($letter);
    $positions = [];
    while (TRUE) {
      $position = mb_strpos($scope, $letter, $offset);
      if ($position === FALSE) {
        break;
      }
      $offset = $position + $length;
      $positions[$position] = $letter;
    }
    return $positions;
  }

  /**
   * Removes string parts which can't be used as parser syntax.
   *
   * In simple words - syntax normalizer.
   * @todo: I will need to write some kind of protection for data imported from game.
   *
   * @param string $string
   *
   * @return string
   */
  public function highlightSyntax(string $string): string {
    // Remove reserved Barotrauma syntax (case insensitive).
    $reserved = ['%ModDir%'];
    foreach ($reserved as $text) {
      // We will use just empty whitespace as replacement.
      $string = str_ireplace($text, $this->getWhiteSpace($string), $string);
    }
    // Remove protection letters and letters which are protected by them.
    $string = $this->removeProtectionLetters($string, TRUE);
    // Content in "", '' or [] - needs to be replaced with whitespace.
    $start = $started = FALSE;
    $whiteSpace = $trigger = '';
    for ($position = 0; $position < mb_strlen($string); $position++) {
      $letter = mb_substr($string, $position, 1);
      // Apply whitespace.
      if ($started && $letter == $trigger) {
        $started = FALSE;
        $trigger = '';
        // Check if we have something to replace.
        $length = mb_strlen($whiteSpace);
        if ($length) {
          $string = substr_replace($string, $whiteSpace, $start, $length);
        }
        continue;
      }
      // Add piece to whitespace.
      if (!empty($started)) {
        // Validate content.
        if ($letter == '[') {
          Core::error('Invalid syntax in a command. Parser do not allow to use arrays inside of arrays [[]]: ' . $string);
        }
        // We should use % as is to replace them with variables.
        if ($letter == '%') {
          $whiteSpace .= $letter;
        }
        else {
          $whiteSpace .= ' ';
        }
      }
      // Enable whitespace start.
      if (in_array($letter, ["'", '"', '['])) {
        $started = TRUE;
        $trigger = $letter == '[' ? ']' : $letter;
        $start = $position + 1;
        $whiteSpace = '';
      }
    }
    // Invalid syntax.
    if ($started) {
      Core::error('Invalid syntax in a command. Unterminated wrapper: ' . $trigger);
    }
    // Return string prepared for syntax parser.
    return $string;
  }

  /**
   * Removes protection letters: \
   *
   * @param string $string - String to modify.
   * @param bool $whitespace - Should we convert protection letters and
   *   letters which they protect to whitespace?
   *
   * @return string
   */
  public function removeProtectionLetters(string $string, bool $whitespace = FALSE): string {
    // Remove protection letters and letters which are protected by them.
    $position = 0;
    $scope = $string;
    $replacement = '  ';
    $length = $whitespace ? 2 : 1;
    while (TRUE) {
      // Check if we have something to replace.
      $offset = mb_strpos($scope, '\\');
      if ($offset === FALSE) {
        break;
      }
      $position += $offset;
      // Prepare safe replacement.
      if (!$whitespace) {
        $replacement = mb_substr($scope, $position + 1, 1);
      }
      // Apply changes to original string.
      $string = substr_replace($string, $replacement, $position, $length);
      // Reduce search scope.
      $position += $length;
      $scope = mb_substr($string, $position);
    }
    return $string;
  }

  /**
   * Replace variable tokens with variable values.
   *
   * This is a recursive function. If variables have tokens to another
   *   variables - they will get replaced too.
   *
   * @param string $string - Command to tokenize.
   *
   * @return string
   */
  public function applyVariables(string $string): string {
    // Look for possible variables.
    $positions = $this->findSyntaxLetter('%', $string);
    if (empty($positions)) {
      return $string;
    }
    // It's easier to parse from the end.
    $positions = array_reverse($positions, TRUE);
    foreach ($positions as $position => $delimiter) {
      // Process variable.
      $scope = mb_substr($string, $position);
      preg_match('/^%[a-zA-Z\d_>\-]*/', $scope, $matches);
      $token = reset($matches);
      // Leave percent chars as is (if unable to pick it as variable).
      if ($token == '%') {
        continue;
      }
      // Get variable (skip % sign).
      $keys = explode('>', mb_substr($token, 1));
      $variable = $this->bank->variable($keys);
      // Convert arrays to string.
      if (is_array($variable)) {
        $variable = implode(',', $variable);
      }
      // Every variable may contain another variable inside it.
      if (is_scalar($variable)) {
        // Recursive replacement.
        $variable = $this->applyVariables(strval($variable));
      }
      // Apply changes.
      $string = substr_replace($string, $variable, $position, mb_strlen($token));
    }
    return $string;
  }

  /**
   * Converts any string to whitespace with same length.
   *
   * @param string $string - String to convert.
   *
   * @return string
   */
  public function getWhiteSpace(string $string): string {
    return str_repeat(' ', mb_strlen($string));
  }

}