<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Versioned site configuration and supported-object discovery. */
class Settings {
	const OPTION = 'cinderwell_seo_settings';

	public static function defaults() {
		return [
			'schema_version'          => 1,
			'post_types'              => self::default_post_types(),
			'taxonomies'              => self::default_taxonomies(),
			'home_title'              => '',
			'home_description'        => '',
			'default_social_image_id' => 0,
			'google_verification'     => '',
			'bing_verification'       => '',
			'archives'                => [],
			'remove_data'             => false,
		];
	}

	public static function get() {
		$value = get_option( self::OPTION, [] );
		return wp_parse_args( is_array( $value ) ? $value : [], self::defaults() );
	}

	public static function supported_post_types() {
		$types = [];
		foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $name => $object ) {
			if ( 'attachment' !== $name && ! empty( $object->show_ui ) && is_post_type_viewable( $object ) && empty( $object->exclude_from_search ) ) {
				$types[ $name ] = $object;
			}
		}
		return (array) apply_filters( 'cinderwell_seo_supported_post_types', $types );
	}

	public static function supported_taxonomies() {
		$taxonomies = [];
		foreach ( get_taxonomies( [ 'public' => true ], 'objects' ) as $name => $object ) {
			if ( 'post_format' !== $name && ! empty( $object->show_ui ) && is_taxonomy_viewable( $object ) ) {
				$taxonomies[ $name ] = $object;
			}
		}
		return (array) apply_filters( 'cinderwell_seo_supported_taxonomies', $taxonomies );
	}

	public static function enabled_post_types() {
		$settings = self::get();
		return array_values( array_intersect( array_keys( self::supported_post_types() ), (array) $settings['post_types'] ) );
	}

	public static function enabled_taxonomies() {
		$settings = self::get();
		return array_values( array_intersect( array_keys( self::supported_taxonomies() ), (array) $settings['taxonomies'] ) );
	}

	private static function default_post_types() {
		return array_keys( self::supported_post_types() );
	}

	private static function default_taxonomies() {
		return array_keys( self::supported_taxonomies() );
	}

	public static function sanitize( $submitted ) {
		$submitted = is_array( $submitted ) ? $submitted : [];
		$archives  = [];
		foreach ( self::supported_post_types() as $name => $object ) {
			if ( empty( $object->has_archive ) ) {
				continue;
			}
			$row = isset( $submitted['archives'][ $name ] ) && is_array( $submitted['archives'][ $name ] ) ? $submitted['archives'][ $name ] : [];
			$archives[ $name ] = [
				'title'       => sanitize_text_field( $row['title'] ?? '' ),
				'description' => sanitize_textarea_field( $row['description'] ?? '' ),
			];
		}

		return [
			'schema_version'          => 1,
			'post_types'              => array_values( array_intersect( array_keys( self::supported_post_types() ), array_map( 'sanitize_key', (array) ( $submitted['post_types'] ?? [] ) ) ) ),
			'taxonomies'              => array_values( array_intersect( array_keys( self::supported_taxonomies() ), array_map( 'sanitize_key', (array) ( $submitted['taxonomies'] ?? [] ) ) ) ),
			'home_title'              => sanitize_text_field( $submitted['home_title'] ?? '' ),
			'home_description'        => sanitize_textarea_field( $submitted['home_description'] ?? '' ),
			'default_social_image_id' => absint( $submitted['default_social_image_id'] ?? 0 ),
			'google_verification'     => self::verification_token( $submitted['google_verification'] ?? '', 'google-site-verification' ),
			'bing_verification'       => self::verification_token( $submitted['bing_verification'] ?? '', 'msvalidate.01' ),
			'archives'                => $archives,
			'remove_data'             => ! empty( $submitted['remove_data'] ),
		];
	}

	private static function verification_token( $value, $meta_name ) {
		$value = trim( wp_strip_all_tags( (string) $value ) );
		if ( preg_match( '/content=["\']([^"\']+)["\']/', $value, $match ) ) {
			$value = $match[1];
		}
		$value = str_replace( [ $meta_name, '<', '>', '"', "'" ], '', $value );
		return sanitize_text_field( trim( $value ) );
	}
}
