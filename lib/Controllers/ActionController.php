<?php

namespace Tritrics\AflevereApi\v1\Controllers;

use Kirby\Cms\Response;
use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Services\ActionService;
use Tritrics\AflevereApi\v1\Helper\GlobalHelper;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
use Tritrics\AflevereApi\v1\Helper\TokenHelper;

/**
 * Action Controller
 * Entry point for actions which post data.
 * Basic checks and delegation to services.
 */
class ActionController
{
  /**
   * Get a token for submit action.
   * 
   * @param String $action 
   * @return Array
   */
  public function token($action)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return GlobalHelper::ok();
    }
    try {
      if (!GlobalHelper::isEnabledAction()) {
        return GlobalHelper::disabled();
      }
      $action = RequestHelper::getAction($action);
      if ($action === null) {
        return GlobalHelper::badRequest();
      }
      return ActionService::token($action);
    } catch (Exception $e) {
      return GlobalHelper::fatal($e->getMessage());
    }
  }

  /**
   * Submit an action
   * 
   * @param Mixed $lang 
   * @param Mixed $action 
   * @return Array
   */
  public function submit($lang, $action, $token)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return GlobalHelper::ok();
    }
    try {
      if (!GlobalHelper::isEnabledAction()) {
        return GlobalHelper::disabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return GlobalHelper::invalidLang();
      }
      $action = RequestHelper::getAction($action);
      if ($action === null) {
        return GlobalHelper::badRequest();
      }
      if (!TokenHelper::check($action, $token)) {
        return GlobalHelper::badRequest();
      }
      $data = $request->data() ?? [];
      return ActionService::submit($lang, $action, $data);
    } catch (Exception $e) {
      return GlobalHelper::fatal($e->getMessage());
    }
  }
}
