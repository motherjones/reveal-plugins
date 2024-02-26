<?php

class ContextlyKitAssetsPackage extends ContextlyKitAssetsPackageBase {

  protected function extractAssets($base, $key, $config) {
    if (!isset($config->{$key})) {
      return;
    }

    $parsed = array();
    foreach ($config->{$key} as $filepath) {
      if (!is_string($filepath)) {
        $subkey = $this->settings->mode;
        if (!isset($filepath->{$subkey})) {
          continue;
        }
        $filepath = $filepath->{$subkey};
      }

      $parsed[$base . $filepath] = TRUE;
    }

    if (!empty($parsed)) {
      $this->{$key} += $parsed;
    }
  }

  protected function extractResources($base, $key, $config) {
    if (!isset($config->{$key})) {
      return;
    }

    $parsed = array();
    foreach ($config->{$key} as $name => $filepath) {
      $parsed[$name] = $base . $filepath;
    }

    if (!empty($parsed)) {
      $this->{$key} += $parsed;
    }
  }

  public function addConfig($config) {
    $base = '';

    if (!empty($config->base)) {
      $base .= rtrim($config->base, '\/') . '/';
    }

    foreach (array('js', 'css', 'media') as $key) {
      $this->extractAssets($base, $key, $config);
    }

    foreach (array('tpl', 'data') as $key) {
      $this->extractResources($base, $key, $config);
    }

    if (!empty($config->versions)) {
      $this->versions = TRUE;
    }

    return $this;
  }

  function buildJsUrls() {
    $js = $this->getJs();
    if (empty($js)) {
      return array();
    }

    $urls = array();
    foreach ($js as $path) {
      $urls[$path] = $this->kit->buildAssetUrl($path . '.js');
    }

    return $urls;
  }

  function buildCssUrls() {
    $css = $this->getCss();
    if (empty($css)) {
      return array();
    }

    $urls = array();
    foreach ($css as $path) {
      $urls[$path] = $this->kit->buildAssetUrl($path . '.css');
    }

    return $urls;
  }

  protected function buildResourcesPaths($key, $extension) {
    if (empty($this->{$key})) {
      return array();
    }

    $paths = array();
    $basePath = $this->kit->getFolderPath('client', TRUE);
    foreach ($this->{$key} as $name => $filePath) {
      $paths[$name] = $basePath . '/' . $filePath . '.' . $extension;
    }
    return $paths;
  }

  function buildTplPaths() {
    return $this->buildResourcesPaths('tpl', 'handlebars');
  }

  function buildDataPaths() {
    return $this->buildResourcesPaths('data', 'json');
  }

}
