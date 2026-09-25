<?php
/**
 * Opt-in entrance animation module.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Animations {

    const OPTION = 'cinderwell_animations_settings';

    public function __construct() {
        add_filter( 'block_type_metadata_settings', [ $this, 'register_attributes' ], 20, 2 );
        add_filter( 'render_block', [ $this, 'render_block' ], 20, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 11 );
        add_action( 'admin_post_cinderwell_save_animations', [ $this, 'save_settings' ] );
        add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );
    }

    public static function get_defaults() {
        return [
            'animation' => 'fade-up',
            'duration'  => 'slow',
            'delay'     => 'none',
        ];
    }

    public static function get_settings() {
        return self::sanitize_settings( get_option( self::OPTION, [] ) );
    }

    public static function sanitize_settings( $value ) {
        $settings = wp_parse_args( (array) $value, self::get_defaults() );
        $settings['animation'] = self::valid_key( $settings['animation'], self::get_presets(), 'fade-up' );
        $settings['duration']  = self::valid_key( $settings['duration'], self::get_durations(), 'slow' );
        $settings['delay']     = self::valid_key( $settings['delay'], self::get_delays(), 'none' );
        return $settings;
    }

    private static function valid_key( $value, $options, $fallback ) {
        $value = sanitize_key( $value );
        return isset( $options[ $value ] ) ? $value : $fallback;
    }

    public static function get_presets() {
        return (array) apply_filters( 'cinderwell_animation_presets', [
            'fade'        => __( 'Fade', 'cinderwell' ),
            'fade-up'     => __( 'Fade up', 'cinderwell' ),
            'fade-down'   => __( 'Fade down', 'cinderwell' ),
            'slide-left'  => __( 'Slide from left', 'cinderwell' ),
            'slide-right' => __( 'Slide from right', 'cinderwell' ),
            'scale'       => __( 'Fade and scale', 'cinderwell' ),
        ] );
    }

    public static function get_durations() {
        return (array) apply_filters( 'cinderwell_animation_durations', [
            'fast'   => __( 'Fast', 'cinderwell' ),
            'normal' => __( 'Normal', 'cinderwell' ),
            'slow'   => __( 'Slow', 'cinderwell' ),
        ] );
    }

    public static function get_delays() {
        return (array) apply_filters( 'cinderwell_animation_delays', [
            'none'   => __( 'None', 'cinderwell' ),
            'fast'   => __( 'Short', 'cinderwell' ),
            'normal' => __( 'Medium', 'cinderwell' ),
            'slow'   => __( 'Long', 'cinderwell' ),
        ] );
    }

    public static function get_supported_blocks() {
        return (array) apply_filters( 'cinderwell_animation_supported_blocks', [
            'cinderwell/accordion',
            'cinderwell/body',
            'cinderwell/button',
            'cinderwell/card-grid',
            'cinderwell/columns',
            'cinderwell/cta',
            'cinderwell/divider',
            'cinderwell/faq',
            'cinderwell/gallery',
            'cinderwell/gravity-form',
            'cinderwell/heading',
            'cinderwell/hero',
            'cinderwell/icon',
            'cinderwell/icon-list',
            'cinderwell/image',
            'cinderwell/image-carousel',
            'cinderwell/image-text',
            'cinderwell/link',
            'cinderwell/loop',
            'cinderwell/note',
            'cinderwell/quote',
            'cinderwell/section',
            'cinderwell/tabs',
        ] );
    }

    public static function get_section_selectors() {
        return (array) apply_filters( 'cinderwell_animation_section_selectors', [
            'cinderwell/accordion'      => '.cinderwell-accordion__items > *',
            'cinderwell/card-grid'      => '.cinderwell-card-grid__grid > *',
            'cinderwell/columns'        => '.cinderwell-columns__grid > .cinderwell-column',
            'cinderwell/faq'            => '.cinderwell-faq__items > *',
            'cinderwell/gallery'        => '.cinderwell-gallery__grid > *',
            'cinderwell/icon-list'      => '.cinderwell-icon-list__items > *',
            'cinderwell/image-carousel' => '.cinderwell-image-carousel__track > *',
            'cinderwell/loop'           => '.cinderwell-loop__grid > *',
        ] );
    }

    public static function get_editor_settings() {
        $settings = self::get_settings();
        return [
            'enabled'          => true,
            'defaults'         => $settings,
            'presets'          => self::options_for_editor( self::get_presets() ),
            'durations'        => self::options_for_editor( self::get_durations() ),
            'delays'           => self::options_for_editor( self::get_delays() ),
            'supportedBlocks'  => array_values( self::get_supported_blocks() ),
            'sectionSelectors' => self::get_section_selectors(),
        ];
    }

    private static function options_for_editor( $options ) {
        $output = [];
        foreach ( $options as $value => $label ) {
            $output[] = [ 'value' => $value, 'label' => $label ];
        }
        return $output;
    }

    public function register_attributes( $settings, $metadata ) {
        if ( ! in_array( $metadata['name'] ?? '', self::get_supported_blocks(), true ) ) {
            return $settings;
        }

        $settings['attributes'] = array_merge( $settings['attributes'] ?? [], [
            'cwAnimation'         => [ 'type' => 'string', 'default' => '' ],
            'cwAnimationTarget'   => [ 'type' => 'string', 'default' => 'block' ],
            'cwAnimationDuration' => [ 'type' => 'string', 'default' => '' ],
            'cwAnimationDelay'    => [ 'type' => 'string', 'default' => '' ],
        ] );
        return $settings;
    }

    public function enqueue_assets() {
        $asset_path = CINDERWELL_BUILD_DIR . 'modules/animations/frontend.asset.php';
        $asset      = file_exists( $asset_path ) ? include $asset_path : [ 'dependencies' => [], 'version' => CINDERWELL_VERSION ];

        wp_enqueue_style(
            'cinderwell-animations',
            CINDERWELL_BUILD_URL . 'modules/animations/frontend.css',
            [ 'cinderwell-base' ],
            $asset['version']
        );
        wp_enqueue_script(
            'cinderwell-animations',
            CINDERWELL_BUILD_URL . 'modules/animations/frontend.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
        wp_localize_script( 'cinderwell-animations', 'cinderwellAnimationSettings', [
            'sectionSelectors' => self::get_section_selectors(),
        ] );
        wp_script_add_data( 'cinderwell-animations', 'strategy', 'defer' );
    }

    public function render_block( $content, $block ) {
        $name       = $block['blockName'] ?? '';
        $attributes = $block['attrs'] ?? [];
        $animation  = sanitize_key( $attributes['cwAnimation'] ?? '' );

        if ( ! $animation || ! in_array( $name, self::get_supported_blocks(), true ) || ! isset( self::get_presets()[ $animation ] ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
            return $content;
        }

        $settings = self::get_settings();
        $duration = self::valid_key( $attributes['cwAnimationDuration'] ?? '', self::get_durations(), $settings['duration'] );
        $delay    = self::valid_key( $attributes['cwAnimationDelay'] ?? '', self::get_delays(), $settings['delay'] );
        $target   = 'sections' === ( $attributes['cwAnimationTarget'] ?? '' ) ? 'sections' : 'block';
        $html     = new \WP_HTML_Tag_Processor( $content );

        if ( ! $html->next_tag() ) {
            return $content;
        }

        $html->add_class( 'cw-animation-root' );
        $html->set_attribute( 'data-cw-animation', $animation );
        $html->set_attribute( 'data-cw-animation-target', $target );
        $html->set_attribute( 'data-cw-animation-duration', $duration );
        $html->set_attribute( 'data-cw-animation-delay', $delay );
        $html->set_attribute( 'data-cw-animation-block', $name );
        return $html->get_updated_html();
    }

    public function add_settings_tab( $tabs ) {
        $tabs['animations'] = [
            'label'    => __( 'Animations', 'cinderwell' ),
            'group'    => 'design',
            'callback' => [ $this, 'render_settings' ],
        ];
        return $tabs;
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage animation settings.', 'cinderwell' ) );
        }
        check_admin_referer( 'cinderwell_save_animations' );

        $submitted = isset( $_POST['animations'] ) && is_array( $_POST['animations'] ) ? (array) wp_unslash( $_POST['animations'] ) : [];
        update_option( self::OPTION, self::sanitize_settings( $submitted ) );
        wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'animations', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_settings() {
        $settings = self::get_settings();
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Animation defaults saved.', 'cinderwell' ) . '</p></div>';
        }
        ?>
        <p><?php esc_html_e( 'Choose the site defaults offered to animated blocks. Individual blocks may override these values using the same constrained presets.', 'cinderwell' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="cinderwell_save_animations">
            <?php wp_nonce_field( 'cinderwell_save_animations' ); ?>
            <table class="form-table" role="presentation">
                <?php $this->render_select_row( 'animation', __( 'Default animation', 'cinderwell' ), self::get_presets(), $settings['animation'] ); ?>
                <?php $this->render_select_row( 'duration', __( 'Default duration', 'cinderwell' ), self::get_durations(), $settings['duration'] ); ?>
                <?php $this->render_select_row( 'delay', __( 'Default delay', 'cinderwell' ), self::get_delays(), $settings['delay'] ); ?>
            </table>
            <p class="description"><?php esc_html_e( 'Durations, delays, easing, and movement distance resolve through Cinderwell motion tokens. Visitors who prefer reduced motion see content immediately without movement.', 'cinderwell' ); ?></p>
            <?php submit_button( __( 'Save Animation Defaults', 'cinderwell' ) ); ?>
        </form>
        <?php
    }

    private function render_select_row( $key, $label, $options, $value ) {
        echo '<tr><th scope="row"><label for="cw-animation-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
        echo '<select id="cw-animation-' . esc_attr( $key ) . '" name="animations[' . esc_attr( $key ) . ']">';
        foreach ( $options as $option_value => $option_label ) {
            echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
        }
        echo '</select></td></tr>';
    }

    public static function get_health() {
        return [ 'status' => 'good', 'message' => __( 'Token-based animation presets are ready.', 'cinderwell' ) ];
    }

    public function register_documentation( $registry ) {
        $registry->register_directory( 'cinderwell-animations', CINDERWELL_DIR . 'help/modules/animations' );
    }
}
