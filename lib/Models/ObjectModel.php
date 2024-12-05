<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Content\Field;
use Tritrics\Ahoi\v1\Data\FieldsModel;
use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Model for Kirby's fields: object
 */
class ObjectModel extends FieldsModel
{
  /**
   */
  public function __construct(
    Field $model,
    Collection $blueprint,
    string $lang = null,
    array $addFields = [],
    bool $addLanguages = false
  ) {
    $addFields = array_merge(['*'], is_array($addFields) ? $addFields : []);
    parent::__construct($model, $blueprint, $lang, $addFields, $addLanguages);
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'object');

    // fields
    if ($this->fields->count() > 0) {
      $this->add('fields', $this->fields);
    }
  }
}