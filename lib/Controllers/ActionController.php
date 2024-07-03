<?php

namespace Tritrics\Ahoi\v1\Controllers;

use Kirby\Http\Response as KirbyResponse;
use Tritrics\Ahoi\v1\Data\Response;
use Kirby\Exception\Exception;
use Tritrics\Ahoi\v1\Services\ActionService;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\RequestHelper;
use Tritrics\Ahoi\v1\Helper\TokenHelper;

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
  public function create(?string $lang, ?string $action, ?string $token): KirbyResponse
  {
    $Response = new Response('create', $lang, $action);
    if($Error = $this->isInvalidRequest($Response, $lang, $action, $token)) {
      return $Error;
    };
    try {
      $data = kirby()->request()->data() ?? [];
      return $Response->get(ActionService::create($lang, $action, $data));
    } catch (Exception $e) {
      return $Response->getFatal($e->getMessage());
    }
  }

  /**
   * Delete
   */
  public function delete(): KirbyResponse
  {
    $Response = new Response('delete');
    return $Response->getNotImplemented();
  }

  /**
   * Get
   */
  public function get(): KirbyResponse
  {
    $Response = new Response('get');
    return $Response->getNotImplemented();
  }

  /**
   * Validate parameter, check config.
   */
  private function isInvalidRequest(Response $Response, ?string $lang, ?string $action, ?string $token): Response|bool
  {
    if (!ConfigHelper::isEnabledAction()) {
      return $Response->getDisabled();
    }
    $lang = RequestHelper::getLang($lang);
    if ($lang === null) {
      return $Response->getInvalidLang();
    }
    $action = RequestHelper::getAction($action);
    if ($action === null) {
      return $Response->getBadRequest();
    }
    if (!TokenHelper::check($action, $token)) {
      return $Response->getBadRequest();
    }
    return false;
  }

  /**
   * Options
   */
  public function options(?string $lang, ?string $action, ?string $token): KirbyResponse
  {
    $Response = new Response('options', $lang, $action);
    if ($Error = $this->isInvalidRequest($Response, $lang, $action, $token)) {
      return $Error;
    };
    return $Response->get();
  }

  /**
   * Get a token for submit action.
   */
  public function token(?string $action): KirbyResponse
  {
    $Response = new Response('token', null, $action);
    if (!ConfigHelper::isEnabledAction()) {
      return $Response->getDisabled();
    }
    $action = RequestHelper::getAction($action);
    if ($action === null) {
      return $Response->getBadRequest();
    }
    try {
      return $Response->get(ActionService::token($action));
    } catch (Exception $e) {
      return $Response->getFatal($e->getMessage());
    }
  }

  /**
   * Update
   */
  public function update(): KirbyResponse
  {
    $Response = new Response('update');
    return $Response->getNotImplemented();
  }
}
