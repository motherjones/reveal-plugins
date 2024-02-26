<?php

class ContextlyKitOverlayDialog extends ContextlyKitBase {

  /**
   * @var string
   */
  protected $type;

  /**
   * @param ContextlyKit $kit
   * @param string $type
   */
  function __construct($kit, $type) {
    parent::__construct($kit);

    $this->type = $type;
  }

  function render($params = array()) {
    $params += array(
      'loader' => 'loader',
    );

    // Get the assets list.
    $manager = $this->kit->newAssetsManager();
    $packages = $manager->getPackageWithDependencies($params['loader']);
    $exposedTree = $manager->buildExposedTree(array_keys($packages));
    $head = $this->kit->newAssetsAsyncRenderer($packages, $exposedTree)
      ->renderAll($params);

    // Render the dialog.
    return $this->kit->newServerTemplate('dialog')
      ->render(array(
        'language' => 'en',
        'head' => $head,
        'type' => $this->type,
      ));
  }

}
