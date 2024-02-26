<?php

class ContextlyKitAssetsConfig extends ContextlyKitAssetsConfigBase {

  protected $filepath;

  function __construct($kit, $name, $path) {
    parent::__construct($kit);

    $this->name = $name;
    $this->filepath = $path;
  }

  protected function load() {
    // Load only once.
    if (isset($this->config)) {
      return;
    }

    if (!file_exists($this->filepath)) {
      throw $this->kit->newException("Unable to load config at {$this->filepath}");
    }

    $content = file_get_contents($this->filepath);
    if ($content === FALSE) {
      throw $this->kit->newException("Config at {$this->filepath} is empty.");
    }

    $config = json_decode($content);
    if (!isset($config)) {
      throw $this->kit->newException("Unable to decode config at {$this->filepath}");
    }

    $this->config = $config;
  }

  function getFilepath() {
    return $this->filepath;
  }

  function getName() {
    return $this->name;
  }

  function __isset($name) {
    $this->load();
    return isset($this->config->{$name});
  }

  function __get($name) {
    $this->load();
    return $this->config->{$name};
  }

}
