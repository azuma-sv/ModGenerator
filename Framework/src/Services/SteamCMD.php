<?php

/**
 * @file
 * Source code of the service to connect with SteamCMD.
 *
 * Helps to establish connections with SteamCMD and to perform
 *   downloads/updates.
 */

namespace Barotraumix\Framework\Services;

/**
 * Class SteamCMD.
 */
class SteamCMD {

  /**
   * Method to install barotrauma.
   *
   * @return int|bool|NULL
   *  In case of integer - new build id has been provided.
   *  FALSE - Application is already installed.
   */
  public function install(): int|bool|null {
    // Prepare directory.
    $path = API::pathGame();
    if (!API::prepareDirectory($path)) {
      API::error('Unable to create directory for Barotrauma source files');
    }
    // Get the real path to the folder and run the command.
    $id = API::APP_ID;
    $command = "+app_update $id validate";
    $preLogin = "+force_install_dir $path";
    $output = $this->run($command, TRUE, $preLogin);
    // Parse output with regular expression.
    $regexp = "/Success! App '$id' fully installed./";
    preg_match($regexp, $output, $matches, PREG_OFFSET_CAPTURE);
    $status = !empty($matches[0][0]);
    // Log error.
    if (!$status) {
      API::error('SteamCMD has executed an update, but we haven\'t received "success" message.');
    }
    return $id;
  }

  /**
   * Method to run SteamCMD commands.
   *
   * @param string $command
   *  Command to run. Can't work with double quote sign.
   *
   * @param bool $login
   *  Will log in as anonymous user.
   *
   * @param string $preLoginCommand
   *  Set of commands which need to be executed before logging in.
   *
   * @return bool|string|NULL
   */
  protected function run(string $command, bool $login = TRUE, string $preLoginCommand = ''): bool|string|NULL {
    // Prepare command, it's prefix and suffix.
    $prefix = 'steamcmd +@ShutdownOnFailedCommand 1 +@NoPromptForPassword 1 ';
    $login = $login ? ' +login anonymous ' : '';
    $suffix = ' +quit';
    $command = $prefix . $preLoginCommand . $login . $command . $suffix;

    // Run command and save data about that for debugging purpose.
    $output = shell_exec($command);
    API::debug($output);
    API::debug('^^^ SteamCMD has executed command: ' . $command);
    return $output;
  }

}
