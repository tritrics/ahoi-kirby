<?php

namespace Tritrics\Ahoi\v1\Helper;

use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Debugging, logging.
 */
class DebugHelper
{
  /**
   * Compute the base slug like /public-api/v1 
   */
  public static function logActionError(
    string $action,
    ?string $message = '',
    mixed $errno = 1,
    ?array $parse = []
  ): void {
    if (!kirby()->option('debug')) {
      return;
    }
    if (!strlen($message)) {
      $message = 'An unknown error occured.';
    }
    if (is_array($parse)) {
      foreach ($parse as $key => $value) {
        $message = TypeHelper::replaceTag($message, $key, $value);
      }
    }
    error_log(ConfigHelper::getPluginName() . ': Error ' . $errno . ' on excecuting /action/' . $action . ' (' . $message . ')');
  }
}
