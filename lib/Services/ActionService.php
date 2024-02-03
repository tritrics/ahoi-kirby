<?php

namespace Tritrics\AflevereApi\v1\Services;

use Exception;
use Tritrics\AflevereApi\v1\Exceptions\PayloadException;
use Tritrics\AflevereApi\v1\Factories\PostFactory;
use Tritrics\AflevereApi\v1\Helper\TokenHelper;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\ResponseHelper;
use Tritrics\AflevereApi\v1\Actions\EmailAction;

/**
 * Handling actions (post-data)
 * 
 * Error messages, only stored to error-log, not published.
 * 
 * Fatal errors:
 * 1 - 19 Global
 * 20 - 29 EmailAction
 * 30 - 99 unused
 * 
 *  1 An unknown error occured.
 * 10 Action configuration is missing or incomplete in config.php.
 * 11 Security token is missing in config or doesn\'t match the requirements.
 * 15 Action was declined due to security concerns. // not used, reserved
 * 17 Post-input configuration is missing or incomplete in config.php.
 * 19 Submitted data was not saved because all sub-actions failed.
 * 20 All mail configurations in config.php are invalid, nothing to send.
 * 21 No valid inbound mail action configured in config.php.
 * 22 Sending failed for all inbound mails.
 * 
 * Non-fatal errors
 * 100 - 199 Global
 * 200 - 299 EmailAction
 * 300 - 999 unused
 * 
 * 100 Error in one or more sub-actions.
 * 110 Submitted post data failed validation.
 * 120 Field is of wrong data type.
 * 121 Field is required.
 * 122 Value is not matching the required min/max.
 * 200 Error on sending %fail from %total mails.
 */
class ActionService
{
  /**
   * Getting a token.
   */
  public static function token (string $action): array
  {
    $res = ResponseHelper::getHeader();
    $body = $res->add('body');
    $body->add('action', $action);
    $body->add('token', TokenHelper::get($action));
    return $res->get();
  }

  /**
   * Main function to submit (execute) a given action.
   * Token and action are already checked by controller.
   */
  public static function submit(string $lang, string $action, array $data): array
  {
    // init response
    $res = ResponseHelper::getHeader();
    $body = $res->add('body');
    $body->add('action', $action);
    $errno = $body->add('errno', 0);
    $protocol = $body->add('result');

    // read post data and validate
    try {
      $meta = PostFactory::createMeta($lang);
      $post = PostFactory::create($action, $data);
    } catch (Exception $E) {
      $errno->set($E->getCode());
      self::logError($action, $E->getMessage(), $E->getCode());
      return $res->get();
    }
    
    // write post data to result
    $protocol->add('input', $post->getResult(
      ConfigHelper::getConfig('form-security.return-post-values', false)
    ));

    // Error in field validation
    $validationError = $post->hasError();
    if ($validationError) {
      $errno->set(110);
      self::logError($action, 'Submitted post data failed validation.', 110); // @errno110
      return $res->get();
    }

    // read actions config
    $actions = ConfigHelper::getConfig('actions');

    // actions.[action] is not existing or is not an array
    if (
      !is_array($actions) ||
      count($actions) === 0 ||
      !isset($actions[$action]) ||
      !is_array($actions[$action]) ||
      count($actions[$action]) === 0
    ) {
      $errno->set(10);
      self::logError($action, 'Action configuration is missing or incomplete in config.php.', 10); // @errno10
      return $res->get();
    }

    // At least one action which saves the data must be successfull.
    $isSaved = false;
    $subActionFailed = false;

    // ... other actions, evtl. set $isSaved = true

    if (isset($actions[$action]['email'])) {
      try {
        $resEmail = EmailAction::send(
          $actions[$action]['email'],
          $meta,
          $post,
          $lang,
          !$isSaved
        );
        $isSaved = true;
        $protocol->add('email', $resEmail);
        if ($resEmail['errno'] === 200) {
          self::logError($action, 'Error on sending %fail from %total mails.', 200, $resEmail);
        }
      } catch (Exception $E) {
        $subActionFailed = true;
        if ($E instanceof PayloadException) {
          $protocol->add('email', $E->getPayload());
        }
        self::logError($action, $E->getMessage(), $E->getCode());
      }
    }

    // overall errors in actions
    if (!$isSaved) {
      $errno->set(19);
      self::logError($action, 'Submitted data was not saved because all sub-actions failed.', 19); // @errno19
    } else if ($subActionFailed) {
      $errno->set(100);
      self::logError($action, 'Error in one or more sub-actions.', 100); // @errno100
    }
    return $res->get();
  }

  /**
   * Log errors to PHP error log.
   */
  private static function logError(
    string $action,
    ?string $message = '',
    ?int $errno = 1,
    ?array $parse = []
  ): void {
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
