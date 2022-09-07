<?php

/**
 * @file
 * Source code of the service to connect with SteamCMD.
 *
 * Helps to establish connections with SteamCMD and to perform
 *   downloads/updates.
 */

namespace Barotraumix\Generator\Services;

use Barotraumix\Generator\Core;

/**
 * Class SteamCMD.
 */
class SteamCMD {

  /**
   * Method to get current Build ID of specific app from Steam.
   *
   * @param int $appId
   *  ID of the application. Barotrauma App ID will be used as default value.
   *
   * @return null|string
   */
  public function buildId(int $appId = Core::BAROTRAUMA_APP_ID): null|string {
    // Attempt to get build id.
    $command = "+app_info_update 1 +app_info_print '$appId'";
    $buildId = $this->parseBuildId($this->run($command));
    // Log errors.
    if (empty($buildId)) {
      $appName = ($appId == Core::BAROTRAUMA_APP_ID) ? Core::BAROTRAUMA_APP_NAME : "the app: $appId";
      $message = $this->prepareSteamLog("Failed to get Build ID of $appName");
      Core::error($message);
    }
    return $buildId;
  }

  /**
   * Method to install specific app.
   *
   * @param int|NULL $appId
   *  ID of the application. Barotrauma App ID will be used as default value.
   *
   * @return int|bool|NULL
   *  In case of integer - new build id has been provided.
   *  FALSE - Application is already installed.
   *  NULL - Error occurred.
   */
  public function appInstall(int $appId = NULL): int|bool|null {
    // Default value for appId.
    $appId = $appId ?? Core::BAROTRAUMA_APP_ID;
    // Get app build id from Steam.
    $buildId = $this->buildId($appId);
    // String which will help to log errors.
    $appName = ($appId == Core::BAROTRAUMA_APP_ID) ? Core::BAROTRAUMA_APP_NAME : "the app: $appId";

    // Log errors if build id is not available.
    if (empty($buildId)) {
      $message = "Failed to install $appName, can't get Build ID from Steam.";
      Core::error($message);
      return NULL;
    }
    // Append build id for logs.
    $appName .= " (build id: $buildId)";

    // Prepare directory.
    if (!Framework::prepareDirectory(Framework::pathGame("$appId/$buildId"))) {
      $message = "Failed to install $appName, because of wrong file permissions.";
      Core::error($message);
      return NULL;
    }

    // Finally run SteamCMD.
    $status = $this->runUpdate($appId, $buildId);
    if ($status) {
      return $buildId;
    }
    return NULL;
  }

  /**
   * Last executed command.
   *
   * @param string|NULL $command - Last command.
   *
   * @return null|string
   */
  public function lastCommand(string $command = NULL): null|string {
    static $lastCommand;
    if (isset($command)) {
      $lastCommand = $command;
    }
    return $lastCommand;
  }

  /**
   * Last executed command output.
   *
   * @param string|NULL $output - Last command output.
   *
   * @return mixed
   */
  public function lastCommandOutput(string $output = NULL): mixed {
    static $lastCommandOutput;
    if (isset($output)) {
      $lastCommandOutput = $output;
    }
    return $lastCommandOutput;
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
   * @return mixed
   */
  protected function run(string $command, bool $login = TRUE, string $preLoginCommand = ''): mixed {
    // Prepare command, it's prefix and suffix.
    $prefix = 'steamcmd +@ShutdownOnFailedCommand 1 +@NoPromptForPassword 1 ';
    $login = $login ? ' +login anonymous ' : '';
    $suffix = ' +quit';
    $command = $prefix . $preLoginCommand . $login . $command . $suffix;

    // Check for cache.
    $cached = $this->cacheGet($command);
    if (isset($cached)) {
      return $cached;
    }

    // Run command and save data about that for debugging purpose.
    $this->lastCommand($command);
    $output = shell_exec($command);
    $this->lastCommandOutput($output);
    $message = $this->prepareSteamLog('SteamCMD has been executed.');
    Core::debug($message);

    // Use cache.
    $this->cacheSet($command, $this->lastCommandOutput());
    return $this->cacheGet($command);
  }

  /**
   * Direct method to update application.
   *
   * @param int $appId - ID of the app in Steam.
   * @param int $buildId - Build id of the app in Steam.
   *
   * @return bool
   *  Update status.
   */
  protected function runUpdate(int $appId, int $buildId): bool {
    // Get the real path to the folder and run the command.
    $path = Framework::pathGame("$appId/$buildId");
    $command = "+app_update $appId validate";
    $preLogin = "+force_install_dir $path";
    $output = $this->run($command, TRUE, $preLogin);

    // Parse output with regular expression.
    $regexp = "/Success! App '$appId' fully installed./";
    preg_match($regexp, $output, $matches, PREG_OFFSET_CAPTURE);
    $status = !empty($matches[0][0]);

    // Log error.
    if (!$status) {
      $message = $this->prepareSteamLog('SteamCMD has executed an update, but we haven\'t received "success" message.');
      Core::error($message);
    }

    // Return status.
    return $status;
  }

  /**
   * Static cache storage.
   *
   * This function will help us to avoid cases when two similar requests were
   * made during single PHP run.
   *
   * @return array
   *  Array with cached data.
   */
  protected function &staticCache(): array {
    // Define static cache.
    static $cache = [];
    return $cache;
  }

  /**
   * Set cache for specific command.
   *
   * @param string $command
   *  Exact cached command.
   * @param mixed $value
   *  Will set into cache if value is provisioned. Also, it might return cached
   *   data otherwise.
   */
  protected function cacheSet(string $command, mixed $value): void {
    $cache = &$this->staticCache();
    // We can cache NULL.
    $value = !isset($value) ? FALSE : $value;
    $cache[$command] = $value;
  }

  /**
   * Get cache for specific command.
   *
   * @param string $command
   *  Exact cached command.
   *
   * @return mixed
   *   Cached data.
   */
  protected function cacheGet(string $command): mixed {
    $cache = &$this->staticCache();
    return $cache[$command] ?? NULL;
  }

  /**
   * Method to prepare debugging information for log.
   *
   * @param string $message - Main message for reviewer.
   *
   * @return string - Prepared message.
   */
  protected function prepareSteamLog(string $message): string {
    $lastCommand = "Last command: " . $this->lastCommand() . " ";
    $lastCommandOutput = "Output: \r\n" . $this->lastCommandOutput();
    return "\r\n$lastCommand\r\n$lastCommandOutput\r\n$message";
  }

  /**
   * This method will parse response from SteamCMD to provide Build ID of the
   * app.
   *
   * @param mixed $response
   *  Response which came to us form SteamCMD.
   *
   * @return null|int
   *   Numeric build id or nothing.
   */
  protected function parseBuildId(mixed $response): null|int {
    // Reject unknown responses.
    if (!is_string($response)) {
      return NULL;
    }
    // Parse with regular expression.
    $regexp = '/"branches"\s*{\s*"public"\s*{\s*"buildid"\s*"(?<buildid>\d*)"/ms';
    preg_match_all($regexp, $response, $matches, PREG_SET_ORDER);
    // Return numeric build id value or NULL.
    $data = isset($matches[0]['buildid']) ? intval($matches[0]['buildid']) : NULL;
    return empty($data) ? NULL : $data;
  }

}
