<?php
/**
 * Implements the main API class for the UMW Directory Syndication
 *
 * @package    wordpress
 * @subpackage umw-directory-syndication
 * @version    1.2
 */
if ( ! class_exists( 'UMW_Directory_API' ) ) {
	class UMW_Directory_API {
		/**
		 * @var bool whether this code is being executed on the main directory site or not
		 */
		public $is_directory  = false;
		/**
		 * @var null|string the URL for the main directory site
		 */
		public $directory_url = null;
		/**
		 * @var Types_Relationship_API[] the array of extended Types_Relationship_API classes that are used by this plugin
		 */
		public $rest_classes  = array();
		
		/**
		 * Build the UMW_Directory_API object
		 *
		 * @access public
		 * @since  1.0.1
		 */
		function __construct() {
			/**
			 * Determine whether this is the main directory site or not
			 */
			$this->is_directory_site();
			
			add_action( 'rest_api_init', array( $this, 'setup_rest_classes' ) );
			
			/**
			 * Set up our shortcode
			 */
			add_shortcode( 'umw-directory', array( $this, 'do_shortcode' ) );
			add_action( 'init', array( $this, '_add_extra_api_post_type_arguments' ), 12 );
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
				if ( ! defined( 'UMW_EMPLOYEE_DIRECTORY' ) ) {
					define( 'UMW_EMPLOYEE_DIRECTORY', 'https://www.umw.edu/directory' );
				}
				$this->is_directory = false;
				$this->directory_url = esc_url( UMW_EMPLOYEE_DIRECTORY );
			}
		}
		
		/**
		 * Ensure that the appropriate post types are exposed in the REST API on 
		 * 		the directory site, regardless of how they're initially registered
		 */
		function _add_extra_api_post_type_arguments() {
			global $wp_post_types;
			
			foreach ( array( 'employee', 'building', 'department', 'office' ) as $post_type ) {
				if ( ! array_key_exists( $post_type, $wp_post_types ) )
					continue;
				
				$wp_post_types[$post_type]->show_in_rest = true;
				$wp_post_types[$post_type]->rest_base = $post_type;
				$wp_post_types[$post_type]->rest_controller_class = 'WP_REST_Posts_Controller';
			}
		}
		
		/**
		 * Identify and include the REST classes that are used by this plugin
		 */
		function gather_rest_classes() {
			if ( ! class_exists( 'Types_Relationship_API' ) ) {
				require_once( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . '/types-relationship-api/classes/class-types-relationship-api.php' );
			}
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
		 * Register and include all of the REST classes we'll need
		 */
		function setup_rest_classes() {
			$this->gather_rest_classes();
			
			$this->register_rest_fields();
		}
		
		/**
		 * Register a new REST route to retrieve a specific employee by username
		 */
		function register_rest_route_employee_by_username() {
			require_once( plugin_dir_path( __FILE__ ) . 'class-employee-username-rest-posts-controller.php' );
			
			$cb_class = new \Employee_Username_REST_Posts_Controller;
			
			$rest_args = array(
				'per_page' => array(
					'default' => 10,
					'sanitize_callback' => 'absint',
				),
				'orderby' => array(
					'default' => 'title', 
					'sanitize_callback' => array( $cb_class, 'valid_orderby' ), 
				), 
				'page' => array(
					'default' => 1,
					'sanitize_callback' => 'absint',
				),
				'parent_id' => array(
					'default' => 0, 
					'sanitize_callback' => 'absint', 
				), 
				'slug' => array(
					'default' => false,
					'sanitize_callback' => 'sanitize_title',
				)
			);
			
			$root = 'wp';
			$version = 'v2';
			
			register_rest_route( "{$root}/{$version}", $cb_class->get_route() . '/(?P<username>[\w]+)', array(
				array(
					'methods'         => \WP_REST_Server::READABLE,
					'callback'        => array( $cb_class, 'get_item' ),
					'args'            => $rest_args, 
		
					'permission_callback' => array( $cb_class, 'permissions_check' )
				),
			) );
		}
		
		/**
		 * Register the custom field API endpoints
		 */
		function register_rest_fields() {
			foreach ( array( 'username', 'first-name', 'last-name', 'title', 'email', 'phone', 'office-room-number', 'building' ) as $f ) {
				register_rest_field( 
					'employee', 
					sprintf( 'employee_%s', $f ), 
					array( 
						'get_callback'    => array( $this, 'get_types_field' ), 
						'update_callback' => array( $this, 'update_types_field' ), 
						'schema'          => null,
					)
				);
			}
			
			foreach ( array( 'department', 'employee', 'empdept-rel-last-name', 'empdept-rel-first-name' ) as $field ) {
				register_rest_field( 
					'office', 
					sprintf( 'office_%s', $field ), 
					array( 
						'get_callback'    => array( $this, 'get_types_field' ), 
						'update_callback' => array( $this, 'update_types_field' ), 
						'schema'          => null, 
					)
				);
			}
			
			add_filter( 'is_protected_meta', array( $this, 'unprotect_meta' ), 10, 3 );
		}
		
		/**
		 * Attempt to whitelist the "protected meta" fields that need to be
		 * 		updatable through the API
		 *
		 * @param  bool $protected whether or not the meta value is a protected field
		 * @param  string $key the meta key to query
		 * @param  string $type unused
		 *
		 * @access public
		 * @since  1.0
		 * @return bool whether the key should be protected or not
		 */
		function unprotect_meta( $protected, $key, $type ) {
			if ( in_array( $key, array( '_wpcf_belongs_employee_id', '_wpcf_belongs_department_id', '_wpcf_belongs_building_id' ) ) ) {
				return false;
			}
			
			return $protected;
		}
		
		/**
		 * Set up the API procedures for the directory site
		 */
		function setup_directory_site() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ), 11 );
		}
		
		/**
		 * Call the appropriate method to register the necessary REST API routes
		 */
		function register_routes() {
			foreach ( $this->rest_classes as $c ) {
				$c->register_routes();
			}
			
			$this->register_rest_route_employee_by_username();
		}
		
		/**
		 * Execute the shortcode itself
		 */
		function do_shortcode( $atts=array() ) {
			$this->gather_rest_classes();
			
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
		 * Retrieve the value of a custom field
		 */
		function get_types_field( $object, $field_name, $request ) {
			switch( $field_name ) {
				case 'office_employee' : 
					$field_name = '_wpcf_belongs_employee_id';
					break;
				case 'office_department' : 
					$field_name = '_wpcf_belongs_department_id';
					break;
				case 'employee_building' : 
					$field_name = '_wpcf_belongs_building_id'; 
					break;
				default : 
					$field_name = str_replace( array( 'employee_', 'office_' ), 'wpcf-', $field_name );
					break;
			}
				
			return get_post_meta( $object[ 'id' ], $field_name );
		}
		
		/**
		 * Update/set the value of a custom field
		 */
		function update_types_field( $value, $object, $field_name ) {
			if ( ! $value || ! is_string( $value ) ) {
				return;
			}
			
			switch( $field_name ) {
				case 'office_employee' : 
					$field_name = '_wpcf_belongs_employee_id';
					break;
				case 'office_department' : 
					$field_name = '_wpcf_belongs_department_id';
					break;
				case 'employee_building' : 
					$field_name = '_wpcf_belongs_building_id'; 
					break;
				default : 
					$field_name = str_replace( array( 'employee_', 'office_' ), 'wpcf-', $field_name );
					break;
			}
						
			return update_post_meta( $object->ID, $field_name, strip_tags( $value ) );
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