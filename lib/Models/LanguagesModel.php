<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Models\LanguageModel;

/** */
class LanguagesModel extends Model
{
  private $add_details;

  /** */
  public function __construct($model, $blueprint = null, $lang = null, $add_details = false)
  {
    parent::__construct($model);
    $this->add_details = $add_details;
  }
  
  protected function getProperties()
  {
    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('count', $this->model->count());
    $meta->add('default', $this->model->default()->code());
    return $res;
  }

  /** */
  protected function getValue()
  {
    $res = new Collection();
    foreach ($this->model as $language) {
      $model = new LanguageModel($language, null, null, $this->add_details);
      $res->push($model);
    }
    return $res;
  }
}
