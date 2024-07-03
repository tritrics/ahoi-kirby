<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\LinkHelper;

/**
 * Model for Kirby's fields: url
 */
class UrlModel extends BaseModel
{
  protected function getType(): string
  {
    return 'link';
  }

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties (): Collection
  {
    $res = new Collection();
    $res->add('meta', LinkHelper::get(
      $this->model->value(),
      null,
      false,
      null,
      'url'
    ));
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue (): string
  {
    return (string) $this->model->value();
  }
}
