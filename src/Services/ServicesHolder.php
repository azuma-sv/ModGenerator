<?php

/**
 * @file
 * Objects which use Drupal services container should use this trait.
 */

namespace Barotraumix\Generator\Services;

/**
 * Trait definition.
 */
trait ServicesHolder {

    /**
     * @var Services $services - Injected services.
     */
    protected Services $services;

    /**
     * Services container.
     *
     * @return \Barotraumix\Generator\Services\Services
     */
    public function services(): Services {
        return $this->services;
    }

    /**
     * Inject services object.
     *
     * @param Services $services - Services object.
     *
     * @return void
     */
    public function setServices(Services $services): void {
        $this->services = $services;
    }

}
