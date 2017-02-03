<?php
/**
 * UMW Directory - Full Bio Template
 */
namespace UMW_Directory_API_Templates;

class Full_Bio extends Base_Template {
	/**
	 * Holds the class instance.
	 *
	 * @since   1.0.1
	 * @access	private
	 * @var		Full_Bio
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   1.0.1
	 * @return	Full_Bio
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}
	
	/**
	 * Process an individual item and return the formatted data
	 *
	 * @param  stdClass an individual employee post object from the API
	 *
	 * @access public
	 * @since  1.0.1
	 * @return string the formatted employee bio
	 */
	public function do_item( $employee ) {
	}
}