<?php
/**
 * Plugin Name: Cinderwell Local Update Guard
 * Description: Protects local Cinderwell source directories from package updates.
 * Version: 1.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! in_array( wp_get_environment_type(), [ 'local', 'development' ], true ) ) {
    return;
}

if ( ! defined( 'CINDERWELL_DISABLE_UPDATES' ) ) {
    define( 'CINDERWELL_DISABLE_UPDATES', true );
}

/**
 * Remove Cinderwell plugin packages from cached update results.
 */
function cinderwell_local_guard_plugin_updates( $transient ) {
    if ( ! is_object( $transient ) ) {
        return $transient;
    }

    $plugins = [
        'cinderwell/cinderwell.php',
        'cinderwell-alerts/cinderwell-alerts.php',
        'cinderwell-cookie-consent/cinderwell-cookie-consent.php',
        'cinderwell-help/cinderwell-help.php',
    ];

    foreach ( $plugins as $plugin ) {
        unset( $transient->response[ $plugin ], $transient->no_update[ $plugin ] );
    }

    return $transient;
}

/**
 * Remove the Cinderwell parent theme from cached update results.
 */
function cinderwell_local_guard_theme_updates( $transient ) {
    if ( ! is_object( $transient ) ) {
        return $transient;
    }

    unset(
        $transient->response['cinderwell-starter'],
        $transient->no_update['cinderwell-starter']
    );

    return $transient;
}

add_filter( 'pre_set_site_transient_update_plugins', 'cinderwell_local_guard_plugin_updates', PHP_INT_MAX );
add_filter( 'site_transient_update_plugins', 'cinderwell_local_guard_plugin_updates', PHP_INT_MAX );
add_filter( 'pre_set_site_transient_update_themes', 'cinderwell_local_guard_theme_updates', PHP_INT_MAX );
add_filter( 'site_transient_update_themes', 'cinderwell_local_guard_theme_updates', PHP_INT_MAX );

add_filter( 'auto_update_plugin', static function ( $update, $item ) {
    $plugin = isset( $item->plugin ) ? $item->plugin : '';

    return in_array( $plugin, [ 'cinderwell/cinderwell.php', 'cinderwell-alerts/cinderwell-alerts.php', 'cinderwell-cookie-consent/cinderwell-cookie-consent.php', 'cinderwell-help/cinderwell-help.php' ], true )
        ? false
        : $update;
}, PHP_INT_MAX, 2 );

add_filter( 'auto_update_theme', static function ( $update, $item ) {
    return isset( $item->theme ) && 'cinderwell-starter' === $item->theme ? false : $update;
}, PHP_INT_MAX, 2 );

/**
 * Block a stale or manually initiated package update before it can replace the
 * local source tree. Other WordPress plugin, theme, and core updates are not
 * affected.
 */
add_filter( 'upgrader_pre_install', static function ( $response, $hook_extra ) {
    if ( 'update' !== ( $hook_extra['action'] ?? '' ) ) {
        return $response;
    }

    $type = $hook_extra['type'] ?? '';

    if ( 'plugin' === $type ) {
        $plugins = array_filter( array_merge(
            [ $hook_extra['plugin'] ?? '' ],
            isset( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ? $hook_extra['plugins'] : []
        ) );

        if ( array_intersect( $plugins, [ 'cinderwell/cinderwell.php', 'cinderwell-alerts/cinderwell-alerts.php', 'cinderwell-cookie-consent/cinderwell-cookie-consent.php', 'cinderwell-help/cinderwell-help.php' ] ) ) {
            return new WP_Error(
                'cinderwell_local_update_blocked',
                __( 'Cinderwell package updates are disabled on this local development site.', 'cinderwell' )
            );
        }
    }

    if ( 'theme' === $type ) {
        $themes = array_filter( array_merge(
            [ $hook_extra['theme'] ?? '' ],
            isset( $hook_extra['themes'] ) && is_array( $hook_extra['themes'] ) ? $hook_extra['themes'] : []
        ) );

        if ( in_array( 'cinderwell-starter', $themes, true ) ) {
            return new WP_Error(
                'cinderwell_local_update_blocked',
                __( 'Cinderwell Base updates are disabled on this local development site.', 'cinderwell' )
            );
        }
    }

    return $response;
}, PHP_INT_MAX, 2 );
