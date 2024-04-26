<?php

namespace Tritrics\Tric\v1\Models;

use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Helper\LinkHelper;

/**
 * Model for Kirby's fields: url
 */
class UrlModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties (): Collection
  {
    $res = new Collection();
    $res->add('link', LinkHelper::get(
      $this->model->value(),
      null,
      false,
      null,
      'url'
    ));
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue (): string
  {
    return (string) $this->model->value();
  }
}
