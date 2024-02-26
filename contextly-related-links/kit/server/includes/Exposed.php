<?php

class ContextlyKitExposedAssetsManager extends ContextlyKitBase {

  /**
   * @var array
   */
  protected $tree = array();

  public function __construct($kit, $tree) {
    $this->tree = $tree;

    parent::__construct($kit);
  }

  public function compile($escapeHtml = TRUE, $namespace = 'assets') {
    return $this->kit->newJsExporter($this->tree)
      ->export($namespace, $escapeHtml);
  }

}
