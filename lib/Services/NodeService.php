<?php

namespace Tritrics\Api\Services;

use Kirby\Cms\Site;
use Tritrics\Api\Data\Collection;
use Tritrics\Api\Models\PageModel;
use Tritrics\Api\Models\SiteModel;
use Tritrics\Api\Services\ApiService;
use Tritrics\Api\Services\RequestService;
use Tritrics\Api\Services\BlueprintService;
use Tritrics\Api\Services\FieldService;
/**
 * 
 */
class NodeService
{
  /**
   * Main method for action api/node/[id]
   * Gets a page or the site
   * 
   * @param Kirby\Cms\[Page|Site] $page
   * @param Array $params
   * @return Array
   */
  public static function get ($node, $lang, $fields)
  {
    $blueprint = BlueprintService::getBlueprint($node);
    $res = ApiService::initResponse();
    if ($node instanceof Site) {
      $body = new SiteModel($node, $blueprint, $lang);
    } else {
      $body = new PageModel($node, $blueprint, $lang, true);
    }

    if ($fields === 'all' || (is_array($fields) && count($fields) > 0)) {
      $value = new Collection();
      FieldService::addFields(
        $value,
        $node->content($lang)->fields(),
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
