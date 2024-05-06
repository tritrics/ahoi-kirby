<?php

namespace Tritrics\Ahoi\v1\Services;

use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\File;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Models\FileModel;
use Tritrics\Ahoi\v1\Models\PageModel;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;
use Tritrics\Ahoi\v1\Helper\FieldHelper;

/**
 * Service for API's page interface. Handles a single page or site.
 */
class FieldsService
{
  /**
   * Main method to respond to "page" action.
   * 
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get (Page|Site|File $model, ?string $lang, array|string $fields): Collection
  {
    $blueprint = BlueprintHelper::getBlueprint($model);
    if($model instanceof File) {
      $body = new FileModel($model, $blueprint, $lang);
    } else {
      $body = new PageModel($model, $blueprint, $lang);
    }

    if ($fields === 'all' || (is_array($fields) && count($fields) > 0)) {
      $value = new Collection();
      FieldHelper::addFields(
        $value,
        $model->content($lang)->fields(),
        $blueprint->node('fields'),
        $lang,
        $fields
      );
      if ($value->count() > 0) {
        $body->add('fields', $value);
      }
    }
    return $body;
  }
}
