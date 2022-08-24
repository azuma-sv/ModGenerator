<?php

/**
 * @file
 * Interface for Barotrauma parser object.
 *
 * @todo: Implement and use parser interface.
 */

namespace Barotraumix\Generator\Parser;

use Barotraumix\Generator\BaroEntity\SanitizedXMLData;

/**
 * Interface ParserInterface.
 */
interface ParserInterface {

    /**
     * Get all data from parser (file).
     *
     * @return SanitizedXMLData
     */
    public function sanitizedXMLData():SanitizedXMLData;

}
