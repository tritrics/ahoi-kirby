<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Models\LanguageModel;

/**
 * Model for Kirby's languages object 
 *
 * @package   AflevereAPI Models
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class LanguagesModel extends Model
{
  private $add_details;

  /**
   * Constructor with additional property $add_details
   * 
   * @param mixed $model 
   * @param mixed $blueprint 
   * @param mixed $lang 
   * @param bool $add_details 
   * @return void 
   */
  public function __construct($model, $blueprint = null, $lang = null, $add_details = false)
  {
    parent::__construct($model, $blueprint, $lang);
    $this->add_details = $add_details;
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
    $meta = $res->add('meta');
    $meta->add('count', $this->model->count());
    $meta->add('default', $this->model->default()->code());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return Collection
   */
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
