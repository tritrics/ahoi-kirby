<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Services\LinkService;


/** */
class LanguageModel extends Model
{
  protected $add_details;

  /** */
  public function __construct($model, $blueprint = null, $lang = null, $add_details = false)
  {
    $this->add_details = $add_details;
    parent::__construct($model);
  }

  /** */
  protected function getProperties()
  {
    $code = trim(strtolower($this->model->code()));
    $home = kirby()->site()->homePage();

    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('code', $code);
    $meta->add('default', $this->model->isDefault());
    if ($this->add_details) {
      $meta->add('locale', LanguagesService::getLocale($code));
      $meta->add('direction', $this->model->direction());
    }
    $res->add('link', LinkService::getPage(
      LanguagesService::getUrl($code, $home->uri($code))
    ));
    if ($this->add_details) {
      $res->add('terms', $this->model->translations());
    }
    return $res;
  }

  /** */
  protected function getValue()
  {
    return $this->model->name();
  }
}
