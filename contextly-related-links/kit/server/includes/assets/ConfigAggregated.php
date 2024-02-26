<?php

class ContextlyKitAssetsConfigAggregated extends ContextlyKitAssetsConfigBase {

  /**
   * @param ContextlyKit $kit
   */
  public function __construct($kit) {
    parent::__construct($kit);

    $this->config = array();
  }

  public function export() {
    return json_encode($this->config);
  }

  public function &__get($name) {
    return $this->config[$name];
  }

  public function __isset($name) {
    return isset($this->config[$name]);
  }

  public function __set($name, $value) {
    $this->config[$name] = $value;
  }

  public function __unset($name) {
    unset($this->config[$name]);
  }

}
