<?php
/**
 * @file
 * Core service class definition.
 */


namespace Barotraumix\Generator;

class Core {

	/**
	 * Function to create core service.
	 */
	public function __construct() {
	}

	/**
	 * Path to the game.
	 */
	public function pathGame() {
		// @todo: Manage this as setting.
		return 'C:\Program Files (x86)\Steam\steamapps\common\Barotrauma';
	}

	/**
	 * Path to the mods from workshop.
	 */
	public function pathMods() {
		// @todo: Manage this as setting.
		return 'C:\Users\Professional\AppData\Local\Daedalic Entertainment GmbH\Barotrauma\WorkshopMods\Installed';
	}

	/**
	 * Path to export generated mod.
	 */
	public function pathOutput() {
		// @todo: Manage this as setting.
		return 'C:\Program Files (x86)\Steam\steamapps\common\Barotrauma\LocalMods\[DS] Gather Resources Quickly';
	}

	/**
	 * Function to send message to console.
	 *
	 * @param $msg - Message to send. Might be some variable.
	 */
	public function log($msg = NULL, $level = 3) {
		// Null.
		if (!isset($msg)) {
			$msg = $GLOBALS;
		}
		// Object.
		if (is_object($msg)) {
			$msg = (array) $msg;
		}
		// Array.
		if (is_array($msg)) {
			$msg = print_r($msg, TRUE);
		}
		// String.
		$msg = strval($msg);
		// Print.
		print $msg;
	}

}