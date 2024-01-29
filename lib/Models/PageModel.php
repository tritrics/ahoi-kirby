<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Helper\LinkHelper;

/**
 * Model for Kirby's page object
 */
class PageModel extends Model
{
  /** */
  private $add_details;

  /**
   * Constructor with additional property $add_details
   * 
   * @param Mixed $model 
   * @param Mixed $blueprint 
   * @param Mixed $lang 
   * @param Boolean $add_details 
   * @return Void 
   */
  public function __construct ($model, $blueprint, $lang, $add_details = false)
  {
    $this->add_details = $add_details;
    parent::__construct($model, $blueprint, $lang);
  }

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties ()
  {
    $content = $this->model->content($this->lang);

    $res = new Collection();

    $meta = $res->add('meta');
    $meta->add('id', $this->model->id());
    $meta->add('parent', $this->getParentUrl($this->lang));
    $meta->add('slug',$this->model->slug($this->lang));
    if ($this->lang !== null) {
      $meta->add('lang', $this->lang);
      $meta->add('locale', LanguagesService::getLocale($this->lang));
    }
    $meta->add('title', $content->title()->get());
    $meta->add('status', $this->model->status());
    $meta->add('sort', (int) $this->model->num());
    $meta->add('modified',  date('c', $this->model->modified()));
    $meta->add('blueprint', (string) $this->model->intendedTemplate());
    $meta->add('home', $this->model->isHomePage());

    if ($this->blueprint->has('api', 'meta')) {
      foreach ($this->blueprint->node('api', 'meta')->get() as $key => $value) {
        if (!$meta->has($key)) {
          $meta->add($key, $value);
        }
      }
    }

    $res->add('link', LinkHelper::getPage(
      LanguagesService::getUrl($this->lang, $this->model->uri($this->lang))
    ));

    if ($this->add_details && LanguagesService::isMultilang()) {
      $translations = $res->add('translations');
      foreach(LanguagesService::list() as $code => $data) {
        $lang = $translations->add($code);
        $lang->add('type', 'url');
        $lang->add('link', LinkHelper::getPage(
          LanguagesService::getUrl($code, $this->model->uri($code))
        ));
        $lang->add('value', $data->node('name')->get());
      }
    }
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return Void
   */
  protected function getValue () {}

  /** */
  private function getParentUrl ($lang) : string
  {
    $langSlug = LanguagesService::getSlug($lang);
    $parent = $this->model->parent();
    if ($parent) {
      return '/' . ltrim($langSlug . '/' . $parent->uri($lang), '/');
    }
    return '/'; // or false?
  }
}