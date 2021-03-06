<?php
/**
 * The Directory Shortcode definitions
 *
 * @package WordPress
 * @subpackage UMW Directory Shortcode
 * @version 1.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'UMW_Directory_Shortcode' ) ) {
	class UMW_Directory_Shortcode {
		/**
		 * @var null|UMW_Directory_API holds the object that defines a lot of the methods/properties used by this class
		 */
		public $api_object = null;

		/**
		 * @var UMW_Directory_Shortcode the single instance of this object
		 * @access private
		 */
		private static $instance;

		/**
		 * @var int the version of this plugin
		 */
		public $version = '1.2';

		/**
		 * Returns the instance of this class.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return	UMW_Directory_Shortcode
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				$className = __CLASS__;
				self::$instance = new $className;
			}

			return self::$instance;
		}

		/**
		 * UMW_Directory_Shortcode constructor.
		 *
		 * @access protected
		 * @since  1.0
		 */
		protected function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load' ) );
		}

		/**
		 * Perform any startup tasks that need to happen for this plugin
		 *
		 * @access public
		 * @since  1.2
		 * @return void
		 */
		public function load() {
			if ( ! class_exists( 'UMW_Directory_API' ) ) {
				require_once plugin_dir_path( __FILE__ ) . '/class-umw-directory-api.php';
			}

			$this->api_object = UMW_Directory_API::instance();

			add_shortcode( 'umw-directory', array( $this, 'do_shortcode' ) );

			add_filter( 'wp_editor_settings', array( $this, 'enable_teeny_editor' ), 99, 2 );
		}

		/**
		 * Enables the TeenyMCE editor for specific fields
		 * @param array $settings the settings sent to the editor
		 * @param string $editor_id the HTML ID of the editor being modified
		 *
		 * @access public
		 * @since  1.1
		 * @return array()
		 */
		public function enable_teeny_editor( $settings=array(), $editor_id=null ) {
			$editors = apply_filters( 'directory-api-teeny-fields', array( 'wpcf-biography', 'wpcf-expert-publications' ) );
			if ( ! in_array( $editor_id, $editors ) ) {
				return $settings;
			}

			$settings['teeny'] = true;
			$settings['media_buttons'] = false;

			return $settings;
		}

		/**
		 * Execute the shortcode itself
		 * @param array $atts the array of arguments/attributes to send to the shortcode
		 *
		 * @access  public
		 * @since   1.2
		 * @return  string the processed HTML
		 */
		function do_shortcode( $atts=array() ) {
			$this->api_object->gather_rest_classes();

			$atts = shortcode_atts( $this->get_defaults(), $atts, 'umw-directory' );

			if ( ! empty( $atts['department'] ) ) {
				$rest_class = $this->api_object->rest_classes['department-employees'];

				$url = untrailingslashit( $this->api_object->directory_url ) . $rest_class->get_rest_url();
				if ( ! is_numeric( $atts['department'] ) ) {
					$url = add_query_arg( array(
						'slug' => $atts['department'],
						'per_page'  => 200,
					), $url );
				} else {
					$url .= '/' . intval( $atts['department'] );
					$url = add_query_arg( 'per_page', 200, $url );
				}

				print( '<pre><code>' );
				var_dump( $url );
				print( '</code></pre>' );

				$employees = @json_decode( wp_remote_retrieve_body( wp_remote_request( $url ) ) );
				ob_start();
				print( '<pre><code>' );
				var_dump( $employees );
				print( '</code></pre>' );
				return ob_get_clean();
			} else if ( ! empty( $atts['building'] ) ) {
				$rest_class = $this->api_object->rest_classes['building-employees'];

				$url = untrailingslashit( $this->api_object->directory_url ) . $rest_class->get_rest_url();
				$url = add_query_arg( array(
					'parent' => $atts['building'],
					'per_page'  => 200,
				), $url );

				$employees = @json_decode( wp_remote_request( $url ) );
				ob_start();
				print( '<pre><code>' );
				var_dump( $employees );
				print( '</code></pre>' );
				return ob_get_clean();
			}

			return '';
		}

		/**
		 * Retrieve the default shortcode parameters
		 */
		function get_defaults() {
			return array(
				'type'       => 'summary',
				'department' => null,
				'building'   => null,
				'username'   => null,
			);
		}
	}
}
