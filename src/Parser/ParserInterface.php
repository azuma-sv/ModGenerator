<?php

/**
 * @file
 * Interface for Barotrauma parser object.
 *
 * @todo: Implement and use parser interface.
 */

namespace Barotraumix\Framework\Parser;

/**
 * Interface ParserInterface.
 */
interface ParserInterface {

    /**
     * Get all data from parser (file).
     *
     * @return array|NULL
     */
    public function doParse(): array|NULL;

}
