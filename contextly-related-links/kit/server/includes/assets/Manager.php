<?php

class ContextlyKitAssetsManager extends ContextlyKitBase {

  /**
   * @var ContextlyKitAssetsConfig[]
   */
  protected $configs = array();

  /**
   * @var ContextlyKitAssetsPackage[]
   */
  protected $packages = array();

  protected $configsPath;

  public function __construct($kit) {
    parent::__construct($kit);

    $this->configsPath = $this->kit->getFolderPath('config', TRUE);
  }

  /**
   * @param string $packageName
   *
   * @return ContextlyKitAssetsConfig
   */
  public function getConfig($packageName) {
    if (!isset($this->configs[$packageName])) {
      $path = $this->configsPath . '/' . $packageName . '.json';
      $this->configs[$packageName] = $this->kit->newAssetsConfig($packageName, $path);
    }

    return $this->configs[$packageName];
  }

  /**
   * @param string $packageName
   *
   * @return ContextlyKitAssetsPackage
   */
  public function getPackage($packageName) {
    if (!isset($this->packages[$packageName])) {
      $package = $this->kit->newAssetsPackage();
      $this->fillPackage($packageName, $package);
      $this->packages[$packageName] = $package;
    }

    return $this->packages[$packageName];
  }

  /**
   * @param $parentName
   * @param ContextlyKitAssetsPackage $package
   * @param array $utilized
   */
  protected function fillPackage($parentName, $package, &$utilized = array()) {
    $utilized[$parentName] = TRUE;

    $config = $this->getConfig($parentName);
    if (!empty($config->include)) {
      foreach ($config->include as $childName) {
        if (isset($utilized[$childName])) {
          continue;
        }

        $includedConfig = $this->getConfig($childName);
        if (!empty($includedConfig->aggregate)) {
          // Aggregated configs are kept as includes.
          $package->addIncluded(array(
            $childName => TRUE,
          ));
        }
        else {
          // Non-aggregated are merged into the package.
          $this->fillPackage($childName, $package, $utilized);
        }
      }
    }

    if (!empty($config->expose)) {
      // Exposed
      $package->addExposed(array_flip($config->expose));
    }

    $package->addConfig($config);
  }

  public function getPackageWithDependencies($packageName, $ignore = array()) {
    $package = $this->getPackage($packageName);

    $result = array();
    foreach (array_diff($package->getIncluded(), $ignore) as $includeName) {
      $result += $this->getPackageWithDependencies($includeName);
    }
    $result[$packageName] = $package;

    return $result;
  }

  public function buildExposedTree($packageNames, $isTopLevel = TRUE) {
    $tree = array();

    foreach ($packageNames as $name) {
      $package = $this->getPackage($name);

      // Exposed packages go to a deeper level, we make them available to the
      // package manager as soon as the package itself is loaded.
      $subtree = array();
      $exposed = $package->getExposed();
      if (!empty($exposed)) {
        $subtree = $this->buildExposedTree($exposed, FALSE);
      }

      // Dependencies go to the same level, so that they are available at the
      // same time with the package itself.
      $included = $package->getIncluded();
      $included = array_diff($included, array_keys($tree));
      if (!empty($included)) {
        $tree += $this->buildExposedTree($included, $isTopLevel);
      }

      if ($isTopLevel) {
        // We only want to see sub-tree of exposed packages on the top level,
        // the package itself will be loaded by the renderer anyway.
        $tree += $subtree;
      }
      else {
        // In case current list of packages was exposed by parents, we want to
        // render their full structure.
        $leaf = $package->toExposed();
        if (!empty($subtree)) {
          $leaf['expose'] = $subtree;
        }
        if (!empty($leaf)) {
          $tree[$name] = $leaf;
        }
      }
    }

    return $tree;
  }

  /**
   * Finds all the configs in "config/src" folder and returns their names.
   *
   * @return array
   *   Config names (file name without extension, relative to the "config/src").
   */
  function discoverPackages() {
    $root = $this->configsPath;

    $paths = array(
      $root,
    );
    $configs = array();
    for ($i = 0; $i < count($paths); $i++) {
      $files = glob($paths[$i] . '/*.json');
      foreach ($files as $file) {
        if (is_file($file)) {
          $configs[] = $file;
        }
      }

      $subdirs = glob($paths[$i] . '/*', GLOB_ONLYDIR);
      if ($subdirs) {
        $paths = array_merge($paths, $subdirs);
      }
    }

    $patterns = array(
      '@^' . preg_quote($root . '/', '@') . '@',
      '@\.json$@',
    );
    $packages = preg_replace($patterns, '', $configs);
    return $packages;
  }

}
