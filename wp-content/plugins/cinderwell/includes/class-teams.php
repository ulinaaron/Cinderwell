<?php
/**
 * Opt-in Teams/People module.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Teams {

    const OPTION    = 'cinderwell_teams_settings';
    const POST_TYPE = 'cw_person';
    const TAXONOMY  = 'cw_people_category';

    private static function get_contact_fields() {
        return [
            'email'     => __( 'Email', 'cinderwell' ),
            'phone'     => __( 'Phone', 'cinderwell' ),
            'website'   => __( 'Website', 'cinderwell' ),
            'linkedin'  => __( 'LinkedIn', 'cinderwell' ),
            'facebook'  => __( 'Facebook', 'cinderwell' ),
            'instagram' => __( 'Instagram', 'cinderwell' ),
            'x'         => __( 'X / Twitter', 'cinderwell' ),
        ];
    }

    private static function get_person_fields() {
        $fields = [
            'first_name' => [ 'label' => __( 'First name', 'cinderwell' ) ],
            'last_name'  => [ 'label' => __( 'Last name', 'cinderwell' ) ],
            'position'   => [ 'label' => __( 'Position', 'cinderwell' ) ],
        ];
        foreach ( self::get_contact_fields() as $key => $label ) {
            $fields[ $key ] = [
                'label' => $label,
                'type'  => 'email' === $key ? 'email' : ( 'phone' === $key ? 'tel' : 'url' ),
            ];
        }
        return $fields;
    }

    public function __construct() {
        add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );
        add_action( 'init', [ $this, 'register_content_types' ] );
		add_action( 'cinderwell_register_fields', [ $this, 'register_content_fields' ] );
        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 6 );
        add_action( 'admin_post_cinderwell_save_teams', [ $this, 'save_settings' ] );
        add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'add_meta_box' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_person' ], 10, 2 );
        add_filter( 'enter_title_here', [ $this, 'title_placeholder' ], 10, 2 );
        add_filter( 'the_content', [ $this, 'append_profile_details' ] );
        add_filter( 'render_block_core/post-date', [ $this, 'hide_profile_post_date' ], 10, 2 );
        add_filter( 'rest_pre_dispatch', [ $this, 'protect_private_rest_profiles' ], 10, 3 );
        add_filter( 'cinderwell_loop_link_behavior', [ $this, 'filter_loop_link_behavior' ], 10, 3 );
        add_action( 'cinderwell_loop_item_after_title', [ $this, 'render_loop_position' ], 10, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
    }

    public function register_documentation( $registry ) {
        $registry->register_directory( 'cinderwell-teams', CINDERWELL_DIR . 'help/modules/teams' );
    }

    public static function get_defaults() {
        return [
            'terminology'     => 'people',
            'public_profiles' => false,
            'profile_slug'    => 'people',
            'fields'          => [ 'position' ],
        ];
    }

    public static function get_settings() {
        $settings = get_option( self::OPTION, [] );
        $settings = is_array( $settings ) ? $settings : [];
        $settings = wp_parse_args( $settings, self::get_defaults() );

        $settings['terminology']     = in_array( $settings['terminology'], [ 'people', 'team' ], true ) ? $settings['terminology'] : 'people';
        $settings['public_profiles'] = ! empty( $settings['public_profiles'] );
        $settings['profile_slug']    = sanitize_title( $settings['profile_slug'] ) ?: 'people';
        $settings['fields']          = is_array( $settings['fields'] ) ? array_values( array_intersect( array_merge( [ 'position' ], array_keys( self::get_contact_fields() ) ), $settings['fields'] ) ) : [ 'position' ];

        return $settings;
    }

    public static function get_editor_settings() {
        $settings = self::get_settings();
        return [
            'enabled'        => true,
            'pluralLabel'    => 'people' === $settings['terminology'] ? __( 'People', 'cinderwell' ) : __( 'Team Members', 'cinderwell' ),
            'profilesPublic' => $settings['public_profiles'],
            'postType'       => self::POST_TYPE,
            'taxonomy'       => self::TAXONOMY,
        ];
    }

    public static function get_health() {
        $counts = wp_count_posts( self::POST_TYPE );
        $count  = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
        return $count
            ? [ 'status' => 'good', 'message' => sprintf( _n( '%d published person.', '%d published people.', $count, 'cinderwell' ), $count ) ]
            : [ 'status' => 'warning', 'message' => __( 'No published people yet.', 'cinderwell' ) ];
    }

	/** Expose the existing people model through the shared field registry. */
	public function register_content_fields( $registry ) {
		$settings = self::get_settings();
		$allowed  = array_merge( [ 'first_name', 'last_name', 'position' ], $settings['fields'] );
		$fields   = array_intersect_key( self::get_person_fields(), array_flip( $allowed ) );
		foreach ( $fields as $key => &$field ) {
			$field['storage_key'] = '_cw_person_' . $key;
		}
		unset( $field );
		$registry->register_group( 'cinderwell/person_details', [
			'label'           => __( 'Person Details', 'cinderwell' ),
			'object_type'     => 'post',
			'object_subtypes' => [ self::POST_TYPE ],
			'fields'          => $fields,
			'ui'              => false,
		] );
	}

    public function enqueue_styles() {
        if ( ! is_singular( self::POST_TYPE ) ) {
            return;
        }

        wp_enqueue_style(
            'cinderwell-teams',
            CINDERWELL_BUILD_URL . 'modules/teams/frontend.css',
            [ 'cinderwell-base' ],
            CINDERWELL_VERSION
        );
    }

    public function add_settings_tab( $tabs ) {
        $tab = [
            'teams' => [
                'label'    => __( 'Teams', 'cinderwell' ),
                'group'    => 'content',
                'callback' => [ $this, 'render_settings' ],
            ],
        ];
        $keys     = array_keys( $tabs );
        $position = array_search( 'addons', $keys, true );
        $position = false === $position ? 1 : $position + 1;

        return array_slice( $tabs, 0, $position, true ) + $tab + array_slice( $tabs, $position, null, true );
    }

    public function register_content_types() {
        $settings    = self::get_settings();
        $people_mode = 'people' === $settings['terminology'];
        $singular    = $people_mode ? __( 'Person', 'cinderwell' ) : __( 'Team Member', 'cinderwell' );
        $plural      = $people_mode ? __( 'People', 'cinderwell' ) : __( 'Team Members', 'cinderwell' );

        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'                     => $plural,
                'singular_name'            => $singular,
                'add_new'                  => __( 'Add New', 'cinderwell' ),
                'add_new_item'             => sprintf( __( 'Add New %s', 'cinderwell' ), $singular ),
                'edit_item'                => sprintf( __( 'Edit %s', 'cinderwell' ), $singular ),
                'new_item'                 => sprintf( __( 'New %s', 'cinderwell' ), $singular ),
                'view_item'                => sprintf( __( 'View %s', 'cinderwell' ), $singular ),
                'search_items'             => sprintf( __( 'Search %s', 'cinderwell' ), $plural ),
                'not_found'                => sprintf( __( 'No %s found.', 'cinderwell' ), strtolower( $plural ) ),
                'featured_image'           => __( 'Profile photo', 'cinderwell' ),
                'set_featured_image'       => __( 'Set profile photo', 'cinderwell' ),
                'remove_featured_image'    => __( 'Remove profile photo', 'cinderwell' ),
                'use_featured_image'       => __( 'Use as profile photo', 'cinderwell' ),
                'item_published'           => sprintf( __( '%s published.', 'cinderwell' ), $singular ),
                'item_updated'             => sprintf( __( '%s updated.', 'cinderwell' ), $singular ),
                'item_reverted_to_draft'   => sprintf( __( '%s reverted to draft.', 'cinderwell' ), $singular ),
                'item_scheduled'           => sprintf( __( '%s scheduled.', 'cinderwell' ), $singular ),
            ],
            'public'              => true,
            'publicly_queryable'  => $settings['public_profiles'],
            'exclude_from_search' => ! $settings['public_profiles'],
            'show_in_rest'        => true,
            'show_in_nav_menus'   => $settings['public_profiles'],
            'has_archive'         => false,
            'rewrite'             => $settings['public_profiles'] ? [ 'slug' => $settings['profile_slug'], 'with_front' => false ] : false,
            'query_var'           => $settings['public_profiles'],
            'menu_icon'           => 'dashicons-groups',
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ],
            'taxonomies'          => [ self::TAXONOMY ],
        ] );

        register_taxonomy( self::TAXONOMY, [ self::POST_TYPE ], [
            'labels' => [
                'name'          => __( 'People Categories', 'cinderwell' ),
                'singular_name' => __( 'People Category', 'cinderwell' ),
                'search_items'  => __( 'Search People Categories', 'cinderwell' ),
                'all_items'     => __( 'All People Categories', 'cinderwell' ),
                'edit_item'     => __( 'Edit People Category', 'cinderwell' ),
                'update_item'   => __( 'Update People Category', 'cinderwell' ),
                'add_new_item'  => __( 'Add New People Category', 'cinderwell' ),
                'new_item_name' => __( 'New People Category Name', 'cinderwell' ),
                'menu_name'     => __( 'Categories', 'cinderwell' ),
            ],
            'public'             => false,
            'publicly_queryable' => false,
            'hierarchical'       => true,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_rest'       => true,
            'rewrite'            => false,
        ] );

        foreach ( array_merge( [ 'first_name', 'last_name', 'position' ], array_keys( self::get_contact_fields() ) ) as $key ) {
            register_post_meta( self::POST_TYPE, '_cw_person_' . $key, [
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => in_array( $key, [ 'website', 'linkedin', 'facebook', 'instagram', 'x' ], true ) ? 'esc_url_raw' : ( 'email' === $key ? 'sanitize_email' : 'sanitize_text_field' ),
                'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
                    return current_user_can( 'edit_post', $post_id );
                },
            ] );
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'cinderwell-person-details',
            __( 'Person Details', 'cinderwell' ),
            [ $this, 'render_meta_box' ],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        $settings = self::get_settings();
        wp_nonce_field( 'cinderwell_save_person', 'cinderwell_person_nonce' );
        echo '<p>' . esc_html__( 'Use the title for the public display name, the featured image for the profile photo, and the editor for the biography.', 'cinderwell' ) . '</p>';

        $allowed = array_merge( [ 'first_name', 'last_name' ], $settings['fields'] );
        $fields  = array_intersect_key( self::get_person_fields(), array_flip( $allowed ) );
        $values  = [];
        foreach ( $fields as $key => $field ) {
            $values[ $key ] = get_post_meta( $post->ID, '_cw_person_' . $key, true );
        }
        Admin_Fields::render_table( $fields, $values, 'cinderwell_person', 'cw-person' );
    }

    public function save_person( $post_id, $post ) {
        if ( ! isset( $_POST['cinderwell_person_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cinderwell_person_nonce'] ) ), 'cinderwell_save_person' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $submitted = isset( $_POST['cinderwell_person'] ) ? (array) wp_unslash( $_POST['cinderwell_person'] ) : [];
        $settings  = self::get_settings();
        $allowed   = array_merge( [ 'first_name', 'last_name' ], $settings['fields'] );

        $fields    = array_intersect_key( self::get_person_fields(), array_flip( $allowed ) );
        $sanitized = Admin_Fields::sanitize_values( $fields, $submitted );

        foreach ( $allowed as $key ) {
            $value = $sanitized[ $key ] ?? '';

            if ( '' === $value ) {
                delete_post_meta( $post_id, '_cw_person_' . $key );
            } else {
                update_post_meta( $post_id, '_cw_person_' . $key, $value );
            }
        }

        if ( '' === trim( $post->post_title ) ) {
            $name = trim( sanitize_text_field( ( $submitted['first_name'] ?? '' ) . ' ' . ( $submitted['last_name'] ?? '' ) ) );
            if ( $name ) {
                remove_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_person' ], 10 );
                wp_update_post( [ 'ID' => $post_id, 'post_title' => $name ] );
                add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_person' ], 10, 2 );
            }
        }
    }

    public function title_placeholder( $title, $post ) {
        return self::POST_TYPE === $post->post_type ? __( 'Display name', 'cinderwell' ) : $title;
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage Teams settings.', 'cinderwell' ) );
        }
        check_admin_referer( 'cinderwell_save_teams' );

        $submitted = isset( $_POST['teams'] ) ? (array) wp_unslash( $_POST['teams'] ) : [];
        $settings  = Admin_Fields::sanitize_values( $this->get_settings_fields(), $submitted );
        if ( ! $settings['profile_slug'] ) {
            $settings['profile_slug'] = 'people' === $settings['terminology'] ? 'people' : 'team';
        }

        update_option( self::OPTION, $settings );
        update_option( 'cinderwell_flush_rewrite_rules', 1, false );

        wp_safe_redirect( add_query_arg( [
            'page'    => 'cinderwell',
            'tab'     => 'teams',
            'updated' => '1',
        ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_settings() {
        $settings = self::get_settings();
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Teams settings saved.', 'cinderwell' ) . '</p></div>';
        }
        ?>
        <div class="card cw-settings-card">
            <h2><?php esc_html_e( 'Teams', 'cinderwell' ); ?></h2>
            <p><?php esc_html_e( 'Create a structured directory for people. Choose here whether individual profile URLs exist; each People Loop controls how it presents profile details.', 'cinderwell' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="cinderwell_save_teams">
                <?php wp_nonce_field( 'cinderwell_save_teams' ); ?>
                <?php Admin_Fields::render_table( $this->get_settings_fields(), $settings, 'teams', 'cw-teams' ); ?>
                <?php submit_button( __( 'Save Teams Settings', 'cinderwell' ) ); ?>
            </form>
        </div>
        <?php
    }

    private function get_settings_fields() {
        return [
            'terminology' => [
                'label'   => __( 'Terminology', 'cinderwell' ),
                'type'    => 'select',
                'default' => 'people',
                'options' => [
                    'people' => __( 'People / Person', 'cinderwell' ),
                    'team'   => __( 'Team Members / Team Member', 'cinderwell' ),
                ],
            ],
            'public_profiles' => [
                'label'          => __( 'Individual profiles', 'cinderwell' ),
                'type'           => 'checkbox',
                'checkbox_label' => __( 'Enable public profile pages', 'cinderwell' ),
                'description'    => __( 'Controls only direct profile URLs. People Loops can still show biographies in a modal when pages are disabled.', 'cinderwell' ),
            ],
            'profile_slug' => [
                'label'       => __( 'Profile URL base', 'cinderwell' ),
                'type'        => 'slug',
                'default'     => 'people',
                'description' => __( 'Reserved while profile pages are disabled and applied when they are enabled.', 'cinderwell' ),
            ],
            'fields' => [
                'label'       => __( 'Profile fields', 'cinderwell' ),
                'type'        => 'checkboxes',
                'default'     => [ 'position' ],
                'options'     => [ 'position' => __( 'Position', 'cinderwell' ) ] + self::get_contact_fields(),
                'description' => __( 'First name, last name, profile photo, and biography are always available.', 'cinderwell' ),
            ],
        ];
    }

    public function render_loop_position( $post_id, $attributes ) {
        if ( self::POST_TYPE !== get_post_type( $post_id ) || empty( $attributes['showTeamPosition'] ) ) {
            return;
        }
        $position = get_post_meta( $post_id, '_cw_person_position', true );
        if ( $position ) {
            echo '<p class="cinderwell-loop__position">' . esc_html( $position ) . '</p>';
        }
    }

    public function filter_loop_link_behavior( $behavior, $post_type, $attributes ) {
        if ( self::POST_TYPE !== $post_type || empty( $attributes['variation'] ) || 'people' !== $attributes['variation'] ) {
            return $behavior;
        }

        if ( 'page' === $behavior && ! self::get_settings()['public_profiles'] ) {
            return 'none';
        }

        return $behavior;
    }

    public function protect_private_rest_profiles( $result, $server, $request ) {
        if ( self::get_settings()['public_profiles'] || current_user_can( 'edit_posts' ) ) {
            return $result;
        }

        $route = $request->get_route();
        if ( 0 !== strpos( $route, '/wp/v2/' . self::POST_TYPE ) ) {
            return $result;
        }

        return new \WP_Error(
            'cinderwell_private_profiles',
            __( 'People profiles are not publicly available.', 'cinderwell' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    public function append_profile_details( $content ) {
        if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id  = get_the_ID();
        $settings = self::get_settings();
        $position = get_post_meta( $post_id, '_cw_person_position', true );
        $details  = '';

        if ( $position && in_array( 'position', $settings['fields'], true ) ) {
            $details .= '<p class="cinderwell-person-profile__position">' . esc_html( $position ) . '</p>';
        }

        $links = [];
        foreach ( self::get_contact_fields() as $key => $label ) {
            if ( ! in_array( $key, $settings['fields'], true ) ) {
                continue;
            }
            $value = get_post_meta( $post_id, '_cw_person_' . $key, true );
            if ( ! $value ) {
                continue;
            }

            if ( 'email' === $key ) {
                $email   = antispambot( $value );
                $links[] = '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
            } elseif ( 'phone' === $key ) {
                $links[] = '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $value ) ) . '">' . esc_html( $value ) . '</a>';
            } else {
                $links[] = '<a href="' . esc_url( $value ) . '">' . esc_html( $label ) . '</a>';
            }
        }

        if ( $links ) {
            $details .= '<div class="cinderwell-person-profile__contact" aria-label="' . esc_attr__( 'Contact information', 'cinderwell' ) . '">' . implode( '<span aria-hidden="true"> · </span>', $links ) . '</div>';
        }

        return $details ? $details . $content : $content;
    }

    public function hide_profile_post_date( $block_content, $block ) {
        return is_singular( self::POST_TYPE ) ? '' : $block_content;
    }
}
