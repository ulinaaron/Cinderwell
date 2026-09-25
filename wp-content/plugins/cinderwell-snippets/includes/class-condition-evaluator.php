<?php
/**
 * Context-aware snippet conditions.
 *
 * @package Cinderwell_Snippets
 */

namespace Cinderwell_Snippets;

defined( 'ABSPATH' ) || exit;

class Condition_Evaluator {
	public static function defaults() {
		return [
			'relation'      => 'and',
			'contexts'      => [],
			'post_types'    => [],
			'content_ids'   => [],
			'include_paths' => [],
			'exclude_paths' => [],
			'user_state'    => 'any',
			'roles'         => [],
			'start'         => 0,
			'end'           => 0,
		];
	}

	public static function sanitize( $conditions, $location = '' ) {
		$conditions = wp_parse_args( is_array( $conditions ) ? $conditions : [], self::defaults() );
		$relation   = 'or' === sanitize_key( $conditions['relation'] ) ? 'or' : 'and';
		$contexts   = array_values( array_intersect( array_map( 'sanitize_key', (array) $conditions['contexts'] ), [ 'home', 'singular', 'archive', 'search', '404' ] ) );
		$post_types = array_values( array_intersect( array_map( 'sanitize_key', (array) $conditions['post_types'] ), get_post_types( [ 'public' => true ] ) ) );
		$content_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $conditions['content_ids'] ) ) ) );
		$user_state = sanitize_key( $conditions['user_state'] );
		if ( ! in_array( $user_state, [ 'any', 'logged_in', 'logged_out' ], true ) ) {
			$user_state = 'any';
		}
		$roles = array_values( array_intersect( array_map( 'sanitize_key', (array) $conditions['roles'] ), array_keys( wp_roles()->roles ) ) );

		if ( ! self::supports_query_conditions( $location ) ) {
			$contexts    = [];
			$post_types  = [];
			$content_ids = [];
		}

		return [
			'relation'      => $relation,
			'contexts'      => $contexts,
			'post_types'    => $post_types,
			'content_ids'   => $content_ids,
			'include_paths' => self::sanitize_paths( $conditions['include_paths'] ),
			'exclude_paths' => self::sanitize_paths( $conditions['exclude_paths'] ),
			'user_state'    => $user_state,
			'roles'         => $roles,
			'start'         => self::sanitize_datetime( $conditions['start'] ),
			'end'           => self::sanitize_datetime( $conditions['end'] ),
		];
	}

	public static function matches( array $snippet ) {
		$conditions = self::sanitize( $snippet['conditions'] ?? [], $snippet['location'] ?? '' );
		$path       = self::current_path();

		foreach ( $conditions['exclude_paths'] as $pattern ) {
			if ( self::path_matches( $path, $pattern ) ) {
				return false;
			}
		}

		$checks = [];
		if ( $conditions['include_paths'] ) {
			$checks[] = (bool) array_filter( $conditions['include_paths'], static function ( $pattern ) use ( $path ) {
				return self::path_matches( $path, $pattern );
			} );
		}
		if ( $conditions['contexts'] ) {
			$checks[] = self::matches_contexts( $conditions['contexts'] );
		}
		if ( $conditions['post_types'] ) {
			$checks[] = in_array( get_post_type( get_queried_object_id() ), $conditions['post_types'], true );
		}
		if ( $conditions['content_ids'] ) {
			$checks[] = in_array( get_queried_object_id(), $conditions['content_ids'], true );
		}
		if ( 'logged_in' === $conditions['user_state'] ) {
			$checks[] = is_user_logged_in();
		} elseif ( 'logged_out' === $conditions['user_state'] ) {
			$checks[] = ! is_user_logged_in();
		}
		if ( $conditions['roles'] ) {
			$user     = wp_get_current_user();
			$checks[] = $user->exists() && (bool) array_intersect( $conditions['roles'], (array) $user->roles );
		}
		$now = current_time( 'timestamp' );
		if ( $conditions['start'] || $conditions['end'] ) {
			$checks[] = ( ! $conditions['start'] || $now >= $conditions['start'] ) && ( ! $conditions['end'] || $now <= $conditions['end'] );
		}

		$matches = ! $checks || ( 'or' === $conditions['relation'] ? in_array( true, $checks, true ) : ! in_array( false, $checks, true ) );
		return (bool) apply_filters( 'cinderwell_snippet_should_run', $matches, $snippet, $conditions );
	}

	public static function supports_query_conditions( $location ) {
		return in_array( $location, [ 'frontend', 'shortcode', 'frontend_head', 'frontend_footer', 'body_open', 'before_content', 'after_content' ], true );
	}

	private static function matches_contexts( array $contexts ) {
		foreach ( $contexts as $context ) {
			if ( 'home' === $context && ( is_front_page() || is_home() ) ) return true;
			if ( 'singular' === $context && is_singular() ) return true;
			if ( 'archive' === $context && is_archive() ) return true;
			if ( 'search' === $context && is_search() ) return true;
			if ( '404' === $context && is_404() ) return true;
		}
		return false;
	}

	private static function current_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		return '/' . ltrim( $path, '/' );
	}

	private static function path_matches( $path, $pattern ) {
		$regex = '#^' . str_replace( '\\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';
		return (bool) preg_match( $regex, $path );
	}

	private static function sanitize_paths( $paths ) {
		if ( is_string( $paths ) ) {
			$paths = preg_split( '/\r\n|\r|\n/', $paths );
		}
		$clean = [];
		foreach ( (array) $paths as $path ) {
			$path = trim( strtok( sanitize_text_field( (string) $path ), '?#' ) ?: '' );
			if ( '' === $path ) continue;
			$clean[] = '/' . ltrim( $path, '/' );
		}
		return array_values( array_unique( $clean ) );
	}

	private static function sanitize_datetime( $value ) {
		if ( is_numeric( $value ) ) {
			return max( 0, (int) $value );
		}
		$value = sanitize_text_field( (string) $value );
		if ( ! $value ) return 0;
		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, wp_timezone() );
		return $date ? $date->getTimestamp() : 0;
	}
}

