<?php
// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

if (! function_exists('assetCleanUpNoLoad')) {
	/**
	 * There are special cases when triggering "Asset CleanUp" plugin is not relevant
	 * Thus, for maximum compatibility and backend processing speed, it's better to avoid running any of its code
	 *
	 * @return bool
	 */
	function assetCleanUpNoLoad() {
		// On request: for debugging purposes - e.g. https://yourwebsite.com/?wpacu_no_load
		// Also make sure it's in the REQUEST URI and $_GET wasn't altered incorrectly before it's checked
		// Technically, it will be like Asset CleanUp is not activated: no global settings and unload rules will be applied
		if (array_key_exists('wpacu_no_load', $_GET) && strpos($_SERVER['REQUEST_URI'], 'wpacu_no_load') !== false) {
			return true;
		}

		// "Elementor" plugin Admin Area: Edit Mode
		if (isset($_GET['post'], $_GET['action']) && $_GET['post'] && $_GET['action'] === 'elementor' && is_admin()) {
			return true;
		}

		// "Elementor" plugin (Preview Mode within Page Builder)
		if (isset($_GET['elementor-preview'], $_GET['ver']) && (int)$_GET['elementor-preview'] > 0 && $_GET['ver']) {
			return true;
		}

		$wpacuIsAjaxRequest = (! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

		// "Elementor" plugin: Do not trigger the plugin on AJAX calls
		if (isset($_POST['action']) && (strpos($_POST['action'], 'elementor_') === 0) && $wpacuIsAjaxRequest) {
			return true;
		}

		// "Oxygen" plugin: Edit Mode
		if (isset($_GET['ct_builder'], $_GET['ct_inner']) && $_GET['ct_builder'] === 'true' && $_GET['ct_inner'] === 'true') {
			return true;
		}

		// "Divi" theme builder: Front-end View Edit Mode
		if (isset($_GET['et_fb'], $_GET['PageSpeed']) && $_GET['et_fb'] == 1 && $_GET['PageSpeed']) {
			return true;
		}

		// "Divi" theme builder: Do not trigger the plugin on AJAX calls
		if (isset($_POST['action']) && (strpos($_POST['action'], 'et_fb_') === 0) && $wpacuIsAjaxRequest) {
			return true;
		}

		// Beaver Builder
		if (isset($_GET['fl_builder'])) {
			return true;
		}

		// Thrive Architect (Dashboard)
		if (isset($_GET['action'], $_GET['tve']) && $_GET['action'] === 'architect' && $_GET['tve'] === 'true' && is_admin()) {
			return true;
		}

		// Thrive Architect (iFrame)
		$tveFrameFlag = defined('TVE_FRAME_FLAG') ? TVE_FRAME_FLAG : 'tcbf';

		if (isset($_GET['tve'], $_GET[$tveFrameFlag]) && $_GET['tve'] === 'true') {
			return true;
		}

		// Page Builder by SiteOrigin
		if (isset($_GET['action'], $_GET['so_live_editor']) && $_GET['action'] === 'edit' && $_GET['so_live_editor'] && is_admin()) {
			return true;
		}

		// Brizy - Page Builder
		if (isset($_GET['brizy-edit']) || isset($_GET['brizy-edit-iframe'])) {
			return true;
		}

		// WPBakery Page Builder
		if (isset($_GET['vc_editable'], $_GET['_vcnonce']) || (is_admin() && isset($_GET['vc_action']))) {
			return true;
		}

		// Perfmatters: Script Manager
		if (isset($_GET['perfmatters'])) {
			return true;
		}

		// WordPress Customise Mode
		if ((isset($_GET['customize_changeset_uuid'], $_GET['customize_theme']) && $_GET['customize_changeset_uuid'] && $_GET['customize_theme'])
		    || (strpos($_SERVER['REQUEST_URI'],
					'/wp-admin/customize.php') !== false && isset($_GET['url']) && $_GET['url'])) {
			return true;
		}

		// REST Request
		if ((defined('REST_REQUEST') && REST_REQUEST) || (strpos($_SERVER['REQUEST_URI'], '/wp-json/wp/v2/') !== false)) {
			return true;
		}

		// WordPress AJAX Heartbeat
		if (isset($_POST['action']) && $_POST['action'] === 'heartbeat') {
			return true;
		}

		// Stripe Requests via EDD Plugin
		if (isset($_GET['edd-listener']) && $_GET['edd-listener'] === 'stripe') {
			return true;
		}

		// AJAX Requests from various plugins/themes
		if ($wpacuIsAjaxRequest && isset($_GET['action'])
		    && (   strpos($_GET['action'], 'woocommerce') === 0
		        || strpos($_GET['action'], 'jetpack') === 0
		        || strpos($_GET['action'], 'wpfc_') === 0
			    || strpos($_GET['action'], 'oxygen_') === 0
			    || strpos($_GET['action'], 'oxy_') === 0
			    || strpos($_GET['action'], 'w3tc_') === 0
			    || strpos($_GET['action'], 'wpforms_') === 0
			    || strpos($_GET['action'], 'wdi_') === 0
		    )) {
			return true;
		}

		return false;
	}
}

// In case JSON library is not enabled (rare cases)
if (! defined('JSON_ERROR_NONE')) {
	define('JSON_ERROR_NONE', 0);
}
