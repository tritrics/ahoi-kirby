<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\BaseModel;
use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: radio, select, toggles
 */
class OptionModel extends BaseModel
{
  /**
   */
  public function __construct()
  {
    parent::__construct(...func_get_args());
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'option');
    // $this->add('label', $this->getLabel($this->model->value()));
    $this->add('value', TypeHelper::toChar($this->model->value()));
  }

  /**
   * Get the corresponding label for the selected option.
   */
  // protected function getLabel(mixed $value): mixed
  // {
  //   $options = $this->blueprint->node('options');
  //   if ($options instanceof Collection && $options->count() > 0) {
  //     $options = $options->get(false);
  //     $type = $this->getOpionsType($options);
  //     if ($type === 'IS_STRING') {
  //       return isset($options[$value]) ? $options[$value] : $value;
  //     }
  //     if ($type === 'IS_KEY_VALUE') {
  //       foreach ($options as $entry) {
  //         if ($entry['value'] == $value) {
  //           return $entry['text'];
  //         }
  //       }
  //     }
  //   }
  //   return '';
  // }

  /**
   * Helper for fields with option-node: Kirby allowes different type of options.
   * (So far we can only handle static options.)
   */
  //private function getOpionsType(array $options): ?string
  //{
  //  $values = array_values($options);
  //  if (isset($values[0])) {
//
  //    // for numeric keys Kirby uses options-def like:
  //    // - value: '100'
  //    //   text: Design
  //    // - value: '200'
  //    //   text: Architecture
  //    if (is_array($values[0]) && isset($values[0]['value']) && isset($values[0]['text'])) {
  //      return 'IS_KEY_VALUE';
  //    }
//
  //    // string keys like
  //    // - design: Design
  //    // - architecture: Architecture
  //    // or like
  //    // - center
  //    // - middle
  //    else if (is_string($values[0])) {
  //      return 'IS_STRING';
  //    }
  //  }
  //  return null;
  //}
}