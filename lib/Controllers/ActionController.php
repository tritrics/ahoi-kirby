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
   * Create
   */
  public function create(?string $lang, ?string $action, ?string $token): Response
  {
    if($response = $this->isInvalidRequest($lang, $action, $token)) {
      return $response;
    };
    try {
      $data = kirby()->request()->data() ?? [];
      return Response::json(ActionService::create($lang, $action, $data));
    } catch (Exception $e) {
      return ResponseHelper::fatal($e->getMessage());
    }
  }

  /**
   * Delete
   */
  public function delete(): Response
  {
    return ResponseHelper::notImplemented();
  }

  /**
   * Get
   */
  public function get(): Response
  {
    return ResponseHelper::notImplemented();
  }

  /**
   * Validate parameter, check config.
   */
  private function isInvalidRequest(?string $lang, ?string $action, ?string $token): Response|bool
  {
    if (!ConfigHelper::isEnabledAction()) {
      return ResponseHelper::disabled();
    }
    $lang = RequestHelper::getLang($lang);
    if ($lang === null) {
      return ResponseHelper::invalidLang();
    }
    $action = RequestHelper::getAction($action);
    if ($action === null) {
      return ResponseHelper::badRequest();
    }
    if (!TokenHelper::check($action, $token)) {
      return ResponseHelper::badRequest();
    }
    return false;
  }

  /**
   * Options
   */
  public function options(?string $lang, ?string $action, ?string $token): Response
  {
    if ($response = $this->isInvalidRequest($lang, $action, $token)) {
      return $response;
    };
    return ResponseHelper::ok();
  }

  /**
   * Get a token for submit action.
   */
  public function token(?string $action): Response
  {
    if (!ConfigHelper::isEnabledAction()) {
      return ResponseHelper::disabled();
    }
    $action = RequestHelper::getAction($action);
    if ($action === null) {
      return ResponseHelper::badRequest();
    }
    try {
      return Response::json(ActionService::token($action));
    } catch (Exception $e) {
      return ResponseHelper::fatal($e->getMessage());
    }
  }

  /**
   * Update
   */
  public function update(): Response
  {
    return ResponseHelper::notImplemented();
  }
}
