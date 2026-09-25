<?php
/**
 * Editor-only address geocoding for the Map block.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Map_Service {

	const CACHE_TTL = 30 * DAY_IN_SECONDS;

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_rest_routes() {
		register_rest_route(
			'cinderwell/v1',
			'/map/records',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_records' ],
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'source' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					'term' => [
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			'cinderwell/v1',
			'/map/geocode',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'geocode' ],
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'address' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $value ) {
							$length = function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
							return $length >= 3 && $length <= 300;
						},
					],
				],
			]
		);
	}

	public function get_records( \WP_REST_Request $request ) {
		$source  = sanitize_key( $request->get_param( 'source' ) );
		$sources = Map::get_sources();
		if ( 'manual' === $source || empty( $sources[ $source ]['enabled'] ) ) {
			return new \WP_Error( 'cinderwell_map_source_unavailable', __( 'This map source is unavailable.', 'cinderwell' ), [ 'status' => 404 ] );
		}

		return rest_ensure_response( Map::resolve_records( [], [
			'source'       => $source,
			'locationTerm' => absint( $request->get_param( 'term' ) ),
		] ) );
	}

	public function geocode( \WP_REST_Request $request ) {
		$address   = trim( (string) $request->get_param( 'address' ) );
		$cache_key = 'cinderwell_map_' . md5( strtolower( $address ) );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return rest_ensure_response( $cached );
		}

		$rate_key = 'cinderwell_map_rate_' . get_current_user_id();
		if ( get_transient( $rate_key ) ) {
			return new \WP_Error( 'cinderwell_map_rate_limited', __( 'Please wait a moment before searching for another address.', 'cinderwell' ), [ 'status' => 429 ] );
		}
		set_transient( $rate_key, 1, 2 );

		$endpoint = (string) apply_filters( 'cinderwell_map_geocoder_url', 'https://nominatim.openstreetmap.org/search' );
		$url      = add_query_arg(
			[
				'q'              => $address,
				'format'         => 'jsonv2',
				'limit'          => 1,
				'addressdetails' => 0,
			],
			$endpoint
		);
		$response = wp_remote_get(
			$url,
			[
				'timeout'    => 10,
				'user-agent' => sprintf( 'Cinderwell/%s (+%s)', CINDERWELL_VERSION, home_url( '/' ) ),
				'headers'    => [ 'Accept-Language' => get_user_locale() ],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'cinderwell_map_geocoder_unavailable', __( 'The address service could not be reached. Try again or enter coordinates manually.', 'cinderwell' ), [ 'status' => 502 ] );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( 'cinderwell_map_geocoder_error', __( 'The address service did not return a usable response.', 'cinderwell' ), [ 'status' => 502 ] );
		}

		$results = json_decode( wp_remote_retrieve_body( $response ), true );
		$result  = is_array( $results ) && isset( $results[0] ) && is_array( $results[0] ) ? $results[0] : [];
		$lat     = isset( $result['lat'] ) ? (float) $result['lat'] : null;
		$lng     = isset( $result['lon'] ) ? (float) $result['lon'] : null;

		if ( null === $lat || null === $lng || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
			return new \WP_Error( 'cinderwell_map_address_not_found', __( 'No matching location was found. Add more address detail or enter coordinates manually.', 'cinderwell' ), [ 'status' => 404 ] );
		}

		$data = [
			'latitude'     => $lat,
			'longitude'    => $lng,
			'display_name' => sanitize_text_field( $result['display_name'] ?? $address ),
			'attribution'  => '© OpenStreetMap contributors',
		];
		set_transient( $cache_key, $data, self::CACHE_TTL );

		return rest_ensure_response( $data );
	}
}
