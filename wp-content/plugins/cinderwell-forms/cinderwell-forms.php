<?php
/**
 * Plugin Name: Cinderwell Forms
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-forms
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-forms/
 * Description: Accessible form building, submissions, and notifications for Cinderwell sites.
 * Version: 0.1.17
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-forms
 *
 * @package Cinderwell_Forms
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_FORMS_VERSION', '0.1.17' );
define( 'CINDERWELL_FORMS_FILE', __FILE__ );
define( 'CINDERWELL_FORMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_FORMS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( static function ( $class ) {
	$prefix = 'Cinderwell_Forms\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$relative = substr( $class, strlen( $prefix ) );
	$file     = CINDERWELL_FORMS_PATH . 'includes/class-' . strtolower( str_replace( [ '\\', '_' ], '-', $relative ) ) . '.php';
	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );

/** Get a normalized form definition. */
function cinderwell_forms_get_form( $form_id ) {
	return Cinderwell_Forms\Form_Repository::get( absint( $form_id ) );
}

/** Render a published form. */
function cinderwell_forms_render_form( $form_id, array $args = [] ) {
	return Cinderwell_Forms\Renderer::render( absint( $form_id ), $args );
}

register_activation_hook( __FILE__, static function () {
	Cinderwell_Forms\Database::install();
	Cinderwell_Forms\Post_Type::register();
	Cinderwell_Forms\Capabilities::install();
	Cinderwell_Forms\Retention::schedule();
} );

register_deactivation_hook( __FILE__, [ 'Cinderwell_Forms\\Retention', 'unschedule' ] );

add_action( 'plugins_loaded', static function () {
	if ( ! defined( 'CINDERWELL_VERSION' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Cinderwell Forms requires the Cinderwell plugin.', 'cinderwell-forms' ) . '</p></div>';
		} );
		return;
	}
	load_plugin_textdomain( 'cinderwell-forms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	Cinderwell_Forms\Plugin::instance();
}, 20 );

add_filter( 'cinderwell_addon_catalog', static function ( $catalog ) {
	if ( isset( $catalog['cinderwell-forms'] ) ) {
		$catalog['cinderwell-forms']['icon']            = 'dashicons-feedback';
		$catalog['cinderwell-forms']['settings_url']    = admin_url( 'admin.php?page=cinderwell-forms' );
		$catalog['cinderwell-forms']['health_callback'] = [ 'Cinderwell_Forms\\Plugin', 'get_health' ];
	}
	return $catalog;
} );
