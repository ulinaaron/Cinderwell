<?php
/**
 * Alert post type and metadata.
 *
 * @package Cinderwell_Alerts
 */

namespace Cinderwell_Alerts;

defined( 'ABSPATH' ) || exit;

class Post_Type {

    const POST_TYPE = 'cw_alert';

    public function __construct() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_filter( 'allowed_block_types_all', [ $this, 'limit_editor_blocks' ], 10, 2 );
    }

    public static function register() {
        register_post_type(
            self::POST_TYPE,
            [
                'labels' => [
                    'name'                  => __( 'Alerts', 'cinderwell-alerts' ),
                    'singular_name'         => __( 'Alert', 'cinderwell-alerts' ),
                    'add_new'               => __( 'Add Alert', 'cinderwell-alerts' ),
                    'add_new_item'          => __( 'Add Alert', 'cinderwell-alerts' ),
                    'edit_item'             => __( 'Edit Alert', 'cinderwell-alerts' ),
                    'new_item'              => __( 'New Alert', 'cinderwell-alerts' ),
                    'view_item'             => __( 'Preview Alert', 'cinderwell-alerts' ),
                    'search_items'          => __( 'Search Alerts', 'cinderwell-alerts' ),
                    'not_found'             => __( 'No alerts found.', 'cinderwell-alerts' ),
                    'item_published'        => __( 'Alert published.', 'cinderwell-alerts' ),
                    'item_updated'          => __( 'Alert updated.', 'cinderwell-alerts' ),
                    'item_reverted_to_draft'=> __( 'Alert reverted to draft.', 'cinderwell-alerts' ),
                ],
                'public'              => false,
                'publicly_queryable'  => false,
                'show_ui'             => true,
                'show_in_menu'        => 'cinderwell',
                'show_in_rest'        => true,
                'show_in_nav_menus'   => false,
                'exclude_from_search' => true,
                'menu_icon'           => 'dashicons-megaphone',
                'supports'            => [ 'title', 'editor', 'revisions', 'custom-fields' ],
                'has_archive'         => false,
                'rewrite'             => false,
                'map_meta_cap'        => true,
                'capability_type'     => 'post',
                'template'            => [
                    [
                        'cinderwell/body',
                        [
                            'showHeading'       => false,
                            'showBody'          => true,
                            'width'             => 'full',
                            'spacingResponsive' => [
                                'desktop' => [
                                    'linked' => true,
                                    'top'    => 'none',
                                    'bottom' => 'none',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        self::register_meta();
    }

    private static function register_meta() {
        $auth = static function () {
            return current_user_can( 'edit_posts' );
        };

        $definitions = [
            '_cw_alert_placement'    => [ 'type' => 'string', 'default' => 'top' ],
            '_cw_alert_tone'         => [ 'type' => 'string', 'default' => 'info' ],
            '_cw_alert_priority'     => [ 'type' => 'integer', 'default' => 10 ],
            '_cw_alert_start'        => [ 'type' => 'integer', 'default' => 0 ],
            '_cw_alert_end'          => [ 'type' => 'integer', 'default' => 0 ],
            '_cw_alert_audience'     => [ 'type' => 'string', 'default' => 'all' ],
            '_cw_alert_dismissible'  => [ 'type' => 'boolean', 'default' => true ],
            '_cw_alert_dismiss_days' => [ 'type' => 'integer', 'default' => 7 ],
            '_cw_alert_show_title'   => [ 'type' => 'boolean', 'default' => true ],
        ];

        foreach ( $definitions as $key => $definition ) {
            register_post_meta(
                self::POST_TYPE,
                $key,
                [
                    'single'        => true,
                    'show_in_rest'  => true,
                    'auth_callback' => $auth,
                    'type'          => $definition['type'],
                    'default'       => $definition['default'],
                ]
            );
        }

        foreach ( [ '_cw_alert_post_types', '_cw_alert_include_paths', '_cw_alert_exclude_paths' ] as $key ) {
            register_post_meta(
                self::POST_TYPE,
                $key,
                [
                    'single'        => true,
                    'show_in_rest'  => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'string' ],
                        ],
                    ],
                    'auth_callback' => $auth,
                    'type'          => 'array',
                    'default'       => [],
                ]
            );
        }
    }

    public function limit_editor_blocks( $allowed_blocks, $context ) {
        if ( self::POST_TYPE !== ( $context->post->post_type ?? '' ) ) {
            return $allowed_blocks;
        }

        $blocks = [
            'cinderwell/body',
            'cinderwell/cta',
            'cinderwell/button',
            'cinderwell/heading',
            'cinderwell/link',
            'cinderwell/icon',
            'cinderwell/note',
            'cinderwell/divider',
        ];

        return apply_filters( 'cinderwell_alerts_allowed_blocks', $blocks, $context );
    }
}
