<?php

namespace Tritrics\Ahoi\v1\Data;

use Kirby\Http\Response as KirbyResponse;

class Response {

  private $ok = true;

  private $status = 200;

  private $msg = 'OK';

  public $request = null;

  private $lang = null;

  private $node = null;

  /**
   * @param string $request 
   * @return void 
   */
  function __construct(string $request, ?string $lang = null, ?string $node = null)
  {
    $this->request = $request;
    $this->lang = $lang;
    $this->node = $node;
  }

  /**
   * Get a response.
   */
  function get(array|Collection|null $body = null): KirbyResponse
  {
    $Query = kirby()->request()->query();
    $data = [
      'ok' => $this->ok,
      'status' => $this->status,
      'msg' => $this->msg,
      'request' => $this->request,
      'node' => rtrim('/' . ltrim($this->lang . '/' . $this->node, '/'), '/')
    ];
    if (!empty($Query->toString())) {
      $data['query'] = urldecode($Query->toString());
    }
    if ($Query->get('id')) {
      $data['id'] = $Query->get('id');
    }
    if (is_array($body)) {
      $data['body'] = $body;
    } else if ($body instanceof Collection) {
      $data['body'] = $body->get();
    }
    return KirbyResponse::json($data, $this->status, true);
  }

  /**
   * Response: Bad Request.
   */
  public function getBadRequest(?string $msg = null): KirbyResponse
  {
    $this->ok = false;
    $this->status = 400;
    $this->msg = $msg ?? 'Bad Request';
    return $this->get();
  }

  /**
   * Response: API is diabled.
   */
  public function getDisabled(?string $msg = null): KirbyResponse
  {
    $this->ok = false;
    $this->status = 403;
    $this->msg = $msg ?? 'API is disabled for this action';
    return $this->get();
  }

  /**
   * Response: Internal Server Error.
   */
  public function getFatal(?string $msg = null): KirbyResponse
  {
    $this->ok = false;
    $this->status = 500;
    $this->msg = $msg ?? 'Internal Server Error';
    return $this->get();
  }

  /**
   * Reponse: Invalid language.
   */
  public function getInvalidLang(?string $msg = null): KirbyResponse
  {
    $this->ok = false;
    $this->status = 400;
    $this->msg = $msg ?? 'Given language is not valid';
    return $this->get();
  }

  /**
   * Response: Not Allowed.
   */
  public function getNotAllowed(?string $msg = null): KirbyResponse
  {
    $this->ok = false;
    $this->status = 405;
    $this->msg = $msg ?? 'Action not allowed';
    return $this->get();
  }

  /**
   * Response: Not found.
   */
  public function getNotFound(?string $msg = null): KirbyResponse
  {
    $this->ok = false;
    $this->status = 404;
    $this->msg = $msg ?? 'Page is not found';
    return $this->get();
  }

  /**
   * Response: Not implemented.
   */
  public function getNotImplemented(?string $msg = null): KirbyResponse
  {
    $this->ok = false;
    $this->status = 501;
    $this->msg = $msg ?? 'Not Implemented or misconfigured';
    return $this->get();
  }
}