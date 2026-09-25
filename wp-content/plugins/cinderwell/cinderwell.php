<?php
/**
 * Plugin Name: Cinderwell
 * Plugin URI: https://github.com/ulinaaron/Cinderwell
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell/
 * Description: A governed WordPress foundation with a curated block kit, design system, client theme layer, and focused add-on ecosystem.
 * Version: 0.1.102
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: Stevens Inc.
 * Author URI: https://stevensinc.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cinderwell
 * Domain Path: /languages
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_VERSION', '0.1.102' );
define( 'CINDERWELL_FILE', __FILE__ );
define( 'CINDERWELL_DIR', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_URL', plugin_dir_url( __FILE__ ) );
define( 'CINDERWELL_BUILD_DIR', CINDERWELL_DIR . 'build/' );
define( 'CINDERWELL_BUILD_URL', CINDERWELL_URL . 'build/' );

// Autoloader.
spl_autoload_register( function ( $class ) {
    $prefix = 'Cinderwell\\';
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }
    $relative = substr( $class, strlen( $prefix ) );
    $file     = CINDERWELL_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Boot.
add_action( 'plugins_loaded', function () {
    Cinderwell\Cinderwell::instance();
} );

// Activation hook.
register_activation_hook( __FILE__, function () {
    if ( ! get_option( 'cinderwell_design_tokens' ) ) {
        update_option( 'cinderwell_design_tokens', [] );
    }
    if ( ! get_option( 'cinderwell_block_permissions' ) ) {
        update_option( 'cinderwell_block_permissions', [] );
    }
    if ( false === get_option( 'cinderwell_enabled_addons', false ) ) {
        add_option( 'cinderwell_enabled_addons', [] );
    }
    flush_rewrite_rules();
} );

// Deactivation hook.
register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );
