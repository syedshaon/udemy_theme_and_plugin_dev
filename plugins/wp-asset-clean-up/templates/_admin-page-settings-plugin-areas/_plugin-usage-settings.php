<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
	exit;
}

$tabIdArea = 'wpacu-setting-plugin-usage-settings';
$styleTabContent = ($selectedTabArea === $tabIdArea) ? 'style="display: table-cell;"' : '';

// [wpacu_lite]
$availableForPro = '<a class="go-pro-link-no-style" target="_blank" href="' . WPACU_PLUGIN_GO_PRO_URL . '?utm_source=plugin_usage_settings&utm_medium=assets_list_layout"><span class="wpacu-tooltip" style="width: 154px;">'.__('Click here to unlock it', 'wp-asset-clean-up').'!</span> <img style="opacity: 0.6;" width="20" height="20" src="'.WPACU_PLUGIN_URL.'/assets/icons/icon-lock.svg" valign="top" alt="" /></a>';
// [/wpacu_lite]
?>
<div id="<?php echo $tabIdArea; ?>" class="wpacu-settings-tab-content" <?php echo $styleTabContent; ?>>
    <h2 class="wpacu-settings-area-title"><?php _e('Plugin Usage Preferences', 'wp-asset-clean-up'); ?></h2>
    <p><?php _e('Choose how the assets are retrieved and whether you would like to see them within the Dashboard / Front-end view', 'wp-asset-clean-up'); ?>; <?php _e('Decide how the management list of CSS &amp; JavaScript files will show up and get sorted, depending on your preferences.', 'wp-asset-clean-up'); ?></p>
    <table class="wpacu-form-table">
        <tr valign="top">
            <th scope="row">
                <label for="wpacu_dashboard"><?php _e('Manage in the Dashboard', 'wp-asset-clean-up'); ?></label>
            </th>
            <td>
                <label class="wpacu_switch">
                    <input id="wpacu_dashboard"
                           type="checkbox"
						<?php echo (($data['dashboard_show'] == 1) ? 'checked="checked"' : ''); ?>
                           name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[dashboard_show]"
                           value="1" /> <span class="wpacu_slider wpacu_round"></span> </label>
                &nbsp;
                <?php _e('This will show the list of assets in a meta box on edit the post (any type) / page within the Dashboard', 'wp-asset-clean-up'); ?>
                <p><?php _e('The assets would be retrieved via AJAX call(s) that will fetch the post/page URL and extract all the styles &amp; scripts that are enqueued.', 'wp-asset-clean-up'); ?></p>
                <p><?php _e('Note that sometimes the assets list is not loading within the Dashboard. That could be because "mod_security" Apache module is enabled or some security plugins are blocking the AJAX request. If this option doesn\'t work, consider managing the list in the front-end view.', 'wp-asset-clean-up'); ?></p>

                <div id="wpacu-settings-assets-retrieval-mode"
					<?php if (! ($data['dashboard_show'] == 1)) { echo 'style="display: none;"'; } ?>>

                    <ul id="wpacu-dom-get-type-selections">
                        <li>
                            <label for="wpacu_dom_get_type"><?php _e('Select a retrieval way', 'wp-asset-clean-up'); ?>:</label>
                        </li>
                        <li>
                            <label>
                                <input class="wpacu-dom-get-type-selection"
                                       data-target="wpacu-dom-get-type-direct-info"
								       <?php if ($data['dom_get_type'] === 'direct') { ?>checked="checked"<?php } ?>
                                       type="radio" name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[dom_get_type]"
                                       value="direct" /> <?php _e('Direct', 'wp-asset-clean-up'); ?>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input class="wpacu-dom-get-type-selection"
                                       data-target="wpacu-dom-get-type-wp-remote-post-info"
								       <?php if ($data['dom_get_type'] === 'wp_remote_post') { ?>checked="checked"<?php } ?>
                                       type="radio" name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[dom_get_type]"
                                       value="wp_remote_post" /> WP Remote Post
                            </label>
                        </li>
                    </ul>

                    <div class="wpacu-clearfix" style="height: 0;"></div>

                    <ul id="wpacu-dom-get-type-infos">
                        <li <?php if ($data['dom_get_type'] !== 'direct') { ?>style="display: none;"<?php } ?>
                            class="wpacu-dom-get-type-info"
                            id="wpacu-dom-get-type-direct-info">
                            <strong><?php _e('Direct', 'wp-asset-clean-up'); ?></strong> - <?php _e('This one makes an AJAX call directly on the URL for which the assets are retrieved, then an extra WordPress AJAX call to process the list. Sometimes, due to some external factors (e.g. mod_security module from Apache, security plugin or the fact that non-http is forced for the front-end view and the AJAX request will be blocked), this might not work and another choice method might work better. This used to be the only option available, prior to version 1.2.4.4 and is set as default.', 'wp-asset-clean-up'); ?>
                        </li>
                        <li <?php if ($data['dom_get_type'] !== 'wp_remote_post') { ?>style="display: none;"<?php } ?>
                            class="wpacu-dom-get-type-info"
                            id="wpacu-dom-get-type-wp-remote-post-info">
                            <strong>WP Remote Post</strong> - <?php _e('It makes a WordPress AJAX call and gets the HTML source code through wp_remote_post(). This one is less likely to be blocked as it is made on the same protocol (no HTTP request from HTTPS). However, in some cases (e.g. a different load balancer configuration), this might not work when the call to fetch a domain\'s URL (your website) is actually made from the same domain.', 'wp-asset-clean-up'); ?>
                        </li>
                    </ul>
                </div>

                <div id="wpacu-settings-hide-meta-boxes">
                    <p><?php _e('Whether you have this option enabled or not, the post/page plugin\'s meta boxes will always be generated. If you wish to hide them completely for any reason (e.g. you rarely manage the assets and you want to reduce cluttering in the edit post/page area, especially if you do lots of edits), you can do so using the options below (<em>don\'t forget to uncheck them whenever you wish to manage the CSS/JS assets again</em>)', 'wp-asset-clean-up'); ?>:</p>
                    <ul>
                        <li><label for="wpacu-hide-assets-meta-box-checkbox"><input <?php echo (($data['hide_assets_meta_box'] == 1) ? 'checked="checked"' : ''); ?> id="wpacu-hide-assets-meta-box-checkbox" type="checkbox" name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[hide_assets_meta_box]" value="1" /> Hide "Asset CleanUp Pro: CSS &amp; JavaScript Manager" meta box</label></li>
                        <li><label for="wpacu-hide-options-meta-box-checkbox"><input <?php echo (($data['hide_options_meta_box'] == 1) ? 'checked="checked"' : ''); ?> id="wpacu-hide-options-meta-box-checkbox" type="checkbox" name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[hide_options_meta_box]" value="1" /> Hide "Asset CleanUp Pro: Options" meta box</label></li>
                    </ul>
                </div>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="wpacu_frontend"><?php _e('Manage in the Front-end', 'wp-asset-clean-up'); ?></label>
            </th>
            <td>
                <label class="wpacu_switch">
                    <input id="wpacu_frontend"
                           type="checkbox"
						<?php echo (($data['frontend_show'] == 1) ? 'checked="checked"' : ''); ?>
                           name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[frontend_show]"
                           value="1" /> <span class="wpacu_slider wpacu_round"></span> </label>
                &nbsp;
                If you are logged in, this will make the list of assets show below the page that you view (either home page, a post or a page).
                <p style="margin-top: 10px;">The area will be shown through the <code>wp_footer</code> action so in case you do not see the asset list at the bottom of the page, make sure the theme is using <a href="https://codex.wordpress.org/Function_Reference/wp_footer"><code>wp_footer()</code></a> function before the <code>&lt;/body&gt;</code> tag. Any theme that follows the standards should have it. If not, you will have to add it to make sure other plugins and code from functions.php will work fine.</p>

                <div id="wpacu-settings-frontend-exceptions" <?php if (! ($data['frontend_show'] == 1)) { echo 'style="display: none;"'; } ?>>
                    <div style="margin: 0 0 10px;"><label for="wpacu_frontend_show_exceptions"><span class="dashicons dashicons-info"></span> In some situations, you might want to avoid showing the CSS/JS list at the bottom of the pages (e.g. you're using a page builder such as Divi, you often load specific pages as an admin and you don't need to manage assets there or you do it rarely etc.). If that's the case, you can use the following textarea to prevent the list from showing up on pages where the <strong>URI contains</strong> the specified strings (<?php _e('one per line', 'wp-asset-clean-up'); ?>):</label></div>
                    <textarea id="wpacu_frontend_show_exceptions"
                              name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[frontend_show_exceptions]"
                              rows="5"
                              style="width: 100%;"><?php echo $data['frontend_show_exceptions']; ?></textarea>
                    <p><strong>Example:</strong> If the URI contains <strong>et_fb=1</strong> which triggers the front-end Divi page builder, then you can specify it in the list above (it's added by default) to prevent the asset list from showing below the page builder area.</p>
                </div>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="wpacu_assets_list_layout"><?php _e('Assets List Layout', 'wp-asset-clean-up'); ?></label>
            </th>
            <td>
                <label>
                    <select id="wpacu_assets_list_layout"
                            name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[assets_list_layout]">
                        <option <?php if ($data['assets_list_layout'] === 'by-location') { echo 'selected="selected"'; } ?> value="by-location"><?php _e('All Styles &amp; Scripts', 'wp-asset-clean-up'); ?> &#10230; <?php _e('Grouped by location (themes, plugins, core &amp; external)', 'wp-asset-clean-up'); ?></option>
                        <option <?php if ($data['assets_list_layout'] === 'by-position') { echo 'selected="selected"'; } ?> value="by-position"><?php _e('All Styles &amp; Scripts', 'wp-asset-clean-up'); ?> &#10230; <?php _e('Grouped by tag position: &lt;head&gt; &amp; &lt;body&gt;', 'wp-asset-clean-up'); ?></option>
                        <option <?php if ($data['assets_list_layout'] === 'by-parents') { echo 'selected="selected"'; } ?> value="by-parents"><?php _e('All Styles &amp; Scripts', 'wp-asset-clean-up'); ?> &#10230; <?php _e('Grouped by dependencies: Parents, Children, Independent', 'wp-asset-clean-up'); ?></option>
                        <option <?php if ($data['assets_list_layout'] === 'by-loaded-unloaded') { echo 'selected="selected"'; } ?> value="by-loaded-unloaded"><?php _e('All Styles &amp; Scripts', 'wp-asset-clean-up'); ?> &#10230; <?php _e('Grouped by loaded or unloaded status', 'wp-asset-clean-up'); ?></option>
                        <option <?php if (in_array($data['assets_list_layout'], array('two-lists', 'default'))) { echo 'selected="selected"'; } ?> value="two-lists"><?php _e('All Styles', 'wp-asset-clean-up'); ?> + <?php _e('All Scripts', 'wp-asset-clean-up'); ?> &#10230; <?php _e('Two lists', 'wp-asset-clean-up'); ?></option>
                        <option disabled="disabled" value="all"><?php _e('All Styles &amp; Scripts', 'wp-asset-clean-up'); ?> &#10230; <?php _e('One list', 'wp-asset-clean-up'); ?> (<?php _e('Pro Version', 'wp-asset-clean-up'); ?>)</option>
                    </select>
                </label>

                <div id="wpacu-assets-list-by-location-selected" style="margin: 10px 0; <?php if ($data['assets_list_layout'] !== 'by-location') { ?> display: none; <?php } ?>">
                    <div style="margin-bottom: 6px;"><?php _e('When list is grouped by location, keep the assets from each of the plugins in the following state', 'wp-asset-clean-up'); ?>:</div>
                    <ul class="assets_list_layout_areas_status_choices">
                        <li>
                            <label for="assets_list_layout_plugin_area_status_expanded">
                                <input id="assets_list_layout_plugin_area_status_expanded"
					                   checked="checked"
                                       type="radio"
                                       name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[assets_list_layout_plugin_area_status]"
                                       value="expanded"> <?php _e('Expanded', 'wp-asset-clean-up'); ?> (<?php _e('Default', 'wp-asset-clean-up'); ?>)
                            </label>
                        </li>
                        <li>
                            <label for="assets_list_layout_plugin_area_status_contracted">
                                <input id="assets_list_layout_plugin_area_status_contracted"
                                       type="radio"
                                       disabled="disabled"
                                       name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[assets_list_layout_plugin_area_status]"
                                       value="contracted"> <?php _e('Contracted', 'wp-asset-clean-up'); ?> (<?php _e('Pro Version', 'wp-asset-clean-up'); ?>) <?php echo $availableForPro; ?>
                            </label>
                        </li>
                    </ul>
                    <div class="clear"></div>
                </div>

                <div class="wpacu-clearfix"></div>

                <p style="margin-top: 10px;"><?php _e('These are various ways in which the list of assets that you will manage will show up. Depending on your preference, you might want to see the list of styles &amp; scripts first, or all together sorted in alphabetical order etc.', 'wp-asset-clean-up'); ?> <?php _e('Options that are disabled are available in the Pro version.', 'wp-asset-clean-up'); ?></p>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">
                <label for="wpacu_hide_from_admin_bar"><?php echo sprintf(__('Hide %s from the top Admin Bar', 'wp-asset-clean-up'), '"'.WPACU_PLUGIN_TITLE.'"'); ?></label>
            </th>
            <td>
                <label class="wpacu_switch">
                    <input id="wpacu_hide_from_admin_bar"
                           type="checkbox"
			            <?php echo (($data['hide_from_admin_bar'] == 1) ? 'checked="checked"' : ''); ?>
                           name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[hide_from_admin_bar]"
                           value="1" /> <span class="wpacu_slider wpacu_round"></span> </label>

                This is useful if you're not using too often the plugin's options from the top Admin Bar and wish to make up some space there.
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">
                <label><?php _e('On Assets List Layout Load, keep the expandable areas:', 'wp-asset-clean-up'); ?></label>
            </th>
            <td>
                <ul class="assets_list_layout_areas_status_choices">
                    <li>
                        <label for="assets_list_layout_areas_status_expanded">
                            <input id="assets_list_layout_areas_status_expanded"
							       <?php if (! $data['assets_list_layout_areas_status'] || $data['assets_list_layout_areas_status'] === 'expanded') { ?>checked="checked"<?php } ?>
                                   type="radio"
                                   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[assets_list_layout_areas_status]"
                                   value="expanded"> <?php _e('Expanded', 'wp-asset-clean-up'); ?> (<?php _e('Default', 'wp-asset-clean-up'); ?>)
                        </label>
                    </li>
                    <li>
                        <label for="assets_list_layout_areas_status_contracted">
                            <input id="assets_list_layout_areas_status_contracted"
							       <?php if ($data['assets_list_layout_areas_status'] === 'contracted') { ?>checked="checked"<?php } ?>
                                   type="radio"
                                   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[assets_list_layout_areas_status]"
                                   value="contracted"> <?php _e('Contracted', 'wp-asset-clean-up'); ?>
                        </label>
                    </li>
                </ul>
                <div class="wpacu-clearfix"></div>

                <p><?php _e('Sometimes, when you have plenty of elements in the edit page, you might want to contract the list of assets when you\'re viewing the page as it will save space. This can be a good practice, especially when you finished optimising the pages and you don\'t want to keep seeing the long list of files every time you edit a page.', 'wp-asset-clean-up'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label><?php _e('On Assets List Layout Load, keep "Inline code associated with this handle" area', 'wp-asset-clean-up'); ?>:</label>
            </th>
            <td>
                <ul class="assets_list_inline_code_status_choices">
                    <li>
                        <label for="assets_list_inline_code_status_expanded">
                            <input id="assets_list_inline_code_status_expanded"
							       <?php if (! $data['assets_list_inline_code_status'] || $data['assets_list_inline_code_status'] === 'expanded') { ?>checked="checked"<?php } ?>
                                   type="radio"
                                   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[assets_list_inline_code_status]"
                                   value="expanded"> <?php _e('Expanded (Default)', 'wp-asset-clean-up'); ?>
                        </label>
                    </li>
                    <li>
                        <label for="assets_list_inline_code_status_contracted">
                            <input id="assets_list_inline_code_status_contracted"
							       <?php if ($data['assets_list_inline_code_status'] === 'contracted') { ?>checked="checked"<?php } ?>
                                   type="radio"
                                   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[assets_list_inline_code_status]"
                                   value="contracted"> <?php _e('Contracted', 'wp-asset-clean-up'); ?>
                        </label>
                    </li>
                </ul>
                <div class="wpacu-clearfix"></div>

                <p><?php echo sprintf(
                        __('Some assets (CSS &amp; JavaScript) have inline code associate with them and often, they are quite large, making the asset row bigger and requiring you to scroll more until you reach a specific area. By setting it to "%s", it will hide all the inline code by default and you can view it by clicking on the toggle link inside the asset row.', 'wp-asset-clean-up'),
                        __('Contracted', 'wp-asset-clean-up')
                    ); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="setting_title">
                <label><?php _e('Input Fields Style', 'wp-asset-clean-up'); ?>:</label>
                <p class="wpacu_subtitle"><small><em><?php _e('How would you like to view the checkboxes / selectors?', 'wp-asset-clean-up'); ?></em></small></p>
                <p class="wpacu_read_more"><a href="https://assetcleanup.com/docs/?p=95" target="_blank"><?php _e('Read More', 'wp-asset-clean-up'); ?></a></p>
            </th>
            <td>
                <ul class="input_style_choices">
                    <li>
                        <label for="input_style_enhanced">
                            <input id="input_style_enhanced"
							       <?php if (! $data['input_style'] || $data['input_style'] === 'enhanced') { ?>checked="checked"<?php } ?>
                                   type="radio"
                                   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[input_style]"
                                   value="enhanced"> <?php _e('Enhanced iPhone Style (Default)', 'wp-asset-clean-up'); ?>
                        </label>
                    </li>
                    <li>
                        <label for="input_style_standard">
                            <input id="input_style_standard"
							       <?php if ($data['input_style'] === 'standard') { ?>checked="checked"<?php } ?>
                                   type="radio"
                                   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[input_style]"
                                   value="standard"> <?php _e('Standard', 'wp-asset-clean-up'); ?>
                        </label>
                    </li>
                </ul>
                <div class="wpacu-clearfix"></div>

                <p><?php _e('In case you prefer standard HTML checkboxes instead of the enhanced CSS3 iPhone style ones (on &amp; off) or you need a simple HTML layout in case you\'re using a screen reader software (e.g. for people with disabilities) which requires standard/clean HTML code, then you can choose "Standard" as an option.', 'wp-asset-clean-up'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="wpacu_hide_core_files"><?php _e('Hide WordPress Core Files From The Assets List?', 'wp-asset-clean-up'); ?></label>
            </th>
            <td>
                <label class="wpacu_switch">
                    <input id="wpacu_hide_core_files"
                           type="checkbox"
						<?php echo (($data['hide_core_files'] == 1) ? 'checked="checked"' : ''); ?>
                           name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[hide_core_files]"
                           value="1" /> <span class="wpacu_slider wpacu_round"></span> </label>
                &nbsp;
                <?php echo sprintf(__('WordPress Core Files have handles such as %s', 'wp-asset-clean-up'), "'jquery', 'wp-embed', 'comment-reply', 'dashicons'"); ?> etc.
                <p style="margin-top: 10px;"><?php _e('They should only be unloaded by experienced developers when they are convinced that are not needed in particular situations. It\'s better to leave them loaded if you have any doubts whether you need them or not. By hiding them in the assets management list, you will see a smaller assets list (easier to manage) and you will avoid updating by mistake any option (unload, async, defer) related to any core file.', 'wp-asset-clean-up'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="wpacu_allow_usage_tracking"><?php _e('Allow Usage Tracking', 'wp-asset-clean-up'); ?></label>
            </th>
            <td>
                <label class="wpacu_switch">
                    <input id="wpacu_allow_usage_tracking"
                           type="checkbox"
					    <?php echo (($data['allow_usage_tracking'] == 1) ? 'checked="checked"' : ''); ?>
                           name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[allow_usage_tracking]"
                           value="1" /> <span class="wpacu_slider wpacu_round"></span> </label>
                &nbsp;
                Allow Asset CleanUp to anonymously track plugin usage in order to help us make the plugin better? No sensitive or personal data is collected. <span style="color: #004567;" class="dashicons dashicons-info"></span> <a id="wpacu-show-tracked-data-list-modal-target" href="#wpacu-show-tracked-data-list-modal">What kind of data will be sent for the tracking?</a>
            </td>
        </tr>
    </table>
</div>

<style type="text/css">
    #wpacu-show-tracked-data-list-modal {
        margin: 14px 0 0;
    }

    #wpacu-show-tracked-data-list-modal .table-striped {
        border: none;
        border-spacing: 0;
    }

    #wpacu-show-tracked-data-list-modal .table-striped tbody tr:nth-of-type(even) {
        background-color: rgba(0, 143, 156, 0.05);
    }

    #wpacu-show-tracked-data-list-modal .table-striped tbody tr td:first-child {
        font-weight: bold;
    }
</style>

<div id="wpacu-show-tracked-data-list-modal" class="wpacu-modal" style="padding-top: 100px;">
    <div class="wpacu-modal-content" style="max-width: 800px;">
        <span class="wpacu-close">&times;</span>
        <p>The following information will be sent to us and it would be helpful to make the plugin better.</p>
        <p>e.g. see which themes and plugins are used the most and make the plugin as compatible as possible with them, see the most used plugin settings, determine the most used languages after English which is helpful to prioritise translations etc.</p>
        <?php
        $pluginTrackingClass = new \WpAssetCleanUp\PluginTracking();
        $pluginTrackingClass->setup_data();
        $pluginTrackingClass::showSentInfoDataTable($pluginTrackingClass->data);
        ?>
    </div>
</div>