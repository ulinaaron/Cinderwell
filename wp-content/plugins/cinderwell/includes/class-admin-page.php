<?php
/**
 * Top-level Cinderwell admin page.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Admin_Page {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_menu', [ $this, 'order_submenu' ], 1000 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets( $hook_suffix ) {
        if ( 'toplevel_page_cinderwell' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'cinderwell-admin-settings',
            CINDERWELL_BUILD_URL . 'admin/settings.css',
            [],
            CINDERWELL_VERSION
        );
        wp_enqueue_media();
        $asset_path = CINDERWELL_BUILD_DIR . 'admin/settings.asset.php';
        $asset      = file_exists( $asset_path ) ? include $asset_path : [ 'dependencies' => [], 'version' => CINDERWELL_VERSION ];
        wp_enqueue_script(
            'cinderwell-admin-settings',
            CINDERWELL_BUILD_URL . 'admin/settings.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
    }

    public function add_menu() {
        /**
         * Controls who can see the shared Cinderwell admin menu container.
         *
         * Add-ons may lower this capability so their own screens can be shown
         * to editors. The settings screen itself always requires
         * `manage_options`.
         *
         * @param string $capability Menu capability.
         */
        $menu_capability = apply_filters( 'cinderwell_admin_menu_capability', 'manage_options' );

        add_menu_page(
            __( 'Cinderwell Settings', 'cinderwell' ),
            __( 'Cinderwell', 'cinderwell' ),
            $menu_capability,
            'cinderwell',
            [ $this, 'render_page' ],
            'dashicons-layout',
            58
        );

		// Register the settings destination explicitly. WordPress otherwise creates
		// it implicitly using the parent capability, which add-ons may lower so
		// editor-facing screens such as Help remain available.
		add_submenu_page(
			'cinderwell',
			__( 'Cinderwell Settings', 'cinderwell' ),
			__( 'Settings', 'cinderwell' ),
			'manage_options',
			'cinderwell',
			[ $this, 'render_page' ]
		);
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__( 'You do not have permission to manage Cinderwell settings.', 'cinderwell' ),
                esc_html__( 'Access denied', 'cinderwell' ),
                [ 'response' => 403 ]
            );
        }

        $requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
        $requested_tab = 'permissions' === $requested_tab ? 'editor-access' : $requested_tab;
        $tabs          = apply_filters( 'cinderwell_settings_tabs', [
            'general' => [
                'label'    => __( 'General', 'cinderwell' ),
                'group'    => 'overview',
                'callback' => [ $this, 'render_general_tab' ],
            ],
            'editor-access' => [
                'label'    => __( 'Editor Access', 'cinderwell' ),
                'group'    => 'design',
                'callback' => [ $this, 'render_editor_access_tab' ],
            ],
            'tokens' => [
                'label'    => __( 'Design Tokens', 'cinderwell' ),
                'group'    => 'design',
                'callback' => [ $this, 'render_tokens_tab' ],
            ],
            'templates' => [
                'label'    => __( 'Template Updates', 'cinderwell' ),
                'group'    => 'maintenance',
                'callback' => [ $this, 'render_templates_tab' ],
            ],
			'advanced' => [
				'label'    => __( 'Advanced', 'cinderwell' ),
				'group'    => 'maintenance',
				'callback' => [ $this, 'render_advanced_tab' ],
			],
        ] );
        $active_tab    = isset( $tabs[ $requested_tab ] ) ? $requested_tab : 'general';
        $groups        = apply_filters( 'cinderwell_settings_groups', [
            'overview' => __( 'Overview', 'cinderwell' ),
            'content' => __( 'Content', 'cinderwell' ),
            'design' => __( 'Design & Editing', 'cinderwell' ),
            'extensions' => __( 'Extensions', 'cinderwell' ),
            'maintenance' => __( 'Maintenance', 'cinderwell' ),
        ] );

        foreach ( $tabs as $tab_key => $tab ) {
            if ( empty( $tab['group'] ) || ! isset( $groups[ $tab['group'] ] ) ) {
                $tabs[ $tab_key ]['group'] = 'extensions';
            }
        }
        ?>
        <div class="wrap cw-settings-wrap">
            <header class="cw-settings-header">
                <div>
                    <span class="cw-settings-header__eyebrow"><?php esc_html_e( 'Cinderwell', 'cinderwell' ); ?></span>
                    <h1><?php echo esc_html( $tabs[ $active_tab ]['label'] ?? __( 'Settings', 'cinderwell' ) ); ?></h1>
                </div>
                <p><?php esc_html_e( 'Configure the foundation, reusable site content, and optional capabilities in one place.', 'cinderwell' ); ?></p>
            </header>

            <div class="cw-settings-layout">
                <aside class="cw-settings-sidebar">
                    <nav aria-label="<?php esc_attr_e( 'Cinderwell settings', 'cinderwell' ); ?>">
                        <?php foreach ( $groups as $group_key => $group_label ) : ?>
                            <?php $group_tabs = array_filter( $tabs, static function ( $tab ) use ( $group_key ) { return $group_key === $tab['group']; } ); ?>
                            <?php if ( ! $group_tabs ) { continue; } ?>
                            <section class="cw-settings-nav-group">
                                <h2><?php echo esc_html( $group_label ); ?></h2>
                                <?php foreach ( $group_tabs as $tab_key => $tab ) : ?>
                                    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'cinderwell', 'tab' => $tab_key ], admin_url( 'admin.php' ) ) ); ?>" <?php echo $tab_key === $active_tab ? 'class="is-active" aria-current="page"' : ''; ?>>
                                        <?php echo esc_html( $tab['label'] ?? $tab_key ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </section>
                        <?php endforeach; ?>
                    </nav>
                </aside>

                <main class="cw-settings-content">
                    <?php
                    $callback = $tabs[ $active_tab ]['callback'] ?? null;
                    if ( is_callable( $callback ) ) {
                        call_user_func( $callback );
                    }
                    ?>
                </main>
            </div>
        </div>
        <?php
    }

    public function render_general_tab() {
        $blocks    = $this->get_registered_blocks();
        $patterns  = $this->get_registered_patterns();
        $theme     = wp_get_theme();
        $override  = file_exists( get_template_directory() . '/cinderwell/' );
        $templates = $this->get_template_overrides();
        ?>
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2><?php esc_html_e( 'General Information', 'cinderwell' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Version', 'cinderwell' ); ?></th>
                    <td><code><?php echo esc_html( CINDERWELL_VERSION ); ?></code></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Blocks Registered', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( count( $blocks ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Patterns Registered', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( count( $patterns ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Active Theme', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( $theme->get( 'Name' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Theme Role', 'cinderwell' ); ?></th>
                    <td>
                        <?php if ( is_child_theme() ) : ?>
                            <?php
                            printf(
                                /* translators: %s: parent theme name. */
                                esc_html__( 'Client child theme inheriting from %s', 'cinderwell' ),
                                esc_html( wp_get_theme( get_template() )->get( 'Name' ) )
                            );
                            ?>
                        <?php else : ?>
                            <?php esc_html_e( 'Parent/base theme', 'cinderwell' ); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Database Template Overrides', 'cinderwell' ); ?></th>
                    <td>
                        <?php if ( $templates ) : ?>
                            <strong style="color: #b32d2e;">
                                <?php
                                printf(
                                    /* translators: %d: number of template overrides. */
                                    esc_html( _n( '%d override', '%d overrides', count( $templates ), 'cinderwell' ) ),
                                    esc_html( number_format_i18n( count( $templates ) ) )
                                );
                                ?>
                            </strong>
                            &mdash;
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=templates' ) ); ?>">
                                <?php esc_html_e( 'Review update blockers', 'cinderwell' ); ?>
                            </a>
                        <?php else : ?>
                            <span style="color: #008a20;">&#10003; <?php esc_html_e( 'None', 'cinderwell' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Override Directory', 'cinderwell' ); ?></th>
                    <td>
                        <?php if ( $override ) : ?>
                            <span style="color: green;">&#10003; <?php esc_html_e( 'Found', 'cinderwell' ); ?></span>
                        <?php else : ?>
                            <span style="color: #999;">&#9888; <?php esc_html_e( 'Not found', 'cinderwell' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'WordPress Version', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'PHP Version', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( PHP_VERSION ); ?></td>
                </tr>
            </table>
        </div>

        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2><?php esc_html_e( 'Registered Blocks', 'cinderwell' ); ?></h2>
            <ul style="columns: 2; -webkit-columns: 2;">
                <?php foreach ( $blocks as $block ) : ?>
                    <li><code><?php echo esc_html( $block ); ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    public function render_templates_tab() {
        $overrides = $this->get_template_overrides();
        $theme     = wp_get_theme();
        ?>
        <div class="card" style="max-width: 900px; margin-top: 20px;">
            <h2><?php esc_html_e( 'Template Update Status', 'cinderwell' ); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: %s: active theme name. */
                    esc_html__( 'Cinderwell checked Site Editor customizations associated with %s.', 'cinderwell' ),
                    '<strong>' . esc_html( $theme->get( 'Name' ) ) . '</strong>'
                );
                ?>
            </p>
            <p>
                <?php esc_html_e( 'A database customization takes precedence over the corresponding parent- or child-theme file. Upstream file updates will not appear for that template until the customization is exported to the client theme and the database version is reset.', 'cinderwell' ); ?>
            </p>

            <?php if ( ! $overrides ) : ?>
                <div class="notice notice-success inline">
                    <p><?php esc_html_e( 'No database template overrides are blocking theme file updates.', 'cinderwell' ); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php
                        printf(
                            /* translators: %d: number of template overrides. */
                            esc_html( _n( '%d database override is taking precedence over theme files.', '%d database overrides are taking precedence over theme files.', count( $overrides ), 'cinderwell' ) ),
                            esc_html( number_format_i18n( count( $overrides ) ) )
                        );
                        ?>
                    </p>
                </div>

                <table class="widefat striped" style="margin-top: 16px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Name', 'cinderwell' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'cinderwell' ); ?></th>
                            <th><?php esc_html_e( 'Theme', 'cinderwell' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'cinderwell' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $overrides as $override ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $override['title'] ); ?></strong><br><code><?php echo esc_html( $override['slug'] ); ?></code></td>
                                <td><?php echo esc_html( $override['type_label'] ); ?></td>
                                <td><code><?php echo esc_html( $override['theme'] ); ?></code></td>
                                <td><a href="<?php echo esc_url( $override['editor_url'] ); ?>"><?php esc_html_e( 'Open in Site Editor', 'cinderwell' ); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h3><?php esc_html_e( 'Safe downstream workflow', 'cinderwell' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Build each client site as a child theme of the Cinderwell parent theme.', 'cinderwell' ); ?></li>
                <li><?php esc_html_e( 'Export intentional Site Editor template changes into that child theme.', 'cinderwell' ); ?></li>
                <li><?php esc_html_e( 'Confirm the exported file is deployed, then reset the database customization in the Site Editor.', 'cinderwell' ); ?></li>
                <li><?php esc_html_e( 'Leave the Cinderwell plugin and parent theme unmodified so their updates remain replaceable.', 'cinderwell' ); ?></li>
            </ol>
        </div>
        <?php
    }

    public function render_editor_access_tab() {
        Editor_Access::render_settings();
    }

	public function render_advanced_tab() {
		Editor_Utilities::render_settings();
	}

	/**
	 * Keep Settings ahead of editor-facing add-on content screens.
	 */
	public function order_submenu() {
		global $submenu;

		if ( empty( $submenu['cinderwell'] ) || ! is_array( $submenu['cinderwell'] ) ) {
			return;
		}

		$priority = [
			'cinderwell'                                => 0,
			'edit.php?post_type=cinderwell_popup'       => 10,
			'edit.php?post_type=cw_alert'               => 20,
			'cinderwell-help'                           => 90,
		];
		$indexed = [];
		foreach ( array_values( $submenu['cinderwell'] ) as $index => $item ) {
			$indexed[] = [
				'item'  => $item,
				'index' => $index,
				'rank'  => $priority[ $item[2] ?? '' ] ?? 50,
			];
		}

		usort( $indexed, static function ( $left, $right ) {
			return $left['rank'] === $right['rank']
				? $left['index'] <=> $right['index']
				: $left['rank'] <=> $right['rank'];
		} );

		$submenu['cinderwell'] = array_column( $indexed, 'item' );
	}

    public function render_tokens_tab() {
        $manifest = Design_Tokens::get_manifest();
        $saved    = get_option( 'cinderwell_design_tokens', [] );

        // Handle form submission.
        if ( isset( $_POST['cinderwell_save_tokens'] ) && check_admin_referer( 'cinderwell_tokens_nonce' ) ) {
            $new_tokens = [];
            foreach ( $manifest as $key => $token ) {
                $val = wp_unslash( $_POST[ 'token_' . $key ] ?? '' );
                $new_tokens[ $key ] = Design_Tokens::sanitize_token_value( $key, $val, $token );
            }
            update_option( 'cinderwell_design_tokens', $new_tokens );
            $saved = $new_tokens;
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Tokens saved.', 'cinderwell' ) . '</p></div>';
        }

        echo '<div class="card" style="max-width: 700px; margin-top: 20px;">';
        echo '<h2>' . esc_html__( 'Design Tokens', 'cinderwell' ) . '</h2>';
        echo '<p>' . esc_html__( 'Override default design tokens. Leave empty to use defaults.', 'cinderwell' ) . '</p>';
        echo '<form method="post">';
        wp_nonce_field( 'cinderwell_tokens_nonce' );
        echo '<table class="form-table">';

        foreach ( $manifest as $key => $token ) {
            $value = $saved[ $key ] ?? '';
            $default = $token['default'];
            printf(
                '<tr><th><label for="token_%s">%s</label><br><small><code>--%s</code></small></th><td>',
                esc_attr( $key ),
                esc_html( $token['label'] ),
                esc_attr( $key )
            );

            if ( 'color' === $token['type'] ) {
                printf(
                    '<input type="color" id="token_%s" name="token_%s" value="%s" style="width: 60px; height: 36px;"> <span style="color: #999;">%s: %s</span>',
                    esc_attr( $key ),
                    esc_attr( $key ),
                    esc_attr( $value ?: $default ),
                    esc_html__( 'Default', 'cinderwell' ),
                    esc_html( $default )
                );
            } elseif ( 'choice' === $token['type'] ) {
                printf( '<select id="token_%s" name="token_%s">', esc_attr( $key ), esc_attr( $key ) );
                foreach ( $token['options'] ?? [] as $option ) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr( $option['value'] ),
                        selected( $value ?: $default, $option['value'], false ),
                        esc_html( $option['label'] )
                    );
                }
                echo '</select>';
            } else {
                printf(
                    '<input type="text" id="token_%s" name="token_%s" value="%s" class="regular-text" placeholder="%s"> <span style="color: #999;">%s: %s</span>',
                    esc_attr( $key ),
                    esc_attr( $key ),
                    esc_attr( $value ),
                    esc_attr( $default ),
                    esc_html__( 'Default', 'cinderwell' ),
                    esc_html( $default )
                );
            }

            echo '</td></tr>';
        }

        echo '</table>';
        submit_button( __( 'Save Tokens', 'cinderwell' ), 'primary', 'cinderwell_save_tokens' );
        echo '</form></div>';
    }

    private function get_registered_blocks() {
        $blocks = [];
        $all    = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        foreach ( $all as $name => $block ) {
            if ( str_starts_with( $name, 'cinderwell/' ) ) {
                $blocks[] = $name;
            }
        }
        sort( $blocks );
        return $blocks;
    }

    private function get_registered_patterns() {
        $patterns = [];
        if ( class_exists( 'WP_Block_Patterns_Registry' ) ) {
            $all = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
            foreach ( $all as $name => $pattern ) {
                if ( str_starts_with( $name, 'cinderwell/' ) ) {
                    $patterns[] = $name;
                }
            }
        }
        sort( $patterns );
        return $patterns;
    }

    /**
     * Return Site Editor templates that shadow files from the active theme
     * inheritance chain.
     */
    private function get_template_overrides() {
        $theme_slugs = array_values( array_unique( [ get_stylesheet(), get_template() ] ) );
        $posts       = get_posts( [
            'post_type'              => [ 'wp_template', 'wp_template_part' ],
            'post_status'            => 'any',
            'posts_per_page'         => -1,
            'orderby'                => 'post_type title',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
        ] );
        $overrides   = [];

        foreach ( $posts as $post ) {
            $post_themes = wp_get_object_terms( $post->ID, 'wp_theme', [ 'fields' => 'names' ] );
            if ( is_wp_error( $post_themes ) ) {
                continue;
            }

            $matching_themes = array_values( array_intersect( $theme_slugs, $post_themes ) );
            if ( ! $matching_themes ) {
                continue;
            }

            $theme_slug = $matching_themes[0];
            $editor_url = add_query_arg(
                [
                    'postType' => $post->post_type,
                    'postId'   => $theme_slug . '//' . $post->post_name,
                    'canvas'   => 'edit',
                ],
                admin_url( 'site-editor.php' )
            );

            $overrides[] = [
                'title'      => $post->post_title ?: $post->post_name,
                'slug'       => $post->post_name,
                'theme'      => $theme_slug,
                'type_label' => 'wp_template_part' === $post->post_type
                    ? __( 'Template part', 'cinderwell' )
                    : __( 'Template', 'cinderwell' ),
                'editor_url' => $editor_url,
            ];
        }

        return $overrides;
    }
}
