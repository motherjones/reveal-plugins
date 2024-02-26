<?php


class ContextlyKitPackageManager extends ContextlyKitBase {

  /**
   * @var ContextlyKitPackageSettings
   */
  protected $settings;

  /**
   * @var ContextlyKitAssetsManager
   */
  protected $assetsManager;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * @var ContextlyKitPackageAssetsAggregator
   */
  protected $assetsAggregator;

  /**
   * @var string
   */
  protected $tempPath;

  /**
   * @var bool
   */
  protected $override = FALSE;

  public function __construct($kit, $options) {
    parent::__construct($kit);

    // Set up options.
    $keys = array('override');
    foreach ($keys as $key) {
      if (isset($options[$key])) {
        $this->{$key} = $options[$key];
      }
    }

    $iniPath = $this->kit->getFolderPath('console', TRUE) . '/settings.ini';
    $this->settings = $this->kit->newPackageSettings($iniPath);
    $this->assetsManager = $this->kit->newAssetsManager();
    $this->fs = new \Symfony\Component\Filesystem\Filesystem();

    // Set up logging.
    $this->log = new \Monolog\Logger('log');
    $this->log->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));

    $this->assetsAggregator = $this->kit->newPackageAssetsAggregator($this);
  }

  public function checkRequirements() {
    // TODO Check that all required binaries are installed.
    // TODO Make sure we're NOT in safe mode (to call exec())
    // TODO Make sure we're at least on PHP 5.3 for namespaces.
    // TODO Make sure we're at least on PHP 5.4 for \RecursiveCallbackFilterIterator.
  }

  protected function discoverAggregatedPackageNames() {
    $result = array();

    $configNames = $this->assetsManager->discoverPackages();
    foreach ($configNames as $name) {
      $config = $this->assetsManager->getConfig($name);
      if (!empty($config->aggregate)) {
        $result[] = $name;
      }
    }

    return $result;
  }

  /**
   * @param string[] $names
   *
   * @return ContextlyKitAssetsPackage[]
   */
  protected function getPackagesSortedByDependenciesDepth($names) {
    $packages = array();

    foreach ($names as $name) {
      $package = $this->assetsManager->getPackage($name);

      $exposedNames = $package->getExposed();
      if (!empty($exposedNames)) {
        $packages += $this->getPackagesSortedByDependenciesDepth($exposedNames);
      }

      $includedNames = $package->getIncluded();
      if (!empty($includedNames)) {
        $packages += $this->getPackagesSortedByDependenciesDepth($includedNames);
      }

      $packages[$name] = $package;
    }

    return $packages;
  }

  public function getLicenseInfo() {
    if (empty($this->settings['aggregator']['license'])) {
      return '';
    }

    $filename = basename($this->settings['aggregator']['license']);
    $url = $this->kit->buildCdnUrl($filename, array(
      'cdn-version' => $this->kit->buildCdnVersion(ContextlyKit::CDN_SAME, $this->kit->version()),
    ));
    return 'For licensing information see ' . $url;
  }

  public function aggregateAssets() {
    $this->log->addInfo('Aggregating assets.');

    // Discover packages marked as aggregated and sort them by include & expose
    // depth, deepest first, so we could build-in aggregated package structures.
    $packageNames = $this->discoverAggregatedPackageNames();
    $packages = $this->getPackagesSortedByDependenciesDepth($packageNames);
    $this->assetsAggregator->aggregate($packages);

    $this->log->addInfo('Assets aggregation is complete.');
  }

  protected function getUploaderConfig($subkey) {
    $config = array();

    $keys = array("uploader.$subkey", 'uploader');
    foreach ($keys as $key) {
      if (!empty($this->settings[$key])) {
        $config += $this->settings[$key];
      }
    }

    return $config;
  }

  public function buildArchives() {
    $this->log->addInfo("Building kit archives of " . $this->kit->getCdnVersion() . " version.");

    $archiver = $this->kit->newPackageArchiver($this, $this->getArchiverConfig());
    foreach ($this->settings['archiver']['formats'] as $type) {
      if (!$archiver->typeSupported($type)) {
        $this->log->addError("Archive type $type not supported.");
        continue;
      }

      if (!$this->isOverrideEnabled()) {
        $filepath = $archiver->getArchiveFilepath($type);
        if (file_exists($filepath)) {
          $this->log->addError("Archive of $type type already exists and will NOT be overwritten.");
          continue;
        }
      }

      $archiver->buildKitArchive($type);
      $this->log->addInfo("Archive of $type type created successfully.");
    }
  }

  protected function getArchiverConfig() {
    $config = array();

    if (!empty($this->settings['archiver'])) {
      $config += $this->settings['archiver'];
      unset($config['formats']);
    }

    $config += array(
      'exclude' => array(),
    );

    return $config;
  }

  public function uploadAssets() {
    $version = $this->kit->getCdnVersion();
    $override = $this->isOverrideEnabled();
    $path = $this->kit->getFolderPath('client/aggregated', TRUE);

    $this->log->addInfo("Uploading aggregated assets of '$version' version.");

    $config = $this->getUploaderConfig('assets');
    $uploader = $this->kit->newPackageUploader($this, $config);
    if ($uploader->uploadDirectory($path, $version, $override)) {
      $this->log->addInfo('Aggregated assets uploaded successfully.');
    }
    else {
      $this->log->addError('Aggregated assets were NOT uploaded.');
    }
  }

  public function uploadArchives() {
    $override = $this->isOverrideEnabled();
    $archiverConfig = $this->getArchiverConfig();
    $archiver = $this->kit->newPackageArchiver($this, $archiverConfig);
    $uploaderConfig = $this->getUploaderConfig('archives');
    $uploader = $this->kit->newPackageUploader($this, $uploaderConfig);

    $this->log->addInfo('Uploading kit archives of ' . $this->kit->getCdnVersion() . ' version.');
    foreach ($this->settings['archiver']['formats'] as $type) {
      $filePath = $archiver->getArchiveFilepath($type);
      if (!file_exists($filePath)) {
        $this->log->addError("Archive of $type type not found and will NOT be uploaded.");
        continue;
      }

      $fileName = $archiver->getArchiveFilename($type);
      if ($uploader->uploadFile($filePath, $fileName, $override)) {
        $this->log->addInfo("Archive $fileName was uploaded successfully.");
      }
      else {
        $this->log->addError("Archive $fileName was NOT uploaded.");
      }
    }
  }

  protected function compactInt($int) {
    return base_convert((int) $int, 10, 36);
  }

  public function getTempPath() {
    if (!isset($this->tempPath)) {
      $temp = $this->settings['paths']['temp'];
      if (empty($temp)) {
        throw $this->kit->newException('Temporary path is not set.');
      }

      if (!is_writable($temp)) {
        throw $this->kit->newException("Temporary path $temp is not writable.");
      }

      $temp = $temp . '/contextly-' . $this->compactInt($_SERVER['REQUEST_TIME']) . $this->compactInt(mt_rand());
      $this->fs->mkdir($temp);

      $this->tempPath = $temp;
    }

    return $this->tempPath;
  }

  public function getFs() {
    return $this->fs;
  }

  public function getLog() {
    return $this->log;
  }

  /**
   * @return bool
   */
  public function isOverrideEnabled() {
    return !empty($this->override);
  }

  /**
   * Builds path of the file relative to specified directory.
   *
   * @param string $filePath
   * @param string $startDir
   */
  public function buildRelativeFilePath($filePath, $startDir) {
    $fileDir = dirname($filePath);
    $fileName = basename($filePath);

    $relativePath = $this->fs->makePathRelative($fileDir, $startDir);
    if ($relativePath === './') {
      $relativePath = '';
    }

    return $relativePath . $fileName;
  }

  function __destruct() {
    // Cleanup temporary folder.
    if (isset($this->tempPath)) {
      $this->fs->remove($this->tempPath);
    }
  }

}
