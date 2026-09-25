<?php
/**
 * Plugin Name: Cinderwell Snippet Manager
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-snippets
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-snippets/
 * Description: Safely manage PHP, JavaScript, CSS, and HTML snippets for Cinderwell sites.
 * Version: 0.1.3
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-snippets
 *
 * @package Cinderwell_Snippets
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_SNIPPETS_VERSION', '0.1.3' );
define( 'CINDERWELL_SNIPPETS_FILE', __FILE__ );
define( 'CINDERWELL_SNIPPETS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_SNIPPETS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( static function ( $class ) {
	if ( 0 !== strpos( $class, 'Cinderwell_Snippets\\' ) ) {
		return;
	}

	$class_name = str_replace( 'Cinderwell_Snippets\\', '', $class );
	$file       = CINDERWELL_SNIPPETS_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Execute trusted PHP snippet code in the global namespace.
 *
 * Variables supplied in $context are made available to shortcode snippets.
 * This function intentionally cannot sandbox PHP; access is restricted to
 * administrators with the unfiltered_html capability.
 *
 * @param string $code    PHP code normalized without opening or closing tags.
 * @param array  $context Variables exposed to the snippet.
 * @return mixed
 */
function cinderwell_snippets_execute_php_code( $code, array $context = [] ) {
	extract( $context, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Deliberate snippet scope.
	return eval( $code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- This plugin exists to run administrator-authored snippets.
}

register_activation_hook( __FILE__, static function () {
	Cinderwell_Snippets\Post_Type::register();
	Cinderwell_Snippets\Repository::rebuild_manifest();
	add_option( Cinderwell_Snippets\Repository::SAFE_MODE_OPTION, false, '', false );
} );

add_action( 'plugins_loaded', static function () {
	if ( ! defined( 'CINDERWELL_VERSION' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'Cinderwell Snippet Manager requires the Cinderwell plugin to be active.', 'cinderwell-snippets' );
			echo '</p></div>';
		} );
		return;
	}

	load_plugin_textdomain( 'cinderwell-snippets', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	Cinderwell_Snippets\Plugin::instance();
}, 20 );

add_filter( 'cinderwell_addon_catalog', static function ( $catalog ) {
	if ( isset( $catalog['cinderwell-snippets'] ) ) {
		$catalog['cinderwell-snippets']['icon']            = 'dashicons-editor-code';
		$catalog['cinderwell-snippets']['settings_url']    = admin_url( 'admin.php?page=cinderwell-snippets' );
		$catalog['cinderwell-snippets']['health_callback'] = [ 'Cinderwell_Snippets\\Plugin', 'get_health' ];
	}
	return $catalog;
} );
