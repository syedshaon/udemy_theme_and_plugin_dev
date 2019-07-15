<?php
/*
 * The file is included from _asset-style-rows.php
*/
if (! isset($data)) {
	exit; // no direct access
}

$inlineCodeStatus = $data['plugin_settings']['assets_list_inline_code_status'];
$isCoreFile       = isset($data['row']['obj']->wp) && $data['row']['obj']->wp;
$hideCoreFiles    = $data['plugin_settings']['hide_core_files'];
$isBulkUnloaded   = $data['row']['global_unloaded'] || $data['row']['is_post_type_unloaded'];

// Does it have "children"? - other CSS file(s) depending on it
$childHandles     = isset($data['all_deps']['styles'][$data['row']['obj']->handle]) ? $data['all_deps']['styles'][$data['row']['obj']->handle] : array();
sort($childHandles);
?>
<tr class="wpacu_asset_row <?php echo $data['row']['class']; ?>" style="<?php if ($isCoreFile && $hideCoreFiles) { echo 'display: none;'; } ?>">
    <td valign="top">
        <p class="wpacu_handle">
            <label for="style_<?php echo $data['row']['obj']->handle; ?>"><?php _e('Handle:', 'wp-asset-clean-up'); ?> <strong><span style="color: green;"><?php echo $data['row']['obj']->handle; ?></span></strong></label>
	        <?php if (isset($data['view_by_location'])) { echo '&nbsp;<em>* Stylesheet (.css)</em>'; } ?>
            <?php if ($isCoreFile && ! $hideCoreFiles) { ?>
				<span class="dashicons dashicons-warning wordpress-core-file"><span class="wpacu-tooltip">WordPress Core File<br /><?php _e('Not sure if needed or not? In this case, it\'s better to leave it loaded to avoid breaking the website.', 'wp-asset-clean-up'); ?></span></span>
				<?php
			}
			?>
		</p>

	    <?php
        if (! empty($childHandles)) {
	        $ignoreChild = (isset($data['ignore_child']['styles'][$data['row']['obj']->handle]) && $data['ignore_child']['styles'][$data['row']['obj']->handle]);
            ?>
		    <p>
                <em style="font-size: 85%;">
                    <span style="color: #0073aa; width: 19px; height: 19px; vertical-align: middle;" class="dashicons dashicons-info"></span>
                    This file has other CSS "children" files depending on it. By unloading this CSS, the following "children" files will be unloaded too:
                    <span style="color: green; font-weight: 600;">
                        <?php echo implode(', ', $childHandles); ?>
                    </span>
                </em>
                <label for="style_<?php echo $data['row']['obj']->handle; ?>_ignore_children">
                    <input type="hidden" name="wpacu_ignore_child[styles][<?php echo $data['row']['obj']->handle; ?>]" value="" />
                    &#10230; <input id="style_<?php echo $data['row']['obj']->handle; ?>_ignore_children"
                                    type="checkbox"
                                    <?php if ($ignoreChild) { ?>checked="checked"<?php } ?>
                                    name="wpacu_ignore_child[styles][<?php echo $data['row']['obj']->handle; ?>]"
                                    value="1" /> <small><?php _e('Ignore dependency rule and keep the "children" loaded', 'wp-asset-clean-up'); ?></small>
                </label>
            </p>
        <?php
        }

	    $ver = (isset($data['row']['obj']->ver) && trim($data['row']['obj']->ver)) ? $data['row']['obj']->ver : $data['wp_version'];

        if (isset($data['row']['obj']->src, $data['row']['obj']->srcHref) && $data['row']['obj']->src && $data['row']['obj']->srcHref) {
            $relSrc = str_replace(site_url(), '', $data['row']['obj']->src);

            if (isset($data['row']['obj']->baseUrl)) {
                $relSrc = str_replace($data['row']['obj']->baseUrl, '/', $data['row']['obj']->src);
            }

            $appendAfterSrcHref = (strpos($data['row']['obj']->srcHref, '?') === false) ? '?' : '&';

	        $isCssPreload = (isset($data['preloads']['styles'][$data['row']['obj']->handle]) && $data['preloads']['styles'][$data['row']['obj']->handle])
		        ? $data['preloads']['styles'][$data['row']['obj']->handle]
		        : false;
            ?>
            <p>
                <?php _e('Source:', 'wp-asset-clean-up'); ?> <a target="_blank" style="color: green;" href="<?php echo $data['row']['obj']->srcHref . $appendAfterSrcHref . 'ver='.$ver; ?>"><?php echo $relSrc; ?></a>
                &nbsp; &#10230; &nbsp;
                <input type="hidden" name="wpacu_preloads[styles][<?php echo $data['row']['obj']->handle; ?>]" value="" />
                <label for="wpacu_css_preload_<?php echo $data['row']['obj']->handle; ?>">
                    <input <?php if ($isCssPreload) { echo 'checked="checked"'; } ?> id="wpacu_css_preload_<?php echo $data['row']['obj']->handle; ?>" type="checkbox" name="wpacu_preloads[styles][<?php echo $data['row']['obj']->handle; ?>]" value="1" /> Preload (if kept loaded) <small>* applies site-wide</small></label> <small><a style="text-decoration: none; color: inherit;" target="_blank" href="https://developers.google.com/web/tools/lighthouse/audits/preload"><span class="dashicons dashicons-editor-help"></span></a></small>
            </p>
	    <?php
        }

        // Any tips?
        if (isset($data['tips']['css'][$data['row']['obj']->handle]) && ($assetTip = $data['tips']['css'][$data['row']['obj']->handle])) {
            ?>
            <div class="tip"><strong>Tip:</strong> <?php echo $assetTip; ?></div>
            <?php
        }

		$extraInfo = array();

	    if (! empty($data['row']['obj']->deps)) {
		    $depsOutput = '';

		    $dependsOnText = (count($data['row']['obj']->deps) === 1)
			    ? __('"Child" of one "parent" CSS file:')
			    : sprintf(__('"Child" of %s CSS "parent" files:', 'wp-asset-clean-up'), count($data['row']['obj']->deps));

		    $depsOutput .= $dependsOnText.' ';

		    foreach ($data['row']['obj']->deps as $depHandle) {
			    $depsOutput .= '<span style="color: green; font-weight: 300;">'.$depHandle.'</span>, ';
		    }

		    $depsOutput = rtrim($depsOutput, ', ');

		    $extraInfo[] = $depsOutput;
	    }

	    $extraInfo[] = __('Version:', 'wp-asset-clean-up').' '.$ver;

        if (isset($data['row']['obj']->position) && $data['row']['obj']->position !== '') {
	        $extraInfo[] = __('Position:', 'wp-asset-clean-up') . ' ' . (( $data['row']['obj']->position === 'head') ? 'HEAD' : 'BODY') . '<a class="go-pro-link-no-style" href="' . WPACU_PLUGIN_GO_PRO_URL . '?utm_source=manage_asset&utm_medium=change_css_position"><span class="wpacu-tooltip" style="width: 300px; margin-left: -146px;">Upgrade to Pro and change the location<br />of the CSS file (e.g. to BODY to reduce render-blocking or to HEAD for very early triggering)</span><img width="20" height="20" src="' . WPACU_PLUGIN_URL . '/assets/icons/icon-lock.svg" valign="top" alt="" /> Change it?</a>';
        }

        // [wpacu_lite]
        if (isset($data['row']['obj']->src) && $data['row']['obj']->src) {
	        $extraInfo[] = __('File Size:', 'wp-asset-clean-up') . ' <a href="' . WPACU_PLUGIN_GO_PRO_URL . '?utm_source=manage_asset&utm_medium=file_size" class="go-pro-link-no-style"><span class="wpacu-tooltip">Upgrade to Pro and unlock all features</span><img width="20" height="20" src="' . WPACU_PLUGIN_URL . '/assets/icons/icon-lock.svg" valign="top" alt="" /> Pro Version</a>';
        }
        // [/wpacu_lite]

        if (! empty($extraInfo)) {
	        echo '<p>'.implode(' &nbsp;/&nbsp; ', $extraInfo).'</p>';
        }
        ?>

        <div <?php if (! $isBulkUnloaded) { ?>class="wrap_bulk_unload_options"<?php } ?>>
		    <div class="wpacu_asset_options_wrap">
			<ul class="wpacu_asset_options" <?php if ($isBulkUnloaded) { echo 'style="display: none;"'; } ?>>
				<li class="wpacu_unload_this_page">
					<label class="wpacu_switch">
                        <input class="input-unload-on-this-page <?php if (! $isBulkUnloaded) { echo 'wpacu-not-locked'; } ?>"
                               id="style_<?php echo $data['row']['obj']->handle; ?>" <?php /* [wpacu_lite] */ if ($isBulkUnloaded) { /* [/wpacu_lite] */ echo 'disabled="disabled"'; }
                               echo $data['row']['checked']; ?>
                               name="<?php echo WPACU_PLUGIN_ID; ?>[styles][]"
                               type="checkbox"
                               value="<?php echo $data['row']['obj']->handle; ?>" />
                        <span class="wpacu_slider wpacu_round"></span>
                    </label>
                    <label class="wpacu_slider_text" for="style_<?php echo $data['row']['obj']->handle; ?>">
                        <?php _e('Unload on this page', 'wp-asset-clean-up'); ?>
                    </label>
				</li>
			</ul>

			<?php
			if ($isBulkUnloaded) {
				?>
                <em>
					<?php echo sprintf(
						__('"%s" rule is locked and irrelevant as there are global rules set below that overwrite it', 'wp-asset-clean-up'),
						__('Unload on this page', 'wp-asset-clean-up')
					); ?>.
					<?php _e('Once all the rules below are removed, this option will become available again', 'wp-asset-clean-up'); ?>.
                </em>
				<?php
			}
			?>
		    </div>

		    <div class="wpacu_asset_options_wrap">
			<?php
			// Unloaded Everywhere
			if ($data['row']['global_unloaded']) {
				?>
				<p><strong style="color: #d54e21;"><?php _e('This stylesheet file is unloaded everywhere', 'wp-asset-clean-up'); ?></strong></p>
				<div class="wpacu-clearfix"></div>
				<?php
			}
			?>

			<ul class="wpacu_asset_options">
				<?php
				// [START] UNLOAD EVERYWHERE
				if ($data['row']['global_unloaded']) {
					?>
					<li>
						<label><input data-handle="<?php echo $data['row']['obj']->handle; ?>"
						              class="wpacu_global_option wpacu_style"
						              type="radio"
						              name="wpacu_options_styles[<?php echo $data['row']['obj']->handle; ?>]"
						              checked="checked"
						              value="default" />
							<?php _e('Keep site-wide rule', 'wp-asset-clean-up'); ?></label>
					</li>

					<li>
						<label><input data-handle="<?php echo $data['row']['obj']->handle; ?>"
						              class="wpacu_global_option wpacu_style"
						              type="radio"
						              name="wpacu_options_styles[<?php echo $data['row']['obj']->handle; ?>]"
						              value="remove" />
							<?php _e('Remove site-wide rule', 'wp-asset-clean-up'); ?></label>
					</li>
					<?php
				} else {
					?>
					<li>
						<label><input data-handle="<?php echo $data['row']['obj']->handle; ?>"
						              class="wpacu_global_unload wpacu_global_style"
						              id="wpacu_global_unload_style_<?php echo $data['row']['obj']->handle; ?>" type="checkbox"
						              name="wpacu_global_unload_styles[]" value="<?php echo $data['row']['obj']->handle; ?>"/>
							<?php _e('Unload site-wide', 'wp-asset-clean-up'); ?> (<?php _e('everywhere', 'wp-asset-clean-up'); ?>) <small>* bulk unload</small></label>
					</li>
					<?php
				}
				// [END] UNLOAD EVERYWHERE
				?>
			</ul>
		</div>

		<?php if ($data['bulk_unloaded_type'] === 'post_type') { ?>
		<div class="wpacu_asset_options_wrap">
			<?php } ?>

			<?php
			// Unloaded On All Pages Belonging to the page's Post Type
			if ($data['row']['is_post_type_unloaded']) {
				switch ($data['post_type']) {
					case 'product':
						$alreadyUnloadedBulkText = __('This stylesheet file is unloaded on all WooCommerce "Product" pages', 'wp-asset-clean-up');
						break;
					case 'download':
						$alreadyUnloadedBulkText = __('This stylesheet file is unloaded on all Easy Digital Downloads "Download" pages', 'wp-asset-clean-up');
						break;
					default:
						$alreadyUnloadedBulkText = sprintf(__('This stylesheet file is unloaded on all <u>%s</u> post types', 'wp-asset-clean-up'), $data['post_type']);
				}
				?>
				<p><strong style="color: #d54e21;"><?php echo $alreadyUnloadedBulkText; ?>.</strong></p>
				<div class="wpacu-clearfix"></div>
				<?php
			}
			?>

			<?php
			if ($data['bulk_unloaded_type'] === 'post_type') {
				?>
				<ul class="wpacu_asset_options">
					<?php
					// [START] ALL PAGES HAVING THE SAME POST TYPE
					if ($data['row']['is_post_type_unloaded']) {
						?>
						<li>
							<label><input data-handle="<?php echo $data['row']['obj']->handle; ?>"
							              class="wpacu_bulk_option wpacu_style wpacu_keep_bulk_rule"
							              type="radio"
							              name="wpacu_options_post_type_styles[<?php echo $data['row']['obj']->handle; ?>]"
							              checked="checked"
							              value="default"/>
								<?php _e('Keep bulk rule', 'wp-asset-clean-up'); ?></label>
						</li>

						<li>
							<label><input data-handle="<?php echo $data['row']['obj']->handle; ?>"
							              class="wpacu_bulk_option wpacu_style wpacu_remove_bulk_rule"
							              type="radio"
							              name="wpacu_options_post_type_styles[<?php echo $data['row']['obj']->handle; ?>]"
							              value="remove"/>
								<?php _e('Remove bulk rule', 'wp-asset-clean-up'); ?></label>
						</li>
						<?php
					} else {
						switch ($data['post_type']) {
							case 'product':
								$unloadBulkText = __('Unload CSS on all WooCommerce "Product" pages', 'wp-asset-clean-up');
								break;
							case 'download':
								$unloadBulkText = __('Unload CSS on all Easy Digital Downloads "Download" pages', 'wp-asset-clean-up');
								break;
                            default:
	                            $unloadBulkText = sprintf(__('Unload on All Pages of "<strong>%s</strong>" post type', 'wp-asset-clean-up'), $data['post_type']);
						}
						?>
						<li>
							<label><input data-handle="<?php echo $data['row']['obj']->handle; ?>"
							              class="wpacu_bulk_unload wpacu_post_type_unload wpacu_post_type_style"
							              id="wpacu_bulk_unload_post_type_style_<?php echo $data['row']['obj']->handle; ?>"
							              type="checkbox"
							              name="wpacu_bulk_unload_styles[post_type][<?php echo $data['post_type']; ?>][]"
							              value="<?php echo $data['row']['obj']->handle; ?>"/>
								<?php echo $unloadBulkText; ?> <small>* <?php _e('bulk unload', 'wp-asset-clean-up'); ?></small></label>
						</li>
						<?php
					}
					?>
				</ul>
				<?php
			}
			// [END] ALL PAGES HAVING THE SAME POST TYPE
			?>

			<?php if ($data['bulk_unloaded_type'] === 'post_type') { ?>
		</div>
	<?php } ?>
            <div class="wpacu-clearfix"></div>
        </div>
		<?php
		?>

		<ul class="wpacu_asset_options wpacu_exception_options_area">
			<li id="wpacu_load_it_option_style_<?php echo $data['row']['obj']->handle; ?>">
				<label><input data-handle="<?php echo $data['row']['obj']->handle; ?>"
				              id="wpacu_style_load_it_<?php echo $data['row']['obj']->handle; ?>"
				              class="wpacu_load_it_option wpacu_style wpacu_load_exception"
				              type="checkbox"
						<?php if ($data['row']['is_load_exception']) { ?> checked="checked" <?php } ?>
						      name="wpacu_styles_load_it[]"
						      value="<?php echo $data['row']['obj']->handle; ?>"/>
					Load it on this page (make exception<?php if (! $isBulkUnloaded) { echo ' * works only IF any of bulk rule above is selected'; } ?>)</label>
			</li>
		</ul>
        <?php
		if (! empty($data['row']['extra_data_css_list'])) { ?>
            <div class="wpacu-assets-inline-code-wrap">
                <?php _e('Inline styling associated with the handle:', 'wp-asset-clean-up'); ?>
                <a class="wpacu-assets-inline-code-collapsible"
                   <?php if ($inlineCodeStatus !== 'contracted') { echo 'wpacu-assets-inline-code-collapsible-active'; } ?>
                   href="#"><?php _e('Show / Hide', 'wp-asset-clean-up'); ?></a>
                <div class="wpacu-assets-inline-code-collapsible-content <?php if ($inlineCodeStatus !== 'contracted') { echo 'wpacu-open'; } ?>">
                    <div>
                        <p style="margin-bottom: 15px; line-height: normal !important;">
                            <?php foreach ($data['row']['extra_data_css_list'] as $extraDataCSS) {
                                echo '<em>'.htmlspecialchars($extraDataCSS).'</em>'.'<br />';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
			<?php
		}

        $handleNote = (isset($data['handle_notes']['styles'][$data['row']['obj']->handle]) && $data['handle_notes']['styles'][$data['row']['obj']->handle])
            ? $data['handle_notes']['styles'][$data['row']['obj']->handle]
            : false;
	    ?>
        <div class="wpacu-handle-notes">
            <?php if (! $handleNote) { ?>
                <p><small>No notes have been added about this stylesheet file (e.g. why you unloaded it or decided to keep it loaded) &#10230; <a data-handle="<?php echo $data['row']['obj']->handle; ?>" href="#" class="wpacu-add-handle-note wpacu-for-style"><span class="dashicons dashicons-welcome-write-blog"></span> <label for="wpacu_handle_note_<?php echo $data['row']['obj']->handle; ?>">Add Note</label></a></small></p>
            <?php } else { ?>
                <p><small>The following note has been added for this stylesheet file (<em>to have it removed on update, just leave the text area empty</em>):</small></p>
            <?php } ?>
            <div <?php if ($handleNote) { echo 'style="display: block;"'; } ?> data-style-handle="<?php echo $data['row']['obj']->handle; ?>" class="wpacu-handle-notes-field">
                <textarea id="wpacu_handle_note_style_<?php echo $data['row']['obj']->handle; ?>"
                          rows="3"
                          placeholder="<?php echo esc_attr('Add your note here about this stylesheet file', 'wp-asset-clean-up'); ?>"
                          name="wpacu_handle_notes[styles][<?php echo $data['row']['obj']->handle; ?>]"><?php echo $handleNote; ?></textarea>
            </div>
        </div>
        <img style="display: none;" class="wpacu-ajax-loader" src="<?php echo WPACU_PLUGIN_URL; ?>/assets/icons/icon-ajax-loading-spinner.svg" alt="" />
	</td>
</tr>