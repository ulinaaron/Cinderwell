<?php
/**
 * Add-on catalog and bundled module activation.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Addons {

    const OPTION = 'cinderwell_enabled_addons';

    /** @var Update_Mechanism */
    private $updates;

    public function __construct( Update_Mechanism $updates ) {
        $this->updates = $updates;

        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 5 );
        add_action( 'admin_post_cinderwell_save_addons', [ $this, 'save' ] );
        add_action( 'admin_post_cinderwell_install_addon', [ $this, 'install' ] );
        add_action( 'admin_post_cinderwell_reset_addon', [ $this, 'reset' ] );
        add_action( 'init', [ $this, 'maybe_flush_rewrite_rules' ], 99 );
    }

    /**
     * Whether a bundled module is enabled for this site.
     */
    public static function is_enabled( $slug ) {
        $enabled = get_option( self::OPTION, [] );
        return is_array( $enabled ) && in_array( sanitize_key( $slug ), $enabled, true );
    }

    /**
     * Bundled modules remain discoverable if the remote catalog is offline.
     */
    public static function get_bundled() {
        return apply_filters( 'cinderwell_bundled_addons', [
            'teams' => [
                'name'         => __( 'Teams', 'cinderwell' ),
                'slug'         => 'teams',
                'distribution' => 'bundled',
                'version'      => CINDERWELL_VERSION,
                'description'  => __( 'Manage people, team categories, optional profile pages, and focused People loops.', 'cinderwell' ),
                'settings_tab' => 'teams',
                'icon'         => 'dashicons-groups',
                'option'       => Teams::OPTION,
                'defaults_callback' => [ Teams::class, 'get_defaults' ],
                'health_callback'   => [ Teams::class, 'get_health' ],
            ],
            'portfolio' => [
                'name'         => __( 'Portfolio', 'cinderwell' ),
                'slug'         => 'portfolio',
                'distribution' => 'bundled',
                'version'      => CINDERWELL_VERSION,
                'description'  => __( 'Manage project work, configurable fields, categories, templates, and focused Portfolio loops.', 'cinderwell' ),
                'settings_tab' => 'portfolio',
                'icon'         => 'dashicons-portfolio',
                'option'       => Portfolio::OPTION,
                'defaults_callback' => [ Portfolio::class, 'get_defaults' ],
                'health_callback'   => [ Portfolio::class, 'get_health' ],
            ],
            'company-details' => [
                'name'         => __( 'Company Details', 'cinderwell' ),
                'slug'         => 'company-details',
                'distribution' => 'bundled',
                'version'      => CINDERWELL_VERSION,
                'description'  => __( 'Define reusable company identity, contact, address, hours, and social-profile information.', 'cinderwell' ),
                'settings_tab' => 'company-details',
                'icon'         => 'dashicons-building',
                'option'       => Company_Details::OPTION,
                'defaults_callback' => [ Company_Details::class, 'get_defaults' ],
                'health_callback'   => [ Company_Details::class, 'get_health' ],
            ],
            'locations' => [
                'name'         => __( 'Locations', 'cinderwell' ),
                'slug'         => 'locations',
                'distribution' => 'bundled',
                'version'      => CINDERWELL_VERSION,
                'description'  => __( 'Manage reusable branch, office, campus, or service-area details and optional public location pages.', 'cinderwell' ),
                'settings_tab' => 'locations',
                'icon'         => 'dashicons-location-alt',
                'dependencies' => [ 'company-details' ],
                'option'       => Locations::OPTION,
                'defaults_callback' => [ Locations::class, 'get_defaults' ],
                'health_callback'   => [ Locations::class, 'get_health' ],
            ],
            'animations' => [
                'name'         => __( 'Animations', 'cinderwell' ),
                'slug'         => 'animations',
                'distribution' => 'bundled',
                'version'      => CINDERWELL_VERSION,
                'description'  => __( 'Add token-based entrance animations to whole blocks or their content sections.', 'cinderwell' ),
                'settings_tab' => 'animations',
                'icon'         => 'dashicons-controls-play',
                'option'       => Animations::OPTION,
                'defaults_callback' => [ Animations::class, 'get_defaults' ],
                'health_callback'   => [ Animations::class, 'get_health' ],
            ],
        ] );
    }

    public function add_settings_tab( $tabs ) {
        $tab = [
            'addons' => [
                'label'    => __( 'Add-Ons', 'cinderwell' ),
                'group'    => 'extensions',
                'callback' => [ $this, 'render' ],
            ],
        ];

        return array_slice( $tabs, 0, 1, true ) + $tab + array_slice( $tabs, 1, null, true );
    }

    public function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage Cinderwell add-ons.', 'cinderwell' ) );
        }

        check_admin_referer( 'cinderwell_save_addons' );

        $requested = isset( $_POST['addons'] ) ? (array) wp_unslash( $_POST['addons'] ) : [];
        $allowed   = array_keys( self::get_bundled() );
        $enabled   = array_values( array_intersect( $allowed, array_map( 'sanitize_key', $requested ) ) );
        $resolved  = self::resolve_dependencies( $enabled );
        $adjusted  = $resolved !== $enabled;
        $enabled   = $resolved;

        update_option( self::OPTION, $enabled );
        update_option( 'cinderwell_flush_rewrite_rules', 1, false );

        wp_safe_redirect( add_query_arg( [
            'page'    => 'cinderwell',
            'tab'     => 'addons',
            'updated' => '1',
            'dependencies' => $adjusted ? '1' : false,
        ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function maybe_flush_rewrite_rules() {
        if ( get_option( 'cinderwell_flush_rewrite_rules' ) ) {
            flush_rewrite_rules();
            delete_option( 'cinderwell_flush_rewrite_rules' );
        }
    }

    /**
     * Ensure enabled bundled modules retain all declared dependencies.
     */
    public static function resolve_dependencies( $enabled ) {
        $bundled = self::get_bundled();
        $enabled = array_values( array_intersect( array_keys( $bundled ), array_map( 'sanitize_key', (array) $enabled ) ) );

        do {
            $before = $enabled;
            foreach ( $enabled as $slug ) {
                foreach ( (array) ( $bundled[ $slug ]['dependencies'] ?? [] ) as $dependency ) {
                    $dependency = sanitize_key( $dependency );
                    if ( isset( $bundled[ $dependency ] ) && ! in_array( $dependency, $enabled, true ) ) {
                        $enabled[] = $dependency;
                    }
                }
            }
            $enabled = array_values( array_unique( $enabled ) );
        } while ( $before !== $enabled );

        return $enabled;
    }

    /**
     * Merge the remote catalog, locally installed add-ons, and authoritative
     * bundled-module metadata.
     */
    private function get_catalog() {
        $catalog  = [];
        $manifest = $this->updates->get_manifest();

        if ( is_object( $manifest ) && ! empty( $manifest->addons ) && is_object( $manifest->addons ) ) {
            foreach ( get_object_vars( $manifest->addons ) as $key => $addon ) {
                if ( ! is_object( $addon ) ) {
                    continue;
                }

                $slug = sanitize_key( $addon->module ?? $addon->slug ?? $key );
                if ( ! $slug ) {
                    continue;
                }

                $catalog[ $slug ] = [
                    'name'         => sanitize_text_field( $addon->name ?? $slug ),
                    'slug'         => sanitize_key( $addon->slug ?? $slug ),
                    'module'       => sanitize_key( $addon->module ?? '' ),
                    'distribution' => sanitize_key( $addon->distribution ?? 'plugin' ),
                    'version'      => sanitize_text_field( $addon->version ?? '' ),
                    'description'  => sanitize_text_field( $addon->sections->description ?? $addon->description ?? '' ),
                    'plugin_file'  => sanitize_text_field( $addon->plugin_file ?? '' ),
                    'download_url' => esc_url_raw( $addon->download_url ?? '' ),
                ];
            }
        }

        foreach ( $this->get_installed_addons() as $slug => $addon ) {
            $remote = $catalog[ $slug ] ?? [];

            // The installed plugin is authoritative for its identity and current
            // version. Keep richer release metadata, such as its download URL,
            // when the remote catalog also knows about it.
            $catalog[ $slug ] = array_merge( $addon, $remote, [
                'name'         => $addon['name'],
                'slug'         => $addon['slug'],
                'distribution' => 'plugin',
                'version'      => $addon['version'],
                'plugin_file'  => $addon['plugin_file'],
            ] );
        }

        foreach ( self::get_bundled() as $slug => $addon ) {
            $catalog[ $slug ] = array_merge( $catalog[ $slug ] ?? [], $addon );
        }

        return (array) apply_filters( 'cinderwell_addon_catalog', $catalog );
    }

    /**
     * Discover independently packaged Cinderwell add-ons already installed on
     * this site, including inactive plugins.
     *
     * The Update URI host is used as the ecosystem marker so an unrelated
     * plugin cannot appear in the catalog merely by using a similar slug.
     */
    private function get_installed_addons() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $addons        = [];
        $allowed_hosts = $this->get_package_hosts();

        foreach ( get_plugins() as $plugin_file => $plugin ) {
            if ( 'cinderwell/cinderwell.php' === $plugin_file ) {
                continue;
            }

            $update_uri = $plugin['UpdateURI'] ?? '';
            $host       = $update_uri ? strtolower( (string) wp_parse_url( $update_uri, PHP_URL_HOST ) ) : '';

            if ( ! $host || ! in_array( $host, $allowed_hosts, true ) ) {
                continue;
            }

            $directory = dirname( $plugin_file );
            $slug      = sanitize_key( '.' === $directory ? pathinfo( $plugin_file, PATHINFO_FILENAME ) : $directory );

            if ( ! $slug ) {
                continue;
            }

            $addons[ $slug ] = [
                'name'         => sanitize_text_field( $plugin['Name'] ?? $slug ),
                'slug'         => $slug,
                'module'       => '',
                'distribution' => 'plugin',
                'version'      => sanitize_text_field( $plugin['Version'] ?? '' ),
                'description'  => sanitize_text_field( $plugin['Description'] ?? '' ),
                'plugin_file'  => sanitize_text_field( $plugin_file ),
                'download_url' => '',
            ];
        }

        return $addons;
    }

    /**
     * Hosts trusted to identify and distribute Cinderwell add-on packages.
     */
    private function get_package_hosts() {
        $hosts = (array) apply_filters( 'cinderwell_addon_package_hosts', [ 'cinderwell-updates.surge.sh' ] );

        return array_values( array_unique( array_filter( array_map( static function ( $host ) {
            return strtolower( sanitize_text_field( $host ) );
        }, $hosts ) ) ) );
    }

    public function render() {
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Add-on settings saved.', 'cinderwell' ) . '</p></div>';
        }
        if ( isset( $_GET['installed'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Add-on installed. You can activate it below.', 'cinderwell' ) . '</p></div>';
        }
        if ( isset( $_GET['install_error'] ) ) {
            echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The add-on could not be installed from the Cinderwell release service.', 'cinderwell' ) . '</p></div>';
        }
        if ( isset( $_GET['dependencies'] ) ) {
            echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Required add-ons were kept enabled automatically.', 'cinderwell' ) . '</p></div>';
        }
        if ( isset( $_GET['reset'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Add-on settings reset to their defaults.', 'cinderwell' ) . '</p></div>';
        }

        $catalog = $this->get_catalog();
        $enabled = get_option( self::OPTION, [] );
        $enabled = is_array( $enabled ) ? $enabled : [];
        ?>
        <p><?php esc_html_e( 'Extend Cinderwell with optional modules and independently updated plugins. Bundled modules ship with core but do nothing until enabled.', 'cinderwell' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="cinderwell_save_addons">
            <?php wp_nonce_field( 'cinderwell_save_addons' ); ?>
            <div class="cw-addon-grid">
                <?php foreach ( $catalog as $slug => $addon ) : ?>
                    <?php $is_enabled = in_array( $slug, $enabled, true ); ?>
                    <?php $display_name = self::get_display_name( $addon['name'] ?? $slug ); ?>
                    <div class="card cw-addon-card">
                        <div class="cw-addon-card__heading">
                            <?php if ( ! empty( $addon['icon'] ) ) : ?>
                                <span class="dashicons <?php echo esc_attr( sanitize_html_class( $addon['icon'] ) ); ?>" aria-hidden="true"></span>
                            <?php endif; ?>
                            <h2><?php echo esc_html( $display_name ); ?></h2>
                        </div>
                        <p style="margin-top:0;color:#646970;">
                            <?php
                            echo esc_html(
                                'bundled' === $addon['distribution']
                                    ? __( 'Bundled with Cinderwell', 'cinderwell' )
                                    : __( 'Independent add-on', 'cinderwell' )
                            );
                            if ( ! empty( $addon['version'] ) ) {
                                echo ' · ' . esc_html( $addon['version'] );
                            }
                            ?>
                        </p>
                        <p style="flex:1;"><?php echo esc_html( $addon['description'] ); ?></p>
                        <?php if ( 'bundled' === $addon['distribution'] ) : ?>
                            <?php $required_by = $this->get_required_by( $slug, $enabled ); ?>
                            <label>
                                <input type="checkbox" name="addons[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_enabled ); ?>>
                                <?php esc_html_e( 'Enabled', 'cinderwell' ); ?>
                            </label>
                            <?php if ( $required_by ) : ?>
                                <p class="description cw-addon-card__dependency">
                                    <?php printf( esc_html__( 'Required by: %s', 'cinderwell' ), esc_html( implode( ', ', $required_by ) ) ); ?>
                                </p>
                            <?php endif; ?>
                            <?php $this->render_bundled_status( $addon, $is_enabled ); ?>
                        <?php else : ?>
                            <?php $this->render_plugin_action( $addon ); ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php submit_button( __( 'Save Add-Ons', 'cinderwell' ) ); ?>
        </form>
        <?php
    }

    private function get_required_by( $slug, $enabled ) {
        $required_by = [];
        foreach ( self::get_bundled() as $candidate_slug => $candidate ) {
            if ( in_array( $candidate_slug, $enabled, true ) && in_array( $slug, (array) ( $candidate['dependencies'] ?? [] ), true ) ) {
                $required_by[] = self::get_display_name( $candidate['name'] );
            }
        }
        return $required_by;
    }

    /**
     * Keep the product brand in package metadata while avoiding repetitive
     * "Cinderwell …" labels inside the Cinderwell Add-Ons screen.
     */
    private static function get_display_name( $name ) {
        $name         = sanitize_text_field( $name );
        $display_name = trim( (string) preg_replace( '/^Cinderwell(?:\s+|\s*[-:]\s*)/i', '', $name ) );

        return $display_name ?: $name;
    }

    private function render_bundled_status( $addon, $is_enabled ) {
        if ( ! $is_enabled ) {
            echo '<p class="cw-addon-health is-disabled">' . esc_html__( 'Not active', 'cinderwell' ) . '</p>';
            return;
        }

        $this->render_health( $addon );

        echo '<div class="cw-addon-card__actions">';
        if ( ! empty( $addon['settings_tab'] ) ) {
            echo '<a class="button" href="' . esc_url( add_query_arg( [ 'page' => 'cinderwell', 'tab' => $addon['settings_tab'] ], admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Configure', 'cinderwell' ) . '</a>';
        }
        if ( ! empty( $addon['option'] ) && ! empty( $addon['defaults_callback'] ) ) {
            $reset_url = wp_nonce_url(
                add_query_arg( [ 'action' => 'cinderwell_reset_addon', 'addon' => $addon['slug'] ], admin_url( 'admin-post.php' ) ),
                'cinderwell_reset_addon_' . $addon['slug']
            );
            echo '<a class="button-link-delete" data-cw-confirm="' . esc_attr__( 'Reset this add-on’s settings to defaults?', 'cinderwell' ) . '" href="' . esc_url( $reset_url ) . '">' . esc_html__( 'Reset settings', 'cinderwell' ) . '</a>';
        }
        echo '</div>';
    }

    private function render_plugin_action( $addon ) {
        if ( empty( $addon['plugin_file'] ) || empty( $addon['slug'] ) ) {
            echo '<span class="button disabled">' . esc_html__( 'Unavailable', 'cinderwell' ) . '</span>';
            return;
        }

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( is_plugin_active( $addon['plugin_file'] ) ) {
            echo '<span><span class="dashicons dashicons-yes-alt" style="color:#008a20;"></span> ' . esc_html__( 'Active', 'cinderwell' ) . '</span>';
            $this->render_health( $addon );
            $this->render_settings_link( $addon );
            return;
        }

        if ( file_exists( WP_PLUGIN_DIR . '/' . $addon['plugin_file'] ) ) {
            $url = wp_nonce_url(
                add_query_arg( [ 'action' => 'activate', 'plugin' => $addon['plugin_file'] ], admin_url( 'plugins.php' ) ),
                'activate-plugin_' . $addon['plugin_file']
            );
            echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Activate', 'cinderwell' ) . '</a>';
            return;
        }

        if ( empty( $addon['download_url'] ) ) {
            echo '<span class="button disabled">' . esc_html__( 'Unavailable', 'cinderwell' ) . '</span>';
            return;
        }

        $url = wp_nonce_url(
            add_query_arg( [ 'action' => 'cinderwell_install_addon', 'addon' => ( $addon['module'] ?? '' ) ?: $addon['slug'] ], admin_url( 'admin-post.php' ) ),
            'cinderwell_install_addon_' . ( ( $addon['module'] ?? '' ) ?: $addon['slug'] )
        );
        echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Install', 'cinderwell' ) . '</a>';
    }

    private function render_settings_link( $addon ) {
        $url = '';
        if ( ! empty( $addon['settings_tab'] ) ) {
            $url = add_query_arg( [ 'page' => 'cinderwell', 'tab' => $addon['settings_tab'] ], admin_url( 'admin.php' ) );
        } elseif ( ! empty( $addon['settings_url'] ) ) {
            $url = $addon['settings_url'];
        }
        if ( $url ) {
            echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Configure', 'cinderwell' ) . '</a>';
        }
    }

    private function render_health( $addon ) {
        if ( empty( $addon['health_callback'] ) || ! is_callable( $addon['health_callback'] ) ) {
            return;
        }
        $health = (array) call_user_func( $addon['health_callback'] );
        echo '<p class="cw-addon-health is-' . esc_attr( sanitize_html_class( $health['status'] ?? 'good' ) ) . '">' . esc_html( $health['message'] ?? __( 'Ready', 'cinderwell' ) ) . '</p>';
    }

    public function reset() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to reset add-on settings.', 'cinderwell' ) );
        }
        $slug = isset( $_GET['addon'] ) ? sanitize_key( wp_unslash( $_GET['addon'] ) ) : '';
        check_admin_referer( 'cinderwell_reset_addon_' . $slug );
        $addon = self::get_bundled()[ $slug ] ?? null;
        if ( ! $addon || empty( $addon['option'] ) || empty( $addon['defaults_callback'] ) || ! is_callable( $addon['defaults_callback'] ) ) {
            wp_die( esc_html__( 'This add-on does not expose resettable settings.', 'cinderwell' ) );
        }
        update_option( sanitize_key( $addon['option'] ), call_user_func( $addon['defaults_callback'] ) );
        update_option( 'cinderwell_flush_rewrite_rules', 1, false );
        wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'addons', 'reset' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Install an independently packaged add-on from the signed-in site's
     * configured Cinderwell release manifest.
     */
    public function install() {
        if ( ! current_user_can( 'install_plugins' ) ) {
            wp_die( esc_html__( 'You are not allowed to install add-ons.', 'cinderwell' ) );
        }

        $slug = isset( $_GET['addon'] ) ? sanitize_key( wp_unslash( $_GET['addon'] ) ) : '';
        check_admin_referer( 'cinderwell_install_addon_' . $slug );
        $catalog = $this->get_catalog();
        $addon   = $catalog[ $slug ] ?? null;
        $url     = $addon['download_url'] ?? '';
        $host    = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';
        $allowed_hosts = $this->get_package_hosts();

        if ( ! $addon || 'plugin' !== $addon['distribution'] || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) || ! in_array( $host, $allowed_hosts, true ) ) {
            $this->redirect_after_install( false );
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        $skin     = new \Automatic_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader( $skin );
        $result   = $upgrader->install( $url );
        $this->redirect_after_install( true === $result );
    }

    private function redirect_after_install( $success ) {
        wp_safe_redirect( add_query_arg( [
            'page' => 'cinderwell',
            'tab'  => 'addons',
            $success ? 'installed' : 'install_error' => '1',
        ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
