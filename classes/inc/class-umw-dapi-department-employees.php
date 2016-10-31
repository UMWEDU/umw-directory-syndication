<?php
class UMW_DAPI_Department_Employees extends Types_Relationship_API {
	function __construct() {
		$this->route = 'department/employee';
		$this->parent_type = 'department';
		$this->child_type = 'employee';
		$this->interim_type = 'office';
		
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
