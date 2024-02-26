<?php

abstract class ContextlyKitAssetsPackageBase extends ContextlyKitBase {

  protected $js = array();

  protected $css = array();

  protected $tpl = array();

  protected $data = array();

  protected $media = array();

  /**
   * @var array
   *
   * Keys are exposed package names, values are ignored.
   */
  protected $expose = array();

  /**
   * @var array
   *
   * Keys are package names of dependencies, values are ignored.
   */
  protected $include = array();

  /**
   * Whether to add Kit version to the data.
   *
   * @var bool
   */
  protected $versions = FALSE;

  public function addExposed($packages) {
    $this->expose += $packages;

    return $this;
  }

  public function addIncluded($packages) {
    // Prepend includes to make deepest dependencies go first.
    $this->include = $packages + $this->include;

    return $this;
  }

  function getJs() {
    return array_keys($this->js);
  }

  function getCss() {
    return array_keys($this->css);
  }

  function getTpl() {
    return $this->tpl;
  }

  function getData() {
    return $this->data;
  }

  function getExposed() {
    return array_keys($this->expose);
  }

  function getIncluded() {
    return array_keys($this->include);
  }

  function getMedia() {
    return array_keys($this->media);
  }

  function containsVersions() {
    return $this->versions;
  }

  function toExposed() {
    $result = array(
      'dependencies' => $this->getIncluded(),
      'js' => $this->getJs(),
      'css' => $this->getCss(),
      'tpl' => $this->getTpl(),
    );
    return array_filter($result);
  }

}
