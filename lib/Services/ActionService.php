<?php

namespace Tritrics\AflevereApi\v1\Services;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Services\ApiService;

/**
 * Handling actions (post-data)
 */
class ActionService
{
  /**
   * Main function to execute a given action.
   * 
   * @param String $lang 
   * @param String $action 
   * @param Array $data 
   * @return Response
   */
  public static function do($lang, $action, $data)
  {
    $res = ApiService::initResponse();
    $body = new Collection();
    $body->add('action', $action);
    $body->add('lang', $lang);
    $body->add('data', $data);
    $res->add('body', $body);
    return $res->get();
  }
}
