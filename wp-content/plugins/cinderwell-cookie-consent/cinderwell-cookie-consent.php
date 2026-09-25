<?php
/**
 * Plugin Name: Cinderwell Cookie Consent
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-cookie-consent
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-cookie-consent/
 * Description: Accessible, category-based consent controls and prior blocking for optional site technologies.
 * Version: 0.1.3
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-cookie-consent
 *
 * @package Cinderwell_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_COOKIE_CONSENT_VERSION', '0.1.3' );
define( 'CINDERWELL_COOKIE_CONSENT_FILE', __FILE__ );
define( 'CINDERWELL_COOKIE_CONSENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_COOKIE_CONSENT_URL', plugin_dir_url( __FILE__ ) );

require_once CINDERWELL_COOKIE_CONSENT_DIR . 'includes/class-settings.php';
require_once CINDERWELL_COOKIE_CONSENT_DIR . 'includes/class-assets.php';
require_once CINDERWELL_COOKIE_CONSENT_DIR . 'includes/class-frontend.php';
require_once CINDERWELL_COOKIE_CONSENT_DIR . 'includes/class-plugin.php';

add_action( 'plugins_loaded', static function () {
    if ( ! defined( 'CINDERWELL_VERSION' ) || ! class_exists( 'Cinderwell\\Admin_Fields' ) ) {
        add_action( 'admin_notices', static function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e( 'Cinderwell Cookie Consent requires the current Cinderwell plugin to be active.', 'cinderwell-cookie-consent' );
            echo '</p></div>';
        } );
        return;
    }
    Cinderwell_Cookie_Consent\Plugin::instance();
}, 20 );

register_activation_hook( __FILE__, static function () {
    if ( false === get_option( Cinderwell_Cookie_Consent\Settings::OPTION, false ) ) {
        add_option( Cinderwell_Cookie_Consent\Settings::OPTION, Cinderwell_Cookie_Consent\Settings::defaults() );
    }
} );
