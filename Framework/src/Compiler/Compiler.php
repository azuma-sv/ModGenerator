<?php

/**
 * @file
 * XML Compiler class.
 */

namespace Barotraumix\Framework\Compiler;

use Barotraumix\Framework\Entity\Property\ID;
use Barotraumix\Framework\Entity\RootEntity;
use Barotraumix\Framework\Entity\BaroEntity;
use Barotraumix\Framework\Services\Database;
use Barotraumix\Framework\Entity\Element;
use Barotraumix\Framework\Services\API;
use Barotraumix\Framework\Core;
use SimpleXMLElement;
use Symfony\Component\Yaml\Yaml;

/**
 * Class definition.
 */
class Compiler {

  /**
   * Use ID.
   */
  use ID;

  /**
   * @var Database - Mod database.
   */
  protected Database $database;

  /**
   * @var array Mod data.
   */
  protected array $modData;

  /**
   * @var RootEntity - Content package object for this mod.
   *
   * @todo: Ability to handle multiple content packages.
   */
  protected RootEntity $contentPackage;

  /**
   * Class constructor.
   *
   * @param string $mod - Mod to compile.
   * @todo: Maybe database?
   */
  public function __construct(string $mod) {
    $this->setID($mod);
    $this->database = Core::get()->database($this->id());
  }

  /**
   * Method to trigger mod building.
   */
  public function doCompile(): void {
    $db = $this->database();
    // Get mod data.
    $data = $db->modData();
    $modData = $this->processDefaultSettings($data, TRUE);
    $this->modData[] = $modData;
    // Execute main module file.
    $db->variableAddMultiple($modData['variables'], TRUE);
    $this->contentPackage();
    $this->execute($modData['execute'], $db->context());
    // Run includes.
    foreach ($this->includes() as $include) {
      $db->variablesResetLocal();
      $db->variableAddMultiple($include['variables']);
      $this->execute($include['execute'], $db->context());
    }
    // Prepare directory.
    $path = API::pathOutput($modData['folder']);
    if (!API::removeDirectory($path)) {
      API::error('Unable to remove existing mod directory: ' . $path);
    }
    if (!mkdir($path, 0777, TRUE)) {
      API::error('Unable to create mod directory: ' . $path);
    }
    // Prepare build.
    $this->build($path);
    API::debug("Mod: '$this->id' is DONE!");
  }

  /**
   * Method to execute commands for specific context (wrap recursive function).
   *
   * @param array $commands - Array of commands to execute.
   * @param Context $context - Context object.
   *
   * @return void
   */
  public function execute(array $commands, Context $context): void {
    // Check each command.
    foreach ($commands as $rawCommand => $data) {
      // Prepare variables.
      // @todo: Refactor. Parser shouldn't apply variables.
      $command = Parser::applyVariables($rawCommand, $this->database());
      if (is_string($data)) {
        $data = Parser::applyVariables($data, $this->database(), TRUE);
      }
      // @todo: Inline functions.
      // Check if this is a function.
      if (Functions::isFn($command)) {
        Functions::function($this, $command, $data, $context);
        continue;
      }
      // In case if we need to filter context scope.
      if (Parser::isQuery($command)) {
        // Validate data.
        if (!is_array($data)) {
          API::notice('Wrong command input. Scalar value is given "' . $data . '", when array is expected. Command: ' . $command);
        }
        // Build our context.
        $filtered = $this->filter($command, $context);
        // Skip empty context.
        if ($filtered->isEmpty()) {
          API::notice('Empty context for command: ' . $command);
          continue;
        }
        // Run recursive commands.
        $this->execute($data, $filtered);
        continue;
      }
      // If we have bypassed all conditions above - it must be just a tag value.
      // @todo: Implode arrays to comma separated values.
      if (!is_scalar($data)) {
        API::error('Mod command syntax error. Unable to set value for attribute: ' . $command);
      }
      foreach ($context as $entity) {
        if ($entity instanceof BaroEntity) {
          $entity->setAttribute($command, $data);
        }
      }
    }
  }

  /**
   * Creates or returns content package of the mod.
   *
   * @todo: Ability to handle multiple content packages.
   *
   * @return RootEntity
   */
  public function contentPackage(): RootEntity {
    if (isset($this->contentPackage)) {
      return $this->contentPackage;
    }
    $db = $this->database();
    $attributes = array_filter(
      $this->processDefaultSettings($db->modData(), TRUE),
      fn ($key) => in_array($key, explode(',', 'name,modversion,gameversion,corepackage,altnames')),
      ARRAY_FILTER_USE_KEY
    );
    $this->contentPackage = new RootEntity('ContentPackage', $attributes, 'ContentPackage', $this->id(), 'filelist');
    $db->context()->add($this->contentPackage);
    return $this->contentPackage;
  }

  /**
   * Method to build mod structure from database.
   *
   * @todo: This shit needs refactoring, some day...
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
    $entityFiles = [];
    $context = $this->database()->context();
    $settingsPrimary = reset($this->modData);
    // Prepare info.
    /** @var RootEntity $entity */
    foreach ($context as $entity) {
      // Skip entities which we don't need to export.
      if (!$entity->isModified()) {
        continue;
      }
      $file = $entity->override() && !$settingsPrimary['corepackage'] ? $entity->file() . '.override' : $entity->file();
      $type = $entity->type();
      $replacements = $this->database()->contextNames();
      $file = str_replace(array_keys($replacements), array_values($replacements), $file);
      if (!isset($build[$file])) {
        $build[$file] = '';
        $types[$file] = $type;
        $count[$file] = 0;
      }
      // Validate storage.
      $validate[$file][$type] = $type;
      if (count($validate[$file]) > 1) {
        API::error('We can not store multiple entities of different type (' . $type . ') in single file: ' . $file);
      }
      // Process images.
      foreach ($entity->sprites($replacements[$entity->appID()]) as $entityFile => $sprites) {
        if (stripos($sprites['ORIGINAL'], '%ModDir%') !== FALSE && !in_array($entityFile, $entityFiles)) {
          // @todo: Ability to cut images into smaller pieces.
          $entityFiles[$entityFile] = $path . '/' . $sprites['ACTIVE'];
        }
      }
      // Prepare entity.
      $build[$file] .= $entity->toXML();
      $count[$file]++;
      // @todo: Do the job with entity files (images, sounds etc.).
    }
    // Prepare content package.
    $primaryModFile = 'filelist';
    $contentPackage = $this->contentPackage();
    foreach ($build as $file => $item) {
      $asset = new Element($types[$file], ['file' => "%ModDir%/$file.xml"], $contentPackage);
      $contentPackage->addChild($asset);
    }
    $build[$primaryModFile] = $contentPackage->toXML();
    // @todo: Multiple content packages?
    $count[$primaryModFile] = 1;
    $types[$primaryModFile] = 'ContentPackage';
    // Prepare XML objects.
    foreach ($build as $file => $data) {
      // Provide wrapping tag.
      $wrapper = API::getMainWrapper($types[$file]);
      if (!isset($wrapper)) {
        API::error('Unable to write non-XML file for asset: ' . $types[$file] . ' file: ' . $file);
      }
      // Wrap content.
      $content = (empty($wrapper) || $count[$file] == 1) ? $data : "<$wrapper>$data</$wrapper>";
      $override = str_ends_with($file, '.override');
      if ($override) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Override>' . $content . '</Override>';
      }
      else {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
      }
      $xml = new SimpleXMLElement($xml);
      // Validate directory.
      if (!API::prepareDirectory("$path/$file", TRUE)) {
        API::error('Unable to create directory: ' . $path);
      }
      // Beautify XML.
      $dom = API::dom();
      $dom->loadXML($xml->asXML());
      // Create a file.
      file_put_contents("$path/$file.xml", $dom->saveXML());
    }
    // Copy additional files too.
    foreach ($entityFiles as $from => $to) {
      if (!(API::prepareDirectory($to, TRUE) && copy($from, $to))) {
        API::error("Unable to copy file from '$from' to '$to'.");
      }
    }
  }

  /**
   * Method to query the database by a given query string.
   *
   * @param string $query - Query string. Empty string to query EVERYTHING.
   * @param string|NULL $contextName - Name of the context to query.
   *  NULL - Will query an active context.
   *
   * @return Context
   */
  public function query(string $query = '', string $contextName = NULL): Context {
    return $this->database()->query(Parser::query($query), $contextName);
  }

  /**
   * Method to filter context by a given query.
   *
   * @param string $query - Query string.
   * @param Context|string|NULL $context - Context object. Or name to use DB.
   *  Will use database active context in the case if context is not provided.
   *
   * @return Context
   */
  public function filter(string $query = '', Context|string $context = NULL): Context {
    if ($context instanceof Context) {
      return $context->query(Parser::query($query));
    }
    return $this->query($query, $context);
  }

  /**
   * Method to return database of this compiler.
   *
   * @return Database
   */
  public function database(): Database {
    return $this->database;
  }

  /**
   * Method to prepare included files.
   *
   * @return array
   */
  protected function includes(): array {
    $includes = [];
    $modData = $this->processDefaultSettings($this->database()->modData(), TRUE);
    foreach ($modData['includes'] as $include) {
      $path = API::pathInput($include, $this->id());
      if (is_dir($include) || !file_exists($path)) {
        API::notice("File '$include' doesn't exists");
        continue;
      }
      $parsed = Yaml::parseFile($path);
      if (!empty($parsed)) {
        $includes[$include] = $this->processDefaultSettings($parsed);
      }
    }
    return $includes;
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
        API::error('Module name can\'t be empty');
      }
      if (empty($settings['folder'])) {
        $settings['folder'] = $settings['name'];
      }
      // Mod version.
      if (!isset($settings['modversion'])) {
        $settings['modversion'] = '1.0.0';
      }
      // Game version.
      if (!isset($settings['gameversion'])) {
        // @todo: Implement order operator.
        $context = $this->query('ContentPackage@name="Vanilla">gameversion');
        $settings['gameversion'] = $context->current();
      }
      // Core package attribute.
      if (!isset($settings['corepackage'])) {
        $settings['corepackage'] = FALSE;
      }
      // Core package attribute.
      if (!isset($settings['translations'])) {
        $settings['translations'] = FALSE;
      }
      // Core package attribute.
      if (!isset($settings['includes'])) {
        $settings['includes'] = [];
      }
    }
    // Mod variables.
    if (!isset($settings['variables'])) {
      $settings['variables'] = [];
    }
    // Execute hooks.
    if (!isset($settings['execute'])) {
      $settings['execute'] = [];
    }
    return $settings;
  }

}