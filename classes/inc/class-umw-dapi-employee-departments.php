<?php
class UMW_DAPI_Employee_Departments extends Types_Relationship_API {
	function __construct() {
		$this->route = 'employee/building';
		$this->parent_type = 'employee';
		$this->child_type = 'building';
		$this->interim_type = 'office';
		
		parent::__construct();
	}
	
	function add_meta_data( $data, $post ) {
		if ( $post->post_type != $this->parent_type )
			return $data;
		
		return $data;
	}
}
