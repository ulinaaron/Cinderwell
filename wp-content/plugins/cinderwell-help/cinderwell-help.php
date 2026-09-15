<?php
/**
 * Plugin Name: Cinderwell Help
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-help
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-help/
 * Description: Client-facing documentation for Cinderwell with a theme-extensible topic library.
 * Version: 0.1.3
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-help
 *
 * @package Cinderwell_Help
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_HELP_VERSION', '0.1.3' );
define( 'CINDERWELL_HELP_FILE', __FILE__ );
define( 'CINDERWELL_HELP_DIR', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_HELP_URL', plugin_dir_url( __FILE__ ) );

require_once CINDERWELL_HELP_DIR . 'includes/class-help.php';

add_action( 'plugins_loaded', static function () {
    if ( ! defined( 'CINDERWELL_VERSION' ) ) {
        add_action( 'admin_notices', static function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e( 'Cinderwell Help requires the Cinderwell plugin to be active.', 'cinderwell-help' );
            echo '</p></div>';
        } );
        return;
    }

    Cinderwell_Help\Help::instance();
}, 20 );
