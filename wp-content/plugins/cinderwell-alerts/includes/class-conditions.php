<?php
/**
 * Frontend alert eligibility rules.
 *
 * @package Cinderwell_Alerts
 */

namespace Cinderwell_Alerts;

defined( 'ABSPATH' ) || exit;

class Conditions {

    public static function is_eligible( $post ) {
        if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
            return false;
        }

        $now   = time();
        $start = absint( get_post_meta( $post->ID, '_cw_alert_start', true ) );
        $end   = absint( get_post_meta( $post->ID, '_cw_alert_end', true ) );

        if ( ( $start && $now < $start ) || ( $end && $now > $end ) ) {
            return false;
        }

        $path     = self::current_path();
        $included = self::string_array( get_post_meta( $post->ID, '_cw_alert_include_paths', true ) );
        $excluded = self::string_array( get_post_meta( $post->ID, '_cw_alert_exclude_paths', true ) );

        if ( self::matches_any( $path, $excluded ) ) {
            return false;
        }

        if ( $included && ! self::matches_any( $path, $included ) ) {
            return false;
        }

        $audience   = get_post_meta( $post->ID, '_cw_alert_audience', true ) ?: 'all';
        $post_types = self::string_array( get_post_meta( $post->ID, '_cw_alert_post_types', true ) );

        switch ( $audience ) {
            case 'front_page':
                $eligible = is_front_page();
                break;
            case 'singular':
                $eligible = $post_types ? is_singular( $post_types ) : is_singular();
                break;
            case 'archives':
                $eligible = is_archive() || is_home() || is_search();
                break;
            case 'paths':
                $eligible = ! empty( $included );
                break;
            case 'all':
            default:
                $eligible = true;
                break;
        }

        return (bool) apply_filters(
            'cinderwell_alerts_is_eligible',
            $eligible,
            $post,
            [
                'path'       => $path,
                'audience'   => $audience,
                'post_types' => $post_types,
                'included'   => $included,
                'excluded'   => $excluded,
            ]
        );
    }

    private static function current_path() {
        $request = isset( $GLOBALS['wp']->request ) ? (string) $GLOBALS['wp']->request : '';
        return '/' . trim( $request, '/' ) . ( $request ? '/' : '' );
    }

    private static function string_array( $value ) {
        if ( ! is_array( $value ) ) {
            return [];
        }
        return array_values( array_filter( array_map( 'strval', $value ) ) );
    }

    private static function matches_any( $path, $patterns ) {
        foreach ( $patterns as $pattern ) {
            $expression = '#^' . str_replace( '\\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';
            if ( preg_match( $expression, $path ) ) {
                return true;
            }
        }
        return false;
    }
}
