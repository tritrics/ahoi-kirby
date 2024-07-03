<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Http\Url;
use Kirby\Cms\File;
use Kirby\Cms\Page;

class UrlHelper
{

  /**
   * Build the url, reverse of parse().
   */
  public static function build(array $parts): string
  {
    return self::buildHost($parts) . self::buildPath($parts);
  }

  /**
   * Build the host path of url.
   */
  public static function buildHost(array $parts): string
  {
    return ''
      . (isset($parts['scheme']) ? "{$parts['scheme']}:" : '')
      . ((isset($parts['user']) || isset($parts['host'])) ? '//' : '')
      . (isset($parts['user']) ? "{$parts['user']}" : '')
      . (isset($parts['pass']) ? ":{$parts['pass']}" : '')
      . (isset($parts['user']) ? '@' : '')
      . (isset($parts['host']) ? "{$parts['host']}" : '')
      . (isset($parts['port']) ? ":{$parts['port']}" : '');
  }

  /**
   * Build the path part of url.
   */
  public static function buildPath(array $parts): string
  {
    return ''
      . (isset($parts['path']) ? "{$parts['path']}" : '')
      . (isset($parts['hash']) ? "#{$parts['hash']}" : '')
      . (isset($parts['query']) ? "?{$parts['query']}" : '');
  }

  /**
   * Get Client IP
   */
  public static function getClientIp(): string
  {
    return getenv('HTTP_CLIENT_IP') ? getenv('HTTP_CLIENT_IP') : getenv('REMOTE_ADDR'); // don't care about proxys, too complicated for our purpose
  }

  /**
   * Get host from an url.
   */
  public static function getHost(string $url): string
  {
    $parts = self::parse($url);
    return self::buildHost($parts);
  }

  /**
   * Get node for a page or file.
   */
  public static function getNode(Page|File $model, ?string $lang): string
  {
    if ($model instanceof Page) {
      return '/' . trim($lang . '/' . $model->uri($lang), '/');
    }
    if ($model instanceof File) {
      $page = $model->parent($lang);
      return '/' . ltrim($lang . '/' . $page->uri($lang), '/') . '/' . $model->filename();
    }
  }

  /**
   * Get path of an url.
   */
  public static function getPath(string $url): string
  {
    $parts = self::parse($url);
    return self::buildPath($parts);
  }

  /**
   * Get url of referer/frontend.
   */
  public static function getReferer(): string
  {
    return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : self::getSelfUrl();
  }

  /**
   * Get url of backend.
   */
  public static function getSelfUrl(): string
  {
    $backend = self::parse(URL::current());
    return self::buildHost($backend);
  }

  /**
   * Parsing url in parts.
   */
  public static function parse(string $href): array
  {
    $parts = parse_url($href);

    // doing some normalization
    if (isset($parts['scheme'])) {
      $parts['scheme'] = strtolower($parts['scheme']);
    }
    if (isset($parts['host'])) {
      $parts['host'] = strtolower($parts['host']);
    }
    if (isset($parts['port'])) {
      $parts['port'] = (int) $parts['port'];
    }
    if (isset($parts['path'])) {
      $parts['path'] = trim(strtolower($parts['path']), '/');
      if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['mailto', 'tel'])) {
        $parts['path'] = '/' . $parts['path'];
      }

      // path is the complete path [/some/dir/index.html], where as dirname, basename,
      // filename and extension are the splitted path like returned from pathinfo():
      // dirname: /some/dir
      // basename: index.html
      // extension: html
      // filename: index
      $parts = array_merge($parts, pathinfo($parts['path']));
    }
    if (isset($parts['fragment'])) {
      if (strpos($parts['fragment'], '?') === false) {
        $hash = $parts['fragment'];
        $query = null;
      } else {
        $hash = substr($parts['fragment'], 0, strpos($parts['fragment'], '?') - 1);
        $query = substr($parts['fragment'], strpos($parts['fragment'], '?'));
      }
      if (!empty($hash)) {
        $parts['hash'] = $hash;
      }
      if (!empty($query)) {
        $parts['query'] = $query;
      }
      unset($parts['fragment']);
    }
    return $parts;
  }
}
