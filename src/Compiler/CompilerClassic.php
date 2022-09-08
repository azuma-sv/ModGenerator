<?php

/**
 * @file
 * XML Compiler class.
 */

namespace Barotraumix\Framework\Compiler;

use Barotraumix\Framework\Compiler\Parser\Parser;
use Barotraumix\Framework\Compiler\Parser\F;
use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Services\Framework;
use Barotraumix\Framework\Services\Services;
use Barotraumix\Framework\Services\Key;
use SimpleXMLElement;
use DOMDocument;

/**
 * Class definition.
 */
class CompilerClassic implements CompilerInterface {

  /**
   * Method to trigger mod building.
   *
   * @todo: Refactor.
   *
   * @return bool
   */
  public function doCompile(): bool {
    // Get mod data.
    $database = Services::$database;
    $modData = $database->modSources();
    $settingsPrimary = reset($modData);
    unset($modData[key($modData)]);
    $settingsPrimary = $this->processDefaultSettings($settingsPrimary, TRUE);
    // Import variables.
    $database->addVariables($settingsPrimary[Key::VARS], TRUE);
    // Execute initialization hook.
    $this->execute($settingsPrimary[Key::EXEC]);
    // Walk through files.
    foreach ($modData as $settings) {
      $settings = $this->processDefaultSettings($settings);
      // Import variables.
      $database->addVariables($settings[Key::VARS]);
      // Execute.
      $this->execute($settings[Key::EXEC]);
    }
    // Prepare directory.
    $path = Framework::pathOutput($settingsPrimary[Key::FOLDER]);
    // Remove existing data.
    if (file_exists($path) && !Framework::removeDirectory($path)) {
      Framework::error('Unable to remove existing mod directory: ' . $path);
    }
    // Attempt to create a new one.
    if (!mkdir($path, 0777, TRUE)) {
      Framework::error('Unable to create mod directory: ' . $path);
    }
    // Prepare build.
    $this->build($path);
    return TRUE;
  }

  /**
   * Method to execute commands for specific context (wrap recursive function).
   *
   * @param array $commands - Array of commands to execute.
   * @param array|NULL $context - Context scope.
   *
   * @return void
   */
  public function execute(array $commands, array $context = NULL): void {
    // Check each command.
    foreach ($commands as $rawCommand => $data) {
      // Prepare variables.
      $command = Parser::applyVariables($rawCommand);
      if (is_string($data)) {
        $data = Parser::applyVariables($data);
      }
      // Check if this is a function.
      if (F::isFn($command)) {
        F::function($command, $data, $context);
        continue;
      }
      // @todo: Implement way to create new element.
      // In case if we need to filter context scope.
      if (Parser::isQuery($command)) {
        // Validate data.
        if (!is_array($data)) {
          Framework::notice('Wrong command input. Value is given "' . $data . '", when array is expected. Command: ' . $command);
        }
        // Ensure that objects are cloned.
        $clone = !isset($context) || is_scalar(reset($context));
        // Build our context.
        $filtered = F::query($command, $context, $clone);
        // Skip empty context.
        if (empty($filtered)) {
          Framework::notice('Empty context for command: ' . $command);
          continue;
        }
        // Error for wrong context type.
        if (!is_array($filtered)) {
          Framework::notice('Wrong context type. Context is not an array for command: ' . $command);
          continue;
        }
        // Run recursive commands.
        $this->execute($data, $filtered);
        continue;
      }
      // If we have bypassed all conditions above - it must be just a tag value.
      // @todo: Implode arrays to comma separated values.
      if (!is_scalar($data)) {
        Framework::error('Mod command syntax error. Unable to set value for attribute: ' . $command);
      }
      // If context is empty - apply to EVERYTHING O_O
      if (!isset($context)) {
        // Build our context.
        $context = F::query('');
      }
      foreach ($context as $entity) {
        if ($entity instanceof BaroEntity) {
          if ($command == 'name') {
            $a = 1;
          }
          $entity->setAttribute($command, $data);
        }
      }
    }
  }

  /**
   * Method to build mod structure from database.
   *
   * @param string $path - Path to export mod.
   *
   * @return void
   */
  protected function build(string $path): void {
    $build = [];
    $types = [];
    $count = [];
    $validate = [];
    $context = Services::$database->getContext();
    $modData = Services::$database->modSources();
    $settingsPrimary = $this->processDefaultSettings(reset($modData), TRUE);
    // Prepare info.
    /** @var BaroEntity $entity */
    foreach ($context as $entity) {
      // Skip entities which we don't need to export.
      if (!$entity->isModified()) {
        continue;
      }
      $file = $entity->file();
      $type = $entity->type();
      if (!isset($build[$file])) {
        $build[$file] = '';
        $types[$file] = $type;
        $count[$file] = 0;
      }
      // Validate storage.
      $validate[$file][$type] = $type;
      if (count($validate[$file]) > 1) {
        Framework::error('We can not store multiple entities of different type (' . $type . ') in single file: ' . $file);
      }
      // Prepare entity.
      $build[$file] .= $entity->toXML();
      $count[$file]++;
      // @todo: Do the job with entity files (images, sounds etc.).
    }
    // Prepare content package.
    $attributes = array_filter(
      $settingsPrimary,
      fn ($key) => in_array($key, explode(',', 'name,modversion,gameversion,corepackage,altnames')),
      ARRAY_FILTER_USE_KEY
    );
    $contentPackage = new BaroEntity('ContentPackage', $attributes, Framework::CONTEXT, Framework::PRIMARY_MOD_FILE);
    foreach ($build as $file => $item) {
      $asset = new BaroEntity($types[$file], ['file' => "%ModDir%/$file"], Framework::CONTEXT, Framework::PRIMARY_MOD_FILE, $contentPackage);
      $contentPackage->addChild($asset);
    }
    $primaryModFile = Services::gameLikePathToContentPackage(Framework::CONTEXT);
    $build[$primaryModFile] = $contentPackage->toXML();
    // @todo: Multiple content packages?
    $count[$primaryModFile] = 1;
    // Prepare XML objects.
    foreach ($build as $file => $data) {
      // Provide wrapping tag.
      $wrapper = '';
      if ($count[$file] > 1) {
        $wrapper = $types[$file] . 's';
      }
      // Wrap content.
      $content = empty($wrapper) ? $data : "<$wrapper>$data</$wrapper>";
      // @todo: Implement Override behavior.
      if ($file == $primaryModFile) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
      }
      else {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Override>' . $content . '</Override>';
      }
      $xml = new SimpleXMLElement($xml);
      // Validate directory.
      if (!Framework::prepareDirectory("$path/$file", TRUE)) {
        Framework::error('Unable to create directory: ' . $path);
      }
      // Beautify XML
      // @todo: Refactor.
      $dom = new DOMDocument("1.0");
      $dom->preserveWhiteSpace = false;
      $dom->formatOutput = true;
      $dom->loadXML($xml->asXML());
      // Create a file.
      file_put_contents("$path/$file", $dom->saveXML());
    }
  }

  /**
   * Method to process default settings of imported mod file.
   *
   * @todo: Ability to split mod into multiple smaller mods.
   * @todo: Ability to generate multiple content packages at once.
   *
   * @param array $settings - Settings array.
   * @param bool $primary - Indicates that it's primary module file.
   *
   * @return array
   */
  protected function processDefaultSettings(array $settings, bool $primary = FALSE): array {
    // In case if it's primary module file.
    if ($primary) {
      if (empty($settings['name'])) {
        Framework::error('Module name can\'t be empty');
      }
      if (empty($settings[Key::FOLDER])) {
        $settings[Key::FOLDER] = $settings['name'];
      }
      // Mod version.
      if (!isset($settings['modversion'])) {
        $settings['modversion'] = '1.0.0';
      }
      // Game version.
      if (!isset($settings['gameversion'])) {
        // @todo: Implement order operator.
        $results = F::query('ContentPackage@name="Vanilla">gameversion');
        $settings['gameversion'] = reset($results);
      }
      // Core package attribute.
      if (!isset($settings['corepackage'])) {
        $settings['corepackage'] = FALSE;
      }
    }
    // Mod variables.
    if (!isset($settings[Key::VARS])) {
      $settings[Key::VARS] = [];
    }
    // Execute hooks.
    if (!isset($settings[Key::EXEC])) {
      $settings[Key::EXEC] = [];
    }
    return $settings;
  }

}