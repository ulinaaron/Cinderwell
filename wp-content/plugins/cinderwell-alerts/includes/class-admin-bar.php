<?php
/**
 * Cinderwell admin-bar integration.
 *
 * @package Cinderwell_Alerts
 */

namespace Cinderwell_Alerts;

defined( 'ABSPATH' ) || exit;

class Admin_Bar {

    private $renderer;

    public function __construct( Renderer $renderer ) {
        $this->renderer = $renderer;
        add_action( 'cinderwell_admin_bar_menu', [ $this, 'add_menu' ], 10, 3 );
    }

    /**
     * Add alert management and current-page shortcuts.
     *
     * @param \WP_Admin_Bar $admin_bar Admin bar instance.
     * @param string        $root_id   Cinderwell root node ID.
     * @param array         $context   Current request context.
     */
    public function add_menu( $admin_bar, $root_id, $context ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $active = $context['is_frontend'] ? $this->renderer->get_active_posts() : [];
        $active = array_values( array_filter( $active, static function ( $post ) {
            return current_user_can( 'edit_post', $post->ID );
        } ) );
        $count  = count( $active );
        $title  = $count
            ? sprintf( '%s <span class="ab-label">%d</span>', esc_html__( 'Alerts', 'cinderwell-alerts' ), $count )
            : esc_html__( 'Alerts', 'cinderwell-alerts' );

        $admin_bar->add_node( [
            'id'     => 'cinderwell-alerts',
            'parent' => $root_id,
            'title'  => $title,
            'href'   => admin_url( 'edit.php?post_type=' . Post_Type::POST_TYPE ),
            'meta'   => [ 'title' => esc_attr__( 'Manage alerts', 'cinderwell-alerts' ) ],
        ] );

        if ( $context['is_frontend'] ) {
            if ( $active ) {
                foreach ( $active as $post ) {
                    $placement = get_post_meta( $post->ID, '_cw_alert_placement', true ) ?: 'top';
                    $priority  = (int) get_post_meta( $post->ID, '_cw_alert_priority', true );
                    $admin_bar->add_node( [
                        'id'     => 'cinderwell-alert-' . $post->ID,
                        'parent' => 'cinderwell-alerts',
                        'title'  => sprintf(
                            /* translators: 1: Alert title. 2: Placement. 3: Priority. */
                            __( 'Edit “%1$s” — %2$s, priority %3$d', 'cinderwell-alerts' ),
                            esc_html( get_the_title( $post ) ?: __( 'Untitled alert', 'cinderwell-alerts' ) ),
                            esc_html( ucfirst( $placement ) ),
                            $priority
                        ),
                        'href'   => get_edit_post_link( $post->ID, 'raw' ),
                    ] );
                }
            } else {
                $admin_bar->add_node( [
                    'id'     => 'cinderwell-alerts-none',
                    'parent' => 'cinderwell-alerts',
                    'title'  => esc_html__( 'No active alerts on this page', 'cinderwell-alerts' ),
                    'href'   => false,
                    'meta'   => [ 'class' => 'cinderwell-alerts-none' ],
                ] );
            }
        }

        $admin_bar->add_node( [
            'id'     => 'cinderwell-alerts-new',
            'parent' => 'cinderwell-alerts',
            'title'  => esc_html__( 'Add new alert', 'cinderwell-alerts' ),
            'href'   => admin_url( 'post-new.php?post_type=' . Post_Type::POST_TYPE ),
        ] );

        $admin_bar->add_node( [
            'id'     => 'cinderwell-alerts-manage',
            'parent' => 'cinderwell-alerts',
            'title'  => esc_html__( 'Manage all alerts', 'cinderwell-alerts' ),
            'href'   => admin_url( 'edit.php?post_type=' . Post_Type::POST_TYPE ),
        ] );
    }
}
