<?php

class ContextlyKitAssetsAsyncRenderer extends ContextlyKitAssetsRenderer {

  public function renderCss() {
    // Asynchronous assets renderer is not able to render CSS.
    return '';
  }

  public function renderJs($params = array()) {
    $urls = array();
    foreach ($this->packages as $package) {
      $urls += array_flip($package->buildJsUrls());
    }
    if (empty($urls)) {
      return '';
    }

    $params += array(
      'ready' => array(),
      'code' => '',
    );
    return $this->kit->newServerTemplate('async-scripts')
      ->render(array(
        'isDevMode' => $this->kit->isDevMode(),
        'ready' => $params['ready'],
        'urls' => array_keys($urls),
        'code' => $this->buildJsCode(TRUE) . $params['code'],
      ));
  }

  public function renderTpl() {
    // Asynchronous assets renderer is not able to render templates.
    return '';
  }

  public function renderAll($params = array()) {
    return implode("\n", array(
      $this->renderCss($params),
      $this->renderTpl($params),
      $this->renderJs($params),
    ));
  }

}
