<?php

namespace Tritrics\Ahoi\v1\Services;

use Exception;
use Kirby\Cms\Page;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Factories\PostFactory;
use Tritrics\Ahoi\v1\Exceptions\PayloadException;
use Tritrics\Ahoi\v1\Helper\TokenHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Actions\EmailAction;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;
use Tritrics\Ahoi\v1\Helper\TypeHelper;
use Tritrics\Ahoi\v1\Helper\DebugHelper;

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
 * 16 Template configuration is missing or wrong in config.php.
 * 17 Parent configuration is missing or wrong in config.php.
 * 18 Submitted post data failed validation.
 * 21 No valid inbound mail action configured in config.php.
 * 22 Sending failed for all inbound mails.
 * 
 * Non-fatal errors
 * 100 - 199 Global
 * 200 - 299 EmailAction
 * 300 - 999 unused
 * 
 * 100 Error in one or more sub-actions.
 * 120 Field value failed validation (the Kirby error message from error.validation is added)
 * 200 Error on sending %fail from %total mails.
 */
class ActionService
{
  /**
   * Main function to submit (execute) a given action.
   * Token and action are already checked by controller.
   */
  public static function create(string $lang, string $action, array $data): Collection
  {
    // init response
    $body = new Collection();
    $body->add('action', $action);
    $errno = $body->add('errno', 0);
    $result = $body->add('result');

    // read actions config
    $actions = ConfigHelper::getConfig('actions');

    // actions.[action] is not existing or is not an array
    if (
      !is_array($actions) ||
      count($actions) === 0 ||
      !isset($actions[$action]) ||
      !is_array($actions[$action])
    ) {
      $errno->set(10);
      DebugHelper::logActionError($action, 'Action configuration is missing or incomplete in config.php.', 10); // @errno10
      return $body;
    }

    // read post data and validate
    try {
      $page = PostFactory::create($lang, $action, $data);
    } catch (Exception $E) {
      $errno->set($E->getCode());
      DebugHelper::logActionError($action, $E->getMessage(), $E->getCode());
      return $body;
    }

    // write post data to result
    if (ConfigHelper::getConfig('form-security.return-post-values', false)) {
      $result->add('saved', true);
      $result->add('id', $page->slug());
      $result->add('data', self::getInputData($action, $page));
    }

    // Error in field validation
    if (!$page->isValid()) {
      $errno->set(18);
      DebugHelper::logActionError($action, 'Submitted post data failed validation.', 18); // @errno18
      KirbyHelper::deletePage($page);
      return $body;
    }

    // Executing additional actions
    $subActionFailed = false;
    if (isset($actions[$action]['email'])) {
      try {
        $resEmail = EmailAction::send($actions[$action]['email'], $page, $lang);
        $result->add('email', $resEmail);
        if ($resEmail['errno'] === 200) {
          DebugHelper::logActionError($action, 'Error on sending %fail from %total mails.', 200, $resEmail);
        }
      } catch (Exception $E) {
        $subActionFailed = true;
        if ($E instanceof PayloadException) {
          $result->add('email', $E->getPayload());
        }
        DebugHelper::logActionError($action, $E->getMessage(), $E->getCode());
      }
    }

    // ... other actions

    // overall errors in actions
    if ($subActionFailed) {
      $errno->set(100);
      DebugHelper::logActionError($action, 'Error in one or more actions.', 100); // @errno100
    }

    // Delete page if not needed
    // Change of status is deactivated due to security reasons. Status is always draft, so
    // it's not possible to get information through API. This is also the standard Kirby behaviour.
    // To activate it, add 'status' => 'unlisted|listed' to action definition in config.php.
    // $status = ConfigHelper::getConfig('actions.' . $action . '.status', 'draft');
    if(ConfigHelper::getConfig('actions.' . $action . '.save', true) === false) {
      KirbyHelper::deletePage($page);
      $result->node('saved')->set(false);
      $result->unset('id');
    }
    // elseif ($status === 'listed' || $status === 'unlisted') {
    //  KirbyHelper::changeStatus($page, $status);
    // }
    return $body;
  }

  /**
   * Getting a token.
   */
  public static function token(string $action): Collection
  {
    $body = new Collection();
    $body->add('action', $action);
    $body->add('token', TokenHelper::get($action));
    return $body;
  }

  /**
   * Helper to get the field-data out of Page model and add error codes.
   */
  private static function getInputData(string $action, Page $page): Collection
  {
    $res = new Collection();
    $errors = $page->errors();
    foreach (PostFactory::fields($action) as $key => $type) {
      $field = $res->add($key);
      switch ($type) {
        case 'toggle':
          $field->add('value', $page->$key()->value() === 'true' ? 1 : 0);
          break;
        case 'checkboxes':
        case 'multiselect':
        case 'tags':
          $field->add('value', TypeHelper::optionsToArray($page->$key()->value()));
          break;
        default:
          $field->add('value', $page->$key()->value());
      }
      if (isset($errors[$key])) {
        $field->add('errno', 120);
        if (isset($errors[$key]['message']) && count($errors[$key]['message']) > 0) {
          $field->add('errmsg', array_key_first($errors[$key]['message']));
        } else {
          $field->add('errmsg', 'unknown');
        }
      } else {
        $field->add('errno', 0);
      }
    }
    return $res;
  }
}
