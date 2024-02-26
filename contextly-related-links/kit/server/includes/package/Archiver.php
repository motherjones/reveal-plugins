<?php

class ContextlyKitPackageArchiver extends ContextlyKitBase {

  /**
   * @var array
   */
  protected $config;

  /**
   * @var ContextlyKitPackageManager
   */
  protected $manager;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * @var array
   */
  protected $exclude = array();

  /**
   * @var string
   */
  protected $rootPath;

  /**
   * @var string
   */
  protected $tempPath;

  /**
   * @var string
   */
  protected $targetPath;

  /**
   * @var \RecursiveIteratorIterator
   */
  protected $iterator;

  /**
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * @param ContextlyKit $kit
   * @param ContextlyKitPackageManager $manager
   */
  public function __construct($kit, $manager, $config) {
    parent::__construct($kit);

    $this->manager = $manager;
    $this->config = $config;
    $this->fs = $manager->getFs();

    $this->rootPath = $this->kit->getRootPath();
    $this->tempPath = $this->manager->getTempPath() . '/archives';
    $this->targetPath = $this->kit->getFolderPath('releases', TRUE);
  }

  protected function fillExclusions($rootPath) {
    $this->exclude = array();
    foreach ($this->config['exclude'] as $localPath) {
      $absolutePath = $rootPath . '/' . trim($localPath, '/\/');

      if (strpos($localPath, '*') !== FALSE) {
        foreach (glob($absolutePath, GLOB_NOSORT) as $path) {
          $this->exclude[$path] = TRUE;
        }
      }
      else {
        $this->exclude[$absolutePath] = TRUE;
      }
    }
  }

  protected function init() {
    if ($this->initialized) {
      return;
    }
    $this->initialized = TRUE;

    $this->fs->mkdir($this->tempPath);
    $this->fs->mkdir($this->targetPath);
    $this->fillExclusions($this->rootPath);

    // Build iterator with callback to filter-out unwanted files and folders
    // from the archive.
    $flags = \RecursiveDirectoryIterator::FOLLOW_SYMLINKS | \RecursiveDirectoryIterator::SKIP_DOTS;
    $dir = new \RecursiveDirectoryIterator($this->rootPath, $flags);
    $filtered = new \RecursiveCallbackFilterIterator($dir, array($this, 'filterArchiveContent'));
    $this->iterator = new \RecursiveIteratorIterator($filtered);
  }

  /**
   * @param SplFileInfo $current
   * @param string $key
   * @param \RecursiveDirectoryIterator $iterator
   */
  public function filterArchiveContent($current, $key, $iterator) {
    $filePath = $current->getPathname();
    return !isset($this->exclude[$filePath]);
  }

  protected function getTypeInfo($type = NULL) {
    static $types = array(
      'tar.gz' => array(
        'extension' => 'tar',
        'suffix' => 'gz',
        'format' => \Phar::TAR,
        'compression' => \Phar::GZ,
      ),
      'zip' => array(
        'extension' => 'zip',
        'format' => \Phar::ZIP,
      ),
    );
    if (!isset($type)) {
      return $types;
    }
    elseif (isset($types[$type])) {
      return $types[$type];
    }
    else {
      return FALSE;
    }
  }

  public function buildKitArchive($type) {
    $this->init();

    $info = $this->getTypeInfo($type);
    $version = $this->kit->version();
    $tempFilepath = $this->tempPath . '/archive.' . $info['extension'];

    // Create kit archive on the temporary path first.
    $archive = new \PharData($tempFilepath, NULL, NULL, $info['format']);
    $archive->buildFromIterator($this->iterator, $this->rootPath);
    $archive->addFromString('version', $version);

    if (!empty($info['compression']) && !empty($info['suffix'])) {
      $archive->compress($info['compression'], $info['extension'] . '.' . $info['suffix']);

      // Remove uncompressed file and add suffix to the temp file path.
      unlink($tempFilepath);
      $tempFilepath .= '.' . $info['suffix'];
    }

    $targetFilepath = $this->getArchiveFilepath($type);
    $this->fs->rename($tempFilepath, $targetFilepath, TRUE);
  }

  public function getArchiveFilename($type) {
    $info = $this->getTypeInfo($type);
    $version = $this->kit->getCdnVersion();
    $filename = $this->config['prefix'] . $version . '.' . $info['extension'];
    if (isset($info['suffix'])) {
      $filename .= '.' . $info['suffix'];
    }
    return $filename;
  }

  public function getArchiveFilepath($type) {
    return $this->targetPath . '/' . $this->getArchiveFilename($type);
  }

  public function typeSupported($type) {
    $info = $this->getTypeInfo($type);
    return !empty($info);
  }

}
