<?php
/**
 * Plugin Name: Cinderwell Events
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-events
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-events/
 * Description: One-time and multi-session event publishing for Cinderwell sites.
 * Version: 0.1.3
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-events
 *
 * @package Cinderwell_Events
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_EVENTS_VERSION', '0.1.3' );
define( 'CINDERWELL_EVENTS_FILE', __FILE__ );
define( 'CINDERWELL_EVENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_EVENTS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( static function ( $class ) {
	$prefix = 'Cinderwell_Events\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = CINDERWELL_EVENTS_PATH . 'includes/class-' . strtolower( str_replace( [ '\\', '_' ], '-', $relative ) ) . '.php';
	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );

/**
 * Fetch Sessions through the public query contract.
 *
 * @param array $args Query arguments.
 * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
 */
function cinderwell_events_get_sessions( array $args = [] ) {
	return Cinderwell_Events\Plugin::instance()->sessions()->query( $args, true );
}

/** Get one normalized Session record. */
function cinderwell_events_get_session( $session_id ) {
	return Cinderwell_Events\Plugin::instance()->sessions()->get( absint( $session_id ) );
}

/** Create one Session or return WP_Error. */
function cinderwell_events_create_session( $event_id, array $data ) {
	return Cinderwell_Events\Plugin::instance()->sessions()->create( absint( $event_id ), $data );
}

/** Update one Session or return WP_Error. */
function cinderwell_events_update_session( $session_id, array $data ) {
	return Cinderwell_Events\Plugin::instance()->sessions()->update( absint( $session_id ), $data );
}

/** Delete one Session. */
function cinderwell_events_delete_session( $session_id ) {
	return Cinderwell_Events\Plugin::instance()->sessions()->delete( absint( $session_id ) );
}

register_activation_hook( __FILE__, static function () {
	Cinderwell_Events\Database::install();
	Cinderwell_Events\Event_Post_Type::register();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'plugins_loaded', static function () {
	if ( ! defined( 'CINDERWELL_VERSION' ) || version_compare( CINDERWELL_VERSION, '0.1.85', '<' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Cinderwell Events requires Cinderwell 0.1.85 or newer.', 'cinderwell-events' );
			echo '</p></div>';
		} );
		return;
	}

	load_plugin_textdomain( 'cinderwell-events', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	Cinderwell_Events\Plugin::instance();
}, 20 );

add_filter( 'cinderwell_addon_catalog', static function ( $catalog ) {
	if ( isset( $catalog['cinderwell-events'] ) ) {
		$catalog['cinderwell-events']['icon']            = 'dashicons-calendar-alt';
		$catalog['cinderwell-events']['settings_tab']    = 'events';
		$catalog['cinderwell-events']['health_callback'] = [ 'Cinderwell_Events\\Plugin', 'get_health' ];
	}
	return $catalog;
} );
