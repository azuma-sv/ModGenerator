<?php

/**
 * @file
 * Objects which use Drupal services container should use this trait.
 */

namespace Barotraumix\Generator\BaroEntity\Property;

use Barotraumix\Generator\Core;
use Barotraumix\Generator\Services;

/**
 * Trait definition.
 */
trait ServicesHolder {

    /**
     * @var Services $services - Injected services container.
     */
    protected Services $services;

    /**
     * Services container.
     *
     * @return Services
     */
    public function services():Services {
        return $this->services;
    }

    /**
     * Inject services object.
     *
     * @param Services $services - Services object.
     *
     * @return void
     */
    public function setServices(Services $services):void {
        // Set services if they don't exist.
        if (isset($this->services)) {
            Core::error('Container was already set.');
        }
        $this->services = $services;
    }

}
