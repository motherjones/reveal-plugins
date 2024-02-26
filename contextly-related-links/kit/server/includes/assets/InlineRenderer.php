<?php

class ContextlyKitAssetsInlineRenderer extends ContextlyKitAssetsRenderer {

  public function renderCss() {
    // TODO: Implement renderCss() method.
  }

  public function renderJs($params = array()) {
    $content = $this->buildJsCode(TRUE);

    $basePath = $this->kit->getFolderPath('client', TRUE);
    foreach ($this->packages as $package) {
      foreach ($package->getJs() as $filePath) {
        $path = $basePath . '/' . $filePath . '.js';
        if (!file_exists($path)) {
          throw $this->kit->newException('Unable to read JS file at ' . $path);
        }

        $content .= file_get_contents($path) . "\n";
      }
    }

    $params += array(
      'code' => '',
    );
    $content .= $params['code'] . "\n";

    return $this->kit->newServerTemplate('inline-scripts')
      ->render(array(
        'code' => $content,
      ));
  }

  public function renderTpl() {
    // TODO: Implement renderTpl() method.
  }

  public function renderAll($params = array()) {
    return implode("\n", array(
      $this->renderCss($params),
      $this->renderTpl($params),
      $this->renderJs($params),
    ));
  }

}
