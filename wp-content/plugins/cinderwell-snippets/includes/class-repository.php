<?php
/**
 * Snippet persistence and runtime manifest.
 *
 * @package Cinderwell_Snippets
 */

namespace Cinderwell_Snippets;

defined( 'ABSPATH' ) || exit;

class Repository {
	const MANIFEST_OPTION  = 'cinderwell_snippets_manifest';
	const SAFE_MODE_OPTION = 'cinderwell_snippets_safe_mode';
	const META_TYPE        = '_cw_snippet_type';
	const META_LOCATION    = '_cw_snippet_location';
	const META_PRIORITY    = '_cw_snippet_priority';
	const META_CONDITIONS  = '_cw_snippet_conditions';
	const META_LAST_ERROR  = '_cw_snippet_last_error';
	const META_FAILED      = '_cw_snippet_auto_disabled';

	public static function get_types() {
		return [
			'php'  => __( 'PHP', 'cinderwell-snippets' ),
			'js'   => __( 'JavaScript', 'cinderwell-snippets' ),
			'css'  => __( 'CSS', 'cinderwell-snippets' ),
			'html' => __( 'HTML', 'cinderwell-snippets' ),
		];
	}

	public static function get_locations( $type = '' ) {
		$locations = [
			'php' => [
				'init'      => __( 'Every request (init)', 'cinderwell-snippets' ),
				'frontend'  => __( 'Frontend after query', 'cinderwell-snippets' ),
				'admin'     => __( 'WordPress admin', 'cinderwell-snippets' ),
				'login'     => __( 'Login screen', 'cinderwell-snippets' ),
				'shortcode' => __( 'Shortcode only', 'cinderwell-snippets' ),
			],
			'js' => [
				'frontend_head'   => __( 'Frontend head', 'cinderwell-snippets' ),
				'frontend_footer' => __( 'Frontend footer', 'cinderwell-snippets' ),
				'admin_head'      => __( 'Admin head', 'cinderwell-snippets' ),
				'admin_footer'    => __( 'Admin footer', 'cinderwell-snippets' ),
				'login_head'      => __( 'Login head', 'cinderwell-snippets' ),
				'login_footer'    => __( 'Login footer', 'cinderwell-snippets' ),
			],
			'css' => [
				'frontend' => __( 'Frontend', 'cinderwell-snippets' ),
				'admin'    => __( 'WordPress admin', 'cinderwell-snippets' ),
				'login'    => __( 'Login screen', 'cinderwell-snippets' ),
			],
			'html' => [
				'frontend_head'   => __( 'Frontend head', 'cinderwell-snippets' ),
				'body_open'       => __( 'After opening body', 'cinderwell-snippets' ),
				'before_content'  => __( 'Before singular content', 'cinderwell-snippets' ),
				'after_content'   => __( 'After singular content', 'cinderwell-snippets' ),
				'frontend_footer' => __( 'Frontend footer', 'cinderwell-snippets' ),
				'shortcode'       => __( 'Shortcode only', 'cinderwell-snippets' ),
			],
		];

		$locations = (array) apply_filters( 'cinderwell_snippets_run_locations', $locations );
		return $type ? ( $locations[ $type ] ?? [] ) : $locations;
	}

	public static function get_default_location( $type ) {
		$defaults = [
			'php'  => 'frontend',
			'js'   => 'frontend_footer',
			'css'  => 'frontend',
			'html' => 'frontend_footer',
		];
		return $defaults[ $type ] ?? 'frontend';
	}

	public static function get( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || Post_Type::POST_TYPE !== $post->post_type ) {
			return null;
		}
		return self::normalize_post( $post );
	}

	public static function normalize_post( $post ) {
		$type      = get_post_meta( $post->ID, self::META_TYPE, true ) ?: 'php';
		$locations = self::get_locations( $type );
		$location  = get_post_meta( $post->ID, self::META_LOCATION, true );
		if ( ! isset( $locations[ $location ] ) ) {
			$location = self::get_default_location( $type );
		}
		return [
			'id'            => (int) $post->ID,
			'title'         => $post->post_title,
			'slug'          => $post->post_name,
			'code'          => $post->post_content,
			'status'        => $post->post_status,
			'type'          => $type,
			'location'      => $location,
			'priority'      => self::sanitize_priority( get_post_meta( $post->ID, self::META_PRIORITY, true ) ?: 10 ),
			'conditions'    => Condition_Evaluator::sanitize( get_post_meta( $post->ID, self::META_CONDITIONS, true ), $location ),
			'last_error'    => get_post_meta( $post->ID, self::META_LAST_ERROR, true ),
			'auto_disabled' => (bool) get_post_meta( $post->ID, self::META_FAILED, true ),
			'modified'      => $post->post_modified_gmt,
		];
	}

	public static function save( array $data, $post_id = 0 ) {
		$type = sanitize_key( $data['type'] ?? 'php' );
		if ( ! isset( self::get_types()[ $type ] ) ) {
			$type = 'php';
		}
		$location  = sanitize_key( $data['location'] ?? self::get_default_location( $type ) );
		$locations = self::get_locations( $type );
		if ( ! isset( $locations[ $location ] ) ) {
			$location = self::get_default_location( $type );
		}

		$title = sanitize_text_field( $data['title'] ?? '' );
		$code  = self::sanitize_code( $data['code'] ?? '' );
		$slug  = sanitize_title( $data['slug'] ?? $title );
		$slug  = self::unique_slug( $slug ?: 'snippet', $post_id );
		$status = ! empty( $data['enabled'] ) ? 'publish' : 'draft';

		if ( 'publish' === $status && 'php' === $type ) {
			$valid = self::validate_php( $code );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}

		$postarr = [
			'post_type'    => Post_Type::POST_TYPE,
			'post_title'   => $title ?: __( 'Untitled snippet', 'cinderwell-snippets' ),
			'post_name'    => $slug,
			'post_content' => wp_slash( $code ),
			'post_status'  => $status,
		];
		if ( $post_id ) {
			$postarr['ID'] = absint( $post_id );
		}

		$result = wp_insert_post( $postarr, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_post_meta( $result, self::META_TYPE, $type );
		update_post_meta( $result, self::META_LOCATION, $location );
		update_post_meta( $result, self::META_PRIORITY, self::sanitize_priority( $data['priority'] ?? 10 ) );
		update_post_meta( $result, self::META_CONDITIONS, Condition_Evaluator::sanitize( $data['conditions'] ?? [], $location ) );
		if ( ! empty( $data['clear_error'] ) || 'publish' === $status ) {
			delete_post_meta( $result, self::META_LAST_ERROR );
			delete_post_meta( $result, self::META_FAILED );
		}

		self::rebuild_manifest();
		return $result;
	}

	public static function sanitize_code( $code ) {
		$code = (string) $code;
		return str_replace( [ "\r\n", "\r" ], "\n", $code );
	}

	private static function unique_slug( $slug, $exclude_id = 0 ) {
		$base      = sanitize_title( $slug ) ?: 'snippet';
		$candidate = $base;
		$suffix    = 2;
		do {
			$posts = get_posts( [
				'post_type'      => Post_Type::POST_TYPE,
				'post_status'    => [ 'publish', 'draft', 'trash', 'pending', 'private' ],
				'name'           => $candidate,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			] );
			$conflict = $posts && absint( $posts[0] ) !== absint( $exclude_id );
			if ( $conflict ) {
				$candidate = $base . '-' . $suffix++;
			}
		} while ( $conflict );
		return $candidate;
	}

	public static function validate_php( $code ) {
		$code = self::prepare_php_for_execution( $code );
		if ( is_wp_error( $code ) ) return $code;
		try {
			token_get_all( "<?php\n" . (string) $code, TOKEN_PARSE );
		} catch ( \ParseError $error ) {
			return new \WP_Error( 'php_parse_error', $error->getMessage() );
		}
		return true;
	}

	/**
	 * Remove the optional editor-facing PHP tags before eval execution.
	 *
	 * @param string $code Stored snippet code.
	 * @return string|\WP_Error
	 */
	public static function prepare_php_for_execution( $code ) {
		$code = self::sanitize_code( $code );
		$code = preg_replace( '/^\xEF\xBB\xBF/', '', $code );
		$code = preg_replace( '/^\s*<\?php\b[ \t]*(?:\n)?/i', '', $code, 1 );
		$code = preg_replace( '/\?>\s*$/', '', $code, 1 );
		if ( preg_match( '/<\?(?:php|=)?|\?>/i', $code ) ) {
			return new \WP_Error( 'php_tags', __( 'PHP tags may only appear at the beginning and end of a snippet.', 'cinderwell-snippets' ) );
		}
		return $code;
	}

	/**
	 * Remove the optional editor-facing script element before output.
	 *
	 * @param string $code Stored snippet code.
	 * @return string
	 */
	public static function prepare_javascript_for_output( $code ) {
		$code = trim( self::sanitize_code( $code ) );
		if ( preg_match( '/^<script(?:\s[^>]*)?>\s*(.*?)\s*<\/script>$/is', $code, $matches ) ) {
			return $matches[1];
		}
		return $code;
	}

	public static function sanitize_priority( $priority ) {
		return min( 999, max( 1, (int) $priority ) );
	}

	public static function rebuild_manifest() {
		$posts = get_posts( [
			'post_type'              => Post_Type::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => [ 'menu_order' => 'ASC', 'ID' => 'ASC' ],
			'no_found_rows'          => true,
			'suppress_filters'       => true,
			'update_post_term_cache' => false,
		] );
		$manifest = [];
		foreach ( $posts as $post ) {
			$item = self::normalize_post( $post );
			unset( $item['last_error'], $item['auto_disabled'], $item['modified'], $item['status'] );
			$manifest[ $item['id'] ] = $item;
		}
		update_option( self::MANIFEST_OPTION, $manifest, false );
		return $manifest;
	}

	public static function get_manifest() {
		$manifest = get_option( self::MANIFEST_OPTION, [] );
		return is_array( $manifest ) ? $manifest : [];
	}

	public static function disable_with_error( $post_id, array $error ) {
		$post = get_post( $post_id );
		if ( ! $post || Post_Type::POST_TYPE !== $post->post_type ) {
			return;
		}
		wp_update_post( [ 'ID' => $post_id, 'post_status' => 'draft' ] );
		$error = [
			'type'      => sanitize_text_field( $error['type'] ?? __( 'Runtime error', 'cinderwell-snippets' ) ),
			'message'   => sanitize_textarea_field( $error['message'] ?? '' ),
			'file'      => sanitize_text_field( $error['file'] ?? '' ),
			'line'      => absint( $error['line'] ?? 0 ),
			'timestamp' => time(),
		];
		update_post_meta( $post_id, self::META_LAST_ERROR, $error );
		update_post_meta( $post_id, self::META_FAILED, 1 );
		self::rebuild_manifest();
		do_action( 'cinderwell_snippet_auto_disabled', $post_id, $error );
	}

	public static function safe_mode_enabled() {
		if ( defined( 'CINDERWELL_SNIPPETS_SAFE_MODE' ) && CINDERWELL_SNIPPETS_SAFE_MODE ) {
			return true;
		}
		if ( function_exists( 'wp_is_recovery_mode' ) && wp_is_recovery_mode() ) {
			return true;
		}
		return (bool) get_option( self::SAFE_MODE_OPTION, false );
	}

	public static function count_by_status( $status ) {
		$counts = wp_count_posts( Post_Type::POST_TYPE );
		return isset( $counts->{$status} ) ? absint( $counts->{$status} ) : 0;
	}

	public static function count_auto_disabled() {
		$query = new \WP_Query( [
			'post_type'      => Post_Type::POST_TYPE,
			'post_status'    => [ 'draft', 'publish' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'meta_key'       => self::META_FAILED,
			'meta_value'     => '1',
		] );
		return (int) $query->found_posts;
	}
}
