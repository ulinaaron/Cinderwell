<?php
/**
 * Dynamic text-source registry.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Data_Sources {

	public static function get_sources() {
		return apply_filters( 'cinderwell_data_sources', [
			'static' => [ 'label' => __( 'Static text', 'cinderwell' ), 'group' => 'basic', 'context' => null ],
			'post_title' => [ 'label' => __( 'Post title', 'cinderwell' ), 'group' => 'post', 'context' => 'post' ],
			'post_permalink' => [ 'label' => __( 'Post permalink', 'cinderwell' ), 'group' => 'post', 'context' => 'post' ],
			'post_excerpt' => [ 'label' => __( 'Post excerpt', 'cinderwell' ), 'group' => 'post', 'context' => 'post' ],
			'post_date' => [ 'label' => __( 'Post date', 'cinderwell' ), 'group' => 'post', 'context' => 'post' ],
			'post_author' => [ 'label' => __( 'Post author', 'cinderwell' ), 'group' => 'post', 'context' => 'post' ],
			'site_title' => [ 'label' => __( 'Site title', 'cinderwell' ), 'group' => 'site', 'context' => 'site' ],
			'site_tagline' => [ 'label' => __( 'Site tagline', 'cinderwell' ), 'group' => 'site', 'context' => 'site' ],
			'current_year' => [ 'label' => __( 'Current year', 'cinderwell' ), 'group' => 'site', 'context' => 'site' ],
			'current_user_name' => [ 'label' => __( 'Current user name', 'cinderwell' ), 'group' => 'user', 'context' => 'user' ],
			'acf_field' => [ 'label' => __( 'Advanced Custom Fields field…', 'cinderwell' ), 'group' => 'advanced_custom_fields', 'context' => 'post', 'requires_field_name' => true ],
		] );
	}

	public static function get_groups() {
		$groups = [];
		foreach ( self::get_sources() as $key => $source ) {
			$group = isset( $source['group'] ) ? $source['group'] : 'other';
			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = [];
			}
			$groups[ $group ][] = [
				'key'                 => $key,
				'label'               => $source['label'],
				'requiresFieldName'   => ! empty( $source['requires_field_name'] ),
			];
		}
		return $groups;
	}

	/**
	 * Provide ACF fields as editor suggestions without making ACF a dependency.
	 */
	public static function get_acf_fields( $post_id = 0 ) {
		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return [];
		}
		$fields = [];
		$groups = $post_id ? acf_get_field_groups( [ 'post_id' => absint( $post_id ) ] ) : acf_get_field_groups();
		foreach ( $groups as $group ) {
			foreach ( (array) acf_get_fields( $group ) as $field ) {
				if ( empty( $field['name'] ) || in_array( $field['type'] ?? '', [ 'tab', 'message', 'accordion' ], true ) ) {
					continue;
				}
				$fields[] = [
					'name'  => $field['name'],
					'label' => $field['label'] ?? $field['name'],
					'group' => $group['title'] ?? __( 'ACF fields', 'cinderwell' ),
				];
			}
		}
		return $fields;
	}

	/**
	 * Return current-post ACF values for the editor canvas preview.
	 */
	public static function get_acf_preview_values( $post_id ) {
		if ( ! $post_id || ! function_exists( 'get_field' ) ) {
			return [];
		}
		$values = [];
		foreach ( self::get_acf_fields( $post_id ) as $field ) {
			$values[ $field['name'] ] = Renderer::normalize_value( get_field( $field['name'], $post_id ) );
		}
		return $values;
	}
}
