<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Services\FileService;

/** */
class LinkModel extends Model
{
  /** */
  private $type;

  /** */
  public function __construct($model, $blueprint, $lang)
  {
    $value = $model->value();
    if (str_starts_with($value, '#')) {
      $this->type = 'anchor';
    } else if (str_starts_with($value, 'mailto:')) {
      $this->type = 'email';
    } else if (str_starts_with($value, 'file://')) {
      $model = $model->toFile();
      $this->type = 'file';
    } else if (str_starts_with($value, 'page://')) {
      $model = $model->toPage();
      $this->type = 'page';
    } else if (str_starts_with($value, 'tel:')) {
      $this->type = 'tel';
    } else if (str_starts_with($value, 'http://')) {
      $this->type = 'http';
    } else if (str_starts_with($value, 'https://')) {
      $this->type = 'https';
    } else {
      $this->type = 'custom';
    }
    parent::__construct($model, $blueprint, $lang);
  }

  /** */
  protected function getProperties()
  {
    $res = new Collection();
    switch($this->type) {
      case 'anchor':
        $res->add('link', LinkService::getAnchor($this->model->value()));
        break;
      case 'email':
        $res->add('link', LinkService::getEmail($this->model->value()));
        break;
      case 'file':
        $pathinfo = FileService::getPathinfo($this->model->url());
        $res->add('link', LinkService::getFile($pathinfo['path']));
        break;
      case 'page':
        $res->add('link', LinkService::getPage(
          LanguagesService::getUrl($this->lang, $this->model->uri($this->lang)))
        );
        break;
      case 'tel':
        $res->add('link', LinkService::getTel($this->model->value()));
        break;
      case 'http':
      case 'https':
        $res->add('link', LinkService::getUrl($this->model->value()));
        break;
      default:
        $res->add('link', LinkService::getCustom($this->model->value()));
    }
    return $res;
  }

  /** */
  protected function getValue()
  {
    switch ($this->type) {
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


