<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
    exit;
}

include_once '_top-area.php';

// [wpacu_lite]
$availableForPro  = '<a href="'.WPACU_PLUGIN_GO_PRO_URL.'?utm_source=plugin_settings" class="go-pro-link-no-style"><span class="wpacu-tooltip">'.__('Available for Pro users', 'wp-asset-clean-up').'<br />'.__('Buy now to unlock all features!', 'wp-asset-clean-up').'</span> <img width="20" height="20" src="'.WPACU_PLUGIN_URL.'/assets/icons/icon-lock.svg" valign="top" alt="" /></a> &nbsp; ';
$settingsWithLock = '<em><strong>'.__('Note', 'wp-asset-clean-up').':</strong> '.__('The settings that have a lock are available to Pro users.', 'wp-asset-clean-up').' <a href="' . WPACU_PLUGIN_GO_PRO_URL . '?utm_source=plugin_settings">'.__('Click here to upgrade!', 'wp-asset-clean-up').'</a></em>';
// [/wpacu_lite]

do_action('wpacu_admin_notices');

$wikiStatus = ($data['wiki_read'] == 1) ? '<small style="font-weight: 200; color: green;">* '.__('read', 'wp-asset-clean-up').'</small>'
                                        : '<small style="font-weight: 200; color: #cc0000;">* '.__('unread', 'wp-asset-clean-up').'</small>';

$showSettingsType = array_key_exists('wpacu_show_all', $_GET) ? 'all' : 'tabs';
$selectedTabArea = '';

if ($showSettingsType === 'tabs') {
	$settingsTabs = array(
		'wpacu-setting-strip-the-fat'         => __( 'Stripping the "fat"', 'wp-asset-clean-up' ) . ' ' . $wikiStatus,
		'wpacu-setting-plugin-usage-settings' => __( 'General &amp; Files Management', 'wp-asset-clean-up' ),
		'wpacu-setting-test-mode'             => __( 'Test Mode', 'wp-asset-clean-up' ),
		'wpacu-setting-minify-loaded-files'   => __( 'Minify CSS &amp; JS Files', 'wp-asset-clean-up' ),
		'wpacu-setting-combine-loaded-files'  => __( 'Combine CSS &amp; JS Files', 'wp-asset-clean-up' ),
		'wpacu-setting-common-files-unload'   => __( 'Site-Wide Common Unloads', 'wp-asset-clean-up' ),
		'wpacu-setting-html-source-cleanup'   => __( 'HTML Source CleanUp', 'wp-asset-clean-up' ),
		'wpacu-setting-disable-xml-rpc'       => __( 'Disable XML-RPC', 'wp-asset-clean-up' ),
	);

	$settingsTabActive = 'wpacu-setting-plugin-usage-settings';

    // Is 'Stripping the "fat"' marked as read? Mark the "General & Files Management" as the default tab
	$defaultTabArea = ($data['wiki_read'] == 1) ? 'wpacu-setting-plugin-usage-settings' : 'wpacu-setting-strip-the-fat';

	$selectedTabArea = isset($_REQUEST['wpacu_selected_tab_area']) && array_key_exists($_REQUEST['wpacu_selected_tab_area'],
		$settingsTabs) // the tab id area has to be one within the list above
		? $_REQUEST['wpacu_selected_tab_area'] // after update
		: $defaultTabArea; // default

	if ($selectedTabArea && array_key_exists( $selectedTabArea, $settingsTabs)) {
		$settingsTabActive = $selectedTabArea;
	}
}
?>
<div class="wpacu-wrap wpacu-settings-area <?php if ($showSettingsType === 'all') { echo 'wpacu-settings-show-all'; } ?> <?php if ($data['input_style'] !== 'standard') { ?>wpacu-switch-enhanced<?php } else { ?>wpacu-switch-standard<?php } ?>">
    <form method="post" action="" id="wpacu-settings-form">
        <input type="hidden" name="wpacu_settings_page" value="1" />

        <div id="wpacu-settings-vertical-tab-wrap">
            <?php if ($showSettingsType === 'tabs') { ?>
                <div class="wpacu-settings-tab">
                    <?php
                    foreach ($settingsTabs as $navId => $navText) {
                        $active = ($settingsTabActive === $navId) ? 'active' : '';
                    ?>
                        <a href="#<?php echo $navId; ?>" class="wpacu-settings-tab-link <?php echo $active; ?>" onclick="wpacuTabOpenSettingsArea(event, '<?php echo $navId; ?>');"><?php echo $navText; ?></a>
                    <?php
                    }
                    ?>
                </div>
            <?php } ?>

            <?php
            include_once '_admin-page-settings-plugin-areas/_strip-the-fat.php';
            include_once '_admin-page-settings-plugin-areas/_plugin-usage-settings.php';
            include_once '_admin-page-settings-plugin-areas/_test-mode.php';
            include_once '_admin-page-settings-plugin-areas/_minify-loaded-files.php';
            include_once '_admin-page-settings-plugin-areas/_combine-loaded-files.php';
            include_once '_admin-page-settings-plugin-areas/_common-files-unload.php';
            include_once '_admin-page-settings-plugin-areas/_html-source-cleanup.php';
            include_once '_admin-page-settings-plugin-areas/_disable-xml-rpc-protocol.php';
            ?>

            <div class="clearfix"></div>
        </div>

        <div id="wpacu-update-button-area">
            <?php
            wp_nonce_field('wpacu_settings_update', 'wpacu_settings_nonce');
            submit_button(__('Update All Settings', 'wp-asset-clean-up'));
            ?>
            <div id="wpacu-updating-settings">
                <img src="<?php echo admin_url(); ?>/images/spinner.gif" align="top" width="20" height="20" alt="" />
            </div>
        </div>
        <input type="hidden"
               name="wpacu_selected_tab_area"
               id="wpacu-selected-tab-area"
               value="<?php echo $selectedTabArea; ?>" />
    </form>
</div>

<script type="text/javascript">
    <?php
    if (! empty($_POST)) {
    ?>
        // Situations: After settings update (post mode), do not jump to URL's anchor
        if (location.hash) {
            setTimeout(function() {
                window.scrollTo(0, 0);
            }, 1);
        }
    <?php
    }
    ?>
</script>