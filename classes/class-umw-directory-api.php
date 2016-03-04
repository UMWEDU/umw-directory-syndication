<?php
if ( ! class_exists( 'UMW_Directory_API' ) ) {
	class UMW_Directory_API extends Types_Relationship_API {
		public $is_directory  = false;
		public $directory_url = null;
		public $rest_classes  = array();
		
		function __construct() {
			/**
			 * Determine whether this is the main directory site or not
			 */
			$this->is_directory_site();
			
			$this->rest_classes = array(
				'department-employees' => new UMW_DAPI_Department_Employees, 
				'building-employees'   => new UMW_DAPI_Building_Employees, 
				'employee-departments' => new UMW_DAPI_Employee_Departments, 
			);
			
			/**
			 * Set up our shortcode
			 */
			add_shortcode( 'umw-directory', array( $this, 'do_shortcode' ) );
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
		
		function register_routes() {
			foreach ( $this->rest_classes as $c ) {
				$c->register_routes();
			}
		}
		
		function do_shortcode( $atts=array() ) {
			$atts = shortcode_atts( $this->get_defaults(), $atts, 'umw-directory' );
			
			if ( ! empty( $atts['department'] ) ) {
				$rest_class = $this->rest_classes['department-employees'];
				
				$url = untrailingslashit( $this->directory_url ) . $rest_class->get_rest_url();
				$url = add_query_arg( array(
					'parent_id' => $atts['department'], 
					'per_page'  => 200, 
				), $url );
				
				$employees = @json_decode( wp_remote_request( $url ) );
				ob_start();
				print( '<pre><code>' );
				var_dump( $employees );
				print( '</code></pre>' );
				return ob_get_clean();
			} else if ( ! empty( $atts['building'] ) ) {
				$rest_class = $this->rest_classes['building-employees'];
				
				$url = untrailingslashit( $this->directory_url ) . $rest_class->get_rest_url();
				$url = add_query_arg( array( 
					'parent_id' => $atts['building'], 
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

class UMW_DAPI_Department_Employees extends Types_Relationship_API {
	function __construct() {
		$this->route = 'department/employee';
		$this->parent_type = 'department';
		$this->child_type = 'employee';
		$this->interim_type = 'office';
	}
	
	function add_meta_data( $data, $post, $final=false ) {
		if ( $post->post_type != $this->child_type )
			return $data;
		
		$post_id = $post->ID;
		$rt = array();
		/**
		 * General employee information
		 */
		$keys = array( 'blurb', 'email', 'phone', 'website', 'photo', 'room', 'username', 'biography' );
		$rt['job-title'] = get_post_meta( $post_id, '_wpcf_title', true );
		foreach ( $keys as $key ) {
			$rt[$key] = get_post_meta( $post_id, sprintf( '_wpcf_%s', $key ), true );
		}
		/**
		 * Advanced employee information
		 */
		$keys = array( 
			'degrees', 
			'ph-d', 
			'facebook', 
			'twitter', 
			'instagram', 
			'linkedin', 
			'academia', 
			'google-plus', 
			'tumblr', 
			'pinterest', 
			'vimeo', 
			'flickr', 
			'youtube', 
		);
		foreach ( $keys as $key ) {
			$rt[$key] = get_post_meta( $post_id, sprintf( '_wpcf_%s', $key ), true );
		}
		
		foreach ( $rt as $k=>$v ) {
			switch( $k ) {
				case 'email' : 
					$rt[$k] = is_email( $v );
					break;
				case 'room' : 
				case 'blurb' : 
				case 'biography' : 
				case 'username' : 
				case 'degrees' : 
					$rt[$k] = esc_attr( $v );
					break;
				case 'ph-d' : 
					$rt[$k] = absint( $v );
					break;
				default : 
					$rt[$k] = esc_url( $v );
					break;
			}
		}
		
		return array_merge( $rt, $data );
	}
}

class UMW_DAPI_Building_Employees extends Types_Relationship_API {
	function __construct() {
		$this->route = 'building/employee';
		$this->parent_type = 'building';
		$this->child_type = 'employee';
		$this->interim_type = null;
	}
	
	function add_meta_data( $data, $post ) {
		if ( $post->post_type != $this->parent_type )
			return $data;
		
		return $data;
	}
}

class UMW_DAPI_Employee_Departments extends Types_Relationship_API {
	function __construct() {
		$this->route = 'employee/building';
		$this->parent_type = 'employee';
		$this->child_type = 'building';
		$this->interim_type = 'office';
	}
	
	function add_meta_data( $data, $post ) {
		if ( $post->post_type != $this->parent_type )
			return $data;
		
		return $data;
	}
}
