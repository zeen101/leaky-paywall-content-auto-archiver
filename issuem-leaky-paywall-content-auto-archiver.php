<?php
/**
 * Main PHP file used to for initial calls to IssueM's Leak Paywall classes and functions.
 *
 * @package IssueM's Leak Paywall - Content Auto-Archiver
 * @since 1.0.0
 */
 
/*
Plugin Name: IssueM's Leaky Paywall - Content Auto-Archiver
Plugin URI: http://issuem.com/
Description: A premium leaky paywall add-on for WordPress and IssueM.
Author: IssueM Development Team
Version: 1.0.0
Author URI: http://issuem.com/
Tags:
*/

//Define global variables...
if ( !defined( 'ISSUEM_STORE_URL' ) )
	define( 'ISSUEM_STORE_URL',				'http://issuem.com' );
	
define( 'ISSUEM_LP_CAA_NAME', 		'Leaky Paywall - _SM_' );
define( 'ISSUEM_LP_CAA_SLUG', 		'issuem-leaky-paywall-content-auto-archiver' );
define( 'ISSUEM_LP_CAA_VERSION', 	'1.0.0' );
define( 'ISSUEM_LP_CAA_DB_VERSION', '1.0.0' );
define( 'ISSUEM_LP_CAA_URL', 		plugin_dir_url( __FILE__ ) );
define( 'ISSUEM_LP_CAA_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'ISSUEM_LP_CAA_BASENAME', 	plugin_basename( __FILE__ ) );
define( 'ISSUEM_LP_CAA_REL_DIR', 	dirname( ISSUEM_LP_CAA_BASENAME ) );

/**
 * Instantiate Pigeon Pack class, require helper files
 *
 * @since 1.0.0
 */
function issuem_leaky_paywall_content_auto_archiver_plugins_loaded() {
	
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	if ( is_plugin_active( 'issuem-leaky-paywall/issuem-leaky-paywall.php' ) ) {

		require_once( 'class.php' );
	
		// Instantiate the Pigeon Pack class
		if ( class_exists( 'IssueM_Leaky_Paywall_Content_Auto_Archiver' ) ) {
			
			global $dl_pluginissuem_leaky_paywall_content_auto_archiver;
			
			$dl_pluginissuem_leaky_paywall_content_auto_archiver = new IssueM_Leaky_Paywall_Content_Auto_Archiver();
			
			require_once( 'functions.php' );
				
			//Internationalization
			load_plugin_textdomain( 'issuem-lp-caa', false, ISSUEM_LP_CAA_REL_DIR . '/i18n/' );
				
		}
	
	} else {
	
		add_action( 'admin_notices', 'issuem_leaky_paywall_content_auto_archiver_requirement_nag' );
		
	}

}
add_action( 'plugins_loaded', 'issuem_leaky_paywall_content_auto_archiver_plugins_loaded', 4815162342 ); //wait for the plugins to be loaded before init

function issuem_leaky_paywall_content_auto_archiver_requirement_nag() {
	?>
	<div id="leaky-paywall-requirement-nag" class="update-nag">
		<?php _e( 'You must have the Leaky Paywall plugin activated to use the Leaky Paywall Content Auto Archiver plugin.' ); ?>
	</div>
	<?php
}