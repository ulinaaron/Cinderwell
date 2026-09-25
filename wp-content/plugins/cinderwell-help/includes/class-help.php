<?php
/**
 * Help screen for the shared Cinderwell documentation registry.
 *
 * @package Cinderwell_Help
 */

namespace Cinderwell_Help;

defined( 'ABSPATH' ) || exit;

class Help {

    const PAGE_SLUG = 'cinderwell-help';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'cinderwell_admin_menu_capability', [ $this, 'allow_editor_menu' ] );
        add_action( 'admin_menu', [ $this, 'add_menu' ], 30 );
        add_action( 'admin_menu', [ $this, 'tidy_submenu' ], 999 );
        add_action( 'admin_init', [ $this, 'redirect_editor_root' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
        add_action( 'cinderwell_admin_bar_menu', [ $this, 'add_admin_bar_item' ], 10, 2 );
        add_filter( 'cinderwell_addon_catalog', [ $this, 'enhance_catalog' ] );
    }

    public function allow_editor_menu( $capability ) {
        return $this->get_capability();
    }

    private function get_capability() {
        return (string) apply_filters( 'cinderwell_help_capability', 'edit_posts' );
    }

    public function add_menu() {
        add_submenu_page(
            'cinderwell',
            __( 'Cinderwell Help', 'cinderwell-help' ),
            __( 'Help', 'cinderwell-help' ),
            $this->get_capability(),
            self::PAGE_SLUG,
            [ $this, 'render_page' ]
        );
    }

    public function tidy_submenu() {
        global $submenu;

        if ( empty( $submenu['cinderwell'] ) ) {
            return;
        }

        foreach ( $submenu['cinderwell'] as $index => $item ) {
            if ( isset( $item[2] ) && 'cinderwell' === $item[2] ) {
                if ( current_user_can( 'manage_options' ) ) {
                    $submenu['cinderwell'][ $index ][0] = __( 'Settings', 'cinderwell-help' );
                    $submenu['cinderwell'][ $index ][1] = 'manage_options';
                } else {
                    unset( $submenu['cinderwell'][ $index ] );
                }
                break;
            }
        }
    }

    public function redirect_editor_root() {
        if ( ! is_admin() || current_user_can( 'manage_options' ) || ! current_user_can( $this->get_capability() ) ) {
            return;
        }

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'cinderwell' === $page ) {
            wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
            exit;
        }
    }

    public function enqueue_assets( $hook_suffix ) {
        $help_screen = 'cinderwell_page_' . self::PAGE_SLUG === $hook_suffix;
        if ( ! $help_screen && 'index.php' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'cinderwell-help-admin',
            CINDERWELL_HELP_URL . 'assets/admin.css',
            [],
            CINDERWELL_HELP_VERSION
        );
        if ( $help_screen ) {
            wp_enqueue_script(
                'cinderwell-help-admin',
                CINDERWELL_HELP_URL . 'assets/admin.js',
                [],
                CINDERWELL_HELP_VERSION,
                true
            );
        }
    }

    public function add_dashboard_widget() {
        if ( ! current_user_can( $this->get_capability() ) ) {
            return;
        }

        /**
         * Determines whether the Help dashboard widget is shown.
         *
         * @param bool $show Whether to register the widget.
         */
        if ( ! apply_filters( 'cinderwell_help_show_dashboard_widget', true ) ) {
            return;
        }

        $settings = $this->get_dashboard_widget_settings();
        wp_add_dashboard_widget(
            'cinderwell_help_dashboard',
            $settings['title'],
            [ $this, 'render_dashboard_widget' ],
            null,
            null,
            'normal',
            'high'
        );
    }

    public function render_dashboard_widget() {
        if ( ! current_user_can( $this->get_capability() ) ) {
            return;
        }

        $settings = $this->get_dashboard_widget_settings();
        $topics   = self::get_topics();
        ?>
        <div class="cw-help-dashboard">
            <span class="dashicons dashicons-editor-help cw-help-dashboard__icon" aria-hidden="true"></span>
            <p><?php echo wp_kses_post( $settings['message'] ); ?></p>

            <?php if ( $settings['topics'] ) : ?>
                <ul class="cw-help-dashboard__links">
                    <?php foreach ( $settings['topics'] as $topic_key ) : ?>
                        <?php if ( ! isset( $topics[ $topic_key ] ) ) { continue; } ?>
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '#cw-help-' . $topic_key ) ); ?>">
                                <?php echo esc_html( $topics[ $topic_key ]['title'] ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <p class="cw-help-dashboard__action">
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">
                    <?php echo esc_html( $settings['link_label'] ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    private function get_dashboard_widget_settings() {
        $settings = [
            'title'      => __( 'Website help', 'cinderwell-help' ),
            'message'    => __( 'Need a refresher? Find clear instructions for editing content, working with Cinderwell blocks, and maintaining this website.', 'cinderwell-help' ),
            'link_label' => __( 'Open Cinderwell Help', 'cinderwell-help' ),
            'topics'     => [ 'editor-overview', 'edit-links', 'images' ],
        ];

        /**
         * Filters the Help dashboard widget content and quick links.
         *
         * Topic values are stable IDs from the Cinderwell documentation registry.
         *
         * @param array $settings Widget title, message, link label, and topics.
         */
        $settings = (array) apply_filters( 'cinderwell_help_dashboard_widget', $settings );

        return [
            'title'      => sanitize_text_field( $settings['title'] ?? '' ),
            'message'    => (string) ( $settings['message'] ?? '' ),
            'link_label' => sanitize_text_field( $settings['link_label'] ?? '' ),
            'topics'     => array_values( array_filter( array_map( 'sanitize_key', (array) ( $settings['topics'] ?? [] ) ) ) ),
        ];
    }

    public function add_admin_bar_item( $admin_bar, $root_id ) {
        if ( ! current_user_can( $this->get_capability() ) ) {
            return;
        }

        $admin_bar->add_node( [
            'id'     => 'cinderwell-help',
            'parent' => $root_id,
            'title'  => esc_html__( 'Help', 'cinderwell-help' ),
            'href'   => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
        ] );
    }

    public function enhance_catalog( $catalog ) {
        if ( isset( $catalog['cinderwell-help'] ) ) {
            $catalog['cinderwell-help']['icon']            = 'dashicons-editor-help';
            $catalog['cinderwell-help']['settings_url']    = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
            $catalog['cinderwell-help']['health_callback'] = [ __CLASS__, 'get_health' ];
        }
        return $catalog;
    }

    public static function get_health() {
        return [
            'status'  => 'good',
            'message' => sprintf(
                /* translators: %d: number of visible help topics. */
                _n( '%d help topic is available.', '%d help topics are available.', count( self::get_topics() ), 'cinderwell-help' ),
                count( self::get_topics() )
            ),
        ];
    }

    public function render_page() {
        if ( ! current_user_can( $this->get_capability() ) ) {
            wp_die( esc_html__( 'You do not have permission to view Cinderwell Help.', 'cinderwell-help' ) );
        }

        $sections  = self::get_sections();
        $topics    = self::get_topics( $sections, true );
        $sections  = array_intersect_key( $sections, array_flip( array_unique( array_column( $topics, 'section' ) ) ) );
        $audiences = [
            'user'        => __( 'User', 'cinderwell-help' ),
            'development' => __( 'Development', 'cinderwell-help' ),
        ];
        $audiences = array_intersect_key( $audiences, array_flip( array_unique( array_column( $sections, 'audience' ) ) ) );
        ?>
        <div class="wrap cw-help">
            <header class="cw-help__header">
                <div>
                    <span class="cw-help__eyebrow"><?php esc_html_e( 'Cinderwell', 'cinderwell-help' ); ?></span>
                    <h1><?php esc_html_e( 'Help', 'cinderwell-help' ); ?></h1>
                    <p><?php esc_html_e( 'Practical guidance for using, maintaining, and extending this website.', 'cinderwell-help' ); ?></p>
                </div>
                <label class="cw-help__search">
                    <span><?php esc_html_e( 'Search help', 'cinderwell-help' ); ?></span>
                    <input type="search" placeholder="<?php esc_attr_e( 'Search topics…', 'cinderwell-help' ); ?>" data-cw-help-search>
                </label>
            </header>

            <?php do_action( 'cinderwell_help_screen_before', $sections, $topics ); ?>

            <div class="cw-help__layout" data-cw-help-root>
                <nav class="cw-help__nav" aria-label="<?php esc_attr_e( 'Help sections', 'cinderwell-help' ); ?>">
                    <?php foreach ( $audiences as $audience_key => $audience_label ) : ?>
                        <div class="cw-help__nav-group" data-cw-help-nav-group="<?php echo esc_attr( $audience_key ); ?>">
                            <button type="button" class="cw-help__audience<?php echo 'user' === $audience_key ? ' is-active' : ''; ?>" data-cw-help-audience="<?php echo esc_attr( $audience_key ); ?>" aria-pressed="<?php echo 'user' === $audience_key ? 'true' : 'false'; ?>" aria-expanded="<?php echo 'user' === $audience_key ? 'true' : 'false'; ?>">
                                <?php echo esc_html( $audience_label ); ?>
                            </button>
                            <div class="cw-help__categories" data-cw-help-categories="<?php echo esc_attr( $audience_key ); ?>"<?php echo 'user' === $audience_key ? '' : ' hidden'; ?>>
                                <button type="button" class="is-active" data-cw-help-section="all" data-cw-help-section-audience="<?php echo esc_attr( $audience_key ); ?>" aria-pressed="true">
                                    <?php echo esc_html( sprintf( __( 'All %s topics', 'cinderwell-help' ), strtolower( $audience_label ) ) ); ?>
                                </button>
                                <?php foreach ( $sections as $section_key => $section ) : ?>
                                    <?php if ( $audience_key !== $section['audience'] ) { continue; } ?>
                                    <button type="button" data-cw-help-section="<?php echo esc_attr( $section_key ); ?>" data-cw-help-section-audience="<?php echo esc_attr( $audience_key ); ?>" aria-pressed="false">
                                        <?php echo esc_html( $section['title'] ); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </nav>

                <main class="cw-help__content">
                    <p class="cw-help__count" aria-live="polite" data-cw-help-count></p>
                    <div class="cw-help__topics">
                        <?php foreach ( $topics as $topic_key => $topic ) : ?>
                            <article class="cw-help-topic" data-cw-help-topic data-audience="<?php echo esc_attr( $sections[ $topic['section'] ]['audience'] ); ?>" data-section="<?php echo esc_attr( $topic['section'] ); ?>" data-search="<?php echo esc_attr( strtolower( wp_strip_all_tags( $topic['title'] . ' ' . $topic['summary'] . ' ' . implode( ' ', (array) ( $topic['tags'] ?? [] ) ) . ' ' . $topic['content'] ) ) ); ?>">
                                <details id="cw-help-<?php echo esc_attr( $topic_key ); ?>">
                                    <summary>
                                        <span class="dashicons <?php echo esc_attr( $topic['icon'] ); ?>" aria-hidden="true"></span>
                                        <span>
                                            <strong><?php echo esc_html( $topic['title'] ); ?></strong>
                                            <small><?php echo esc_html( $topic['summary'] ); ?></small>
                                        </span>
                                    </summary>
                                    <div class="cw-help-topic__body">
                                        <?php echo wp_kses_post( $topic['content'] ); ?>
                                    </div>
                                </details>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="cw-help__empty" data-cw-help-empty hidden>
                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                        <h2><?php esc_html_e( 'No matching help topics', 'cinderwell-help' ); ?></h2>
                        <p><?php esc_html_e( 'Try another search or choose a different section.', 'cinderwell-help' ); ?></p>
                    </div>
                </main>
            </div>

            <?php do_action( 'cinderwell_help_screen_after', $sections, $topics ); ?>
        </div>
        <?php
    }

    public static function get_sections() {
        return \Cinderwell\Documentation::instance()->get_sections();
    }

    public static function get_topics( $sections = null, $with_content = false ) {
        $sections = null === $sections ? self::get_sections() : $sections;
        $topics   = \Cinderwell\Documentation::instance()->get_topics( (bool) $with_content );

        foreach ( $topics as $key => &$topic ) {
            if ( ! is_array( $topic ) || empty( $topic['title'] ) || empty( $topic['section'] ) || ! isset( $sections[ $topic['section'] ] ) ) {
                unset( $topics[ $key ] );
                continue;
            }
            if ( ! empty( $topic['capability'] ) && ! current_user_can( $topic['capability'] ) ) {
                unset( $topics[ $key ] );
                continue;
            }
            if ( ! apply_filters( 'cinderwell_documentation_topic_is_visible', true, $key, $topic, 'help' ) ) {
                unset( $topics[ $key ] );
                continue;
            }
            $topic['summary'] = isset( $topic['summary'] ) ? (string) $topic['summary'] : '';
            $topic['content'] = isset( $topic['content'] ) ? (string) $topic['content'] : '';
            $topic['icon']    = isset( $topic['icon'] ) ? sanitize_html_class( $topic['icon'] ) : 'dashicons-editor-help';
            $topic['order']   = isset( $topic['order'] ) ? (int) $topic['order'] : 100;
        }
        unset( $topic );

        uasort( $topics, static function ( $a, $b ) {
            return $a['order'] <=> $b['order'];
        } );
        return $topics;
    }
}
