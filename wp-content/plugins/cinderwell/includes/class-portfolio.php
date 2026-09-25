<?php
/**
 * Opt-in Portfolio module.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Portfolio {

    const OPTION     = 'cinderwell_portfolio_settings';
    const POST_TYPE  = 'cw_project';
    const TAXONOMY   = 'cw_portfolio_category';
    const META_PREFIX = '_cw_project_';

    public function __construct() {
        add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );
        add_action( 'init', [ $this, 'register_content_types' ] );
		add_action( 'cinderwell_register_fields', [ $this, 'register_content_fields' ] );
        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 7 );
        add_action( 'admin_post_cinderwell_save_portfolio', [ $this, 'save_settings' ] );
        add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'add_meta_box' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_item' ], 10, 2 );
        add_filter( 'enter_title_here', [ $this, 'title_placeholder' ], 10, 2 );
        add_filter( 'the_content', [ $this, 'prepend_item_details' ] );
        add_filter( 'render_block_core/post-date', [ $this, 'hide_post_date' ], 10, 2 );
        add_filter( 'cinderwell_loop_link_behavior', [ $this, 'filter_loop_link_behavior' ], 10, 3 );
        add_action( 'cinderwell_loop_item_after_title', [ $this, 'render_loop_details' ], 10, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

        $template_dir = CINDERWELL_DIR . 'templates/portfolio/';
        new Module_Templates( 'cinderwell', [
            'single-' . self::POST_TYPE => [
                'title'       => __( 'Portfolio Item', 'cinderwell' ),
                'description' => __( 'Default single portfolio item template.', 'cinderwell' ),
                'path'        => $template_dir . 'single-cw_project.html',
                'post_types'  => [ self::POST_TYPE ],
            ],
            'archive-' . self::POST_TYPE => [
                'title'       => __( 'Portfolio Archive', 'cinderwell' ),
                'description' => __( 'Default portfolio archive template.', 'cinderwell' ),
                'path'        => $template_dir . 'archive-cw_project.html',
            ],
            'taxonomy-' . self::TAXONOMY => [
                'title'       => __( 'Portfolio Category', 'cinderwell' ),
                'description' => __( 'Default portfolio category template.', 'cinderwell' ),
                'path'        => $template_dir . 'taxonomy-cw_portfolio_category.html',
            ],
        ] );
    }

    public function register_documentation( $registry ) {
        $registry->register_directory( 'cinderwell-portfolio', CINDERWELL_DIR . 'help/modules/portfolio' );
    }

    public static function get_defaults() {
        return [
            'public_items' => true,
            'item_slug'    => 'work',
            'archive_slug' => 'portfolio',
            'fields'       => [ 'client', 'services', 'project_date', 'project_url' ],
        ];
    }

    public static function get_field_definitions() {
        $fields = [
            'client' => [
                'label'        => __( 'Client', 'cinderwell' ),
                'type'         => 'text',
                'show_in_loop' => true,
            ],
            'services' => [
                'label'       => __( 'Services', 'cinderwell' ),
                'type'        => 'text',
                'description' => __( 'A short, comma-separated summary works well in loops.', 'cinderwell' ),
                'show_in_loop' => true,
            ],
            'project_date' => [
                'label'        => __( 'Project date', 'cinderwell' ),
                'type'         => 'date',
                'show_in_loop' => true,
            ],
            'project_url' => [
                'label' => __( 'Project URL', 'cinderwell' ),
                'type'  => 'url',
            ],
            'location' => [
                'label' => __( 'Location', 'cinderwell' ),
                'type'  => 'text',
            ],
        ];

        /**
         * Filters portfolio field definitions. Child themes may add, remove, or
         * replace definitions using the Admin_Fields schema.
         */
        return Admin_Fields::normalize_fields( apply_filters( 'cinderwell_portfolio_fields', $fields ) );
    }

    public static function get_settings() {
        $settings = get_option( self::OPTION, [] );
        $settings = wp_parse_args( is_array( $settings ) ? $settings : [], self::get_defaults() );
        $allowed  = array_keys( self::get_field_definitions() );

        $settings['public_items'] = ! empty( $settings['public_items'] );
        $settings['item_slug']    = sanitize_title( $settings['item_slug'] ) ?: 'work';
        $settings['archive_slug'] = sanitize_title( $settings['archive_slug'] ) ?: 'portfolio';
        $settings['fields']       = is_array( $settings['fields'] ) ? array_values( array_intersect( $allowed, $settings['fields'] ) ) : [];
        return $settings;
    }

    public static function get_editor_settings() {
        $settings = self::get_settings();
        $fields   = [];
        foreach ( self::get_field_definitions() as $key => $field ) {
            if ( in_array( $key, $settings['fields'], true ) && ! empty( $field['show_in_loop'] ) ) {
                $fields[ self::META_PREFIX . $key ] = $field['label'];
            }
        }
        return [
            'enabled'     => true,
            'itemsPublic' => $settings['public_items'],
            'postType'    => self::POST_TYPE,
            'taxonomy'    => self::TAXONOMY,
            'loopFields'   => $fields,
        ];
    }

    public static function get_health() {
        $counts = wp_count_posts( self::POST_TYPE );
        $count  = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
        return $count
            ? [ 'status' => 'good', 'message' => sprintf( _n( '%d published project.', '%d published projects.', $count, 'cinderwell' ), $count ) ]
            : [ 'status' => 'warning', 'message' => __( 'No published projects yet.', 'cinderwell' ) ];
    }

	/** Expose existing project meta through the shared field registry. */
	public function register_content_fields( $registry ) {
		$settings = self::get_settings();
		$fields   = array_intersect_key( self::get_field_definitions(), array_flip( $settings['fields'] ) );
		foreach ( $fields as $key => &$field ) {
			$field['storage_key'] = self::META_PREFIX . $key;
		}
		unset( $field );
		$registry->register_group( 'cinderwell/project_details', [
			'label'           => __( 'Project Details', 'cinderwell' ),
			'object_type'     => 'post',
			'object_subtypes' => [ self::POST_TYPE ],
			'fields'          => $fields,
			'ui'              => false,
		] );
	}

    public function register_content_types() {
        $settings = self::get_settings();
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'               => __( 'Portfolio', 'cinderwell' ),
                'singular_name'      => __( 'Portfolio Item', 'cinderwell' ),
                'add_new_item'       => __( 'Add New Portfolio Item', 'cinderwell' ),
                'edit_item'          => __( 'Edit Portfolio Item', 'cinderwell' ),
                'new_item'           => __( 'New Portfolio Item', 'cinderwell' ),
                'view_item'          => __( 'View Portfolio Item', 'cinderwell' ),
                'search_items'       => __( 'Search Portfolio', 'cinderwell' ),
                'not_found'          => __( 'No portfolio items found.', 'cinderwell' ),
                'featured_image'     => __( 'Project image', 'cinderwell' ),
                'set_featured_image' => __( 'Set project image', 'cinderwell' ),
            ],
            'public'              => true,
            'publicly_queryable'  => $settings['public_items'],
            'exclude_from_search' => ! $settings['public_items'],
            'show_in_rest'        => true,
            'show_in_nav_menus'   => $settings['public_items'],
            'has_archive'         => $settings['public_items'] ? $settings['archive_slug'] : false,
            'rewrite'             => $settings['public_items'] ? [ 'slug' => $settings['item_slug'], 'with_front' => false ] : false,
            'query_var'           => $settings['public_items'],
            'menu_icon'           => 'dashicons-portfolio',
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ],
            'taxonomies'          => [ self::TAXONOMY ],
        ] );

        register_taxonomy( self::TAXONOMY, [ self::POST_TYPE ], [
            'labels' => [
                'name'          => __( 'Portfolio Categories', 'cinderwell' ),
                'singular_name' => __( 'Portfolio Category', 'cinderwell' ),
                'menu_name'     => __( 'Categories', 'cinderwell' ),
            ],
            'public'            => $settings['public_items'],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => $settings['public_items'] ? [ 'slug' => $settings['archive_slug'] . '/category', 'with_front' => false ] : false,
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

    public function add_settings_tab( $tabs ) {
        $tabs['portfolio'] = [
            'label'    => __( 'Portfolio', 'cinderwell' ),
            'group'    => 'content',
            'callback' => [ $this, 'render_settings' ],
        ];
        return $tabs;
    }

    public function add_meta_box() {
        add_meta_box( 'cinderwell-portfolio-details', __( 'Project Details', 'cinderwell' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'cinderwell_save_portfolio_item', 'cinderwell_portfolio_nonce' );
        echo '<p>' . esc_html__( 'Use the featured image as the project cover, the excerpt as the loop summary, and the editor for the case study.', 'cinderwell' ) . '</p>';
        $fields = $this->get_enabled_fields();
        $values = [];
        foreach ( $fields as $key => $field ) {
            $values[ $key ] = get_post_meta( $post->ID, self::META_PREFIX . $key, true );
        }
        Admin_Fields::render_table( $fields, $values, 'cinderwell_portfolio_item', 'cw-project' );
    }

    public function save_item( $post_id, $post ) {
        if ( ! isset( $_POST['cinderwell_portfolio_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cinderwell_portfolio_nonce'] ) ), 'cinderwell_save_portfolio_item' ) ) {
            return;
        }
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        $submitted = isset( $_POST['cinderwell_portfolio_item'] ) ? (array) wp_unslash( $_POST['cinderwell_portfolio_item'] ) : [];
        $values    = Admin_Fields::sanitize_values( $this->get_enabled_fields(), $submitted );
        foreach ( $this->get_enabled_fields() as $key => $field ) {
            if ( '' === ( $values[ $key ] ?? '' ) ) {
                delete_post_meta( $post_id, self::META_PREFIX . $key );
            } else {
                update_post_meta( $post_id, self::META_PREFIX . $key, $values[ $key ] );
            }
        }
    }

    public function title_placeholder( $title, $post ) {
        return self::POST_TYPE === $post->post_type ? __( 'Project name', 'cinderwell' ) : $title;
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage Portfolio settings.', 'cinderwell' ) );
        }
        check_admin_referer( 'cinderwell_save_portfolio' );
        $submitted = isset( $_POST['portfolio'] ) ? (array) wp_unslash( $_POST['portfolio'] ) : [];
        $settings  = Admin_Fields::sanitize_values( $this->get_settings_fields(), $submitted );
        $settings['item_slug']    = $settings['item_slug'] ?: 'work';
        $settings['archive_slug'] = $settings['archive_slug'] ?: 'portfolio';
        update_option( self::OPTION, $settings );
        update_option( 'cinderwell_flush_rewrite_rules', 1, false );
        wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'portfolio', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_settings() {
        $settings = self::get_settings();
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Portfolio settings saved.', 'cinderwell' ) . '</p></div>';
        }
        echo '<div class="card cw-settings-card"><h2>' . esc_html__( 'Portfolio', 'cinderwell' ) . '</h2>';
        echo '<p>' . esc_html__( 'Manage case studies or project work, then place a focused Portfolio loop anywhere.', 'cinderwell' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="cinderwell_save_portfolio">';
        wp_nonce_field( 'cinderwell_save_portfolio' );
        Admin_Fields::render_table( $this->get_settings_fields(), $settings, 'portfolio', 'cw-portfolio' );
        submit_button( __( 'Save Portfolio Settings', 'cinderwell' ) );
        echo '</form></div>';
    }

    private function get_settings_fields() {
        $field_options = [];
        foreach ( self::get_field_definitions() as $key => $field ) {
            $field_options[ $key ] = $field['label'];
        }
        return [
            'public_items' => [
                'label'          => __( 'Public portfolio', 'cinderwell' ),
                'type'           => 'checkbox',
                'default'        => true,
                'checkbox_label' => __( 'Enable individual items, archives, and category pages', 'cinderwell' ),
                'description'    => __( 'When disabled, items remain editable and available to loops without public URLs.', 'cinderwell' ),
            ],
            'item_slug' => [
                'label'       => __( 'Item URL base', 'cinderwell' ),
                'type'        => 'slug',
                'default'     => 'work',
                'description' => __( 'Example: /work/project-name/', 'cinderwell' ),
            ],
            'archive_slug' => [
                'label'       => __( 'Archive URL', 'cinderwell' ),
                'type'        => 'slug',
                'default'     => 'portfolio',
                'description' => __( 'Example: /portfolio/', 'cinderwell' ),
            ],
            'fields' => [
                'label'       => __( 'Project fields', 'cinderwell' ),
                'type'        => 'checkboxes',
                'default'     => self::get_defaults()['fields'],
                'options'     => $field_options,
                'description' => __( 'The title, cover image, excerpt, case-study content, and categories are always available.', 'cinderwell' ),
            ],
        ];
    }

    private function get_enabled_fields() {
        return array_intersect_key( self::get_field_definitions(), array_flip( self::get_settings()['fields'] ) );
    }

    private function get_meta_sanitizer( $type ) {
        if ( 'url' === $type ) {
            return 'esc_url_raw';
        }
        if ( 'email' === $type ) {
            return 'sanitize_email';
        }
        if ( 'textarea' === $type ) {
            return 'sanitize_textarea_field';
        }
        return 'sanitize_text_field';
    }

    public function prepend_item_details( $content ) {
        if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }
        $details = $this->get_details_html( get_the_ID(), false );
        return $details . $content;
    }

    public function render_loop_details( $post_id, $attributes ) {
        if ( self::POST_TYPE !== get_post_type( $post_id ) || empty( $attributes['showPortfolioDetails'] ) ) {
            return;
        }
        echo $this->get_details_html( $post_id, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function get_details_html( $post_id, $compact ) {
        $items = [];
        foreach ( $this->get_enabled_fields() as $key => $field ) {
            $value = get_post_meta( $post_id, self::META_PREFIX . $key, true );
            if ( ! $value || ( $compact && empty( $field['show_in_loop'] ) ) ) {
                continue;
            }
            if ( 'project_url' === $key ) {
                $value = '<a href="' . esc_url( $value ) . '">' . esc_html__( 'Visit project', 'cinderwell' ) . '</a>';
            } elseif ( 'date' === $field['type'] ) {
                $timestamp = strtotime( $value );
                $value     = $timestamp ? esc_html( wp_date( get_option( 'date_format' ), $timestamp ) ) : esc_html( $value );
            } else {
                $value = esc_html( $value );
            }
            $items[] = '<div class="cinderwell-portfolio-details__item"><dt>' . esc_html( $field['label'] ) . '</dt><dd>' . $value . '</dd></div>';
        }
        if ( ! $items ) {
            return '';
        }
        $class = 'cinderwell-portfolio-details' . ( $compact ? ' cinderwell-portfolio-details--compact' : '' );
        return '<dl class="' . esc_attr( $class ) . '">' . implode( '', $items ) . '</dl>';
    }

    public function filter_loop_link_behavior( $behavior, $post_type, $attributes ) {
        if ( self::POST_TYPE !== $post_type || 'portfolio' !== ( $attributes['variation'] ?? '' ) ) {
            return $behavior;
        }
        return self::get_settings()['public_items'] ? $behavior : 'none';
    }

    public function hide_post_date( $block_content, $block ) {
        return is_singular( self::POST_TYPE ) ? '' : $block_content;
    }

    public function enqueue_styles() {
        if ( ! is_singular( self::POST_TYPE ) && ! is_post_type_archive( self::POST_TYPE ) && ! is_tax( self::TAXONOMY ) ) {
            return;
        }
        wp_enqueue_style( 'cinderwell-portfolio', CINDERWELL_BUILD_URL . 'modules/portfolio/frontend.css', [ 'cinderwell-base' ], CINDERWELL_VERSION );
    }
}
