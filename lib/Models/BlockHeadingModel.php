<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;

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
   * Nodename for fields.
   */
  protected $valueNodeName = 'fields';

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
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   */
  protected function getType(): string
  {
    return 'block';
  }

  /**
   * Get the value of model.
   */
  protected function getValue (): Collection
  {
    // combine inline-html-field text with field level
    if ($this->fields->has('level') && $this->fields->has('text')) {
      
      // combine level and text to html-element headline
      $this->fields->add('headline', [
        'type' => 'html',
        'value' => [
          'elem' => $this->fields->node('level', 'value')->get(),
          'value' => $this->fields->node('text', 'value')->get()
        ],
      ]);

      $this->fields->unset('level');
      $this->fields->unset('text');
    }
    return $this->fields;
  }
}
