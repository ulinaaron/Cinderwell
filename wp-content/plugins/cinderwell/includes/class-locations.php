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
    const TAXONOMY    = 'cw_location_category';
    const META_PREFIX = '_cw_location_';

    public function __construct() {
        add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );
        add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'cinderwell_register_fields', [ $this, 'register_content_fields' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_location_editor_assets' ] );
        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 9 );
        add_action( 'admin_post_cinderwell_save_locations', [ $this, 'save_settings' ] );
        add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'add_meta_box' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_location' ] );
        add_filter( 'cinderwell_data_sources', [ $this, 'register_data_sources' ] );
        add_filter( 'cinderwell_resolve_data_source', [ $this, 'resolve_data_source' ], 10, 3 );
        add_filter( 'cinderwell_editor_preview_values', [ $this, 'add_editor_preview_values' ] );
        add_filter( 'cinderwell_loop_post_type_allowed', [ $this, 'allow_location_loop' ], 10, 3 );
        add_filter( 'cinderwell_loop_link_behavior', [ $this, 'filter_loop_link_behavior' ], 10, 3 );
        add_filter( 'cinderwell_map_locations', [ $this, 'provide_map_locations' ], 10, 2 );

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

    public function register_documentation( $registry ) {
        $registry->register_directory( 'cinderwell-locations', CINDERWELL_DIR . 'help/modules/locations' );
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
        $terms    = get_terms( [
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
        ] );
        return [
            'enabled'         => true,
            'locationsPublic' => $settings['public_locations'],
            'postType'        => self::POST_TYPE,
            'taxonomy'        => self::TAXONOMY,
            'mapEndpoint'     => '/cinderwell/v1/locations/map',
            'categories'      => is_wp_error( $terms ) ? [] : array_map( static function ( $term ) {
                return [ 'value' => (int) $term->term_id, 'label' => $term->name ];
            }, $terms ),
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
            'latitude' => [
                'label'             => __( 'Latitude', 'cinderwell' ),
                'description'       => __( 'Used to place this location on Cinderwell maps.', 'cinderwell' ),
                'sanitize_callback' => [ self::class, 'sanitize_latitude' ],
            ],
            'longitude' => [
                'label'             => __( 'Longitude', 'cinderwell' ),
                'description'       => __( 'Used to place this location on Cinderwell maps.', 'cinderwell' ),
                'sanitize_callback' => [ self::class, 'sanitize_longitude' ],
            ],
        ] ) );
    }

	/** Expose existing location meta through the shared field registry. */
	public function register_content_fields( $registry ) {
		$fields = self::get_field_definitions();
		foreach ( $fields as $key => &$field ) {
			$field['storage_key'] = self::META_PREFIX . $key;
		}
		unset( $field );
		$registry->register_group( 'cinderwell/location_details', [
			'label'           => __( 'Location Details', 'cinderwell' ),
			'object_type'     => 'post',
			'object_subtypes' => [ self::POST_TYPE ],
			'fields'          => $fields,
			'ui'              => false,
		] );
	}

    public function register_post_type() {
        $settings = self::get_settings();
        $post_type_args = [
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
        ];

        register_post_type( self::POST_TYPE, apply_filters( 'cinderwell_location_post_type_args', $post_type_args, $settings ) );

        $taxonomy_args = [
            'labels' => [
                'name'          => __( 'Location Categories', 'cinderwell' ),
                'singular_name' => __( 'Location Category', 'cinderwell' ),
                'search_items'  => __( 'Search location categories', 'cinderwell' ),
                'all_items'     => __( 'All location categories', 'cinderwell' ),
                'edit_item'     => __( 'Edit location category', 'cinderwell' ),
                'add_new_item'  => __( 'Add location category', 'cinderwell' ),
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'hierarchical'      => true,
            'rewrite'           => false,
            'query_var'         => false,
        ];

        register_taxonomy( self::TAXONOMY, [ self::POST_TYPE ], apply_filters( 'cinderwell_location_taxonomy_args', $taxonomy_args, $settings ) );

        foreach ( self::get_field_definitions() as $key => $field ) {
            register_post_meta( self::POST_TYPE, self::META_PREFIX . $key, [
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => is_callable( $field['sanitize_callback'] ?? null ) ? $field['sanitize_callback'] : $this->get_meta_sanitizer( $field['type'] ),
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
        echo '<div class="cw-location-pin-tool" data-cw-location-pin-tool>';
        echo '<button type="button" class="button button-secondary" data-cw-location-geocode>' . esc_html__( 'Place pin from address', 'cinderwell' ) . '</button>';
        echo '<p class="description">' . esc_html__( 'Uses the structured address above to fill the map coordinates. Confirm the result before updating the location.', 'cinderwell' ) . '</p>';
        echo '<p class="cw-location-pin-tool__status" data-cw-location-geocode-status role="status" aria-live="polite"></p>';
        echo '</div>';
    }

    public function enqueue_location_editor_assets( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) || self::POST_TYPE !== get_current_screen()->post_type ) {
            return;
        }
        $asset_path = CINDERWELL_BUILD_DIR . 'admin/location-map.asset.php';
        if ( ! file_exists( $asset_path ) ) {
            return;
        }
        $asset = include $asset_path;
        wp_enqueue_script( 'cinderwell-location-map-admin', CINDERWELL_BUILD_URL . 'admin/location-map.js', $asset['dependencies'], $asset['version'], true );
    }

    public function register_rest_routes() {
        register_rest_route( 'cinderwell/v1', '/locations/map', [
            'methods'             => \WP_REST_Server::READABLE,
            'permission_callback' => static function () {
                return current_user_can( 'edit_posts' );
            },
            'args'                => [
                'term' => [
                    'type'              => 'integer',
                    'default'           => 0,
                    'sanitize_callback' => 'absint',
                ],
            ],
            'callback'            => function ( \WP_REST_Request $request ) {
                $attributes = [
                    'source'       => 'locations',
                    'locationTerm' => (int) $request->get_param( 'term' ),
                ];
                return rest_ensure_response( Map::resolve_records( [], $attributes ) );
            },
        ] );
    }

    public function provide_map_locations( $locations, $attributes ) {
        if ( 'locations' !== ( $attributes['source'] ?? 'manual' ) ) {
            return $locations;
        }
        return self::get_map_locations( absint( $attributes['locationTerm'] ?? 0 ) );
    }

    public static function get_map_locations( $term_id = 0 ) {
        $query_args = [
            'post_type'              => self::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => 100,
            'orderby'                => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
            'no_found_rows'          => true,
            'update_post_term_cache' => true,
        ];
        if ( $term_id ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => self::TAXONOMY,
                'field'    => 'term_id',
                'terms'    => [ $term_id ],
            ] ];
        }

        $query_args = (array) apply_filters( 'cinderwell_location_map_query_args', $query_args, $term_id );

        $settings = self::get_settings();
        $records  = [];
        foreach ( get_posts( $query_args ) as $location ) {
            $values    = self::get_data_source_values( $location->ID );
            $latitude  = $values['location_latitude'] ?? '';
            $longitude = $values['location_longitude'] ?? '';
            if ( ! is_numeric( $latitude ) || ! is_numeric( $longitude ) ) {
                continue;
            }
            $address    = $values['location_address'] ?? '';
            $directions = $values['location_directions_url'] ?? '';
            $terms      = wp_get_post_terms( $location->ID, self::TAXONOMY );
            $record = [
                'id'            => 'location-' . $location->ID,
                'postId'        => (int) $location->ID,
                'name'          => get_the_title( $location ),
                'address'       => $address,
                'phone'         => $values['location_phone'] ?? '',
                'latitude'      => (float) $latitude,
                'longitude'     => (float) $longitude,
                'directionsUrl' => $directions ?: ( $address ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $address ) : '' ),
                'url'           => $settings['public_locations'] ? get_permalink( $location ) : '',
                'termIds'       => is_wp_error( $terms ) ? [] : array_map( static function ( $term ) { return (int) $term->term_id; }, $terms ),
                'terms'         => is_wp_error( $terms ) ? [] : array_map( static function ( $term ) {
                    return [ 'id' => (int) $term->term_id, 'name' => $term->name ];
                }, $terms ),
            ];

            $record = apply_filters( 'cinderwell_location_map_record', $record, $location, $term_id );
            if ( is_array( $record ) ) {
                $records[] = $record;
            }
        }
        return array_values( (array) apply_filters( 'cinderwell_location_map_records', $records, $term_id, $query_args ) );
    }

    public static function sanitize_latitude( $value ) {
        return self::sanitize_coordinate( $value, -90, 90 );
    }

    public static function sanitize_longitude( $value ) {
        return self::sanitize_coordinate( $value, -180, 180 );
    }

    private static function sanitize_coordinate( $value, $minimum, $maximum ) {
        if ( '' === trim( (string) $value ) || ! is_numeric( $value ) ) {
            return '';
        }
        $number = (float) $value;
        return $number >= $minimum && $number <= $maximum ? (string) $number : '';
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
