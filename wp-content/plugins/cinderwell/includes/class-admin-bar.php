<?php
/**
 * Shared admin-bar entry point for Cinderwell and its add-ons.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Admin_Bar {

    const ROOT_ID = 'cinderwell';

    public function __construct() {
        add_action( 'admin_bar_menu', [ $this, 'add_menu' ], 90 );
    }

    /**
     * Add the root menu, then give add-ons a stable place to contribute nodes.
     *
     * @param \WP_Admin_Bar $admin_bar WordPress admin bar instance.
     */
    public function add_menu( $admin_bar ) {
        $capability = apply_filters( 'cinderwell_admin_bar_capability', 'edit_posts' );
        if ( ! current_user_can( $capability ) ) {
            return;
        }

        $settings_url = current_user_can( 'manage_options' )
            ? admin_url( 'admin.php?page=cinderwell' )
            : false;

        $admin_bar->add_node( [
            'id'    => self::ROOT_ID,
            'title' => esc_html__( 'Cinderwell', 'cinderwell' ),
            'href'  => $settings_url,
            'meta'  => [
                'class' => 'cinderwell-admin-bar',
                'title' => esc_attr__( 'Cinderwell tools', 'cinderwell' ),
            ],
        ] );

        if ( $settings_url ) {
            $admin_bar->add_node( [
                'id'     => 'cinderwell-settings',
                'parent' => self::ROOT_ID,
                'title'  => esc_html__( 'Cinderwell settings', 'cinderwell' ),
                'href'   => $settings_url,
            ] );
        }

        /**
         * Add items to the Cinderwell admin-bar menu.
         *
         * Add-ons should use the supplied root ID as the parent for their nodes.
         * The context is deliberately read-only and describes the current request.
         *
         * @param \WP_Admin_Bar $admin_bar Admin bar instance.
         * @param string        $root_id   Cinderwell root node ID.
         * @param array         $context   Current request context.
         */
        do_action(
            'cinderwell_admin_bar_menu',
            $admin_bar,
            self::ROOT_ID,
            [
                'is_admin'         => is_admin(),
                'is_frontend'      => ! is_admin(),
                'queried_object_id' => is_admin() ? 0 : get_queried_object_id(),
                'request_path'     => isset( $GLOBALS['wp']->request ) ? '/' . trim( (string) $GLOBALS['wp']->request, '/' ) : '',
            ]
        );
    }
}
