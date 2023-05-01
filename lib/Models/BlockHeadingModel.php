<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Model;

/** */
class BlockHeadingModel extends Model
{
  /** */
  protected $hasChildFields = true;

  /** */
  protected function getValue ()
  {
    // combine inline-html-field text with field level
    if ($this->fields->has('level')) {
      $level = $this->fields->node('level', 'value')->get();
      $text = $this->fields->node('text', 'value')->get();
      $this->fields->node('text', 'value')->set('<' . $level . '>' . $text . '</' . $level . '>');
      $this->fields->node('text')->add('type', 'html');
      $this->fields->unset('level');
    }
    return $this->fields;
  }
}
