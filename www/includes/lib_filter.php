<?php

/**
 * Lib_filter.txt.
 *
 * A PHP HTML filtering library
 * Release 6 (10th May 2004)
 *
 * http://iamcal.com/publish/articles/php/processing_html/
 * http://iamcal.com/publish/articles/php/processing_html_part_2/
 *
 * (C)2001-2004 Cal Henderson <cal@iamcal.com>
 */


$filter = new lib_filter();
/**
 *
 */
class lib_filter {
  public $tag_counts = [];

  //
  // Tags and attributes that are allowed.
  /**
   * .
   */

  public $allowed = [
    'a' => ['href', 'target'],
  // 'img' => array('src', 'width', 'height', 'alt'),
    'b' => [],
  ];


  /**
   * Tags which should always be self-closing (e.g. "<img />")
   */

  /**
   * 'img',
   */
  public $no_close = [];
  /**
   * Tags which must always have seperate opening and closing tags (e.g. "<b></b>")
   */

  public $always_close = ['a', 'b', 'i', 'em', 'strong'];
  /**
   * Attributes which should be checked for valid protocols.
   */

  public $protocol_attributes = ['src', 'href'];
  //
  // Protocols which are allowed.
  /**
   * .
   */

  public $allowed_protocols = ['http', 'ftp', 'mailto'];
  //
  // Tags which should be removed if they contain no content (e.g. "<b></b>" or "<b />")
  /**
   *
   */

  public $remove_blanks = ['a', 'b'];

  /**
   *
   */
  public function go($data) {
    $this->tag_counts = [];
    $data = $this->balance_html($data);
    $data = $this->check_tags($data);
    $data = $this->process_remove_blanks($data);
    return $data;
  }

  /**
   *
   */
  public function balance_html($data) {
    $data = preg_replace("/<([^>]*?)(?=<|$)/", "<$1>", $data);
    $data = preg_replace("/(^|>)([^<]*?)(?=>)/", "$1<$2", $data);
    return $data;
  }

  /**
   *
   */
  public function check_tags($data) {
    $data = preg_replace_callback("/<(.*?)>/s", function ($matches) {
      return $this->process_tag(stripslashes($matches[1]));
    }, $data);

    foreach (array_keys($this->tag_counts) as $tag) {
      for ($i = 0; $i < $this->tag_counts[$tag]; $i++) {
        $data .= "</$tag>";
      }
    }

    return $data;
  }

  /**
   *
   */
  public function process_tag($data) {
    // Ending tags.
    if (preg_match("/^\/([a-z0-9]+)/si", $data, $matches)) {
      $name = strtolower($matches[1]);
      if (in_array($name, array_keys($this->allowed))) {
        if (!in_array($name, $this->no_close)) {
          if (isset($this->tag_counts[$name])) {
            $this->tag_counts[$name]--;
            return '</' . $name . '>';
          }
        }
      }
      else {
        return '';
      }
    }

    // Starting tags.
    if (preg_match("/^([a-z0-9]+)(.*?)(\/?)$/si", $data, $matches)) {
      $name = strtolower($matches[1]);
      $body = $matches[2];
      $ending = $matches[3];
      if (in_array($name, array_keys($this->allowed))) {
        $params = "";
        preg_match_all("/([a-z0-9]+)=\"(.*?)\"/si", $body, $matches_2, PREG_SET_ORDER);
        preg_match_all("/([a-z0-9]+)=([^\"\s]+)/si", $body, $matches_1, PREG_SET_ORDER);
        $matches = array_merge($matches_1, $matches_2);
        foreach ($matches as $match) {
          $pname = strtolower($match[1]);
          if (in_array($pname, $this->allowed[$name])) {
            $value = $match[2];
            if (in_array($pname, $this->protocol_attributes)) {
              $value = $this->process_param_protocol($value);
            }
            $params .= " $pname=\"$value\"";
          }
        }
        if (in_array($name, $this->no_close)) {
          $ending = ' /';
        }
        if (in_array($name, $this->always_close)) {
          $ending = '';
        }
        if (!$ending) {
          if (isset($this->tag_counts[$name])) {
            $this->tag_counts[$name]++;
          }
          else {
            $this->tag_counts[$name] = 1;
          }
        }
        if ($ending) {
          $ending = ' /';
        }
        return '<' . $name . $params . $ending . '>';
      }
      else {
        return '';
      }
    }

    // garbage, ignore it.
    return '';
  }

  /**
   *
   */
  public function process_param_protocol($data) {
    if (preg_match("/^([^:]+)\:/si", $data, $matches)) {
      if (!in_array($matches[1], $this->allowed_protocols)) {
        $data = '#' . substr($data, strlen($matches[1]) + 1);
      }
    }

    return $data;
  }

  /**
   *
   */
  public function process_remove_blanks($data) {
    foreach ($this->remove_blanks as $tag) {
      $data = preg_replace("/<{$tag}[^>]*><\\/{$tag}>/", '', $data);
      $data = preg_replace("/<{$tag}[^>]*\\/>/", '', $data);
    }
    return $data;
  }

}
