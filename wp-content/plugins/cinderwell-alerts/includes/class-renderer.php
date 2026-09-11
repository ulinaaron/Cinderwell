<?php
/**
 * Conditional frontend alert rendering.
 *
 * @package Cinderwell_Alerts
 */

namespace Cinderwell_Alerts;

defined( 'ABSPATH' ) || exit;

class Renderer {

    private $prepared = false;
    private $alerts   = [ 'top' => [], 'bottom' => [] ];
    private $active_posts = [ 'top' => [], 'bottom' => [] ];

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'prepare' ], 5 );
        add_action( 'wp_body_open', [ $this, 'render_top' ], 5 );
        add_action( 'wp_footer', [ $this, 'render_bottom' ], 5 );
    }

    public function prepare() {
        if ( $this->prepared || is_admin() || is_feed() || is_embed() || wp_doing_ajax() ) {
            return;
        }
        $this->prepared = true;

        $posts = get_posts( [
            'post_type'              => Post_Type::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => 50,
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        ] );

        $eligible = array_values( array_filter( $posts, [ Conditions::class, 'is_eligible' ] ) );
        usort( $eligible, static function ( $left, $right ) {
            $priority = (int) get_post_meta( $right->ID, '_cw_alert_priority', true ) <=> (int) get_post_meta( $left->ID, '_cw_alert_priority', true );
            return $priority ?: strcmp( $right->post_modified_gmt, $left->post_modified_gmt );
        } );

        foreach ( $eligible as $post ) {
            $placement = get_post_meta( $post->ID, '_cw_alert_placement', true );
            $placement = in_array( $placement, [ 'top', 'bottom' ], true ) ? $placement : 'top';
            $this->active_posts[ $placement ][] = $post;
        }

        foreach ( [ 'top', 'bottom' ] as $placement ) {
            $limit = max( 1, absint( apply_filters( 'cinderwell_alerts_max_per_placement', 1, $placement ) ) );
            $this->active_posts[ $placement ] = array_slice( $this->active_posts[ $placement ], 0, $limit );
            $this->alerts[ $placement ] = array_values( array_filter( array_map( [ $this, 'render_alert' ], $this->active_posts[ $placement ] ) ) );
        }

        if ( ! $this->alerts['top'] && ! $this->alerts['bottom'] ) {
            return;
        }

        wp_enqueue_style( 'cinderwell-alerts', CINDERWELL_ALERTS_URL . 'assets/css/alerts.css', [ 'cinderwell-base' ], CINDERWELL_ALERTS_VERSION );
        wp_enqueue_script( 'cinderwell-alerts', CINDERWELL_ALERTS_URL . 'assets/js/alerts.js', [], CINDERWELL_ALERTS_VERSION, true );
    }

    /**
     * Get alerts selected for the current request after priority and placement limits.
     *
     * @param string|null $placement Optional top or bottom placement.
     * @return \WP_Post[]
     */
    public function get_active_posts( $placement = null ) {
        if ( ! $this->prepared && ! is_admin() ) {
            $this->prepare();
        }

        if ( in_array( $placement, [ 'top', 'bottom' ], true ) ) {
            return $this->active_posts[ $placement ];
        }

        return array_merge( $this->active_posts['top'], $this->active_posts['bottom'] );
    }

    public function render_top() {
        $this->render_placement( 'top' );
    }

    public function render_bottom() {
        $this->render_placement( 'bottom' );
    }

    private function render_placement( $placement ) {
        if ( ! $this->prepared ) {
            $this->prepare();
        }
        if ( empty( $this->alerts[ $placement ] ) ) {
            return;
        }

        printf( '<div class="cw-alerts cw-alerts--%s">%s</div>', esc_attr( $placement ), implode( '', $this->alerts[ $placement ] ) );
    }

    private function render_alert( $post ) {
        $tone         = get_post_meta( $post->ID, '_cw_alert_tone', true ) ?: 'info';
        $dismissible  = '0' !== (string) get_post_meta( $post->ID, '_cw_alert_dismissible', true );
        $dismiss_days = min( 365, max( 1, absint( get_post_meta( $post->ID, '_cw_alert_dismiss_days', true ) ?: 7 ) ) );
        $show_title   = '0' !== (string) get_post_meta( $post->ID, '_cw_alert_show_title', true );
        $role         = 'critical' === $tone ? 'alert' : 'region';
        $revision     = strtotime( $post->post_modified_gmt . ' UTC' );
        $label        = get_the_title( $post ) ?: __( 'Site notice', 'cinderwell-alerts' );
        $content      = do_blocks( $post->post_content );
        $content      = apply_filters( 'cinderwell_alerts_content', $content, $post );

        ob_start();
        ?>
        <aside
            class="cw-alert cw-alert--<?php echo esc_attr( $tone ); ?>"
            data-cw-alert
            data-cw-alert-key="<?php echo esc_attr( $post->ID . '-' . $revision ); ?>"
            data-cw-alert-dismiss-days="<?php echo esc_attr( $dismiss_days ); ?>"
            role="<?php echo esc_attr( $role ); ?>"
            aria-label="<?php echo esc_attr( $label ); ?>"
        >
            <div class="cw-alert__shell">
                <span class="cw-alert__mark" aria-hidden="true"></span>
                <div class="cw-alert__message">
                    <?php if ( $show_title ) : ?><p class="cw-alert__title"><?php echo esc_html( get_the_title( $post ) ); ?></p><?php endif; ?>
                    <div class="cw-alert__content"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted block output, escaped by its renderers. ?></div>
                </div>
                <?php if ( $dismissible ) : ?>
                    <button type="button" class="cw-alert__dismiss" data-cw-alert-dismiss aria-label="<?php esc_attr_e( 'Dismiss alert', 'cinderwell-alerts' ); ?>">
                        <span aria-hidden="true">×</span>
                    </button>
                <?php endif; ?>
            </div>
        </aside>
        <?php
        return trim( ob_get_clean() );
    }
}
