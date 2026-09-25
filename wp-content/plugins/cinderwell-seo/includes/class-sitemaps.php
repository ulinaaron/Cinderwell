<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Configure WordPress native sitemap providers and object queries. */
class Sitemaps {
	public function __construct() {
		add_filter( 'wp_sitemaps_post_types', [ $this, 'post_types' ] );
		add_filter( 'wp_sitemaps_taxonomies', [ $this, 'taxonomies' ] );
		add_filter( 'wp_sitemaps_posts_query_args', [ $this, 'post_query' ], 10, 2 );
		add_filter( 'wp_sitemaps_taxonomies_query_args', [ $this, 'term_query' ], 10, 2 );
	}

	public function post_types( $post_types ) {
		$enabled = Settings::enabled_post_types();
		foreach ( $post_types as $name => $object ) {
			if ( ! in_array( $name, $enabled, true ) ) {
				unset( $post_types[ $name ] );
			}
		}
		return $post_types;
	}

	public function taxonomies( $taxonomies ) {
		$enabled = Settings::enabled_taxonomies();
		foreach ( $taxonomies as $name => $object ) {
			if ( ! in_array( $name, $enabled, true ) ) {
				unset( $taxonomies[ $name ] );
			}
		}
		return $taxonomies;
	}

	public function post_query( $args, $post_type ) {
		$args['meta_query']  = $this->query_constraints( $args['meta_query'] ?? [] );
		$args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? [] ), $this->canonical_post_exclusions( $post_type ) ) ) );
		return $args;
	}

	public function term_query( $args, $taxonomy ) {
		$args['meta_query'] = $this->query_constraints( $args['meta_query'] ?? [] );
		$args['exclude']    = array_values( array_unique( array_merge( (array) ( $args['exclude'] ?? [] ), $this->canonical_term_exclusions( $taxonomy ) ) ) );
		return $args;
	}

	private function query_constraints( $existing ) {
		$constraints = [
			'relation' => 'AND',
			[
				'relation' => 'OR',
				[ 'key' => Meta::key( 'robots_index' ), 'compare' => 'NOT EXISTS' ],
				[ 'key' => Meta::key( 'robots_index' ), 'value' => 'noindex', 'compare' => '!=' ],
			],
		];
		$existing = (array) $existing;
		return $existing ? [ 'relation' => 'AND', $existing, $constraints ] : $constraints;
	}

	private function canonical_post_exclusions( $post_type ) {
		$ids = get_posts( [
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_key'               => Meta::key( 'canonical' ),
		] );
		return array_values( array_filter( $ids, static function ( $post_id ) {
			$canonical = untrailingslashit( strtolower( (string) get_post_meta( $post_id, Meta::key( 'canonical' ), true ) ) );
			$permalink = untrailingslashit( strtolower( (string) get_permalink( $post_id ) ) );
			return '' !== $canonical && $canonical !== $permalink;
		} ) );
	}

	private function canonical_term_exclusions( $taxonomy ) {
		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_key'   => Meta::key( 'canonical' ),
		] );
		if ( is_wp_error( $terms ) ) {
			return [];
		}
		return array_values( array_filter( $terms, static function ( $term_id ) {
			$canonical = untrailingslashit( strtolower( (string) get_term_meta( $term_id, Meta::key( 'canonical' ), true ) ) );
			$term_url  = get_term_link( $term_id );
			$term_url  = is_wp_error( $term_url ) ? '' : untrailingslashit( strtolower( $term_url ) );
			return '' !== $canonical && $canonical !== $term_url;
		} ) );
	}
}
