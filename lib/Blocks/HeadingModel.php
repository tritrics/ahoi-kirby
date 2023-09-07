<?php

namespace Tritrics\Api\Blocks;

use Tritrics\Api\Data\Model;

/** */
class HeadingModel extends Model
{
  /** */
  protected $hasChildFields = true;

  /** */
  protected function getValue ()
  {
    // combine inline-html-field text with field level
    if ($this->fields->has('level') && $this->fields->has('text')) {
      
      // get values and delete nodes
      $elem = $this->fields->node('level', 'value')->get();
      $this->fields->unset('level');
      $value = $this->fields->node('text', 'value')->get();
      $this->fields->unset('text');

      // recombine
      $data = [ 'type' => 'html' ];
      if (is_array($value) && count($value) > 1) {
        $data['value'] = [
          'elem' => $elem,
          'children' => $value
        ];
      } else {
        $data['value'] = [
          'elem' => $elem,
          'text' => isset($value['text']) ? $value['text'] : ''
        ];
      }
      $this->fields->add('headline', $data);
    }
    return $this->fields;
  }
}
