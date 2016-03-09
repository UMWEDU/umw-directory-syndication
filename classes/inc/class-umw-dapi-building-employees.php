<?php
class UMW_DAPI_Building_Employees extends Types_Relationship_API {
	function __construct() {
		$this->route = 'building/employee';
		$this->parent_type = 'building';
		$this->child_type = 'employee';
		$this->interim_type = null;
		
		parent::__construct();
	}
	
	function add_meta_data( $data, $post ) {
		if ( $post->post_type != $this->parent_type )
			return $data;
		
		return $data;
	}
}
