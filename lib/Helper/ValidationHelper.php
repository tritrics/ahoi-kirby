<?php

namespace Tritrics\AflevereApi\v1\Helper;

use Tritrics\AflevereApi\v1\Helper\ConfigHelper;

/**
 * Validation input data
 */
class ValidationHelper
{
  public static function validate(string $action, array $data): mixed
  {
    return 0;
  }

  /**
   * Sanitize input, strip everything but stings and numbers.
   */
  public static function sanitizeData(array $data): array
  {
    $res = [];
    $stripTags =        ConfigHelper::getConfig('form-security.strip-tags', true);
    $stripBackslashes = ConfigHelper::getConfig('form-security.strip-backslashes', true);
    $stripUrls =        ConfigHelper::getConfig('form-security.strip-urls', true);
    foreach ($data as $key => $value) {

      // $value is string or number here @see validateData()
      // number needs no special treatment
      if (is_string($value)) {
        if ($stripTags) {
          $value = strip_tags($value);
        }
        if ($stripBackslashes) {
          $value = str_replace('\\', '', $value);
        }
        if ($stripUrls) {
          $value = preg_replace('/(https?:\/\/([-\w\.]+[-\w])+(:\d+)?(\/([\w\/_\.#-]*(\?\S+)?[^\.\s])?)?)/', '[link removed]', $value);
        }
        $value = trim($value);
      }
      $res[$key] = $value;
    }
    return $res;
  }
}