<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\FieldHelper;

/**
 * Basic model for Kirby Fields and Models with fields.
 */
class BaseFieldsModel extends BaseModel
{
  /**
   * Fields
   */
  protected $fields = null;

  /**
   */
  public function __construct()
  {
    parent::__construct(...func_get_args());
    $this->setFields();
  }

  /**
   * Set fields, if $this->hasFields is set to true.
   */
  protected function setFields(): void
  {
    $this->fields = new Collection();
    if ($this->blueprint->has('fields')) {

      // Inconsistency in Kirby's field definition
      // furthermore $this->lang is not documented and maybe not working for toObject()
      if ($this->blueprint->node('type')->is('object')) {
        $fields = $this->model->toObject($this->lang)->fields();
      } else {
        $fields = $this->model->content($this->lang)->fields();
      }
      FieldHelper::addFields(
        $this->fields,
        $fields,
        $this->blueprint->node('fields'),
        $this->lang,
        $this->addFields
      );
    }
  }
}
