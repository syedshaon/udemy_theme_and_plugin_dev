<?php
namespace WpAssetCleanUp\OptimiseAssets;

use WpAssetCleanUp\Preloads;
use WpAssetCleanUp\FileSystem;
use WpAssetCleanUp\CleanUp;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\Menu;
use WpAssetCleanUp\MetaBoxes;
use WpAssetCleanUp\Misc;

/**
 * Class OptimizeCss
 * @package WpAssetCleanUp
 */
class OptimizeCss
{
	/**
	 * @var float|int
	 */
	public static $cachedCssAssetsFileExpiresIn = 28800; // 8 hours in seconds (60 * 60 * 8)

	/**
	 * @var string
	 */
	public $jsonStorageFile = 'css-combined{maybe-extra-info}.json';

	/**
	 *
	 */
	public function init()
	{
		add_action('wp_loaded', function() {
			if (is_admin()) { // don't apply any changes if not in the front-end view (e.g. Dashboard view)
				return;
			}

			ob_start(function ($htmlSource) {
				// Do not do any optimization if "Test Mode" is Enabled
				if (! Menu::userCanManageAssets() && Main::instance()->settings['test_mode']) {
					return $htmlSource;
				}

				// There has to be at least one "<link", otherwise, it could be a feed request or something similar (not page, post, homepage etc.)
				if (stripos($htmlSource, '<link') === false) {
					return $htmlSource;
				}

				// Are there any assets unloaded where their "children" are ignored?
				// Since they weren't dequeued the WP way (to avoid unloading the "children"), they will be stripped here
				$ignoreChild = Main::instance()->getIgnoreChildren();

				if (isset($ignoreChild['styles']) && ! empty($ignoreChild['styles'])) {
					foreach ($ignoreChild['styles'] as $styleSrc) {
						$htmlSource = CleanUp::cleanLinkTagFromHtmlSource($styleSrc, $htmlSource);
					}
				}

				if (MinifyCss::isMinifyCssEnabled()) {
					// 'wpacu_css_minify_list' caching list is also checked; if it's empty, no minification is made
					$htmlSource = MinifyCss::updateHtmlSourceOriginalToMinCss($htmlSource);
				}

				$htmlSource = Preloads::instance()->doChanges($htmlSource);

				if ( array_key_exists('wpacu_no_css_combine', $_GET) || // not on query string request (debugging purposes)
					! $this->doCssCombine() ) {
					return $htmlSource;
				}

				// If "Do not combine CSS on this page" is checked in "Asset CleanUp: Options" side meta box
				// Works for posts, pages and custom post types
				if (defined('WPACU_CURRENT_PAGE_ID') && WPACU_CURRENT_PAGE_ID > 0) {
					$pageOptions = MetaBoxes::getPageOptions( WPACU_CURRENT_PAGE_ID );

					if ( isset( $pageOptions['no_css_optimize'] ) && $pageOptions['no_css_optimize'] ) {
						return $htmlSource;
					}
				}

				$useDom = function_exists('libxml_use_internal_errors') && function_exists('libxml_clear_errors') && class_exists('DOMDocument');

				if (! $useDom) {
					return $htmlSource;
				}

				// Speed up processing by getting the already existing final CSS file URI
				// This will avoid parsing the HTML DOM and determine the combined URI paths for all the CSS files
				$storageJsonContents = OptimizeCommon::getAssetCachedData($this->jsonStorageFile, self::getRelPathCssCacheDir(), 'css');

				// $uriToFinalCssFile will always be relative ONLY within WP_CONTENT_DIR . self::getRelPathCssCacheDir()
				// which is usually "wp-content/cache/asset-cleanup/css/"

				if (empty($storageJsonContents)) {
					$storageJsonContentsToSave = array();

					/*
					 * NO CACHING? Parse the DOM
					*/
					// Nothing in the database records or the retrieved cached file does not exist?
					OptimizeCommon::clearAssetCachedData( $this->jsonStorageFile );

					// Fetch the DOM, and then set a new transient
					$documentForCSS = new \DOMDocument();

					libxml_use_internal_errors(true);
					$documentForCSS->loadHTML( $htmlSource );

					$storageJsonContents = array();

					foreach ( array( 'head', 'body' ) as $docLocationTag ) {
						$combinedUriPaths = $hrefUriNotCombinableList = $localAssetsPaths = $linkHrefs = array();

						$docLocationElements = $documentForCSS->getElementsByTagName( $docLocationTag )->item( 0 );
						$linkTags            = $docLocationElements->getElementsByTagName( 'link' );

						if ( $linkTags === null ) {
							continue;
						}

						foreach ( $linkTags as $tagObject ) {
							if ( ! $tagObject->hasAttributes() ) {
								continue;
							}

							$linkAttributes = array();

							foreach ( $tagObject->attributes as $attrObj ) {
								$linkAttributes[ $attrObj->nodeName ] = trim( $attrObj->nodeValue );
							}

							// Only rel="stylesheet" (with no rel="preload" associated with it) gets prepared for combining as links with rel="preload" (if any) are never combined into a standard render-blocking CSS file
							// rel="preload" is there for a reason to make sure the CSS code is made available earlier prior to the one from rel="stylesheet" which is render-blocking
							if (isset($linkAttributes['rel'], $linkAttributes['href']) && $linkAttributes['href']) {
								// Make sure that tag value is checked and it's matched against the value from the HTML source code
								//$htmlSource .= $attrObj->nodeValue."\n";
								$href = (string) $linkAttributes['href'];

								$cssNotCombinable = false;

								// 1) Check if there is any rel="preload" connected to the rel="stylesheet"
								//    making sure the file is not added to the final CSS combined file

								// 2) Only combine media "all", "screen" and the ones with no media
								//    Do not combine media='only screen and (max-width: 768px)' etc.
								if ( $linkAttributes['rel'] === 'preload' ) {
									$cssNotCombinable = true;
								}

								if (isset($linkAttributes['data-wpacu-to-be-preloaded']) && $linkAttributes['data-wpacu-to-be-preloaded']) {
									$cssNotCombinable = true;
								}

								if ( array_key_exists( 'media',
										$linkAttributes ) && ! in_array( $linkAttributes['media'],
										array( 'all', 'screen' ) ) ) {
									$cssNotCombinable = true;
								}

								if ( $this->skipCombine( $linkAttributes['href'] ) ) {
									$cssNotCombinable = true;
								}

								if ( ! $cssNotCombinable ) {
									$localAssetPath = OptimizeCommon::getLocalAssetPath( $href, 'css' );

									// It will skip external stylesheets (from a different domain)
									if ( $localAssetPath ) {
										$combinedUriPaths[]        = OptimizeCommon::getHrefRelPath( $href );
										$localAssetsPaths[ $href ] = $localAssetPath;
										$linkHrefs[]               = $href;
									}
								}
							}
						}

						// No Link Tags? Continue
						if ( empty( $linkHrefs ) ) {
							continue;
						}

						$maybeDoCssCombine = $this->maybeDoCssCombine( sha1( implode( '', $combinedUriPaths ) ),
							$localAssetsPaths, $linkHrefs, $docLocationTag );

						// Local path to combined CSS file
						$localFinalCssFile = $maybeDoCssCombine['local_final_css_file'];

						// URI (e.g. /wp-content/cache/asset-cleanup/[file-name-here.css]) to the combined CSS file
						$uriToFinalCssFile = $maybeDoCssCombine['uri_final_css_file'];

						// Any link hrefs removed perhaps if the file wasn't combined?
						$linkHrefs = $maybeDoCssCombine['link_hrefs'];

						if ( file_exists( $localFinalCssFile ) ) {
							$storageJsonContents[$docLocationTag] = array(
								'uri_to_final_css_file' => $uriToFinalCssFile,
								'link_hrefs'            => array_map( function ( $href ) {
									return str_replace( '{site_url}', site_url(), $href );
								}, $linkHrefs )
							);

							$storageJsonContentsToSave[$docLocationTag] = array(
								'uri_to_final_css_file' => $uriToFinalCssFile,
								'link_hrefs'            => array_map( function ( $href ) {
									return str_replace( site_url(), '{site_url}', $href );
								}, $linkHrefs )
							);
						}
					}

					libxml_clear_errors();

					OptimizeCommon::setAssetCachedData(
						$this->jsonStorageFile,
						self::getRelPathCssCacheDir(),
						json_encode($storageJsonContentsToSave)
					);
				}

				if ( ! empty($storageJsonContents) ) {
					foreach ($storageJsonContents as $locationTag => $storageJsonContentLocation) {
						if (! isset($storageJsonContentLocation['link_hrefs'][0])) {
							continue;
						}

						$storageJsonContentLocation['link_hrefs'] = array_map( function ( $href ) {
							return str_replace( '{site_url}', site_url(), $href );
						}, $storageJsonContentLocation['link_hrefs'] );

						$finalTagUrl = OptimizeCommon::filterWpContentUrl() . self::getRelPathCssCacheDir() . $storageJsonContentLocation['uri_to_final_css_file'];

						$finalCssTag = <<<HTML
<link id='asset-cleanup-combined-css-{$locationTag}' rel='stylesheet' href='{$finalTagUrl}' type='text/css' media='all' />
HTML;

						$htmlSourceBeforeAnyLinkTagReplacement = $htmlSource;

						// Detect first LINK tag from the <$locationTag> and replace it with the final combined LINK tag
						$firstLinkTag = $this->getFirstLinkTag($storageJsonContentLocation['link_hrefs'][0], $htmlSource);

						if ($firstLinkTag) {
							$htmlSource = str_replace( $firstLinkTag, $finalCssTag, $htmlSource );
						}

						if ($htmlSource !== $htmlSourceBeforeAnyLinkTagReplacement) {
							$htmlSource = OptimizeCommon::stripJustCombinedFileTags( $storageJsonContentLocation['link_hrefs'], $htmlSource, 'css' ); // Strip the combined files to avoid duplicate code

							// There should be at least two replacements made
							if ( $htmlSource === 'do_not_combine' ) {
								$htmlSource = $htmlSourceBeforeAnyLinkTagReplacement;
							}
						}
					}
				}

				return $htmlSource;
			});
		}, 1);
	}

	/**
	 * @return string
	 */
	public static function getRelPathCssCacheDir()
	{
		return OptimizeCommon::getRelPathPluginCacheDir().'css/'; // keep trailing slash at the end
	}

	/**
	 * @param $firstLinkHref
	 * @param $htmlSource
	 *
	 * @return string
	 */
	public function getFirstLinkTag($firstLinkHref, $htmlSource)
	{
		$regExpPattern = '#<link[^>]*stylesheet[^>]*(>)#Usmi';

		preg_match_all($regExpPattern, $htmlSource, $matches);
		foreach ($matches[0] as $matchTag) {
			if (strpos($matchTag, $firstLinkHref) !== false) {
				return trim($matchTag);
			}
		}

		return '';
	}

	/**
	 * @param $shaOneCombinedUriPaths
	 * @param $localAssetsPaths
	 * @param $linkHrefs
	 * @param $docLocationTag
	 *
	 * @return array
	 */
	public function maybeDoCssCombine($shaOneCombinedUriPaths, $localAssetsPaths, $linkHrefs, $docLocationTag)
	{
		$current_user = wp_get_current_user();
		$dirToUserCachedFile = ((isset($current_user->ID) && $current_user->ID > 0) ? 'logged-in/'.$current_user->ID.'/' : '');

		$uriToFinalCssFile = $dirToUserCachedFile . $docLocationTag . '-' .$shaOneCombinedUriPaths . '.css';
		$localFinalCssFile = WP_CONTENT_DIR . self::getRelPathCssCacheDir() . $uriToFinalCssFile;

		$localDirForCssFile = WP_CONTENT_DIR . self::getRelPathCssCacheDir() . $dirToUserCachedFile;

		// Only combine if $shaOneCombinedUriPaths.css does not exist
		// If "?ver" value changes on any of the assets or the asset list changes in any way
		// then $shaOneCombinedUriPaths will change too and a new CSS file will be generated and loaded

		$skipIfFileExists = true;

		if ($skipIfFileExists || ! file_exists($localFinalCssFile)) {
			// Change $assetsContents as paths to fonts and images that are relative (e.g. ../, ../../) have to be updated
			$finalAssetsContents = '';

			foreach ($localAssetsPaths as $assetHref => $localAssetsPath) {
				$posLastSlash   = strrpos($assetHref, '/');
				$pathToAssetDir = substr($assetHref, 0, $posLastSlash);

				$parseUrl = parse_url($pathToAssetDir);

				if (isset($parseUrl['scheme']) && $parseUrl['scheme'] !== '') {
					$pathToAssetDir = str_replace(
						array('http://'.$parseUrl['host'], 'https://'.$parseUrl['host']),
						'',
						$pathToAssetDir
					);
				} elseif (strpos($pathToAssetDir, '//') === 0) {
					$pathToAssetDir = str_replace(
						array('//'.$parseUrl['host'], '//'.$parseUrl['host']),
						'',
						$pathToAssetDir
					);
				}

				$cssContent = FileSystem::file_get_contents($localAssetsPath);

				if ($cssContent) {
					// Do not combine it if it contains "@import"
					if (stripos($cssContent, '@import') !== false) {
						unset($localAssetsPaths[$assetHref]);
						$linkHrefKey = array_search($assetHref, $linkHrefs);
						unset($linkHrefs[$linkHrefKey]);
						continue;
					}

					// Does it have a source map? Strip it
					if (strpos($cssContent, 'sourceMappingURL') !== false) {
						$cssContent = OptimizeCommon::stripSourceMap($cssContent);
					}

					$finalAssetsContents .= '/*** Source: '.str_replace(ABSPATH, '/', $localAssetsPath)." ***/\n";
					$finalAssetsContents .= self::maybeFixCssBackgroundUrls($cssContent, $pathToAssetDir . '/') . "\n\n";
				}
			}

			$finalAssetsContents = trim($finalAssetsContents);

			if ($finalAssetsContents) {
				if ($dirToUserCachedFile !== '' && isset($current_user->ID) && $current_user->ID > 0) {
					if (! is_dir($localDirForCssFile)) {
						$makeLocalDirForCss = @mkdir($localDirForCssFile);

						if (! $makeLocalDirForCss) {
							return array('uri_final_css_file' => '', 'local_final_css_file' => '');
						}
					}
				}

				FileSystem::file_put_contents($localFinalCssFile, $finalAssetsContents);
			}
		}

		return array(
			'uri_final_css_file'   => $uriToFinalCssFile,
			'local_final_css_file' => $localFinalCssFile,
			'link_hrefs'           => $linkHrefs
		);
	}

	/**
	 * @param $cssContent
	 * @param $appendBefore
	 *
	 * @return mixed
	 */
	public static function maybeFixCssBackgroundUrls($cssContent, $appendBefore)
	{
		$cssContent = str_replace(
			array('url("../', "url('../", 'url(../'),
			array('url("'.$appendBefore.'../', "url('".$appendBefore.'../', 'url('.$appendBefore.'../'),
			$cssContent
		);

		// Avoid Background URLs starting with "data" or "http" as they do not need to have a path updated
		preg_match_all('/url\((?![\'"]?(?:data|http):)[\'"]?([^\'"\)]*)[\'"]?\)/i', $cssContent, $matches);

		// If it start with forward slash (/), it doesn't need fix, just skip it
		// Also skip ../ types as they were already processed
		$toSkipList = array("url('/", 'url("/', 'url(/');

		foreach ($matches[0] as $match) {
			$fullUrlMatch = trim($match);

			foreach ($toSkipList as $toSkip) {
				if (substr($fullUrlMatch, 0, strlen($toSkip)) === $toSkip) {
					continue 2; // doesn't need any fix, go to the next match
				}
			}

			// Go through all situations: with and without quotes, with traversal directory (e.g. ../../)
			$alteredMatch = str_replace(
				array('url("', "url('"),
				array('url("'.$appendBefore, "url('".$appendBefore),
				$fullUrlMatch
			);

			$alteredMatch = trim($alteredMatch);

			if (! in_array($fullUrlMatch{4}, array("'", '"', '/', '.'))) {
				$alteredMatch = str_replace('url(', 'url('.$appendBefore, $alteredMatch);
				$alteredMatch = str_replace(array('")', '\')'), ')', $alteredMatch);
			}

			// Finally, apply the changes
			$cssContent = str_replace($fullUrlMatch, $alteredMatch, $cssContent);

			// Bug fix
			$cssContent = str_replace(
				array($appendBefore.'"'.$appendBefore, $appendBefore."'".$appendBefore),
				$appendBefore,
				$cssContent
			);

			// Bug Fix 2
			$cssContent = str_replace($appendBefore . 'http', 'http', $cssContent);
			$cssContent = str_replace($appendBefore . '//', '//', $cssContent);
		}

		return $cssContent;
	}

	/**
	 * @param $href
	 *
	 * @return bool
	 */
	public function skipCombine($href)
	{
		$regExps = array();

		if (Main::instance()->settings['combine_loaded_css_exceptions'] !== '') {
			$loadedCssExceptionsPatterns = trim(Main::instance()->settings['combine_loaded_css_exceptions']);

			if (strpos($loadedCssExceptionsPatterns, "\n")) {
				// Multiple values (one per line)
				foreach (explode("\n", $loadedCssExceptionsPatterns) as $loadedCssExceptionPattern) {
					$regExps[] = '#'.trim($loadedCssExceptionPattern).'#';
				}
			} else {
				// Only one value?
				$regExps[] = '#'.trim($loadedCssExceptionsPatterns).'#';
			}
		}

		// No exceptions set? Do not skip combination
		if (empty($regExps)) {
			return false;
		}

		foreach ($regExps as $regExp) {
			if ( preg_match( $regExp, $href ) ) {
				// Skip combination
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function doCssCombine()
	{
		// No CSS files are combined in the Dashboard
		// Always in the front-end view
		// Do not combine if there's a POST request as there could be assets loading conditionally
		// that might not be needed when the page is accessed without POST, making the final CSS file larger
		if (! empty($_POST) || is_admin()) {
			return false; // Do not combine
		}

		// Only clean request URIs allowed
		if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
			// Exceptions
			if (! OptimizeCommon::loadOptimizedAssetsIfQueryStrings()) {
				return false;
			}
		}

		if (! OptimizeCommon::doCombineIsRegularPage()) {
			return false;
		}

		$pluginSettings = Main::instance()->settings;

		if ($pluginSettings['test_mode'] && ! Menu::userCanManageAssets()) {
			return false; // Do not combine anything if "Test Mode" is ON and the user is in guest mode (not logged-in)
		}

		if ($pluginSettings['combine_loaded_css'] === '') {
			return false; // Do not combine
		}

		if (Misc::isOptimizeCssEnabledByOtherParty('if_enabled')) {
			return false; // Do not combine (it's already enabled in other plugin)
		}

		if ( ($pluginSettings['combine_loaded_css'] === 'for_admin'
		      || $pluginSettings['combine_loaded_css_for_admin_only'] == 1)
		     && Menu::userCanManageAssets()) {
			return true; // Do combine
		}

		if ( $pluginSettings['combine_loaded_css_for_admin_only'] === ''
		     && in_array($pluginSettings['combine_loaded_css'], array('for_all', 1)) ) {
			return true; // Do combine
		}

		// Finally, return false as none of the checks above matched
		return false;
	}

	}
