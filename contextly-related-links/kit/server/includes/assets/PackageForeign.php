<?php

/**
 * Package containing external assets.
 *
 * External assets are provided directly by URL, for use by CMS to integrate
 * their scripts and styles into the loader infrastructure.
 */
class ContextlyKitAssetsPackageForeign extends ContextlyKitAssetsPackageBase {

  /**
   * Adds foreign assets to the package.
   *
   * @param $assets
   *   First level keys are either "js" or "css", second level keys are asset
   *   URLs, values are ignored.
   */
  public function addAssets($assets) {
    foreach (array('js', 'css') as $key) {
      if (!isset($assets[$key])) {
        continue;
      }

      $this->{$key} += $assets[$key];
    }

    return $this;
  }

  function toExposed() {
    $result = parent::toExposed();

    if (!empty($result)) {
      $result['foreign'] = TRUE;
    }

    return $result;
  }


}
