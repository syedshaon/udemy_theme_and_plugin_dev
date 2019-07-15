<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
    exit;
}

include_once '_top-area.php';

$wpacuTabCurrent = isset($_REQUEST['wpacu_bulk_menu_tab']) ? $_REQUEST['wpacu_bulk_menu_tab'] : 'bulk_unloaded';

$wpacuTabList = array(
    'bulk_unloaded'    => __('Bulk Unloaded', 'wp-asset-clean-up'),
    'preloaded_assets' => __('Preloaded CSS/JS', 'wp-asset-clean-up'),
    'script_attrs'     => __('Defer &amp; Async used on all pages', 'wp-asset-clean-up'),
    'assets_positions' => __('Updated CSS/JS positions', 'wp-asset-clean-up')
);
?>
<div class="wpacu-wrap">
    <ul class="wpacu-bulk-changes-tabs">
        <?php
        foreach ($wpacuTabList as $wpacuTabKey => $wpacuTabValue) {
            ?>
            <li <?php if ($wpacuTabKey === $wpacuTabCurrent) { ?>class="current"<?php } ?>>
                <a href="<?php echo admin_url('admin.php?page=wpassetcleanup_bulk_unloads&wpacu_bulk_menu_tab='.$wpacuTabKey); ?>"><?php echo $wpacuTabValue; ?></a>
            </li>
            <?php
        }
        ?>
    </ul>
    <?php
    if ($wpacuTabCurrent === 'bulk_unloaded') {
	    include_once '_admin-page-settings-bulk-changes/_bulk-unloaded.php';
    } elseif ($wpacuTabCurrent === 'preloaded_assets') {
	    include_once '_admin-page-settings-bulk-changes/_preloaded-assets.php';
    } elseif ($wpacuTabCurrent === 'script_attrs') {
	    include_once '_admin-page-settings-bulk-changes/_script-attrs.php';
    } elseif ($wpacuTabCurrent === 'assets_positions') {
        include_once '_admin-page-settings-bulk-changes/_assets-positions.php';
    }
    ?>
</div>