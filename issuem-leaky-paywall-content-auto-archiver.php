<?php
/**
 * Main PHP file used to for initial calls to zeen101's Leak Paywall classes and functions.
 *
 * @package zeen101's Leak Paywall - Content Auto-Archiver
 * @since 1.0.0
 */
 
/*
Plugin Name: Leaky Paywall - Content Auto-Archiver
Plugin URI: http://zeen101.com/
Description: A premium addon for the Leaky Paywall for WordPress plugin.
Author: zeen101 Development Team
Version: 3.2.0
Author URI: http://zeen101.com/
Tags:
*/

//Define global variables...
if ( !defined( 'ZEEN101_STORE_URL' ) )
	define( 'ZEEN101_STORE_URL',	'http://zeen101.com' );
	
define( 'LP_CAA_NAME', 		'Leaky Paywall - Content Auto Archiver' );
define( 'LP_CAA_SLUG', 		'leaky-paywall-content-auto-archiver' );
define( 'LP_CAA_VERSION', 	'3.2.0' );
define( 'LP_CAA_DB_VERSION', '1.0.0' );
define( 'LP_CAA_URL', 		plugin_dir_url( __FILE__ ) );
define( 'LP_CAA_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'LP_CAA_BASENAME', 	plugin_basename( __FILE__ ) );
define( 'LP_CAA_REL_DIR', 	dirname( LP_CAA_BASENAME ) );

/**
 * Instantiate Pigeon Pack class, require helper files
 *
 * @since 1.0.0
 */
function leaky_paywall_content_auto_archiver_plugins_loaded() {
	
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( is_plugin_active( 'issuem-leaky-paywall/issuem-leaky-paywall.php' ) 
		|| is_plugin_active( 'leaky-paywall/leaky-paywall.php' ) ) {
						
		global $leaky_paywall_data;

		if ( is_plugin_active( 'issuem-leaky-paywall/issuem-leaky-paywall.php' ) ) {
			$leaky_paywall_data = get_plugin_data( WP_PLUGIN_DIR . '/issuem-leaky-paywall/issuem-leaky-paywall.php' );
		} else {
			$leaky_paywall_data = get_plugin_data( WP_PLUGIN_DIR . '/leaky-paywall/leaky-paywall.php' );
		}
		

		require_once( 'class.php' );
	
		// Instantiate the Pigeon Pack class
		if ( class_exists( 'Leaky_Paywall_Content_Auto_Archiver' ) ) {
			global $leaky_paywall_content_auto_archiver;
			
			$leaky_paywall_content_auto_archiver = new Leaky_Paywall_Content_Auto_Archiver();
			
			require_once( 'functions.php' );
				
			//Internationalization
			load_plugin_textdomain( 'issuem-lp-caa', false, LP_CAA_REL_DIR . '/i18n/' );
		}
	
	} else {
	
		add_action( 'admin_notices', 'leaky_paywall_content_auto_archiver_requirement_nag' );
		
	}

}
add_action( 'plugins_loaded', 'leaky_paywall_content_auto_archiver_plugins_loaded', 4815162342 ); //wait for the plugins to be loaded before init

function leaky_paywall_content_auto_archiver_requirement_nag() {
	?>
	<div id="leaky-paywall-requirement-nag" class="update-nag">
		<?php _e( 'You must have the Leaky Paywall plugin activated to use the Leaky Paywall Content Auto Archiver plugin.' ); ?>
	</div>
	<?php
}
