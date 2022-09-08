<?php

/**
 * @file
 * Interface for Barotrauma compiler service.
 */

namespace Barotraumix\Framework\Compiler;

/**
 * Interface ParserInterface.
 */
interface CompilerInterface {

  /**
   * Get all data from parser (file).
   *
   * @return bool
   */
  public function doCompile(): bool;

}
