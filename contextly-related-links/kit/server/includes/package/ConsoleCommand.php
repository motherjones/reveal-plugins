<?php

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

class ContextlyKitPackageConsoleCommand extends Command {

  protected function configure() {
    $this
      ->setName('run')
      ->setDescription('Perform packaging operations on the Kit.')
      ->addArgument(
        'operations',
        InputArgument::IS_ARRAY,
        'Space-separated list of operations: "aggregate-assets", "upload-assets", "build-archives", "upload-archives". Default is to aggregate assets and build Kit archives only. Input order is not important.'
      )
      ->addOption(
        'override',
        'o',
        InputOption::VALUE_NONE,
        'Specify to override existing remote assets and local/remote archives. By default only local aggregated assets are overwritten.'
      )
      ->addOption(
        'cdn',
        'c',
        InputOption::VALUE_REQUIRED,
        'Specify "' . ContextlyKit::CDN_SAME . '" (default), "' . ContextlyKit::CDN_BRANCH . '" or "' . ContextlyKit::CDN_LATEST . '" to upload branch latest or global latest assets and archives. Has no effect on dev builds.',
        ContextlyKit::CDN_SAME
      )
      ->addOption(
        'build',
        'b',
        InputOption::VALUE_REQUIRED,
        'Version of the Kit to build. Must be either "dev" or the version number with mandatory major & minor numbers and optional suffix separated with dot, e.g. 2.3 or 2.3.1',
        'dev'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $map = array(
      "aggregate-assets" => 'aggregateAssets',
      "upload-assets" => 'uploadAssets',
      "build-archives" => 'buildArchives',
      "upload-archives" => 'uploadArchives',
    );
    $defaults = array("aggregate-assets", "build-archives");

    $operations = $input->getArgument('operations');
    if (empty($operations)) {
      $operations = $defaults;
    }
    else {
      $operations = array_intersect(array_keys($map), $operations);
    }

    // Build Kit settings with special mode and settings set from input options.
    $settings = new ContextlyKitSettings();
    $settings->mode = ContextlyKit::MODE_PKG;
    $settings->version = $input->getOption('build');
    $settings->cdn = $input->getOption('cdn');

    // Validate version passed.
    if ($settings->version !== 'dev' && !ContextlyKit::parseVersion($settings->version)) {
      throw new ContextlyKitException('Invalid build number specified, must be either "dev" or numerical "M.N" with optional ".suffix"');
    }

    // Validate CDN parameter.
    $allowed = array(
      ContextlyKit::CDN_SAME => TRUE,
      ContextlyKit::CDN_BRANCH => TRUE,
      ContextlyKit::CDN_LATEST => TRUE,
    );
    if (!isset($allowed[$settings->cdn])) {
      throw new ContextlyKitException('Invalid CDN parameter specified. See command help for a valid options.');
    }

    $kit = new ContextlyKit($settings);
    $options = array(
      'override' => (bool) $input->getOption('override'),
    );
    $manager = $kit->newPackageManager($options);
    foreach ($operations as $operation) {
      $method = $map[$operation];
      $manager->{$method}();
    }
  }

}
