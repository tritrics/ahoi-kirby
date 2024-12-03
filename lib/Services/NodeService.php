<?php

namespace Tritrics\Ahoi\v1\Services;

use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\File;
use Kirby\Exception\InvalidArgumentException;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Models\FileModel;
use Tritrics\Ahoi\v1\Models\PageModel;
use Tritrics\Ahoi\v1\Models\SiteModel;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;

/**
 * Service for API's page interface. Handles a single page or site.
 */
class NodeService
{
  /**
   * Main method to respond to "page" action.
   * 
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get(
    Page|Site|File $model,
    ?string $lang,
    array $addFields,
    bool $addLanguages
  ): Collection {
    $blueprint = BlueprintHelper::get($model);
    if ($model instanceof File) {
      $body = new FileModel($model, $blueprint, $lang, $addFields, $addLanguages);
    } else if ($model instanceof Site) {
      $body = new SiteModel($model, $blueprint, $lang, $addFields, $addLanguages);
    } else {
      $body = new PageModel($model, $blueprint, $lang, $addFields, $addLanguages);
    }
    return $body;
  }
}
