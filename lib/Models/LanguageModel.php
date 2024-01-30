<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Helper\LinkHelper;
use Tritrics\AflevereApi\v1\Helper\LanguagesHelper;

/**
 * Model for Kirby's language object
 */
class LanguageModel extends Model
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
  {
    $code = trim(strtolower($this->model->code()));
    $home = kirby()->site()->homePage();

    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('code', $code);
    $meta->add('default', $this->model->isDefault());
    if ($this->addDetails) {
      $meta->add('locale', LanguagesHelper::getLocale($code));
      $meta->add('direction', $this->model->direction());
    }
    $res->add('link', LinkHelper::getPage(
      LanguagesHelper::getUrl($code, $home->uri($code))
    ));
    if ($this->addDetails) {
      $res->add('terms', $this->model->translations());
    }
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue(): string
  {
    return $this->model->name();
  }
}
