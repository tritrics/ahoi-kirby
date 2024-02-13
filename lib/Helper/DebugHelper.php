<?php

namespace Tritrics\AflevereApi\v1\Helper;

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
        $message = str_replace('%' . $key, $value, $message);
      }
    }
    error_log(ConfigHelper::getPluginName() . ': Error ' . $errno . ' on excecuting /action/' . $action . ' (' . $message . ')');
  }
}
