<?php
/**
 * Add-on coordinator.
 *
 * @package Cinderwell_Cookie_Consent
 */

namespace Cinderwell_Cookie_Consent;

defined( 'ABSPATH' ) || exit;

class Plugin {

    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );

        new Settings();
        new Assets();
        new Frontend();
    }

    public function register_documentation( $registry ) {
        $registry->register_directory( 'cinderwell-cookie-consent', CINDERWELL_COOKIE_CONSENT_DIR . 'help' );
    }
}
