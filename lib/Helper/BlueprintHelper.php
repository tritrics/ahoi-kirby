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
   */
  private static $files = [];

  /**
   * Cache parsed blueprints.
   */
  private static $map = [];

  /**
   * Nodes in blueprints, that are excludes from result
   */
  private static $excludeNodes = [
    'after',
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
   * allow listed blueprints or
   * true = allow all, false = allow none
   * @see config.php
   */
  private static $access = null;

  /**
   * Check blueprint access settings.
   */
  private static function checkAccess(string $name): bool
  {
    return isset(self::$access[$name]) ? self::$access[$name] : self::$access['all'];
  }

  /**
   * Get the blueprint either from intern map or compute.
   * Map is used to avoid repetition, which may occour for files, users and pages.
   */
  public static function get(object $model): Collection
  {
    if (!is_array(self::$access)) {
      self::setAccess();
    }
    if ($model instanceof Page) {
      $folder = 'pages';
      $template = $model->intendedTemplate();
      $add_title_field = true;
    } elseif ($model instanceof Site) {
      $folder = '';
      $template = 'site';
      $add_title_field = true;
    } elseif ($model instanceof File) {
      $folder = 'files';
      $template = $model->template();
      $add_title_field = false;
    } elseif ($model instanceof User) {
      $folder = 'users';
      $template = $model->role();
      $add_title_field = false;
    }
    if (!$template) {
      return new Collection();
    }
    $path = trim($folder . '/' . $template, '/');
    $name = str_replace('/', '_', $path);

    // getting the blueprint
    if (!isset(self::$map[$name])) {
      self::$map[$name] = new Collection();
      self::$map[$name]->add('name', $path);

      // find blueprint-file by path
      $blueprint = self::getFile($path);
      $fields = [];
      if ($add_title_field) {
        $fields['title'] = [
          'type' => 'text',
          'required' => true,
        ];
      }
      $fields = array_merge($fields, self::getFieldsFromSectionsRec($blueprint));
      self::$map[$name]->add('fields', $fields);
    }
    return self::$map[$name];
  }

  /**
   * Get raw blueprint/fragment, avoid Exceptions.
   */
  private static function getFile(string $path): array
  {
    $name = str_replace('/', '_', TypeHelper::toString($path, true, true));
    if (!self::checkAccess($name)) {
      error_log($name);
      return [];
    }
    if (!isset(self::$files[$name])) {
      try {
        $blueprint = Blueprint::find($path);
        $blueprint = self::recursiveExtendBlueprint($blueprint);
        self::$files[$name] = self::normalizeValues($blueprint, ['type', 'extends']);
      } catch (NotFoundException $e) {
        self::$files[$name] = [];
      }
    }
    return self::$files[$name];
  }

  /**
   * Get fields from field-sections.
   */
  private static function getFieldsFromSectionsRec(array $nodes): array {
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
            $res = array_merge($res, self::getFieldsRec($section['fields']));
          }
        }
      }

      // fields
      // (may be in /files-blueprint)
      else if ($key === 'fields') {
        $res = array_merge($res, self::getFieldsRec($node));
      }
      
      // dig deeper
      else {
        $res = array_merge($res, self::getFieldsFromSectionsRec($node));
      }
    }
    return $res;
  }

  /**
   * Recursivly extracts all field definitions.
   */
  private static function getFieldsRec(array $fields): array {
    $res = [];
    foreach($fields as $key => $def) {

      // textnode or not a field, continue
      if (!is_array($def) || !isset($def['type'])) {
        continue;
      }

      // a fieldgroup, don't add group itself
      if ($def['type'] === 'group' && isset($def['fields'])) {
        $res = array_merge($res, self::getFieldsRec($def['fields']));
        continue;
      }

      // loop field properties
      $field = [];
      foreach ($def as $prop => $val) {
        if (!in_array($prop, self::$excludeNodes)) {
          $field[$prop] = $val;
        }
      }

      // parse subfields
      if (isset($def['fields'])) {
        $field['fields'] = self::getFieldsRec($def['fields']);
        if (count($field['fields']) === 0) {
          continue;
        }
      }

      // parse blocks
      if ($def['type'] === 'blocks' && isset($def['fieldsets'])) {
        $field['blocks'] = self::getBlocks($def['fieldsets']);
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
  private static function getBlocks(array $nodes): array {
    $res = [];
    foreach($nodes as $key => $def) {

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
      $blockfields = self::getFieldsRec($fields);
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

  /**
   * Recursive function to extend and normalise blueprint.
   */
  private static function recursiveExtendBlueprint(mixed $nodes): mixed
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
        $sub = self::recursiveExtendBlueprint($node);
        $nodes[$key] = $sub;
      }
    }
    return $nodes;
  }

  /**
   * Set access settings from config.php
   */
  private static function setAccess(): void
  {
    self::$access = [
      'all' => false
    ];
    $config = ConfigHelper::get('blueprints');
    if (TypeHelper::isTrue($config)) {
      self::$access['all'] = true;
    } else if (is_array($config)) {
      foreach($config as $blueprint => $access) {
        $name = str_replace('/', '_',TypeHelper::toString($blueprint, true, true));
        if (strlen($name) > 0) {
          self::$access[$name] = TypeHelper::toBool($access);
        }
      }
    }
  }
}
