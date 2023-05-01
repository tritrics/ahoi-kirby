<?php

namespace Tritrics\Api\Models;

use Collator;
use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LanguageService;

/** */
class TextModel extends Model
{
  /** */
  private $fieldtype;

  /** */
  private $type;

  /**
   * Possible Texttypes:
   * 
   * -------------------------------------------------------------------------------------------
   * | FIELD-TYPE   | FIELD-DEF              | FORMATTING | LINEBREAKS        | API-TYPE       | 
   * |--------------|------------------------|------------|-------------------|----------------|
   * | text, slug   |                        | ./.        | ./.               | text           |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | textarea     | buttons: false         | ./.        | \n                | text-multiline |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | textarea     | buttons: true          | markdown   | \n                | markdown       |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | textarea     | buttons: false|true    | html       | <blocks>          | html           |
   * |              | api: html: true        |            | <br>              |                |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | writer, list | inline: false          | html       | <blocks>          | html           |
   * |              |                        |            | <br>              |                |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | writer       | inline: true           | html       | <br>              | html-inline    |
   * |              |                        |            | no wrapping block |                |
   * -------------------------------------------------------------------------------------------
   * 
   * Textarea parsing as html is provided for older Kirby-projects, where writer-field was
   * not existing. In new projects writer should be used for html and textarea for text/markdown.
   * The possible combination: $textarea->kirbytext()->inline() is not provided, because the
   * field-buttons contains the block-elements headline and lists. These blocks are
   * stripped out by inline() which only makes sense, when the buttons are configured without.
   */
  protected function getType ()
  {
    // set properties
    $this->fieldtype = $this->blueprint->node('type')->get();

    switch ($this->fieldtype) {
      case 'textarea':
        if ($this->blueprint->node('api', 'html')->is(true)) {
          $this->type = 'html';
        } elseif ($this->blueprint->node('buttons')->is(false)) {
          $this->type = 'text-multiline';
        } else {
          $this->type = 'markdown';
        }
        break;
      case 'list':
        $this->type = 'html';
        break;
      case 'writer':
        if ($this->blueprint->node('inline')->is(true)) {
          $this->type = 'html-inline';
        } else {
          $this->type = 'html';
        }
        break;
      default: // text, slug
        $this->type = 'text';
        break;
    }
    return $this->type;
  }

  /** */
  protected function getValue ()
  {
    if ($this->type !== 'html' && $this->type !== 'html-inline') {
      return '' . $this->model->value();
    }

    if ($this->fieldtype === 'textarea') {
      $buffer = $this->model->kirbytext();
    } else if ($this->fieldtype === 'writer') {
      $buffer = $this->model->text();
    } else if ($this->fieldtype === 'list') {
      $buffer = $this->model->list();
    } else { // error
      return '';
    }

      // delete line breaks
    $buffer = str_replace(["\n", "\r", "\rn"], "", $buffer);

    // delete special elements
    $buffer = str_replace(["<figure>", "</figure>"], "", $buffer);

    // make HTML editabel
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $buffer . '</div>', LIBXML_HTML_NOIMPLIED);
    
    // parse links
    $dom = $this->parseLinks($dom);

    // ... more to come

    $html = substr($dom->saveHTML($dom->getElementsByTagName('div')->item(0)), 5, -6);

    // cosmetic
    $html = str_replace(["=\"\""], "", $html);
    return $html;
  }

  /**
   * Correct intern links, add target="_blank" to extern links
   */
  private function parseLinks ($dom)
  {
    $kirby = kirby();
    $site = site();
    $url = rtrim($site->url($this->lang), '/');
    $mediaUrl = rtrim($kirby->url('media'), '/');
    $homeSlug = $site->homePage()->uri($this->lang);
    $langSlug = LanguageService::getSlug($this->lang);

    $links = $dom->getElementsByTagName('a');
    foreach ($links as $link){
      $href = $link->getAttribute('href');

      // media link, keep as it is
      if (substr($href, 0, strlen($mediaUrl)) === $mediaUrl) {
        $link->setAttribute('data-link-file', null);
        //$link->setAttribute('target', '_blank');
        $link->removeAttribute('download');
      }

      // intern link starting with host
      else if (substr($href, 0, strlen($url)) === $url) {
        $href = substr($href, strlen($url));
        if ($href === '' . $langSlug || $href === '/' . $langSlug) {
          $href = '/' . ltrim($langSlug . '/' . $homeSlug, '/');
        } else {
          $href = '/' . ltrim($langSlug . $href, '/');
        }
        $link->setAttribute('href', $href);
        $link->setAttribute('data-link-intern', null);
      }
      
      // mailto
      else if (substr($href, 0, 7) === 'mailto:') {
        $link->setAttribute('data-link-email', null);
      }
      
      // tel
      else if (substr($href, 0, 4) === 'tel:') {
        $link->setAttribute('data-link-tel', null);
      }
      
      // anchor
      else if (substr($href, 0, 1) === '#') {
        $link->setAttribute('data-link-anchor', null);
      }

      // extern links
      else if (substr($href, 0, 7) === 'http://' || substr($href, 0, 8) === 'https://') {
        $link->setAttribute('data-link-extern', null);
      }
      
      // intern links like /some/path or some/path
      else {
        $link->setAttribute('data-link-intern', null);
      }
    }
    return $dom;
  }
}