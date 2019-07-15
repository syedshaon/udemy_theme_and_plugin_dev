<?php
namespace WpAssetCleanUp\OptimiseAssets;

use WpAssetCleanUp\FileSystem;
use WpAssetCleanUp\CleanUp;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\Menu;
use WpAssetCleanUp\MetaBoxes;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\Preloads;

/**
 * Class CombineJs
 * @package WpAssetCleanUp
 */
class OptimizeJs
{
	/**
	 * @var float|int
	 */
	public static $cachedJsAssetsFileExpiresIn = 28800; // 8 hours in seconds (60 * 60 * 8)

	/**
	 * @var string
	 */
	public $jsonStorageFile = 'js-combined{maybe-extra-info}.json';

	/**
	 *
	 */
	public function init()
	{
		add_action('wp_loaded', function() {
			if (is_admin()) { // don't apply any changes if not in the front-end view (e.g. Dashboard view)
				return;
			}

			ob_start(function($htmlSource) {
				// Do not do any optimization if "Test Mode" is Enabled
				if (! Menu::userCanManageAssets() && Main::instance()->settings['test_mode']) {
					return $htmlSource;
				}

				// There has to be at least one "<script", otherwise, it could be a feed request or something similar (not page, post, homepage etc.)
				if (stripos($htmlSource, '<script') === false) {
					return $htmlSource;
				}

				// Are there any assets unloaded where their "children" are ignored?
				// Since they weren't dequeued the WP way (to avoid unloading the "children"), they will be stripped here
				$ignoreChild = Main::instance()->getIgnoreChildren();

				if (isset($ignoreChild['scripts']) && ! empty($ignoreChild['scripts'])) {
					foreach ($ignoreChild['scripts'] as $scriptSrc) {
						$htmlSource = CleanUp::cleanScriptTagFromHtmlSource($scriptSrc, $htmlSource);
					}
				}

				/*
				 * #minifying
				 * STEP 2: Load minify-able caching list and replace the original source URLs with the new cached ones
				 */
				if (MinifyJs::isMinifyJsEnabled()) {
					// 'wpacu_js_minify_list' caching list is also checked; if it's empty, no minification is made
					$htmlSource = MinifyJs::updateHtmlSourceOriginalToMinJs($htmlSource);
				}

				$preloads = Preloads::instance()->getPreloads();

				if (isset($preloads['scripts']) && ! empty($preloads['scripts'])) {
					$htmlSource = Preloads::appendPreloadsForScriptsToHead($htmlSource);
				}

				$htmlSource = str_replace(Preloads::DEL_SCRIPTS_PRELOADS, '', $htmlSource);

				if ( array_key_exists('wpacu_no_js_combine', $_GET) || // not on query string request (debugging purposes)
					 ! $this->doJsCombine() ) {
					return $htmlSource;
				}

				// If "Do not combine CSS on this page" is checked in "Asset CleanUp Options" side meta box
				// Works for posts, pages and custom post types
				if (defined('WPACU_CURRENT_PAGE_ID') && WPACU_CURRENT_PAGE_ID > 0) {
					$pageOptions = MetaBoxes::getPageOptions( WPACU_CURRENT_PAGE_ID );

					if ( isset( $pageOptions['no_js_optimize'] ) && $pageOptions['no_js_optimize'] ) {
						return $htmlSource;
					}
				}

				$useDom = function_exists('libxml_use_internal_errors') && function_exists('libxml_clear_errors') && class_exists('DOMDocument');

				if (! $useDom) {
					return $htmlSource;
				}

				$combineLevel = 2;

				// Speed up processing by getting the already existing final CSS file URI
				// This will avoid parsing the HTML DOM and determine the combined URI paths for all the CSS files
				$finalCacheList = OptimizeCommon::getAssetCachedData($this->jsonStorageFile, self::getRelPathJsCacheDir(), 'js');

				// $uriToFinalJsFile will always be relative ONLY within WP_CONTENT_DIR . self::getRelPathJsCacheDir()
				// which is usually "wp-content/cache/asset-cleanup/js/"

				// "false" would make it avoid checking the cache and always use the DOM Parser / RegExp
				// for DEV purposes ONLY as it uses more resources
				if (empty($finalCacheList)) {
					/*
					 * NO CACHING TRANSIENT; Parse the DOM
					*/
					// Nothing in the database records or the retrieved cached file does not exist?
					OptimizeCommon::clearAssetCachedData($this->jsonStorageFile);

					$regExpPattern = '#<script[^>]*>.*?</script>#is';

					preg_match_all($regExpPattern, OptimizeCommon::cleanerHtmlSource($htmlSource), $matchesSourcesFromTags, PREG_SET_ORDER);

					// No <script> tag found? Do not continue
					if (empty($matchesSourcesFromTags)) {
						return $htmlSource;
					}

					if ($combineLevel === 2) {
						$matchesSourcesFromTags = $this->clearInlineScriptTags($matchesSourcesFromTags);
					}

					if (empty($matchesSourcesFromTags)) {
						return $htmlSource;
					}

					$combinableList = $bodyGroupIndexes = array();

					$groupIndex = 1;
					$jQueryAndMigrateGroup = 0;

					$jQueryGroupIndex = $loadsLocaljQuery = $loadsLocaljQueryMigrate = false;

					$lastScriptSrcFromHead = $this->lastScriptSrcFromHead($htmlSource);

					$reachedBody = false;

					$domTag = new \DOMDocument();

					libxml_use_internal_errors( true );

					// Only keep combinable JS files
					foreach ($matchesSourcesFromTags as $matchSourceFromTag) {
						$matchedSourceFromTag = trim( $matchSourceFromTag[0] );

						$domTag->loadHTML($matchedSourceFromTag);

						$scriptNotCombinable = $scriptPreloaded = $src = false;

						foreach ($domTag->getElementsByTagName( 'script' ) as $tagObject) {
							if (! $tagObject->hasAttributes()) {
								continue;
							}

							$scriptAttributes = array();

							foreach ( $tagObject->attributes as $attrObj ) {
								$scriptAttributes[ $attrObj->nodeName ] = trim($attrObj->nodeValue);
							}

							if (isset($scriptAttributes['src']) && $scriptAttributes['src']) {
								$src = (string) $scriptAttributes['src'];

								$scriptNotCombinable = false;

								if ($this->skipCombine($src)) {
									$scriptNotCombinable = true;
								}

								// Do not add it to the combination list if it has "async" or "defer" attributes
								if (in_array($scriptAttributes, array('async', 'defer'))) {
									$scriptNotCombinable = true;
								}

								if (isset($scriptAttributes['data-wpacu-to-be-preloaded']) && $scriptAttributes['data-wpacu-to-be-preloaded']) {
									$scriptNotCombinable = $scriptPreloaded = true;
								}
							}

							}

						if ( $src && ! $scriptNotCombinable ) {
							$localAssetPath = OptimizeCommon::getLocalAssetPath( $src, 'js' );

							if ( $localAssetPath ) {
								$combinableList[ $groupIndex ][] = array(
									'src'   => $src,
									'local' => $localAssetPath,
									'html'  => $matchedSourceFromTag
								);

								if ( strpos( $localAssetPath, '/wp-includes/js/jquery/jquery.js' ) !== false ) {
									$loadsLocaljQuery = true;
									$jQueryGroupIndex = $groupIndex;

									$jQueryArrayGroupKeys = array_keys( $combinableList[ $groupIndex ] );
									$jQueryScriptIndex    = array_pop( $jQueryArrayGroupKeys );

									$jQueryAndMigrateGroup ++;
								} elseif ( strpos( $localAssetPath,
										'/wp-includes/js/jquery/jquery-migrate.' ) !== false ) {
									$loadsLocaljQueryMigrate = true;
									$jQueryAndMigrateGroup ++;
								}
							}

							// We'll check the current group
							// If we have jQuery and jQuery migrate, we will consider the group completed
							// and we will move on to the next group
							if ( $jQueryAndMigrateGroup > 1 ) {
								$groupIndex ++;
								$jQueryAndMigrateGroup = 0; // reset it to avoid having one file per group!
							}

							// Have we passed <head> and stumbled upon the first script tag from the <body>
							// Then consider the group completed
							if ($lastScriptSrcFromHead && ($src === $lastScriptSrcFromHead)) {
								$groupIndex++;
								$reachedBody = true;
							}
						} elseif (! $scriptPreloaded) {
							$groupIndex ++;
						}

						if ($reachedBody) {
							$bodyGroupIndexes[] = $groupIndex;
						}
					}

					// Is the page loading local jQuery but not local jQuery Migrate?
					// Keep jQuery as standalone file (not in the combinable list)
					if ( $loadsLocaljQuery && ! $loadsLocaljQueryMigrate && isset($jQueryScriptIndex) ) {
						unset($combinableList[$jQueryGroupIndex][$jQueryScriptIndex]);
					}

					// Could be pages such as maintenance mode with no external JavaScript files
					if (empty($combinableList)) {
						return $htmlSource;
					}

					$groupNo = 1;

					$finalCacheList = array();

					foreach ($combinableList as $groupIndex => $groupFiles) {
						// Any groups having one file? Then it's not really a group and the file should load on its own
						// Could be one extra file besides the jQuery & jQuery Migrate group or the only JS file called within the HEAD
						if (count($groupFiles) < 2) {
							continue;
						}

						$combinedUriPaths = $localAssetsPaths = $groupScriptTags = $groupScriptSrcs = array();

						foreach ( $groupFiles as $groupFileData ) {
							$src                      = $groupFileData['src'];
							$groupScriptSrcs[]        = $src;
							$combinedUriPaths[]       = OptimizeCommon::getHrefRelPath( $src );
							$localAssetsPaths[ $src ] = $groupFileData['local'];
							$groupScriptTags[]        = $groupFileData['html'];
						}

						// <head> or <body>
						$docLocationScript = in_array($groupIndex, $bodyGroupIndexes) ? 'body' : 'head';

						$maybeDoJsCombine = $this->maybeDoJsCombine(
							sha1( implode( '', $combinedUriPaths ) ) . '-' . $groupNo,
							$localAssetsPaths,
							$docLocationScript
						);

						// Local path to combined CSS file
						$localFinalJsFile = $maybeDoJsCombine['local_final_js_file'];

						// URI (e.g. /wp-content/cache/asset-cleanup/[file-name-here.js]) to the combined JS file
						$uriToFinalJsFile = $maybeDoJsCombine['uri_final_js_file'];

						if ( ! file_exists( $localFinalJsFile ) ) {
							return $htmlSource; // something is not right as the file wasn't created, we will return the original HTML source
						}

						$groupScriptSrcsFilter = array_map( function ( $src ) {
							return str_replace( site_url(), '{site_url}', $src );
						}, $groupScriptSrcs );

						$groupScriptTagsFilter = array_map( function ( $scriptTag ) {
							return str_replace( site_url(), '{site_url}', $scriptTag );
						}, $groupScriptTags );

						$finalCacheList[ $groupNo ] = array(
							'uri_to_final_js_file' => $uriToFinalJsFile,
							'script_srcs'          => $groupScriptSrcsFilter,
							'script_tags'          => $groupScriptTagsFilter
						);

						if (Main::instance()->settings['combine_loaded_js_defer_body'] && in_array($groupIndex, $bodyGroupIndexes)) {
							$finalCacheList[ $groupNo ]['extras'][] = 'defer';
						}

						$groupNo++;
					}

					OptimizeCommon::setAssetCachedData($this->jsonStorageFile, self::getRelPathJsCacheDir(), json_encode($finalCacheList));
				}

				if (! empty($finalCacheList)) {
					foreach ( $finalCacheList as $groupNo => $cachedValues ) {
						$htmlSourceBeforeGroupReplacement = $htmlSource;

						$uriToFinalJsFile = $cachedValues['uri_to_final_js_file'];

						// Basic Combining (1) -> replace "first" tag with the final combination tag (there would be most likely multiple groups)
						// Enhanced Combining (2) -> replace "last" tag with the final combination tag (most likely one group)
						$indexReplacement = ($combineLevel === 2) ? (count($cachedValues['script_tags']) - 1) : 0;

						$finalTagUrl = OptimizeCommon::filterWpContentUrl() . self::getRelPathJsCacheDir() . $uriToFinalJsFile;

						$deferAttr = (isset($cachedValues['extras']) && in_array('defer', $cachedValues['extras'])) ? 'defer="defer"' : '';

						$finalJsTag = <<<HTML
<script {$deferAttr} id='asset-cleanup-combined-js-group-{$groupNo}' type='text/javascript' src='{$finalTagUrl}'></script>
HTML;
						$tagsStripped = 0;

						foreach ( $cachedValues['script_tags'] as $groupScriptTagIndex => $scriptTag ) {
							$scriptTag = str_replace( '{site_url}', site_url(), $scriptTag );

							if ( $groupScriptTagIndex === $indexReplacement ) {
								$htmlSourceBeforeTagReplacement = $htmlSource;
								$htmlSource = $this->strReplaceOnce( $scriptTag, $finalJsTag, $htmlSource );
							} else {
								$htmlSourceBeforeTagReplacement = $htmlSource;
								$htmlSource = $this->strReplaceOnce( $scriptTag, '', $htmlSource );
							}

							if ($htmlSource !== $htmlSourceBeforeTagReplacement) {
								$tagsStripped++;
							}
						}

						// At least two tags has have be stripped from the group to consider doing the group replacement
						// If the tags weren't replaced it's likely there were changes to their structure after they were cached for the group merging
						if ($tagsStripped < 2) {
							$htmlSource = $htmlSourceBeforeGroupReplacement;
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
	public static function getRelPathJsCacheDir()
	{
		return OptimizeCommon::getRelPathPluginCacheDir().'js/'; // keep trailing slash at the end
	}

	/**
	 * @param $matchesSourcesFromTags
	 *
	 * @return mixed
	 */
	public function clearInlineScriptTags($matchesSourcesFromTags)
	{
		$domTag = new \DOMDocument();

		libxml_use_internal_errors( true );

		foreach ($matchesSourcesFromTags as $scriptTagIndex => $matchSourceFromTag) {
			$matchedSourceFromTag = trim( $matchSourceFromTag[0] );

			$domTag->loadHTML( $matchedSourceFromTag );

			foreach ( $domTag->getElementsByTagName( 'script' ) as $tagObject ) {
				$hasSrc = false;

				if ( ! $tagObject->hasAttributes() ) {
					$hasSrc = false;
				} else {
					// Has attributes? Check them
					foreach ( $tagObject->attributes as $attrObj ) {
						if ( $attrObj->nodeName === 'src' && $attrObj->nodeValue ) {
							$hasSrc = true;
						}
					}
				}

				if (! $hasSrc) {
					unset($matchesSourcesFromTags[$scriptTagIndex]);
				}
			}
		}

		libxml_clear_errors();

		return $matchesSourcesFromTags;
	}

	/**
	 * @param $htmlSource
	 *
	 * @return string
	 */
	public function lastScriptSrcFromHead($htmlSource)
	{
		// Do not check MSIE conditional comments as they are not combined
		$htmlSource = OptimizeCommon::cleanerHtmlSource($htmlSource);

		$bodyHtml = Misc::extractBetween( $htmlSource, '<head', '</head>' );

		$regExpPattern = '#<script[^>]*>.*?</script>#is';

		preg_match_all( $regExpPattern, $bodyHtml, $matchesSourcesFromTags, PREG_SET_ORDER );

		$domTag = new \DOMDocument();

		libxml_use_internal_errors( true );

		// Only keep combinable JS files
		foreach ( array_reverse($matchesSourcesFromTags) as $matchSourceFromTag ) {
			$matchedSourceFromTag = trim( $matchSourceFromTag[0] );

			$domTag->loadHTML( $matchedSourceFromTag );

			foreach ( $domTag->getElementsByTagName( 'script' ) as $tagObject ) {
				if ( ! $tagObject->hasAttributes() ) {
					continue;
				}

				foreach ( $tagObject->attributes as $attrObj ) {
					if ( $attrObj->nodeName === 'src' && $attrObj->nodeValue ) {
						return (string) $attrObj->nodeValue;
						break;
					}
				}
			}
		}

		libxml_clear_errors();

		return '';
	}

	/**
	 * @param $shaOneCombinedUriPaths
	 * @param $localAssetsPaths
	 * @param $doclocationScript
	 *
	 * @return array
	 */
	public function maybeDoJsCombine($shaOneCombinedUriPaths, $localAssetsPaths, $docLocationScript)
	{
		$current_user = wp_get_current_user();
		$dirToUserCachedFile = ((isset($current_user->ID) && $current_user->ID > 0) ? 'logged-in/'.$current_user->ID.'/' : '');

		$uriToFinalJsFile = $dirToUserCachedFile . $docLocationScript . '-' . $shaOneCombinedUriPaths . '.js';

		$localFinalJsFile = WP_CONTENT_DIR . self::getRelPathJsCacheDir() . $uriToFinalJsFile;
		$localDirForJsFile = WP_CONTENT_DIR . self::getRelPathJsCacheDir() . $dirToUserCachedFile;

		// Only combine if $shaOneCombinedUriPaths.js does not exist
		// If "?ver" value changes on any of the assets or the asset list changes in any way
		// then $shaOneCombinedUriPaths will change too and a new JS file will be generated and loaded

		$skipIfFileExists = true;

		if ($skipIfFileExists || ! file_exists($localFinalJsFile)) {
			// Change $assetsContents as paths to fonts and images that are relative (e.g. ../, ../../) have to be updated
			$finalJsContentsGroupsArray = array();

			foreach ($localAssetsPaths as $assetHref => $localAssetsPath) {
				$posLastSlash = strrpos($assetHref, '/');
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

				$jsContent = FileSystem::file_get_contents($localAssetsPath);

				if ($jsContent) {
					// Does it have a source map? Strip it
					if (strpos($jsContent, 'sourceMappingURL') !== false) {
						$jsContent = OptimizeCommon::stripSourceMap($jsContent);
					}

					$finalJsContentsIndex = 1;

					$finalJsContentsGroupsArray[$finalJsContentsIndex][] = '/*** Source: '.str_replace(ABSPATH, '/', $localAssetsPath)." ***/\n" . self::maybeDoJsFixes($jsContent, $pathToAssetDir . '/') . "\n\n";
				}
			}

			if (! empty($finalJsContentsGroupsArray)) {
				$contentsOne = isset($finalJsContentsGroupsArray[1]) && ! empty($finalJsContentsGroupsArray[1]) ? implode ('', $finalJsContentsGroupsArray[1]) : '';
				$contentsTwo = isset($finalJsContentsGroupsArray[2]) && ! empty($finalJsContentsGroupsArray[2]) ? implode ('', $finalJsContentsGroupsArray[2]) : '';

				$finalJsContents = $contentsOne . $contentsTwo;

				if ( $dirToUserCachedFile !== '' && isset( $current_user->ID ) && $current_user->ID > 0) {
				     if (! is_dir( $localDirForJsFile)) {
					     $makeLocalDirForJs = @mkdir($localDirForJsFile);

					     if (! $makeLocalDirForJs) {
						     return array('uri_final_js_file' => '', 'local_final_js_file' => '');
					     }
				     }
				}

				@file_put_contents( $localFinalJsFile, $finalJsContents );
			}
		}

		return array(
			'uri_final_js_file'   => $uriToFinalJsFile,
			'local_final_js_file' => $localFinalJsFile
		);
	}

	/**
	 * @param $jsContent
	 * @param $appendBefore
	 *
	 * @return mixed
	 */
	public static function maybeDoJsFixes($jsContent, $appendBefore)
	{
		// Relative URIs for CSS Paths
		// For code such as:
		// $(this).css("background", "url('../images/image-1.jpg')");
		$jsContent = str_replace(
			array('url("../', "url('../", 'url(../'),
			array('url("'.$appendBefore.'../', "url('".$appendBefore.'../', 'url('.$appendBefore.'../'),
			$jsContent
		);

		$jsContent = trim($jsContent);

		if (substr($jsContent, -1) !== ';') {
			$jsContent .= "\n" . ';'; // add semicolon as the last character
		}
		return $jsContent;
	}

	/**
	 * @param $src
	 *
	 * @return bool
	 */
	public function skipCombine($src)
	{
		$regExps = array();

		if (Main::instance()->settings['combine_loaded_js_exceptions'] !== '') {
			$loadedCssExceptionsPatterns = trim(Main::instance()->settings['combine_loaded_js_exceptions']);

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
			if ( preg_match( $regExp, $src ) ) {
				// Skip combination
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function doJsCombine()
	{
		// No JS files are combined in the Dashboard
		// Always in the front-end view
		// Do not combine if there's a POST request as there could be assets loading conditionally
		// that might not be needed when the page is accessed without POST, making the final JS file larger
		if (! empty($_POST) || is_admin()) {
			return false; // Do not combine
		}

		// Only clean request URIs allowed (with few exceptions)
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
			return false; // Do not combine anything if "Test Mode" is ON
		}

		if ($pluginSettings['combine_loaded_js'] === '') {
			return false; // Do not combine
		}

		if (Misc::isOptimizeJsEnabledByOtherParty('if_enabled')) {
			return false; // Do not combine (it's already enabled in other plugin)
		}

		if ( ($pluginSettings['combine_loaded_js'] === 'for_admin'
		     || $pluginSettings['combine_loaded_js_for_admin_only'] == 1)
		    && Menu::userCanManageAssets() ) {
			return true; // Do combine
		}

		if ( $pluginSettings['combine_loaded_js_for_admin_only'] === ''
		    && in_array($pluginSettings['combine_loaded_js'], array('for_all', 1)) ) {
			return true; // Do combine
		}

		// Finally, return false as none of the checks above matched
		return false;
	}

	/**
	 * @param $strFind
	 * @param $strReplaceWith
	 * @param $string
	 *
	 * @return mixed
	 */
	public static function strReplaceOnce($strFind, $strReplaceWith, $string)
	{
		if ( strpos($string, $strFind) === false ) {
			return $string;
		}

		$occurrence = strpos($string, $strFind);
		return substr_replace($string, $strReplaceWith, $occurrence, strlen($strFind));
	}

	}
