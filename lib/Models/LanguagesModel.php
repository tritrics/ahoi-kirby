<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Model for Kirby's languages object
 */
class LanguagesModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('count', $this->model->count());
    $meta->add('default', $this->model->default()->code());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue(): Collection
  {
    $res = new Collection();
    foreach ($this->model as $language) {
      $model = new LanguageModel($language, null, null, $this->addDetails);
      $res->push($model);
    }
    return $res;
  }
}
