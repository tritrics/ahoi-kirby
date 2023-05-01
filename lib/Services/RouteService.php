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
    $slug = trim(rtrim(kirby()->option('tritrics.restapi.slug'), '/'));
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
    $path = strtolower(kirby()->option('tritrics.restapi.slug'));
    $slugs = explode('/', $path);
    return in_array(strtolower($slug), $slugs);
  }
}
