<?php
namespace WpAssetCleanUp;

/**
 * Class CleanUp
 * @package WpAssetCleanUp
 */
class CleanUp
{
	/**
	 *
	 */
	public function init()
	{
		// Is "Test Mode" is enabled and the page is viewed by a regular visitor (not administrator with plugin activation privileges)?
		// Stop here as the script will NOT PREVENT any of the elements below to load
		// They will load as they used to for the regular visitor while the admin debugs the website
		add_action('init', function() {
			if ( Main::instance()->preventUnloadAssets() ) {
				return;
			}

			CleanUp::doClean();
		}, 12);
	}

	/**
	 *
	 */
	public function doClean()
	{
		$settings = Main::instance()->settings;

		// Remove "Really Simple Discovery (RSD)" link?
		if ($settings['remove_rsd_link'] == 1) {
			// <link rel="EditURI" type="application/rsd+xml" title="RSD" href="https://yourwebsite.com/xmlrpc.php?rsd" />
			remove_action('wp_head', 'rsd_link');
		}

		// Remove "Windows Live Writer" link?
		if ($settings['remove_wlw_link'] == 1) {
			// <link rel="wlwmanifest" type="application/wlwmanifest+xml" href="http://yourwebsite.com/wp-includes/wlwmanifest.xml">
			remove_action('wp_head', 'wlwmanifest_link');
		}

		// Remove "REST API" link?
		if ($settings['remove_rest_api_link'] == 1) {
			// <link rel='https://api.w.org/' href='https://yourwebsite.com/wp-json/' />
			remove_action('wp_head', 'rest_output_link_wp_head');
		}

		// Remove "Shortlink"?
		if ($settings['remove_shortlink'] == 1) {
			// <link rel='shortlink' href="https://yourdomain.com/?p=1">
			remove_action('wp_head', 'wp_shortlink_wp_head');

			// link: <https://yourdomainname.com/wp-json/>; rel="https://api.w.org/", <https://yourdomainname.com/?p=[post_id_here]>; rel=shortlink
			remove_action('template_redirect', 'wp_shortlink_header', 11);

			}

		// Remove "Post's Relational Links"?
		if ($settings['remove_posts_rel_links'] == 1) {
			// <link rel='prev' title='Title of adjacent post' href='https://yourdomain.com/adjacent-post-slug-here/' />
			remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
		}

		// Remove "WordPress version" tag?
		if ($settings['remove_wp_version']) {
			// <meta name="generator" content="WordPress 4.9.8" />
			remove_action('wp_head', 'wp_generator');

			// also hide it from RSS
			add_filter('the_generator', '__return_false');
		}

		// Remove Main RSS Feed Link?
		if ($settings['remove_main_feed_link']) {
			add_filter('feed_links_show_posts_feed', '__return_false');
			remove_action('wp_head', 'feed_links_extra', 3);
		}

		// Remove Comment RSS Feed Link?
		if ($settings['remove_comment_feed_link']) {
			add_filter('feed_links_show_comments_feed', '__return_false');
		}

		// Remove "WordPress version" and all other "generator" meta tags?
		if ($settings['remove_generator_tag']) {
			add_action('wp_loaded', function () {
				ob_start(function ($htmlSource) {
					return self::removeMetaGenerators($htmlSource);
				});
			}, PHP_INT_MAX);
		}

		// Remove valid HTML Comments
		if ($settings['remove_html_comments']) {
			add_action('wp_loaded', function () {
				ob_start(function ($htmlSource) {
					return self::removeHtmlComments($htmlSource);
				});
			}, PHP_INT_MAX);
		}

		// Disable XML-RPC protocol support (partially or completely)
		if (in_array($settings['disable_xmlrpc'], array('disable_all', 'disable_pingback'))) {
			// Partially or Completely Options / Pingback will be disabled
			$this->disableXmlRpcPingback();

			// Complete disable the service
			if ($settings['disable_xmlrpc'] === 'disable_all') {
				add_filter('xmlrpc_enabled', '__return_false');
			}

			// Also clean it up from the <head>
			add_action('wp_loaded', function() {
				ob_start(function ($htmlSource) {
					$pingBackUrl = get_bloginfo('pingback_url');

					$matchRegExps = array(
						'#<link rel=("|\')pingback("|\') href=("|\')'.$pingBackUrl.'("|\')( /|)>#',
						'#<link href=("|\')'.$pingBackUrl.'("|\') rel=("|\')pingback("|\')( /|)>#'
					);

					foreach ($matchRegExps as $matchRegExp) {
						$htmlSource = preg_replace($matchRegExp, '', $htmlSource);
					}

					return $htmlSource;
				});
			});
		}
	}

	/**
	 *
	 */
	public function disableXmlRpcPingback()
	{
		// Disable Pingback method
		add_filter('xmlrpc_methods', function ($methods) {
			unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']);
			return $methods;
		} );

		// Remove X-Pingback HTTP header
		add_filter('wp_headers', function ($headers) {
			unset($headers['X-Pingback']);
			return $headers;
		});
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function removeMetaGenerators($htmlSource)
	{
		if (stripos($htmlSource, '<meta') === false) {
			return $htmlSource;
		}

		// Use DOMDocument to alter the HTML Source and Remove the tags
		$htmlSourceOriginal = $htmlSource;

		if (function_exists('libxml_use_internal_errors')
		    && function_exists('libxml_clear_errors')
		    && class_exists('DOMDocument'))
		{
			$document = new \DOMDocument();
			libxml_use_internal_errors(true);

			$document->loadHTML($htmlSource);

			$domUpdated = false;

			foreach ($document->getElementsByTagName('meta') as $tagObject) {
				$nameAttrValue = $tagObject->getAttribute('name');

				if ($nameAttrValue === 'generator') {
					$outerTag = $outerTagRegExp = trim(self::getOuterHTML($tagObject));
					$last2Chars = substr($outerTag, -2);

					if ($last2Chars === '">' || $last2Chars === "'>") {
						$tagWithoutLastChar = substr($outerTag, 0, -1);
						$outerTagRegExp = $tagWithoutLastChar.'(.*?)>';
					}

					if (strpos($outerTagRegExp, '<meta') !== false) {
						preg_match_all('#' . $outerTagRegExp . '#si', $htmlSource, $matches);

						if (isset($matches[0][0]) && ! empty($matches[0][0]) && strip_tags($matches[0][0]) === '') {
							$htmlSource = str_replace( $matches[0][0], '', $htmlSource );
						}

						if ($htmlSource !== $htmlSourceOriginal) {
							$domUpdated = true;
						}
					}
				}
			}

			libxml_clear_errors();

			if ($domUpdated) {
				return $htmlSource;
			}
		}

		// DOMDocument is not enabled. Use the RegExp instead (not as smooth, but does its job)!
		preg_match_all('#<meta(.*?)>#si', $htmlSource, $matches);

		if (isset($matches[0]) && ! empty($matches[0])) {
			foreach ($matches[0] as $metaTag) {
				if (strip_tags($metaTag) === ''
				    && (stripos($metaTag, 'name="generator"') !== false || stripos($metaTag, 'name=\'generator\'') !== false)
				) {
					$htmlSource = str_replace($metaTag, '', $htmlSource);
				}
			}
		}

		return $htmlSource;
	}

	/**
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function removeHtmlComments($htmlSource)
	{
		// No comments? Do not continue
		if (strpos($htmlSource, '<!--') === false) {
			return $htmlSource;
		}

		if (! (function_exists('libxml_use_internal_errors')
		    && function_exists('libxml_clear_errors')
		    && class_exists('DOMDocument')))
		{
			return $htmlSource;
		}

		$domComments = new \DOMDocument();
		libxml_use_internal_errors(true);

		$domComments->loadHTML($htmlSource);

		$xpathComments = new \DOMXPath($domComments);
		$comments = $xpathComments->query('//comment()');

		libxml_clear_errors();

		if ($comments === null) {
			return $htmlSource;
		}

		preg_match_all('#<!--(.*?)-->#s', $htmlSource, $matchesRegExpComments);

		// "comments" within tag attributes or script tags?
		// e.g. <script>var type='<!-- A comment here -->';</script>
		// e.g. <div data-info="This is just a <!-- comment --> text">Content here</div>
		$commentsWithinQuotes = array();

		if (isset($matchesRegExpComments[1]) && count($matchesRegExpComments[1]) !== count($comments)) {
			preg_match_all('#=(|\s+)(\'|")(|\s+)<!--(.*?)-->(|\s+)(\'|")#s', $htmlSource, $matchesCommentsWithinQuotes);

			if (isset($matchesCommentsWithinQuotes[0]) && ! empty($matchesCommentsWithinQuotes[0])) {
				foreach ($matchesCommentsWithinQuotes[0] as $matchedDataOriginal) {
					$matchedDataUpdated = str_replace(
						array('', '<!--', '-->'),
						array('--wpacu-space-del--', '--wpacu-start-comm--', '--wpacu-end-comm--'),
						$matchedDataOriginal
					);

					$htmlSource = str_replace($matchedDataOriginal, $matchedDataUpdated, $htmlSource);

					$commentsWithinQuotes[] = array(
						'original' => $matchedDataOriginal,
						'updated'  => $matchedDataUpdated
					);
				}
			}
		}

		foreach ($comments as $comment) {
			$entireComment = self::getOuterHTML($comment);

			// Do not strip MSIE conditional comments
			if (preg_match('#<!--\[if(.*?)\[endif\]-->#si', $entireComment)) {
				continue;
			}

			// Any exceptions set in "Strip HTML comments?" textarea?
			if (Main::instance()->settings['remove_html_comments_exceptions']) {
				$removeHtmlCommentsExceptions = trim(Main::instance()->settings['remove_html_comments_exceptions']);

				if (strpos($removeHtmlCommentsExceptions, "\n") !== false) {
					foreach (explode("\n", $removeHtmlCommentsExceptions) as $removeCommExceptionPattern) {
						$removeCommExceptionPattern = trim($removeCommExceptionPattern);

						if (stripos($entireComment, $removeCommExceptionPattern) !== false) {
							continue 2;
						}
					}
				} elseif (stripos($entireComment, $removeHtmlCommentsExceptions) !== false) {
					continue;
				}
			}

			$htmlSource = str_replace(
				array(
					$entireComment,
					'<!--' . $comment->nodeValue . '-->'
				),
				'',
				$htmlSource
			);
		}

		if (! empty($commentsWithinQuotes)) {
			foreach ($commentsWithinQuotes as $commentQuote) {
				$htmlSource = str_replace($commentQuote['updated'], $commentQuote['original'], $htmlSource);
			}
		}

		return $htmlSource;
	}

	/**
	 * @param $e
	 *
	 * @return mixed
	 */
	public static function getOuterHTML($e)
	{
		$doc = new \DOMDocument();
		$doc->appendChild($doc->importNode($e, true));

		return trim($doc->saveHTML());
	}

	/**
	 * @param $strContains
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function cleanLinkTagFromHtmlSource($strContains, $htmlSource)
	{
		if ($strContains === '' || strlen($strContains) < 5) {
			return $htmlSource;
		}

		$strContainsFormat = preg_quote($strContains, '/');
		$regExpPattern = '#<link[^>]*stylesheet[^>]*'. $strContainsFormat. '.*(>)#Usmi';

		preg_match_all($regExpPattern, $htmlSource, $matchesSourcesFromTags, PREG_SET_ORDER);

		if (isset($matchesSourcesFromTags[0][0])) {
			$linkTag = $matchesSourcesFromTags[0][0];

			if (stripos($linkTag, '<link') === 0 && substr($linkTag, -1) === '>' && strip_tags($linkTag) === '') {
				$htmlSource = str_replace($matchesSourcesFromTags[0][0], '', $htmlSource);
			}
		}

		return $htmlSource;
	}

	/**
	 * @param $strContains
	 * @param $htmlSource
	 *
	 * @return mixed
	 */
	public static function cleanScriptTagFromHtmlSource($strContains, $htmlSource)
	{
		if ($strContains === '' || strlen($strContains) < 5) {
			return $htmlSource;
		}

		$strContainsFormat = preg_quote($strContains, '/');

		$regExpPattern = '#<script[^>]*src(|\s+)=(|\s+)[^>]*'. $strContainsFormat. '.*(>)#Usmi';

		preg_match_all($regExpPattern, $htmlSource, $matchesSourcesFromTags, PREG_SET_ORDER);

		if (isset($matchesSourcesFromTags[0][0])) {
			$htmlSource = str_replace($matchesSourcesFromTags[0][0].'</script>', '', $htmlSource);
		}

		return $htmlSource;
	}

	/**
	 *
	 */
	public function doDisableEmojis()
	{
		// Emojis Actions and Filters
		remove_action('admin_print_styles', 'print_emoji_styles');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('wp_print_styles', 'print_emoji_styles');

		remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
		remove_filter('the_content_feed', 'wp_staticize_emoji');
		remove_filter('comment_text_rss', 'wp_staticize_emoji');

		// TinyMCE Emojis
		add_filter('tiny_mce_plugins', array($this, 'removeEmojisTinymce'));

		add_filter('emoji_svg_url', '__return_false');
	}

	/**
	 * @param $plugins
	 *
	 * @return array
	 */
	public function removeEmojisTinymce($plugins)
	{
		if (is_array($plugins)) {
			return array_diff($plugins, array('wpemoji'));
		}

		return array();
	}

	/**
	 *
	 */
	public function doDisableOembed()
	{
		add_action('init', function() {
			// Remove the REST API endpoint.
			remove_action('rest_api_init', 'wp_oembed_register_route');

			// Turn off oEmbed auto discovery.
			add_filter('embed_oembed_discover', '__return_false');

			// Don't filter oEmbed results.
			remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

			// Remove oEmbed discovery links.
			remove_action('wp_head', 'wp_oembed_add_discovery_links');

			// Remove oEmbed-specific JavaScript from the front-end and back-end.
			remove_action('wp_head', 'wp_oembed_add_host_js');

			add_filter('tiny_mce_plugins', function ($plugins) {
				return array_diff($plugins, array('wpembed'));
			});

			// Remove all embeds rewrite rules.
			add_filter('rewrite_rules_array', function ($rules) {
				foreach($rules as $rule => $rewrite) {
					if (false !== strpos($rewrite, 'embed=true')) {
						unset($rules[$rule]);
					}
				}
				return $rules;
			});

			// Remove filter of the oEmbed result before any HTTP requests are made.
			remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
		}, 9999 );
	}

	/**
	 *
	 */
	public function cleanUpHtmlOutputForAssetsCall()
	{
		// WP Rocket (No Minify or Concatenate)
		add_filter('get_rocket_option_minify_css', '__return_false');
		add_filter('get_rocket_option_minify_concatenate_css', '__return_false');

		add_filter('get_rocket_option_minify_js', '__return_false');
		add_filter('get_rocket_option_minify_concatenate_js', '__return_false');

		// W3 Total Cache: No Minify
		add_filter('w3tc_minify_enable', '__return_false');

		// SG Optimizer Plugin
		$sgOptimizerMapping = array(
			'autoflush'            => 'siteground_optimizer_autoflush_cache',
			'dynamic-cache'        => 'siteground_optimizer_enable_cache',
			'memcache'             => 'siteground_optimizer_enable_memcached',
			'ssl-fix'              => 'siteground_optimizer_fix_insecure_content',
			'html'                 => 'siteground_optimizer_optimize_html',
			'js'                   => 'siteground_optimizer_optimize_javascript',
			'js-async'             => 'siteground_optimizer_optimize_javascript_async',
			'css'                  => 'siteground_optimizer_optimize_css',
			'combine-css'          => 'siteground_optimizer_combine_css',
			'querystring'          => 'siteground_optimizer_remove_query_strings',
			'emojis'               => 'siteground_optimizer_disable_emojis',
			'images'               => 'siteground_optimizer_optimize_images',
			'lazyload_images'      => 'siteground_optimizer_lazyload_images',
			'lazyload_gravatars'   => 'siteground_optimizer_lazyload_gravatars',
			'lazyload_thumbnails'  => 'siteground_optimizer_lazyload_thumbnails',
			'lazyload_responsive'  => 'siteground_optimizer_lazyload_responsive',
			'lazyload_textwidgets' => 'siteground_optimizer_lazyload_textwidgets',
			'ssl'                  => 'siteground_optimizer_ssl_enabled',
			'gzip'                 => 'siteground_optimizer_enable_gzip_compression',
			'browser-caching'      => 'siteground_optimizer_enable_browser_caching',
		);

		foreach ($sgOptimizerMapping as $optionName) {
			add_filter('pre_option_'.$optionName, '__return_false');
		}

		// Fallback in case SG Optimizer is triggered BEFORE Asset CleanUp and the filter above will not work
		add_filter('sgo_css_combine_exclude', array($this, 'allCssHandles'));
		add_filter('sgo_css_minify_exclude',  array($this, 'allCssHandles'));
		add_filter('sgo_js_minify_exclude',   array($this, 'allJsHandles'));
		add_filter('sgo_js_async_exclude',    array($this, 'allJsHandles'));

		add_filter('sgo_html_minify_exclude_params', function ($excludeParams) {
			$excludeParams[] = WPACU_LOAD_ASSETS_REQ_KEY;
			return $excludeParams;
		});
	}

	/**
	 * @return array
	 */
	public function allCssHandles()
	{
		global $wp_styles;

		$allCssHandles = array();

		if (isset($wp_styles->registered) && ! empty($wp_styles->registered)) {
			$allCssHandles = array_keys($wp_styles->registered);
		}

		return $allCssHandles;
	}

	/**
	 * @return array
	 */
	public function allJsHandles()
	{
		global $wp_scripts;

		$allJsHandles = array();

		if (isset($wp_scripts->registered) && ! empty($wp_scripts->registered)) {
			$allJsHandles = array_keys($wp_scripts->registered);
		}

		return $allJsHandles;
	}
}
