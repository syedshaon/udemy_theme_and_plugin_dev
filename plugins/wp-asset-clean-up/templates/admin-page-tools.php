<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
	exit;
}

include_once '_top-area.php';

do_action('wpacu_admin_notices');
?>
<div class="wpacu-wrap wpacu-tools-area">
    <nav class="wpacu-tab-nav-wrapper nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=wpassetcleanup_tools&wpacu_for=reset'); ?>" class="nav-tab <?php if ($data['for'] === 'reset') { ?>nav-tab-active<?php } ?>"><?php _e('Reset', 'wp-asset-clean-up'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=wpassetcleanup_tools&wpacu_for=system_info'); ?>" class="nav-tab <?php if ($data['for'] === 'system_info') { ?>nav-tab-active<?php } ?>"><?php _e('System Info', 'wp-asset-clean-up'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=wpassetcleanup_tools&wpacu_for=storage'); ?>" class="nav-tab <?php if ($data['for'] === 'storage') { ?>nav-tab-active<?php } ?>"><?php _e('Storage Info', 'wp-asset-clean-up'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=wpassetcleanup_tools&wpacu_for=import_export'); ?>" class="nav-tab <?php if ($data['for'] === 'import_export') { ?>nav-tab-active<?php } ?>"><?php _e('Import &amp; Export', 'wp-asset-clean-up'); ?></a>
    </nav>

	<div class="wpacu-tools-container">
		<form id="wpacu-tools-form" action="<?php echo admin_url('admin.php?page='.WPACU_PLUGIN_ID.'_tools'); ?>" method="post">
            <?php if ($data['for'] === 'reset') { ?>
                <div><label for="wpacu-reset-drop-down"><?php _e('Do you need to reset the plugin to its initial settings or reset all changes?', 'wp-asset-clean-up'); ?></label></div>

                <select name="wpacu-reset" id="wpacu-reset-drop-down">
                    <option value=""><?php _e('Select an option first', 'wp-asset-clean-up'); ?>...</option>
                    <option data-id="wpacu-warning-reset-settings" value="reset_settings"><?php _e('Reset "Settings"', 'wp-asset-clean-up'); ?></option>
                    <option data-id="wpacu-warning-reset-everything-except-settings" value="reset_everything_except_settings"><?php _e('Reset everything except "Settings"', 'wp-asset-clean-up'); ?></option>
                    <option data-id="wpacu-warning-reset-everything" value="reset_everything"><?php _e('Reset everything: "Settings", All Unloads (bulk &amp; per page) &amp; Load Exceptions', 'wp-asset-clean-up'); ?></option>
                </select>

                <div id="wpacu-license-data-remove-area">
                    <label for="wpacu-remove-license-data">
                       <input id="wpacu-remove-license-data" type="checkbox" name="wpacu-remove-license-data" value="1" /> <?php _e('Also remove license data in case the premium version was active at any point', 'wp-asset-clean-up'); ?>
                    </label>
                </div>

                <div id="wpacu-warning-read"><span class="dashicons dashicons-warning"></span> <strong><?php _e('Please read carefully below what the chosen action does as this process is NOT reversible.', 'wp-asset-clean-up'); ?></strong></div>

                <div id="wpacu-warning-reset-settings" class="wpacu-warning">
                    <p><?php _e('This will reset every option from the "Settings" page/tab to the same state it was when you first activated the plugin.', 'wp-asset-clean-up'); ?></p>
                </div>

                <div id="wpacu-warning-reset-everything-except-settings" class="wpacu-warning">
                    <p><?php _e('This will reset everything (changes per page &amp; any load exceptions), except the values from "Settings".', 'wp-asset-clean-up'); ?></p>
                    <p><?php _e('This action is usually taken if you are happy with the "Settings" configuration, but want to clear everything else in terms of changes per page or group of pages.', 'wp-asset-clean-up'); ?></p>
                </div>

                <div id="wpacu-warning-reset-everything" class="wpacu-warning">
                    <p><?php _e('This will reset everything (settings, page loads &amp; any load exceptions) to the same point it was when you first activated the plugin. All the plugin\'s database records will be removed. It will technically have the same effect for your website as if the plugin would be deactivated.', 'wp-asset-clean-up'); ?></p>

                    <p><?php _e('This action is usually taken if:', 'wp-asset-clean-up'); ?></p>
                    <ul>
                        <li><?php _e('You believe you have applied some changes (such as unloading the wrong CSS / JavaScript file(s)) that broke the website and you need a quick fix to make it work the way it used to. Note that for this option, you can also enable "Test Mode" from the plugin\'s settings which will only apply the changes to you (logged-in administrator), while the regular visitors will view the website as if Asset CleanUp is deactivated.', 'wp-asset-clean-up'); ?></li>
                        <li><?php _e('You want to uninstall Asset CleanUp and remove the traces left in the database (this is not the same thing as deactivating and activating the plugin again, as any changes applied would be preserved in this scenario)', 'wp-asset-clean-up'); ?></li>
                    </ul>
                </div>

                <?php
                wp_nonce_field('wpacu_tools_reset', 'wpacu_tools_reset_nonce');
                ?>

                <input type="hidden" name="wpacu-tools-reset" value="1" />
                <input type="hidden" name="wpacu-action-confirmed" id="wpacu-action-confirmed" value="" />

                <div id="wpacu-reset-submit-area">
                    <button name="submit"
                            disabled="disabled"
                            id="wpacu-reset-submit-btn"
                            class="button button-secondary"><?php esc_attr_e('Submit', 'wp-asset-clean-up'); ?></button>
                </div>
            <?php } elseif ($data['for'] === 'system_info') {
	            wp_nonce_field('wpacu_get_system_info', 'wpacu_get_system_info_nonce');
	            ?>
                <input type="hidden" name="wpacu-get-system-info" value="1" />

                <textarea disabled="disabled" style="color: rgba(51,51,51,1); background: #eee; white-space: pre; font-family: Menlo, Monaco, Consolas, 'Courier New', monospace; width: 80%; max-width: 100%;"
                          rows="20"><?php echo $data['system_info']; ?></textarea>

                <p><button name="submit"
                           id="wpacu-download-system-info-btn"
                           class="button button-primary"
                           style="font-size: 15px; line-height: 20px; padding: 3px 20px; height: 37px;">
                        <span style="padding-top: 1px;"
                              class="dashicons dashicons-download"></span>
                        <?php esc_attr_e('Download System Info For Support', 'wp-asset-clean-up'); ?>
                    </button>
                </p>
            <?php } ?>
		</form>

        <?php
        if ($data['for'] === 'storage') {
	        $currentStorageDirRel        = \WpAssetCleanUp\OptimiseAssets\OptimizeCommon::getRelPathPluginCacheDir();
	        $currentStorageDirFull       = WP_CONTENT_DIR . $currentStorageDirRel;
	        $currentStorageDirIsWritable = is_writable($currentStorageDirFull);
	        ?>
            <p><?php _e('If either of the Minify &amp; Combine CSS/JS features is enabled, a storage directory of the minified &amp; concatenated files is needed.', 'wp-asset-clean-up'); ?></p>
            <p><?php echo sprintf(__('On certain hosting platforms such as Pantheon, the number of writable directories is limited, in this case you have to change it to %s', 'wp-asset-clean-up'), '<code><strong>/uploads/asset-cleanup/</strong></code>'); ?></p>
            <p>
                <?php echo sprintf(
                    __('To change the relative directory, you have to add the following code to %s file within the root of your WordPress installation, where other constants are defined, above the line %s', 'wp-asset-clean-up'),
                '<em>wp-config.php</em>',
                    '<code><em>/* That\'s all, stop editing! Happy blogging. */</em></code>'
                );
                ?>
            </p>
            <p><code>define('WPACU_CACHE_DIR', '/uploads/asset-cleanup/');</code></p>
            <p><?php echo sprintf(
                    __('Note that the relative path is appended to %s', 'wp-asset-clean-up'),
                    '<em>'.WP_CONTENT_DIR.'/</em>'
                ); ?></p>
            <hr/>
            <p>
                <?php _e('Current storage directory', 'wp-asset-clean-up'); ?>: <code><?php echo WP_CONTENT_DIR; ?><strong><?php echo $currentStorageDirRel; ?></strong></code>
                &nbsp; <?php if ($currentStorageDirIsWritable) {
			        echo '<span style="color: green;"><span class="dashicons dashicons-yes"></span> '.__('writable', 'wp-asset-clean-up').'</span>';
		        } ?>
            </p>
	        <?php
	        if (! $currentStorageDirIsWritable) {
		        ?>
                <div class="wpacu-warning" style="width: 98%;">
                    <p style="margin: 0;">
                        <span style="color: #cc0000;" class="dashicons dashicons-warning"></span>
                        <?php echo sprintf(
                            __('The system detected the directory as non-writable, thus the minify &amp; combine CSS/JS files feature will not work. Please %smake it writable%s or raise a ticket with your hosting company about this matter.', 'wp-asset-clean-up'),
                            '<a href="https://wordpress.org/support/article/changing-file-permissions/">',
                            '</a>'
                        ); ?>
                    </p>
                </div>
	        <?php }
        }

        if ($data['for'] === 'import_export') {
            ?>
            <div id="wpacu-import-area" class="wpacu-export-import-area">
                <form id="wpacu-import-form"
                      action="<?php echo admin_url('admin.php?page='.WPACU_PLUGIN_ID.'_tools&wpacu_for='.$data['for']); ?>"
                      method="post"
                      enctype="multipart/form-data">
                    <p><label for="wpacu-import-file">Please choose the exported JSON file and upload it for import:</label></p>
                    <p><input required="required" type="file" id="wpacu-import-file" name="wpacu_import_file" accept="application/json" /></p>
                    <p><button type="submit"
                               class="button button-secondary"
                               style="font-size: 15px; line-height: 20px; padding: 3px 12px; height: 37px;">
                                <span style="padding-top: 1px;"
                                      class="dashicons dashicons-upload"></span>
					        <?php esc_attr_e('Import', 'wp-asset-clean-up'); ?>
                            <img class="wpacu-spinner" src="<?php site_url(); ?>/wp-includes/images/wpspin-2x.gif" alt="" />
                        </button> &nbsp;<small>* only .json extension allowed</small>
                    </p>
			        <?php wp_nonce_field('wpacu_do_import', 'wpacu_do_import_nonce'); ?>
                </form>

                <p><small><strong><span class="dashicons dashicons-warning"></span> Note:</strong> Make sure to properly test the pages of your website after you do the import to be sure the changes from the location you performed the export (e.g. staging) will work just as fine on the current server (e.g. live). The CSS/JS caching will be rebuilt after you're done with the import in case Minify/Combine CSS/JS is used.</small></p>
            </div>

            <hr />

            <div id="wpacu-export-area" class="wpacu-export-import-area">
                <form id="wpacu-export-form"
                      action="<?php echo admin_url('admin.php?page='.WPACU_PLUGIN_ID.'_tools&wpacu_for='.$data['for']); ?>"
                      method="post">
                    <p><label for="wpacu-export-selection">Please select what you would like to export:</label></p>
                    <p>
                        <select required="required" id="wpacu-export-selection" name="wpacu_export_for">
                            <option value="">Select an option first...</option>
                            <option value="settings">Settings</option>
                            <option value="everything">Everything</option>
                        </select>
                    </p>
                    <p><button type="submit"
                               class="button button-secondary"
                               style="font-size: 15px; line-height: 20px; padding: 3px 12px; height: 37px;">
                                <span style="padding-top: 1px;"
                                      class="dashicons dashicons-download"></span>
                            <?php esc_attr_e('Export', 'wp-asset-clean-up'); ?>
                        </button>
                    </p>
                    <?php wp_nonce_field('wpacu_do_export', 'wpacu_do_export_nonce'); ?>
                </form>
            </div>
        <?php
        }
        ?>
	</div>
</div>
