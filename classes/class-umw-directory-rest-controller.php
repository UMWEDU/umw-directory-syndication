<?php
class UMW_Directory_Rest_Controller extends \WP_REST_Posts_Controller {
	public function __construct() {
	}
	
	protected function query_args( $params ) {
		$per_page = $params['per_page'];
		$args = array(
			'posts_per_page' => $per_page,
			'paged'          => $params[ 'page' ],
			'post_type'      => $this->post_type,
			'orderby'        => 'title',
			'meta_key'       => sprintf( '_wpcf_belongs_%s_id', $params['parent'] ),
			'meta_value'     => $params['id'], 
		);
		
		return $args;
	}
	
	protected function do_query( $request, $args, $respond = true ) {
		$posts_query  = new \WP_Query();
		$query_result = $posts_query->query( $args );
		
		$data = array();
		if ( ! empty( $query_result ) ) {
			foreach ( $query_result as $post ) {
				$data = $this->make_data( $post, $data );
			}
		}
		if ( $respond ) {
			return $this->create_response( $request, $args, $data );
		} else {
			return $data;
		}
	}
	
	protected function create_response( $request, $args, $data ) {
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
	
	/**
	 * This is somewhat of a boilerplate function just to make sure
	 * 		we know where we started. It shouldn't be used
	 */
	public function get_items( $request ) {
		/**
		 * Short-circuit this function, since we aren't 
		 * 		actually using it.
		 */
		return $this->get_rest_items( $request );
		
		$params = $request->get_params();
		if ( $params[ 'slug' ] ) {
			$args[ 'name' ] = $params[ 'slug' ];
			$args[ 'post_type' ] = $this->post_type;
		}elseif( $params[ 'soon' ] ) {
			$args[ 'meta_key' ] = 'edd_coming_soon';
			$args[ 'meta_value' ] = true;
		}else{
			$args = $this->query_args( $params );
		}
		return $this->do_query( $request, $args );
	}
	
	/**
	 * Attempt to retrieve a list of items that match the request
	 */
	function get_rest_items( $request ) {
		$params = $request->get_params();
		
		$args = array(
			'posts_per_page' => $params['per_page'],
			'paged'          => $params[ 'page' ],
			'orderby'        => 'title',
		);
		if ( empty( $params['parent_id'] ) && ! empty( $params['slug'] ) ) {
			$department = get_page_by_path( $params['slug'] );
			if ( ! is_wp_error( $department ) )
				$params['parent_id'] = $department->ID;
		}
		if ( empty( $params['interim'] ) ) {
			$args['post_type'] = $params['child'];
			$args['meta_query'] = array( array(
				'key' => sprintf( '_wpcf_belongs_%s_id', $params['parent'] ), 
				'value' => $params['parent_id'], 
			) );
		} else {
			$args['post_type'] = $params['interim'];
			$args['meta_query'] = array( array(
				'key' => sprintf( '_wpcf_belongs_%s_id', $params['parent'] ), 
				'value' => $params['parent_id'], 
			) );
			
			$q = new WP_Query( $args );
			$ids = array();
			global $post;
			$meta_key = sprintf( '_wpcf_belongs_%s_id', $params['child'] );
			if ( $q->have_posts() ) : while ( $q->have_posts() ) : $q->the_post();
				setup_postdata( $post );
				$tmp = get_post_meta( $post->ID, sprintf( '_wpcf_belongs_%s_id', $params['child'] ), true );
				if ( ! empty( $tmp ) ) {
					$ids[$tmp] = clone $post;
				}
			endwhile; endif;
			wp_reset_postdata();
			wp_reset_query();
			
			$args['post_type'] = $params['child'];
			$args['post__in'] = array_keys( $ids );
			unset( $args['meta_query'] );
		}
		
		/**
		 * Begin process of performing the final query
		 * @see UMW_Directory_Rest_Controller::do_query()
		 */
		$posts_query  = new \WP_Query();
		$query_result = $posts_query->query( $args );
		
		$data = array();
		if ( ! empty( $query_result ) ) {
			foreach ( $query_result as $post ) {
				$data = $this->make_data( $post, $data );
				$data[$post->ID][$params['parent']] = get_post( $params['parent_id'] );
				if ( ! empty( $params['interim'] ) && array_key_exists( $post->ID, $ids ) ) {
					$data[$post->ID][$params['interim']] = $ids[$post->ID];
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
	
	/**
	 * Add current post to response data for this route.
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $post Current post object.
	 * @param array $data Current collection of data
	 *
	 * @return array
	 */
	protected function make_data( $post, $data ) {
		$image = get_post_thumbnail_id( $post->ID );
		if ( $image ) {
			$_image = wp_get_attachment_image_src( $image, 'large' );
			if ( is_array( $_image ) ) {
				$image = $_image[0];
			}
		}
		$data[ $post->ID ] = array(
			'name'         => $post->post_title,
			'link'         => get_permalink( $post->ID ),
			'image_markup' => get_the_post_thumbnail( $post->ID, 'large' ),
			'image_src'    => $image,
			'excerpt'      => $post->post_excerpt,
			'slug'         => $post->post_name, 
		);
		switch( $post->post_type ) {
			case 'employee' : 
				$data[ $post->ID ] = $this->fill_employee_data( $post, $data );
				break;
			case 'department' : 
				$data[ $post->ID ] = $this->fill_department_data( $post, $data );
				break;
			case 'building' : 
				$data[ $post->ID ] = $this->fill_building_data( $post, $data );
				break;
		}
		return $data;
	}
	
	/**
	 * Retrieve any additional meta data we need for employees
	 * @param stdClass $post the post object
	 * @param array $data the array of posts (you should only use/manipulate 
	 * 		the array item related to the current post)
	 * @return array the array of post data related to the current post
	 */
	function fill_employee_data( $post, $data ) {
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
		
		return array_merge( $rt, $data[$post_id] );
	}
	
	/**
	 * Retrieve any additional meta data we need for departments
	 * @param stdClass $post the post object
	 * @param array $data the array of posts (you should only use/manipulate 
	 * 		the array item related to the current post)
	 * @return array the array of post data related to the current post
	 */
	function fill_department_data( $post, $data ) {
		return $data[$post->ID];
	}
	
	/**
	 * Retrieve any additional meta data we need for buildings
	 * @param stdClass $post the post object
	 * @param array $data the array of posts (you should only use/manipulate 
	 * 		the array item related to the current post)
	 * @return array the array of post data related to the current post
	 */
	function fill_building_data( $post, $data ) {
		return $data[$post->ID];
	}
}
