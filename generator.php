<?php
/**
 * @file
 * File executable from bash.
 *
 * @todo: Description.
 */

require 'vendor/autoload.php';

use Barotraumix\Generator\Core;

$core = new Core(__DIR__);
$buildId = $core->init();
$scanner = $core->scan(Core::BAROTRAUMA_APP_ID, $buildId);
$core->prepareStatistic();
$scanner->items();
Core::debug('Done!');