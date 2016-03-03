<?php
/*
Plugin Name: UMW Directory Syndication
Description: Utilizes the REST API to syndicate UMW directory information
Version:     0.1
Author:      Curtiss Grymala
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! class_exists( 'UMW_Directory_API' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/classes/class-umw-directory-api.php' );
}
add_action( 'plugins_loaded', 'inst_umw_directory_shortcode' );
function inst_umw_directory_shortcode() {
	global $umw_directory_shortcode_obj;
	$umw_directory_shortcode_obj = new UMW_Directory_API;
}