<?php
/**
 * Evaluates visitor-facing visibility rules.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Condition_Evaluator {

    public static function should_show( $attributes ) {
		if ( ! empty( $attributes['conditions']['block'] ) ) {
			return self::matches_group( $attributes['conditions']['block'] );
		}
		$visibility = isset( $attributes['visibility'] ) ? $attributes['visibility'] : 'always';
		$config = isset( $attributes['visibilityConfig'] ) && is_array( $attributes['visibilityConfig'] ) ? $attributes['visibilityConfig'] : [];

		return self::matches_rule( [ 'type' => $visibility, 'config' => $config ] );
	}

	public static function matches_group( $group ) {
		$rules = isset( $group['rules'] ) && is_array( $group['rules'] ) ? $group['rules'] : [];
		if ( empty( $rules ) ) {
			return true;
		}
		$matches = array_map( [ __CLASS__, 'matches_rule' ], $rules );
		return isset( $group['relation'] ) && 'or' === $group['relation'] ? in_array( true, $matches, true ) : ! in_array( false, $matches, true );
	}

	public static function matches_rule( $rule ) {
		$visibility = isset( $rule['type'] ) ? $rule['type'] : 'always';
		$config = isset( $rule['config'] ) && is_array( $rule['config'] ) ? $rule['config'] : [];
		switch ( $visibility ) {
			case 'homepage_only':
				return is_front_page() || is_home();
			case 'single_post':
				return is_singular( 'post' );
			case 'archive':
				return is_archive();
			case 'search_results':
				return is_search();
			case 'dynamic_data':
				$binding = isset( $config['dynamicData'] ) && is_array( $config['dynamicData'] ) ? $config['dynamicData'] : [];
				if ( empty( $binding['source'] ) ) {
					return true;
				}
				$actual = Renderer::resolve_data_source( $binding['fallback'] ?? '', $binding['source'], [ 'post_id' => get_the_ID(), 'field' => $binding['field'] ?? '' ] );
				return self::compare( $actual, $config['operator'] ?? 'is', $config['value'] ?? '' );
			case 'specific_pages':
				$pages = isset( $config['pages'] ) && is_array( $config['pages'] ) ? array_map( 'absint', $config['pages'] ) : [];
				return empty( $pages ) || in_array( get_queried_object_id(), $pages, true );
			case 'date_range':
				$now = current_time( 'Y-m-d' );
				return ( empty( $config['startDate'] ) || $now >= $config['startDate'] ) && ( empty( $config['endDate'] ) || $now <= $config['endDate'] );
			case 'user_logged_in':
				return is_user_logged_in();
			case 'user_role':
				$roles = isset( $config['userRoles'] ) && is_array( $config['userRoles'] ) ? $config['userRoles'] : [];
				if ( empty( $roles ) ) {
					return true;
				}
				$user = wp_get_current_user();
				return $user->exists() && (bool) array_intersect( $roles, (array) $user->roles );
			case 'always':
				return true;
			case 'post_type':
				$types = isset( $config['postTypes'] ) && is_array( $config['postTypes'] ) ? $config['postTypes'] : [];
				return empty( $types ) || in_array( get_post_type( get_queried_object_id() ), $types, true );
			default:
				$key = ! empty( $config['key'] ) ? sanitize_key( $config['key'] ) : $visibility;
				return (bool) apply_filters( 'cinderwell_condition_evaluate', true, $key, $config );
		}
	}

	private static function compare( $actual, $operator, $expected ) {
		$actual = is_scalar( $actual ) ? (string) $actual : wp_json_encode( $actual );
		$expected = (string) $expected;
		switch ( $operator ) {
			case 'is_not': return $actual !== $expected;
			case 'contains': return false !== strpos( $actual, $expected );
			case 'not_contains': return false === strpos( $actual, $expected );
			case 'is_empty': return '' === trim( $actual );
			case 'is_not_empty': return '' !== trim( $actual );
			case 'is':
			default: return $actual === $expected;
		}
	}
}
