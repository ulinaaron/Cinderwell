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
		add_filter( 'cinderwell_admin_menu_capability', [ $this, 'allow_editor_menu' ] );
		add_action( 'admin_init', [ $this, 'redirect_editor_root' ] );
        add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );

        new Post_Type();
        $renderer = new Renderer();
        new Admin_Bar( $renderer );

        if ( is_admin() ) {
            new Admin();
        }
    }

	public function allow_editor_menu() {
		return 'edit_posts';
	}

	public function redirect_editor_root() {
		if ( current_user_can( 'manage_options' ) || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if (
			'cinderwell' === $page &&
			! class_exists( 'Cinderwell_Help\\Help' ) &&
			! class_exists( 'Cinderwell_Popups\\Cinderwell_Popups' )
		) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=' . Post_Type::POST_TYPE ) );
			exit;
		}
	}

    public function register_documentation( $registry ) {
        $registry->register_directory( 'cinderwell-alerts', CINDERWELL_ALERTS_DIR . 'help' );
    }
}
