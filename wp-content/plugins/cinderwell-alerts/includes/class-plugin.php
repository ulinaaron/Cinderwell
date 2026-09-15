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
        add_filter( 'cinderwell_help_sections', [ $this, 'add_help_section' ] );
        add_filter( 'cinderwell_help_topics', [ $this, 'add_help_topics' ] );

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

    public function add_help_section( $sections ) {
        $sections['alerts'] = [
            'title'       => __( 'Alerts', 'cinderwell-alerts' ),
            'description' => __( 'Create, target, schedule, and review site alerts.', 'cinderwell-alerts' ),
            'order'       => 50,
        ];
        return $sections;
    }

    public function add_help_topics( $topics ) {
        $topics['alerts-create'] = [
            'section' => 'alerts',
            'title'   => __( 'Create and publish an alert', 'cinderwell-alerts' ),
            'summary' => __( 'Build an accessible alert with the focused block editor.', 'cinderwell-alerts' ),
            'icon'    => 'dashicons-megaphone',
            'order'   => 10,
            'content' => sprintf(
                wp_kses_post( __( '<p>Open <a href="%s"><strong>Alerts</strong></a> and choose <strong>Add New</strong>. Add the message with the available Cinderwell blocks, give the alert a descriptive internal title, then publish it.</p><p>The alert editor intentionally limits available blocks so the message remains compact and dependable.</p>', 'cinderwell-alerts' ) ),
                esc_url( admin_url( 'edit.php?post_type=cw_alert' ) )
            ),
        ];
        $topics['alerts-targeting'] = [
            'section' => 'alerts',
            'title'   => __( 'Choose where and when an alert appears', 'cinderwell-alerts' ),
            'summary' => __( 'Configure placement, scheduling, priority, and page conditions.', 'cinderwell-alerts' ),
            'icon'    => 'dashicons-calendar-alt',
            'order'   => 20,
            'content' => __( '<p>Use the alert settings beside the editor to select a top or bottom placement, optional start and end times, and the pages where it applies. Scheduling uses the WordPress site timezone.</p><p>When multiple alerts qualify for the same placement, the higher priority number wins. Preview the target page while signed in; the Cinderwell admin bar identifies alerts active on that page.</p>', 'cinderwell-alerts' ),
        ];
        $topics['alerts-dismissal'] = [
            'section' => 'alerts',
            'title'   => __( 'Control alert dismissal', 'cinderwell-alerts' ),
            'summary' => __( 'Decide whether visitors can close an alert and when it returns.', 'cinderwell-alerts' ),
            'icon'    => 'dashicons-dismiss',
            'order'   => 30,
            'content' => __( '<p>Enable dismissal when visitors do not need to see the message on every visit. The expiry determines how long that browser remembers the choice. Critical or rapidly changing information may be better left non-dismissible.</p>', 'cinderwell-alerts' ),
        ];
        return $topics;
    }
}
