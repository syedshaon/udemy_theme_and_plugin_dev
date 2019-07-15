<?php
namespace WpAssetCleanUp;

/**
 * Class OwnAssets
 *
 * These are plugin's own assets (CSS, JS etc.) and they are used only when you're logged in and do not show in the list for unload
 *
 * @package WpAssetCleanUp
 */
class OwnAssets
{
	/**
	 * @var bool
	 */
	public $loadPluginAssets = false; // default

	/**
	 *
	 */
	public function init()
    {
		add_action('admin_enqueue_scripts', array($this, 'stylesAndScriptsForAdmin'));
		add_action('wp_enqueue_scripts',    array($this, 'stylesAndScriptsForPublic'));

		// Code only for the Dashboard
		add_action('admin_head', array($this, 'inlineAdminCode'));

		// Code for both the Dashboard and the Front-end view
		add_action('admin_head', array($this, 'inlineCode'));
		add_action('wp_head',    array($this, 'inlineCode'));

		add_action('admin_footer', array($this, 'inlineFooterCode'));

		// Rename ?ver= to ?wpacuversion to prevent other plugins from stripping "ver"
		add_filter('script_loader_src', array($this, 'ownAssetLoaderSrc'));
		add_filter('style_loader_src',  array($this, 'ownAssetLoaderSrc'));
		add_filter('script_loader_tag', array($this, 'ownAssetLoaderTag'), 10, 2);
	}

	/**
	 *
	 */
	public function inlineCode()
    {
		if (is_admin_bar_showing()) {
			?>
            <style type="text/css">
                #wp-admin-bar-assetcleanup-parent span.dashicons {
                    width: 15px;
                    height: 15px;
                    font-family: 'Dashicons', Arial, "Times New Roman", "Bitstream Charter", Times, serif !important;
                }

                #wp-admin-bar-assetcleanup-parent > a:first-child strong {
                    font-weight: bolder;
                    color: #76f203;
                }

                #wp-admin-bar-assetcleanup-parent > a:first-child:hover {
                    color: #00b9eb;
                }

                #wp-admin-bar-assetcleanup-parent > a:first-child:hover strong {
                    color: #00b9eb;
                }

                #wp-admin-bar-assetcleanup-test-mode-info {
                    margin-top: 5px !important;
                    margin-bottom: -8px !important;
                    padding-top: 3px !important;
                    border-top: 1px solid #ffffff52;
                }

                /* Add some spacing below the last text */
                #wp-admin-bar-assetcleanup-test-mode-info-2 {
                    padding-bottom: 3px !important;
                }
            </style>
			<?php
		}
	}

	/**
	 *
	 */
	public function inlineAdminCode()
    {
		?>
        <style type="text/css">
            .menu-top.toplevel_page_wpassetcleanup_getting_started .wp-menu-image > img {
                width: 26px;
                position: absolute;
                left: 8px;
                top: -4px;
            }

            .plugin-title .opt-in-or-opt-out.wp-asset-clean-up {
                display: none;
            }
        </style>
		<?php
	}

	/**
	 *
	 */
	public function stylesAndScriptsForAdmin()
    {
		global $post;

		if (! Menu::userCanManageAssets()) {
			return;
		}

		$page      = Misc::getVar('get', 'page');
		$getPostId = (int)Misc::getVar('get', 'post');

		// Only load the plugin's assets when they are needed
		// This an example of assets that are correctly loaded in WordPress
		if (isset($post->ID)) {
			$this->loadPluginAssets = true;
		}

		if ($getPostId > 0) {
			$this->loadPluginAssets = true;
		}

		if (strpos($page, WPACU_PLUGIN_ID) === 0) {
			$this->loadPluginAssets = true;
		}

		if (! $this->loadPluginAssets) {
			return;
		}

		$this->enqueueAdminStyles();
		$this->enqueueAdminScripts();
	}

	/**
	 *
	 */
	public function stylesAndScriptsForPublic()
    {
		// Do not print it when an AJAX call is made from the Dashboard
		if (isset($_POST[WPACU_LOAD_ASSETS_REQ_KEY])) {
			return;
		}

		// Only for the administrator with the right permission
		if (! Menu::userCanManageAssets()) {
			return;
		}

		// Is the Admin Bar not showing and "Manage in the Front-end" option is not enabled in the plugin's "Settings"?
		// In this case, there's no point in loading the assets below
		if (! is_admin_bar_showing() && ! Main::instance()->frontendShow()) {
			return;
		}

		// Do not load any CSS & JS belonging to Asset CleanUp if in "Elementor" preview
		if (Main::instance()->isFrontendEditView && array_key_exists('elementor-preview',
				$_GET) && $_GET['elementor-preview'] && Main::instance()->isFrontendEditView) {
			return;
		}

		$this->enqueuePublicStyles();
		$this->enqueuePublicScripts();
	}

	/**
	 *
	 */
	private function enqueueAdminStyles()
    {
		$styleRelPath = '/assets/style.min.css';
		wp_enqueue_style(WPACU_PLUGIN_ID . '-style', plugins_url($styleRelPath, WPACU_PLUGIN_FILE), array(),
			$this->_assetVer($styleRelPath));
	}

	/**
	 *
	 */
	private function enqueueAdminScripts()
    {
		global $post, $pagenow;

		$page = Misc::getVar('get', 'page');

		$getPostId = (isset($_GET['post'], $_GET['action']) && $_GET['action'] === 'edit' && $pagenow === 'post.php') ? (int)$_GET['post'] : '';

		$postId = isset($post->ID) ? $post->ID : 0;

		if ($getPostId > 0 && $getPostId !== $postId) {
			$postId = $getPostId;
		}

		if ($page === WPACU_PLUGIN_ID . '_home_page' || $postId < 1) {
			$postId = 0; // for home page
		}

		$scriptRelPath = '/assets/script.min.js';

		wp_register_script(
			WPACU_PLUGIN_ID . '-script',
			plugins_url($scriptRelPath, WPACU_PLUGIN_FILE),
			array('jquery'),
			$this->_assetVer($scriptRelPath)
		);

		// It can also be the front page URL
		$pageUrl = Misc::getPageUrl($postId);

		$svgReloadIcon = <<<HTML
<svg aria-hidden="true" role="img" focusable="false" class="dashicon dashicons-cloud" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path d="M14.9 9c1.8.2 3.1 1.7 3.1 3.5 0 1.9-1.6 3.5-3.5 3.5h-10C2.6 16 1 14.4 1 12.5 1 10.7 2.3 9.3 4.1 9 4 8.9 4 8.7 4 8.5 4 7.1 5.1 6 6.5 6c.3 0 .7.1.9.2C8.1 4.9 9.4 4 11 4c2.2 0 4 1.8 4 4 0 .4-.1.7-.1 1z"></path></svg>
HTML;

		$wpacuObjectData = array(
			'plugin_name'  => WPACU_PLUGIN_ID,
			'reload_icon'  => $svgReloadIcon,
			'reload_msg'   => sprintf(__('Reloading %s CSS &amp; JS list', 'wp-asset-clean-up'),
				'<strong style="margin: 0 4px;">' . WPACU_PLUGIN_TITLE . '</strong>'),
			'dom_get_type' => Main::$domGetType,
			'start_del'    => Main::START_DEL,
			'end_del'      => Main::END_DEL,
			'ajax_url'     => admin_url('admin-ajax.php'),
			'post_id'      => $postId, // if any
			'page_url'     => $pageUrl // post, page, custom post type, homepage etc.
		);

		// [wpacu_lite]
		$submitTicketLink = 'https://wordpress.org/support/plugin/wp-asset-clean-up';
		// [/wpacu_lite]

		$wpacuObjectData['ajax_direct_fetch_error'] = <<<HTML
<div class="ajax-direct-call-error-area">
    <p class="note"><strong>Note:</strong> The checked URL returned an error when fetching the assets via AJAX call. This could be because of a firewall that is blocking the AJAX call, a redirect loop or an error in the script that is retrieving the output which could be due to an incompatibility between the plugin and the WordPress setup you are using.</p>
    <p>Here is the response from the call:</p>

    <table>
        <tr>
            <td width="135"><strong>Status Code Error:</strong></td>
            <td><span class="error-code">{wpacu_status_code_error}</span> * for more information about client and server errors, <a target="_blank" href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes">check this link</a></td>
        </tr>
        <tr>
            <td valign="top"><strong>Suggestion:</strong></td>
            <td>Select "WP Remote Post" as a method of retrieving the assets from the "Settings" page. If that doesn't fix the issue, just use "Manage in Front-end" option which should always work and <a target="_blank" href="{$submitTicketLink}">submit a ticket</a> about your problem.</td>
        </tr>
        <tr>
            <td><strong>Output:</strong></td>
            <td>{wpacu_output}</td>
        </tr>
    </table>
</div>
HTML;

		$wpacuObjectData['jquery_migration_disable_confirm_msg'] =
			__('Make sure to properly test your website if you unload the jQuery migration library.',
				'wp-asset-clean-up') . "\n\n" .
			__('In some cases, due to old jQuery code triggered from plugins or the theme, unloading this migration library could cause those scripts not to function anymore and break some of the front-end functionality.',
				'wp-asset-clean-up') . "\n\n" .
			__('If you are not sure about whether activating this option is right or not, it is better to leave it as it is (to be loaded by default) and consult with a developer.',
				'wp-asset-clean-up') . "\n\n" .
			__('Confirm this action to enable the unloading or cancel to leave it loaded by default.',
				'wp-asset-clean-up');

		$wpacuObjectData['comment_reply_disable_confirm_msg'] =
			__('This is worth disabling if you are NOT using the default WordPress comment system (e.g. you are using the website for business purposes, to showcase your products and you are not using it as a blog where people leave comments to your posts).',
				'wp-asset-clean-up') . "\n\n" .
			__('If you are not sure about whether activating this option is right or not, it is better to leave it as it is (to be loaded by default).',
				'wp-asset-clean-up') . "\n\n" .
			__('Confirm this action to enable the unloading or cancel to leave it loaded by default.',
				'wp-asset-clean-up');

		// "Tools" - "Reset"
		$wpacuObjectData['reset_settings_confirm_msg'] =
			__('Are you sure you want to reset the settings to their default values?', 'wp-asset-clean-up') . "\n\n" .
			__('This is an irreversible action.', 'wp-asset-clean-up') . "\n\n" .
			__('Please confirm to continue or "Cancel" to abort it', 'wp-asset-clean-up');

		$wpacuObjectData['reset_everything_except_settings_confirm_msg'] =
			__('Are you sure you want to reset everything (unloads, load exceptions etc.) except settings?',
				'wp-asset-clean-up') . "\n\n" .
			__('This is an irreversible action.', 'wp-asset-clean-up') . "\n\n" .
			__('Please confirm to continue or "Cancel" to abort it.', 'wp-asset-clean-up');

		$wpacuObjectData['reset_everything_confirm_msg'] =
			__('Are you sure you want to reset everything (settings, unloads, load exceptions etc.) to the same point it was when you first activated the plugin?',
				'wp-asset-clean-up') . "\n\n" .
			__('This is an irreversible action.', 'wp-asset-clean-up') . "\n\n" .
			__('Please confirm to continue or "Cancel" to abort it.', 'wp-asset-clean-up');

		// "Tools" - "Import & Export"
		$wpacuObjectData['import_confirm_msg'] =
			__('This process is NOT reversible.', 'wp-asset-clean-up') . "\n\n" .
			__('Please make sure you have a backup (e.g. an exported JSON file) before proceeding.',
				'wp-asset-clean-up') . "\n\n" .
			__('Please confirm to continue or "Cancel" to abort it.', 'wp-asset-clean-up');

		wp_localize_script(
			WPACU_PLUGIN_ID . '-script',
			'wpacu_object',
			apply_filters('wpacu_object_data', $wpacuObjectData)
		);

		wp_enqueue_script(WPACU_PLUGIN_ID . '-script');
	}

	/**
	 *
	 */
	private function enqueuePublicStyles()
    {
		$styleRelPath = '/assets/style.min.css';
		wp_enqueue_style(WPACU_PLUGIN_ID . '-style', plugins_url($styleRelPath, WPACU_PLUGIN_FILE), array(),
			$this->_assetVer($styleRelPath));
	}

	/**
	 *
	 */
	public function enqueuePublicScripts()
    {
		$scriptRelPath = '/assets/script.min.js';
		wp_enqueue_script(WPACU_PLUGIN_ID . '-script', plugins_url($scriptRelPath, WPACU_PLUGIN_FILE), array('jquery'),
			$this->_assetVer($scriptRelPath), true);
	}

	/**
	 * @param $relativePath
	 *
	 * @return bool|false|int|string
	 */
	private function _assetVer($relativePath)
    {
		$assetVer = @filemtime(dirname(WPACU_PLUGIN_FILE) . $relativePath);

		if (! $assetVer) {
			$assetVer = date('dmYHi');
		}

		return $assetVer;
	}

	/**
	 * Prevent "?ver=" or "&ver=" from being stripped when loading plugin's own assets
	 * It will force them to refresh whenever there's a change in either of the files
	 *
	 * @param $src
	 *
	 * @return mixed
	 */
	public function ownAssetLoaderSrc($src)
    {
		if (strpos($src, '/wp-asset-clean-up/assets/script.min.js') !== false
		    || strpos($src, '/wp-asset-clean-up/assets/style.min.css') !== false) {
			$src = str_replace(
				array('?ver=', '&ver='),
				array('?wpacuversion=', '&wpacuversion='),
				$src);
		}

		return $src;
	}

	/**
	 * @param $tag
	 * @param $handle
	 *
	 * @return mixed
	 */
	public function ownAssetLoaderTag($tag, $handle)
    {
		// Useful in case jQuery library is deferred too (rare situations)
		if ($handle === WPACU_PLUGIN_ID . '-script' && ! is_admin()) {
			$tag = str_replace(' src=', ' defer=\'defer\' src=', $tag);
		}

		return $tag;
	}

	/**
	 *
	 */
	public function inlineFooterCode()
    {
        if (defined('WPACU_USE_MODAL_BOX') && WPACU_USE_MODAL_BOX === true) { ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                /*
                 * START WPACU MODAL
                 */
                var wpacuCurrentModal, $wpacuModals = $('.wpacu-modal');

                if ($wpacuModals.length < 1) {
                    return;
                }

                $wpacuModals.each(function (wpacuIndex) {
                    var wpacuModalId = $(this).attr('id');

                    // Get the modal
                    var wpacuModal = document.getElementById(wpacuModalId);

                    // Get the link/button that opens the modal
                    var wpacuTarget = document.getElementById(wpacuModalId + '-target');

                    // When the user clicks the button, open the modal
                    wpacuTarget.onclick = function () {
                        wpacuModal.style.display = 'block';
                        wpacuCurrentModal = wpacuModal;
                    };

                    // Get the <span> element that closes the modal
                    var wpacuSpan = document.getElementsByClassName('wpacu-close')[wpacuIndex];

                    // When the user clicks on <span> (x), close the modal
                    wpacuSpan.onclick = function () {
                        wpacuModal.style.display = 'none';
                    };
                });

                // When the user clicks anywhere outside of the modal, close it
                window.onclick = function (event) {
                    if (event.target === wpacuCurrentModal) {
                        wpacuCurrentModal.style.display = 'none';
                    }
                };
                /*
                 * END WPACU MODAL
                 */
            });
        </script>
        <?php }

        if (isset($_GET['page']) && $_GET['page'] === WPACU_PLUGIN_ID.'_settings') {
            // Only relevant in the "Settings" area
            ?>
            <script type="text/javascript">
                // Tab Area | Keep selected tab after page reload
                if (location.href.indexOf('#') !== -1) {
                    var hashFromUrl = location.href.substr(location.href.indexOf('#'));
                    //wpacuTabOpenSettingsArea(event, hashFromUrl.substring(1));
                    //console.log(hashFromUrl);
                    jQuery('a[href="'+ hashFromUrl +'"]').trigger('click');
                    //console.log(hashFromUrl);
                    //console.log(hashFromUrl.substring(1));
                }
            </script>
            <?php
        }
    }
}
