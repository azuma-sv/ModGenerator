<?php

/**
 * @file
 * Class to manipulate with Barotraumix Prices entity.
 */

namespace Barotraumix\Generator\BaroEntity\Entity;

use Barotraumix\Generator\BaroEntity\Base;
use Barotraumix\Generator\Core;

/**
 * Class Asset.
 */
class Prices extends BaseEntity {

    /**
     * @inheritDoc
     */
    public function createChild(Base $child): null|bool|Base {
        $newChild = NULL;
        switch ($child->getName()) {
            case 'Price':
                $newChild = Price::createFrom($child, $this->services());
                break;
            default:
                Core::error('This case needs attention. Item child element is not recognized: ' . $child->getName());
                break;
        }
        return $newChild;
    }

}
