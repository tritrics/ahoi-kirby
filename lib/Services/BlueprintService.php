<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Cms\Blueprint;
use Kirby\Cms\Site;
use Kirby\Cms\Page;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Exception\NotFoundException;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Services\GlobalService;

/**
 * Reads a Kirby blueprint and translates it for internal needs.
 */
class BlueprintService
{
  /**
   * Cache Kirby's blueprint-files.
   * 
   * @var Array
   */
  private static $files = [];

  /**
   * Cache parsed blueprints.
   * 
   * @var Array
   */
  private static $map = [];

  /**
   * Get the blueprint either from intern map or compute.
   * Map is used to avoid repetition, which may occour for files, users and pages.
   * 
   * @param Object $model
   * @return Collection
   */
  public static function getBlueprint ($model)
  {
    if ($model instanceof Page) {
      $path = 'pages/' . $model->intendedTemplate();
      $add_title_field = true;
    } elseif ($model instanceof Site) {
      $path = 'site';
      $add_title_field = false;
    } elseif ($model instanceof File) {
      $path = 'files/' . $model->template();
      $add_title_field = true;
    } elseif ($model instanceof User) {
      $path = 'users/' . $model->role();
      $add_title_field = false;
    }

    $name = trim(str_replace('/', '_', $path), '_');
    if (!isset(self::$map[$name])) {
      self::$map[$name] = self::parse($path, $add_title_field);
    }
    return self::$map[$name];
  }

  /**
   * Get an instace of Collection with the relevant blueprint-information.
   * 
   * @param Mixed $path 
   * @param Mixed $add_title_field 
   * @return Collection 
   */
  private static function parse ($path, $add_title_field)
  {
    $res = new Collection();
    $res->add('name', $path);

    // find blueprint-file by path
    $blueprint = self::getBlueprintFile($path);
    if (isset($blueprint['api']) && is_array($blueprint['api'])) {
      $res->add('api', $blueprint['api']);
    }
    $fields = self::getFields($blueprint, false, $add_title_field, true);
    $res->add('fields', $fields);
    return $res;
  }

  /**
   * Get raw blueprint/fragment, avoid Exceptions.
   * 
   * @param String $path 
   * @return Array 
   */
  private static function getBlueprintFile ($path)
  {
    $name = trim(str_replace('/', '_', $path), '_');
    if (!isset(self::$files[$name])) {
      try {
        $blueprint = Blueprint::find($path);
        $blueprint = self::extend($blueprint);
        self::$files[$name] = GlobalService::normaliseArray($blueprint, ['api', 'type', 'extends']);
      } catch (NotFoundException $e) {
        self::$files[$name] = [];
      }
    }
    return self::$files[$name];
  }

  /**
   * Recursive function to extend and normalise blueprint.
   * 
   * @param Array $nodes 
   * @return Array 
   */
  private static function extend ($nodes)
  {
    // rewrite fieldsets of block, which can be notated like:
    // fieldsets:
    //   - fieldsetname
    // where fieldsetname is the file "blocks/fieldsetname.yml"
    if (is_array($nodes) && isset($nodes['type']) && $nodes['type'] === 'blocks') {
      if (isset($nodes['fieldsets'])) {
        foreach ($nodes['fieldsets'] as $key => $fieldsetname) {
          if (is_int($key) && is_string($fieldsetname)) {
            $nodes['fieldsets'][$fieldsetname] = 'blocks/' . $fieldsetname;
            unset($nodes['fieldsets'][$key]);
          }
        }
      }
    }

    // check if node has extension, either
    // nodename: path/to/fragement -or-
    // nodename:
    //   extends: path/to/fragment
    $path = null;
    if (is_array($nodes) && isset($nodes['extends']) && is_string($nodes['extends'])) {
      $path = $nodes['extends'];
      unset($nodes['extends']); // experimental
    } elseif (is_string($nodes)) {
      $path = $nodes;
    }
    if ($path && preg_match('#(blocks|fields|sections|tabs|layout)(/\w)+#', $path)) {
      $fragment = self::getBlueprintFile($path);
      $nodes = is_array($nodes) ? array_replace_recursive($fragment, $nodes) : $fragment;
    }

    // extension of sub-notes
    if (is_array($nodes)) {
      foreach ($nodes as $key => $node) {
        if ($key === 'extends' || $key === 'uploads') continue;
        $nodes[$key] = self::extend($node);
      }
    }
    return $nodes;
  }

  /**
   * Recursivly extracts all field definitions from blueprint array.
   * 
   * @param Array $nodes 
   * @param Boolean $publish 
   * @param Boolean $add_title_field 
   * @param Boolean $toplevel 
   * @return Array 
   */
  private static function getFields ($nodes, $publish, $add_title_field = false, $toplevel = false)
  {
    $res = [];
    if ($toplevel && $add_title_field) {
      $res['title'] = [
        'type' => 'text',
        'required' => true,
        'api' => true // always published, because there is no possiblity to configure in blueprint
      ];
    }
    foreach ($nodes as $key => $node) {
      if ($toplevel && in_array($key, ['title', 'options', 'api', 'status'])) {
        continue;
      }

      // fields-node, collect the childs
      if ($key === 'fields') {

        // loop fields
        foreach($node as $fieldname => $fielddef) {

          // check if it is published or invalid, otherwise skip
          if (!self::isPublished($fielddef, $publish) || !isset($fielddef['type'])) {
            continue;
          }
          $publish = self::isPublishedApplied($fielddef, $publish);

          // Block - special case, fields have different structure
          if ($fielddef['type'] === 'blocks') {
            $res[$fieldname] = [];

            // loop block properties
            foreach($fielddef as $property => $setting) {

              // fieldsets = blocks
              if ($property === 'fieldsets' && is_array($setting)) {
                $res[$fieldname]['blocks'] = [];
                foreach($setting as $fieldset => $block) {

                  // grouped blocks
                  if (isset($block['type']) && $block['type'] === 'group' && isset($block['fieldsets'])) {
                    foreach($block['fieldsets'] as $fieldset2 => $block2) {
                      if (!self::isPublished($block2, $publish)) {
                        continue;
                      }
                      $publish = self::isPublishedApplied($block2, $publish);
                      if (isset($block2['api'])) {
                        $res[$fieldname]['blocks'][$fieldset]['api'] = $block2['api'];
                      }
                      $res[$fieldname]['blocks'][$fieldset2]['fields'] = self::getFields($block2, $publish);
                    }
                  }
                  
                  // ungrouped blocks
                  else {
                    if (!self::isPublished($block, $publish)) {
                      continue;
                    }
                    $publish = self::isPublishedApplied($block, $publish);
                    if (isset($block['api'])) {
                      $res[$fieldname]['blocks'][$fieldset]['api'] = $block['api'];
                    }
                    $res[$fieldname]['blocks'][$fieldset]['fields'] = self::getFields($block, $publish);
                  }
                }
              }
              
              // other block properties
              else {
                $res[$fieldname][$property] = $setting;
              }
            }
          }
          
          // Field
          else {
            $res[$fieldname] = [];
            foreach($fielddef as $property => $setting) {
              if (is_array($setting) && $property === 'fields') {
                $res[$fieldname][$property] = self::getFields($fielddef, $publish);
              } else {
                $res[$fieldname][$property] = $setting;
              }
            }
          }
        }
      }
      
      // no fields, search deeper
      elseif (is_array($node)) {
        $res = array_merge($res, self::getFields($node, $publish));
      }
    }
    return $res;
  }

  /**
   * Check field definition for publish-settings:
   * fieldname:
   *   api: publish -or-
   *   api:
   *     publish: true
   * 
   * @param Array $def 
   * @param Boolean $publish_default 
   * @return Boolean 
   */
  private static function isPublished ($def, $publish_default)
  {
    if (is_array($def) && isset($def['api'])) {
      if (is_bool($def['api'])) {
        return !!$def['api'];
      } elseif (
        is_array($def['api']) &&
        isset($def['api']['publish']) &&
        is_bool($def['api']['publish'])
      ) {
        return !!$def['api']['publish'];
      }
    }
    return $publish_default;
  }

  /**
   * Check field defintion for applied publish-settings.
   * 
   * @param Array $def 
   * @param Boolean $publish_default 
   * @return Boolean 
   */
  private static function isPublishedApplied ($def, $publish_default)
  {
    if (
      is_array($def) &&
      isset($def['api']) &&
      is_array($def['api']) &&
      isset($def['api']['apply']) &&
      is_bool($def['api']['apply'])
    ) {
      return !!$def['api']['apply'];
    }
    return $publish_default;
  }
}
