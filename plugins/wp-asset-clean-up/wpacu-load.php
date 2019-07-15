<?php
// Exit if accessed directly
if (! defined('WPACU_PLUGIN_CLASSES_PATH')) {
    exit;
}

// Autoload Classes
function includeWpAssetCleanUpClassesAutoload($class)
{
    $namespace = 'WpAssetCleanUp';

    // continue only if the namespace is within $class
    if (strpos($class, $namespace) === false) {
        return;
    }

    $classFilter = str_replace($namespace.'\\', '', $class);

    // Can be directories such as "Helpers"
    $classFilter = str_replace('\\', '/', $classFilter);

    $pathToClass = WPACU_PLUGIN_CLASSES_PATH.$classFilter.'.php';

    if (file_exists($pathToClass)) {
        include_once $pathToClass;
    }
}

spl_autoload_register('includeWpAssetCleanUpClassesAutoload');

// Main Class
WpAssetCleanUp\Main::instance();

// Code needed ONLY for Asset CleanUp LITE
new \WpAssetCleanUp\Lite();

// Plugin's Assets (used only when you're logged in)
$wpacuOwnAssets = new \WpAssetCleanUp\OwnAssets;
$wpacuOwnAssets->init();

// Add / Update / Remove Settings
$wpacuUpdate = new WpAssetCleanUp\Update;
$wpacuUpdate->init();

// Settings
$wpacuSettings = new WpAssetCleanUp\Settings;

if (is_admin()) {
	$wpacuSettings->adminInit();
}

// Various functions
new WpAssetCleanUp\Misc;

// Menu
new \WpAssetCleanUp\Menu;

// Admin Bar (Top Area of the website when user is logged in)
new \WpAssetCleanUp\AdminBar();

// Initialize information
new \WpAssetCleanUp\Info();

// Common functions for both CSS & JS combinations
// Clear cache functionality
$wpacuOptimizeCommon = new \WpAssetCleanUp\OptimiseAssets\OptimizeCommon();
$wpacuOptimizeCommon->init();

// Sometimes when page builders are used such as "Oxygen Builder", it's better to keep the CSS/JS combine/minify disabled
// The following will only trigger in specific situations (most cases)
if (\WpAssetCleanUp\Misc::triggerFrontendOptimization()) {
	/*
	 * Trigger the CSS & JS combination only in the front-end view in certain conditions (not within the Dashboard)
	 */
	// Combine/Minify CSS Files Setup
	$wpacuOptimizeCss = new \WpAssetCleanUp\OptimiseAssets\OptimizeCss();
	$wpacuOptimizeCss->init();

	$wpacuMinifyCss = new \WpAssetCleanUp\OptimiseAssets\MinifyCss();
	$wpacuMinifyCss->init();

	// Combine/Minify JS Files Setup
	$wpacuOptimizeJs = new \WpAssetCleanUp\OptimiseAssets\OptimizeJs();
	$wpacuOptimizeJs->init();
	new \WpAssetCleanUp\OptimiseAssets\MinifyJs();
}

if (is_admin()) {
	/*
	 * Trigger only within the Dashboard view (e.g. within /wp-admin/)
	 */
	new \WpAssetCleanUp\Plugin;
	new \WpAssetCleanUp\PluginReview();

	$wpacuPluginTracking = new \WpAssetCleanUp\PluginTracking();
	$wpacuPluginTracking->init();

	$wpacuTools = new \WpAssetCleanUp\Tools();
	$wpacuTools->init();

	\WpAssetCleanUp\Preloads::instance()->init();
} elseif (\WpAssetCleanUp\Misc::triggerFrontendOptimization()) {
	/*
	 * Trigger only in the front-end view (e.g. Homepage URL, /contact/, /about/ etc.)
	 */
	$wpacuCleanUp = new \WpAssetCleanUp\CleanUp();
	$wpacuCleanUp->init();
}
