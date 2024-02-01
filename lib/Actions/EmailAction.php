<?php

namespace Tritrics\AflevereApi\v1\Actions;

use Exception;
use Throwable;
use Kirby\Exception\LogicException;
use Tritrics\AflevereApi\v1\Data\SimpleCollection;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;

/**
 * Sending E-Mails
 */
class EmailAction
{
  /**
   * Sending emails defined in $presets.
   * 
   * @throws Exception 
   */
  public static function send(
    array $presets,
    SimpleCollection $meta,
    SimpleCollection $data,
    string $lang,
    bool $checkInbound = false
  ): array {
    $res = [
      'total' => 0,
      'success' => 0,
      'fail' => 0,
      'errno' => 0,
    ];

    // computing the mails from $presets
    $emails = self::getEmails($presets,$lang, $meta, $data);
    $res['total'] = is_array($emails) ? count($emails) : 0;

    // @errno20: All mail configurations in config.php are invalid, nothing to send.
    if ($res['total'] === 0) {
      $res['errno'] = 20;
      return $res;
    }

    // @errno21: No valid inbound mail action configured in config.php.
    if ($checkInbound) {
      $count = 0;
      foreach($emails as $email) {
        if ($email['inbound']) {
          $count++;
        }
      }
      if ($count === 0) {
        $res['errno'] = 21;
        return $res;
      }
    }

    // sending
    $inboundSent = 0;
    foreach ($emails as $email) {
      try {
        if (kirby()->email($email)->isSent()) {
          $res['success']++;
          if ($email['inbound']) {
            $inboundSent++;
          }
        } else {
          $res['fail']++;
        }
      } catch(Exception $E) {
        $res['fail']++;
      }
    }

    // @errno22: Sending failed for all inbound mails.
    if ($inboundSent === 0) {
      $res['errno'] = 22;
    }

    // @errno200: Error on sending %fail from %total mails.
    else if ($res['fail'] > 0) {
      $res['errno'] = 200;
    }

    // OK
    return $res;
  }

  /**
   * Helper to get a list with objects of email configuration, same structure
   * like it would be configures in config.php email.presets.
   * https://getkirby.com/docs/guide/emails
   */
  private static function getEmails(
    array $presets,
    string $lang,
    SimpleCollection $meta,
    SimpleCollection $data
  ): array {
    $res = [];
    $hosts = RequestHelper::getHosts($lang);
    foreach ($presets as $preset) {

      // build array with email config, like required by Kirby's mail function
      $email = [ 'inbound' => false ];

      // from, one, required
      if (isset($preset['from'])) {
        $email['from'] = self::getAddresses($preset['from'], $data);
        if ($email['from'] === null || is_array($email['from'])) {
          continue;
        }
      } else {
        continue;
      }

      // from name, optional
      if (
        isset($preset['fromName']) &&
        is_string($preset['fromName']) &&
        strlen($preset['fromName']) > 0
      ) {
        $email['fromName'] =
          $data->has($preset['fromName'])
          ? $data->$preset['fromName']->get()
          : $preset['fromName'];
      }

      // to, one or multiple, required
      if (isset($preset['to'])) {
        $email['to'] = self::getAddresses($preset['to'], $data);
        if ($email['to'] === null) {
          continue;
        }
        if (!$email['inbound']) {
          $email['inbound'] = self::isInbound($preset['to']);
        }
      } else {
        continue;
      }

      // reply to, one, optional
      if (isset($preset['replyTo'])) {
        $email['replyTo'] = self::getAddresses($preset['replyTo'], $data);
        if ($email['replyTo'] === null || is_array($email['replyTo'])) {
          unset($email['replyTo']);
        }
      }

      // replay to name, optional
      if (
        isset($email['replyTo']) &&
        isset($preset['replyToName']) &&
        is_string($preset['replyToName']) &&
        strlen($preset['replyToName']) > 0
      ) {
        $email['replyToName'] =
          isset($data[$preset['replyToName']])
          ? $data[$preset['replyToName']]
          : $preset['replyToName'];
      }

      // cc, optional
      if (isset($preset['cc'])) {
        $email['cc'] = self::getAddresses($preset['cc'], $data);
        if ($email['cc'] === null) {
          unset($email['cc']);
        }
        if (!$email['inbound']) {
          $email['inbound'] = self::isInbound($preset['cc']);
        }
      }

      // bcc optional
      if (isset($preset['bcc'])) {
        $email['bcc'] = self::getAddresses($preset['bcc'], $data);
        if ($email['bcc'] === null) {
          unset($email['bcc']);
        }
        if (!$email['inbound']) {
          $email['inbound'] = self::isInbound($preset['bcc'], );
        }
      }

      // subject, lang-specific, required
      if (
        is_string($lang) &&
        isset($preset['subject-' . $lang]) &&
        is_string($preset['subject-' . $lang]) &&
        strlen($preset['subject-' . $lang]) > 0
      ) {
        $email['subject'] = $preset['subject-' . $lang];
      } else if (
        isset($preset['subject']) &&
        is_string($preset['subject']) &&
        strlen($preset['subject']) > 0
      ) {
        $email['subject'] = $preset['subject'];
      } else {
        $email['subject'] = 'Message from ' . $hosts['self']['host'];
      }

      // body, lang-specific, required
      $email['body'] = null;
      if (
        is_string($lang) &&
        isset($preset['template-' . $lang]) &&
        is_string($preset['template-' . $lang]) &&
        strlen($preset['template-' . $lang]) > 0
      ) {
        $email['body'] = self::parseTemplate($preset['template-' . $lang], $meta, $data);
      }
      if (
        $email['body'] === null &&
        isset($preset['template']) &&
        is_string($preset['template']) &&
        strlen($preset['template']) > 0
      ) {
        $email['body'] = self::parseTemplate($preset['template'], $meta, $data);
      }
      if ($email['body'] === null) {
        $email['body'] = self::buildInTemplate($meta, $data);
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
   * If to, cc or bcc have at least one valid email address in config,
   * the mail is considered to be inbound. Mail actions which don't send
   * any inbound mails are in some cases skipped.
   */
  private static function isInbound(string|array $addresses): bool
  {
    if (!is_array($addresses)) {
      $addresses = [$addresses];
    }
    foreach ($addresses as $address) {
      if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if addresses are valid mail adresses or a field name,
   * so the mail adress is taken from data.
   */
  private static function getAddresses(
    string|array $addresses,
    SimpleCollection $data
  ): mixed {
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
        $data->has($address) &&
        filter_var($data->$address->get(), FILTER_VALIDATE_EMAIL)
      ) {
        $res[] = $data->$address->get();
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
   * Read a template an parse data in.
   * Supports both text and html templates.
   * 
   * @throws Throwable 
   * @throws LogicException 
   */
  private static function parseTemplate(
    string $template,
    SimpleCollection $meta,
    SimpleCollection $data
  ): mixed {
    $html = kirby()->template('emails/' . $template, 'html', 'text');
    $text = kirby()->template('emails/' . $template, 'text', 'text');
    $templateData = [ 'meta' => $meta, 'data' => $data ]; // will be deconstructed to $meta and $data in template
    if ($html->exists()) {
      $body = [];
      $body['html'] = $html->render($templateData);
      if ($text->exists()) {
        $body['text'] = $text->render($templateData);
      }
      return $body;
    } elseif ($text->exists()) {
      return $text->render($templateData);
    }
    return null;
  }

  /**
   * Simple list with values as mail body in case a template is missing.
   */
  private static function buildInTemplate(SimpleCollection $meta, SimpleCollection $data): string
  {
    $res = "Automatically generated email\n\n";
    foreach ($meta as $key => $model) {
      $res .= $key . ': ' . $model->get() . "\n";
    }
    $res .= "\n";
    foreach ($data as $key => $model) {
      $res .= $key . ': ' . $model->get() . "\n";
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
}
