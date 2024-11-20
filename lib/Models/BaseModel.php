<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Site;
use Kirby\Cms\Page;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Content\Field;
use Kirby\Cms\Block;
use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Basic model for Kirby Fields and Models.
 * Inherits from Collection and adds some model functions.
 */
class BaseModel extends Collection
{
  /**
   * the Kirby Blueprint fragment
   * 
   * @var Collection
   */
  protected $blueprint;

  /**
   * Can be used, if model should output different content in different cases.
   */
  protected $addDetails = false;

  /**
   * Output control of child fields.
   * 
   * @var array
   */
  protected $addFields = [];

  /**
   * 2-digit Language-code
   * 
   * @var ?string
   */
  protected $lang;

  /**
   * the Kirby model instance
   * 
   * @var mixed
   */
  protected $model;

  /**
   */
  public function __construct (
    Block|Field|User|File|Page|Site|null $model = null,
    Collection $blueprint = null,
    string $lang = null,
    array $addFields = [],
    bool $addDetails = false
  ) {
    $this->model = $model;
    $this->blueprint = $blueprint instanceof Collection ? $blueprint : new Collection();
    $this->lang = $lang;
    $this->addFields = is_array($addFields) ? $addFields : [];
    $this->addDetails = $addDetails;
  }

  /**
   * Get the corresponding label for the selected option.
   */
  protected function getLabel(mixed $value): mixed
  {
    $options = $this->blueprint->node('options');
    if ($options instanceof Collection && $options->count() > 0) {
      $options = $options->get(false);
      $type = $this->getOpionsType($options);
      if ($type === 'IS_STRING') {
        return isset($options[$value]) ? $options[$value] : $value;
      }
      if ($type === 'IS_KEY_VALUE') {
        foreach ($options as $entry) {
          if ($entry['value'] == $value) {
            return $entry['text'];
          }
        }
      }
    }
    return '';
  }

  /**
   * Helper for fields with option-node: Kirby allowes different type of options.
   * (So far we can only handle static options.)
   */
  private function getOpionsType(array $options): ?string
  {
    $values = array_values($options);
    if (isset($values[0])) {

      // for numeric keys Kirby uses options-def like:
      // - value: '100'
      //   text: Design
      // - value: '200'
      //   text: Architecture
      if (is_array($values[0]) && isset($values[0]['value']) && isset($values[0]['text'])) {
        return 'IS_KEY_VALUE';
      }

      // string keys like
      // - design: Design
      // - architecture: Architecture
      // or like
      // - center
      // - middle
      else if (is_string($values[0])) {
        return 'IS_STRING';
      }
    }
    return null;
  }
}