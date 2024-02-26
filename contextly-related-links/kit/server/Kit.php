<?php

/**
 * Generic kit class.
 *
 * Contains factory for the most kit classes, auto-loader and re-usable
 * functions.
 *
 * @method ContextlyKitApi newApi()
 * @method ContextlyKitApiRequest newApiRequest()
 * @method ContextlyKitApiResponse newApiResponse()
 * @method ContextlyKitApiToken newApiToken()
 * @method ContextlyKitApiTokenEmpty newApiTokenEmpty()
 * @method ContextlyKitApiSessionInterface newApiSession()
 * @method ContextlyKitApiSessionIsolated newApiSessionIsolated()
 * @method ContextlyKitApiTransportInterface newApiTransport()
 * @method ContextlyKitApiException newApiException()
 * @method ContextlyKitAssetsAsyncRenderer newAssetsAsyncRenderer()
 * @method ContextlyKitAssetsInlineRenderer newAssetsInlineRenderer()
 * @method ContextlyKitAssetsManager newAssetsManager()
 * @method ContextlyKitAssetsConfig newAssetsConfig()
 * @method ContextlyKitAssetsConfigAggregated newAssetsConfigAggregated()
 * @method ContextlyKitAssetsPackage newAssetsPackage()
 * @method ContextlyKitAssetsPackageForeign newAssetsPackageForeign()
 * @method ContextlyKitDataManager newDataManager()
 * @method ContextlyKitException newException()
 * @method ContextlyKitExposedAssetsManager newExposedAssetsManager()
 * @method ContextlyKitExecCommand newExecCommand()
 * @method ContextlyKitExecResult newExecResult()
 * @method ContextlyKitJsExporter newJsExporter()
 * @method ContextlyKitPackageArchiver newPackageArchiver()
 * @method ContextlyKitPackageAssetsAggregator newPackageAssetsAggregator()
 * @method ContextlyKitPackageManager newPackageManager()
 * @method ContextlyKitPackageSettings newPackageSettings()
 * @method ContextlyKitPackageUploader newPackageUploader()
 * @method ContextlyKitOverlayDialog newOverlayDialog()
 * @method ContextlyKitOverridesManager newOverridesManager()
 * @method ContextlyKitServerTemplate newServerTemplate()
 * @method ContextlyKitWidgetsEditor newWidgetsEditor()
 * @method ContextlyKitWidgetsEditorException newWidgetsEditorException()
 */
class ContextlyKit {

	const CDN_SAME   = 'same';
	const CDN_BRANCH = 'branch';
	const CDN_LATEST = 'latest';

	const MODE_LIVE = 'live';
	const MODE_DEV  = 'dev';
	const MODE_PKG  = 'pkg';

	/**
	 * Path of the Kit root.
	 *
	 * @var null|string
	 */
	protected static $rootPath;

	public static function autoload( $class ) {
		$pattern = '/^ContextlyKit/';
		if ( preg_match( $pattern, $class ) ) {
			$words     = preg_replace( $pattern, '', $class );
			$words     = preg_split( '/(?=[A-Z])/', $words, -1, PREG_SPLIT_NO_EMPTY );
			$directory = dirname( __FILE__ ) . '/includes';

			// Go into directories first...
			while ( ! empty( $words ) ) {
				$candidate = $directory . '/' . strtolower( $words[0] );
				if ( is_dir( $candidate ) ) {
					$directory = $candidate;

					// Remove first word for the next iteration.
					array_shift( $words );
				} else {
					break;
				}
			}

			// ... next find the file name.
			while ( ! empty( $words ) ) {
				$filename = $directory . '/' . implode( '', $words ) . '.php';
				if ( file_exists( $filename ) ) {
					require_once $filename;
					break;
				}

				// Remove last word for the next iteration.
				array_pop( $words );
			}
		}
	}

	public static function registerAutoload() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 *
	 * @var ContextlyKitSettings
	 */
	protected $settings;

	/**
	 *
	 * @var array
	 */
	protected $urls;

	/**
	 *
	 * @var string
	 */
	protected $cdnVersion;

	/**
	 *
	 * @var string
	 */
	protected $version;

	function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function version() {
		if ( ! isset( $this->version ) ) {
			if ( $this->isPackagerMode() ) {
				$this->version = $this->settings->version;
			} else {
				$path = $this->getRootPath() . '/version';
				if ( ! file_exists( $path ) ) {
					$this->version = 'dev';
				} else {
					$this->version = trim( file_get_contents( $path ) );
				}
			}
		}

		return $this->version;
	}

	/**
	 * Parses version into an array with major, minor versions and suffix.
	 *
	 * @param string $version
	 *
	 * @return array|null
	 *   Array with zero-based numeric indexes or NULL in case the version doesn't
	 *   match the format.
	 */
	public static function parseVersion( $version ) {
		if ( preg_match( '/^(\d+)\.(\d+)\.?(.*)$/', $version, $matches ) ) {
			return array( $matches[1], $matches[2], $matches[3] );
		} else {
			return null;
		}
	}

	/**
	 * Returns asset URL.
	 *
	 * @param string $filepath
	 *   Path relative to the "client/src" folder on dev mode and to the
	 *   "client/aggregated" folder on live mode.
	 * @return string
	 *   Asset URL.
	 */
	function buildAssetUrl( $filepath ) {
		if ( $this->isCdnEnabled() ) {
			return $this->buildCdnUrl( $filepath );
		} else {
			return $this->buildFileUrl( $this->getFolderPath( 'client' ) . '/' . $filepath );
		}
	}

	/**
	 * Returns asset URL on CDN.
	 *
	 * @param string $filepath
	 *   Path relative to the "client/aggregated" folder.
	 *
	 * @return string
	 *   Asset URL.
	 */
	function buildCdnUrl( $filepath, $params = array() ) {
		if ( ! isset( $params['cdn-version'] ) ) {
			$params['cdn-version'] = $this->getCdnVersion();
		}

		return $this->getServerUrl( 'cdn', $params ) . $params['cdn-version'] . '/' . $filepath;
	}

	public function buildCdnVersion( $cdn, $version ) {
		switch ( $cdn ) {
			case self::CDN_LATEST:
				return 'latest';

			case self::CDN_BRANCH:
				if ( $parsed = $this->parseVersion( $version ) ) {
					return $parsed[0] . '.latest';
				}
				// no break;
			default:
				return $version;
		}
	}

	public function getCdnVersion() {
		if ( ! isset( $this->cdnVersion ) ) {
			$this->cdnVersion = $this->buildCdnVersion( $this->settings->cdn, $this->version() );
		}

		return $this->cdnVersion;
	}

	/**
	 * Returns URL of the passed file.
	 *
	 * Integration should override it for more control over the URL building.
	 *
	 * @param string $filepath
	 *   File path relative to the Kit root.
	 *
	 * @return string
	 *   Absolute URL to the passed file.
	 *
	 * @throws ContextlyKitException
	 */
	function buildFileUrl( $filepath ) {
		if ( isset( $this->settings->urlPrefix ) ) {
			return $this->settings->urlPrefix . '/' . $filepath;
		} else {
			throw $this->newException( 'Unable to convert file path to URL because of wrong kit settings.' );
		}
	}

	function escapeHTML( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}

	function exportJsVar( $value, $escapeHtml = true ) {
		if ( $escapeHtml ) {
			if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
				throw $this->newException( 'PHP version 5.3.0+ is required to properly encode JS value.' );
			}

			$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		} else {
			$flags = 0;
		}

		return json_encode( $value, $flags );
	}

	function getSettings() {
		return $this->settings;
	}

	function isDevMode() {
		// TODO Optimize all 3 functions to avoid comparing strings each time over and over again.
		return $this->settings->mode === 'dev';
	}

	function isLiveMode() {
		return $this->settings->mode === 'live';
	}

	function isPackagerMode() {
		return $this->settings->mode === 'pkg';
	}

	function isHttps() {
		// Make sure we return HTTPS URLs in packaging mode.
		if ( $this->isPackagerMode() ) {
			return true;
		}

		if ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) {
			return true;
		}

		return false;
	}

	function isCdnEnabled() {
		return ! $this->isDevMode() && ! empty( $this->settings->cdn );
	}

	protected function getServerUrls() {
		if ( ! isset( $this->urls ) ) {
			// Use data files directly and force source folder to properly get it when
			// in live mode.
			$path       = $this->getFolderPath( 'client/src/data', true ) . '/urls.json';
			$data       = $this->newDataManager( array( 'urls' => $path ) )
			->parse();
			$this->urls = $data['urls'] ? $data['urls'] : array();
		}

		return $this->urls;
	}

	function getServerUrl( $serverType, $params = array() ) {
		$params += array(
			'protocol-required' => true,
		);

		$urls = $this->getServerUrls();
		if ( ! isset( $urls[ $serverType ] ) ) {
			throw $this->newException( "Unknown server type: {$serverType}" );
		}

		$url        = null;
		$serverUrls = $urls[ $serverType ];
		if ( is_string( $serverUrls ) ) {
			$url = $serverUrls;
		} else {
			$keys   = array();
			$keys[] = $this->settings->mode;
			$keys[] = $this->isHttps() ? 'https' : 'http';

			foreach ( $keys as $key ) {
				if ( isset( $serverUrls[ $key ] ) ) {
					if ( is_string( $serverUrls[ $key ] ) ) {
						// URL found, add protocol if necessary.
						$url = $serverUrls[ $key ];
						break;
					} else {
						$serverUrls = $serverUrls[ $key ];
					}
				}
			}

			if ( ! isset( $url ) ) {
				// No URL found for the case.
				$keys = implode( ', ', $keys );
				throw $this->newException( "Server URL {$serverType} not found for keys {$keys}" );
			}
		}

		if ( $params['protocol-required'] && substr( $url, 0, 2 ) === '//' ) {
			$url = ( $this->isHttps() ? 'https' : 'http' ) . ':' . $url;
		}

		return $url;
	}

	/**
	 * Returns absolute path to the root folder of the kit.
	 *
	 * @return string
	 */
	public static function getRootPath() {
		if ( ! isset( self::$rootPath ) ) {
			// Get parent directory of the current file.
			self::$rootPath = dirname( dirname( __FILE__ ) );
		}

		return self::$rootPath;
	}

	/**
	 * Returns path to the Kit sub-folder.
	 *
	 * @param string $folder
	 *   Top-level folder of the Kit. Optionally with first level sub-folder. If
	 *   sub-folder is not specified it will be automatically set depending on the
	 *   Kit mode for "client" and "config" top-level folders.
	 * @param bool   $absolute
	 *   Pass TRUE to get absolute path, otherwise path related to the kit root
	 *   is returned.
	 *
	 * @return string
	 *   For "client" and "config" folders it returns path to the sub-folder
	 *   depending on the current mode.
	 */
	function getFolderPath( $folder, $absolute = false ) {
		$path = '';

		if ( $absolute ) {
			$path .= self::getRootPath() . '/';
		}

		$path .= $folder;

		if ( in_array( $folder, array( 'client', 'config' ), true ) ) {
			if ( $this->isLiveMode() ) {
				$path .= '/aggregated';
			} else {
				$path .= '/src';
			}
		}

		return $path;
	}

	/**
	 * Returns default implementations of some interfaces.
	 *
	 * Should be overwritten on child classes to use alternatives.
	 *
	 * @return array
	 */
	protected function getClassesMap() {
		return array(
			'ApiSession'      => 'ContextlyKitApiSessionIsolated',
			'ApiTransport'    => 'ContextlyKitApiCurlTransport',
			'PackageUploader' => 'ContextlyKitPackageCloudFilesUploader',
		);
	}

	protected function getClassName( $baseName ) {
		$map = $this->getClassesMap();
		if ( isset( $map[ $baseName ] ) ) {
			return $map[ $baseName ];
		} else {
			return 'ContextlyKit' . $baseName;
		}
	}

	/**
	 * Magic method to create new instances of the kit classes.
	 *
	 * To override some classes the child class is able to just implement
	 * non-magic new*() methods and create instances of custom classes there.
	 */
	function __call( $name, $arguments ) {
		$replaced = 0;
		$baseName = preg_replace( '/^new/', '', $name, -1, $replaced );
		if ( ! $replaced ) {
			throw $this->newException( "Undefined method {$name}." );
		}

		$className  = $this->getClassName( $baseName );
		$reflection = new ReflectionClass( $className );
		if ( $reflection->isSubclassOf( 'ContextlyKitBase' ) ) {
			array_unshift( $arguments, $this );
		}

		if ( ! empty( $arguments ) ) {
			return $reflection->newInstanceArgs( $arguments );
		} else {
			return $reflection->newInstance();
		}
	}

}

class ContextlyKitSettings {

	/**
	 * Kit mode.
	 *
	 * Valid values are:
	 * - live: production mode
	 * - dev: development mode
	 * - pkg: resources packager mode
	 *
	 * @var string
	 */
	public $mode = 'live';

	/**
	 * Controls version of assets loaded from the CDN.
	 *
	 * Affects live mode only. Possible values controls which assets to use:
	 * - empty value: local assets
	 * - 'same': CDN assets of the same version
	 * - 'branch': latest CDN assets of the same major branch, recommended
	 * - 'latest': latest CDN assets from last branch, may cause compatibility
	 *   issues and is not recommended
	 *
	 * @var bool|string
	 */
	public $cdn = ContextlyKit::CDN_BRANCH;

	/**
	 * Kit version for packaging.
	 *
	 * Is only taken into account on 'pkg' mode, ignored on the rest modes.
	 *
	 * @var string
	 */
	public $version = 'dev';

	/**
	 * Contextly application ID.
	 *
	 * @var string
	 */
	public $appID = '';

	/**
	 * Contextly application secret.
	 *
	 * @var string
	 */
	public $appSecret = '';

	/**
	 * URL of the kit root folder.
	 *
	 * No trailing slash!
	 *
	 * Integration should either specify this setting or override the
	 * ContextlyKit::buildFileURL().
	 *
	 * @var string|null
	 */
	public $urlPrefix = null;

	/**
	 * Client short name.
	 *
	 * @var string|null
	 */
	public $client = null;

	/**
	 * Version of the client.
	 *
	 * @var string|null
	 */
	public $clientVersion = null;

}

/**
 * Base class for all the kit classes holding link to the kit instance.
 */
class ContextlyKitBase {

	/**
	 *
	 * @var ContextlyKit
	 */
	protected $kit;

	protected $settings;

	/**
	 *
	 * @param ContextlyKit $kit
	 */
	public function __construct( $kit ) {
		$this->kit      = $kit;
		$this->settings = $kit->getSettings();
	}

}

class ContextlyKitException extends Exception {

	protected function getPrintableDetails() {
		$details = array();

		$details['class']   = get_class( $this ) . '. Code: ' . $this->getCode();
		$details['message'] = 'Message: "' . $this->getMessage() . '"';
		$details['file']    = 'File: ' . $this->getFile() . ':' . $this->getLine();

		return $details;
	}

	public function __toString() {
		$details = $this->getPrintableDetails();

		// Add call stack to the end.
		$details['stack'] = "Stack trace:\n" . $this->getTraceAsString();

		return implode( "\n\n", $details );
	}

}
