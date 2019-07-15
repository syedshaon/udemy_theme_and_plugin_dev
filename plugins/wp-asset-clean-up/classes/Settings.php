<?php
namespace WpAssetCleanUp;

use WpAssetCleanUp\OptimiseAssets\OptimizeCommon;

/**
 * Class Settings
 * @package WpAssetCleanUp
 */
class Settings
{
	/**
	 * @var array
	 */
	public $settingsKeys = array(
		// Stored in 'wpassetcleanup_settings'
        'wiki_read',

		// Dashboard Assets Management
		'dashboard_show',
		'dom_get_type',

		'hide_assets_meta_box',  // Asset CleanUp Pro: CSS & JavaScript Manager
		'hide_options_meta_box', // Asset CleanUp Pro: Options

		// Front-end View Assets Management
        'frontend_show',
        'frontend_show_exceptions',

		'hide_from_admin_bar',

		// The way the CSS/JS list is showing (various ways depending on the preference)
		'assets_list_layout',
        'assets_list_layout_areas_status',
        'assets_list_inline_code_status',

		'input_style',

        'hide_core_files',
        'test_mode',

		// Combine loaded CSS (remaining ones after unloading the useless ones) into fewer files
        'combine_loaded_css',
		'combine_loaded_css_exceptions',
		'combine_loaded_css_for_admin_only',

		// Combine loaded JS (remaining ones after unloading the useless ones) into fewer files
		'combine_loaded_js',
		'combine_loaded_js_exceptions',
		'combine_loaded_js_for_admin_only',
		'combine_loaded_js_defer_body', // Applies defer="defer" to the combined file(s) within BODY tag

		// Minify each loaded CSS (remaining ones after unloading the useless ones)
		'minify_loaded_css',
		'minify_loaded_css_exceptions',

		// Minify each loaded JS (remaining ones after unloading the useless ones)
		'minify_loaded_js',
		'minify_loaded_js_exceptions',

        'disable_emojis',
		'disable_oembed',
		'disable_dashicons_for_guests',

		// Stored in 'wpassetcleanup_global_unload' option
        'disable_jquery_migrate',
        'disable_comment_reply',

		// <head> CleanUp
		'remove_rsd_link',
		'remove_wlw_link',
		'remove_rest_api_link',
		'remove_shortlink',
		'remove_posts_rel_links',
		'remove_wp_version',

		// all "generator" meta tags including the WordPress version
		'remove_generator_tag',

		// RSS Feed Links
		'remove_main_feed_link',
		'remove_comment_feed_link',

		// Remove HTML comments
		'remove_html_comments',
		'remove_html_comments_exceptions',

		'disable_xmlrpc',

        // Allow Usage Tracking
        'allow_usage_tracking'
    );

    /**
     * @var array
     */
    public $currentSettings = array();

	/**
	 * @var array
	 */
	public $defaultSettings = array();

	/**
	 * Settings constructor.
	 */
	public function __construct()
    {
        $this->defaultSettings = array(
	        // Show the assets list within the Dashboard, while they are hidden in the front-end view
	        'dashboard_show' => '1',

	        // Direct AJAX call by default (not via WP Remote Post)
	        'dom_get_type'   => 'direct',

	        // Very good especially for page builders: Divi Visual Builder, Oxygen Builder, WPBakery, Beaver Builder etc.
	        // It is also hidden in preview mode (if query strings such as 'preview_nonce' are used)
	        'frontend_show_exceptions' =>  'et_fb=1'."\n"
	                                       .'ct_builder=true'."\n"
	                                       .'vc_editable=true'."\n"
	                                       .'preview_nonce='."\n",

	        // Since v1.2.9.3 (lite), the default value is "by-location" (All Styles & All Scripts - By Location (Theme, Plugins, Custom & External))
	        // Prior to that it's "two-lists" (All Styles & All Scripts - 2 separate lists)
	        'assets_list_layout'              => 'by-location',
	        'assets_list_layout_areas_status' => 'expanded',

	        'assets_list_layout_plugin_area_status' => 'expanded', // Go Pro for 'contracted'

	        'assets_list_inline_code_status'  => 'contracted', // takes less space overall

	        'minify_loaded_css_exceptions' => '(.*?).min.css'. "\n". '/plugins/wd-instagram-feed/(.*?).css',
	        'minify_loaded_js_exceptions'  => '(.*?).min.js' . "\n". '/plugins/wd-instagram-feed/(.*?).js',

	        'combine_loaded_css_exceptions' => '/plugins/wd-instagram-feed/(.*?).css',
	        'combine_loaded_js_exceptions'  => '/plugins/wd-instagram-feed/(.*?).js',

	        'input_style' => 'enhanced',

	        // Since v1.2.8.6 (lite), WordPress core files are hidden in the assets list as a default setting
	        'hide_core_files' => '1'
        );
    }

	/**
     *
     */
    public function adminInit()
    {
        // This is triggered BEFORE "triggerAfterInit" from 'Main' class
        add_action('admin_init', array($this, 'saveSettings'), 9);

        if (Misc::getVar('get', 'page') === WPACU_PLUGIN_ID . '_settings') {
	        add_action('wpacu_admin_notices', array($this, 'notices'));
        }
    }

	/**
	 *
	 */
	public function notices()
    {
    	$settings = $this->getAll();

    	// When all ways to manage the assets are not enabled
    	if ($settings['dashboard_show'] != 1 && $settings['frontend_show'] != 1) {
		    ?>
		    <div class="notice notice-warning">
				<p><span style="color: #ffb900;" class="dashicons dashicons-info"></span>&nbsp;<?php _e('It looks like you have both "Manage in the Dashboard?" and "Manage in the Front-end?" inactive. The plugin still works fine and any assets you have selected for unload are not loaded. However, if you want to manage the assets in any page, you need to have at least one of the view options enabled.', 'wp-asset-clean-up'); ?></p>
		    </div>
		    <?php
	    }

	    // After "Save changes" is clicked
        if (get_transient('wpacu_settings_updated')) {
            delete_transient('wpacu_settings_updated');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><span class="dashicons dashicons-yes"></span> <?php _e('The settings were successfully updated.', 'wp-asset-clean-up'); ?></p>
            </div>
            <?php
        }
    }

    /**
     *
     */
    public function saveSettings()
    {
	    if (! Misc::getVar('post', 'wpacu_settings_nonce')) {
		    return;
	    }

	    check_admin_referer('wpacu_settings_update', 'wpacu_settings_nonce');

        $savedSettings = Misc::getVar('post', WPACU_PLUGIN_ID . '_settings', array());

        // Hooks can be attached here
        // e.g. from PluginTracking.php (check if "Allow Usage Tracking" has been enabled)
        do_action('wpacu_before_save_settings', $savedSettings);

        $this->update($savedSettings);
    }

    /**
     *
     */
    public function settingsPage()
    {
        $data = $this->getAll();

        foreach ($this->settingsKeys as $settingKey) {
            // Special check for plugin versions < 1.2.4.4
            if ($settingKey === 'frontend_show') {
                $data['frontend_show'] = $this->showOnFrontEndLegacy();
            }
        }

        $globalUnloadList = Main::instance()->getGlobalUnload();

        if (in_array('jquery-migrate', $globalUnloadList['scripts'])) {
            $data['disable_jquery_migrate'] = 1;
        }

	    if (in_array('comment-reply', $globalUnloadList['scripts'])) {
		    $data['disable_comment_reply'] = 1;
	    }

        Main::instance()->parseTemplate('admin-page-settings-plugin', $data, true);
    }

    /**
     * @return bool
     */
    public function showOnFrontEndLegacy()
    {
        $settings = $this->getAll();

        if ($settings['frontend_show'] == 1) {
            return true;
        }

        // [wpacu_lite]
        // Prior to 1.2.4.4
        if (get_option( WPACU_PLUGIN_ID . '_frontend_show') == 1) {
            // Put it in the main settings option
            $settings = $this->getAll();
            $settings['frontend_show'] = 1;
            $this->update($settings);

            delete_option( WPACU_PLUGIN_ID . '_frontend_show');
            return true;
        }
	    // [/wpacu_lite]

        return false;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        if (! empty($this->currentSettings)) {
            return $this->currentSettings;
        }

        $settingsOption = get_option(WPACU_PLUGIN_ID . '_settings');

        // If there's already a record in the database
        if ($settingsOption !== '' && is_string($settingsOption)) {
            $settings = (array)json_decode($settingsOption);

            if (Misc::jsonLastError() === JSON_ERROR_NONE) {
                // Make sure all the keys are there even if no value is attached to them
                // To avoid writing extra checks in other parts of the code and prevent PHP notice errors
                foreach ($this->settingsKeys as $settingsKey) {
                    if (! array_key_exists($settingsKey, $settings)) {
                        $settings[$settingsKey] = '';

                        // If it doesn't exist, it was never saved
                        // Make sure the default value is added to the textarea
	                    if (in_array($settingsKey, array('frontend_show_exceptions', 'minify_loaded_css_exceptions', 'minify_loaded_js_exceptions'))) {
	                        $settings[$settingsKey] = $this->defaultSettings[$settingsKey];
                        }
                    }
                }

                $this->currentSettings = $this->filterSettings($settings);

                return $this->currentSettings;
            }
        }

	    // No record in the database? Set the default values
	    // That could be because no changes were done on the "Settings" page
	    // OR a full reset of the plugin (via "Tools") was performed
        $defaultSettings = $this->defaultSettings;

        foreach ($this->settingsKeys as $settingsKey) {
	        if (! array_key_exists($settingsKey, $defaultSettings)) {
		        // Keep the keys with empty values to avoid notice errors
		        $defaultSettings[$settingsKey] = '';
	        }
        }

	    return $this->filterSettings($defaultSettings);
    }

	/**
	 * @param $settingsKey
	 *
	 * @return mixed
	 */
	public function getOption($settingsKey)
    {
        $settings = $this->getAll();
        return $settings[$settingsKey];
    }

	/**
	 * @param $setting
	 */
	public function updateOption($key, $value)
    {
	    $settings = $this->getAll();
	    $settings[$key] = $value;
	    $this->update($settings, false, false);
    }

	/**
	 * @param $setting
	 */
	public function deleteOption($key)
	{
		$settings = $this->getAll();
		$settings[$key] = '';
		$this->update($settings, false, false);
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function filterSettings($settings)
	{
		// /?wpacu_test_mode (will load the page with "Test Mode" enabled disregarding the value from the plugin's "Settings")
		// For debugging purposes
		if (array_key_exists('wpacu_test_mode', $_GET)) {
			$settings['test_mode'] = true;
		}

		return $settings;
	}

	/**
	 * @param $settings
	 * @param bool $redirectAfterUpdate
	 * @param bool $clearCache
	 */
	public function update($settings, $redirectAfterUpdate = true, $clearCache = true)
    {
	    $settingsNotNull = array();

	    foreach ($settings as $settingKey => $settingValue) {
	        if ($settingValue !== '') {
		        $settingsNotNull[$settingKey] = $settingValue;
            }
        }

	    if (json_encode($this->defaultSettings) === json_encode($settingsNotNull)) {
	        // Do not keep a record in the database (no point of having an extra entry)
            // if the submitted values are the same as the default ones
	        delete_option(WPACU_PLUGIN_ID . '_settings');

	        if ($redirectAfterUpdate) {
		        $this->redirectAfterUpdate(); // script ends here
	        }
        }

	    // The following are only triggered IF the user submitted the form from "Settings" area
        if (Misc::getVar('post', 'wpacu_settings_nonce')) {
	        // "Site-Wide Common Unloads" tab
	        $disableJQueryMigrate = isset($_POST[WPACU_PLUGIN_ID . '_global_unloads']['disable_jquery_migrate']);
	        $disableCommentReply  = isset($_POST[WPACU_PLUGIN_ID . '_global_unloads']['disable_comment_reply']);

	        $this->updateSiteWideRuleForCommonAssets(array(
		        'jquery_migrate' => $disableJQueryMigrate,
		        'comment_reply'  => $disableCommentReply
	        ));
        }

        Misc::addUpdateOption(WPACU_PLUGIN_ID . '_settings', json_encode($settings));

        if ($clearCache) {
	        // After settings are saved, clear all cache to re-built the CSS/JS based on the new settings
	        OptimizeCommon::clearAllCache();
        }

	    if ($redirectAfterUpdate) {
		    $this->redirectAfterUpdate();
	    }
    }

	/**
	 * @param $unloadsList
	 */
	public function updateSiteWideRuleForCommonAssets($unloadsList)
    {
	    $wpacuUpdate = new Update;

	    $disableJQueryMigrate = $unloadsList['jquery_migrate'];
	    $disableCommentReply  = $unloadsList['comment_reply'];

	    /*
	     * Add element(s) to the global unload rules
	     */
	    if ($disableJQueryMigrate || $disableCommentReply) {
		    $unloadList = array();

		    // Add jQuery Migrate to the global unload rules
		    if ($disableJQueryMigrate) {
			    $unloadList[] = 'jquery-migrate';
		    }

		    // Add Comment Reply to the global unload rules
		    if ($disableCommentReply) {
			    $unloadList[] = 'comment-reply';
		    }

		    $wpacuUpdate->saveToEverywhereUnloads(array(), $unloadList);
	    }

	    /*
		 * Remove element(s) from the global unload rules
		 */
	    if (! $disableJQueryMigrate || ! $disableCommentReply) {
		    $removeFromUnloadList = array();

		    // Remove jQuery Migrate from global unload rules
		    if (! $disableJQueryMigrate) {
			    $removeFromUnloadList['jquery-migrate'] = 'remove';
		    }

		    // Remove Comment Reply from global unload rules
		    if (! $disableCommentReply) {
			    $removeFromUnloadList['comment-reply'] = 'remove';
		    }

		    $wpacuUpdate->removeEverywhereUnloads(array(), $removeFromUnloadList);
	    }
    }

	/**
	 *
	 */
	public function redirectAfterUpdate()
    {
	    $tabArea = Misc::getVar('post', 'wpacu_selected_tab_area', 'wpacu-setting-plugin-usage-settings');

	    set_transient('wpacu_settings_updated', 1, 30);

	    wp_redirect(admin_url('admin.php?page=wpassetcleanup_settings&wpacu_selected_tab_area='.$tabArea.'&wpacu_time='.time()));
	    exit();
    }
}
