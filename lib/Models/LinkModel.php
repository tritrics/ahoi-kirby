<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Helper\LinkHelper;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Services\FileService;

/**
 * Model for Kirby's fields: link
 */
class LinkModel extends Model
{
  /**
   * Linktype, intern use
   * 
   * @var String [http, https, page, file, email, tel, anchor, custom]
   */
  private $linktype;

  /**
   * Constructor with additional initialization.
   * 
   * @param Mixed $model 
   * @param Mixed $blueprint 
   * @param Mixed $lang 
   * @param Boolean $add_details 
   * @return Void 
   */
  public function __construct($model, $blueprint, $lang)
  {
    $value = $model->value();
    if (str_starts_with($value, '#')) {
      $this->linktype = 'anchor';
    } else if (str_starts_with($value, 'mailto:')) {
      $this->linktype = 'email';
    } else if (str_starts_with($value, 'file://')) {
      $model = $model->toFile();
      $this->linktype = 'file';
    } else if (str_starts_with($value, 'page://')) {
      $model = $model->toPage();
      $this->linktype = 'page';
    } else if (str_starts_with($value, 'tel:')) {
      $this->linktype = 'tel';
    } else if (str_starts_with($value, 'http://')) {
      $this->linktype = 'http';
    } else if (str_starts_with($value, 'https://')) {
      $this->linktype = 'https';
    } else {
      $this->linktype = 'custom';
    }
    parent::__construct($model, $blueprint, $lang);
  }

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties()
  {
    $res = new Collection();
    switch($this->linktype) {
      case 'anchor':
        $res->add('link', LinkHelper::getAnchor($this->model->value()));
        break;
      case 'email':
        $res->add('link', LinkHelper::getEmail($this->model->value()));
        break;
      case 'file':
        $pathinfo = FileService::getPathinfo($this->model->url());
        $res->add('link', LinkHelper::getFile($pathinfo['path']));
        break;
      case 'page':
        $res->add('link', LinkHelper::getPage(
          LanguagesService::getUrl($this->lang, $this->model->uri($this->lang)))
        );
        break;
      case 'tel':
        $res->add('link', LinkHelper::getTel($this->model->value()));
        break;
      case 'http':
      case 'https':
        $res->add('link', LinkHelper::getUrl($this->model->value()));
        break;
      default:
        $res->add('link', LinkHelper::getCustom($this->model->value()));
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
    switch ($this->linktype) {
      case 'anchor':
        return substr($this->model->value(), 1);
      case 'email':
        return substr($this->model->value(), 7);
      case 'file':
        $title = (string) $this->model->title()->get();
        if (!$title) {
          $pathinfo = FileService::getPathinfo($this->model->url());
          $title = $pathinfo['file'];
        }
        return $title;
      case 'page':
        return $this->model->title()->get();
      case 'tel':
        return substr($this->model->value(), 4);
      case 'http':
        return substr($this->model->value(), 7);
      case 'https':
        return substr($this->model->value(), 8);
      default:
        return $this->model->value();
    }
  }
}


