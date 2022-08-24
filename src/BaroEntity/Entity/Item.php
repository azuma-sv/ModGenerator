<?php

/**
 * @file
 * Class to manipulate with Barotraumix item entity.
 */

namespace Barotraumix\Generator\BaroEntity\Entity;

use Barotraumix\Generator\BaroEntity\Base;
use Barotraumix\Generator\Core;

/**
 * Class Asset.
 */
class Item extends BaseEntity {

  /**
   * @inheritDoc
   */
  public function createChild(Base $child): null|bool|Base {
    $newChild = NULL;
    $name = $child->getName();
    switch ($name) {

      case 'PreferredContainer':
        $newChild = PreferredContainer::createFrom($child, $this->services());
        break;

      case 'Price':
        $newChild = Prices::createFrom($child, $this->services());
        $newChild->setName('Prices');
        break;

      case 'InventoryIcon':
        $newChild = InventoryIcon::createFrom($child, $this->services());
        break;

      case 'Sprite':
        $newChild = Sprite::createFrom($child, $this->services());
        break;

      case 'Body':
        $newChild = Body::createFrom($child, $this->services());
        break;

      case 'IdCard':
        $newChild = IdCard::createFrom($child, $this->services());
        break;

      default:
        Core::error('This case needs attention. Item child element is not recognized: ' . $name);
        break;
    }
    return $newChild;
  }

}

$a = "/**\r\n * @inheritDoc\r\n */\r\npublic function createChild(Base \$child): null|bool|Base {\r\n  \$newChild = NULL;\r\n  \$name = \$child->getName();\r\n  switch ($name) {\r\n\r\n    case 'PreferredContainer':\r\n      \$newChild = PreferredContainer::createFrom(\$child, \$this->services());\r\n      break;\r\n\r\n    case 'CHILD_NAME':\r\n      \$newChild = Prices::createFrom(\$child, \$this->services());\r\n      \$newChild->setName('OPTIONAL');\r\n      break;\r\n\r\n    default:\r\n      Core::error('This case needs attention. CHILD_NAME child element is not recognized: ' . \$name);\r\n      break;\r\n  }\r\n  return $newChild;\r\n}";