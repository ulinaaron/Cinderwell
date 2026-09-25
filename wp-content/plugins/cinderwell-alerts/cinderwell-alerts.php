<?php
/**
 * Plugin Name: Cinderwell Alerts
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-alerts
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-alerts/
 * Description: Scheduled, condition-aware alerts composed with Cinderwell blocks.
 * Version: 0.1.7
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-alerts
 *
 * @package Cinderwell_Alerts
 */

defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_ALERTS_VERSION', '0.1.7' );
define( 'CINDERWELL_ALERTS_FILE', __FILE__ );
define( 'CINDERWELL_ALERTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CINDERWELL_ALERTS_URL', plugin_dir_url( __FILE__ ) );

require_once CINDERWELL_ALERTS_DIR . 'includes/class-post-type.php';
require_once CINDERWELL_ALERTS_DIR . 'includes/class-conditions.php';
require_once CINDERWELL_ALERTS_DIR . 'includes/class-admin.php';
require_once CINDERWELL_ALERTS_DIR . 'includes/class-admin-bar.php';
require_once CINDERWELL_ALERTS_DIR . 'includes/class-renderer.php';
require_once CINDERWELL_ALERTS_DIR . 'includes/class-plugin.php';

add_action( 'plugins_loaded', static function () {
    if ( ! defined( 'CINDERWELL_VERSION' ) ) {
        add_action( 'admin_notices', static function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e( 'Cinderwell Alerts requires the Cinderwell plugin to be active.', 'cinderwell-alerts' );
            echo '</p></div>';
        } );
        return;
    }

    Cinderwell_Alerts\Plugin::instance();
}, 20 );

register_activation_hook( __FILE__, static function () {
    Cinderwell_Alerts\Post_Type::register();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_filter( 'cinderwell_addon_catalog', static function ( $catalog ) {
    if ( isset( $catalog['cinderwell-alerts'] ) ) {
        $catalog['cinderwell-alerts']['icon']         = 'dashicons-megaphone';
        $catalog['cinderwell-alerts']['settings_url'] = admin_url( 'edit.php?post_type=cw_alert' );
        $catalog['cinderwell-alerts']['health_callback'] = static function () {
            $counts = wp_count_posts( 'cw_alert' );
            $count  = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
            return $count
                ? [ 'status' => 'good', 'message' => sprintf( _n( '%d published alert.', '%d published alerts.', $count, 'cinderwell-alerts' ), $count ) ]
                : [ 'status' => 'warning', 'message' => __( 'No published alerts yet.', 'cinderwell-alerts' ) ];
        };
    }
    return $catalog;
} );
