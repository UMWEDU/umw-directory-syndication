<?php
if ( ! class_exists( 'UMW_Directory_API' ) ) {
	class UMW_Directory_API {
		public $is_directory  = false;
		public $directory_url = null;
		public $rest_classes  = array();
		
		function __construct() {
			/**
			 * Determine whether this is the main directory site or not
			 */
			$this->is_directory_site();
			
			$this->setup_rest_classes();
			
			/**
			 * Set up our shortcode
			 */
			add_shortcode( 'umw-directory', array( $this, 'do_shortcode' ) );
		}
		
		/**
		 * Register and include all of the REST classes we'll need
		 */
		function setup_rest_classes() {
			$this->rest_classes = array(
				'department-employees' => 'UMW_DAPI_Department_Employees', 
				'building-employees'   => 'UMW_DAPI_Building_Employees', 
				'employee-departments' => 'UMW_DAPI_Employee_Departments', 
			);
			
			foreach ( $this->rest_classes as $k => $c ) {
				if ( ! class_exists( $c ) )
					require_once( plugin_dir_path( __FILE__ ) . '/inc/class-' . strtolower( str_replace( '_', '-', $c ) ) . '.php' );
				$this->rest_classes[$k] = new $c;
			}
		}
		
		/**
		 * Determine whether or not this is the main directory site
		 * Set the URL for the main directory site, either way
		 * If so, set up some API functions
		 * @uses UMW_Directory_API::$is_directory
		 * @uses UMW_Directory_API::$directory_url
		 * @uses UMW_Directory_API::setup_directory_site()
		 */
		function is_directory_site() {
			if ( defined( 'UMW_EMPLOYEE_DIRECTORY' ) && is_numeric( UMW_EMPLOYEE_DIRECTORY ) ) {
				if ( UMW_EMPLOYEE_DIRECTORY == $GLOBALS['blog_id'] ) {
					$this->is_directory = true;
					$this->directory_url = esc_url( get_bloginfo( 'url' ) );
					$this->setup_directory_site();
				} else {
					$this->is_directory = false;
					$this->directory_url = esc_url( get_blog_option( UMW_EMPLOYEE_DIRECTORY, 'home' ) );
				}
			} else {
				$this->is_directory = false;
				$this->directory_url = esc_url( UMW_EMPLOYEE_DIRECTORY );
			}
		}
		
		/**
		 * Set up the API procedures for the directory site
		 */
		function setup_directory_site() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
		
		/**
		 * Call the appropriate method to register the necessary REST API routes
		 */
		function register_routes() {
			foreach ( $this->rest_classes as $c ) {
				$c->register_routes();
			}
		}
		
		/**
		 * Execute the shortcode itself
		 */
		function do_shortcode( $atts=array() ) {
			$atts = shortcode_atts( $this->get_defaults(), $atts, 'umw-directory' );
			
			if ( ! empty( $atts['department'] ) ) {
				$rest_class = $this->rest_classes['department-employees'];
				
				$url = untrailingslashit( $this->directory_url ) . $rest_class->get_rest_url();
				if ( ! is_numeric( $atts['department'] ) ) {
					$url = add_query_arg( array(
						'slug' => $atts['department'], 
						'per_page'  => 200, 
					), $url );
				} else {
					$url .= '/' . intval( $atts['department'] );
					$url = add_query_arg( 'per_page', 200, $url );
				}
				
				print( '<pre><code>' );
				var_dump( $url );
				print( '</code></pre>' );
				
				$employees = @json_decode( wp_remote_retrieve_body( wp_remote_request( $url ) ) );
				ob_start();
				print( '<pre><code>' );
				var_dump( $employees );
				print( '</code></pre>' );
				return ob_get_clean();
			} else if ( ! empty( $atts['building'] ) ) {
				$rest_class = $this->rest_classes['building-employees'];
				
				$url = untrailingslashit( $this->directory_url ) . $rest_class->get_rest_url();
				$url = add_query_arg( array( 
					'parent' => $atts['building'], 
					'per_page'  => 200, 
				), $url );
				
				$employees = @json_decode( wp_remote_request( $url ) );
				ob_start();
				print( '<pre><code>' );
				var_dump( $employees );
				print( '</code></pre>' );
				return ob_get_clean();
			}
		}
		
		/**
		 * Retrieve the default shortcode parameters
		 */
		function get_defaults() {
			return array(
				'type'       => 'summary', 
				'department' => null, 
				'building'   => null, 
				'username'   => null, 
			);
		}
	}
}