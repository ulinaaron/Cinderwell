<?php
/**
 * Visibility-condition registry.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Conditions {

	public static function get_conditions() {
		return apply_filters( 'cinderwell_conditions', [
			'always' => [ 'label' => __( 'Always show', 'cinderwell' ), 'safe' => true ],
			'homepage_only' => [ 'label' => __( 'Homepage only', 'cinderwell' ), 'safe' => true ],
			'single_post' => [ 'label' => __( 'Single posts only', 'cinderwell' ), 'safe' => true ],
			'archive' => [ 'label' => __( 'Archive pages only', 'cinderwell' ), 'safe' => true ],
			'search_results' => [ 'label' => __( 'Search results only', 'cinderwell' ), 'safe' => true ],
			'dynamic_data' => [ 'label' => __( 'Dynamic data', 'cinderwell' ), 'safe' => true, 'requires' => 'dynamic_comparison' ],
			'specific_pages' => [ 'label' => __( 'Specific pages', 'cinderwell' ), 'safe' => true, 'requires' => 'page_selector' ],
			'post_type' => [ 'label' => __( 'Specific post type', 'cinderwell' ), 'safe' => true, 'requires' => 'post_type' ],
			'date_range' => [ 'label' => __( 'Date range', 'cinderwell' ), 'safe' => true, 'requires' => 'date_range' ],
			'user_logged_in' => [ 'label' => __( 'Logged-in users only', 'cinderwell' ), 'safe' => false ],
			'user_role' => [ 'label' => __( 'Specific user role', 'cinderwell' ), 'safe' => false, 'requires' => 'role_selector' ],
			'custom' => [ 'label' => __( 'Custom condition (theme/plugin)', 'cinderwell' ), 'safe' => false ],
		] );
	}

	public static function get_client_visible() {
		$conditions = self::get_conditions();
		if ( current_user_can( 'manage_options' ) ) {
			return $conditions;
		}
		return array_filter( $conditions, static function ( $condition, $key ) {
			return 'always' === $key || ! empty( $condition['safe'] );
		}, ARRAY_FILTER_USE_BOTH );
	}
}
