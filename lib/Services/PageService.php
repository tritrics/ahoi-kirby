<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Cms\Site;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Models\PageModel;
use Tritrics\AflevereApi\v1\Models\SiteModel;
use Tritrics\AflevereApi\v1\Services\ApiService;
use Tritrics\AflevereApi\v1\Services\BlueprintService;
use Tritrics\AflevereApi\v1\Services\FieldService;

/**
 * Service for API's page interface. Handles a single page or site.
 *
 * @package   AflevereAPI Services
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class PageService
{
  /**
   * Main method to respond to "page" action.
   * 
   * @param Page|Site $node
   * @param string $lang
   * @param string|array $fields
   * @return Response 
   * @throws DuplicateException 
   * @throws LogicException 
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
