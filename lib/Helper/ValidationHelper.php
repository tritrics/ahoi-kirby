<?php

namespace Tritrics\AflevereApi\v1\Helper;

use Tritrics\AflevereApi\v1\Actions\EmailAction;
use Tritrics\AflevereApi\v1\Helper\GlobalHelper;

/**
 * Validation input data
 */
class ValidationHelper
{
  public static function validate($action, $data)
  {
    return 0;
  }



  /**
   * Sanitize input, strip everything but stings and numbers.
   * 
   * @param Array $data 
   * @return Array 
   */
  public static function sanitizeData($data)
  {
    $res = [];
    $stripTags =        GlobalHelper::getConfig('form-security.strip-tags', true);
    $stripBackslashes = GlobalHelper::getConfig('form-security.strip-backslashes', true);
    $stripUrls =        GlobalHelper::getConfig('form-security.strip-urls', true);
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