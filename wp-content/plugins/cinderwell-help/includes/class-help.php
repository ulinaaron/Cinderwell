<?php
/**
 * Help screen and documentation registry.
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
         * Topic values are stable topic keys from `cinderwell_help_topics`.
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

        $sections = self::get_sections();
        $topics   = self::get_topics( $sections );
        $sections = array_intersect_key( $sections, array_flip( array_unique( array_column( $topics, 'section' ) ) ) );
        ?>
        <div class="wrap cw-help">
            <header class="cw-help__header">
                <div>
                    <span class="cw-help__eyebrow"><?php esc_html_e( 'Cinderwell', 'cinderwell-help' ); ?></span>
                    <h1><?php esc_html_e( 'Help', 'cinderwell-help' ); ?></h1>
                    <p><?php esc_html_e( 'Practical guidance for editing and maintaining this website.', 'cinderwell-help' ); ?></p>
                </div>
                <label class="cw-help__search">
                    <span><?php esc_html_e( 'Search help', 'cinderwell-help' ); ?></span>
                    <input type="search" placeholder="<?php esc_attr_e( 'Search topics…', 'cinderwell-help' ); ?>" data-cw-help-search>
                </label>
            </header>

            <?php do_action( 'cinderwell_help_screen_before', $sections, $topics ); ?>

            <div class="cw-help__layout" data-cw-help-root>
                <nav class="cw-help__nav" aria-label="<?php esc_attr_e( 'Help sections', 'cinderwell-help' ); ?>">
                    <button type="button" class="is-active" data-cw-help-section="all" aria-pressed="true">
                        <?php esc_html_e( 'All topics', 'cinderwell-help' ); ?>
                    </button>
                    <?php foreach ( $sections as $section_key => $section ) : ?>
                        <button type="button" data-cw-help-section="<?php echo esc_attr( $section_key ); ?>" aria-pressed="false">
                            <?php echo esc_html( $section['title'] ); ?>
                        </button>
                    <?php endforeach; ?>
                </nav>

                <main class="cw-help__content">
                    <p class="cw-help__count" aria-live="polite" data-cw-help-count></p>
                    <div class="cw-help__topics">
                        <?php foreach ( $topics as $topic_key => $topic ) : ?>
                            <article class="cw-help-topic" data-cw-help-topic data-section="<?php echo esc_attr( $topic['section'] ); ?>" data-search="<?php echo esc_attr( strtolower( wp_strip_all_tags( $topic['title'] . ' ' . $topic['summary'] . ' ' . $topic['content'] ) ) ); ?>">
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
        $sections = [
            'getting-started' => [
                'title'       => __( 'Getting started', 'cinderwell-help' ),
                'description' => __( 'Find your way around WordPress and the editor.', 'cinderwell-help' ),
                'order'       => 10,
            ],
            'editing-content' => [
                'title'       => __( 'Editing content', 'cinderwell-help' ),
                'description' => __( 'Update words, links, images, and page content.', 'cinderwell-help' ),
                'order'       => 20,
            ],
            'cinderwell-blocks' => [
                'title'       => __( 'Cinderwell blocks', 'cinderwell-help' ),
                'description' => __( 'Use the site’s purpose-built content blocks.', 'cinderwell-help' ),
                'order'       => 30,
            ],
            'site-management' => [
                'title'       => __( 'Site management', 'cinderwell-help' ),
                'description' => __( 'Maintain navigation, reusable content, and publishing quality.', 'cinderwell-help' ),
                'order'       => 40,
            ],
        ];

        $sections = (array) apply_filters( 'cinderwell_help_sections', $sections );
        foreach ( $sections as $key => &$section ) {
            if ( ! is_array( $section ) || empty( $section['title'] ) ) {
                unset( $sections[ $key ] );
                continue;
            }
            $section['order'] = isset( $section['order'] ) ? (int) $section['order'] : 100;
        }
        unset( $section );

        uasort( $sections, static function ( $a, $b ) {
            return $a['order'] <=> $b['order'];
        } );
        return $sections;
    }

    public static function get_topics( $sections = null ) {
        $sections = null === $sections ? self::get_sections() : $sections;
        $topics   = self::default_topics();

        /**
         * Filters the complete Help topic registry.
         *
         * Themes may add, replace, or unset topics. Each topic accepts section,
         * title, summary, content, icon, order, capability, and condition.
         *
         * @param array $topics   Topic definitions keyed by stable slug.
         * @param array $sections Filtered section definitions.
         */
        $topics = (array) apply_filters( 'cinderwell_help_topics', $topics, $sections );

        foreach ( $topics as $key => &$topic ) {
            if ( ! is_array( $topic ) || empty( $topic['title'] ) || empty( $topic['section'] ) || ! isset( $sections[ $topic['section'] ] ) ) {
                unset( $topics[ $key ] );
                continue;
            }
            if ( ! empty( $topic['capability'] ) && ! current_user_can( $topic['capability'] ) ) {
                unset( $topics[ $key ] );
                continue;
            }
            if ( isset( $topic['condition'] ) && is_callable( $topic['condition'] ) && ! call_user_func( $topic['condition'] ) ) {
                unset( $topics[ $key ] );
                continue;
            }
            if ( ! apply_filters( 'cinderwell_help_topic_is_visible', true, $key, $topic ) ) {
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

    private static function default_topics() {
        return [
            'editor-overview' => [
                'section' => 'getting-started',
                'title'   => __( 'Find your way around the editor', 'cinderwell-help' ),
                'summary' => __( 'Understand pages, blocks, the toolbar, and saving.', 'cinderwell-help' ),
                'icon'    => 'dashicons-welcome-learn-more',
                'order'   => 10,
                'content' => __( '<p>Pages are assembled from blocks. Select a block to edit it; use List View when you need to select a nested block or understand the page structure.</p><ol><li>Open <strong>Pages</strong> and choose a page.</li><li>Select the content you want to update.</li><li>Use the block toolbar and sidebar for the controls available to your role.</li><li>Use <strong>Preview</strong>, then <strong>Save</strong> when the change is ready.</li></ol>', 'cinderwell-help' ),
            ],
            'safe-publishing' => [
                'section' => 'getting-started',
                'title'   => __( 'Preview and publish safely', 'cinderwell-help' ),
                'summary' => __( 'Check desktop and mobile layouts before saving.', 'cinderwell-help' ),
                'icon'    => 'dashicons-visibility',
                'order'   => 20,
                'content' => __( '<p>Preview significant edits before publishing. Check narrow and wide previews, links, spelling, image crops, and headings. WordPress revisions can restore earlier page content when needed.</p>', 'cinderwell-help' ),
            ],
            'edit-text' => [
                'section' => 'editing-content',
                'title'   => __( 'Edit text and headings', 'cinderwell-help' ),
                'summary' => __( 'Make content changes without changing the page structure.', 'cinderwell-help' ),
                'icon'    => 'dashicons-editor-textcolor',
                'order'   => 30,
                'content' => __( '<p>Click directly into editable text and type. Keep headings short and descriptive, and do not choose a heading level for its visual size. Cinderwell applies the site typography automatically.</p>', 'cinderwell-help' ),
            ],
            'edit-links' => [
                'section' => 'editing-content',
                'title'   => __( 'Edit links and buttons', 'cinderwell-help' ),
                'summary' => __( 'Update destinations and write meaningful link text.', 'cinderwell-help' ),
                'icon'    => 'dashicons-admin-links',
                'order'   => 40,
                'content' => __( '<p>Select the linked text or button and use the link control in its toolbar. Confirm the destination in the link editor. Link labels should describe where the link goes; avoid labels such as “click here.”</p>', 'cinderwell-help' ),
            ],
            'images' => [
                'section' => 'editing-content',
                'title'   => __( 'Replace images and write alt text', 'cinderwell-help' ),
                'summary' => __( 'Choose appropriate images while preserving accessibility.', 'cinderwell-help' ),
                'icon'    => 'dashicons-format-image',
                'order'   => 50,
                'content' => __( '<p>Select an image and choose <strong>Replace</strong>. Use a suitably sized image rather than uploading an unnecessarily large original. Describe meaningful images in the alt-text field; leave alt text empty for images that are purely decorative.</p>', 'cinderwell-help' ),
            ],
            'add-blocks' => [
                'section' => 'cinderwell-blocks',
                'title'   => __( 'Add a Cinderwell block', 'cinderwell-help' ),
                'summary' => __( 'Insert components that already match the site system.', 'cinderwell-help' ),
                'icon'    => 'dashicons-screenoptions',
                'order'   => 60,
                'content' => __( '<p>Use the block inserter and browse the Cinderwell category. Start with the block closest to the content’s purpose, then replace its sample content. Available blocks and controls may be intentionally limited for your role.</p>', 'cinderwell-help' ),
            ],
            'move-blocks' => [
                'section' => 'cinderwell-blocks',
                'title'   => __( 'Move, duplicate, or remove content', 'cinderwell-help' ),
                'summary' => __( 'Use List View for predictable structural edits.', 'cinderwell-help' ),
                'icon'    => 'dashicons-move',
                'order'   => 70,
                'content' => __( '<p>Open List View, select the block, and use its options menu to duplicate or remove it. Dragging in List View is usually the clearest way to reorder content. Some structures are locked to protect the design.</p>', 'cinderwell-help' ),
            ],
            'responsive-visibility' => [
                'section' => 'cinderwell-blocks',
                'title'   => __( 'Control responsive visibility', 'cinderwell-help' ),
                'summary' => __( 'Hide supported content at selected screen sizes.', 'cinderwell-help' ),
                'icon'    => 'dashicons-smartphone',
                'order'   => 80,
                'content' => __( '<p>Supported blocks include a <strong>Visibility</strong> panel. Use it only when content truly should not appear at a device size. Important information should remain available to every visitor.</p>', 'cinderwell-help' ),
            ],
            'navigation' => [
                'section'    => 'site-management',
                'title'      => __( 'Edit site navigation', 'cinderwell-help' ),
                'summary'    => __( 'Maintain header links and menus in the Site Editor.', 'cinderwell-help' ),
                'icon'       => 'dashicons-menu-alt3',
                'order'      => 90,
                'capability' => 'edit_theme_options',
                'content'    => __( '<p>Open <strong>Appearance → Editor</strong> and select the header or navigation. Keep labels concise, verify submenu relationships, and preview both desktop and mobile navigation before saving.</p>', 'cinderwell-help' ),
            ],
            'content-quality' => [
                'section' => 'site-management',
                'title'   => __( 'Keep content accessible and useful', 'cinderwell-help' ),
                'summary' => __( 'A short checklist for every update.', 'cinderwell-help' ),
                'icon'    => 'dashicons-universal-access-alt',
                'order'   => 100,
                'content' => __( '<ul><li>Use descriptive headings in a logical order.</li><li>Write link text that makes sense out of context.</li><li>Add alt text when an image communicates information.</li><li>Do not communicate meaning with color alone.</li><li>Preview the page and test every changed link.</li></ul>', 'cinderwell-help' ),
            ],
        ];
    }
}
