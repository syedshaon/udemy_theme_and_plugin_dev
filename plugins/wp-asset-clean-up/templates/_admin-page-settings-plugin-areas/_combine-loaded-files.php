<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
	exit;
}

if (! defined('WPACU_USE_MODAL_BOX')) {
	define('WPACU_USE_MODAL_BOX', true);
}

$tabIdArea = 'wpacu-setting-combine-loaded-files';
$styleTabContent = ($selectedTabArea === $tabIdArea) ? 'style="display: table-cell;"' : '';

$isOptimizeCssEnabledByOtherParty = \WpAssetCleanUp\Misc::isOptimizeCssEnabledByOtherParty();
$isOptimizeJsEnabledByOtherParty  = \WpAssetCleanUp\Misc::isOptimizeJsEnabledByOtherParty();
?>
<div id="<?php echo $tabIdArea; ?>" class="wpacu-settings-tab-content" <?php echo $styleTabContent; ?>>
	<h2 class="wpacu-settings-area-title"><?php echo __('Combine loaded CSS &amp; JavaScript files into fewer files', 'wp-asset-clean-up'); ?></h2>

    <div style="line-height: 22px; background: #f8f8f8; border-left: 4px solid #008f9c; padding: 10px; margin: 0 0 15px;">
        <strong><?php _e('NOTE', 'wp-asset-clean-up'); ?>:</strong> <?php echo __('Concatenating assets is no longer a recommended practice in HTTP/2', 'wp-asset-clean-up'); ?>. &nbsp; <span style="color: #0073aa;" class="dashicons dashicons-info"></span> <a id="wpacu-http2-info-target" href="#wpacu-http2-info"><?php _e('Read more', 'wp-asset-clean-up'); ?></a> &nbsp;|&nbsp; <a target="_blank" href="https://tools.keycdn.com/http2-test"><?php _e('Verify if your server has HTTP/2 support', 'wp-asset-clean-up'); ?></a>
    </div>

	<table class="wpacu-form-table">
		<tr valign="top">
			<th scope="row" class="setting_title">
				<label for="wpacu_combine_loaded_css_enable"><?php _e('Combine loaded CSS (Stylesheets) into fewer files', 'wp-asset-clean-up'); ?></label>
				<p class="wpacu_subtitle"><small><em><?php _e('Helps reducing the number of HTTP Requests even further', 'wp-asset-clean-up'); ?></em></small></p>
			</th>
			<td>
				<label class="wpacu_switch <?php if (! empty($isOptimizeCssEnabledByOtherParty)) { echo 'wpacu_disabled'; } ?>">
					<input id="wpacu_combine_loaded_css_enable"
					       type="checkbox"
						<?php
						if (! empty($isOptimizeCssEnabledByOtherParty)) {
							echo 'disabled="disabled"';
						} else {
						    echo (in_array($data['combine_loaded_css'], array('for_admin', 'for_all', 1)) ? 'checked="checked"' : '');
						}
						?>
						   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[combine_loaded_css]"
						   value="1" /> <span class="wpacu_slider wpacu_round"></span> </label>

                &nbsp;<small>* if <code style="font-size: inherit;"><?php echo '/'.str_replace(ABSPATH, '', WP_CONTENT_DIR) . \WpAssetCleanUp\OptimiseAssets\OptimizeCommon::getRelPathPluginCacheDir(); ?></code> directory is not writable for some reason, this feature will not work; requires the DOMDocument XML DOM Parser to be enabled in PHP (which it is by default) for maximum performance</small>
				&nbsp;
				<?php
				if (! empty($isOptimizeCssEnabledByOtherParty)) {
					?>
                    <div style="border-left: 4px solid green; background: #f2faf2; padding: 10px; margin-top: 10px;">
                        <ul style="margin: 0;">
                            <li>This option is not available as optimize/minify stylesheets (CSS) is already enabled in the following plugins: <strong><?php echo implode(', ', $isOptimizeCssEnabledByOtherParty); ?></strong></li>
                            <li><?php echo WPACU_PLUGIN_TITLE; ?> works together with the mentioned plugin(s). Eliminate the bloat first via <a href="<?php echo admin_url('admin.php?page=wpassetcleanup_assets_manager'); ?>">CSS & JAVASCRIPT LOAD MANAGER</a>, then concatenate (if necessary) the remaining CSS with any plugin you prefer.</li>
                        </ul>
                    </div>
					<?php
				}
				?>

				<div id="combine_loaded_css_info_area" <?php if (empty($isOptimizeCssEnabledByOtherParty) && in_array($data['combine_loaded_css'], array('for_admin', 'for_all', 1))) { ?> style="opacity: 1;" <?php } else { ?>style="opacity: 0.4;"<?php } ?>>
					<p style="margin-top: 8px; padding: 10px; background: #f2faf2;">
						<label for="combine_loaded_css_for_admin_only_checkbox">
							<input id="combine_loaded_css_for_admin_only_checkbox"
								<?php echo ((in_array($data['combine_loaded_css_for_admin_only'], array('for_admin', 1))
								             || $data['combine_loaded_css'] === 'for_admin')
									? 'checked="checked"' : ''); ?>
								   type="checkbox"
								   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[combine_loaded_css_for_admin_only]"
								   value="1" />
							<?php _e('Apply combination only for logged-in administrator (for debugging purposes)', 'wp-asset-clean-up'); ?>
						</label>
					</p>

                    <div id="wpacu_combine_loaded_css_exceptions_area">
                        <div style="margin: 0 0 6px;"><?php _e('Do not combine the CSS files matching the patterns below', 'wp-asset-clean-up'); ?> (<?php _e('one per line', 'wp-asset-clean-up'); ?>):</div>
                        <label for="combine_loaded_css_exceptions">
                                    <textarea style="width: 100%;"
                                              rows="4"
                                              id="combine_loaded_css_exceptions"
                                              name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[combine_loaded_css_exceptions]"><?php echo $data['combine_loaded_css_exceptions']; ?></textarea>
                        </label>

                        <p>Pattern Examples (you don't have to add the full URL, as it's recommended to use relative paths):</p>
                        <code>/wp-includes/css/dashicons.min.css<br />/wp-includes/css/admin-bar.min.css<br />/wp-content/plugins/plugin-title/css/(.*?).css</code>

                        <div style="margin-top: 15px; margin-bottom: 0;"><hr /></div>
                    </div>

                    <p>This scans the remaining CSS files (left after cleaning up the unnecessary ones) from the <code>&lt;head&gt;</code> and <code>&lt;body&gt;</code> locations and combines them into ~2 files (one in each location). To be 100% sure everything works fine after activation, consider enabling this feature only for logged-in administrator, so only you can see the updated page. If all looks good, you can later uncheck the option to apply the feature to everyone else.</p>
                    <p style="margin-bottom: -7px;"><span style="color: #ffc107;" class="dashicons dashicons-lightbulb"></span> The following stylesheets are not included in the combined CSS file for maximum performance:</p>
                    <ul style="list-style: disc; margin-left: 35px; margin-bottom: 0;">
                        <li>Have any <a target="_blank" href="https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content">preloading added to them</a> via <code>rel="preload"</code> will not be combined as they have priority in loading and shouldn't be mixed with the rest of the CSS.</li>
                        <li style="margin-bottom: 0;">Have a different media attribute value than "screen" and "all". If the "print" attribute is there, it is for a reason and it's not added together with "all".</li>
                    </ul>
                    <p style="margin-bottom: -7px; margin-top: 20px;"><span style="color: #ffc107;" class="dashicons dashicons-lightbulb"></span> This feature will not work <strong>IF</strong>:</p>
                    <ul style="margin-left: 35px; list-style: disc;">
                        <li>"Test Mode" is enabled, this feature will not work for the guest users, even if "Yes, for everyone" is chosen as "Test Mode" purpose is to make the plugin as inactive for non logged-in administrators for ultimate debugging.</li>
                        <li>The URL has query strings (e.g. an URL such as //www.yourdomain.com/product/title-here/?param=1&amp;param_two=value_here)</li>
                    </ul>
                </div>
            </td>
		</tr>

		<tr valign="top">
			<th scope="row" class="setting_title">
				<label for="wpacu_combine_loaded_js_enable"><?php _e('Combine loaded JS (JavaScript) into fewer files', 'wp-asset-clean-up'); ?></label>
				<p class="wpacu_subtitle"><small><em><?php _e('Helps reducing the number of HTTP Requests even further', 'wp-asset-clean-up'); ?></em></small></p>
			</th>
			<td>
				<label class="wpacu_switch <?php if (! empty($isOptimizeJsEnabledByOtherParty)) { echo 'wpacu_disabled'; } ?>">
					<input id="wpacu_combine_loaded_js_enable"
					       type="checkbox"
						<?php
						if (! empty($isOptimizeJsEnabledByOtherParty)) {
							echo 'disabled="disabled"';
						} else {
						    echo (in_array($data['combine_loaded_js'], array('for_admin', 'for_all', 1)) ? 'checked="checked"' : '');
						}
						?>
						   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[combine_loaded_js]"
						   value="1" /> <span class="wpacu_slider wpacu_round"></span> </label>

				&nbsp;<small>* if <code style="font-size: inherit;"><?php echo '/'.str_replace(ABSPATH, '', WP_CONTENT_DIR) . \WpAssetCleanUp\OptimiseAssets\OptimizeCommon::getRelPathPluginCacheDir(); ?></code> directory is not writable for some reason, this feature will not work; requires the DOMDocument XML DOM Parser to be enabled in PHP (which it is by default) for maximum performance</small>

				<?php
				if (! empty($isOptimizeJsEnabledByOtherParty)) {
					?>
                    <div style="border-left: 4px solid green; background: #f2faf2; padding: 10px; margin-top: 10px;">
                        <ul style="margin: 0;">
                            <li>This option is not available as optimize/minify JavaScript (JS) is already enabled in the following plugins: <strong><?php echo implode(', ', $isOptimizeJsEnabledByOtherParty); ?></strong>.</li>
                            <li><?php echo WPACU_PLUGIN_TITLE; ?> works together with the mentioned plugin(s). Eliminate the bloat first via <a href="<?php echo admin_url('admin.php?page=wpassetcleanup_assets_manager'); ?>">CSS & JAVASCRIPT LOAD MANAGER</a>, then concatenate (if necessary) the JS using any plugin you prefer.</li>
                        </ul>
                    </div>
					<?php
				}
				?>

				<div id="combine_loaded_js_info_area" <?php if (empty($isOptimizeJsEnabledByOtherParty) && in_array($data['combine_loaded_js'], array('for_admin', 'for_all', 1))) { ?> style="opacity: 1;" <?php } else { ?>style="opacity: 0.4;"<?php } ?>>
					<p style="margin-top: 8px; padding: 10px; background: #f2faf2;">
						<label for="combine_loaded_js_for_admin_only_checkbox">
							<input id="combine_loaded_js_for_admin_only_checkbox"
								<?php echo ((in_array($data['combine_loaded_js_for_admin_only'], array('for_admin', 1))
								             || $data['combine_loaded_js'] === 'for_admin')
									? 'checked="checked"' : ''); ?>
								   type="checkbox"
								<?php
								if (! empty($isOptimizeJsEnabledByOtherParty)) {
									echo 'disabled="disabled"';
								}
								?>
								   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[combine_loaded_js_for_admin_only]"
								   value="1" />
							<?php _e('Apply combination only for logged-in administrator', 'wp-asset-clean-up'); ?> (<?php _e('for debugging purposes', 'wp-asset-clean-up'); ?>)
						</label>
					</p>

                    <p style="padding: 10px; background: #f2faf2;">
                        <label for="wpacu_combine_loaded_js_defer_body_checkbox">
                            <input id="wpacu_combine_loaded_js_defer_body_checkbox"
								<?php echo (($data['combine_loaded_js_defer_body'] == 1) ? 'checked="checked"' : ''); ?>
                                   type="checkbox"
	                            <?php
	                            if (! empty($isOptimizeJsEnabledByOtherParty)) {
		                            echo 'disabled="disabled"';
	                            }
	                            ?>
                                   name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[combine_loaded_js_defer_body]"
                                   value="1" />
                            Defer loading JavaScript combined files from <code>&lt;body&gt;</code> (applies <code>defer="defer"</code> attribute to the combined script tags)
                        </label>
                    </p>

                    <hr />

                    <div id="wpacu_combine_loaded_js_exceptions_area">
                        <div style="margin: 0 0 6px;"><?php _e('Do not combine the JavaScript files matching the patterns below (one per line, see pattern examples below)', 'wp-asset-clean-up'); ?>:</div>
                        <label for="combine_loaded_js_exceptions">
                                    <textarea style="width: 100%;"
                                              rows="4"
                                              id="combine_loaded_js_exceptions"
                                              name="<?php echo WPACU_PLUGIN_ID . '_settings'; ?>[combine_loaded_js_exceptions]"><?php echo $data['combine_loaded_js_exceptions']; ?></textarea>
                        </label>

                        <p><?php _e('Pattern Examples (you don\'t have to add the full URL, as it\'s recommended to use relative paths)', 'wp-asset-clean-up'); ?>:</p>
                        <code>/wp-includes/js/admin-bar.min.js<br />/wp-includes/js/masonry.min.js<br />/wp-content/plugins/plugin-title/js/(.*?).js</code>

                        <div style="margin-top: 15px; margin-bottom: 0;"><hr /></div>
                    </div>

					<!--
                               -->
					<p>
						<?php _e('This results in as less JS combination groups as possible (this combines all JS files into 2/3 files, keeping their HEAD and BODY locations and most of the inline script tags before them for maximum compatibility)', 'wp-asset-clean-up'); ?> - <a id="wpacu-combine-js-method-info-target" href="#wpacu-combine-js-method-info"><?php _e('Read more', 'wp-asset-clean-up'); ?></a>
					</p>

					<hr />

					<div class="clearfix"></div>

					<p><span style="color: #ffc107;" class="dashicons dashicons-lightbulb"></span> To be 100% sure everything works fine after activation, consider using the checkbox option above to apply the changes only for logged-in administrator (yourself). If all looks good, you can later uncheck so the changes will apply to everyone.</p>

					<hr />

					<p style="margin-bottom: -7px;"><span style="color: #ffc107;" class="dashicons dashicons-lightbulb"></span> Any scripts having "defer" or "async" attributes (which are there for a reason) will not be combined together with other render-blocking scripts.</p>

					<p style="margin-bottom: -7px; margin-top: 20px;"><span style="color: #ffc107;" class="dashicons dashicons-lightbulb"></span> This feature will not work <strong>IF</strong>:</p>
					<ul style="list-style: disc; margin-left: 35px; margin-bottom: 0;">
						<li>"Test Mode" is enabled and a guest (not logged-in) user visits the page, as the feature's ultimate purpose is to make the plugin inactive for non logged-in administrators for ultimate debugging.</li>
						<li>The URL has query strings (e.g. an URL such as //www.yourdomain.com/product/title-here/?param=1&amp;param_two=value_here)</li>
					</ul>
				</div>

				<!--
				-->
				<div id="wpacu-combine-js-method-info" class="wpacu-modal">
					<div class="wpacu-modal-content">
						<span class="wpacu-close">&times;</span>
						<h2><?php _e('How are the JavaScript files combined?', 'wp-asset-clean-up'); ?></h2>
						<p style="margin-top: 0;"><?php _e('The plugin scans the remaining JavaScript files (left after cleaning up the unnecessary ones) from the <code>&lt;head&gt;</code> and <code>&lt;body&gt;</code> locations and combines them into one file per each location.', 'wp-asset-clean-up'); ?></p>
						<p><?php _e('Any inline JavaScript code associated with the combined scripts, will not be altered or moved in any way.', 'wp-asset-clean-up'); ?></p>
						<p><strong><?php _e('Example', 'wp-asset-clean-up'); ?>:</strong> <?php _e('If you have 5 JS files (including jQuery library) loading in the <code>&lt;head&gt;</code> location and 7 JS files loading in <code>&lt;body&gt;</code> location, you will end up with a total of 3 JS files: jQuery library &amp; jQuery Migrate (they are not combined together with other JS files for maximum performance) in 1 file and the 2 JS files for HEAD and BODY, respectively.', 'wp-asset-clean-up'); ?></p>
					</div>
				</div>
			</td>
		</tr>
	</table>
</div>

<div id="wpacu-http2-info" class="wpacu-modal" style="padding-top: 100px;">
    <div class="wpacu-modal-content" style="max-width: 800px;">
        <span class="wpacu-close">&times;</span>
        <h2 style="margin-top: 5px;"><?php _e('Combining CSS &amp; JavaScript files in HTTP/2 protocol', 'wp-asset-clean-up'); ?></h2>
        <p><?php _e('While it\'s still a good idea to combine assets into fewer (or only one) files in HTTP/1 (since you are restricted to the number of open connections), doing the same in HTTP/2 is no longer a performance optimization due to the ability to transfer multiple small files simultaneously without much overhead.', 'wp-asset-clean-up'); ?></p>

        <hr />

        <p><?php _e('In HTTP/2 some of the issues that were addressed are', 'wp-asset-clean-up'); ?>:</p>
        <ul>

            <li><strong>Multiplexing</strong>: <?php _e('allows concurrent requests across a single TCP connection', 'wp-asset-clean-up'); ?></li>
            <li><strong>Server Push</strong>: <?php _e('whereby a server can push vital resources to the browser before being asked for them.', 'wp-asset-clean-up'); ?></li>
        </ul>

        <hr />

        <p><?php _e('Since HTTP requests are loaded concurrently in HTTP/2, it\'s better to only serve the files that your visitors need and don\'t worry much about concatenation.', 'wp-asset-clean-up'); ?></p>
        <p><?php _e('Note that page speed testing tools such as PageSpeed Insights, YSlow, Pingdom Tools or GTMetrix still recommend combining CSS/JS files because they haven\'t updated their recommendations based on HTTP/1 or HTTP/2 protocols so you should take into account the actual load time, not the performance grade.', 'wp-asset-clean-up'); ?></p>

        <hr />

        <p style="margin-bottom: 12px;"><?php _e('If you do decide to move on with the concatenation (which at least would improve the GTMetrix performance grade from a cosmetic point of view), please remember to <strong>test thoroughly</strong> the pages that have the assets combined (pay attention to any JavaScript errors in the browser\'s console which is accessed via right click &amp; "Inspect") as, in rare cases, due to the order in which the scripts were loaded and the way their code was written, it could break some functionality.', 'wp-asset-clean-up'); ?></p>
    </div>
</div>