<?php

namespace Tritrics\Ahoi\v1\Actions;

use Exception;
use Kirby\Cms\Page;
use Kirby\Toolkit\Str;
use Tritrics\Ahoi\v1\Exceptions\PayloadException;
use Tritrics\Ahoi\v1\Helper\UrlHelper;

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
   * Check if addresses are valid mail adresses or a field name,
   * so the mail adress is taken from data.
   */
  private static function getAddresses(string|array $addresses, Page $page): mixed
  {
    if (!is_array($addresses)) {
      $addresses = [$addresses];
    }
    $res = [];
    foreach ($addresses as $address) {
      if (!is_string($address) || strlen($address) === 0) {
        continue;
      }
      if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
        $res[] = $address;
      } else if (
        $page->content()->has($address) &&
        filter_var($page->$address()->value(), FILTER_VALIDATE_EMAIL)
      ) {
        $res[] = $page->$address()->value();
      }
    }
    if (count($res) === 0) {
      return null;
    } else if (count($res) === 1) {
      return $res[0];
    } else {
      return $res;
    }
  }

  /**
   * Helper to get a list with objects of email configuration, same structure
   * like it would be configures in config.php email.presets.
   * https://getkirby.com/docs/guide/emails
   */
  private static function getEmails(array $presets, string $lang, Page $page): array
  {
    $res = [];
    foreach ($presets as $preset) {

      // build array with email config, like required by Kirby's mail function
      $email = [];

      // from, one, required
      if (isset($preset['from'])) {
        $email['from'] = self::getAddresses($preset['from'], $page);
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
        $email['fromName'] =
          $page->content()->has($preset['from_name'])
          ? $page->$preset['from_name']()->value()
          : $preset['from_name'];
      }

      // to, one or multiple, required
      if (isset($preset['to'])) {
        $email['to'] = self::getAddresses($preset['to'], $page);
        if ($email['to'] === null) {
          continue;
        }
      } else {
        continue;
      }

      // reply to, one, optional
      if (isset($preset['reply_to'])) {
        $email['replyTo'] = self::getAddresses($preset['reply_to'], $page);
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
        $email['replyToName'] =
          isset($data[$preset['reply_to_name']])
          ? $data[$preset['reply_to_name']]
          : $preset['reply_to_name'];
      }

      // cc, optional
      if (isset($preset['cc'])) {
        $email['cc'] = self::getAddresses($preset['cc'], $page);
        if ($email['cc'] === null) {
          unset($email['cc']);
        }
      }

      // bcc optional
      if (isset($preset['bcc'])) {
        $email['bcc'] = self::getAddresses($preset['bcc'], $page);
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
        $email['subject'] = $preset['subject_' . $lang];
      } else if (
        isset($preset['subject']) &&
        is_string($preset['subject']) &&
        strlen($preset['subject']) > 0
      ) {
        $email['subject'] = $preset['subject'];
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
  public static function send(array $presets, Page $page, string $lang): array {
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
