<?php

namespace Tritrics\Api\Services;

class RouteService
{
  /**
   * Compute the base slug
   * @return null|string 
   */
  public static function getApiSlug ()
  {
    $slug = trim(rtrim(kirby()->option('tritrics.aflever-api.slug'), '/'));
    if ( !is_string($slug) || ! strlen($slug) || substr($slug, 0, 1) !== '/') {
      return null;
    }
    return $slug;
  }

  /**
   * Check, if a slug the backend-user enters, has a conflict with the API-Route
   * @param mixed $slug 
   * @return bool 
   */
  public static function isProtectedSlug ($slug)
  {
    $path = strtolower(kirby()->option('tritrics.aflever-api.slug'));
    $slugs = explode('/', $path);
    return in_array(strtolower($slug), $slugs);
  }

  /**
   * Parse the given path and return language and node. In a multi language
   * installation, the first part of the path must be a valid language (which
   * is not validated here).
   * 
   * single language installation:
   * "/" -> site
   * "/some/page" -> page
   * 
   * multi language installation:
   * "/en" -> english version of site
   * "/en/some/page" -> english version of page "/some/path"
   * @param mixed $path 
   * @param bool $multilang
   * @return array 
   */
  public static function parsePath($path, $multilang) {
    $parts = array_filter(explode('/', $path));
    $lang = $multilang ? array_shift($parts) : null;
    $slug = count($parts) > 0 ? implode('/', $parts) : null;
    return [ $lang, $slug];
  }
}
