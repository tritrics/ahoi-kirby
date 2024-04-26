<?php

namespace Tritrics\Tric\v1\Services;

use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Models\PageModel;
use Tritrics\Tric\v1\Helper\ResponseHelper;
use Tritrics\Tric\v1\Helper\BlueprintHelper;
use Tritrics\Tric\v1\Helper\FieldHelper;

/**
 * Service for API's page interface. Handles a single page or site.
 */
class PageService
{
  /**
   * Main method to respond to "page" action.
   * 
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get (Page|Site $model, ?string $lang, array|string $fields): array
  {
    $blueprint = BlueprintHelper::getBlueprint($model);
    $res = ResponseHelper::getHeader();
    $body = new PageModel($model, $blueprint, $lang, true);

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
        $body->add('value', $value);
      }
    }
    $res->add('body', $body);
    return $res->get();
  }
}
