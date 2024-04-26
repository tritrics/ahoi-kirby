<?php

namespace Tritrics\Tric\v1\Models;

use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Helper\LinkHelper;
use Tritrics\Tric\v1\Helper\LanguagesHelper;

/**
 * Model for Kirby's language object
 */
class LanguageModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
  {
    $code = trim(strtolower($this->model->code()));
    $attr = LinkHelper::get(null, null, false, $code, 'page');

    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('code', $code);
    $meta->add('default', $this->model->isDefault());
    $meta->add('href', $attr['href']);
    $meta->add('title', $this->model->name());
    $meta->add('locale', LanguagesHelper::getLocale($code));
    $meta->add('direction', $this->model->direction());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue(): Collection|null
  {
    if (!$this->addDetails) {
      return null;
    }
    $res = new Collection();
    foreach($this->model->translations() as $key => $value) {
      $res->add($key, [
        'type' => 'string',
        'value' => $value
      ]);
    }
    return $res;
  }
}
