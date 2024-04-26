<?php

namespace Tritrics\Tric\v1\Helper;

use Kirby\Cms\Response;
use Tritrics\Tric\v1\Data\Collection;

/**
 * Creating all sorts of responsess.
 */
class ResponseHelper
{

  /**
   * Response: Bad Request.
   */
  public static function badRequest(string $msg = 'Bad Request'): Response
  {
    return self::json(self::getHeader(400, $msg)->get(), 400);
  }

  /**
   * Response: API is diabled.
   */
  public static function disabled(string $msg = 'API is disabled for this action'): Response
  {
    return self::json(self::getHeader(403, $msg)->get(), 403);
  }

  /**
   * Response: Internal Server Error.
   */
  public static function fatal(string $msg = 'Internal Server Error'): Response
  {
    return self::json(self::getHeader(500, $msg)->get(), 500);
  }

  /**
   * Init response with basic properties.
   */
  public static function getHeader(int $status = 200, string $msg = 'OK'): Collection
  {
    $Request = kirby()->request();
    $res = new Collection();
    $res->add('ok', $status === 200);
    $res->add('status', $status);
    $res->add('msg', $msg);
    $res->add('url', $Request->url()->toString());
    return $res;
  }

  /**
   * Reponse: Invalid language.
   */
  public static function invalidLang(): Response
  {
    return self::badRequest('Given language is not valid');
  }

  /**
   * Return a json response with array. 
   */
  public static function json(array $data = []): Response
  {
    return Response::json($data);
  }

  /**
   * Response: Not Allowed.
   */
  public static function notAllowed(string $msg = 'Action not allowed'): Response
  {
    return self::json(self::getHeader(405, $msg)->get(), 405);
  }

  /**
   * Response: Not found.
   */
  public static function notFound(string $msg = 'Page is not found'): Response
  {
    return self::json(self::getHeader(404, $msg)->get(), 404);
  }

  /**
   * Response: Not implemented.
   */
  public static function notImplemented(string $msg = 'Not Implemented or misconfigured'): Response
  {
    return self::json(self::getHeader(501, $msg)->get(), 501);
  }

  /**
   * Response: OK
   */
  public static function ok(string $msg = 'OK'): Response
  {
    return self::json(self::getHeader(200, $msg)->get(), 200);
  }
}
