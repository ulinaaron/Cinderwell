<?php
/**
 * Opt-in company locations module.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Locations {

    const OPTION      = 'cinderwell_locations_settings';
    const POST_TYPE   = 'cw_location';
    const META_PREFIX = '_cw_location_';

    public function __construct() {
        add_filter( 'cinderwell_help_sections', [ $this, 'add_help_section' ] );
        add_filter( 'cinderwell_help_topics', [ $this, 'add_help_topics' ] );
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 9 );
        add_action( 'admin_post_cinderwell_save_locations', [ $this, 'save_settings' ] );
        add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'add_meta_box' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_location' ] );
        add_filter( 'cinderwell_data_sources', [ $this, 'register_data_sources' ] );
        add_filter( 'cinderwell_resolve_data_source', [ $this, 'resolve_data_source' ], 10, 3 );
        add_filter( 'cinderwell_editor_preview_values', [ $this, 'add_editor_preview_values' ] );
        add_filter( 'cinderwell_loop_post_type_allowed', [ $this, 'allow_location_loop' ], 10, 3 );
        add_filter( 'cinderwell_loop_link_behavior', [ $this, 'filter_loop_link_behavior' ], 10, 3 );

        $template_dir = CINDERWELL_DIR . 'templates/locations/';
        new Module_Templates( 'cinderwell', [
            'single-' . self::POST_TYPE => [
                'title'       => __( 'Location', 'cinderwell' ),
                'description' => __( 'Default single company location template.', 'cinderwell' ),
                'path'        => $template_dir . 'single-cw_location.html',
                'post_types'  => [ self::POST_TYPE ],
            ],
            'archive-' . self::POST_TYPE => [
                'title'       => __( 'Locations Archive', 'cinderwell' ),
                'description' => __( 'Default company locations archive template.', 'cinderwell' ),
                'path'        => $template_dir . 'archive-cw_location.html',
            ],
        ] );
    }

    public function add_help_section( $sections ) {
        $sections['locations'] = [
            'title'       => __( 'Locations', 'cinderwell' ),
            'description' => __( 'Maintain offices, branches, campuses, and service areas.', 'cinderwell' ),
            'order'       => 100,
        ];
        return $sections;
    }

    public function add_help_topics( $topics ) {
        $topics['locations-manage'] = [
            'section' => 'locations',
            'title'   => __( 'Add and update locations', 'cinderwell' ),
            'summary' => __( 'Maintain each location’s content and structured details.', 'cinderwell' ),
            'icon'    => 'dashicons-location-alt',
            'order'   => 10,
            'content' => sprintf(
                wp_kses_post( __( '<p>Open <a href="%s"><strong>Locations</strong></a> to add or edit an entry. Use the title for the public location name, the editor for descriptive content, the featured image for its primary visual, and the location fields for address and contact details.</p>', 'cinderwell' ) ),
                esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) )
            ),
        ];
        $topics['locations-display'] = [
            'section' => 'locations',
            'title'   => __( 'Display locations', 'cinderwell' ),
            'summary' => __( 'Build a location listing from the shared source entries.', 'cinderwell' ),
            'icon'    => 'dashicons-grid-view',
            'order'   => 20,
            'content' => __( '<p>Add a Cinderwell Loop block and choose Locations as its content type. Keep addresses and contact details on the location entry so every listing stays synchronized. Individual links work only when public location pages are enabled.</p>', 'cinderwell' ),
        ];
        return $topics;
    }

    public static function get_defaults() {
        return [
            'public_locations' => false,
            'location_slug'    => 'locations',
        ];
    }

    public static function get_settings() {
        $settings                     = wp_parse_args( (array) get_option( self::OPTION, [] ), self::get_defaults() );
        $settings['public_locations'] = ! empty( $settings['public_locations'] );
        $settings['location_slug']    = sanitize_title( $settings['location_slug'] ) ?: 'locations';
        return $settings;
    }

    public static function get_editor_settings() {
        $settings = self::get_settings();
        return [
            'enabled'         => true,
            'locationsPublic' => $settings['public_locations'],
            'postType'        => self::POST_TYPE,
        ];
    }

    public static function get_field_definitions() {
        return Admin_Fields::normalize_fields( apply_filters( 'cinderwell_location_fields', [
            'phone' => [ 'label' => __( 'Phone', 'cinderwell' ), 'type' => 'tel' ],
            'email' => [ 'label' => __( 'Email', 'cinderwell' ), 'type' => 'email' ],
            'address_line_1' => [ 'label' => __( 'Street address', 'cinderwell' ) ],
            'address_line_2' => [ 'label' => __( 'Address line 2', 'cinderwell' ) ],
            'locality' => [ 'label' => __( 'City / locality', 'cinderwell' ) ],
            'region' => [ 'label' => __( 'State / region', 'cinderwell' ) ],
            'postal_code' => [ 'label' => __( 'Postal code', 'cinderwell' ) ],
            'country' => [ 'label' => __( 'Country', 'cinderwell' ) ],
            'hours' => [ 'label' => __( 'Hours', 'cinderwell' ), 'type' => 'textarea' ],
            'directions_url' => [ 'label' => __( 'Directions URL', 'cinderwell' ), 'type' => 'url' ],
        ] ) );
    }

    public function register_post_type() {
        $settings = self::get_settings();
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'               => __( 'Locations', 'cinderwell' ),
                'singular_name'      => __( 'Location', 'cinderwell' ),
                'add_new_item'       => __( 'Add New Location', 'cinderwell' ),
                'edit_item'          => __( 'Edit Location', 'cinderwell' ),
                'view_item'          => __( 'View Location', 'cinderwell' ),
                'search_items'       => __( 'Search Locations', 'cinderwell' ),
                'not_found'          => __( 'No locations found.', 'cinderwell' ),
                'featured_image'     => __( 'Location image', 'cinderwell' ),
                'set_featured_image' => __( 'Set location image', 'cinderwell' ),
            ],
            'public'              => true,
            'publicly_queryable'  => $settings['public_locations'],
            'exclude_from_search' => ! $settings['public_locations'],
            'show_in_rest'        => true,
            'show_in_nav_menus'   => $settings['public_locations'],
            'has_archive'         => $settings['public_locations'] ? $settings['location_slug'] : false,
            'rewrite'             => $settings['public_locations'] ? [ 'slug' => $settings['location_slug'], 'with_front' => false ] : false,
            'query_var'           => $settings['public_locations'],
            'menu_icon'           => 'dashicons-location-alt',
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ],
        ] );

        foreach ( self::get_field_definitions() as $key => $field ) {
            register_post_meta( self::POST_TYPE, self::META_PREFIX . $key, [
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => $this->get_meta_sanitizer( $field['type'] ),
                'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
                    return current_user_can( 'edit_post', $post_id );
                },
            ] );
        }
    }

    public function add_meta_box() {
        add_meta_box( 'cinderwell-location-details', __( 'Location Details', 'cinderwell' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'cinderwell_save_location', 'cinderwell_location_nonce' );
        $values = [];
        foreach ( self::get_field_definitions() as $key => $field ) {
            $values[ $key ] = get_post_meta( $post->ID, self::META_PREFIX . $key, true );
        }
        Admin_Fields::render_table( self::get_field_definitions(), $values, 'cinderwell_location', 'cw-location' );
    }

    public function save_location( $post_id ) {
        if ( ! isset( $_POST['cinderwell_location_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cinderwell_location_nonce'] ) ), 'cinderwell_save_location' ) ) {
            return;
        }
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        $submitted = isset( $_POST['cinderwell_location'] ) ? (array) wp_unslash( $_POST['cinderwell_location'] ) : [];
        $values    = Admin_Fields::sanitize_values( self::get_field_definitions(), $submitted );
        foreach ( self::get_field_definitions() as $key => $field ) {
            if ( '' === ( $values[ $key ] ?? '' ) ) {
                delete_post_meta( $post_id, self::META_PREFIX . $key );
            } else {
                update_post_meta( $post_id, self::META_PREFIX . $key, $values[ $key ] );
            }
        }
    }

    public function add_settings_tab( $tabs ) {
        $tabs['locations'] = [
            'label'    => __( 'Locations', 'cinderwell' ),
            'group'    => 'content',
            'callback' => [ $this, 'render_settings' ],
        ];
        return $tabs;
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage location settings.', 'cinderwell' ) );
        }
        check_admin_referer( 'cinderwell_save_locations' );
        $fields = [
            'public_locations' => [ 'label' => __( 'Public locations', 'cinderwell' ), 'type' => 'checkbox' ],
            'location_slug' => [ 'label' => __( 'Location URL base', 'cinderwell' ), 'type' => 'slug', 'default' => 'locations' ],
        ];
        $submitted = isset( $_POST['locations'] ) ? (array) wp_unslash( $_POST['locations'] ) : [];
        $settings  = Admin_Fields::sanitize_values( $fields, $submitted );
        $settings['location_slug'] = $settings['location_slug'] ?: 'locations';
        update_option( self::OPTION, $settings );
        update_option( 'cinderwell_flush_rewrite_rules', 1, false );
        wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'locations', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_settings() {
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Location settings saved.', 'cinderwell' ) . '</p></div>';
        }
        $fields = [
            'public_locations' => [
                'label'          => __( 'Public locations', 'cinderwell' ),
                'type'           => 'checkbox',
                'checkbox_label' => __( 'Enable individual location pages and an archive', 'cinderwell' ),
                'description'    => __( 'Locations remain editable and available to loops when public pages are disabled.', 'cinderwell' ),
            ],
            'location_slug' => [
                'label'       => __( 'Location URL base', 'cinderwell' ),
                'type'        => 'slug',
                'default'     => 'locations',
                'description' => __( 'Example: /locations/downtown/', 'cinderwell' ),
            ],
        ];
        echo '<div class="card cw-settings-card"><h2>' . esc_html__( 'Locations', 'cinderwell' ) . '</h2>';
        echo '<p>' . esc_html__( 'Manage branches, offices, service areas, or campuses with their own contact details and hours.', 'cinderwell' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="cinderwell_save_locations">';
        wp_nonce_field( 'cinderwell_save_locations' );
        Admin_Fields::render_table( $fields, self::get_settings(), 'locations', 'cw-locations' );
        submit_button( __( 'Save Location Settings', 'cinderwell' ) );
        echo '</form></div>';
    }

    public function register_data_sources( $sources ) {
        foreach ( self::get_field_definitions() as $key => $field ) {
            $sources[ 'location_' . $key ] = [ 'label' => $field['label'], 'group' => 'location', 'context' => 'post' ];
        }
        $sources['location_address'] = [ 'label' => __( 'Formatted address', 'cinderwell' ), 'group' => 'location', 'context' => 'post' ];
        return $sources;
    }

    public function resolve_data_source( $fallback, $source, $context ) {
        $post_id = ! empty( $context['post_id'] ) ? absint( $context['post_id'] ) : get_the_ID();
        if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
            return $fallback;
        }
        $values = self::get_data_source_values( $post_id );
        return array_key_exists( $source, $values ) ? $values[ $source ] : $fallback;
    }

    public function add_editor_preview_values( $values ) {
        global $post;
        if ( $post instanceof \WP_Post && self::POST_TYPE === $post->post_type ) {
            $values = array_merge( $values, self::get_data_source_values( $post->ID ) );
        }
        return $values;
    }

    public static function get_data_source_values( $post_id ) {
        $values = [];
        foreach ( self::get_field_definitions() as $key => $field ) {
            $values[ 'location_' . $key ] = get_post_meta( $post_id, self::META_PREFIX . $key, true );
        }
        $values['location_address'] = implode( ', ', array_filter( [
            trim( ( $values['location_address_line_1'] ?? '' ) . ( ! empty( $values['location_address_line_2'] ) ? ', ' . $values['location_address_line_2'] : '' ) ),
            trim( ( $values['location_locality'] ?? '' ) . ( ! empty( $values['location_region'] ) ? ', ' . $values['location_region'] : '' ) . ( ! empty( $values['location_postal_code'] ) ? ' ' . $values['location_postal_code'] : '' ) ),
            $values['location_country'] ?? '',
        ] ) );
        return $values;
    }

    public static function get_health() {
        $counts = wp_count_posts( self::POST_TYPE );
        $count  = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
        return $count
            ? [ 'status' => 'good', 'message' => sprintf( _n( '%d published location.', '%d published locations.', $count, 'cinderwell' ), $count ) ]
            : [ 'status' => 'warning', 'message' => __( 'No published locations yet.', 'cinderwell' ) ];
    }

    public function allow_location_loop( $allowed, $post_type, $attributes ) {
        return $post_type && self::POST_TYPE === $post_type->name && 'locations' === ( $attributes['variation'] ?? '' ) ? true : $allowed;
    }

    public function filter_loop_link_behavior( $behavior, $post_type, $attributes ) {
        if ( self::POST_TYPE === $post_type && 'locations' === ( $attributes['variation'] ?? '' ) && ! self::get_settings()['public_locations'] ) {
            return 'none';
        }
        return $behavior;
    }

    private function get_meta_sanitizer( $type ) {
        if ( 'url' === $type ) return 'esc_url_raw';
        if ( 'email' === $type ) return 'sanitize_email';
        if ( 'textarea' === $type ) return 'sanitize_textarea_field';
        return 'sanitize_text_field';
    }
}
