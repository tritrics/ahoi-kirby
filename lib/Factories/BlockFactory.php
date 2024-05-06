<?php

namespace Tritrics\Ahoi\v1\Factories;

use Kirby\Content\Field;
use Kirby\Cms\Block;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;

/**
 * Translates Kirby's blocks to API data models.
 */
class BlockFactory extends ModelFactory
{
  protected static $classMap = [
    'block-default' => '\Models\BlockDefaultModel',
    'block-heading' => '\Models\BlockHeadingModel',
  ];

  /**
   * Create an instance of a block-model class defined by type.
   */
  public static function create(
    string $type,
    Field|Block $block,
    Collection $def,
    ?string $lang
  ): object {
    $key = 'block-' . $type;
    if (isset(self::$added[$key])) {
      $class = self::$added[$key];
    } elseif (isset(self::$classMap[$key])) {
      $class = ConfigHelper::getNamespace() . self::$classMap[$key];
    } else {
      $class = ConfigHelper::getNamespace() . self::$classMap['block-default'];
    }
    return new $class($block, $def, $lang);
  }
}
