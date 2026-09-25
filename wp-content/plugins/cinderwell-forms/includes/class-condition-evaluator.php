<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Shared bounded conditional-rule evaluator. */
class Condition_Evaluator {
	public static function matches( array $conditions, $relation, array $values ) {
		if ( ! $conditions ) return true;
		$results = [];
		foreach ( $conditions as $condition ) {
			$current = $values[ $condition['field'] ?? '' ] ?? '';
			$expected = (string) ( $condition['value'] ?? '' );
			$list = is_array( $current ) ? array_map( 'strval', $current ) : [ (string) $current ];
			switch ( $condition['operator'] ?? 'is' ) {
				case 'is_not': $result = ! in_array( $expected, $list, true ); break;
				case 'contains': $result = is_array( $current ) ? in_array( $expected, $list, true ) : false !== strpos( (string) $current, $expected ); break;
				case 'empty': $result = empty( array_filter( $list, 'strlen' ) ); break;
				case 'not_empty': $result = ! empty( array_filter( $list, 'strlen' ) ); break;
				default: $result = in_array( $expected, $list, true );
			}
			$results[] = $result;
		}
		return 'any' === $relation ? in_array( true, $results, true ) : ! in_array( false, $results, true );
	}
}
