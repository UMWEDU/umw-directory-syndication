<?php
if ( ! class_exists( 'UMW_Directory_Rest_Controller' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/class-umw-directory-rest-controller.php' );
}

if ( ! class_exists( 'UMW_Directory_API' ) ) {
	class UMW_Directory_API {
		public $is_directory = false;
		public $directory_url = null;
		public $version = 'v1';
		
		function __construct() {
			$this->is_directory_site();
			
			add_shortcode( 'umw-directory', array( $this, 'do_shortcode' ) );
			$this->urls = array(
				'office'     => '/wp-json/wp/v2/office', 
				'department' => '/wp-json/wp/v2/department', 
				'building'   => '/wp-json/wp/v2/building', 
				'employee'   => '/wp-json/wp/v2/employee'
			);
		}
		
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
			/**
			 * This doesn't seem to do anything, but we tried to whitelist
			 * 		the meta items we want to retrieve
			 */
			add_filter( 'rest_public_meta_keys', array( $this, 'whitelist_custom_post_meta' ) );
			
			/**
			 * Set up some custom endpoints for the API to retrieve Types-related posts
			 */
			$root = 'umwdir';
			$version = $this->version;
			$cb_class = \UMW_Directory_Rest_Controller;
			
			/**
			 * Set up an endpoint to retrieve employees that belong to a department
			 * @param int $parent_id the ID of the department
			 * @param string $slug the slug of the department (if you can't provide the ID)
			 */
			register_rest_route( "{$root}/{$version}", '/department/employees', array(
				array(
					'methods'         => \WP_REST_Server::READABLE,
					'callback'        => array( $cb_class, 'get_items' ),
					'args'            => array(
						'per_page' => array(
							'default' => 10,
							'sanitize_callback' => 'absint',
						),
						'page' => array(
							'default' => 1,
							'sanitize_callback' => 'absint',
						),
						'parent' => array(
							'default' => 'department',
							'sanitize_callback' => array( $this, 'valid_post_types' ),
						),
						'child' => array( 
							'default' => 'employee', 
							'sanitize_callback' => array( $this, 'valid_post_types' ), 
						), 
						'interim' => array(
							'default' => 'office', 
							'sanitize_callback' => array( $this, 'valid_post_types' ), 
						), 
						'parent_id' => array(
							'default' => 0, 
							'sanitize_callback' => 'absint', 
						), 
						'slug' => array(
							'default' => false,
							'sanitize_callback' => 'sanitize_title',
						)
					),
		
					'permission_callback' => array( $this, 'permissions_check' )
				),
			) );
		}
		
		function valid_post_types( $type=null ) {
			if ( ! in_array( $type, array( 'department', 'employee', 'office', 'building' ) ) )
				return false;
			
			return $type;
		}
		
		function permissions_check() {
			return true;
		}
		
		function whitelist_custom_post_meta( $keys=array() ) {
			return array_merge( $keys, array( '_wpcf_belongs_employee_id', '_wpcf_belongs_department_id', '_wpcf_belongs_building_id' ) );
		}
		
		function do_shortcode( $atts=array() ) {
			$atts = shortcode_atts( $this->get_defaults(), $atts, 'umw-directory' );
			/*print( '<pre><code>' );
			var_dump( $atts );
			print( '</code></pre>' );
			wp_die( 'Got this far' );*/
			
			if ( ! empty( $atts['department'] ) ) {
				$meta_key = '_wpcf_belongs_department_id';
				if ( ! is_numeric( $atts['department'] ) ) {
					$atts['department'] = $this->get_department_id( $atts['department'] );
				}
				if ( empty( $atts['department'] ) )
					return;
				
				$url = untrailingslashit( $this->directory_url ) . '/wp-json/umdir/v1/types-relationship';
				$url = add_query_arg( array(
					'parent_id' => $atts['department'], 
					'per_page'  => 200, 
				), $url );
				
				$list = wp_remote_request( $url );
				print( '<pre><code>' );
				var_dump( @json_decode( $list ) );
				print( '</code></pre>' );
				die();
				
				$list = $this->retrieve_posts( 'office', $meta_key, $atts['department'] );
				print( '<pre><code>' );
				var_dump( $list );
				print( '</code></pre>' );
				die();
				if ( empty( $list ) || ! is_array( $list ) ) {
					return false;
				}
				
				$emps = array();
				foreach ( $list as $o ) {
					$emps[] = $o->id;
				}
				
				$employees = $this->retrieve_posts( 'employee', 'include', $emps );
				ob_start();
				print( '<pre><code>' );
				var_dump( $employees );
				print( '</code></pre>' );
				return ob_get_clean();
			}
		}
		
		function get_department_id( $name ) {
			$depts = $this->retrieve_posts( 'department' );
			/*print( '<pre><code>' );
			var_dump( $depts );
			print( '</code></pre>' );*/
			if ( empty( $depts ) || ! is_array( $depts ) ) {
				return false;
			}
			
			foreach ( $depts as $dept ) {
				/*print( '<pre><code>' );
				var_dump( $dept );
				print( '</code></pre>' );*/
				
				if ( $dept->slug == $name || $dept->title->rendered == $name )
					return $dept->id;
			}
			
			return false;
		}
		
		function retrieve_posts( $type='', $key='', $val='' ) {
			if ( empty( $type ) )
				return false;
			
			$url = sprintf( '%s%s', untrailingslashit( $this->directory_url ), $this->urls[$type] );
			if ( ! empty( $key ) && ! empty( $val ) ) {
				if ( substr( $key, 0, 1 ) == '_' ) {
					$url = add_query_arg( 'meta_key', $key, $url );
					$url = add_query_arg( 'meta_value', $val, $url );
				} else {
					$url = add_query_arg( $key, $val, $url );
				}
			}
			$url = add_query_arg( 'per_page', 999, $url );
			
			print( '<pre><code>' . $url . '</code></pre>' );
			
			$done = wp_remote_request( $url );
			return @json_decode( wp_remote_retrieve_body( $done ) );
		}
		
		function get_defaults() {
			return array(
				'fields'     => 'post_title, _wpcf_title, _wpcf_email', 
				'department' => null, 
				'building'   => null, 
				'username'   => null, 
			);
		}
	}
}