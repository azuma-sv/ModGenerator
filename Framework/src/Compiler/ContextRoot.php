<?php
/**
 * @file
 * This type of context will contain only root entities.
 */

namespace Barotraumix\Framework\Compiler;

use Barotraumix\Framework\Entity\RootEntity;
use Barotraumix\Framework\Services\API;

/**
 * Class definition.
 */
class ContextRoot extends Context {

  /**
   * @var string|NULL - Type of contained data.
   */
  protected string|NULL $dataType = 'object';

  /**
   * @var bool|null - If context contains BaroEntities - we should know if they
   *   are root entities.
   */
  protected bool|NULL $isRoot = TRUE;

  /**
   * Class constructor.
   *
   * @param string|int $id - Context name.
   */
  public function __construct(string|int $id) {
    $this->setID($id);
  }

  /**
   * Value validation callback. May throw an error.
   *
   * Validates if added value may be stored in this context.
   *
   * @param mixed $value - Value to validate.
   *
   * @return void
   */
  protected function validateValue(mixed $value): void {
    if (!$value instanceof RootEntity) {
      API::error("Attempt to insert NON-ROOT entity into the context which contains ONLY ROOT entities.");
    }
  }

}