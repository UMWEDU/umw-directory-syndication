<?php
class UMW_DAPI_Employee_Departments extends Types_Relationship_API {
	function __construct() {
		$this->route = 'employee/department';
		$this->parent_type = 'employee';
		$this->child_type = 'department';
		$this->interim_type = 'office';
		
		parent::__construct();
	}
	
	function add_meta_data( $data, $post ) {
		if ( $post->post_type != $this->parent_type )
			return $data;
		
		return $data;
	}
	
	/**
	 * Create an array of taxonomies that should be retrieved for the object
	 * We currently have no taxonomies applied to departments, so nothing to return
	 */
	function add_taxonomies( $taxes=array(), $post ) {
		return $taxes;
	}
}
