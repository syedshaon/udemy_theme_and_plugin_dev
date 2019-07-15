<?php
namespace WpAssetCleanUp;

/**
 * Class Plugin
 * @package WpAssetCleanUp
 */
class Plugin
{
	/**
	 *
	 */
	const RATE_URL = 'https://wordpress.org/support/plugin/wp-asset-clean-up/reviews/?filter=5#new-post';

	/**
	 * The functions below are only called within the Dashboard
	 *
	 * Plugin constructor.
	 */
	public function __construct()
	{
		register_activation_hook(WPACU_PLUGIN_FILE, array($this, 'whenActivated'));

		// After fist time activation or in specific situations within the Dashboard
		add_action('admin_init', array($this, 'adminInit'));

		// [wpacu_lite]
		// Admin footer text: Ask the user to review the plugin
		add_filter('admin_footer_text', array($this, 'adminFooter'), 1, 1);
		// [/wpacu_lite]

		// Show "Settings" and "Go Pro" as plugin action links
		add_filter('plugin_action_links_'.WPACU_PLUGIN_BASE, array($this, 'actionLinks'));

		// Languages
		add_action('plugins_loaded', array($this, 'loadTextDomain'));

		}

	/**
	 *
	 */
	public function loadTextDomain()
	{
		load_plugin_textdomain('wp-asset-clean-up',
			FALSE,
			basename(WPACU_PLUGIN_DIR) . '/languages/'
		);
	}

	// [wpacu_lite]
	/**
	 * @param $text
	 *
	 * @return string
	 */
	public function adminFooter($text)
	{
		if (isset($_GET['page']) && strpos($_GET['page'], WPACU_PLUGIN_ID) !== false) {
			$text = sprintf(__('Thank you for using %s', 'wp-asset-clean-up'), WPACU_PLUGIN_TITLE.' v'.WPACU_PLUGIN_VERSION)
			        . ' <span class="dashicons dashicons-smiley"></span> &nbsp;&nbsp;';

			$text .= sprintf(
				__('If you like it, please %s<strong>rate</strong> %s%s %s on WordPress.org to help me spread the word to the community.', 'wp-asset-clean-up'),
				'<a target="_blank" href="'.self::RATE_URL.'">',
				WPACU_PLUGIN_TITLE,
				'</a>',
				'<a target="_blank" href="'.self::RATE_URL.'"><span class="dashicons dashicons-wpacu dashicons-star-filled"></span><span class="dashicons dashicons-wpacu dashicons-star-filled"></span><span class="dashicons dashicons-wpacu dashicons-star-filled"></span><span class="dashicons dashicons-wpacu dashicons-star-filled"></span><span class="dashicons dashicons-wpacu dashicons-star-filled"></span></a>'
			);
		}

		return $text;
	}
	// [/wpacu_lite]

	/**
	 *
	 */
	public function whenActivated()
	{
		// Is the plugin activated for the first time?
		// Prepare for the redirection to the WPACU_ADMIN_PAGE_ID_START plugin page
		if (! get_transient(WPACU_PLUGIN_ID.'_do_activation_redirect_first_time')) {
			set_transient(WPACU_PLUGIN_ID.'_do_activation_redirect_first_time', 1);
			set_transient(WPACU_PLUGIN_ID . '_redirect_after_activation', 1, 15);
		}

		// Make a record when Asset CleanUp is used for the first time
		self::triggerFirstUsage();

		/**
		 * Note: Could be /wp-content/uploads/ if constant WPACU_CACHE_DIR was used
		 *
		 * /wp-content/cache/asset-cleanup/
		 * /wp-content/cache/asset-cleanup/index.php
		 * /wp-content/cache/asset-cleanup/.htaccess
		 *
		 * /wp-content/cache/asset-cleanup/css/
		 * /wp-content/cache/asset-cleanup/css/index.php
		 *
		 * /wp-content/cache/asset-cleanup/css/logged-in/
		 * /wp-content/cache/asset-cleanup/css/logged-in/index.php
		 *
		 * /wp-content/cache/asset-cleanup/css/min/
		 * /wp-content/cache/asset-cleanup/css/min/index.php
		 */
		self::createCacheFoldersFiles(array('css','js'));

		// Do not apply plugin's settings/rules on WooCommerce/EDD Checkout/Cart pages
		if (function_exists('wc_get_page_id')) {
			if ($wooCheckOutPageId = wc_get_page_id('checkout')) {
				Misc::doNotApplyOptimizationOnPage($wooCheckOutPageId);
			}

			if ($wooCartPageId = wc_get_page_id('cart')) {
				Misc::doNotApplyOptimizationOnPage($wooCartPageId);
			}
		}

		if (function_exists('edd_get_option') && $eddPurchasePage = edd_get_option('purchase_page', '')) {
			Misc::doNotApplyOptimizationOnPage($eddPurchasePage);
		}
	}

	/**
	 * @param $assetTypes
	 */
	public static function createCacheFoldersFiles($assetTypes)
	{
		foreach ($assetTypes as $assetType) {
			if ($assetType === 'css') {
				$cacheDir = WP_CONTENT_DIR . OptimiseAssets\OptimizeCss::getRelPathCssCacheDir();
			} elseif ($assetType === 'js') {
				$cacheDir = WP_CONTENT_DIR . OptimiseAssets\OptimizeJs::getRelPathJsCacheDir();
			} else {
				return;
			}

			$emptyPhpFileContents = <<<TEXT
<?php
// Silence is golden.
TEXT;

			$htAccessContents = <<<HTACCESS
<IfModule mod_autoindex.c>
Options -Indexes
</IfModule>
HTACCESS;

			if ( ! is_dir( $cacheDir ) ) {
				@mkdir( $cacheDir, 0755, true );
			}

			if ( ! is_file( $cacheDir . 'index.php' ) ) {
				// /wp-content/cache/asset-cleanup/cache/{$assetType}/index.php
				@file_put_contents( $cacheDir . 'index.php', $emptyPhpFileContents );
			}

			if ( ! is_dir( $cacheDir . 'logged-in' ) ) {
				@mkdir( $cacheDir . 'logged-in', 0755 );
			}

			if ( ! is_dir( $cacheDir . 'min' ) ) {
				@mkdir( $cacheDir . 'min', 0755 );
			}

			if ( ! is_file( $cacheDir . 'logged-in/index.php' ) ) {
				// /wp-content/cache/asset-cleanup/cache/{$assetType}/logged-in/index.html
				@file_put_contents( $cacheDir . 'logged-in/index.php', $emptyPhpFileContents );
			}

			$htAccessFilePath = dirname( $cacheDir ) . '/.htaccess';

			if ( ! is_file( $htAccessFilePath ) ) {
				// /wp-content/cache/asset-cleanup/.htaccess
				@file_put_contents( $htAccessFilePath, $htAccessContents );
			}

			if ( ! is_file( dirname( $cacheDir ) . '/index.php' ) ) {
				// /wp-content/cache/asset-cleanup/index.php
				@file_put_contents( dirname( $cacheDir ) . '/index.php', $emptyPhpFileContents );
			}
		}

		$storageDir = WP_CONTENT_DIR . OptimiseAssets\OptimizeCommon::getRelPathPluginCacheDir() . '_storage/';
		$siteStorageCache = $storageDir.'/'.str_replace(array('https://', 'http://', '//'), '', site_url());

		if ( ! is_dir($storageDir) ) {
			@mkdir( $siteStorageCache, 0755, true );
		}
	}

	/**
	 *
	 */
	public function adminInit()
	{
		if (strpos($_SERVER['REQUEST_URI'], '/plugins.php') !== false && get_transient(WPACU_PLUGIN_ID . '_redirect_after_activation')) {
			// Remove it as only one redirect is needed (first time the plugin is activated)
			delete_transient(WPACU_PLUGIN_ID . '_redirect_after_activation');
			
			// Do the 'first activation time' redirection
			wp_redirect(admin_url('admin.php?page=' . WPACU_ADMIN_PAGE_ID_START));
			exit();
		}

		$triggerFirstUsage = (strpos($_SERVER['REQUEST_URI'], '/plugins.php') !== false ||
		                      strpos($_SERVER['REQUEST_URI'], '/plugin-install.php') !== false ||
		                      strpos($_SERVER['REQUEST_URI'], '/options-general.php') !== false ||
		                      strpos($_SERVER['REQUEST_URI'], '/update-core.php') !== false);

		// No first usage timestamp set, yet? Set it now!
		if ($triggerFirstUsage) {
			self::triggerFirstUsage();
		}
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	public function actionLinks($links)
	{
		$links['getting_started'] = '<a href="admin.php?page=' . WPACU_PLUGIN_ID . '_getting_started">' . __('Getting Started', 'wp-asset-clean-up') . '</a>';
		$links['settings']        = '<a href="admin.php?page=' . WPACU_PLUGIN_ID . '_settings">'        . __('Settings', 'wp-asset-clean-up') . '</a>';

		// [wpacu_lite]
		$allPlugins = get_plugins();

		// If the Pro version is not installed (active or not), show the upgrade link
		if (! array_key_exists('wp-asset-clean-up-pro/wpacu.php', $allPlugins)) {
			$links['go_pro'] = '<a target="_blank" style="font-weight: bold;" href="'.WPACU_PLUGIN_GO_PRO_URL.'">'.__('Go Pro', 'wp-asset-clean-up').'</a>';
		}
		// [/wpacu_lite]

		return $links;
	}

	/**
	 * Make a record when Asset CleanUp is used for the first time (if it's not there already)
	 */
	public static function triggerFirstUsage()
	{
		// No first usage timestamp set, yet? Set it now!
		if (! get_option(WPACU_PLUGIN_ID.'_first_usage')) {
			Misc::addUpdateOption(WPACU_PLUGIN_ID . '_first_usage', time());
		}
	}

	}
