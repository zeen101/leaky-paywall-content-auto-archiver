<?php

/**
 * Main PHP file used to for initial calls to Leaky Paywall classes and functions.
 *
 * @package Leaky Paywall - Content Auto-Archiver
 * @since 1.0.0
 */

/*
Plugin Name: Leaky Paywall - Content Auto-Archiver
Plugin URI: https://leakypaywall.com/
Description: Place content behind the paywall automatically after a certain time period. (timewall)
Author: Leaky Paywall
Version: 3.5.1
Author URI: https://leakypaywall.com
Tags:
*/

//Define global variables...
if (!defined('ZEEN101_STORE_URL')) {
	define('ZEEN101_STORE_URL',	'https://zeen101.com');
}

define('LP_CAA_NAME', 		'Leaky Paywall - Content Auto Archiver');
define('LP_CAA_SLUG', 		'leaky-paywall-content-auto-archiver');
define('LP_CAA_VERSION', 	'3.5.1');
define('LP_CAA_DB_VERSION', '1.0.0');
define('LP_CAA_URL', 		plugin_dir_url(__FILE__));
define('LP_CAA_PATH', 		plugin_dir_path(__FILE__));
define('LP_CAA_BASENAME', 	plugin_basename(__FILE__));
define('LP_CAA_REL_DIR', 	dirname(LP_CAA_BASENAME));

/**
 * Instantiate Pigeon Pack class, require helper files
 *
 * @since 1.0.0
 */
function leaky_paywall_content_auto_archiver_plugins_loaded()
{

	global $is_leaky_paywall, $which_leaky_paywall;
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');

	if (is_plugin_active('issuem-leaky-paywall/issuem-leaky-paywall.php')) {
		$is_leaky_paywall = true;
		$which_leaky_paywall = '_issuem';
	} else if (is_plugin_active('leaky-paywall/leaky-paywall.php')) {
		$is_leaky_paywall = true;
		$which_leaky_paywall = '';
	} else {
		$is_leaky_paywall = false;
		$which_leaky_paywall = '';
	}

	if (!empty($is_leaky_paywall)) {
		require_once('class.php');

		// Instantiate the Pigeon Pack class
		if (class_exists('Leaky_Paywall_Content_Auto_Archiver')) {
			global $leaky_paywall_content_auto_archiver;

			$leaky_paywall_content_auto_archiver = new Leaky_Paywall_Content_Auto_Archiver();

			require_once('includes/admin/settings.php');
			require_once('includes/updates.php');
			require_once('functions.php');

			//Internationalization
			load_plugin_textdomain('issuem-lp-caa', false, LP_CAA_REL_DIR . '/i18n/');
		}

		// Upgrade function based on EDD updater class
		if (!class_exists('EDD_LP_Plugin_Updater')) {
			include(dirname(__FILE__) . '/includes/EDD_LP_Plugin_Updater.php');
		}

		$license = new Leaky_Paywall_License_Key(LP_CAA_SLUG, LP_CAA_NAME);

		$settings = $license->get_settings();
		$license_key = trim($settings['license_key']);
		$edd_updater = new EDD_LP_Plugin_Updater(ZEEN101_STORE_URL, __FILE__, array(
			'version' 	=> LP_CAA_VERSION, // current version number
			'license' 	=> $license_key,
			'item_id'	=> 13792,
			'author' 	=> 'Zeen101 Development Team'
		));
	} else {
		add_action('admin_notices', 'leaky_paywall_content_auto_archiver_requirement_nag');
	}
}
add_action('plugins_loaded', 'leaky_paywall_content_auto_archiver_plugins_loaded', 4815162344); //wait for the plugins to be loaded before init

function leaky_paywall_content_auto_archiver_requirement_nag()
{
?>
	<div id="leaky-paywall-requirement-nag" class="update-nag">
		<?php _e('You must have the Leaky Paywall plugin activated to use the Leaky Paywall Content Auto Archiver plugin.'); ?>
	</div>
<?php
}
