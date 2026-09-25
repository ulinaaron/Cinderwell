<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Opt Events into the shared Page Header contract. */
class Page_Header_Integration {
	public function __construct() {
		add_filter( 'cinderwell_page_header_post_types', [ $this, 'post_types' ] );
		add_filter( 'cinderwell_page_header_post_type_features', [ $this, 'features' ] );
		add_filter( 'cinderwell_page_header_terms', [ $this, 'terms' ], 10, 3 );
		add_filter( 'cinderwell_page_header_breadcrumbs', [ $this, 'breadcrumbs' ], 10, 2 );
		add_filter( 'cinderwell_page_header_context', [ $this, 'archive_context' ], 10, 3 );
	}

	public function post_types( $post_types ) {
		$post_types[] = Event_Post_Type::POST_TYPE;
		return $post_types;
	}

	public function features( $features ) {
		$features[ Event_Post_Type::POST_TYPE ] = [
			'date'       => false,
			'terms'      => true,
			'termsLabel' => __( 'Event categories', 'cinderwell-events' ),
			'titleHelp'  => __( 'Leave blank to use the Event title.', 'cinderwell-events' ),
		];
		return $features;
	}

	public function terms( $terms, $post_id, $post_type ) {
		if ( Event_Post_Type::POST_TYPE !== $post_type ) {
			return $terms;
		}
		$terms = [];
		$event_terms = get_the_terms( $post_id, Event_Post_Type::TAXONOMY );
		if ( is_array( $event_terms ) ) {
			foreach ( $event_terms as $term ) {
				$terms[] = [ 'label' => $term->name, 'url' => get_term_link( $term ) ];
			}
		}
		return $terms;
	}

	public function breadcrumbs( $items, $post_id ) {
		if ( Event_Post_Type::POST_TYPE !== get_post_type( $post_id ) ) {
			return $items;
		}
		return [
			[ 'label' => __( 'Home', 'cinderwell-events' ), 'url' => home_url( '/' ) ],
			[ 'label' => __( 'Events', 'cinderwell-events' ), 'url' => get_post_type_archive_link( Event_Post_Type::POST_TYPE ) ],
			[ 'label' => get_the_title( $post_id ), 'url' => '' ],
		];
	}

	public function archive_context( $data, $attributes, $post_id ) {
		if ( is_post_type_archive( Event_Post_Type::POST_TYPE ) ) {
			$data['title'] = __( 'Events', 'cinderwell-events' );
			$data['terms'] = [];
			$data['post_date'] = null;
			$data['breadcrumbs'] = [
				[ 'label' => __( 'Home', 'cinderwell-events' ), 'url' => home_url( '/' ) ],
				[ 'label' => __( 'Events', 'cinderwell-events' ), 'url' => '' ],
			];
		} elseif ( is_tax( Event_Post_Type::TAXONOMY ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$data['title']       = $term->name;
				$data['description'] = wp_strip_all_tags( term_description( $term ) );
				$data['terms']       = [];
				$data['post_date']   = null;
				$data['breadcrumbs'] = [
					[ 'label' => __( 'Home', 'cinderwell-events' ), 'url' => home_url( '/' ) ],
					[ 'label' => __( 'Events', 'cinderwell-events' ), 'url' => get_post_type_archive_link( Event_Post_Type::POST_TYPE ) ],
					[ 'label' => $term->name, 'url' => '' ],
				];
			}
		}
		return $data;
	}
}
