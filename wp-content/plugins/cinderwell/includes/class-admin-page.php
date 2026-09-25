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
                'label'    => __( 'Dashboard', 'cinderwell' ),
                'group'    => 'overview',
                'callback' => [ $this, 'render_general_tab' ],
            ],
            'block-kit' => [
                'label'    => __( 'Block Kit', 'cinderwell' ),
                'group'    => 'design',
                'callback' => [ $this, 'render_block_kit_tab' ],
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
        $active_group       = $tabs[ $active_tab ]['group'] ?? 'overview';
        $active_description = $this->get_tab_description( $active_tab, $tabs[ $active_tab ] );
        ?>
        <div class="wrap cw-settings-wrap">
            <header class="cw-settings-header">
                <div class="cw-settings-header__title">
                    <span class="cw-settings-header__eyebrow"><?php echo esc_html( $groups[ $active_group ] ?? __( 'Cinderwell', 'cinderwell' ) ); ?></span>
                    <h1><?php echo esc_html( $tabs[ $active_tab ]['label'] ?? __( 'Settings', 'cinderwell' ) ); ?></h1>
                </div>
                <div class="cw-settings-header__context">
                    <p><?php echo esc_html( $active_description ); ?></p>
                    <span class="cw-settings-version"><?php echo esc_html( sprintf( __( 'Core %s', 'cinderwell' ), CINDERWELL_VERSION ) ); ?></span>
                </div>
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

    private function get_tab_description( $tab_key, $tab ) {
        $descriptions = [
            'general'         => __( 'Monitor the active foundation, ecosystem capabilities, and anything that needs attention.', 'cinderwell' ),
            'block-kit'       => __( 'Review the curated building blocks and patterns contributed by the active Cinderwell ecosystem.', 'cinderwell' ),
            'addons'          => __( 'Choose the capabilities this site needs and leave everything else dormant.', 'cinderwell' ),
            'company-details' => __( 'Keep reusable organization, contact, address, and social information in one source of truth.', 'cinderwell' ),
            'teams'           => __( 'Configure the people directory, public profiles, and client-editable fields.', 'cinderwell' ),
            'portfolio'       => __( 'Configure project publishing, URLs, archives, and structured portfolio fields.', 'cinderwell' ),
            'locations'       => __( 'Control location publishing and the URL structure used by location pages.', 'cinderwell' ),
            'page-headers'    => __( 'Set inherited title, breadcrumb, post meta, layout, spacing, and background defaults.', 'cinderwell' ),
            'editor-access'   => __( 'Shape the editing experience by role without changing frontend output.', 'cinderwell' ),
            'tokens'          => __( 'Manage the shared color, type, spacing, motion, and component decisions used across the site.', 'cinderwell' ),
            'animations'      => __( 'Choose restrained, token-driven motion defaults with accessible reduced-motion behavior.', 'cinderwell' ),
            'performance'     => __( 'Enable measured optimizations individually and verify them against the real site.', 'cinderwell' ),
            'portal'          => __( 'Configure member access, registration, and account templates.', 'cinderwell' ),
            'utilities'       => __( 'Turn on focused content, media, administration, and cleanup tools as needed.', 'cinderwell' ),
            'cookie-consent'  => __( 'Configure prior consent, visitor choices, retention, and policy connections.', 'cinderwell' ),
            'templates'       => __( 'Find database overrides that can prevent downstream theme updates from appearing.', 'cinderwell' ),
            'advanced'        => __( 'Control optional editor conveniences and integration behavior.', 'cinderwell' ),
            'settings-transfer' => __( 'Move portable Cinderwell configuration safely between environments.', 'cinderwell' ),
        ];
        $description = ! empty( $tab['description'] ) ? $tab['description'] : ( $descriptions[ $tab_key ] ?? __( 'Configure the foundation, reusable site content, and optional capabilities in one place.', 'cinderwell' ) );

        return apply_filters( 'cinderwell_settings_tab_description', $description, $tab_key, $tab );
    }

    public function render_general_tab() {
        $blocks    = $this->get_registered_blocks();
        $patterns  = $this->get_registered_patterns();
        $theme     = wp_get_theme();
        $templates = $this->get_template_overrides();
        $ecosystem = $this->get_ecosystem_summary();
        $library   = Block_Library::get_settings();
        $profiles  = Block_Library::get_profiles();
        $profile   = $profiles[ $library['profile'] ]['label'] ?? ucfirst( $library['profile'] );
        $theme_role = is_child_theme()
            ? sprintf(
                /* translators: %s: parent theme name. */
                __( 'Child theme of %s', 'cinderwell' ),
                wp_get_theme( get_template() )->get( 'Name' )
            )
            : __( 'Base theme', 'cinderwell' );
        ?>
        <section class="cw-dashboard-intro">
            <div>
                <span class="cw-dashboard-status"><i aria-hidden="true"></i><?php esc_html_e( 'Foundation active', 'cinderwell' ); ?></span>
                <h2><?php esc_html_e( 'Your Cinderwell ecosystem', 'cinderwell' ); ?></h2>
                <p><?php esc_html_e( 'A governed editing foundation, active client theme, and focused add-ons working as one maintainable system.', 'cinderwell' ); ?></p>
            </div>
            <div class="cw-dashboard-intro__actions">
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=addons' ) ); ?>"><?php esc_html_e( 'Manage add-ons', 'cinderwell' ); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=block-kit' ) ); ?>"><?php esc_html_e( 'View Block Kit', 'cinderwell' ); ?></a>
            </div>
        </section>

        <div class="cw-dashboard-stats">
            <a class="cw-dashboard-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=block-kit' ) ); ?>">
                <span class="dashicons dashicons-screenoptions" aria-hidden="true"></span>
                <strong><?php echo esc_html( number_format_i18n( count( $blocks ) ) ); ?></strong>
                <span><?php esc_html_e( 'Registered blocks', 'cinderwell' ); ?></span>
                <small><?php echo esc_html( sprintf( __( '%s editor profile', 'cinderwell' ), $profile ) ); ?></small>
            </a>
            <a class="cw-dashboard-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=addons' ) ); ?>">
                <span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
                <strong><?php echo esc_html( number_format_i18n( $ecosystem['active_count'] ) ); ?></strong>
                <span><?php esc_html_e( 'Active capabilities', 'cinderwell' ); ?></span>
                <small><?php esc_html_e( 'Bundled and independent add-ons', 'cinderwell' ); ?></small>
            </a>
            <div class="cw-dashboard-stat">
                <span class="dashicons dashicons-admin-appearance" aria-hidden="true"></span>
                <strong class="cw-dashboard-stat__text"><?php echo esc_html( $theme->get( 'Name' ) ); ?></strong>
                <span><?php esc_html_e( 'Active theme', 'cinderwell' ); ?></span>
                <small><?php echo esc_html( $theme_role ); ?></small>
            </div>
            <a class="cw-dashboard-stat <?php echo $templates ? 'has-warning' : 'is-ready'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=templates' ) ); ?>">
                <span class="dashicons <?php echo $templates ? 'dashicons-warning' : 'dashicons-yes-alt'; ?>" aria-hidden="true"></span>
                <strong><?php echo esc_html( number_format_i18n( count( $templates ) ) ); ?></strong>
                <span><?php esc_html_e( 'Template overrides', 'cinderwell' ); ?></span>
                <small><?php echo $templates ? esc_html__( 'Review update blockers', 'cinderwell' ) : esc_html__( 'Theme files are authoritative', 'cinderwell' ); ?></small>
            </a>
        </div>

        <div class="cw-dashboard-grid">
            <section class="card cw-settings-card cw-dashboard-panel">
                <header><div><span class="cw-dashboard-kicker"><?php esc_html_e( 'Foundation', 'cinderwell' ); ?></span><h2><?php esc_html_e( 'System status', 'cinderwell' ); ?></h2></div><span class="cw-dashboard-health is-good"><?php esc_html_e( 'Operational', 'cinderwell' ); ?></span></header>
                <dl class="cw-dashboard-status-list">
                    <div><dt><?php esc_html_e( 'Cinderwell Core', 'cinderwell' ); ?></dt><dd><code><?php echo esc_html( CINDERWELL_VERSION ); ?></code></dd></div>
                    <div><dt><?php esc_html_e( 'WordPress', 'cinderwell' ); ?></dt><dd><?php echo esc_html( get_bloginfo( 'version' ) ); ?></dd></div>
                    <div><dt><?php esc_html_e( 'PHP', 'cinderwell' ); ?></dt><dd><?php echo esc_html( PHP_VERSION ); ?></dd></div>
                    <div><dt><?php esc_html_e( 'Patterns', 'cinderwell' ); ?></dt><dd><?php echo esc_html( number_format_i18n( count( $patterns ) ) ); ?></dd></div>
                </dl>
            </section>

            <section class="card cw-settings-card cw-dashboard-panel">
                <header><div><span class="cw-dashboard-kicker"><?php esc_html_e( 'Workflow', 'cinderwell' ); ?></span><h2><?php esc_html_e( 'Quick actions', 'cinderwell' ); ?></h2></div></header>
                <nav class="cw-dashboard-actions" aria-label="<?php esc_attr_e( 'Cinderwell quick actions', 'cinderwell' ); ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=tokens' ) ); ?>"><span><strong><?php esc_html_e( 'Design Tokens', 'cinderwell' ); ?></strong><small><?php esc_html_e( 'Colors, type, spacing, and components', 'cinderwell' ); ?></small></span><span aria-hidden="true">&rarr;</span></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=editor-access' ) ); ?>"><span><strong><?php esc_html_e( 'Editor Access', 'cinderwell' ); ?></strong><small><?php esc_html_e( 'Roles, block profiles, and controls', 'cinderwell' ); ?></small></span><span aria-hidden="true">&rarr;</span></a>
                    <a href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>"><span><strong><?php esc_html_e( 'Site Editor', 'cinderwell' ); ?></strong><small><?php esc_html_e( 'Templates, navigation, and global styles', 'cinderwell' ); ?></small></span><span aria-hidden="true">&rarr;</span></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cinderwell&tab=templates' ) ); ?>"><span><strong><?php esc_html_e( 'Template Updates', 'cinderwell' ); ?></strong><small><?php esc_html_e( 'Review database customizations', 'cinderwell' ); ?></small></span><span aria-hidden="true">&rarr;</span></a>
                </nav>
            </section>
        </div>
        <?php
    }

    public function render_block_kit_tab() {
        $groups   = Block_Library::get_groups();
        $blocks   = $this->get_registered_block_details();
        $patterns = $this->get_registered_patterns();
        $library  = Block_Library::get_settings();
        $profiles = Block_Library::get_profiles();
        $profile  = $profiles[ $library['profile'] ]['label'] ?? ucfirst( $library['profile'] );
        ?>
        <section class="cw-block-kit-overview card cw-settings-card">
            <div>
                <span class="cw-dashboard-kicker"><?php esc_html_e( 'Curated authoring system', 'cinderwell' ); ?></span>
                <h2><?php esc_html_e( 'The Block Kit', 'cinderwell' ); ?></h2>
                <p><?php esc_html_e( 'Core blocks and active add-on blocks share the same tokens, controls, responsive behavior, and accessibility standards.', 'cinderwell' ); ?></p>
            </div>
            <dl>
                <div><dt><?php esc_html_e( 'Blocks', 'cinderwell' ); ?></dt><dd><?php echo esc_html( number_format_i18n( count( $blocks ) ) ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Patterns', 'cinderwell' ); ?></dt><dd><?php echo esc_html( number_format_i18n( count( $patterns ) ) ); ?></dd></div>
                <div><dt><?php esc_html_e( 'Editor profile', 'cinderwell' ); ?></dt><dd><?php echo esc_html( $profile ); ?></dd></div>
            </dl>
        </section>

        <div class="cw-block-kit-groups">
            <?php foreach ( $groups as $group_key => $group ) : ?>
                <?php $group_blocks = array_values( array_filter( $blocks, static function ( $block ) use ( $group_key ) { return $group_key === $block['group']; } ) ); ?>
                <?php if ( ! $group_blocks ) { continue; } ?>
                <section class="card cw-settings-card cw-block-kit-group">
                    <header><h2><?php echo esc_html( $group['title'] ?? $group_key ); ?></h2><span><?php echo esc_html( number_format_i18n( count( $group_blocks ) ) ); ?></span></header>
                    <ul>
                        <?php foreach ( $group_blocks as $block ) : ?>
                            <li><strong><?php echo esc_html( $block['title'] ); ?></strong><code><?php echo esc_html( $block['name'] ); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>

        <section class="card cw-settings-card cw-block-kit-patterns">
            <h2><?php esc_html_e( 'Patterns', 'cinderwell' ); ?></h2>
            <?php if ( $patterns ) : ?>
                <ul class="cw-settings-code-list"><?php foreach ( $patterns as $pattern ) : ?><li><code><?php echo esc_html( $pattern ); ?></code></li><?php endforeach; ?></ul>
            <?php else : ?>
                <p><?php esc_html_e( 'No plugin-owned patterns are registered. Client themes can compose site-specific patterns from Cinderwell blocks without coupling that presentation to Core.', 'cinderwell' ); ?></p>
            <?php endif; ?>
        </section>
        <?php
    }

    public function render_templates_tab() {
        $overrides = $this->get_template_overrides();
        $theme     = wp_get_theme();
        ?>
        <div class="card cw-settings-card">
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

        $resolved   = Design_Tokens::get_resolved_tokens();
        $groups     = $this->get_token_admin_groups( $manifest );
        $customized = count( array_filter( array_intersect_key( $saved, $manifest ), static fn( $value ) => '' !== (string) $value ) );
        $preview_css = [];
        foreach ( $resolved as $key => $value ) {
            if ( preg_match( '/^cw_[a-z0-9_]+$/', $key ) ) {
                $preview_css[] = '--' . str_replace( '_', '-', $key ) . ':' . $value;
            }
        }

        echo '<form method="post" class="cw-token-form" data-cw-token-form style="' . esc_attr( implode( ';', $preview_css ) ) . '">';
        wp_nonce_field( 'cinderwell_tokens_nonce' );

        echo '<section class="cw-token-overview card cw-settings-card">';
        echo '<div class="cw-token-overview__copy">';
        echo '<span class="cw-token-kicker">' . esc_html__( 'System foundation', 'cinderwell' ) . '</span>';
        echo '<h2>' . esc_html__( 'Design Tokens', 'cinderwell' ) . '</h2>';
        echo '<p>' . esc_html__( 'Tune the shared decisions used by every Cinderwell block. Empty values continue to inherit the documented default.', 'cinderwell' ) . '</p>';
        printf(
            '<div class="cw-token-summary"><strong>%1$d</strong> %2$s <span aria-hidden="true">·</span> <strong data-cw-token-custom-count>%3$d</strong> %4$s</div>',
            count( $manifest ),
            esc_html__( 'tokens', 'cinderwell' ),
            $customized,
            esc_html__( 'customized', 'cinderwell' )
        );
        echo '</div>';
        echo '<div class="cw-token-live-preview" aria-label="' . esc_attr__( 'Live token preview', 'cinderwell' ) . '">';
        echo '<span class="cw-token-live-preview__eyebrow">' . esc_html__( 'Live preview', 'cinderwell' ) . '</span>';
        echo '<strong>' . esc_html__( 'A distinct foundation.', 'cinderwell' ) . '</strong>';
        echo '<p>' . esc_html__( 'Typography, color, shape, and interaction stay connected.', 'cinderwell' ) . '</p>';
        echo '<div><span class="cw-token-preview-button cw-token-preview-button--primary">' . esc_html__( 'Primary', 'cinderwell' ) . '</span>';
        echo '<span class="cw-token-preview-button cw-token-preview-button--secondary">' . esc_html__( 'Secondary', 'cinderwell' ) . '</span></div>';
        echo '</div>';
        echo '</section>';

        echo '<div class="cw-token-toolbar card">';
        echo '<label for="cw-token-search"><span class="dashicons dashicons-search" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Search design tokens', 'cinderwell' ) . '</span></label>';
        echo '<input type="search" id="cw-token-search" data-cw-token-search data-cw-ignore-dirty placeholder="' . esc_attr__( 'Search tokens, variables, or descriptions…', 'cinderwell' ) . '">';
        echo '<span data-cw-token-results aria-live="polite"></span>';
        echo '</div>';

        echo '<nav class="cw-token-jump-nav" aria-label="' . esc_attr__( 'Token groups', 'cinderwell' ) . '">';
        foreach ( $groups as $slug => $group ) {
            printf( '<a href="#cw-token-group-%1$s">%2$s <span>%3$d</span></a>', esc_attr( $slug ), esc_html( $group['label'] ), count( $group['tokens'] ) );
        }
        echo '</nav>';

        echo '<div class="cw-token-groups">';
        foreach ( $groups as $slug => $group ) {
            printf( '<section class="cw-token-section card cw-settings-card" id="cw-token-group-%1$s" data-cw-token-group>', esc_attr( $slug ) );
            echo '<header class="cw-token-section__header">';
            echo '<div><span class="cw-token-kicker">' . esc_html( $group['eyebrow'] ) . '</span><h2>' . esc_html( $group['label'] ) . '</h2><p>' . esc_html( $group['description'] ) . '</p></div>';
            printf( '<span class="cw-token-section__count">%d</span>', count( $group['tokens'] ) );
            echo '</header><div class="cw-token-grid">';

            foreach ( $group['tokens'] as $key => $token ) {
                $this->render_token_admin_field( $key, $token, $saved[ $key ] ?? '', $resolved[ $key ] ?? $token['default'] );
            }

            echo '</div></section>';
        }

        echo '</div>';
        submit_button( __( 'Save Tokens', 'cinderwell' ), 'primary', 'cinderwell_save_tokens' );
        echo '</form>';
    }

    private function get_token_admin_groups( $manifest ) {
        $groups = [
            'colors'     => [ 'label' => __( 'Color system', 'cinderwell' ), 'eyebrow' => __( 'Palette', 'cinderwell' ), 'description' => __( 'Brand, surface, semantic, link, and accessibility colors.', 'cinderwell' ), 'tokens' => [] ],
            'buttons'    => [ 'label' => __( 'Buttons', 'cinderwell' ), 'eyebrow' => __( 'Components', 'cinderwell' ), 'description' => __( 'Primary, secondary, ghost, and link button recipes.', 'cinderwell' ), 'tokens' => [] ],
            'typography' => [ 'label' => __( 'Typography', 'cinderwell' ), 'eyebrow' => __( 'Type system', 'cinderwell' ), 'description' => __( 'Font families, fluid sizes, and reading rhythm.', 'cinderwell' ), 'tokens' => [] ],
            'spacing'    => [ 'label' => __( 'Spacing & layout', 'cinderwell' ), 'eyebrow' => __( 'Composition', 'cinderwell' ), 'description' => __( 'Spacing scale, gaps, page gutters, and content widths.', 'cinderwell' ), 'tokens' => [] ],
            'surfaces'   => [ 'label' => __( 'Shape & elevation', 'cinderwell' ), 'eyebrow' => __( 'Surfaces', 'cinderwell' ), 'description' => __( 'Image corners, radii, and elevation treatments.', 'cinderwell' ), 'tokens' => [] ],
            'motion'     => [ 'label' => __( 'Motion', 'cinderwell' ), 'eyebrow' => __( 'Behavior', 'cinderwell' ), 'description' => __( 'Durations, easing, and movement distance.', 'cinderwell' ), 'tokens' => [] ],
        ];

        foreach ( $manifest as $key => $token ) {
            if ( 0 === strpos( $key, 'cw_color_' ) ) {
                $group = 'colors';
            } elseif ( 0 === strpos( $key, 'cw_button_' ) ) {
                $group = 'buttons';
            } elseif ( 0 === strpos( $key, 'cw_font_' ) || 0 === strpos( $key, 'cw_line_height_' ) ) {
                $group = 'typography';
            } elseif ( 0 === strpos( $key, 'cw_spacing_' ) || 0 === strpos( $key, 'cw_gap_' ) || 0 === strpos( $key, 'cw_layout_' ) || 0 === strpos( $key, 'cw_page_' ) || 0 === strpos( $key, 'cw_width_' ) ) {
                $group = 'spacing';
            } elseif ( 0 === strpos( $key, 'cw_duration_' ) || 0 === strpos( $key, 'cw_ease_' ) || 0 === strpos( $key, 'cw_motion_' ) ) {
                $group = 'motion';
            } else {
                $group = 'surfaces';
            }
            $groups[ $group ]['tokens'][ $key ] = $token;
        }

        return array_filter( $groups, static fn( $group ) => ! empty( $group['tokens'] ) );
    }

    private function render_token_admin_field( $key, $token, $value, $resolved ) {
        $id          = 'token_' . $key;
        $default     = (string) $token['default'];
        $is_custom   = '' !== (string) $value;
        $is_editable = $token['editable'] ?? true;
        $search      = strtolower( implode( ' ', [ $token['label'], $key, $token['description'] ?? '', $default ] ) );
        $css_var     = '--' . str_replace( '_', '-', $key );

        printf( '<article class="cw-token-item%1$s" data-cw-token-item data-cw-token-search-text="%2$s">', $is_custom ? ' is-custom' : '', esc_attr( $search ) );
        echo '<div class="cw-token-item__heading"><div>';
        if ( $is_editable && 'derived-color' !== $token['type'] ) {
            printf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_html( $token['label'] ) );
        } else {
            echo '<strong>' . esc_html( $token['label'] ) . '</strong>';
        }
        printf( '<code>%s</code>', esc_html( $css_var ) );
        echo '</div><span class="cw-token-state" data-cw-token-state>' . ( $is_custom ? esc_html__( 'Custom', 'cinderwell' ) : esc_html__( 'Inherited', 'cinderwell' ) ) . '</span></div>';
        echo '<p>' . esc_html( $token['description'] ?? '' ) . '</p>';

        if ( ! $is_editable || 'derived-color' === $token['type'] ) {
            printf( '<div class="cw-token-derived"><span class="cw-token-swatch" style="--cw-token-swatch:%1$s"></span><code>%2$s</code><span>%3$s</span></div>', esc_attr( $resolved ), esc_html( $default ), esc_html__( 'Automatic', 'cinderwell' ) );
        } elseif ( 'color' === $token['type'] ) {
            echo '<div class="cw-token-color-control">';
            printf( '<input type="color" value="%1$s" data-cw-token-picker aria-label="%2$s">', esc_attr( $resolved ), esc_attr( sprintf( __( 'Choose %s', 'cinderwell' ), $token['label'] ) ) );
            printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" data-cw-token-input data-cw-token-default="%3$s" data-cw-token-variable="%4$s" spellcheck="false">', esc_attr( $id ), esc_attr( $value ), esc_attr( $default ), esc_attr( $css_var ) );
            printf( '<button type="button" class="button-link cw-token-reset" data-cw-token-reset>%s</button>', esc_html__( 'Use default', 'cinderwell' ) );
            echo '</div>';
        } elseif ( 'choice' === $token['type'] ) {
            echo '<fieldset class="cw-admin-segmented cw-token-choice" data-cw-token-choice data-cw-token-variable="' . esc_attr( $css_var ) . '" data-cw-token-default="' . esc_attr( $default ) . '"><legend class="screen-reader-text">' . esc_html( $token['label'] ) . '</legend>';
            printf( '<label><input type="radio" class="screen-reader-text" name="%1$s" value="" %2$s><span class="cw-admin-segmented__option">%3$s</span></label>', esc_attr( $id ), checked( '', $value, false ), esc_html__( 'Default', 'cinderwell' ) );
            foreach ( $token['options'] ?? [] as $option ) {
                printf( '<label><input type="radio" class="screen-reader-text" name="%1$s" value="%2$s" %3$s><span class="cw-admin-segmented__option">%4$s</span></label>', esc_attr( $id ), esc_attr( $option['value'] ), checked( $value, $option['value'], false ), esc_html( $option['label'] ) );
            }
            echo '</fieldset>';
        } else {
            echo '<div class="cw-token-text-control">';
            if ( 0 === strpos( $key, 'cw_button_' ) ) {
                printf(
                    '<span class="cw-token-value-swatch" style="--cw-token-value:%1$s" title="%2$s"><span class="screen-reader-text">%3$s</span></span>',
                    esc_attr( 'var(' . $css_var . ')' ),
                    esc_attr__( 'Resolved color preview', 'cinderwell' ),
                    esc_html__( 'Resolved color preview', 'cinderwell' )
                );
            }
            printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" data-cw-token-input data-cw-token-default="%3$s" data-cw-token-variable="%4$s" spellcheck="false">', esc_attr( $id ), esc_attr( $value ), esc_attr( $default ), esc_attr( $css_var ) );
            printf( '<button type="button" class="button-link cw-token-reset" data-cw-token-reset>%s</button>', esc_html__( 'Use default', 'cinderwell' ) );
            echo '</div>';
        }

        if ( $is_editable && 'choice' !== $token['type'] ) {
            printf( '<div class="cw-token-default"><span>%1$s</span><code>%2$s</code></div>', esc_html__( 'Default', 'cinderwell' ), esc_html( $default ) );
        }
        echo '</article>';
    }

    private function get_registered_blocks() {
        $blocks = [];
        $all    = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        foreach ( $all as $name => $block ) {
            if ( Block_Library::is_cinderwell_block( $name ) ) {
                $blocks[] = $name;
            }
        }
        sort( $blocks );
        return $blocks;
    }

    private function get_registered_block_details() {
        $blocks = [];
        $all    = \WP_Block_Type_Registry::get_instance()->get_all_registered();

        foreach ( $all as $name => $block ) {
            if ( ! Block_Library::is_cinderwell_block( $name ) ) {
                continue;
            }

            $blocks[] = [
                'name'  => $name,
                'title' => $block->title ?: ucwords( str_replace( '-', ' ', substr( $name, strpos( $name, '/' ) + 1 ) ) ),
                'group' => Block_Library::group_for_block( $name ) ?: 'cinderwell-content',
            ];
        }

        usort( $blocks, static function ( $a, $b ) {
            return strcasecmp( $a['title'], $b['title'] );
        } );

        return $blocks;
    }

    private function get_ecosystem_summary() {
        $bundled        = Addons::get_bundled();
        $enabled        = array_map( 'sanitize_key', (array) get_option( Addons::OPTION, [] ) );
        $active_bundled = array_intersect( array_keys( $bundled ), $enabled );
        $active_plugins = [];
        $known_plugins  = [];

        if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ( get_plugins() as $plugin_file => $plugin ) {
            if ( 'cinderwell/cinderwell.php' === $plugin_file ) {
                continue;
            }

            $host = strtolower( (string) wp_parse_url( $plugin['UpdateURI'] ?? '', PHP_URL_HOST ) );
            if ( 'cinderwell-updates.surge.sh' !== $host ) {
                continue;
            }

            $known_plugins[] = $plugin_file;
            if ( is_plugin_active( $plugin_file ) ) {
                $active_plugins[] = $plugin_file;
            }
        }

        return [
            'active_count'    => count( $active_bundled ) + count( $active_plugins ),
            'available_count' => count( $bundled ) + count( $known_plugins ),
        ];
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
