<?php

namespace Tritrics\AflevereApi\v1\Services;

use Exception;
use Kirby\Filesystem\F;
use Kirby\Cms\Page;
use Kirby\Toolkit\Str;
use Throwable;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Exceptions\PayloadException;
use Tritrics\AflevereApi\v1\Helper\TokenHelper;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\ResponseHelper;
use Tritrics\AflevereApi\v1\Actions\EmailAction;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
use Tritrics\AflevereApi\v1\Helper\KirbyHelper;
use Tritrics\AflevereApi\v1\Helper\DebugHelper;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;

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
 * 17 Post data configuration is missing or incomplete in config.php.
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
   * commented types: @todo
   * not listed types: unhandled data structure
   */
  private static $validFieldTypes = [
    //'checkboxes',
    'color',
    'date',
    'datetime', // Kirby date field with time-option
    'email',
    'hidden',
    'link',
    //'multiselect',
    'number',
    //'radio',
    'range',
    //'select',
    'slug',
    //'tags',
    'tel',
    'text',
    'textarea',
    //'time',
    //'toggle',
    //'toggles',
    'url',
    'writer',
  ];

  /**
   * Creates as page from post data
   *
   * @throws Exception 
   * @throws Throwable 
   */
  private static function createPage(string $lang, string $action, array $data): Page
  {
    $uuid = Str::lower(Str::random(16, 'base32hex'));
    $hosts = RequestHelper::getHosts();

    // Template
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');
    $file = rtrim(kirby()->root('blueprints'), '/') . '/pages/' . $template . '.yml';
    if (!F::exists($file)) {
      throw new Exception('Template configuration is missing or wrong in config.php.', 17); // @errno17
    };

    // Parent, ignored needed if page is not saved
    $parent = false;
    if (ConfigHelper::getConfig('actions.' . $action . '.save', true)) {
      $parent = KirbyHelper::findPage(ConfigHelper::getConfig('actions.' . $action . '.parent', null));
      if (!$parent instanceof Page) {
        throw new Exception('Parent configuration is missing or wrong in config.php.', 17); // @errno17
      }
    }

    // get Content
    $stripTags = ConfigHelper::getConfig('form-security.strip-tags', true);
    $stripBackslashes = ConfigHelper::getConfig('form-security.strip-backslashes', true);
    $content = [
      'title' => ConfigHelper::getConfig('actions.' . $action . '.title', 'Incoming %created (action %action)'),
      'uuid' => $uuid,
    ];
    foreach (self::getFormFields($action) as $key => $type) {
      switch ($key) {
        case 'title':
        case 'uuid':
          continue 2; // don't allow overwrite
        case 'action':
           $content[$key] = $action;
          break;
        case 'created':
           $content[$key] = date('Y-m-d H:i:s');
          break;
        case 'host':
           $content[$key] = $hosts['referer']['host'];
          break;
        case 'ip':
           $content[$key] = $hosts['referer']['ip'];
          break;
        case 'lang':
           $content[$key] = $lang;
          break;
        default:
          $content[$key] = self::filterInput($data[$key] ?? '', $type, $stripTags, $stripBackslashes);
      }
      $content['title'] = str_replace('%' . $key, (string) $content[$key], $content['title']);
    }

    // create Page
    $config = [
      'slug' => $uuid,
      'template' => $template,
      'content' => $content,
      'isDraft' => true,
    ];
    if ($parent) {
      $config['parent'] = $parent;
    }
    return KirbyHelper::createPage($config);
  }

  /**
   * 
   */
  private static function filterInput(
    mixed $value,
    string $type,
    bool $stripTags,
    bool $stripBackslashes
  ): string|int|float|null {
    $res = '';
    switch ($type) {

      // text, one-line
      case 'color':
      case 'email':
      case 'hidden':
      case 'link':
      case 'slug':
      case 'tel':
      case 'text':
      case 'url':
        if (TypeHelper::isString($value)) {
          $res = TypeHelper::toString($value, true, false);
          $res = preg_replace('/\s+/', ' ', $res);
          $res = $stripTags ? strip_tags($res) : $res;
          $res = $stripBackslashes ? stripslashes($res) : $res;
        }
        break;

      // date like 2024-10-10
      case 'date':
        if (TypeHelper::isDate($value)) {
          $res = TypeHelper::toDate($value)->format('Y-m-d');
        }
        break;

      // date like 2024-10-10 12:15:00
      case 'datetime':
        error_log('datetime ' . $value);
        if (TypeHelper::isDateTime($value)) {
          $res = TypeHelper::toDateTime($value)->format('Y-m-d H:i:s');
        }
        break;

      // number
      case 'number':
      case 'range':
        if (TypeHelper::isNumber($value)) {
          $res = TypeHelper::toNumber($value);
        }
        break;

      // text multiline
      case 'textarea':
      case 'writer':
        if (TypeHelper::isString($value)) {
          $res = TypeHelper::toString($value, true, false);
          $res = $stripTags ? strip_tags($res) : $res;
          $res = $stripBackslashes ? stripslashes($res) : $res;
        }
        break;
    }
    return $res;
  }

  /**
   * Get a list of field names from template, which have a proper type.
   */
  private static function getFormFields(string $action): array
  {
    // create a dummy page to get blueprint-fields
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');
    $dummy = new Page(['slug' => 'dummy', 'template' => $template]);
    $res = [];
    foreach ($dummy->blueprint()->fields() as $key => $def) {
      if (in_array($def['type'], self::$validFieldTypes) && !in_array($key, $res)) {
        $res[$key] = TypeHelper::toString($def['type'], true, true);
        if ($res[$key] === 'date' && isset($def['time'])) {
          $res[$key] = 'datetime';
        }
      }
    }
    return $res;
  }

  /**
   * Helper to get the field-data out of Page model and add error codes.
   */
  private static function getInputData(string $action, Page $page): Collection
  {
    $res = new Collection();
    $errors = $page->errors();
    foreach (self::getFormFields($action) as $key => $type) {
      $field = $res->add($key);
      $field->add('value', $page->$key()->value());
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

  /**
   * Main function to submit (execute) a given action.
   * Token and action are already checked by controller.
   */
  public static function create(string $lang, string $action, array $data): array
  {
    // init response
    $res = ResponseHelper::getHeader();
    $body = $res->add('body');
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
      return $res->get();
    }

    // read post data and validate
    try {
      $page = self::createPage($lang, $action, $data);
    } catch (Exception $E) {
      $errno->set($E->getCode());
      DebugHelper::logActionError($action, $E->getMessage(), $E->getCode());
      return $res->get();
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
      return $res->get();
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

    // delete page or change status, default is save=true and status=draft
    $status = ConfigHelper::getConfig('actions.' . $action . '.status', 'draft');
    if(ConfigHelper::getConfig('actions.' . $action . '.save', true) === false) {
      KirbyHelper::deletePage($page);
      $result->node('saved')->set(false);
      $result->unset('id');
    } elseif ($status === 'listed' || $status === 'unlisted') {
      KirbyHelper::changeStatus($page, $status);
    }
    return $res->get();
  }

  /**
   * Getting a token.
   */
  public static function token(string $action): array
  {
    $res = ResponseHelper::getHeader();
    $body = $res->add('body');
    $body->add('action', $action);
    $body->add('token', TokenHelper::get($action));
    return $res->get();
  }
}
