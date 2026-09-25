<?php
/**
 * Plugin Name: Cinderwell SEO
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-seo
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-seo/
 * Description: Accessible search metadata, content analysis, social previews, schema, and native sitemap controls for Cinderwell sites.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-seo
 *
 * @package Cinderwell_SEO
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_SEO_VERSION', '0.1.0' );
define( 'CINDERWELL_SEO_FILE', __FILE__ );
define( 'CINDERWELL_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_SEO_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( static function ( $class ) {
	$prefix = 'Cinderwell_SEO\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = CINDERWELL_SEO_DIR . 'includes/class-' . strtolower( str_replace( [ '\\', '_' ], '-', $relative ) ) . '.php';
	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );

register_activation_hook( __FILE__, static function () {
	add_option( 'cinderwell_seo_settings', [], '', false );
} );

add_action( 'plugins_loaded', static function () {
	if ( ! defined( 'CINDERWELL_VERSION' ) || version_compare( CINDERWELL_VERSION, '0.1.86', '<' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Cinderwell SEO requires Cinderwell 0.1.86 or newer.', 'cinderwell-seo' );
			echo '</p></div>';
		} );
		return;
	}

	load_plugin_textdomain( 'cinderwell-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	Cinderwell_SEO\Plugin::instance();
}, 20 );

add_filter( 'cinderwell_addon_catalog', static function ( $catalog ) {
	if ( isset( $catalog['cinderwell-seo'] ) ) {
		$catalog['cinderwell-seo']['icon']            = 'dashicons-search';
		$catalog['cinderwell-seo']['settings_tab']    = 'seo';
		$catalog['cinderwell-seo']['health_callback'] = [ 'Cinderwell_SEO\\Plugin', 'get_health' ];
	}
	return $catalog;
} );

/** Resolve the current or supplied document's normalized SEO data. */
function cinderwell_seo_get_document( array $context = [] ) {
	return Cinderwell_SEO\Document::resolve( $context );
}

/** Resolve local search visibility without claiming search-engine inclusion. */
function cinderwell_seo_get_search_visibility( $object_id, $object_type = 'post' ) {
	return Cinderwell_SEO\Visibility::resolve( $object_id, $object_type );
}
