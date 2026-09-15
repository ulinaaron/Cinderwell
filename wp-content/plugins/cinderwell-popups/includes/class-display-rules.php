<?php
/**
 * Popup page-matching rules.
 *
 * @package Cinderwell_Popups
 */

namespace Cinderwell_Popups;

defined( 'ABSPATH' ) || exit;

class Display_Rules {

	public static function should_show( $popup_id, $context = [] ) {
		if ( 'publish' !== get_post_status( $popup_id ) || 'auto' !== Popup_Meta::get_value( $popup_id, 'trigger_type' ) ) {
			return false;
		}

		$current_url     = isset( $context['url'] ) ? (string) $context['url'] : ( $_SERVER['REQUEST_URI'] ?? '/' );
		$current_post_id = isset( $context['post_id'] ) ? absint( $context['post_id'] ) : get_queried_object_id();

		foreach ( (array) Popup_Meta::get_value( $popup_id, 'exclude_urls' ) as $pattern ) {
			if ( self::url_matches( $current_url, $pattern ) ) {
				return false;
			}
		}

		if ( $current_post_id && in_array( $current_post_id, array_map( 'absint', (array) Popup_Meta::get_value( $popup_id, 'exclude_posts' ) ), true ) ) {
			return false;
		}

		$location = Popup_Meta::get_value( $popup_id, 'display_location' );
		if ( 'all' === $location ) {
			return true;
		}

		if ( 'specific_urls' === $location ) {
			foreach ( (array) Popup_Meta::get_value( $popup_id, 'display_urls' ) as $pattern ) {
				if ( self::url_matches( $current_url, $pattern ) ) {
					return true;
				}
			}
			return false;
		}

		if ( 'specific_post_types' === $location ) {
			$current_type = $current_post_id ? get_post_type( $current_post_id ) : '';
			return $current_type && in_array( $current_type, (array) Popup_Meta::get_value( $popup_id, 'display_post_types' ), true );
		}

		if ( 'specific_posts' === $location ) {
			return $current_post_id && in_array( $current_post_id, array_map( 'absint', (array) Popup_Meta::get_value( $popup_id, 'display_posts' ) ), true );
		}

		return false;
	}

	public static function get_summary( $popup_id ) {
		if ( 'manual' === Popup_Meta::get_value( $popup_id, 'trigger_type' ) ) {
			return __( 'Button or trigger block', 'cinderwell-popups' );
		}

		$location = Popup_Meta::get_value( $popup_id, 'display_location' );
		if ( 'all' === $location ) {
			return __( 'All pages', 'cinderwell-popups' );
		}

		$values = [
			'specific_urls'       => (array) Popup_Meta::get_value( $popup_id, 'display_urls' ),
			'specific_post_types' => (array) Popup_Meta::get_value( $popup_id, 'display_post_types' ),
			'specific_posts'      => (array) Popup_Meta::get_value( $popup_id, 'display_posts' ),
		];
		$count = count( $values[ $location ] ?? [] );
		$labels = [
			'specific_urls'       => _n( '%d URL pattern', '%d URL patterns', $count, 'cinderwell-popups' ),
			'specific_post_types' => _n( '%d post type', '%d post types', $count, 'cinderwell-popups' ),
			'specific_posts'      => _n( '%d page or post', '%d pages or posts', $count, 'cinderwell-popups' ),
		];

		return isset( $labels[ $location ] ) ? sprintf( $labels[ $location ], $count ) : __( 'No matching rule', 'cinderwell-popups' );
	}

	public static function url_matches( $url, $pattern ) {
		$url_path = wp_parse_url( $url, PHP_URL_PATH );
		$url_path = '/' . ltrim( rawurldecode( (string) $url_path ), '/' );
		$pattern  = trim( (string) $pattern );
		if ( preg_match( '#^https?://#i', $pattern ) ) {
			$pattern = (string) wp_parse_url( $pattern, PHP_URL_PATH );
		}
		$pattern = '/' . ltrim( rawurldecode( $pattern ), '/' );

		if ( '/' !== $url_path ) {
			$url_path = untrailingslashit( $url_path );
		}
		if ( '/' !== $pattern && '*' !== substr( $pattern, -1 ) ) {
			$pattern = untrailingslashit( $pattern );
		}

		$regex = '#^' . str_replace( '\\*', '.*', preg_quote( $pattern, '#' ) ) . '$#';
		return 1 === preg_match( $regex, $url_path );
	}
}
