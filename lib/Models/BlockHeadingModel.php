<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Model for Kirby's block: headline
 */
class BlockHeadingModel extends BaseModel
{
  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasChildFields = true;

  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   */
  protected function getType(): string
  {
    return 'block';
  }

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $res->add('block', 'heading');
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue (): Collection
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
