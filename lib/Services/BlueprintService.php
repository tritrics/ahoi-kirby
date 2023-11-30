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

class BlueprintService
{
  /**
   * cache Kirby's blueprint-files
   */
  private static $files = [];

  /**
   * cache parsed blueprints
   */
  private static $map = [];

  /**
   * Get the blueprint either from intern map or compute.
   * Map is used to avoid repetition, which may occour for files, users and pages.
   * 
   * @param (string) $path
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
   * @param (string) $path
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
   * get raw blueprint/fragment, avoid Exceptions
   * 
   * @param (string) $path
   * @return array
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
   * @param (array) $nodes
   * @return array
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
   * recursivly extracts all field definitions from blueprint array
   * 
   * @param (array) $nodes
   * @return array
   */
  private static function getFields ($nodes, $publish_inherited, $add_title_field = false, $toplevel = false)
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
          if (!self::isPublished($fielddef, $publish_inherited) || !isset($fielddef['type'])) {
            continue;
          }
          $publish_inherited = self::getApply($fielddef, $publish_inherited);

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
                      if (!self::isPublished($block2, $publish_inherited)) {
                        continue;
                      }
                      $publish_inherited = self::getApply($block2, $publish_inherited);
                      if (isset($block2['api'])) {
                        $res[$fieldname]['blocks'][$fieldset]['api'] = $block2['api'];
                      }
                      $res[$fieldname]['blocks'][$fieldset2]['fields'] = self::getFields($block2, $publish_inherited);
                    }
                  }
                  
                  // ungrouped blocks
                  else {
                    if (!self::isPublished($block, $publish_inherited)) {
                      continue;
                    }
                    $publish_inherited = self::getApply($block, $publish_inherited);
                    if (isset($block['api'])) {
                      $res[$fieldname]['blocks'][$fieldset]['api'] = $block['api'];
                    }
                    $res[$fieldname]['blocks'][$fieldset]['fields'] = self::getFields($block, $publish_inherited);
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
                $res[$fieldname][$property] = self::getFields($fielddef, $publish_inherited);
              } else {
                $res[$fieldname][$property] = $setting;
              }
            }
          }
        }
      }
      
      // no fields, search deeper
      elseif (is_array($node)) {
        $res = array_merge($res, self::getFields($node, $publish_inherited));
      }
    }
    return $res;
  }

  /**
   * Check field definition for publish-settings:
   * 
   * fieldname:
   *   api: publish -or-
   *   api:
   *     publish: true
   * 
   * @param mixed $def 
   * @return bool 
   */
  private static function isPublished ($def, $publish_inherited)
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
    return $publish_inherited;    
  }

  private static function getApply ($def, $publish_inherited)
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
    return $publish_inherited;
  }

  // private static function log($val)
  // {
  //   if ($val instanceof Collection) {
  //     error_log(print_r($val->get(), true));
  //   } else if (is_array($val)) {
  //     error_log(print_r($val, true));
  //   } else {
  //     error_log($val);
  //   }
  // }
}
