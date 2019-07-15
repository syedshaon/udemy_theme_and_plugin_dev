<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;
use WpAssetCleanUp\OptimiseAssets\OptimizeCss;
use WpAssetCleanUp\OptimiseAssets\OptimizeJs;

/**
 * Class Main
 * @package WpAssetCleanUp
 */
class Main
{
    /**
     *
     */
    const START_DEL = 'BEGIN WPACU PLUGIN JSON';

    /**
     *
     */
    const END_DEL = 'END WPACU PLUGIN JSON';

    /**
     * @var string
     * Can be managed in the Dashboard within the plugin's settings
     * e.g. 'direct', 'wp_remote_post'
     */
    public static $domGetType = 'direct';

	/**
	 * @var string
	 */
	public $assetsRemoved = '';

    /**
     * @var array
     */
    public $globalUnloaded = array();

    /**
     * @var array
     */
    public $loadExceptions = array('styles' => array(), 'scripts' => array());

    /**
     * @var
     */
    public $fetchUrl;

    // [wpacu_lite]
    /**
     * @var
     */
    public $isUpdateable = true;
	// [/wpacu_lite]

    /**
     * @var int
     */
    public $currentPostId = 0;

    /**
     * @var array
     */
    public $currentPost = array();

    /**
     * @var array
     */
    public $vars = array('woo_url_not_match' => false, 'is_woo_shop_page' => false);

    /**
     * This is set to `true` only if "Manage in the Front-end?" is enabled in plugin's settings
     * and the logged-in administrator with plugin activation privileges
     * is outside the Dashboard viewing the pages like a visitor
     *
     * @var bool
     */
    public $isFrontendEditView = false;

	/**
	 * @var array
	 */
	public $stylesInHead = array();

    /**
     * @var array
     */
    public $assetsInFooter = array('styles' => array(), 'scripts' => array());

    /**
     * @var array
     */
    public $wpAllScripts = array();

    /**
     * @var array
     */
    public $wpAllStyles = array();

	/**
	 * @var array
	 */
	public $ignoreChildren = array();

	/**
	 * @var array
	 */
	public $ignoreChildrenHandlesOnTheFly = array();

	/**
	 * @var int
	 */
	/*
	public $lastScriptPos = 1;
    */

	/**
	 * @var int
	 */
	/*
	public $lastStylePos = 1;
	*/

	/**
	 * @var int
	 */
	public static $wpStylesSpecialDelimiters = array(
        'start' => '<!--START-WPACU-SPECIAL-STYLES',
        'end'   => 'END-WPACU-SPECIAL-STYLES-->'
    );

    /**
     * @var array
     */
    public $postTypesUnloaded = array();

	/**
	 * @var array
	 */
	public $settings = array();

	/**
	 * @var bool
	 */
	public $isAjaxCall = false;

	/**
     * Fetch CSS/JS list from the Dashboard
     *
	 * @var bool
	 */
	public $isGetAssetsCall = false;

	/**
	 * Populated in the Parser constructor
	 *
	 * @var array
	 */
	public $skipAssets = array('styles' => array(), 'scripts' => array());

    /**
     * @var Main|null
     */
    private static $singleton;

    /**
     * @return null|Main
     */
    public static function instance()
    {
        if (self::$singleton === null) {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

    /**
     * Parser constructor.
     */
    public function __construct()
    {
	    $this->skipAssets['styles'] = array(
		    WPACU_PLUGIN_ID . '-style', // Asset CleanUp Styling (for admin use only)
		    'admin-bar',                // The top admin bar
		    'yoast-seo-adminbar',       // Yoast "WordPress SEO" plugin
		    'autoptimize-toolbar',
		    'query-monitor',
            'wp-fastest-cache-toolbar', // WP Fastest Cache plugin toolbar CSS
            'litespeed-cache' // LiteSpeed toolbar
	    );

	    $this->skipAssets['scripts'] = array(
		    WPACU_PLUGIN_ID . '-script', // Asset CleanUp Script (for admin use only)
		    'admin-bar',                 // The top admin bar
		    'autoptimize-toolbar',
		    'query-monitor',
            'wpfc-toolbar' // WP Fastest Cache plugin toolbar JS
	    );

	    $this->isAjaxCall      = (! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
	    $this->isGetAssetsCall = isset($_REQUEST[WPACU_LOAD_ASSETS_REQ_KEY]) && $_REQUEST[WPACU_LOAD_ASSETS_REQ_KEY];

        // Early Triggers
        add_action('wp', array($this, 'setVarsBeforeUpdate'), 8);
        add_action('wp', array($this, 'setVarsAfterAnyUpdate'), 10);

	    // Fetch Assets AJAX Call? Make sure the output is as clean as possible (no plugins interfering with it)
	    if ($this->isGetAssetsCall) {
		    $wpacuCleanUp = new CleanUp();
		    $wpacuCleanUp->cleanUpHtmlOutputForAssetsCall();
	    }

	    // "Direct" AJAX call or "WP Remote Post" method used?
	    // Do not trigger the admin bar as it's not relevant
	    if ($this->isAjaxCall || $this->isGetAssetsCall) {
		    Misc::noAdminBarLoad();
	    }

	    // This is triggered AFTER "saveSettings" from 'Settings' class
	    // In case the settings were just updated, the script will get the latest values
	    add_action('init', array($this, 'triggersAfterInit'), 10);

        // Front-end View - Unload the assets
        // If there are reasons to prevent the unloading in case 'test mode' is enabled,
	    // then the prevention will trigger within filterStyles() and filterScripts()

	    if (! $this->isGetAssetsCall && ! is_admin()) { // No AJAX call from the Dashboard? Trigger the code below
	        // [START] Unload CSS/JS on URI Request (for debugging)
            add_filter('wpacu_filter_styles', array($this, 'filterCssOnTheFly'));
            add_filter('wpacu_filter_scripts', array($this, 'filterJsOnTheFly'));
            add_filter('wpacu_ignore_child_parent_list', array($this, 'filterIgnoreChildParentList'));
            // [END] Unload CSS/JS on URI Request (for debugging)

		    // SG Optimizer Compatibility: Unload Styles - HEAD (Before pre_combine_header_styles() from Combinator)
		    if (get_option('siteground_optimizer_combine_css')) {
			    add_action('wp_print_styles', array($this, 'filterStyles'), 9); // priority should be below 10
		    }

		    // Unload Styles - HEAD
		    add_action( 'wp_print_styles', array( $this, 'filterStyles' ), 100000 );
		    $this->filterStylesSpecialCases(); // e.g. CSS enqueued in a different way via Oxygen Builder

		    // Unload Scripts - HEAD
		    add_action( 'wp_print_scripts', array( $this, 'filterScripts' ), 100000 );

		    // Unload Scripts & Styles - FOOTER
		    // Needs to be triggered very soon as some old plugins/themes use wp_footer() to enqueue scripts
		    // Sometimes styles are loaded in the BODY section of the page
		    add_action( 'wp_print_footer_scripts', array( $this, 'filterScripts' ), 1 );
		    add_action( 'wp_print_footer_scripts', array( $this, 'filterStyles' ), 1 );

		    // Preloads
		    add_action('wp_head', function() {
			    echo Preloads::DEL_STYLES_PRELOADS . Preloads::DEL_SCRIPTS_PRELOADS;
		    }, 1);

            Preloads::instance()->init();

		    }

	    // Only trigger it within the Dashboard when an Asset CleanUp page is accessed and the transient is non-existent or expired
	    if (is_admin()) {
		    add_action('admin_footer', array($this, 'ajaxFetchActivePluginsJsFooterCode'));
		    add_action('wp_ajax_' . WPACU_PLUGIN_ID . '_fetch_active_plugins_icons', array($this, 'ajaxFetchActivePluginsIcons'));
	    }

	    $this->wpacuHtmlNoticeForAdmin();
    }

	/**
	 *
	 */
	public function triggersAfterInit()
    {
	    $wpacuSettingsClass = new Settings();
	    $this->settings = $wpacuSettingsClass->getAll();

	    if ($this->settings['dashboard_show'] && $this->settings['dom_get_type']) {
		    self::$domGetType = $this->settings['dom_get_type'];
	    }

	    // Fetch the page in the background to see what scripts/styles are already loading
	    if ($this->isGetAssetsCall || $this->frontendShow()) {
		    if ($this->isGetAssetsCall) {
			    Misc::noAdminBarLoad();
		    }

		    // Save CSS list that is printed in the <HEAD>
		    add_action('wp_head',   array($this, 'saveFooterStyles'), PHP_INT_MAX - 1);

		    // Save CSS/JS list that is printed in the <BODY>
		    add_action('wp_footer', array($this, 'saveFooterScripts'), 100000000);
		    add_action('wp_footer', array($this, 'printScriptsStyles'), PHP_INT_MAX);
	    }

	    if ( is_admin() ) {
		    $metaboxes = new MetaBoxes;

		    // Do not load the meta box nor do any AJAX calls
		    // if the asset management is not enabled for the Dashboard
		    if ($this->settings['dashboard_show'] == 1) {
			    // Send an AJAX request to get the list of loaded scripts and styles and print it nicely
			    add_action(
				    'wp_ajax_' . WPACU_PLUGIN_ID . '_get_loaded_assets',
				    array( $this, 'ajaxGetJsonListCallback' )
			    );
		    }

		    // If assets management within the Dashboard is not enabled, an explanation message will be shown within the box
            // unless the meta box is hidden completely
		    if (! $this->settings['hide_assets_meta_box']) {
			    $metaboxes->initManagerMetaBox();
		    }

		    // Side Meta Box: Asset CleanUp Options
            // check if it's not hidden completely
		    if (! $this->settings['hide_options_meta_box']) {
			    $metaboxes->initCustomOptionsMetaBox();
		    }
	    }

	    /*
		   DO NOT disable the features below if the following apply:
		   - The option is not enabled
		   - Test Mode Enabled & Admin Logged in
		   - The user is in the Dashboard (any changes are applied in the front-end view)
		*/
	    if ( (! ($this->preventUnloadAssets() || is_admin())) ) {
		    if ($this->settings['disable_emojis'] == 1) {
			    $wpacuCleanUp = new CleanUp();
			    $wpacuCleanUp->doDisableEmojis();
		    }

		    if ($this->settings['disable_oembed'] == 1) {
			    $wpacuCleanUp = new CleanUp();
			    $wpacuCleanUp->doDisableOembed();
		    }
	    }
    }

    /**
     * Priority: 8 (earliest)
     */
    public function setVarsBeforeUpdate()
    {
        // Conditions
        // 1) User has rights to manage the assets and the option is enabled in plugin's Settings
        // 2) Not an AJAX call from the Dashboard
	    // 3) Not inside the Dashboard
        $this->isFrontendEditView = ($this->frontendShow() && Menu::userCanManageAssets() // 1
                                      && !$this->isGetAssetsCall // 2
                                      && !is_admin()); // 3

        if ($this->isFrontendEditView) {
	        $wpacuCleanUp = new CleanUp();
	        $wpacuCleanUp->cleanUpHtmlOutputForAssetsCall();
        }

        $this->getCurrentPostId();

	    define('WPACU_CURRENT_PAGE_ID', $this->getCurrentPostId());
    }

    /**
     * Priority: 10 (latest)
     */
    public function setVarsAfterAnyUpdate()
    {
        if (! $this->isGetAssetsCall && ! is_admin()) {
            $this->globalUnloaded = $this->getGlobalUnload();

	        // [wpacu_lite]
            if (! $this->isUpdateable) {
                return;
            }
	        // [/wpacu_lite]

            $getCurrentPost = $this->getCurrentPost();

            if (Misc::isHomePage()) {
            	$type = 'front_page';
            } elseif ( ! empty($getCurrentPost) )  {
            	$type = 'post';
	            $post = $getCurrentPost;
	            $this->postTypesUnloaded = $this->getBulkUnload('post_type', $post->post_type);
            }

            else {
            	// The request is done for a page such as is_archive(), is_author(), 404, search
	            // and the premium extension is not available, thus no load exceptions are available
            	return;
            }

            $this->loadExceptions = $this->getLoadExceptions($type, $this->currentPostId);
        }
    }

    /**
     * See if there is any list with scripts to be removed in JSON format
     * Only the handles (the ID of the scripts) are saved
     */
    public function filterScripts()
    {
        if (is_admin()) {
            return;
        }

	    // [wpacu_lite]
        $nonAssetConfigPage = (! $this->isUpdateable && ! Misc::getShowOnFront());
		// [/wpacu_lite]

        // It looks like the page loaded is neither a post, page or the front-page
        // We'll see if there are assets unloaded globally and unload them
        $globalUnload = $this->globalUnloaded;

        // [wpacu_lite]
	    if ($nonAssetConfigPage && ! empty($globalUnload['scripts'])) {
            $list = $globalUnload['scripts'];
        } else { // [/wpacu_lite]
		    // Post, Page or Front-page?
            $toRemove = $this->getAssetsUnloaded();

            $jsonList = @json_decode($toRemove);

            $list = array();

            if (isset($jsonList->scripts)) {
                $list = (array)$jsonList->scripts;
            }

            // Any global unloaded styles? Append them
            if (! empty($globalUnload['scripts'])) {
                foreach ($globalUnload['scripts'] as $handleScript) {
                    $list[] = $handleScript;
                }
            }

            if ($this->isSingularPage()) {
                // Any bulk unloaded styles (e.g. for all pages belonging to a post type)? Append them
                if (empty($this->postTypesUnloaded)) {
                    $post = $this->getCurrentPost();
                    $this->postTypesUnloaded = $this->getBulkUnload('post_type', $post->post_type);
                }

                if (!empty($this->postTypesUnloaded['scripts'])) {
                    foreach ($this->postTypesUnloaded['scripts'] as $handleStyle) {
                        $list[] = $handleStyle;
                    }
                }
            }
        // [wpacu_lite]
	    }
		// [/wpacu_lite]

	    $list = apply_filters('wpacu_filter_scripts', array_unique($list));

        // Let's see if there are load exceptions for this page
        if (! empty($list) && ! empty($this->loadExceptions['scripts'])) {
            foreach ($list as $handleKey => $handle) {
                if (in_array($handle, $this->loadExceptions['scripts'])) {
                    unset($list[$handleKey]);
                }
            }
        }

        global $wp_scripts;

        $allScripts = $wp_scripts;

        if ($allScripts !== null && ! empty($allScripts->registered)) {
	        foreach ($allScripts->registered as $handle => $value) {
	            // This could be triggered several times, check if the script already exists
                if (! isset($this->wpAllScripts['registered'][$handle])) {
	                $this->wpAllScripts['registered'][$handle] = $value;
	                if (in_array($handle, $allScripts->queue)) {
		                $this->wpAllScripts['queue'][] = $handle;
	                }
                }
            }

	        if (isset($this->wpAllScripts['queue']) && ! empty($this->wpAllScripts['queue'])) {
		        $this->wpAllScripts['queue'] = array_unique( $this->wpAllScripts['queue'] );
	        }
        }

	    // Nothing to unload
	    if (empty($list)) {
		    return;
	    }

	    // e.g. for test mode or AJAX calls (where all assets have to load)
	    if ($this->preventUnloadAssets() === true) {
		    return;
	    }

	    $ignoreChildParentList = apply_filters('wpacu_ignore_child_parent_list', $this->getIgnoreChildren());

	    foreach ($list as $handle) {
            $handle = trim($handle);

            // Special Action for 'jquery-migrate' handler as its tied to 'jquery'
            if ($handle === 'jquery-migrate' && isset($this->wpAllScripts['registered']['jquery'])) {
	            $jQueryRegScript = $this->wpAllScripts['registered']['jquery'];

	            if (isset($jQueryRegScript->deps)) {
		            $jQueryRegScript->deps = array_diff($jQueryRegScript->deps, array('jquery-migrate'));
	            }

	            if (Misc::isPluginActive('jquery-updater/jquery-updater.php')) {
		            wp_dequeue_script($handle);
                }

	            continue;
            }

	        if (isset($ignoreChildParentList['scripts'], $this->wpAllScripts['registered'][$handle]->src) && is_array($ignoreChildParentList['scripts']) && array_key_exists($handle, $ignoreChildParentList['scripts'])) {
		        // Do not dequeue it as it's "children" will also be dequeued (ignore rule is applied)
		        // It will be stripped by cleaning its SCRIPT tag from the HTML Source
                $this->ignoreChildren['scripts'][$handle] = $this->wpAllScripts['registered'][$handle]->src;
		        continue;
	        }

            wp_deregister_script($handle);
            wp_dequeue_script($handle);
        }
    }

    /**
     * See if there is any list with styles to be removed in JSON format
     * Only the handles (the ID of the styles) is stored
     */
    public function filterStyles()
    {
        if (is_admin()) {
            return;
        }

	    // [wpacu_lite]
        $nonAssetConfigPage = (! $this->isUpdateable && ! Misc::getShowOnFront());
		// [/wpacu_lite]

        // It looks like the page loaded is neither a post, page or the front-page
        // We'll see if there are assets unloaded globally and unload them
        $globalUnload = $this->globalUnloaded;

	    // [wpacu_lite]
        if ($nonAssetConfigPage && ! empty($globalUnload['styles'])) {
            $list = $globalUnload['styles'];
        } else { // [/wpacu_lite]
            // Post, Page, Front-page
            // and more (if the Premium Extension is activated)
            $toRemove = $this->getAssetsUnloaded();

            $jsonList = @json_decode($toRemove);

            $list = array();

            if (isset($jsonList->styles)) {
                $list = (array)$jsonList->styles;
            }

            // Any global unloaded styles? Append them
            if (! empty($globalUnload['styles'])) {
                foreach ($globalUnload['styles'] as $handleStyle) {
                    $list[] = $handleStyle;
                }
            }

            if ($this->isSingularPage()) {
                // Any bulk unloaded styles (e.g. for all pages belonging to a post type)? Append them
                if (empty($this->postTypesUnloaded)) {
                    $post = $this->getCurrentPost();
                    $this->postTypesUnloaded = $this->getBulkUnload('post_type', $post->post_type);
                }

                if (!empty($this->postTypesUnloaded['styles'])) {
                    foreach ($this->postTypesUnloaded['styles'] as $handleStyle) {
                        $list[] = $handleStyle;
                    }
                }
            }
        // [wpacu_lite]
        }
	    // [/wpacu_lite]

        // Site-Wide Unload for "Dashicons" if user is not logged-in
        if ($this->settings['disable_dashicons_for_guests'] && ! is_user_logged_in()) {
            $list[] = 'dashicons';
        }

	    // Any bulk unloaded styles for 'category', 'post_tag' and more?
	    // If the premium extension is enabled, any of the unloaded CSS will be added to the list
	    $list = apply_filters('wpacu_filter_styles', array_unique($list));

        // Let's see if there are load exceptions for this page
        if (! empty($list) && ! empty($this->loadExceptions['styles'])) {
            foreach ($list as $handleKey => $handle) {
                if (in_array($handle, $this->loadExceptions['styles'])) {
                    unset($list[$handleKey]);
                }
            }
        }

        global $wp_styles;

        // Add handles such as the  Oxygen Builder CSS ones that are missing and added differently to the queue
        $allStyles = $this->wpStylesFilter($wp_styles, 'registered', $list);

	    if ($allStyles !== null && ! empty($allStyles->registered)) {
		    foreach ($allStyles->registered as $handle => $value) {
			    // This could be triggered several times, check if the style already exists
			    if (! isset($this->wpAllStyles['registered'][$handle])) {
				    $this->wpAllStyles['registered'][$handle] = $value;
				    if (in_array($handle, $allStyles->queue)) {
					    $this->wpAllStyles['queue'][] = $handle;
				    }
			    }
		    }

		    if (isset($this->wpAllStyles['queue']) && ! empty($this->wpAllStyles['queue'])) {
			    $this->wpAllStyles['queue'] = array_unique( $this->wpAllStyles['queue'] );
		    }
	    }

	    // e.g. for test mode or AJAX calls (where all assets have to load)
	    if ($this->preventUnloadAssets() === true) {
	    	return;
	    }

	    if (isset($this->wpAllStyles['registered']) && ! empty($this->wpAllStyles['registered'])) {
		    wp_cache_set('wpacu_all_styles_handles', array_keys($this->wpAllStyles['registered']));
	    }

	    // Nothing to unload?
	    if (empty($list)) {
		    return;
	    }

	    $ignoreChildParentList = apply_filters('wpacu_ignore_child_parent_list', $this->getIgnoreChildren());

	    foreach ($list as $handle) {
	        if (isset($ignoreChildParentList['styles'], $this->wpAllStyles['registered'][$handle]->src) && is_array($ignoreChildParentList['styles']) && array_key_exists($handle, $ignoreChildParentList['styles'])) {
	            // Do not dequeue it as it's "children" will also be dequeued (ignore rule is applied)
                // It will be stripped by cleaning its LINK tag from the HTML Source
                $this->ignoreChildren['styles'][$handle] = $this->wpAllStyles['registered'][$handle]->src;
	            continue;
            }

            $handle = trim($handle);

            wp_deregister_style($handle);
            wp_dequeue_style($handle);
        }
    }

	/**
	 * @param $wpStylesFilter
	 * @param string $listType
     * @param array $unloadedList
	 *
	 * @return mixed
	 */
	public function wpStylesFilter($wpStylesFilter, $listType, $unloadedList = array())
    {
        global $wp_styles, $oxygen_vsb_css_styles;

        if ($listType === 'registered') {
	        if (isset($oxygen_vsb_css_styles->registered) && is_object($oxygen_vsb_css_styles) && ! empty($oxygen_vsb_css_styles->registered)) {
	            $stylesSpecialCases = array();

		        foreach ($oxygen_vsb_css_styles->registered as $oxygenHandle => $oxygenValue) {
			        if (! array_key_exists($oxygenHandle, $wp_styles->registered)) {
				        $wpStylesFilter->registered[$oxygenHandle] = $oxygenValue;
				        $stylesSpecialCases[$oxygenHandle] = $oxygenValue->src;
			        }
		        }

		        $unloadedSpecialCases = array();

		        foreach ($unloadedList as $unloadedHandle) {
		            if (array_key_exists($unloadedHandle, $stylesSpecialCases)) {
			            $unloadedSpecialCases[$unloadedHandle] = $stylesSpecialCases[$unloadedHandle];
                    }
                }

		        if (! empty($unloadedSpecialCases)) {
			        // This will be later used in 'wp_loaded' below to extract the special styles
			        echo self::$wpStylesSpecialDelimiters['start'] . json_encode($unloadedSpecialCases) . self::$wpStylesSpecialDelimiters['end'];
		        }
	        }
        }

        if ($listType === 'done') {
	        if (isset($oxygen_vsb_css_styles->done) && is_object($oxygen_vsb_css_styles)) {
		        foreach ($oxygen_vsb_css_styles->done as $oxygenHandle) {
			        if (! in_array($oxygenHandle, $wp_styles->done)) {
				        $wpStylesFilter[] = $oxygenHandle;
			        }
		        }
	        }
        }

        if ($listType === 'queue') {
	        if (isset($oxygen_vsb_css_styles->queue) && is_object($oxygen_vsb_css_styles)) {
		        foreach ($oxygen_vsb_css_styles->queue as $oxygenHandle) {
			        if (! in_array($oxygenHandle, $wp_styles->queue)) {
				        $wpStylesFilter[] = $oxygenHandle;
			        }
		        }
	        }
        }

	    return $wpStylesFilter;
    }

	/**
	 *
	 */
	public function filterStylesSpecialCases()
    {
        add_action('wp_loaded', function() {
	        ob_start(function($htmlSource) {
	            if (strpos($htmlSource, self::$wpStylesSpecialDelimiters['start']) === false && strpos($htmlSource, self::$wpStylesSpecialDelimiters['end']) === false) {
	                return $htmlSource;
                }

	            $jsonStylesSpecialCases = Misc::extractBetween($htmlSource, self::$wpStylesSpecialDelimiters['start'], self::$wpStylesSpecialDelimiters['end']);

		        $stylesSpecialCases = json_decode($jsonStylesSpecialCases, ARRAY_A);

	            if (Misc::jsonLastError() === JSON_ERROR_NONE && ! empty($stylesSpecialCases)) {
	                foreach ($stylesSpecialCases as $styleHandle => $styleSrc) {
	                    $styleLocalSrc = Misc::getLocalSrc($styleSrc);
	                    $styleRelSrc = isset($styleLocalSrc['rel_src']) ? $styleLocalSrc['rel_src'] : $styleSrc;
	                    $htmlSource = CleanUp::cleanLinkTagFromHtmlSource($styleRelSrc, $htmlSource);
                    }

	                // Strip the info HTML comment
	                $htmlSource = str_replace(
                        self::$wpStylesSpecialDelimiters['start'] . $jsonStylesSpecialCases . self::$wpStylesSpecialDelimiters['end'],
                        '',
                        $htmlSource
                    );
                }

		        return $htmlSource;
	        });
        }, 1);
    }

	/**
     * Alter CSS list marked for dequeue
     *
	 * @param $cssHandles
	 *
	 * @return mixed
	 */
	public function filterCssOnTheFly($cssHandles)
    {
        if (isset($_GET['wpacu_unload_css']) && $_GET['wpacu_unload_css']) {
            $unloadCss = $_GET['wpacu_unload_css'];

            if (strpos($unloadCss, ',') === false) {
	            if (strpos($unloadCss, '[ignore-deps]') === false) {
		            $unloadCss = str_replace('[ignore-deps]', '', $unloadCss);
		            $this->ignoreChildrenHandlesOnTheFly['styles'][] = $unloadCss;
	            }

	            $cssHandles[] = $unloadCss;
            } else {
                $unloadCssList = explode(',', $unloadCss);

                foreach ($unloadCssList as $unloadCss) {
                    $unloadCss = trim($unloadCss);

                    if ($unloadCss) {
	                    if (strpos($unloadCss, '[ignore-deps]') === false) {
		                    $unloadCss = str_replace('[ignore-deps]', '', $unloadCss);
		                    $this->ignoreChildrenHandlesOnTheFly['styles'][] = $unloadCss;
	                    }

	                    $cssHandles[] = $unloadCss;
                    }
                }
            }
        }

        return $cssHandles;
    }

	/**
     * Alter JS list marked for dequeue
     *
	 * @param $jsHandles
	 *
	 * @return mixed
	 */
	public function filterJsOnTheFly($jsHandles)
	{
		if (isset($_GET['wpacu_unload_js']) && $_GET['wpacu_unload_js']) {
			$unloadJs = $_GET['wpacu_unload_js'];

			if (strpos($unloadJs, ',') === false) {
			    if (strpos($unloadJs, '[ignore-deps]') !== false) {
				    $unloadJs = str_replace('[ignore-deps]', '', $unloadJs);
				    $this->ignoreChildrenHandlesOnTheFly['scripts'][] = $unloadJs;
                }

				$jsHandles[] = $unloadJs;
			} else {
				$unloadCssList = explode(',', $unloadJs);

				foreach ($unloadCssList as $unloadJs) {
					$unloadJs = trim($unloadJs);

					if ($unloadJs) {
						if (strpos($unloadJs, '[ignore-deps]') !== false) {
							$unloadJs = str_replace('[ignore-deps]', '', $unloadJs);
							$this->ignoreChildrenHandlesOnTheFly['scripts'][] = $unloadJs;
						}

						$jsHandles[] = $unloadJs;
					}
				}
			}
		}

        return $jsHandles;
	}

    /**
     * @param string $type
     * @param string $postId
     * @return array|mixed|object
     */
    public function getLoadExceptions($type = 'post', $postId = '')
    {
        $exceptionsListDefault = $exceptionsList = $this->loadExceptions;

        if ($type === 'post' && !$postId) {
            // $postId needs to have a value if $type is a 'post' type
            return $exceptionsListDefault;
        }

        if (! $type) {
            // Invalid request
            return $exceptionsListDefault;
        }

        // Default
        $exceptionsListJson = '';

        $homepageClass = new AssetsPagesManager;

        // Post or Post of the Homepage (if chosen in the Dashboard)
        if ($type === 'post'
            || ($homepageClass->data['show_on_front'] === 'page' && $postId)
        ) {
            $exceptionsListJson = get_post_meta(
                $postId, '_' . WPACU_PLUGIN_ID . '_load_exceptions',
                true
            );
        } elseif ($type === 'front_page') {
            // The home page could also be the list of the latest blog posts
            $exceptionsListJson = get_option(
	            WPACU_PLUGIN_ID . '_front_page_load_exceptions'
            );
        }

	    if ($exceptionsListJson) {
            $exceptionsList = json_decode($exceptionsListJson, true);

            if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
                $exceptionsList = $exceptionsListDefault;
            }
        }

	    return $exceptionsList;
    }

    /**
     * @return array
     */
    public function getGlobalUnload()
    {
        $existingListEmpty = array('styles' => array(), 'scripts' => array());
        $existingListJson  = get_option( WPACU_PLUGIN_ID . '_global_unload');

        $existingListData = $this->existingList($existingListJson, $existingListEmpty);

        return $existingListData['list'];
    }

	/**
	 * @param string $for (could be 'post_type', 'taxonomy' for premium extension etc.)
	 * @param string $type
	 *
	 * @return array
	 */
	public function getBulkUnload($for, $type = 'all')
    {
        $existingListEmpty = array('styles' => array(), 'scripts' => array());

        $existingListAllJson = get_option( WPACU_PLUGIN_ID . '_bulk_unload');

        if (! $existingListAllJson) {
            return $existingListEmpty;
        }

        $existingListAll = json_decode($existingListAllJson, true);

        if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
            return $existingListEmpty;
        }

        $existingList = $existingListEmpty;

        if (isset($existingListAll['styles'][$for][$type])
            && is_array($existingListAll['styles'][$for][$type])) {
            $existingList['styles'] = $existingListAll['styles'][$for][$type];
        }

        if (isset($existingListAll['scripts'][$for][$type])
            && is_array($existingListAll['scripts'][$for][$type])) {
            $existingList['scripts'] = $existingListAll['scripts'][$for][$type];
        }

        return $existingList;
    }

	/**
	 * @return array
	 */
	public function getHandleNotes()
	{
		$handleNotes = array('styles' => array(), 'scripts' => array());

		$handleNotesListJson = get_option(WPACU_PLUGIN_ID . '_global_data');

		if ($handleNotesListJson) {
			$handleNotesList = @json_decode($handleNotesListJson, true);

			// Issues with decoding the JSON file? Return an empty list
			if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
				return $handleNotes;
			}

			// Are new positions set for styles and scripts?
			foreach (array('styles', 'scripts') as $assetKey) {
				if ( isset( $handleNotesList[$assetKey]['notes'] ) && ! empty( $handleNotesList[$assetKey]['notes'] ) ) {
					$handleNotes[$assetKey] = $handleNotesList[$assetKey]['notes'];
				}
			}
		}

		return $handleNotes;
	}

	/**
	 * @return array
	 */
	public function getIgnoreChildren()
	{
	    if (empty($this->ignoreChildren)) {
		    $ignoreChildListJson = get_option(WPACU_PLUGIN_ID . '_global_data');

		    if ($ignoreChildListJson) {
			    $ignoreChildList = @json_decode($ignoreChildListJson, true);

			    // Issues with decoding the JSON file? Return an empty list
			    if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
				    return $this->ignoreChildren;
			    }

			    // Are ignore "children" rules set for styles and scripts?
			    foreach (array('styles', 'scripts') as $assetKey) {
				    if (isset($ignoreChildList[$assetKey]['ignore_child']) && $ignoreChildList[$assetKey]['ignore_child']) {
					    $this->ignoreChildren[$assetKey] = $ignoreChildList[$assetKey]['ignore_child'];
				    }
			    }
		    }
	    }

		return $this->ignoreChildren;
	}

	/**
	 * @param $ignoreChildParentList
	 *
	 * @return mixed
	 */
	public function filterIgnoreChildParentList($ignoreChildParentList)
	{
		if (isset($this->ignoreChildrenHandlesOnTheFly['styles']) && ! empty($this->ignoreChildrenHandlesOnTheFly['styles'])) {
			foreach ($this->ignoreChildrenHandlesOnTheFly['styles'] as $cssHandle) {
				$ignoreChildParentList['styles'][$cssHandle] = 1;
			}
		}

		if (isset($this->ignoreChildrenHandlesOnTheFly['scripts']) && ! empty($this->ignoreChildrenHandlesOnTheFly['scripts'])) {
			foreach ($this->ignoreChildrenHandlesOnTheFly['scripts'] as $jsHandle) {
				$ignoreChildParentList['scripts'][$jsHandle] = 1;
			}
		}

		return $ignoreChildParentList;
	}

	/**
	 *
	 */
	public function saveFooterStyles()
	{
		global $wp_styles;

		if (isset($this->wpAllStyles['queue']) && ! empty($this->wpAllStyles['queue'])) {
			$this->stylesInHead = $this->wpAllStyles['queue'];
		}

		if (isset($wp_styles->queue) && ! empty($wp_styles->queue)) {
			foreach ($wp_styles->queue as $styleHandle) {
				$this->stylesInHead[] = $styleHandle;
			}
		}

		$this->stylesInHead = array_unique($this->stylesInHead);
	}

	/**
	 *
	 */
	public function saveFooterScripts()
	{
		global $wp_scripts, $wp_styles;
		$this->assetsInFooter['scripts'] = (isset($wp_scripts->in_footer) && ! empty($wp_scripts->in_footer)) ? $wp_scripts->in_footer : array();

		$footerStyles = array();

		if (isset($this->wpAllStyles['queue']) && ! empty($this->wpAllStyles['queue'])) {
			foreach ( $this->wpAllStyles['queue'] as $handle ) {
				if ( ! in_array( $handle, $this->stylesInHead ) ) {
					$footerStyles[] = $handle;
				}
			}
		}

		if (isset($wp_styles->queue) && ! empty($wp_styles->queue)) {
			foreach ( $wp_styles->queue as $handle ) {
				if ( ! in_array( $handle, $this->stylesInHead ) ) {
					$footerStyles[] = $handle;
				}
			}
		}

		$this->assetsInFooter['styles'] = $footerStyles;

		}

    /**
     * This output will be extracted and the JSON will be processed
     * in the WP Dashboard when editing a post
     *
     * It will also print the asset list in the front-end
     * if the option was enabled in the Settings
     */
    public function printScriptsStyles()
    {
    	// Not for WordPress AJAX calls
        if (self::$domGetType === 'direct' && defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        $isFrontEndEditView = $this->isFrontendEditView;
        $isDashboardEditView = (!$isFrontEndEditView && $this->isGetAssetsCall);

        if (!$isFrontEndEditView && !$isDashboardEditView) {
            return;
        }

        if ($isFrontEndEditView && array_key_exists('elementor-preview', $_GET) && $_GET['elementor-preview']) {
	        return;
        }

        // Prevent plugins from altering the DOM
        add_filter('w3tc_minify_enable', '__return_false');

        // This is the list of the scripts an styles that were eventually loaded
        // We have also the list of the ones that were unloaded
        // located in $this->wpScripts and $this->wpStyles
        // We will add it to the list as they will be marked

        $stylesBeforeUnload = $this->wpAllStyles;
        $scriptsBeforeUnload = $this->wpAllScripts;

        global $wp_scripts, $wp_styles;

        $list = array();

        $currentUnloadedAll = $currentUnloaded = (array)json_decode(
            $this->getAssetsUnloaded($this->getCurrentPostId())
        );

        // Append global unloaded assets to current (one by one) unloaded ones
        if (! empty($this->globalUnloaded['styles'])) {
            foreach ($this->globalUnloaded['styles'] as $globalStyle) {
                $currentUnloadedAll['styles'][] = $globalStyle;
            }
        }

        if (! empty($this->globalUnloaded['scripts'])) {
            foreach ($this->globalUnloaded['scripts'] as $globalScript) {
                $currentUnloadedAll['scripts'][] = $globalScript;
            }
        }

        // Append bulk unloaded assets to current (one by one) unloaded ones
        if ($this->isSingularPage()) {
            if (! empty($this->postTypesUnloaded['styles'])) {
                foreach ($this->postTypesUnloaded['styles'] as $postTypeStyle) {
                    $currentUnloadedAll['styles'][] = $postTypeStyle;
                }
            }

            if (! empty($this->postTypesUnloaded['scripts'])) {
                foreach ($this->postTypesUnloaded['scripts'] as $postTypeScript) {
                    $currentUnloadedAll['scripts'][] = $postTypeScript;
                }
            }
        }

	    $manageStylesCore = $wp_styles->done;
	    $manageStyles     = $this->wpStylesFilter($wp_styles->done, 'done');

	    $manageScripts    = $wp_scripts->done;

	    if ($isFrontEndEditView) {
	    	if (! empty($this->wpAllStyles) && isset($this->wpAllStyles['queue'])) {
			    $manageStyles = $this->wpStylesFilter($this->wpAllStyles['queue'],  'queue');
		    }

		    if (! empty($this->wpAllScripts) && isset($this->wpAllScripts['queue'])) {
			    $manageScripts = $this->wpAllScripts['queue'];
		    }

		    if (! empty($currentUnloadedAll['styles'])) {
			    foreach ( $currentUnloadedAll['styles'] as $currentUnloadedStyleHandle ) {
				    if ( ! in_array( $currentUnloadedStyleHandle, $manageStyles ) ) {
					    $manageStyles[] = $currentUnloadedStyleHandle;
				    }
			    }
		    }

		    if (! empty($manageStylesCore)) {
		    	foreach ($manageStylesCore as $wpDoneStyle) {
				    if ( ! in_array( $wpDoneStyle, $manageStyles ) ) {
					    $manageStyles[] = $wpDoneStyle;
				    }
			    }
		    }

		    $manageStyles = array_unique($manageStyles);

		    if (! empty($currentUnloadedAll['scripts'])) {
			    foreach ( $currentUnloadedAll['scripts'] as $currentUnloadedScriptHandle ) {
				    if ( ! in_array( $currentUnloadedScriptHandle, $manageScripts ) ) {
					    $manageScripts[] = $currentUnloadedScriptHandle;
				    }
			    }
		    }

		    if (! empty($wp_scripts->done)) {
			    foreach ($wp_scripts->done as $wpDoneScript) {
				    if ( ! in_array( $wpDoneScript, $manageScripts ) ) {
					    $manageScripts[] = $wpDoneScript;
				    }
			    }
		    }

		    $manageScripts = array_unique($manageScripts);
	    }

	    /*
		 * Style List
		 */
	    if ($isFrontEndEditView) { // "Manage in the Front-end"
		    $stylesList = $stylesBeforeUnload['registered'];
	    } else { // "Manage in the Dashboard"
		    $stylesListFilterAll = $this->wpStylesFilter($wp_styles, 'registered');
		    $stylesList = $stylesListFilterAll->registered;
        }

        if (! empty($stylesList)) {
            /* These styles below are used by this plugin (except admin-bar) and they should not show in the list
               as they are loaded only when you (or other admin) manage the assets, never for your website visitors */
	        if (is_admin_bar_showing() && is_admin()) {
		        $this->skipAssets['styles'][] = 'dashicons';
	        }

            foreach ($manageStyles as $handle) {
                if (! isset($stylesList[$handle]) || in_array($handle, $this->skipAssets['styles'])) {
                    continue;
                }

                $list['styles'][] = $stylesList[$handle];
            }

            // Append unloaded ones (if any)
            if (! empty($stylesBeforeUnload) && ! empty($currentUnloadedAll['styles'])) {
                foreach ($currentUnloadedAll['styles'] as $sbuHandle) {
                    if (! in_array($sbuHandle, $manageStyles)) {
                        // Could be an old style that is not loaded anymore
                        // We have to check that
                        if (! isset($stylesBeforeUnload['registered'][$sbuHandle])) {
                            continue;
                        }

                        $sbuValue = $stylesBeforeUnload['registered'][$sbuHandle];
	                    $list['styles'][] = $sbuValue;
                    }
                }
            }

            ksort($list['styles']);
        }

        /*
        * Scripts List
        */
	    $scriptsList = $wp_scripts->registered;

	    if ($isFrontEndEditView) {
		    $scriptsList = $scriptsBeforeUnload['registered'];
	    }

        if (! empty($scriptsList)) {
            /* These scripts below are used by this plugin (except admin-bar) and they should not show in the list
								as they are loaded only when you (or other admin) manage the assets, never for your website visitors */
            foreach ($manageScripts as $handle) {
                if (! isset($scriptsList[$handle]) || in_array($handle, $this->skipAssets['scripts'])) {
                    continue;
                }

	            $list['scripts'][] = $scriptsList[$handle];
            }

            // Append unloaded ones (if any)
            if (! empty($scriptsBeforeUnload) && ! empty($currentUnloadedAll['scripts'])) {
                foreach ($currentUnloadedAll['scripts'] as $sbuHandle) {
                    if (! in_array($sbuHandle, $manageScripts)) {
                        // Could be an old script that is not loaded anymore
                        // We have to check that
                        if (! isset($scriptsBeforeUnload['registered'][$sbuHandle])) {
                            continue;
                        }

                        $sbuValue = $scriptsBeforeUnload['registered'][$sbuHandle];

	                    $list['scripts'][] = $sbuValue;
                    }
                }
            }

            ksort($list['scripts']);

            }

        // Front-end View while admin is logged in
        if ($isFrontEndEditView) {
	        $wpacuSettings = new Settings();

            $data = array(
                'is_updateable'   => true,
                'post_type'       => '',
                'bulk_unloaded'   => array('post_type' => array()),
	            'plugin_settings' => $wpacuSettings->getAll()
            );

	        $data['wpacu_page_just_updated'] = false;

	        if (get_transient('wpacu_page_just_updated')) {
		        $data['wpacu_page_just_updated'] = true;
		        delete_transient('wpacu_page_just_updated');
	        }

	        // [wpacu_lite]
            if ($this->isUpdateable) {
            // [/wpacu_lite]
                $data['current'] = $currentUnloaded;

                $data['all']['scripts'] = $list['scripts'];
                $data['all']['styles']  = $list['styles'];

	            if ($data['plugin_settings']['assets_list_layout'] === 'by-location') {
		            $data['all'] = Sorting::appendLocation($data['all']);
	            } else {
		            $data['all'] = Sorting::sortListByAlpha($data['all']);
	            }

	            //echo '<pre>'; print_r($data['all']);

	            $this->fetchUrl         = Misc::getPageUrl($this->getCurrentPostId());

                $data['fetch_url']      = $this->fetchUrl;

                $data['nonce_name']     = Update::NONCE_FIELD_NAME;
                $data['nonce_action']   = Update::NONCE_ACTION_NAME;

                $data = $this->alterAssetObj($data);

                $data['global_unload']   = $this->globalUnloaded;

                if (Misc::isHomePage()) {
                    $type = 'front_page';
                } elseif ($this->getCurrentPostId() > 0) {
                	$type = 'post';
                }
	            $data['load_exceptions'] = $this->getLoadExceptions($type, $this->getCurrentPostId());
            // [wpacu_lite]
            } else {
                $data['is_updateable'] = false;
            }
	        // [/wpacu_lite]

	        // WooCommerce Shop Page?
            $data['is_woo_shop_page'] = $this->vars['is_woo_shop_page'];

            $data['is_bulk_unloadable'] = $data['bulk_unloaded_type'] = false;

	        $data['bulk_unloaded']['post_type'] = array('styles' => array(), 'scripts' => array());

            if ($this->isSingularPage()) {
                $post = $this->getCurrentPost();

                // Current Post Type
                $data['post_type'] = $post->post_type;

                // Are there any assets unloaded for this specific post type?
                // (e.g. page, post, product (from WooCommerce) or other custom post type)
                $data['bulk_unloaded']['post_type'] = $this->getBulkUnload('post_type', $data['post_type']);

	            $data['bulk_unloaded_type'] = 'post_type';

	            $data['is_bulk_unloadable'] = true;

	            $data = $this->setPageTemplate($data);
            }

	        // [wpacu_lite]
	        if ($this->isUpdateable) {
            // [/wpacu_lite]
		        $data['total_styles']  = ! empty($data['all']['styles']) ? count($data['all']['styles']) : 0;
		        $data['total_scripts'] = ! empty($data['all']['scripts']) ? count($data['all']['scripts']) : 0;

		        $data['all_deps']      = $this->getAllDeps($data['all']);
            // [wpacu_lite]
	        }
            // [/wpacu_lite]

	        $data['preloads']     = Preloads::instance()->getPreloads();
            $data['handle_notes'] = $this->getHandleNotes();
	        $data['ignore_child'] = $this->getIgnoreChildren();

	        $this->parseTemplate('settings-frontend', $data, true);
        } elseif ($isDashboardEditView) {
	        // AJAX call (not the classic WP one) from the WP Dashboard
	        // Send the altered value that has the initial position too

	        // Taken front the front-end view
	        $data = array();
	        $data['all']['scripts'] = $list['scripts'];
	        $data['all']['styles'] = $list['styles'];

	        $data = $this->alterAssetObj($data);

	        $list['styles']  = $data['all']['styles'];
	        $list['scripts'] = $data['all']['scripts'];

            echo self::START_DEL
                 .base64_encode(json_encode($list)).
                self::END_DEL;

            // Do not allow further processes as cache plugins such as W3 Total Cache could alter the source code
            // and we need the non-minified version of the DOM (e.g. to determine the position of the elements)
            exit();
        }
    }

    /**
     * @param $name
     * @param array $data (if present $data values are used within the included template)
     * @param bool|false $echo
     * @return bool|string
     */
    public function parseTemplate($name, $data = array(), $echo = false)
    {
        $templateFile = apply_filters(
            'wpacu_template_file', // tag
            dirname(__DIR__) . '/templates/' . $name . '.php', // value
            $name // extra argument
        );

        if (! file_exists($templateFile)) {
            return 'Template '.$templateFile.' not found.';
        }

        ob_start();
        include $templateFile;
        $result = ob_get_clean();

        if ($echo) {
            echo $result;
            return true;
        }

        return $result;
    }

    /**
     *
     */
    public function ajaxGetJsonListCallback()
    {
        $postId  = (int)Misc::getVar('post', 'post_id'); // if any (could be home page for instance)
        $pageUrl = Misc::getVar('post', 'page_url'); // post, page, custom post type, home page etc.

        // Not homepage, but a post/page? Check if it's published in case AJAX call
        // wasn't stopped due to JS errors or other reasons
	    if ($postId > 0 && get_post_status($postId) !== 'publish') {
	        exit(__('The CSS/JS files will be available to manage once the post/page is published.', 'wp-asset-clean-up'));
	    }

        $wpacuList = $contents = '';

        $settings = new Settings();

        if (self::$domGetType === 'direct') {
            $wpacuList = Misc::getVar('post', 'wpacu_list');
        } elseif (self::$domGetType === 'wp_remote_post') {
	        $wpRemotePost = wp_remote_post($pageUrl, array(
                'body' => array(
	                WPACU_LOAD_ASSETS_REQ_KEY => 1
                )
            ));

            $contents = isset($wpRemotePost['body']) ? $wpRemotePost['body'] : '';

            if ($contents
                && (strpos($contents, self::START_DEL) !== false)
                && (strpos($contents, self::END_DEL) !== false)) {
                $wpacuList = Misc::extractBetween(
                    $contents,
                    self::START_DEL,
                    self::END_DEL
                );
            }

            // The list of assets could not be retrieved via "WP Remote Post" for this server
	        // Print out the 'error' response to make the user aware about it
            if (! $wpacuList) {
            	$data = array(
            		'is_dashboard_view' => true,
		            'plugin_settings'   => $settings->getAll(),
            		'wp_remote_post'    => $wpRemotePost
	            );

	            $this->parseTemplate('meta-box-loaded', $data, true);
	            exit;
            }
        }

        $json = base64_decode($wpacuList);

        $data = array(
        	'post_id'         => $postId,
	        'plugin_settings' => $settings->getAll()
        );

        $data['all'] = (array)json_decode($json);

	    if ($data['plugin_settings']['assets_list_layout'] === 'by-location') {
		    $data['all'] = Sorting::appendLocation($data['all']);
	    } else {
		    $data['all'] = Sorting::sortListByAlpha($data['all']);
	    }

	    // Check any existing results
        $data['current'] = (array)json_decode($this->getAssetsUnloaded($postId));

        // Set to empty if not set to avoid any errors
        if (! isset($data['current']['styles']) || !is_array($data['current']['styles'])) {
            $data['current']['styles'] = array();
        }

        if (! isset($data['current']['scripts']) || !is_array($data['current']['scripts'])) {
            $data['current']['scripts'] = array();
        }

        $data['fetch_url'] = $pageUrl;
        $data['global_unload'] = $this->getGlobalUnload();

        $data['is_bulk_unloadable'] = $data['bulk_unloaded_type'] = false;

        // Post Information
	    if ($postId > 0) {
		    $postData = get_post($postId);

		    if (isset($postData->post_type)) {
			    // Current Post Type
			    $data['post_type'] = $postData->post_type;

			    // Are there any assets unloaded for this specific post type?
			    // (e.g. page, post, product (from WooCommerce) or other custom post type)
			    $data['bulk_unloaded']['post_type'] = $this->getBulkUnload('post_type', $data['post_type']);
			    $data['bulk_unloaded_type']         = 'post_type';
			    $data['is_bulk_unloadable']         = true;
		    }
	    }

	    if ($postId > 0) {
			$type = 'post';
		}
		elseif ($postId == 0) {
			$type = 'front_page';
		}

        $data['load_exceptions'] = $this->getLoadExceptions($type, $postId);

        $data['total_styles']  = ! empty($data['all']['styles']) ? count($data['all']['styles']) : 0;
        $data['total_scripts'] = ! empty($data['all']['scripts']) ? count($data['all']['scripts']) : 0;

	    $data['all_deps'] = $this->getAllDeps($data['all']);

	    $data['preloads']     = Preloads::instance()->getPreloads();
	    $data['handle_notes'] = $this->getHandleNotes();
	    $data['ignore_child'] = $this->getIgnoreChildren();

        $this->parseTemplate('meta-box-loaded', $data, true);

        exit;
    }

	/**
	 * @return void
	 */
	public function ajaxFetchActivePluginsIcons()
    {
	    if (! isset($_POST['action'])) {
	    	return;
	    }

	    if (! Menu::userCanManageAssets()) {
		    return;
	    }

	    $activePluginsIcons = Misc::fetchActiveFreePluginsIcons() ?: array();

	    if ($activePluginsIcons && is_array($activePluginsIcons) && ! empty($activePluginsIcons)) {
	    	echo print_r($activePluginsIcons, true)."\n";
	    	exit;
	    }
    }

	/**
	 *
	 */
	public function ajaxFetchActivePluginsJsFooterCode()
	{
		if (! (isset($_GET['page']) && strpos($_GET['page'], WPACU_PLUGIN_ID . '_') === 0)) {
			return;
		}

		if (! Menu::userCanManageAssets()) {
			return;
		}

		if (get_transient('wpacu_active_plugins_icons')) {
			return;
		}
		?>
		<script type="text/javascript" >
            jQuery(document).ready(function($) {
	            jQuery.post(ajaxurl, {
                    'action': '<?php echo WPACU_PLUGIN_ID.'_fetch_active_plugins_icons'; ?>',
                }, function(response) {
	                console.log(response);
	            });
            });
		</script>
		<?php
	}

    /**
     * @param $data
     * @return mixed
     */
    public function alterAssetObj($data)
    {
        $siteUrl = get_site_url();

        if (! empty($data['all']['styles'])) {
            $data['core_styles_loaded'] = false;

	        foreach ($data['all']['styles'] as $key => $obj) {
                if (! isset($obj->handle)) {
                    unset($data['all']['styles']['']);
                    continue;
                }

	            // From WordPress directories (false by default, unless it was set to true before: in Sorting.php for instance)
	            if (! isset($data['all']['styles'][$key]->wp)) {
		            $data['all']['styles'][$key]->wp = false;
	            }

	            if (in_array($obj->handle, $this->assetsInFooter['styles'])) {
		            $data['all']['styles'][$key]->position = 'body';
	            } else {
		            $data['all']['styles'][$key]->position = 'head';
	            }

                if (isset($data['all']['styles'][$key], $obj->src) && $obj->src) {
	                $localSrc = Misc::getLocalSrc($obj->src);

	                if (! empty($localSrc)) {
		                $data['all']['styles'][$key]->baseUrl = $localSrc['base_url'];
	                }

                    $part = str_replace(
                        array(
                            'http://',
                            'https://',
                            '//'
                        ),
                        '',
                        $obj->src
                    );

                    $parts     = explode('/', $part);
	                $parentDir = isset($parts[1]) ? $parts[1] : '';

                    // Loaded from WordPress directories (Core)
                    if (in_array($parentDir, array('wp-includes', 'wp-admin'))) {
                        $data['all']['styles'][$key]->wp = true;
                        $data['core_styles_loaded']      = true;
                    }

                    // Determine source href (starting with '/' but not starting with '//')
                    if (strpos($obj->src, '/') === 0 && strpos($obj->src, '//') !== 0) {
                        $obj->srcHref = $siteUrl . $obj->src;
                    } else {
                        $obj->srcHref = $obj->src;
                    }
                }
            }
        }

        if (! empty($data['all']['scripts'])) {
            $data['core_scripts_loaded'] = false;

            foreach ($data['all']['scripts'] as $key => $obj) {
                if (! isset($obj->handle)) {
                    unset($data['all']['scripts']['']);
                    continue;
                }

                // From WordPress directories (false by default, unless it was set to true before: in Sorting.php for instance)
                if (! isset($data['all']['scripts'][$key]->wp)) {
	                $data['all']['scripts'][$key]->wp = false;
                }

	            $initialScriptPos = wp_cache_get($obj->handle, 'wpacu_scripts_initial_positions');

                if ($initialScriptPos === 'body' || in_array($obj->handle, $this->assetsInFooter['scripts'])) {
                    $data['all']['scripts'][$key]->position = 'body';
                } else {
                    $data['all']['scripts'][$key]->position = 'head';
                }

                if (isset($data['all']['scripts'][$key])) {
                    if (isset($obj->src) && $obj->src) {
	                    $localSrc = Misc::getLocalSrc($obj->src);

	                    if (! empty($localSrc)) {
		                    $data['all']['scripts'][$key]->baseUrl = $localSrc['base_url'];
	                    }

                        $part = str_replace(
                            array(
                                'http://',
                                'https://',
                                '//'
                            ),
                            '',
                            $obj->src
                        );

	                    $parts     = explode('/', $part);
	                    $parentDir = isset($parts[1]) ? $parts[1] : '';

                        // Loaded from WordPress directories (Core)
                        if (in_array($parentDir, array('wp-includes', 'wp-admin')) || strpos($obj->src, '/plugins/jquery-updater/js/jquery-') !== false) {
                            $data['all']['scripts'][$key]->wp = true;
                            $data['core_scripts_loaded'] = true;
                        }

                        // Determine source href
                        if (substr($obj->src, 0, 1) === '/' && substr($obj->src, 0, 2) !== '//') {
                            $obj->srcHref = $siteUrl . $obj->src;
                        } else {
                            $obj->srcHref = $obj->src;
                        }
                    }

                    if (in_array($obj->handle,  array('jquery', 'jquery-core', 'jquery-migrate'))) {
                        $data['all']['scripts'][$key]->wp = true;
                        $data['core_scripts_loaded'] = true;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * This method retrieves only the assets that are unloaded per page
     * Including 404, date and search pages (they are considered as ONE page with the same rules for any URL variation)
     *
     * @param int $postId
     * @return string (The returned value must be a JSON one)
     */
    public function getAssetsUnloaded($postId = 0)
    {
        // Post Type (Overwrites 'front' - home page - if we are in a singular post)
        if ($postId == 0) {
            $postId = (int)$this->getCurrentPostId();
        }

        $isInAdminPageViaAjax = (is_admin() && defined('DOING_AJAX') && DOING_AJAX);

        if (empty($this->assetsRemoved)) {
            // For Home Page (latest blog posts)
            if ($postId < 1 && ($isInAdminPageViaAjax || Misc::isHomePage())) {
                $this->assetsRemoved = get_option( WPACU_PLUGIN_ID . '_front_page_no_load');
            } elseif ($postId > 0) {
                $this->assetsRemoved = get_post_meta($postId, '_' . WPACU_PLUGIN_ID . '_no_load', true);
            }

	        @json_decode($this->assetsRemoved);

	        if (! (Misc::jsonLastError() === JSON_ERROR_NONE) || empty($this->assetsRemoved)) {
	        	// Reset value to a JSON formatted one
		        $this->assetsRemoved = json_encode(array('styles' => array(), 'scripts' => array()));
	        }
        }

        return $this->assetsRemoved;
    }

	/**
	 * @param $allAssets
	 *
	 * @return array
	 */
	public function getAllDeps($allAssets)
    {
	    $allDeps = array();

	    foreach (array('styles', 'scripts') as $assetType) {
		    if ( ! (isset($allAssets[$assetType]) && ! empty($allAssets[$assetType])) ) {
			    continue;
		    }
		    foreach ($allAssets[$assetType] as $assetObj) {
			    if (isset($assetObj->deps) && ! empty($assetObj->deps)) {
				    foreach ($assetObj->deps as $dep) {
					    $allDeps[$assetType][$dep][] = $assetObj->handle;
				    }
			    }
		    }
	    }

	    return $allDeps;
    }

    /**
     * @return bool
     */
    public function isSingularPage()
    {
        return ($this->vars['is_woo_shop_page'] || is_singular());
    }

    /**
     * @return int|mixed|string
     */
    public function getCurrentPostId()
    {
        if ($this->currentPostId > 0) {
            return $this->currentPostId;
        }

        // Are we on the `Shop` page from WooCommerce?
        // Only check option if function `is_shop` exists
        $wooCommerceShopPageId = function_exists('is_shop') ? get_option('woocommerce_shop_page_id') : 0;

        // Check if we are on the WooCommerce Shop Page
        // Do not mix the WooCommerce Search Page with the Shop Page
        if (function_exists('is_shop') && is_shop()) {
            $this->currentPostId = $wooCommerceShopPageId;

            if ($this->currentPostId > 0) {
                $this->vars['is_woo_shop_page'] = true;
            }
        } else {
            if ($wooCommerceShopPageId > 0 && Misc::isHomePage() && strpos(get_site_url(), '://') !== false) {
                list($siteUrlAfterProtocol) = explode('://', get_site_url());
                $currentPageUrlAfterProtocol = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                if ($siteUrlAfterProtocol != $currentPageUrlAfterProtocol && (strpos($siteUrlAfterProtocol,
                            '/shop') !== false)
                ) {
                    $this->vars['woo_url_not_match'] = true;
                }
            }
        }

	    // Blog Home Page (aka: Posts page) is not a singular page, it's checked separately
        if (Misc::isBlogPage()) {
        	$this->currentPostId = get_option('page_for_posts');
        }

        // It has to be a single page (no "Posts page")
        if (($this->currentPostId < 1) && is_singular()) {
            global $post;
            $this->currentPostId = isset($post->ID) ? $post->ID : 0;
        }

	    // [wpacu_lite]
        // Undetectable? The page is not a singular one nor the home page
        // It's likely an archive, category page (WooCommerce), 404 page etc.
        if (! $this->currentPostId && ! Misc::isHomePage()) {
	        $this->isUpdateable = false;

	        }

	    // [/wpacu_lite]

        return $this->currentPostId;
    }

    /**
     * @return array|null|\WP_Post
     */
    public function getCurrentPost()
    {
        // Already set? Return it
        if (! empty($this->currentPost)) {
            return $this->currentPost;
        }

        // Not set? Create and return it
        if (! $this->currentPost && $this->getCurrentPostId() > 0) {
            $this->currentPost = get_post($this->getCurrentPostId());
            return $this->currentPost;
        }

        // Empty
        return $this->currentPost;
    }

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function setPageTemplate($data)
    {
    	global $template;

	    $getPageTpl = get_post_meta($this->getCurrentPostId(), '_wp_page_template', true);

	    // Could be a custom post type with no template set
	    if (! $getPageTpl) {
		    $getPageTpl = get_page_template();

		    if (in_array(basename($getPageTpl), array('single.php', 'page.php'))) {
			    $getPageTpl = 'default';
		    }
	    }

	    if (! $getPageTpl) {
	    	return $data;
	    }

	    $data['page_template'] = $getPageTpl;

	    $data['all_page_templates'] = wp_get_theme()->get_page_templates();

	    // Is the default template shown? Most of the time it is!
	    if ($data['page_template'] === 'default') {
	    	$pageTpl = (isset($template) && $template) ? $template : get_page_template();
		    $data['page_template'] = basename( $pageTpl );
		    $data['all_page_templates'][ $data['page_template'] ] = 'Default Template';
	    }

	    if (isset($template) && $template && defined('ABSPATH')) {
	    	$data['page_template_path'] = str_replace(
			    ABSPATH,
			    '',
			    '/'.$template
		    );
	    }

	    return $data;
    }

    /**
     * @return bool
     */
    public static function isSettingsPage()
    {
        return (Misc::getVar('get', 'page') === WPACU_PLUGIN_ID . '_settings');
    }

	/**
	 * @return bool
	 */
	public static function isWpDefaultSearchPage()
	{
		// It will not interfere with the WooCommerce search page
		// which is considered to be the "Shop" page that has its own unload rules
		return (is_search() && (! (function_exists('is_shop') && is_shop())));
	}

	/**
	 * @param $existingListJson
	 * @param $existingListEmpty
	 *
	 * @return array
	 */
	public function existingList($existingListJson, $existingListEmpty)
	{
		$validJson = $notEmpty = true;

		if (! $existingListJson) {
			$existingList = $existingListEmpty;
			$notEmpty = false;
		} else {
			$existingList = json_decode($existingListJson, true);

			if (Misc::jsonLastError() !== JSON_ERROR_NONE) {
				$validJson = false;
				$existingList = $existingListEmpty;
			}
		}

		return array(
			'list'       => $existingList,
			'valid_json' => $validJson,
			'not_empty'  => $notEmpty
		);
	}

	/**
	 * Situations when the assets will not be prevented from loading
	 * e.g. test mode and a visitor accessing the page, an AJAX request from the Dashboard to print all the assets
	 * @return bool
	 */
	public function preventUnloadAssets()
	{
		// This request specifically asks for all the assets to be loaded in order to print them in the assets management list
		// This is for the AJAX requests within the Dashboard, thus the admin needs to see all the assets,
		// including ones marked for unload, in case he/she decides to change their rules
		if ($this->isGetAssetsCall) {
			return true;
		}

		// Is test mode enabled? Unload assets ONLY for the admin
		if (isset($this->settings['test_mode']) && $this->settings['test_mode'] && ! Menu::userCanManageAssets()) {
			return true; // visitors (non-logged in) will view the pages with all the assets loaded
		}

		if (defined('WPACU_CURRENT_PAGE_ID') && WPACU_CURRENT_PAGE_ID > 0) {
		    $pageOptions = MetaBoxes::getPageOptions(WPACU_CURRENT_PAGE_ID);

		    if (isset($pageOptions['no_assets_settings']) && $pageOptions['no_assets_settings']) {
		        return true;
            }
        }

		return false;
	}

	/**
	 * @return bool
	 */
	public function frontendShow()
	{
		// The option is disabled
		if (! $this->settings['frontend_show']) {
			return false;
		}

		// The asset list is hidden via query string: /?wpacu_no_frontend_show
		if (array_key_exists('wpacu_no_frontend_show', $_GET)) {
			return false;
		}

		// The option is enabled, but there are show exceptions, check if the list should be hidden
		if ($this->settings['frontend_show_exceptions']) {
			$frontendShowExceptions = trim( $this->settings['frontend_show_exceptions'] );

			if ( strpos( $frontendShowExceptions, "\n" ) !== false ) {
				foreach ( explode( "\n", $frontendShowExceptions ) as $frontendShowException ) {
					$frontendShowException = trim($frontendShowException);

					if ( strpos( $_SERVER['REQUEST_URI'], $frontendShowException ) !== false ) {
						return false;
					}
				}
			} elseif ( strpos( $_SERVER['REQUEST_URI'], $frontendShowExceptions ) !== false ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Make administrator more aware if "TEST MODE" is enabled or not
	 */
	public function wpacuHtmlNoticeForAdmin()
	{
		add_action('wp_footer', function() {
            if (! apply_filters('wpacu_show_admin_console_notice', true)) {
                return;
            }

            if ( ! (Menu::userCanManageAssets() && ! is_admin())) {
                return;
            }

            if (Main::instance()->settings['test_mode']) {
                $consoleMessage = __('Asset CleanUp: "TEST MODE" ENABLED (any settings or unloads will be visible ONLY to you, the logged-in administrator)', 'wp-asset-clean-up');
                $testModeNotice = __('"Test Mode" is ENABLED. Any settings or unloads will be visible ONLY to you, the logged-in administrator.', 'wp-asset-clean-up');
            } else {
                $consoleMessage = __('Asset CleanUp: "LIVE MODE" (test mode is not enabled, thus, all the plugin changes are visible for everyone: you, the logged-in administrator and the regular visitors)', 'wp-asset-clean-up');
                $testModeNotice = __('The website is in LIVE MODE as "Test Mode" is not enabled. All the plugin changes are visible for everyone: logged-in administrators and regular visitors.', 'wp-asset-clean-up');
            }

            $htmlCommentNote = __('NOTE: These "Asset CleanUp: Page Speed Booster" messages are only shown to you, the HTML comment is not visible for the regular visitor.', 'wp-asset-clean-up');
            ?>
            <!--
            <?php echo $htmlCommentNote; ?>

            <?php echo $testModeNotice; ?>
            -->
            <script type="text/javascript">
            console.log('<?php echo $consoleMessage; ?>');
            </script>
        <?php
        });
	}
}
