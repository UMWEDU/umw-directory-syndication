<?php
class UMW_DAPI_Building_Employees extends Types_Relationship_API {
	function __construct() {
		$this->route = 'building/employee';
		$this->parent_type = 'building';
		$this->child_type = 'employee';
		$this->interim_type = null;
		
		add_filter( 'types-relationship-api-valid-rest-arguments', array( $this, 'rest_arguments' ) );
		add_filter( 'types-relationship-api-query-args', array( $this, 'query_args' ), 10, 2 );
		
		parent::__construct();
	}
	
	function add_meta_data( $data, $post ) {
		if ( $post->post_type != $this->child_type )
			return $data;
		
		$rt = array(
			'blurb' => 'wpcf-blurb', 
			'email' => 'wpcf-email', 
			'phone' => 'wpcf-phone', 
			'website' => 'wpcf-website', 
			'photo' => 'wpcf-photo', 
			'room' => 'wpcf-room', 
			'username' => 'wpcf-username', 
			'biography' => 'wpcf-biography', 
			'job-title' => 'wpcf-title', 
			'degrees' => 'wpcf-degrees', 
			'ph-d' => 'wpcf-ph-d', 
			'facebook' => 'wpcf-facebook', 
			'twitter' => 'wpcf-twitter', 
			'instagram' => 'wpcf-instagram', 
			'linkedin' => 'wpcf-linkedin', 
			'academia' => 'wpcf-academia', 
			'google-plus' => 'wpcf-google-plus', 
			'tumblr' => 'wpcf-tumblr', 
			'pinterest' => 'wpcf-pinterest', 
			'vimeo' => 'wpcf-vimeo', 
			'flickr' => 'wpcf-flickr', 
			'youtube' => 'wpcf-youtube', 
		);
		
		return array_merge( $data, $rt );
	}
	
	/**
	 * Add some extra rest arguments for this particular route
	 *
	 * @param array $args the array of valid rest arguments
	 * @return array the updated list of arguments
	 */
	function rest_arguments( $args ) {
		$new_args = array(
			'employee_type' => array(
				'default' => null, 
				'sanitize_callback' => array( $this, 'is_valid_employee_type' )
			),
		);
		
		return array_merge( $args, $new_args );
	}
	
	/**
	 * Ensure that a valid employee-type term is provided as that argument
	 *
	 * @param mixed $type the type being checked
	 * @return int|bool the term ID or false if not valid
	 */
	function is_valid_employee_type( $type ) {
		if ( is_numeric( $type ) ) {
			$tax = get_term_by( 'term_id', $type, 'employee-type' );
			if ( is_wp_error( $tax ) || empty( $tax ) ) {
				return false;
			}
		} else {
			$tax = get_term_by( 'slug', $type, 'employee-type' );
			if ( is_wp_error( $tax ) || empty( $tax ) ) {
				$tax = get_term_by( 'name', $type, 'employee-type' );
			}
			if ( is_wp_error( $tax ) || empty( $tax ) ) {
				return false;
			}
		}
		
		return absint( $tax->term_id );
	}
	
	/**
	 * Modify the array of query arguments in order to potentially filter
	 * 		by additional parameters that are specific to this route
	 *
	 * @param array $args the existing array of arguments
	 * @param array $params the parameters sent through the API request
	 * @return array the filtered array of arguments
	 */
	function query_args( $args, $params=array() ) {
		if ( ! array_key_exists( 'employee_type', $params ) || empty( $params['employee_type'] ) ) {
			return $args;
		}
		
		if ( ! array_key_exists( 'tax_query', $args ) ) {
			$args['tax_query'] = array();
		}
		
		if ( ! empty( $params['employee_type'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'employee-type', 
				'field'    => 'term_id', 
				'terms'    => array( $params['employee_type'] ),
			);
		}
		
		/*print( '<pre><code>' );
		var_dump( $args );
		print( '</code></pre>' );
		print( '<pre><code>' );
		var_dump( $params );
		print( '</code></pre>' );
		wp_die( 'Args/Params' );*/
		
		return $args;
	}
	
	/**
	 * Create an array of taxonomies that should be retrieved for the object
	 */
	function add_taxonomies( $taxes=array(), $post ) {
		if ( $post->post_type != $this->child_type )
			return $taxes;
		
		$rt = array(
			'employee-type' => 'employee-type', 
			'expertise'     => 'expertise', 
			'relationship'  => 'relationship', 
		);
		
		return array_merge( $taxes, $rt );
	}
}
