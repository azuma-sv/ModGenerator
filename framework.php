<?php
/**
 * @file
 * This file can be executed from console.
 */

require 'vendor/autoload.php';

use Barotraumix\Generator\Core;

// @todo: Move to environment variable.
const BASE_PATH = __DIR__;
$core = Core::create();
$core->initFramework();
$core->steamUpdateGameAndMods();
$core->importSourcesToDatabase();
$core->compile();
$core::services()::framework()::debug('Done!');