<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Content\Field;
use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Model for Kirby's fields: object
 */
class ObjectModel extends BaseModel
{
  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasFields = true;

  /**
   * Nodename for fields.
   */
  protected $valueNodeName = 'fields';

  /**
   */
  public function __construct(
    Field $model,
    Collection $blueprint,
    string $lang = null,
    array $addFields = [],
  ) {
    // the object-fields makes no sense without the entries (same in StructureModel)
    $addFieldsObject = !is_array($addFields) || count($addFields) === 0 ? [ '*' ] : $addFields;
    parent::__construct($model, $blueprint, $lang, $addFieldsObject, false);
  }

  /**
   * Get the value of model.
   */
  protected function getValue(): Collection|null
  {
    return $this->fields;
  }
}