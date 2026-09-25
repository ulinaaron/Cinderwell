<?php
/**
 * Plugin Name: Cinderwell Help
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-help
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-help/
 * Description: Client-facing admin presentation for package-owned Cinderwell documentation.
 * Version: 0.2.1
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

define( 'CINDERWELL_HELP_VERSION', '0.2.1' );
define( 'CINDERWELL_HELP_FILE', __FILE__ );
define( 'CINDERWELL_HELP_DIR', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_HELP_URL', plugin_dir_url( __FILE__ ) );

require_once CINDERWELL_HELP_DIR . 'includes/class-help.php';

add_action( 'plugins_loaded', static function () {
    if ( ! defined( 'CINDERWELL_VERSION' ) || version_compare( CINDERWELL_VERSION, '0.1.85', '<' ) || ! class_exists( 'Cinderwell\\Documentation' ) ) {
        add_action( 'admin_notices', static function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e( 'Cinderwell Help requires Cinderwell 0.1.85 or newer.', 'cinderwell-help' );
            echo '</p></div>';
        } );
        return;
    }

    Cinderwell_Help\Help::instance();
}, 20 );
