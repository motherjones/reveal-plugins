<?php

class ContextlyKitOverridesManager extends ContextlyKitBase {

  protected $overrides = array();

  public function __construct($kit, $overrides) {
    parent::__construct($kit);

    $this->overrides = $overrides;
  }

  public function compile($escapeHtml = TRUE, $namespace = 'overrides') {
    return $this->kit->newJsExporter($this->overrides)
      ->export($namespace, $escapeHtml);
  }

}
