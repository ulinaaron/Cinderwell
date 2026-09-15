<?php
/**
 * Plugin Name: Cinderwell Popups
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-popups
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-popups/
 * Description: Accessible modals built with Cinderwell blocks and opened automatically or by a button.
 * Version: 0.1.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-popups
 *
 * @package Cinderwell_Popups
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_POPUPS_VERSION', '0.1.2' );
define( 'CINDERWELL_POPUPS_FILE', __FILE__ );
define( 'CINDERWELL_POPUPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_POPUPS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( static function ( $class ) {
	if ( 0 !== strpos( $class, 'Cinderwell_Popups\\' ) ) {
		return;
	}

	$class_name = str_replace( 'Cinderwell_Popups\\', '', $class );
	$file       = CINDERWELL_POPUPS_PATH . 'includes/class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

register_activation_hook( __FILE__, static function () {
	if ( class_exists( 'Cinderwell_Popups\\Popup_CPT' ) ) {
		Cinderwell_Popups\Popup_CPT::register();
		flush_rewrite_rules();
	}
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'Cinderwell\\Block_Loader' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'Cinderwell Popups requires the Cinderwell plugin to be active.', 'cinderwell-popups' );
			echo '</p></div>';
		} );
		return;
	}

	load_plugin_textdomain( 'cinderwell-popups', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	Cinderwell_Popups\Cinderwell_Popups::instance();
}, 20 );

add_filter( 'cinderwell_addon_catalog', static function ( $catalog ) {
	if ( isset( $catalog['cinderwell-popups'] ) ) {
		$catalog['cinderwell-popups']['icon']            = 'dashicons-format-chat';
		$catalog['cinderwell-popups']['settings_url']    = admin_url( 'edit.php?post_type=cinderwell_popup' );
		$catalog['cinderwell-popups']['health_callback'] = static function () {
			$counts = wp_count_posts( 'cinderwell_popup' );
			$count  = isset( $counts->publish ) ? absint( $counts->publish ) : 0;

			return $count
				? [ 'status' => 'good', 'message' => sprintf( _n( '%d published popup.', '%d published popups.', $count, 'cinderwell-popups' ), $count ) ]
				: [ 'status' => 'warning', 'message' => __( 'No published popups yet.', 'cinderwell-popups' ) ];
		};
	}

	return $catalog;
} );
