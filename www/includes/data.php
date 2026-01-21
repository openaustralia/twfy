<?php

/**
 * DATA class v1.1 2003-11-25
 * phil@gyford.com
 *
 * REQUIRED:
 * utiltity.php    v1.0
 * GLOBALS:
 * METADATAPATH    /Library/Webserver/haddock/includes/directory/metadata.php.
 *
 *
 * DOCUMENTATION:
 *
 * Instantiates itself as $DATA.
 *
 * Includes a metadata file that contains the actual data. It will have an array like:
 *
 * $this->page = array (
 * "default" => array (
 * "sitetitle"        => "Haddock Directory",
 * "session_vars" => array()
 * ),
 * "previous" => array (
 * "title"            => "Previous links",
 * "url"            => "previouslinks/",
 * "section"        => "blah"
 * )
 * etc...
 * );
 *
 * And a $this->section array, although this is as yet unspecified. Something like:
 *
 * $this->section = array (
 * "blah" => array (
 * "title"     => "Blah",
 * "menu"         => array (
 * "text"        => "Blah",
 * "title"        => "Here's a link to Blah"
 * )
 * )
 * );
 *
 *
 * PUBLICALLY ACCESSIBLE FUNCTIONS:
 *
 * set_section()            - Sets $this_section depending on this page's section.
 *
 * page_metadata(),
 * section_metadata()        - Returns an item of metadata for this page/section.
 *
 * set_page_metadata(),
 * set_section_metadata()    - Sets an item of metadata for this page/section.
 *
 *
 * NOTE:
 *
 * At some points we have a function where $type is passed in as, say, "page"
 * and then we do:
 * $dataarray =& $this->$type;
 * return $dataarray[$item][$key];
 *
 * Why? Because doing $this->$type[$item][$key] doesn't seem to work and
 * we need to use the reference to get it working.
 *
 *
 *
 * Versions
 * ========
 * v1.1    2003-11-25
 * Changed to using named constants, rather than global variables.
 */
class DATA {

  private $metadata;

  /**
   *
   */
  public function __construct() {

    // Defined in config.php.
    include_once METADATAPATH;
    $this->metadata = new Metadata();

  }

  //
  // PUBLIC METADATA ACCESS FUNCTIONS //
  // .

  /**
   * Special function for setting $this_section depending on the value of $this_page.
   */
  public function set_section() {
    // This should be called at the start of a page.
    global $this_section, $this_page;

    $this_section = $this->page_metadata($this_page, "section");
  }

  // Getting page and section metadata
  // $page/$section is a page name.

  /**
   * $key is the element of metadata you want to retrieve.
   */
  public function page_metadata($page, $key) {
    return $this->metadata->get_metadata(["page" => $page, "key" => $key], "page");
  }

  /**
   *
   */
  public function section_metadata($section, $key) {
    return $this->metadata->get_metadata(["section" => $section, "key" => $key], "section");
  }

  // Setting page and section.

  /**
   * $page/$section, $key and $value should make sense...
   */
  public function set_page_metadata($page, $key, $value) {
    $this->metadata->set_metadata(["page" => $page, "key" => $key, "value" => $value]);
  }

  /**
   *
   */
  public function set_section_metadata($section, $key, $value) {
    $this->metadata->set_metadata(["section" => $section, "key" => $key, "value" => $value]);
  }

  // DEPRECATED.

  /**
   * Directly access an item.
   */
  public function metadata($type, $item, $key) {
    return "ACCESS DEBIED FOR type[$item][$key] USE THE PUBLIC METHODS";
  }


}

$DATA = new DATA();
