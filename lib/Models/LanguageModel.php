<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Helper\LinkHelper;

/**
 * Model for Kirby's language object
 */
class LanguageModel extends Model
{
  /**
   * @var Boolean
   */
  protected $add_details;

  /**
   * Constructor with additional property $add_details
   * 
   * @param Mixed $model 
   * @param Mixed $blueprint 
   * @param Mixed $lang 
   * @param Boolean $add_details 
   * @return Void 
   */
  public function __construct($model, $blueprint = null, $lang = null, $add_details = false)
  {
    $this->add_details = $add_details;
    parent::__construct($model);
  }

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
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
    $res->add('link', LinkHelper::getPage(
      LanguagesService::getUrl($code, $home->uri($code))
    ));
    if ($this->add_details) {
      $res->add('terms', $this->model->translations());
    }
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return String
   */
  protected function getValue()
  {
    return $this->model->name();
  }
}
