<?php

namespace Tritrics\AflevereApi\v1\Controllers;

use Kirby\Cms\Response;
use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Services\ActionService;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
use Tritrics\AflevereApi\v1\Helper\ResponseHelper;
use Tritrics\AflevereApi\v1\Helper\TokenHelper;

/**
 * Action Controller
 * Entry point for actions which post data.
 * Basic checks and delegation to services.
 */
class ActionController
{
  /**
   * Valid actions like implemented in ActionController::submit().
   * 
   * @var array
   */
  var $valid_actions = [ 'default' ];

  /**
   * Get a token for submit action.
   */
  public function token(?string $action): Response
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ResponseHelper::ok();
    }
    try {
      if (!ConfigHelper::isEnabledAction()) {
        return ResponseHelper::disabled();
      }
      $action = RequestHelper::getAction($action, $this->valid_actions);
      if ($action === null) {
        return ResponseHelper::badRequest();
      }
      return Response::json(ActionService::token($action));
    } catch (Exception $e) {
      return ResponseHelper::fatal($e->getMessage());
    }
  }

  /**
   * Submit an action
   */
  public function submit(
    ?string $lang,
    ?string $action,
    ?string $token
  ): Response {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ResponseHelper::ok();
    }
    try {
      if (!ConfigHelper::isEnabledAction()) {
        return ResponseHelper::disabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return ResponseHelper::invalidLang();
      }
      $action = RequestHelper::getAction($action, $this->valid_actions);
      if ($action === null) {
        return ResponseHelper::badRequest();
      }
      if (!TokenHelper::check($action, $token)) {
        return ResponseHelper::badRequest();
      }
      $data = $request->data() ?? [];
      return Response::json(ActionService::submit($lang, $action, $data));
    } catch (Exception $e) {
      return ResponseHelper::fatal($e->getMessage());
    }
  }
}
