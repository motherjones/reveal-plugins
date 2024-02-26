<?php

class ContextlyKitDataManager extends ContextlyKitBase {

  protected $files;

  protected $versions = FALSE;

  /**
   * @param ContextlyKit $kit
   * @param array $files
   *   Keys are resulting data name, values are full paths to JSON files.
   */
  public function __construct($kit, $files) {
    $this->files = $files;

    parent::__construct($kit);
  }

  protected function parseFile($path) {
    $content = file_get_contents($path);
    $content = json_decode($content, TRUE);
    if ($content === NULL) {
      throw $this->kit->newException('Unable to decode JSON data at ' . $path);
    }

    return $content;
  }

  public function parse() {
    $result = array();

    foreach ($this->files as $name => $path) {
      $result[$name] = $this->parseFile($path);
    }

    if ($this->versions) {
      $result['version'] = $this->getVersions();
    }

    return $result;
  }

  public function addVersions($newValue = TRUE) {
    $this->versions = $newValue;

    return $this;
  }

  protected function getVersions() {
    return array(
      'kit' => $this->kit->version(),
    );
  }

  public function compile($escapeHtml = TRUE, $namespace = 'data') {
    $vars = array();

    foreach ($this->files as $name => $path) {
      $vars[$name] = $this->parseFile($path);
    }

    if ($this->versions) {
      $vars['versions'] = $this->getVersions();
    }

    return $this->kit->newJsExporter($vars)
      ->export($namespace, $escapeHtml);
  }

}
