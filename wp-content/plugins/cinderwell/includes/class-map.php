<?php
/**
 * Public extension contract for Cinderwell maps.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Map {

	/**
	 * Return registered map data sources.
	 *
	 * A source with an editor endpoint can provide the same records in the block
	 * editor that `cinderwell_map_locations` provides on the frontend.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_sources() {
		$locations_enabled = class_exists( Addons::class ) && Addons::is_enabled( 'locations' );
		$core_sources = [
			'manual' => [
				'label'   => __( 'Manual', 'cinderwell' ),
				'enabled' => true,
			],
			'locations' => [
				'label'           => __( 'Locations', 'cinderwell' ),
				'enabled'         => $locations_enabled,
				'editor_endpoint' => $locations_enabled ? '/cinderwell/v1/locations/map' : '',
			],
		];
		$sources = (array) apply_filters( 'cinderwell_map_sources', $core_sources );
		if ( empty( $sources['manual'] ) || ! is_array( $sources['manual'] ) ) {
			$sources['manual'] = $core_sources['manual'];
		}

		$normalized = [];
		foreach ( (array) $sources as $key => $source ) {
			if ( ! is_array( $source ) ) {
				continue;
			}
			$slug = sanitize_key( $source['slug'] ?? $key );
			if ( ! $slug ) {
				continue;
			}
			$default_endpoint = 'manual' !== $slug && ( ! isset( $source['enabled'] ) || $source['enabled'] )
				? '/cinderwell/v1/map/records?source=' . rawurlencode( $slug )
				: '';
			$editor_endpoint = esc_url_raw( $source['editor_endpoint'] ?? $default_endpoint );
			if ( $editor_endpoint && 0 !== strpos( $editor_endpoint, '/' ) ) {
				$editor_endpoint = '';
			}
			$normalized[ $slug ] = [
				'slug'           => $slug,
				'label'          => sanitize_text_field( $source['label'] ?? ucwords( str_replace( '-', ' ', $slug ) ) ),
				'enabled'        => ! isset( $source['enabled'] ) || (bool) $source['enabled'],
				'editorEndpoint' => $editor_endpoint,
			];
		}

		return $normalized;
	}

	/**
	 * Return registered map presentations.
	 *
	 * Custom presentations inherit the behavior and semantic markup of one of
	 * the three supported bases, then use their own scoped class for theme CSS.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function get_presentations() {
		$core_presentations = [
			'map' => [
				'label' => __( 'Map', 'cinderwell' ),
				'base'  => 'map',
			],
			'directory' => [
				'label' => __( 'Map + list', 'cinderwell' ),
				'base'  => 'directory',
			],
			'locator' => [
				'label' => __( 'Locator', 'cinderwell' ),
				'base'  => 'locator',
			],
		];
		$presentations = (array) apply_filters( 'cinderwell_map_presentations', $core_presentations );
		foreach ( $core_presentations as $slug => $definition ) {
			if ( empty( $presentations[ $slug ] ) || ! is_array( $presentations[ $slug ] ) ) {
				$presentations[ $slug ] = $definition;
			}
		}

		$normalized = [];
		foreach ( (array) $presentations as $key => $presentation ) {
			if ( ! is_array( $presentation ) ) {
				continue;
			}
			$slug = sanitize_key( $presentation['slug'] ?? $key );
			$base = sanitize_key( $presentation['base'] ?? 'map' );
			if ( ! $slug || ! in_array( $base, [ 'map', 'directory', 'locator' ], true ) ) {
				continue;
			}
			$normalized[ $slug ] = [
				'slug'  => $slug,
				'label' => sanitize_text_field( $presentation['label'] ?? ucwords( str_replace( '-', ' ', $slug ) ) ),
				'base'  => $base,
			];
		}

		return $normalized;
	}

	public static function get_editor_settings() {
		return [
			'sources'       => array_values( self::get_sources() ),
			'presentations' => array_values( self::get_presentations() ),
		];
	}

	/**
	 * Resolve and normalize records through the public map pipeline.
	 *
	 * @param array $records    Initial records, normally saved manual locations.
	 * @param array $attributes Map block attributes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function resolve_records( $records, $attributes ) {
		$records = (array) apply_filters( 'cinderwell_map_locations', (array) $records, (array) $attributes );
		$prepared = [];

		foreach ( array_slice( $records, 0, 100 ) as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			/**
			 * Filters one map record before the public schema is applied.
			 *
			 * Add custom keys to `cinderwell_map_record_schema` before returning
			 * them here. This keeps REST previews and frontend JSON sanitized.
			 */
			$record = apply_filters( 'cinderwell_map_record', $record, (array) $attributes );
			if ( ! is_array( $record ) ) {
				continue;
			}

			$normalized = self::normalize_record( $record, $attributes );
			if ( ( empty( $normalized['name'] ) && empty( $normalized['address'] ) ) || null === ( $normalized['latitude'] ?? null ) || null === ( $normalized['longitude'] ?? null ) ) {
				continue;
			}
			$prepared[] = $normalized;
		}

		return array_values( $prepared );
	}

	/**
	 * Map record schema. Extensions may add fields with a callable sanitizer.
	 *
	 * @param array $attributes Map block attributes.
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_record_schema( $attributes = [] ) {
		$schema = [
			'id'             => [ 'sanitize_callback' => 'sanitize_key', 'default' => '' ],
			'postId'         => [ 'sanitize_callback' => 'absint', 'default' => 0 ],
			'name'           => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'address'        => [ 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '' ],
			'latitude'       => [ 'sanitize_callback' => [ self::class, 'sanitize_latitude' ], 'default' => null ],
			'longitude'      => [ 'sanitize_callback' => [ self::class, 'sanitize_longitude' ], 'default' => null ],
			'directionsUrl'  => [ 'sanitize_callback' => 'esc_url_raw', 'default' => '' ],
			'phone'          => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'url'            => [ 'sanitize_callback' => 'esc_url_raw', 'default' => '' ],
			'termIds'        => [ 'sanitize_callback' => [ self::class, 'sanitize_term_ids' ], 'default' => [] ],
			'terms'          => [ 'sanitize_callback' => [ self::class, 'sanitize_terms' ], 'default' => [] ],
			'meta'           => [ 'sanitize_callback' => [ self::class, 'sanitize_meta' ], 'default' => [] ],
			'popupHtml'      => [ 'sanitize_callback' => 'wp_kses_post', 'default' => '' ],
		];

		$filtered = (array) apply_filters( 'cinderwell_map_record_schema', $schema, (array) $attributes );
		return array_replace( $schema, $filtered );
	}

	private static function normalize_record( $record, $attributes ) {
		$normalized = [];
		foreach ( self::get_record_schema( $attributes ) as $key => $definition ) {
			if ( ! is_array( $definition ) || ! is_callable( $definition['sanitize_callback'] ?? null ) ) {
				continue;
			}
			$value = array_key_exists( $key, $record ) ? $record[ $key ] : ( $definition['default'] ?? '' );
			$normalized[ $key ] = call_user_func( $definition['sanitize_callback'], $value );
		}

		if ( empty( $normalized['directionsUrl'] ) && ! empty( $normalized['address'] ) ) {
			$normalized['directionsUrl'] = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $normalized['address'] );
		}

		return $normalized;
	}

	public static function sanitize_latitude( $value ) {
		return self::sanitize_coordinate( $value, -90, 90 );
	}

	public static function sanitize_longitude( $value ) {
		return self::sanitize_coordinate( $value, -180, 180 );
	}

	private static function sanitize_coordinate( $value, $minimum, $maximum ) {
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		$value = (float) $value;
		return $value >= $minimum && $value <= $maximum ? $value : null;
	}

	public static function sanitize_term_ids( $value ) {
		return array_values( array_filter( array_map( 'absint', (array) $value ) ) );
	}

	public static function sanitize_terms( $value ) {
		$terms = [];
		foreach ( (array) $value as $term ) {
			if ( ! is_array( $term ) || empty( $term['id'] ) || empty( $term['name'] ) ) {
				continue;
			}
			$terms[] = [ 'id' => absint( $term['id'] ), 'name' => sanitize_text_field( $term['name'] ) ];
		}
		return $terms;
	}

	public static function sanitize_meta( $value ) {
		$meta = [];
		foreach ( (array) $value as $key => $item ) {
			$key = sanitize_key( $key );
			if ( ! $key || is_array( $item ) || is_object( $item ) ) {
				continue;
			}
			$meta[ $key ] = sanitize_text_field( (string) $item );
		}
		return $meta;
	}
}
