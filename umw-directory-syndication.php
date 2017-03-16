<?php
/*
Plugin Name: UMW Directory Syndication
Description: Utilizes the REST API to syndicate UMW directory information
Version:     0.1
Author:      Curtiss Grymala
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

add_action( 'plugins_loaded', 'inst_umw_directory_shortcode' );
function inst_umw_directory_shortcode() {
	if ( ! class_exists( 'UMW_Directory_Shortcode' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . '/classes/class-umw-directory-shortcode.php' );
	}
	global $umw_directory_shortcode_obj;
	$umw_directory_shortcode_obj = UMW_Directory_Shortcode::instance();
}