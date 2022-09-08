<?php
/**
 * @file
 * Interface which will contain predefined keys for mod source files.
 */

namespace Barotraumix\Framework\Services;

/**
 * Interface definition.
 */
interface Key {

  /**
   * Name of the folder for compiled mod.
   */
  const FOLDER = 'folder';

  /**
   * Variables.
   */
  const VARS = 'variables';

  /**
   * Key which contains list of integrated mods.
   */
  const MODS = 'order';

  /**
   * Commands to execute.
   */
  const EXEC = 'execute';

}