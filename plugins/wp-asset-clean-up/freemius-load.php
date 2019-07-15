<?php
/**
 * @return bool
 */
function wpacuLoadFreemius()
{
	// Uninstall Reason Sent via AJAX?
	if (isset($_POST['action'], $_POST['reason_id'], $_POST['reason_id'], $_POST['module_id'], $_POST['security'])
	    && $_POST['action'] && $_POST['reason_id'] && $_POST['module_id'] && $_POST['security']) {
		$freemiusModuleId = (int)$_POST['module_id'];

		if ($freemiusModuleId !== 2951) {
			return false;
		}

		if (strpos($_POST['action'], 'fs_submit') === false) {
			return false;
		}

		return true;
	}

	// Only in the following conditions
	if (! function_exists( 'wpassetcleanup_fs' ) // Not initialised before
	    && ! class_exists( 'Freemius' ) // Freemius SDK hasn't been loaded already
	    && is_admin() // Within the Dashboard
		&& strpos($_SERVER['REQUEST_URI'], '/wp-admin/plugins.php') !== false // Only on "Plugins" page
	) {
		return true;
	}

	return false;
}

if (wpacuLoadFreemius()) {
	/**
	 * Create a helper function for easy SDK access.
	 */
	function wpassetcleanup_fs()
	{
		global $wpassetcleanup_fs;

		if ( ! isset( $wpassetcleanup_fs ) ) {
			// Include Freemius SDK.
			require_once __DIR__ . '/freemius/start.php';

			$wpassetcleanup_fs = fs_dynamic_init( array (
				'id'             => '2951',
				'slug'           => 'wp-asset-clean-up',
				'type'           => 'plugin',
				'public_key'     => 'pk_70ecc6600cb03b5168150b4c99257',
				'is_premium'     => false,
				'has_addons'     => false,
				'has_paid_plans' => false,
				'anonymous_mode' => true,
				'menu'           => array(
					'slug'           => WPACU_ADMIN_PAGE_ID_START,
					'override_exact' => true,
					'account'        => false,
					'contact'        => false,
					'support'        => true,
				),
			) );
		}

		return $wpassetcleanup_fs;
	}

	// Init Freemius.
	wpassetcleanup_fs();

	// Signal that SDK was initiated.
	do_action('wpassetcleanup_fs_loaded');

	function wpassetcleanup_fs_settings_url() {
		return admin_url('admin.php?page='.WPACU_ADMIN_PAGE_ID_START);
	}

	wpassetcleanup_fs()->add_filter('connect_url', WPACU_PLUGIN_ID.'_fs_settings_url');
	wpassetcleanup_fs()->add_filter('after_skip_url', WPACU_PLUGIN_ID.'_fs_settings_url');
	wpassetcleanup_fs()->add_filter('after_connect_url', WPACU_PLUGIN_ID.'_fs_settings_url');
	wpassetcleanup_fs()->add_filter('after_pending_connect_url', WPACU_PLUGIN_ID.'_fs_settings_url');
}
