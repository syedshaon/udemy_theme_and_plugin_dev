<?php
namespace WpAssetCleanUp;

/**
 * Class FileSystem
 * @package WpAssetCleanUp
 */
class FileSystem
{
	/**
	 * @return bool|\WP_Filesystem_Direct
	 */
	public static function init()
	{
		global $wp_filesystem;

		if (empty($wp_filesystem)) {
			require_once (ABSPATH . '/wp-admin/includes/file.php');

			if (! function_exists('\WP_Filesystem')) {
				return false;
			}

			return WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * @param $localPathToFile
	 *
	 * @return bool|false|string
	 */
	public static function file_get_contents($localPathToFile)
	{
		// Fallback
		if (! self::init()) {
			return @file_get_contents($localPathToFile);
		}

		global $wp_filesystem;
		return $wp_filesystem->get_contents($localPathToFile);
	}

	/**
	 * @param $localPathToFile
	 * @param $contents
	 *
	 * @return bool|int|void
	 */
	public static function file_put_contents($localPathToFile, $contents)
	{
		// Fallback
		if (! self::init()) {
			return @file_put_contents($localPathToFile, $contents);
		}

		global $wp_filesystem;
		return $wp_filesystem->put_contents($localPathToFile, $contents, FS_CHMOD_FILE);
	}
}
