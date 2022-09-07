<?php

/**
 * @file
 * Interface for Barotrauma parser object.
 *
 * @todo: Implement and use parser interface.
 */

namespace Barotraumix\Generator\Builder;

/**
 * Interface ParserInterface.
 */
interface BuilderInterface {

  /**
   * @const string - Contains a string to reference name of the mod which we are generating.
   */
  const CONTEXT_SELF = 'SELF';

  /**
   * Get all data from parser (file).
   *
   * @return bool
   */
  public function doBuild():bool;

}
