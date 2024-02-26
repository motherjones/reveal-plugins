<?php

abstract class ContextlyKitPackageUploader extends ContextlyKitBase {

  /**
   * @var array
   */
  protected $config;

  /**
   * @var ContextlyKitPackageManager
   */
  protected $manager;

  /**
   * @param ContextlyKit $kit
   * @param ContextlyKitPackageManager $manager
   * @param array $config
   */
  public function __construct($kit, $manager, $config) {
    parent::__construct($kit);

    $this->manager = $manager;
    $this->config = $config;
    $this->validateConfig();
  }

  /**
   * @param string $sourcePath
   *   Path to the local directory.
   * @param string|null $remoteSubfolder
   *   No leading or trailing slashes. Pass empty string to use root on remote
   *   side.
   * @param bool $override
   *   Pass TRUE to override existing data.
   *
   * @return bool
   */
  abstract public function uploadDirectory($sourcePath, $remoteSubfolder = '', $override = FALSE);

  /**
   * @param $sourcePath
   *   Path to the local file.
   * @param $remotePath
   *   Complete remote path, including filename.
   * @param bool $override
   *   Pass TRUE to override existing data.
   *
   * @return bool
   */
  abstract public function uploadFile($sourcePath, $remotePath, $override = FALSE);

  abstract protected function validateConfig();

}
