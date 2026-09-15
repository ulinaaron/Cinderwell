<?php
/**
 * REST endpoints for popup delivery, tracking, and agent management.
 *
 * @package Cinderwell_Popups
 */

namespace Cinderwell_Popups;

defined( 'ABSPATH' ) || exit;

class Rest_Api {

	const NAMESPACE = 'cinder-popups/v1';

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/active', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_active' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'url'     => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
				'post_id' => [ 'type' => 'integer', 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/track', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'track_event' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'popup_id' => [ 'required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint' ],
				'event'    => [ 'required' => true, 'type' => 'string', 'enum' => [ 'view', 'click', 'dismiss', 'convert' ], 'sanitize_callback' => 'sanitize_key' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/popups', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_popups' ],
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_popup' ],
				'permission_callback' => [ $this, 'can_save_popup' ],
			],
		] );

		register_rest_route( self::NAMESPACE, '/popups/(?P<id>\d+)', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_popup' ],
			'permission_callback' => static function ( $request ) {
				return current_user_can( 'edit_post', absint( $request['id'] ) );
			},
		] );
	}

	public function get_active( $request ) {
		$context = [
			'url'     => $request->get_param( 'url' ) ?: '/',
			'post_id' => absint( $request->get_param( 'post_id' ) ),
		];
		$posts = get_posts( [
			'post_type'      => 'cinderwell_popup',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );
		$active = [];
		foreach ( $posts as $post ) {
			if ( Display_Rules::should_show( $post->ID, $context ) ) {
				$active[] = $this->format_popup( $post, true );
			}
		}

		return rest_ensure_response( $active );
	}

	public function get_popups() {
		$args = [
			'post_type'      => 'cinderwell_popup',
			'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future' ],
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$args['author'] = get_current_user_id();
		}
		$posts = get_posts( $args );

		return rest_ensure_response( array_map( function ( $post ) {
			return $this->format_popup( $post, false );
		}, $posts ) );
	}

	public function get_popup( $request ) {
		$post = get_post( absint( $request['id'] ) );
		if ( ! $post || 'cinderwell_popup' !== $post->post_type ) {
			return new \WP_Error( 'popup_not_found', __( 'Popup not found.', 'cinderwell-popups' ), [ 'status' => 404 ] );
		}

		return rest_ensure_response( $this->format_popup( $post, false ) );
	}

	public function can_save_popup( $request ) {
		$id = absint( $request->get_param( 'id' ) );
		return $id ? current_user_can( 'edit_post', $id ) : current_user_can( 'edit_posts' );
	}

	public function save_popup( $request ) {
		$id        = absint( $request->get_param( 'id' ) );
		$post_data = [ 'post_type' => 'cinderwell_popup' ];
		if ( $id ) {
			if ( 'cinderwell_popup' !== get_post_type( $id ) ) {
				return new \WP_Error( 'popup_not_found', __( 'Popup not found.', 'cinderwell-popups' ), [ 'status' => 404 ] );
			}
			$post_data['ID'] = $id;
		} else {
			$post_data['post_title']   = __( 'Untitled popup', 'cinderwell-popups' );
			$post_data['post_content'] = '';
			$post_data['post_status']  = 'draft';
		}

		if ( $request->has_param( 'title' ) ) {
			$post_data['post_title'] = sanitize_text_field( (string) $request->get_param( 'title' ) );
		}
		if ( $request->has_param( 'content' ) ) {
			$content = (string) $request->get_param( 'content' );
			$post_data['post_content'] = current_user_can( 'unfiltered_html' ) ? $content : wp_kses_post( $content );
		}
		if ( $request->has_param( 'status' ) ) {
			$status  = sanitize_key( (string) $request->get_param( 'status' ) );
			$allowed = [ 'draft', 'pending', 'publish', 'private' ];
			$status  = in_array( $status, $allowed, true ) ? $status : 'draft';
			if ( in_array( $status, [ 'publish', 'private' ], true ) && ! current_user_can( 'publish_posts' ) ) {
				$status = 'pending';
			}
			$post_data['post_status'] = $status;
		}

		$saved_id = $id ? wp_update_post( $post_data, true ) : wp_insert_post( $post_data, true );
		if ( is_wp_error( $saved_id ) ) {
			return $saved_id;
		}

		$settings    = (array) $request->get_param( 'settings' );
		$definitions = Popup_Meta::definitions();
		foreach ( $settings as $name => $value ) {
			$name = sanitize_key( $name );
			if ( ! isset( $definitions[ $name ] ) ) {
				continue;
			}
			update_post_meta( $saved_id, Popup_Meta::meta_key( $name ), $value );
		}

		return rest_ensure_response( $this->format_popup( get_post( $saved_id ), false ) );
	}

	public function track_event( $request ) {
		$popup_id = absint( $request->get_param( 'popup_id' ) );
		$event    = sanitize_key( $request->get_param( 'event' ) );
		if ( 'cinderwell_popup' !== get_post_type( $popup_id ) || 'publish' !== get_post_status( $popup_id ) || ! in_array( $event, [ 'view', 'click', 'dismiss', 'convert' ], true ) ) {
			return new \WP_Error( 'invalid_event', __( 'A published popup and valid event are required.', 'cinderwell-popups' ), [ 'status' => 400 ] );
		}

		$fingerprint = sanitize_text_field( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . '|' . ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		$rate_key    = 'cw_popup_' . md5( $popup_id . '|' . $event . '|' . $fingerprint );
		if ( get_transient( $rate_key ) ) {
			return rest_ensure_response( [ 'success' => true, 'tracked' => false ] );
		}
		set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

		$key   = '_cinderwell_popup_event_' . $event;
		$count = (int) get_post_meta( $popup_id, $key, true );
		update_post_meta( $popup_id, $key, $count + 1 );

		return rest_ensure_response( [ 'success' => true, 'tracked' => true ] );
	}

	private function format_popup( $post, $render_content ) {
		$settings = [];
		foreach ( Popup_Meta::definitions() as $name => $definition ) {
			$settings[ $name ] = Popup_Meta::get_value( $post->ID, $name );
		}

		return [
			'id'       => $post->ID,
			'title'    => get_the_title( $post ),
			'content'  => $render_content ? apply_filters( 'the_content', $post->post_content ) : $post->post_content,
			'status'   => $post->post_status,
			'modified' => mysql_to_rfc3339( $post->post_modified_gmt ),
			'settings' => $settings,
		];
	}
}
