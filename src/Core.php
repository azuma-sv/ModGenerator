<?php
/**
 * @file
 * Core service class definition.
 */

namespace Barotraumix\Generator;

use Symfony\Component\Yaml\Yaml;

/**
 * Class definition.
 */
class Core {

  /**
   * @const array - Variable for development process. Contains list of currently
   *   implemented assets.
   */
  const __TYPES = ['Item'];

  /**
   * @var array Settings for generator.
   */
  protected array $settings = [];

  /**
   * @var SteamCMD SteamCMD connector service.
   */
  protected SteamCMD $steam;

  /**
   * @var array Array with scanners.
   */
  protected array $scanners;

  /**
   * @const BAROTRAUMA_APP_ID - Contains barotrauma app ID in steam.
   */
  const BAROTRAUMA_APP_ID = 1026340;

  /**
   * Function to create core service.
   *
   * @param string $dir - Root directory.
   */
  public function __construct(string $dir) {
    $this->steam = new SteamCMD($this);
    $this->settings = Yaml::parseFile('settings.yml');
    $this->settings['root'] = $dir;
  }

  /**
   * Method to initialize mod generator.
   *
   * @return NULL|bool|int
   */
  public function init(): null|bool|int {
    return $this->steam->appInstall(Core::BAROTRAUMA_APP_ID);
  }

  /**
   * Path to the game.
   */
  public function pathGame(): string {
    return $this->pathPrepare($this->settings['files']['game']);
  }

  /**
   * Path to the mods from workshop.
   */
  public function pathMods(): string {
    return $this->pathPrepare($this->settings['files']['mods']);
  }

  /**
   * Path to export generated mod.
   */
  public function pathOutput(): string {
    // @todo: Manage this as setting.
    return 'C:\Program Files (x86)\Steam\steamapps\common\Barotrauma\LocalMods\[DS] Gather Resources Quickly';
  }

  /**
   * Get scanner object which will allow us to scan folder of specific app by
   * build id.
   *
   * @param int $appId - ID of the app in Steam.
   * @param int $buildId - Build id of the app in Steam.
   *
   * @return Scanner
   */
  public function scan(int $appId, int $buildId): Scanner {
    // Use static cache.
    static $scanners = [];
    // Attempt to get scanner from cache.
    if (isset($scanners[$appId][$buildId])) {
      return $scanners[$appId][$buildId];
    }
    // Create new scanner.
    $scanner = new Scanner($appId, $buildId, $this);
    $scanners[$appId][$buildId] = $scanner;
    return $scanner;
  }

  /**
   * Error message.
   *
   * @param $msg - Message to throw.
   */
  public static function error($msg): void {
    static::log($msg, 0);
    exit();
  }

  /**
   * Notice message.
   *
   * @param $msg - Message to throw.
   *
   * @return void
   */
  public static function notice($msg): void {
    static::log($msg, 1);
  }

  /**
   * Info message.
   *
   * @param $msg - Message to throw.
   *
   * @return void
   */
  public static function info($msg): void {
    static::log($msg, 2);
  }

  /**
   * Debug message.
   *
   * @param $msg - Message to throw.
   *
   * @return void
   */
  public static function debug($msg): void {
    static::log($msg, 3);
  }

  /**
   * Function to send message to console.
   *
   * @param $msg - Message to throw.
   * @param $level - 0 is error, 1 is notice, 2 info log, 3 is hidden debugging.
   *
   * @return void
   */
  public static function log($msg, $level): void {
    // @todo: Implement layers of logging.
    unset($level);
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

  /**
   * Method to prepare real path by relative path.
   *
   * @param $path - Relative path (relative to project root).
   *
   * @return string
   */
  public function pathPrepare($path): string {
    return $this->settings['root'] . '/' . $path;
  }

  /**
   * This method is full of shit, do not recommend look into it.
   *
   * @return void
   */
  public function prepareStatistic(): void {
    $buildId = $this->init();
    $scanner = $this->scan(Core::BAROTRAUMA_APP_ID, $buildId);
    $contentPackage = $scanner->contentPackage();
    // At current moment just scan items.
    $assets = $contentPackage->getChildrenByType(Core::__TYPES);
    /** @var \Barotraumix\Generator\BaroEntity\Entity\Asset $asset */
    foreach ($assets as $asset) {
      $parser = $scanner->createParser($asset->getAttribute('file'));
      // Just parse data.
      $parser->sanitizedXMLData();
    }
    // Process collected data.
    $statistic = $GLOBALS['barotrauma'];
    // Prepare mapping.
    $mappingTags = Yaml::parseFile('mapping.tags.yml');
    $tags = array_keys($statistic['tags']);
    $tagsLowercase = array_keys($statistic['tags']);
    array_walk($tagsLowercase, function (&$value, $index) {
      $value = mb_strtolower($value);
    });
    foreach ($tagsLowercase as $tag) {
      $duplicates = array_keys($tagsLowercase, $tag);
      if (count($duplicates) > 1) {
        foreach ($duplicates as $index) {
          if (!isset($mappingTags[$tags[$index]])) {
            $mappingTags[$tags[$index]] = $tags[$index];
          }
        }
      }
    }
    // Save mapping.
    ksort($mappingTags, SORT_STRING | SORT_FLAG_CASE);
    file_put_contents('mapping.tags.yml', Yaml::dump($mappingTags));
    // Generate PHP files.
    asort($tags);
    $phpFiles = [];
    foreach ($tags as $tag) {
      $className = $tag;
      if (isset($mappingTags[$tag])) {
        $className = $mappingTags[$tag];
      }
      // Prepare file data.
      $filePath = "src/BaroEntity/Entity/$className.php";
      if (!file_exists($filePath)) {
        $fileContent = "<?php\r\n/**\r\n * @file\r\n * Class to manipulate with Barotraumix $className entity.\r\n  */\r\n\r\nnamespace Barotraumix\Generator\BaroEntity\Entity;\r\n\r\nuse Barotraumix\Generator\Core;\r\nuse Barotraumix\Generator\BaroEntity\Base;\r\n\r\n/**\r\n * Class $className.\r\n */\r\nclass $className extends BaseEntity {\r\n  /**\r\n   * @inheritDoc\r\n   */\r\n  public function createChild(Base \$child): null|bool|Base {\r\n    \$newChild = NULL;\r\n    \$name = \$child->getName();\r\n    switch (\$name) {\r\n\r\n      case 'PreferredContainer':\r\n        \$newChild = PreferredContainer::createFrom(\$child, \$this->services());\r\n        break;\r\n\r\n      case 'CHILD_NAME':\r\n        \$newChild = Prices::createFrom(\$child, \$this->services());\r\n        \$newChild->setName('OPTIONAL');\r\n        break;\r\n\r\n      default:\r\n        Core::error('This case needs attention. CHILD_NAME child element is not recognized: ' . \$name);\r\n        break;\r\n    }\r\n    return \$newChild;\r\n  }\r\n}";
        // You need to ensure that there is no duplicated tags in array "$tags".
        file_put_contents($filePath, $fileContent);
      }
    }
    // Prepare debugging data.
    $debug = json_encode($statistic, JSON_UNESCAPED_SLASHES);
    unset($debug);
  }

}