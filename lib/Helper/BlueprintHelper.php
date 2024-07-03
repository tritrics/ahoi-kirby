<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Cms\Blueprint;
use Kirby\Cms\Site;
use Kirby\Cms\Page;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Exception\NotFoundException;
use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Reads a Kirby blueprint and translates it for internal needs.
 */
class BlueprintHelper
{
  /**
   * Cache Kirby's blueprint-files.
   * 
   * @var array
   */
  private static $files = [];

  /**
   * Cache parsed blueprints.
   * 
   * @var array
   */
  private static $map = [];

  private static $excludeNodes = [
    'after',
    'api',
    'autofocus',
    'before',
    'blocks',  // parsed separately
    'calendar',
    'fields', // parsed separately
    'font',
    'grow',
    'help',
    'icon',
    'image',
    'label',
    'labels',
    'placeholder',
    'preview',
    'reset',
    'translate',
    'uploads',
    'width',
  ];

  /**
   * Recursive function to extend and normalise blueprint.
   */
  private static function extendRec(mixed $nodes): mixed
  {
    // rewrite fieldsets of block, which can be notated like - fieldsetname
    // where fieldsetname is the file "blocks/fieldsetname.yml"
    if (is_array($nodes) && isset($nodes['type']) && $nodes['type'] === 'blocks' && isset($nodes['fieldsets'])) {
      $fieldsets = [];
      foreach ($nodes['fieldsets'] as $key => $def) {

        // blocks can be grouped, remove groups
        if (isset($def['type']) && $def['type'] === 'group' && isset($def['fieldsets'])) {
          foreach ($def['fieldsets'] as $key2 => $def2) {
            if (is_int($key2) && is_string($def2)) {
              $fieldsets[$def2] = 'blocks/' . $def2;
            } else {
              $fieldsets[$key2] = $def2;
            }
          }
        } else {
          if (is_int($key) && is_string($def)) {
            $fieldsets[$def] = 'blocks/' . $def;
          } else {
            $fieldsets[$key] = $def;
          }
        }
      }
      $nodes['fieldsets'] = $fieldsets;
    }

    // check if node has extension, either
    // nodename: path/to/fragement -or-
    // nodename:
    //   extends: path/to/fragment
    $path = null;
    if (is_array($nodes) && isset($nodes['extends']) && is_string($nodes['extends'])) {
      $path = $nodes['extends'];
      unset($nodes['extends']);
    } elseif (is_string($nodes)) {
      $path = $nodes;
    }
    if ($path && preg_match('#(blocks|fields|sections|tabs|layout)(/\w)+#', $path)) {
      $fragment = self::getFile($path);
      $nodes = is_array($nodes) ? array_replace_recursive($fragment, $nodes) : $fragment;
    }

    // extension of sub-notes
    if (is_array($nodes)) {
      foreach ($nodes as $key => $node) {
        if ($key === 'extends' || $key === 'uploads') continue;
        $sub = self::extendRec($node);
        $nodes[$key] = $sub;
      }
    }
    return $nodes;
  }

  /**
   * Get the blueprint either from intern map or compute.
   * Return only fields with api-node = true.
   * Map is used to avoid repetition, which may occour for files, users and pages.
   */
  public static function get(object $model): Collection
  {
    if ($model instanceof Page) {
      $path = 'pages/' . $model->intendedTemplate();
      $add_title_field = true;
    } elseif ($model instanceof Site) {
      $path = 'site';
      $add_title_field = true;
    } elseif ($model instanceof File) {
      $path = 'files/' . $model->template();
      $add_title_field = false;
    } elseif ($model instanceof User) {
      $path = 'users/' . $model->role();
      $add_title_field = false;
    }
    $name = trim(str_replace('/', '_', $path), '_');
    if (!isset(self::$map[$name])) {
      self::$map[$name] = self::getBlueprint($path, $add_title_field);
    }
    return self::$map[$name];
  }

  /**
   * Main entry point of parsing a blueprint.
   * Get an instace of Collection with the relevant blueprint-information.
   */
  private static function getBlueprint(string $path, bool $add_title_field): Collection
  {
    $res = new Collection();
    $res->add('name', $path);

    // find blueprint-file by path
    $blueprint = self::getFile($path);
    $publish = self::isPublishedApplied($blueprint, false);
    if (isset($blueprint['api']) && is_array($blueprint['api'])) {
      unset($blueprint['api']['publish']);
      unset($blueprint['api']['extend']);
      $res->add('api', $blueprint['api']);
    }
    $fields = [];
    if ($add_title_field) {
      $fields['title'] = [
        'type' => 'text',
        'required' => true,
      ];
    }
    
    $fields = array_merge($fields, self::getFieldsFromSectionsRec($blueprint, $publish));
    $res->add('fields', $fields);
    return $res;
  }

  /**
   * Get raw blueprint/fragment, avoid Exceptions.
   */
  private static function getFile(string $path): array
  {
    $name = trim(str_replace('/', '_', $path), '_');
    if (!isset(self::$files[$name])) {
      try {
        $blueprint = Blueprint::find($path);
        $blueprint = self::extendRec($blueprint);
        self::$files[$name] = self::normalizeValues($blueprint, ['api', 'type', 'extends']);
      } catch (NotFoundException $e) {
        self::$files[$name] = [];
      }
    }
    return self::$files[$name];
  }

  /**
   * Get fields from field-sections.
   */
  private static function getFieldsFromSectionsRec(array $nodes, bool $publish): array {
    $res = [];
    foreach ($nodes as $key => $node) {

      // textnode, continue
      if (!is_array($node)) {
        continue;
      }

      // any section (fields, files, pages, info, stats)
      if ($key === 'sections') {
        foreach($node as $section) {

          // a field-section, get the fields
          if (
            isset($section['type']) &&
            $section['type'] === 'fields' &&
            isset($section['fields']) &&
            is_array($section['fields']) &&
            count($section['fields']) > 0
          ) {
            $res = array_merge($res, self::getFieldsRec($section['fields'], $publish));
          }
        }
      }

      // fields
      // (may be in /files-blueprint)
      else if ($key === 'fields') {
        $res = array_merge($res, self::getFieldsRec($node, $publish));
      }
      
      // dig deeper
      else {
        $res = array_merge($res, self::getFieldsFromSectionsRec($node, $publish));
      }
    }
    return $res;
  }

  /**
   * Recursivly extracts all field definitions.
   */
  private static function getFieldsRec(array $fields, bool $publish): array {
    $res = [];
    foreach($fields as $key => $def) {

      // textnode or not a field, continue
      if (!is_array($def) || !isset($def['type'])) {
        continue;
      }

      // a fieldgroup, don't add group itself
      if ($def['type'] === 'group' && isset($def['fields'])) {
        $publishField = self::isPublishedApplied($def, $publish);
        $res = array_merge($res, self::getFieldsRec($def['fields'], $publishField));
        continue;
      }

      // check publish
      if (!self::isPublished($def, $publish)) {
        continue;
      }
      $publishField = self::isPublishedApplied($def, $publish);

      // loop field properties
      $field = [];
      foreach ($def as $prop => $val) {
        if (!in_array($prop, self::$excludeNodes)) {
          $field[$prop] = $val;
        }
      }

      // parse subfields
      if (isset($def['fields'])) {
        $field['fields'] = self::getFieldsRec($def['fields'], $publishField);
        if (count($field['fields']) === 0) {
          continue;
        }
      }

      // parse blocks
      if ($def['type'] === 'blocks' && isset($def['fieldsets'])) {
        $field['blocks'] = self::getBlocks($def['fieldsets'], $publishField);
        if (count($field['blocks']) === 0) {
          continue;
        }
      }
      $res[$key] = $field;
    }
    return $res;
  }

  /**
   * Blocks have an unique structure.
   */
  private static function getBlocks(array $nodes, bool $publish): array {
    $res = [];
    foreach($nodes as $key => $def) {

      // check publish
      if (!self::isPublished($def, $publish)) {
        continue;
      }
      $publishBlock = self::isPublishedApplied($def, $publish);

      // get fields, may be in tabs
      $fields = [];
      if (isset($def['tabs'])) {
        foreach($def['tabs'] as $tab) {
          if (isset($tab['fields'])) {
            $fields = array_merge($fields, $tab['fields']);
          }
        }
      } else if (isset($def['fields'])) {
        $fields = $def['fields'];
      }

      // parse fields
      $blockfields = self::getFieldsRec($fields, $publishBlock);
      if (count($blockfields) === 0) {
        continue;
      }
      $res[$key] = [
        'fields' => $blockfields
      ];
    }
    return $res;
  }

  /**
   * Check field definition for publish-settings:
   * fieldname:
   *   api: publish -or-
   *   api:
   *     publish: true
   */
  private static function isPublished(array $def, bool $publish_default): bool
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
   */
  private static function isPublishedApplied(array $def, bool $publish_default): bool
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

  /**
   * Helper to convert toChar() for given $nodes in $arr.
   */
  private static function normalizeValues(array $arr, array|bool $keys = false): array
  {
    $res = [];
    foreach ($arr as $key => $value) {
      $key = TypeHelper::toChar($key, true, true);
      if (is_array($value)) {
        $res[$key] = self::normalizeValues(
          $value,
          (is_array($keys) && in_array($key, $keys)) ? true : $keys
        );
      } elseif ($keys === true || (is_array($keys) && in_array($key, $keys))) {
        $res[$key] = TypeHelper::toChar($value, true, true);
      } else {
        $res[$key] = $value;
      }
    }
    return $res;
  }
}
