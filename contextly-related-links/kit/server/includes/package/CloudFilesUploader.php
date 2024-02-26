<?php

class ContextlyKitPackageCloudFilesUploader extends ContextlyKitPackageUploader {

  /**
   * @var \OpenCloud\OpenStack
   */
  protected $client;

  /**
   * @var \OpenCloud\ObjectStore\Service
   */
  protected $service;

  /**
   * @var \OpenCloud\ObjectStore\Resource\Container
   */
  protected $container;

  public function __construct($kit, $manager, $config) {
    parent::__construct($kit, $manager, $config);

    $endpoint = $this->getEndpoints($this->config['endpoint']);
    $secret = array_intersect_key($this->config, array_flip(array('username', 'apiKey')));
    $this->client = new \OpenCloud\Rackspace($endpoint, $secret);
    $this->service = $this->client->objectStoreService('cloudFiles', $this->config['region']);
    $this->container = $this->service->getContainer($this->config['container']);
  }

  protected function getEndpoints($key = NULL) {
    static $endpoints = array(
      'US' => \OpenCloud\Rackspace::US_IDENTITY_ENDPOINT,
      'UK' => \OpenCloud\Rackspace::UK_IDENTITY_ENDPOINT,
    );
    if (isset($key)) {
      return $endpoints[$key];
    }
    else {
      return $endpoints;
    }
  }

  protected function validateConfig() {
    // Filter-out empty values.
    $this->config = array_filter($this->config);

    // Add defaults.
    $this->config += array(
      'endpoint' => 'US',
      'region' => '',
      'folder' => '',
    );

    // Check required.
    $required = array_flip(array(
      'username',
      'apiKey',
      'container',
    ));
    $missing = array_diff_key($required, $this->config);
    if (!empty($missing)) {
      $missing = implode(', ', array_keys($missing));
      throw $this->kit->newException('Required config parameters missing: ' . $missing);
    }

    // Validate endpoint.
    $endpoints = $this->getEndpoints();
    $endpoint_key = $this->config['endpoint'];
    if (!isset($endpoints[$endpoint_key])) {
      throw $this->kit->newException('Unknown endpoint ' . $endpoint_key . ' set on config.');
    }
  }

  protected function alterRemotePath($remotePath) {
    if (isset($this->config['folder']) && $this->config['folder'] !== '') {
      if ($remotePath !== '') {
        $remotePath = '/' . $remotePath;
      }
      $remotePath = $this->config['folder'] . $remotePath;
    }

    return $remotePath;
  }

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
  public function uploadFile($sourcePath, $remotePath, $override = FALSE) {
    $remotePath = $this->alterRemotePath($remotePath);

    if (!$override) {
      try {
        $this->container->getPartialObject($remotePath);
        $this->manager->getLog()
          ->addError("File '$sourcePath' already exists at remote path '$remotePath' and will NOT be uploaded.");
        return FALSE;
      }
      catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
        // This kind of exception means 4xx HTTP status returned, make sure it's
        // 404, which means it's safe to continue upload.
        if (!$e->getResponse()->getStatusCode() == 404) {
          throw $e;
        }
      }
    }

    $handle = fopen($sourcePath, 'rb');
    if (!$handle) {
      throw $this->kit->newException('Unable to open file for reading: ' . $sourcePath);
    }

    $object = $this->container->uploadObject($remotePath, $handle);
    fclose($handle);

    // For fonts set Access-Control-Allow-Origin header.
    static $cors_extensions = array(
      'otf' => TRUE,
      'eot' => TRUE,
      'svg' => TRUE,
      'ttf' => TRUE,
      'woff' => TRUE,
    );
    if (preg_match('@\.([a-z]+)$@iu', $remotePath, $matches)) {
      $extension = strtolower($matches[1]);
      if (isset($cors_extensions[$extension])) {
        $metadata = $object->appendToMetadata(array(
          'Access-Control-Allow-Origin' => '*',
        ));
        $object->saveMetadata($metadata);
      }
    }

    return TRUE;
  }

  /**
   * @param string $sourcePath
   *   Path to the local file or folder.
   * @param string|null $remoteSubfolder
   *   No leading or trailing slashes. Pass NULL to use root on remote side.
   * @param bool $override
   *   Pass TRUE to override existing data.
   *
   * @return bool
   *
   * @todo Use uploadObjects() method to upload all files at once.
   */
  public function uploadDirectory($sourcePath, $remoteSubfolder = '', $override = FALSE) {
    if (!$override) {
      $searchParams = array(
        'limit' => 1,
      );
      $searchPath = $this->alterRemotePath($remoteSubfolder);
      if ($searchPath !== '') {
        $searchParams['path'] = $searchPath;
      }
      $list = $this->container->objectList($searchParams);
      if ($list->count()) {
        $this->manager->getLog()
          ->addError("Remote path '$searchPath' already has files inside, local folder '$sourcePath' will NOT be uploaded.");
        return FALSE;
      }
    }

    if ($remoteSubfolder !== '') {
      $remoteSubfolder .= '/';
    }

    $flags = \RecursiveDirectoryIterator::FOLLOW_SYMLINKS | \RecursiveDirectoryIterator::SKIP_DOTS;
    $dirIterator = new \RecursiveDirectoryIterator($sourcePath, $flags);
    $mode = \RecursiveIteratorIterator::LEAVES_ONLY;
    $iterator = new \RecursiveIteratorIterator($dirIterator, $mode);
    foreach ($iterator as $fileInfo) {
      /**
       * @var SplFileInfo $fileInfo
       */
      $localPath = $fileInfo->getPathname();
      $relativePath = $this->manager->buildRelativeFilePath($localPath, $sourcePath);
      $remotePath = $remoteSubfolder . $relativePath;

      // We handle "override" option on the folder-level to avoid extra requests
      // for each uploaded file. If we reached this point, it's safe to override.
      $this->uploadFile($localPath, $remotePath, TRUE);
    }

    return TRUE;
  }

}
