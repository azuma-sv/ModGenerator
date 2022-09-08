<?php
/**
 * @file
 * This file can be executed from console.
 */

require 'vendor/autoload.php';

use Barotraumix\Framework\Core;

define("BASE_PATH", $_ENV['BASE_PATH'] ?? __DIR__);
$core = Core::create();
$core->initFramework();
$core->steamUpdateGameAndMods();
$core->importSourcesToDatabase();
$core->compile();
$core::$services::$framework::debug('Done!');