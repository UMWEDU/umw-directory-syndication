<?php
if ( ! class_exists( 'Employee_Username_REST_Posts_Controller' ) ) {
	class Employee_Username_REST_Posts_Controller extends \WP_REST_Posts_Controller {
		public function __construct() {
		}
		
		public function get_route() {
			return 'employee/username';
		}
		
		function permissions_check() {
			return true;
		}
		
		/**
		 * Verify that the orderby parameter is a valid key by which to order posts
		 */
		function valid_orderby( $key ) {
			$keys = array( 
				'none', 
				'ID', 
				'author', 
				'title', 
				'name', 
				'type', 
				'date', 
				'modified', 
				'parent', 
				'rand', 
				'comment_count', 
				'menu_order', 
				'post__in'
			);
			
			if ( in_array( $key, $keys ) )
				return $key;
			
			return 'date';
		}
		
		/**
		 * Set up a proper REST response from the data we gathered
		 */
		protected function create_response( $request, $args, $data ) {
			$data = array_values( $data );
			$response    = rest_ensure_response( $data );
			$count_query = new \WP_Query();
			unset( $args['paged'] );
			$query_result = $count_query->query( $args );
			$total_posts  = $count_query->found_posts;
			$response->header( 'X-WP-Total', (int) $total_posts );
			$max_pages = ceil( $total_posts / $request['per_page'] );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );
			if ( $request['page'] > 1 ) {
				$prev_page = $request['page'] - 1;
				if ( $prev_page > $max_pages ) {
					$prev_page = $max_pages;
				}
				$prev_link = add_query_arg( 'page', $prev_page, rest_url( $this->base ) );
				$response->link_header( 'prev', $prev_link );
			}
			if ( $max_pages > $request['page'] ) {
				$next_page = $request['page'] + 1;
				$next_link = add_query_arg( 'page', $next_page, rest_url( $this->base ) );
				$response->link_header( 'next', $next_link );
			}
			return $response;
		}
		
		public function get_item( $request ) {
			$params = $request->get_params();
			
			if ( ! isset( $params['username'] ) || empty( $params['username'] ) )
				return new WP_Error( 'rest_post_invalid_id', __( 'Invalid username.' ), array( 'status' => 404 ) );
			
			$args = array(
				'posts_per_page' => $params['per_page'],
				'paged'          => $params['page'],
				'orderby'        => $params['orderby'],
				'post_type'      => 'employee', 
				'meta_query'     => array( array(
					'key'     => 'wpcf-username', 
					'value'   => $params['username'], 
					'compare' => '=', 
				) )
			);
			
			$posts_query  = new \WP_Query();
			$query_result = $posts_query->query( $args );
			
			$data = array();
			
			foreach ( $query_result as $employee ) {
				$data = $this->make_data( $employee, $data );
			}
			
			$final =  $this->create_response( $request, $args, $data );
			return $final;
		}
		
		/**
		 * Prepare individual items and gather appropriate meta data
		 */
		function make_data( $post, $data ) {
			do_action( 'types-relationship-api-pre-make-data' );
			
			$image = get_post_thumbnail_id( $post->ID );
			if ( $image ) {
				$_image = wp_get_attachment_image_src( $image, 'large' );
				if ( is_array( $_image ) ) {
					$image = $_image[0];
				}
			}
			
			$data[ $post->ID ] = array(
				'id'           => $post->ID, 
				'name'         => $post->post_title,
				'link'         => get_permalink( $post->ID ),
				'image_markup' => get_the_post_thumbnail( $post->ID, 'large' ),
				'image_src'    => $image,
				'excerpt'      => $post->post_excerpt,
				'slug'         => $post->post_name, 
				'type'         => $post->post_type, 
			);
			
			$meta = apply_filters( 'types-relationship-api-post-data', array(), $post );
			foreach ( $meta as $key => $value ) {
				$data[ $post->ID ][$key] = get_post_meta( $post->ID, $value, true );
			}
			
			do_action( 'types-relationship-api-made-data' );
			
			return $data;
		}
		
		/**
		 * Perform a query and prep the results to be returned
		 * @param stdClass $request the original request object
		 * @param array $args the query arguments
		 * @param bool $respond whether to create a REST response from the results
		 * @return mixed either an associative array of post objects or a REST response of post objects
		 */
		protected function do_query( $request, $args, $respond=true ) {
			$posts_query  = new \WP_Query();
			$query_result = $posts_query->query( $args );
			$params = $request->get_params();
			
			if ( empty( $params['parent_id'] ) )
				$params['parent_id'] = ! empty( $params['id'] ) ? intval( $params['id'] ) : 0;
			
			$data = array();
			if ( ! empty( $query_result ) ) {
				foreach ( $query_result as $post ) {
					$data = $this->make_data( $post, $data );
					/**
					 * Add some extra meta data & filter the data based on 
					 * 		what part of the relationship we're querying/returning
					 */
					if ( $post->post_type == $this->child_type ) {
						$data[$post->ID] = apply_filters( 'types-relationship-api-final-data', $data[$post->ID], $post );
						$data[$post->ID][$params['parent']] = get_post( $params['parent_id'] );
						if ( ! empty( $params['interim'] ) && array_key_exists( $post->ID, $this->interim_posts ) ) {
							$data[$post->ID][$params['interim']] = $this->interim_posts[$post->ID];
						}
					} else if ( $post->post_type == $this->parent_type ) {
						$data[$post->ID] = apply_filters( 'types-relationship-api-parent-data', $data[$post->ID], $post );
					} else if ( $post->post_type == $this->interim_type ) {
						$data[$post->ID] = apply_filters( 'types-relationship-api-interim-data', $data[$post->ID], $post );
					}
				}
			}
			
			if ( $respond ) {
				$final =  $this->create_response( $request, $args, $data );
				return $final;
			} else {
				return $data;
			}
		}
	}
}
