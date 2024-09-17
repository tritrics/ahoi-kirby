<?php

namespace Tritrics\Ahoi\v1\Actions;

use Exception;
use Kirby\Cms\Page;
use Kirby\Toolkit\Str;
use Tritrics\Ahoi\v1\Exceptions\PayloadException;
use Tritrics\Ahoi\v1\Helper\UrlHelper;
use Tritrics\Ahoi\v1\Helper\TypeHelper;
use Kirby\Query\Query;
use Kirby\Content\Field;

/**
 * Sending E-Mails
 */
class EmailAction
{
  /**
   * Simple list with values as mail body in case a template is missing.
   */
  private static function buildInTemplate(Page $page): string
  {
    $res = "Automatically generated email\n\n";
    foreach ($page->content()->data() as $key => $value) {
      $res .= $key . ': ' .  Str::esc((string) $value ?? '') . "\n";
    }
    return $res . "\n";
  }

  /**
   * Check if attachment file is existing.
   * Given paths must be abolute or relative inside Kirby root. All non-existing files are
   * stripped, because otherwise PHPMailer will fail to send the mail.
   */
  private static function checkAttachments(array $paths): array
  {
    $root = rtrim(kirby()->root(), '/') . '/';
    $res = [];
    foreach ($paths as $path) {

      // we don't support Models or anything else
      if (!is_string($path) || strlen($path) === 0) {
        continue;
      }

      // relative path, must be inside Kirby root
      if (substr($path, 0, 1) !== '/') {
        $path = $root . $path;
      }
      if (is_file($path)) {
        $res[] = $path;
      }
    }
    return $res;
  }

  /**
   * Check if addresses are valid mail adresses
   */
  private static function checkAddresses(string|array $addresses): array|string|null
  {
    $res = is_array($addresses) ? $addresses : [ $addresses ];
    $res = array_filter($res, function($address) {
      return filter_var($address, FILTER_VALIDATE_EMAIL);
    });
    if (count($res) === 0) {
      return null;
    } else if (count($res) === 1) {
      return $res[0];
    }
    return $res;
  }

  /**
   * Helper to get a list with objects of email configuration, same structure
   * like it would be configures in config.php email.presets.
   * https://getkirby.com/docs/guide/emails
   */
  private static function getEmails(array $presets, ?string $lang, Page $page): array
  {
    $res = [];
    $strFields = self::getStringFields($page);
    foreach ($presets as $preset) {

      // build array with email config, like required by Kirby's mail function
      $email = [];

      // from, one, required
      if (isset($preset['from'])) {
        $email['from'] = self::checkAddresses(self::getPresetValue($preset['from'], $strFields, false));
        if ($email['from'] === null || is_array($email['from'])) {
          continue;
        }
      } else {
        continue;
      }

      // from name, optional
      if (
        isset($preset['from_name']) &&
        is_string($preset['from_name']) &&
        strlen($preset['from_name']) > 0
      ) {
        $email['fromName'] = self::getPresetValue($preset['from_name'], $strFields, false);
      }

      // to, one or multiple, required
      if (isset($preset['to'])) {
        $email['to'] = self::checkAddresses(self::getPresetValue($preset['to'], $strFields, true));
        if ($email['to'] === null) {
          continue;
        }
      } else {
        continue;
      }

      // reply to, one, optional
      if (isset($preset['reply_to'])) {
        $email['replyTo'] = self::checkAddresses(self::getPresetValue($preset['replyTo'], $strFields, false));
        if ($email['replyTo'] === null || is_array($email['replyTo'])) {
          unset($email['replyTo']);
        }
      }

      // replay to name, optional
      if (
        isset($email['replyTo']) &&
        isset($preset['reply_to_name']) &&
        is_string($preset['reply_to_name']) &&
        strlen($preset['reply_to_name']) > 0
      ) {
        $email['replyToName'] = self::getPresetValue($preset['replyToName'], $strFields, false);
      }

      // cc, one or multiple, optional
      if (isset($preset['cc'])) {
        $email['cc'] = self::checkAddresses(self::getPresetValue($preset['cc'], $strFields, true));
        if ($email['cc'] === null) {
          unset($email['cc']);
        }
      }

      // bcc, one or multiple, optional
      if (isset($preset['bcc'])) {
        $email['bcc'] = self::checkAddresses(self::getPresetValue($preset['bcc'], $strFields, true));
        if ($email['bcc'] === null) {
          unset($email['bcc']);
        }
      }

      // subject, lang-specific, required
      if (
        is_string($lang) &&
        isset($preset['subject_' . $lang]) &&
        is_string($preset['subject_' . $lang]) &&
        strlen($preset['subject_' . $lang]) > 0
      ) {
        $email['subject'] = self::getPresetValue($preset['subject_' . $lang], $strFields, false);
      } else if (
        isset($preset['subject']) &&
        is_string($preset['subject']) &&
        strlen($preset['subject']) > 0
      ) {
        $email['subject'] = self::getPresetValue($preset['subject'], $strFields, false);
      } else {
        $email['subject'] = 'Message from ' . UrlHelper::getReferer();
      }

      // body, lang-specific, required
      $email['body'] = null;
      if (
        is_string($lang) &&
        isset($preset['template_' . $lang]) &&
        is_string($preset['template_' . $lang]) &&
        strlen($preset['template_' . $lang]) > 0
      ) {
        $email['body'] = self::parseTemplate($preset['template_' . $lang], $page);
      }
      if (
        $email['body'] === null &&
        isset($preset['template']) &&
        is_string($preset['template']) &&
        strlen($preset['template']) > 0
      ) {
        $email['body'] = self::parseTemplate($preset['template'], $page);
      }
      if ($email['body'] === null) {
        $email['body'] = self::buildInTemplate($page);
      }

      // attachments, optional
      if (isset($preset['attachments']) and is_array($preset['attachments'])) {
        $email['attachments'] = self::checkAttachments($preset['attachments']);
      }

      $res[] = $email;
    }
    return $res;
  }

  /**
   * Getting string or number values from Page, cutted to a max length of 32 chars
   */
  private static function getStringFields(Page $page): array
  {
    $res = [];
    foreach ($page->content()->data() as $key => $value) {
      if ($key === 'title' || $key === 'uuid') continue;
      if (is_string($value) || is_numeric($value)) {
        $str = str_replace(["\n", "\r"], ' ', (string) $value);
        if (strlen($str) > 32) {
          $str = substr($str, 0, 29) . '...';
        }
        $res[$key] = $str;
      }
    }
    return $res;
  }

  /**
   * Get the value of a preset from 3 sources:
   * 
   * 1. from a Kirby query given in preset
   * 2. string from given preset with parsed in post fields
   * 3. string from given preset
   * 
   * Query (must begin with site. or page.):
   * 
   * 1. from field: 'site.find("slug").content.fieldName'
   * 2. from object-field: 'site.find("slug").content.objectFieldName.toObject.fieldName'
   * 3. from structure-field: 'site.find("slug").content.structureFieldName.toStructure.pluck("fieldName")'
   * 
   * result must be single or array with string values
   */
  private static function getPresetValue(string|array $preset, array $fields, bool $allowMultiple = false): string|array
  {
    $res = [];

    // preset is a query
    if (is_string($preset) && (substr($preset, 0, 5) === 'site.' || substr($preset, 0, 5) === 'page.')) {
      $query = new Query($preset);
      $queryResult = $query->resolve();
      if ($queryResult instanceof Field) {
        $res[] = (string) $queryResult->value();
      } else if (is_array($queryResult)) {
        foreach ($queryResult as $entry) {
          if ($entry instanceof Field) {
            $res[] = (string) $entry->value();
          }
        }
      }
    } else if (is_string($preset)) { // preset is string
      $res = [ $preset ];
    } else if (is_array($preset)) { // preset is array
      $res = $preset;
    }

    // filter if all values are strings
    $res = array_filter($res, function($val) {
      return is_string($val) && strlen($val) > 0;
    });

    // empty, no result
    if (count($res) === 0) {
      return null;
    }

    // parse post-fields in
    $res = array_map(function ($value) use ($fields) {
      foreach ($fields as $fieldname => $fieldvalue) {
        $value = TypeHelper::replaceTag($value, $fieldname, $fieldvalue);
      }
      return $value;
    }, $res);
    return ($allowMultiple && count($res) > 1) ? $res : $res[0];
  }

  /**
   * Read a template an parse data in.
   * Supports both text and html templates.
   * 
   * @throws Exception 
   */
  private static function parseTemplate(string $template, Page $page): mixed
  {
    $html = kirby()->template('emails/' . $template, 'html', 'text');
    $text = kirby()->template('emails/' . $template, 'text', 'text');
    $dataHtml = $page->content()->data();
    $dataText = $page->content()->data();
    array_walk($dataHtml, function (&$value) {
      $value = Str::esc((string)$value ?? '');
    });
    if ($html->exists()) {
      $body = [];
      $body['html'] = $html->render($dataHtml);
      if ($text->exists()) {
        $body['text'] = $text->render($dataText);
      }
      return $body;
    } elseif ($text->exists()) {
      return $text->render($dataText);
    }
    return null;
  }

  /**
   * Sending emails defined in $presets.
   * 
   * @throws Exception
   * @throws PayloadException
   */
  public static function send(array $presets, Page $page, ?string $lang): array {
    $res = [
      'total' => 0,
      'success' => 0,
      'fail' => 0,
      'errno' => 0,
    ];

    // computing the mails from $presets
    $emails = self::getEmails($presets, $lang, $page);
    $res['total'] = is_array($emails) ? count($emails) : 0;

    // no valid configurations
    if ($res['total'] === 0) {
      $res['errno'] = 20;
      throw new PayloadException('All mail configurations in config.php are invalid, nothing to send.', 20, $res); // @errno20
    }

    // sending
    foreach ($emails as $email) {
      try {
        if (kirby()->email($email)->isSent()) {
          $res['success']++;
        } else {
          $res['fail']++;
        }
      } catch(Exception $E) {
        $res['fail']++;
      }
    }

    // non-fatal error: Error on sending %fail from %total mails.
    if ($res['fail'] > 0) {
      $res['errno'] = 200; // @errno200
    }
    return $res;
  }
}
