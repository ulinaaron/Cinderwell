<?php
/**
 * Main add-on coordinator.
 *
 * @package Cinderwell_Alerts
 */

namespace Cinderwell_Alerts;

defined( 'ABSPATH' ) || exit;

class Plugin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        new Post_Type();
        $renderer = new Renderer();
        new Admin_Bar( $renderer );

        if ( is_admin() ) {
            new Admin();
        }
    }
}
