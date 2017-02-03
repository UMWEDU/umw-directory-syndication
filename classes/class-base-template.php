<?php
/**
 * Base class for use with UMW Directory API templates
 */
namespace UMW_Directory_API_Templates;
abstract class Base_Template {
	abstract public function instance();
	abstract public function do_template();
	abstract public function do_item();
	
	/**
	 * Process the template and return the formatted data
	 *
	 * @param  array $employees the array of employee objects returned by the API
	 *
	 * @access public
	 * @since  1.0.1
	 * @return string the formatted list of employees
	 */
	function do_template( $objects ) {
		$list = array();
		foreach ( $objects as $object ) {
			$list[] = do_item( $object );
		}
		
		return $this->wrap_items( $list );
	}
	
	abstract public function do_item( $item );
	abstract public function wrap_items( $items );
	
	/**
	 * Format a telephone number & wrap it in a tel link
	 * @uses shortcode_atts() to sanitize the list of shortcode attributes
	 * @see UMW_Outreach_Mods_Sub::do_tel_link_shortcode()
	 *
	 * @param array $atts the array of attributes sent to the shortcode
	 *		* format - the format in which the phone number should be output on the screen
	 * 		* area - the 3-digit default area code
	 * 		* exchange - the 3-digit default exchange
	 * 		* country - the 1-digit country code
	 * 		* title - the name of the person/office/place to which the phone number belongs
	 * @param string $content the telephone number that should be formatted
	 * @return the formatted string with a link around it
	 */
	function do_tel_link( $atts=array(), $content='' ) {
		$original = $content;
		$content = do_shortcode( $content );
		if ( empty( $content ) )
			return '';

		$atts = shortcode_atts( array( 'format' => '###-###-####', 'area' => '540', 'exchange' => '654', 'country' => '1', 'title' => '', 'link' => 1 ), $atts );
		if ( in_array( $atts['link'], array( 'false', false, 0, '0' ), true ) ) {
			$atts['link'] = false;
		} else {
			$atts['link'] = true;
		}
		$content = preg_replace( '/[^0-9]/', '', $content );
		$area = substr( preg_replace( '/[^0-9]/', '', $atts['area'] ), 0, 3 );
		$exchange = substr( preg_replace( '/[^0-9]/', '', $atts['exchange'] ), 0, 3 );
		$country = substr( preg_replace( '/[^0-9]/', '', $atts['country'] ), 0, 1 );
		// Let's make sure the phone number ends up having 11 digits
		switch( strlen( $content ) ) {
			/* Original number was just an extension */
			case 4 : 
				$content = $atts['country'] . $atts['area'] . $atts['exchange'] . $content;
				break;
			/* Original number was just exchange + extension */
			case 7 : 
				$content = $atts['country'] . $atts['area'] . $content;
				break;
			/* Original number included area code, exchange and extension */
			case 10 : 
				$content = $atts['country'] . $content;
				break;
			/* Original number was complete, including country code */
			case 11 : 
				break;
			/* If the original number didn't have 4, 7, 10 or 11 digits in the first place, it 
					probably wasn't valid to begin with, so just return it all by itself */
			default : 
				return $original;
		}
		/* If we somehow ended up with a number that doesn't have 11 digits, just bail out */
		if ( strlen( $content ) !== 11 )
			return $original;

		/* Set up the printf format based on the format argument; replacing number signs with digit placeholders */
		$format = str_replace( '#', '%d', $atts['format'] );
		if ( $atts['link'] ) {
			$link = '<a href="tel:+%1$s" title="%2$s">%3$s</a>';
		} else {
			$link = '%3$s';
		}
		/* Store the 11-digit all-numeric string in a var to use as the link address */
		$linknum = $content;
		/* Split the 11-digit all-numeric string into individual characters */
		$linktext = str_split( $linknum );
		/* Make sure the number that will be formatted has the right number of digits */
		$output_digits = mb_substr_count( $atts['format'], '#' );
		$linktext = array_slice( $linktext, ( 0 - absint( $output_digits ) ) );
		/* Output the phone number in the desired format */
		$format = vsprintf( $format, $linktext );
		$title = do_shortcode( $atts['title'] );
		$title = empty( $atts['title'] ) ? '' : esc_attr( 'Call ' . $title );
		$rt = sprintf( $link, $linknum, $title, $format );

		return $rt;
	}
	
	/**
	 * Retrieve and aggregate RSS feeds
	 *
	 * @param  string|array $feeds the feeds that should be retrieved
	 * @param  int $items how many items to return
	 *
	 * @access public
	 * @since  1.0.1
	 * @return array the array of items retrieved
	 */
	function get_feeds( $feeds, $items=5 ) {
		if ( ! is_array( $feeds ) ) {
			if ( stristr( $feeds, ',' ) ) {
				$feeds = explode( ',', $feeds );
			} else {
				$feeds = array( $feeds );
			}
		}
		
		foreach ( $feeds as $feed ) {
			// Retrieve the feed and process it. Use the date/time as the key for each array item
		}
		
		$items = ksort( $items );
		return $items;
	}
}