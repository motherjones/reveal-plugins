<?php

abstract class ContextlyKitAssetsRenderer extends ContextlyKitBase {

  /**
   * @var ContextlyKitAssetsPackage[]
   */
  protected $packages;

  /**
   * @var array
   */
  protected $exposedTree;

  /**
   * @param ContextlyKit $kit
   * @param ContextlyKitAssetsPackage[] $packages
   * @param array $exposedTree
   */
  public function __construct($kit, $packages, $exposedTree) {
    parent::__construct($kit);

    $this->packages = $packages;
    $this->exposedTree = $exposedTree;
  }

  protected function buildJsCode($escapeHtml = TRUE) {
    if ($this->kit->isLiveMode()) {
      // All the JS code is included in aggregated packages.
      return '';
    }

    return $this->buildData($escapeHtml) . $this->buildExposedAssets($escapeHtml);
  }


  protected function buildData($escapeHtml = TRUE) {
    $versions = FALSE;
    $paths = array();
    foreach ($this->packages as $package) {
      if ($package->containsVersions()) {
        $versions = TRUE;
      }

      $paths += $package->buildDataPaths();
    }

    return $this->kit->newDataManager($paths)
      ->addVersions($versions)
      ->compile($escapeHtml);
  }

  protected function buildExposedAssets($escapeHtml = TRUE) {
    if (empty($this->exposedTree)) {
      return '';
    }

    return $this->kit->newExposedAssetsManager($this->exposedTree)
      ->compile($escapeHtml);
  }

  abstract public function renderCss();

  abstract public function renderJs();

  abstract public function renderTpl();

  abstract public function renderAll();

}
