<?php
class UMW_Directory_Rest_Controller extends \WP_REST_Posts_Controller {
	public function __construct( $post_type, $base ) {
		$this->post_type = $post_type;
		$this->base = $base;
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
	
	public function get_items( $request ) {
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
			
			$q = new WP_Query;
			$r = $q->query( $args );
			$ids = array();
			global $post;
			if ( $r->have_posts() ) : while ( $r->have_posts() ) : $r->the_post(); {
				setup_postdata();
				$ids[] = get_post_meta( sprintf( '_wpcf_belongs_%s_id', $params['child'] ), true );
			}
			wp_reset_postdata();
			wp_reset_query();
			
			$args['post_type'] = $params['child'];
			$args['post__in'] = $ids;
			unset( $args['meta_query'] );
		}
		
		/*$args = $this->query_args( $params );*/
		return $this->do_query( $request, $args );
	}

}
