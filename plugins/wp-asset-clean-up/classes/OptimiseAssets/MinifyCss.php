<?php
namespace WpAssetCleanUp\OptimiseAssets;

use WpAssetCleanUp\FileSystem;
use WpAssetCleanUp\Main;
use WpAssetCleanUp\Menu;
use WpAssetCleanUp\Misc;
use WpAssetCleanUp\MetaBoxes;

/**
 * Class MinifyCss
 * @package WpAssetCleanUp\OptimiseAssets
 */
class MinifyCss
{
	/**
	 *
	 */
	public function init()
	{
		add_action('wp_footer', function() {
			// Do not continue if "Minify CSS" is not enabled (in "Settings" or on the fly)
			if (! self::isMinifyCssEnabled()) {
				return;
			}

			global $wp_styles;

			$allStylesHandles = wp_cache_get('wpacu_all_styles_handles');

			if (empty($allStylesHandles)) {
				return;
			}

			$cssMinifyList = array();

			// [Start] Collect for caching
			$wpStylesDone = $wp_styles->done;
			$wpStylesRegistered = $wp_styles->registered;

			foreach ($wpStylesDone as $handle) {
				if (isset($wpStylesRegistered[$handle])) {
					$value = $wpStylesRegistered[$handle];

					$minifyValues = $this->maybeMinifyIt($value);

					if (! empty($minifyValues)) {
						$cssMinifyList[] = $minifyValues;
					}
				}
			}

			if (empty($cssMinifyList)) {
				return;
			}

			wp_cache_add('wpacu_css_minify_list', $cssMinifyList);
			// [End] Collect for caching
		}, PHP_INT_MAX);
	}

	/**
	 * Next: Alter the HTML source by updating the original link URLs with the just cached ones
	 *
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function updateHtmlSourceOriginalToMinCss($htmlSource)
	{
		$cssMinifyList = wp_cache_get('wpacu_css_minify_list');

		// This will be taken from the transient
		if (empty($cssMinifyList)) {
			return $htmlSource;
		}

		$regExpPattern = '#<link[^>]*(stylesheet|preload)[^>]*(>)#Usmi';

		preg_match_all($regExpPattern, OptimizeCommon::cleanerHtmlSource($htmlSource), $matchesSourcesFromTags, PREG_SET_ORDER);

		if (empty($matchesSourcesFromTags)) {
			return $htmlSource;
		}

		foreach ($matchesSourcesFromTags as $matches) {
			$linkSourceTag = $matches[0];

			if (strip_tags($linkSourceTag) !== '') {
				// Hmm? Not a valid tag... Skip it...
				continue;
			}

			foreach ($cssMinifyList as $listValues) {
				// If the minified files are deleted (e.g. /wp-content/cache/ is cleared)
				// do not replace the CSS file path to avoid breaking the website
				if (! file_exists(rtrim(ABSPATH, '/') . $listValues[1])) {
					continue;
				}

				$sourceUrl = site_url() . $listValues[0];
				$minUrl    = site_url() . $listValues[1];

				if ($linkSourceTag !== str_ireplace($sourceUrl, $minUrl, $linkSourceTag)) {
					$newLinkSourceTag = self::updateOriginalToMinifiedTag($linkSourceTag, $sourceUrl, $minUrl);
					$htmlSource = str_replace($linkSourceTag, $newLinkSourceTag, $htmlSource);
					break;
				}
			}

			}

		return $htmlSource;
	}

	/**
	 * @param $linkSourceTag
	 * @param $sourceUrl
	 * @param $minUrl
	 *
	 * @return mixed
	 */
	public static function updateOriginalToMinifiedTag($linkSourceTag, $sourceUrl, $minUrl)
	{
		$newLinkSourceTag = str_replace($sourceUrl, $minUrl, $linkSourceTag);

		// Strip ?ver=
		$newLinkSourceTag = str_replace('&#038;ver=', '?ver=', $newLinkSourceTag);
		$toStrip = Misc::extractBetween($newLinkSourceTag, '?ver=', ' ');

		if (in_array(substr($toStrip, -1), array('"', "'"))) {
			$toStrip = '?ver='. trim(trim($toStrip, '"'), "'");
			$newLinkSourceTag = str_replace($toStrip, '', $newLinkSourceTag);
		}

		return $newLinkSourceTag;
	}

	/**
	 * @param $value
	 *
	 * @return array
	 */
	public function maybeMinifyIt($value)
	{
		global $wp_version;

		$src = isset($value->src) ? $value->src : false;

		if (! $src || $this->skipMinify($src)) {
			return array();
		}

		$handleDbStr = md5($value->handle);

		$transientName = 'wpacu_css_minify_'.$handleDbStr;

		$savedValues = get_transient( $transientName );

			if ( $savedValues ) {
				$savedValuesArray = json_decode( $savedValues, ARRAY_A );

				if ( $savedValuesArray['ver'] !== $value->ver ) {
					// New File Version? Delete transient as it will be re-added to the database with the new version
					delete_transient( $transientName );
				} else {
					$localPathToCssMin = str_replace( '//', '/', ABSPATH . $savedValuesArray['min_uri'] );

					if ( isset( $savedValuesArray['source_uri'] ) && file_exists( $localPathToCssMin ) ) {
						return array(
							$savedValuesArray['source_uri'],
							$savedValuesArray['min_uri'],
							);
					}
				}
			}

		if (strpos($src, '/wp-includes/') === 0) {
			$src = site_url() . $src;
		}

		if ($value->handle === 'sccss_style' && in_array('simple-custom-css/simple-custom-css.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			/*
			 * Special Case: "Simple Custom CSS" Plugin
			 *
			 * /?sccss=1
			 *
			 * As it is (no minification or optimization), it adds extra load time to the page
			 * as the CSS is read via PHP and all the WP environment is loading
			 */
			$pathToAssetDir = '';
			$sourceBeforeMin = $value->src;

			$cssContent = Misc::getSimpleCustomCss();

			$newLocalSrc = WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir() . 'sccss_style.css';

			// Append CSS content to make it cacheable (exception)
			$cssContent = '/*! Simple Custom CSS */' ."\n". $cssContent;

			if (! FileSystem::file_put_contents($newLocalSrc, $cssContent)) {
				return array();
			}
			// strpos($src, '.css?ver=') === false
		} elseif (strpos($src, '/?custom-css=') !== false) {
			/*
			 * JetPack Custom CSS
			 * /?custom-css
			 */
			global $wp_version;

			$pathToAssetDir  = '';
			$sourceBeforeMin = $value->src;

			if (! OptimizeCommon::isSourceFromSameHost($sourceBeforeMin)) {
				return array();
			}

			$response     = wp_remote_get($sourceBeforeMin);
			$responseCode = wp_remote_retrieve_response_code($response);

			if ($responseCode !== 200) {
				return array();
			}

			$cssContent = wp_remote_retrieve_body($response);

			$listSrcAfterSlash = str_replace('/?', '', strrchr($src, '/?'));

			parse_str($listSrcAfterSlash, $outputSrcParse);

			$customCssVersion = isset($outputSrcParse['custom-css']) ? $outputSrcParse['custom-css'] : $wp_version;
			$extraVersion     = isset($value->ver) ? '_'.$value->ver : '';

			$newLocalSrc = WP_CONTENT_DIR . OptimizeCss::getRelPathCssCacheDir() . 'custom_css_' . $customCssVersion . $extraVersion . '.css';

			// Append CSS content to make it cacheable (exception)
			$cssContent = '/*! JetPack Custom CSS */' ."\n". $cssContent;

			if (! FileSystem::file_put_contents($newLocalSrc, $cssContent)) {
				return array();
			}
		} else {
			/*
			 * All the CSS that exists as a .css file within the plugins/theme
			 */
			$localAssetPath = OptimizeCommon::getLocalAssetPath($src, 'css');

			if (! file_exists($localAssetPath)) {
				return array();
			}

			$assetHref = $src;

			$posLastSlash   = strrpos($assetHref, '/');
			$pathToAssetDir = substr($assetHref, 0, $posLastSlash);

			$parseUrl = parse_url($pathToAssetDir);

			if (isset($parseUrl['scheme']) && $parseUrl['scheme'] !== '') {
				$pathToAssetDir = str_replace(
					array('http://' . $parseUrl['host'], 'https://' . $parseUrl['host']),
					'',
					$pathToAssetDir
				);
			} elseif (strpos($pathToAssetDir, '//') === 0) {
				$pathToAssetDir = str_replace(
					array('//' . $parseUrl['host'], '//' . $parseUrl['host']),
					'',
					$pathToAssetDir
				);
			}

			$cssContent = FileSystem::file_get_contents($localAssetPath);

			$sourceBeforeMin = str_replace(ABSPATH, '/', $localAssetPath);
		}

		$cssContent = OptimizeCss::maybeFixCssBackgroundUrls($cssContent, $pathToAssetDir . '/'); // Minify it and save it to /wp-content/cache/css/min/

		$cssContent = self::applyMinification($cssContent);

		// Relative path to the new file
		$ver = (isset($value->ver) && $value->ver) ? $value->ver : $wp_version;

		$newFilePathUri  = OptimizeCss::getRelPathCssCacheDir() . 'min/' . $value->handle . '-v' . $ver . '.css';

		$newLocalPath    = WP_CONTENT_DIR . $newFilePathUri; // Ful Local path
		$newLocalPathUrl = WP_CONTENT_URL . $newFilePathUri; // Full URL path

		if ($cssContent) {
			$cssContent = '/*** Source (before minification): ' . $sourceBeforeMin . ' ***/' . "\n" . $cssContent;
		}

		$saveFile = FileSystem::file_put_contents($newLocalPath, $cssContent);

		if (! $saveFile && ! $cssContent) {
			return array();
		}

		$saveValues = array(
			'source_uri' => OptimizeCommon::getHrefRelPath($src),
			'min_uri'    => OptimizeCommon::getHrefRelPath($newLocalPathUrl),
			'ver'        => $ver
		);

		// Add / Re-add (with new version) transient
		set_transient($transientName, json_encode($saveValues));

		return array(
			OptimizeCommon::getHrefRelPath($src),
			OptimizeCommon::getHrefRelPath($newLocalPathUrl),
			);
	}

	/**
	 * @param $cssContent
	 *
	 * @return string|string[]|null
	 */
	public static function applyMinification($cssContent)
	{
		// Replace multiple whitespace with only one
		$cssContent = preg_replace( '/\s+/', ' ', $cssContent );

		// Remove comment blocks, everything between /* and */, except the ones preserved with /*! ... */ or /** ... */
		$cssContent = preg_replace( '~/\*(?![\!|\*])(.*?)\*/~', '', $cssContent );

		// Remove ; before }
		$cssContent = preg_replace( '/;(?=\s*})/', '', $cssContent );

		// Remove space after , : ; { } */ >
		$cssContent = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $cssContent );

		// Remove space before , ; { } >
		$cssContent = preg_replace( '/ (,|;|\{|}|>)/', '$1', $cssContent );

		// Strip units such as px,em,pt etc. if value is 0 (converts 0px to 0)
		$cssContent = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $cssContent );

		// Strip leading 0 on decimal values (converts 0.5px into .5px)
		$cssContent = preg_replace( '/(:| )0\.(\d+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $cssContent );

		// Converts #ff000 to #f00
		$cssContent = preg_replace("/#([0-9a-fA-F])\\1([0-9a-fA-F])\\2([0-9a-fA-F])\\3/", '#$1$2$3', $cssContent);

		$strReps = array(
			// Converts things such as "margin:0 0 0 0;" to "margin:0;"
			':0 0 0 0;' => ':0;'
			);

		$cssContent = str_replace(array_keys($strReps), array_values($strReps), $cssContent);

		// Remove whitespaces before and after the content
		return trim($cssContent);
	}

	/**
	 * @param $src
	 *
	 * @return bool
	 */
	public function skipMinify($src)
	{
		$regExps = array(
			'#/wp-content/plugins/wp-asset-clean-up(.*?).min.css#',

			// Other libraries from the core that end in .min.css
			'#/wp-includes/css/(.*?).min.css#',

			// Files within /wp-content/uploads/ or /wp-content/cache/
			// Could belong to plugins such as "Elementor, "Oxygen" etc.
			'#/wp-content/uploads/(.*?).css#',
			'#/wp-content/cache/(.*?).css#'

			);

		if (Main::instance()->settings['minify_loaded_css_exceptions'] !== '') {
			$loadedCssExceptionsPatterns = trim(Main::instance()->settings['minify_loaded_css_exceptions']);

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

		foreach ($regExps as $regExp) {
			if ( preg_match( $regExp, $src ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function isMinifyCssEnabled()
	{
		// Request Minify On The Fly
		// It will preview the page with CSS minified
		// Only if the admin is logged-in as it uses more resources (CPU / Memory)
		if (array_key_exists('wpacu_css_minify', $_GET) && Menu::userCanManageAssets()) {
			return true;
		}

		if ( array_key_exists('wpacu_no_css_minify', $_GET) || // not on query string request (debugging purposes)
		     is_admin() || // not for Dashboard view
		     (! Main::instance()->settings['minify_loaded_css']) || // Minify CSS has to be Enabled
		     (Main::instance()->settings['test_mode'] && ! Menu::userCanManageAssets()) ) { // Does not trigger if "Test Mode" is Enabled
			return false;
		}

		if (defined('WPACU_CURRENT_PAGE_ID') && WPACU_CURRENT_PAGE_ID > 0 && is_singular()) {
			// If "Do not minify CSS on this page" is checked in "Asset CleanUp: Options" side meta box
			$pageOptions = MetaBoxes::getPageOptions( WPACU_CURRENT_PAGE_ID );

			if ( isset( $pageOptions['no_css_minify'] ) && $pageOptions['no_css_minify'] ) {
				return false;
			}
		}

		if (Misc::isOptimizeCssEnabledByOtherParty('if_enabled')) {
			return false;
		}

		return true;
	}
}
