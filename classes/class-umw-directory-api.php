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
		 * @var UMW_Directory_API the single instance of this object
		 * @access private
		 */
		private static $instance;

		/**
		 * Returns the instance of this class.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return	UMW_Directory_API
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				$className = __CLASS__;
				self::$instance = new $className;
			}

			return self::$instance;
		}

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
			add_action( 'setup_theme', array( $this, 'is_directory_site' ) );

			/**
			 * Determine whether we should be bypassing the CAS authentication system
			 */
			add_action( 'setup_theme', array( $this, 'maybe_bypass_cas' ) );

			/**
			 * Register all of the appropriate REST API features
			 */
			add_action( 'rest_api_init', array( $this, 'setup_rest_classes' ) );

			/**
			 * Set up our shortcode
			 */
			/*add_action( 'init', array( $this, '_add_extra_api_post_type_arguments' ), 12 );*/
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
		 * Attempt to bypass CAS authentication when hitting the API
		 *
		 * @access private
		 * @since  0.1
		 * @return void
		 */
		private function maybe_bypass_cas() {
			if ( isset( $_SERVER['PHP_AUTH_USER'] ) && ! defined( 'WPCAS_BYPASS' ) )
				define( 'WPCAS_BYPASS', true );

			return;
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
			foreach ( array( 'username', 'first-name', 'last-name', 'title', 'email', 'phone', 'office-room-number', 'building', 'department' ) as $f ) {
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
		 * Retrieve the value of a custom field
		 * @param WP_Post $object the current post being queried
		 * @param string $field_name the name of the field to be retrieved
		 * @param unused $request an unused parameter
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
				case 'employee_department' :
					global $wpdb;
					$office = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%d", '_wpcf_belongs_employee_id', $object['id'] ) );
					return get_post_meta( $office, '_wpcf_belongs_department_id' );
					break;
				default :
					$field_name = str_replace( array( 'employee_', 'office_' ), 'wpcf-', $field_name );
					break;
			}

			return get_post_meta( $object[ 'id' ], $field_name );
		}

		/**
		 * Update/set the value of a custom field
		 * @param mixed $value the value to save for the custom field
		 * @param WP_Post $object the current post being updated
		 * @param string $field_name the name of the field being updated
		 *
		 * @uses update_post_meta()
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  mixed the results of update_post_meta()
		 */
		function update_types_field( $value, $object, $field_name ) {
			if ( ! $value || ! is_string( $value ) ) {
				return false;
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
	}
}
