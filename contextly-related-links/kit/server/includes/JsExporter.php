<?php

class ContextlyKitJsExporter extends ContextlyKitBase {

  protected $data;

  public function __construct($kit, $data) {
    parent::__construct($kit);

    $this->data = $data;
  }

  public function export($namespace, $escapeHtml = TRUE) {
    if (empty($this->data)) {
      return '';
    }

    $vars = array();
    foreach ($this->data as $name => $value) {
      $vars[$this->kit->exportJsVar($name, $escapeHtml)] = $this->kit->exportJsVar($value, $escapeHtml);
    }

    return $this->kit->newServerTemplate('js-exported')
      ->render(array(
        'namespace' => $this->kit->exportJsVar($namespace, $escapeHtml),
        'vars' => $vars,
      ));
  }

}