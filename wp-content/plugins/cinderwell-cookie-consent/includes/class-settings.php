<?php
/**
 * Consent settings and privacy-policy guidance.
 *
 * @package Cinderwell_Cookie_Consent
 */

namespace Cinderwell_Cookie_Consent;

defined( 'ABSPATH' ) || exit;

class Settings {

    const OPTION = 'cinderwell_cookie_consent_settings';

    public function __construct() {
        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_tab' ], 30 );
        add_filter( 'cinderwell_addon_catalog', [ $this, 'enhance_catalog' ] );
        add_action( 'admin_post_cinderwell_save_cookie_consent', [ $this, 'save' ] );
        add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );
    }

    public function enhance_catalog( $catalog ) {
        if ( isset( $catalog['cinderwell-cookie-consent'] ) ) {
            $catalog['cinderwell-cookie-consent']['icon']         = 'dashicons-privacy';
            $catalog['cinderwell-cookie-consent']['settings_tab'] = 'cookie-consent';
            $catalog['cinderwell-cookie-consent']['health_callback'] = [ __CLASS__, 'get_health' ];
        }
        return $catalog;
    }

    public static function get_health() {
        $settings = self::get();
        if ( empty( $settings['enabled'] ) ) {
            return [ 'status' => 'warning', 'message' => __( 'Consent controls are disabled.', 'cinderwell-cookie-consent' ) ];
        }
        if ( ! $settings['privacy_page_id'] || 'publish' !== get_post_status( $settings['privacy_page_id'] ) ) {
            return [ 'status' => 'warning', 'message' => __( 'A published privacy policy is still needed.', 'cinderwell-cookie-consent' ) ];
        }
        return [ 'status' => 'good', 'message' => __( 'Consent controls and policy are configured.', 'cinderwell-cookie-consent' ) ];
    }

    public static function defaults() {
        return [
            'enabled'           => true,
            'heading'           => __( 'Your privacy choices', 'cinderwell-cookie-consent' ),
            'message'           => __( 'We use necessary technologies to run this site. With your permission, we may also use optional technologies for preferences, analytics, and marketing.', 'cinderwell-cookie-consent' ),
            'privacy_page_id'   => (int) get_option( 'wp_page_for_privacy_policy' ),
            'consent_version'   => '1',
            'expiry_days'       => 180,
            'preferences_names' => '',
            'analytics_names'   => '',
            'marketing_names'   => '',
        ];
    }

    public static function get() {
        $settings = wp_parse_args( (array) get_option( self::OPTION, [] ), self::defaults() );
        $settings['enabled']         = ! empty( $settings['enabled'] );
        $settings['privacy_page_id'] = absint( $settings['privacy_page_id'] );
        $settings['expiry_days']     = min( 365, max( 1, absint( $settings['expiry_days'] ) ) );
        $settings['consent_version'] = sanitize_text_field( $settings['consent_version'] ) ?: '1';
        return $settings;
    }

    public static function categories() {
        return apply_filters( 'cinderwell_cookie_consent_categories', [
            'necessary' => [
                'label'       => __( 'Necessary', 'cinderwell-cookie-consent' ),
                'description' => __( 'Required for security, consent preferences, shopping carts, and other services you explicitly request. These cannot be disabled.', 'cinderwell-cookie-consent' ),
                'required'    => true,
            ],
            'preferences' => [
                'label'       => __( 'Preferences', 'cinderwell-cookie-consent' ),
                'description' => __( 'Remember choices that change how the site behaves or appears.', 'cinderwell-cookie-consent' ),
                'required'    => false,
            ],
            'analytics' => [
                'label'       => __( 'Analytics', 'cinderwell-cookie-consent' ),
                'description' => __( 'Help the site owner understand visits and improve the experience.', 'cinderwell-cookie-consent' ),
                'required'    => false,
            ],
            'marketing' => [
                'label'       => __( 'Marketing', 'cinderwell-cookie-consent' ),
                'description' => __( 'Support advertising, conversion measurement, and third-party media personalization.', 'cinderwell-cookie-consent' ),
                'required'    => false,
            ],
        ] );
    }

    public function add_tab( $tabs ) {
        $tabs['cookie-consent'] = [
            'label'    => __( 'Cookie Consent', 'cinderwell-cookie-consent' ),
            'group'    => 'extensions',
            'callback' => [ $this, 'render' ],
        ];
        return $tabs;
    }

    public function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage cookie consent settings.', 'cinderwell-cookie-consent' ) );
        }
        check_admin_referer( 'cinderwell_save_cookie_consent' );
        $submitted = isset( $_POST['cookie_consent'] ) ? (array) wp_unslash( $_POST['cookie_consent'] ) : [];
        $settings  = \Cinderwell\Admin_Fields::sanitize_values( $this->fields(), $submitted );
        $settings['expiry_days']       = min( 365, max( 1, absint( $settings['expiry_days'] ) ) );
        $settings['privacy_page_id']   = absint( $settings['privacy_page_id'] );
        $settings['consent_version']   = $settings['consent_version'] ?: '1';
        update_option( self::OPTION, $settings );
        wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'cookie-consent', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render() {
        $settings = self::get();
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Cookie consent settings saved.', 'cinderwell-cookie-consent' ) . '</p></div>';
        }
        echo '<div class="card" style="max-width:820px"><h2>' . esc_html__( 'Cookie Consent', 'cinderwell-cookie-consent' ) . '</h2>';
        echo '<p>' . esc_html__( 'Optional technologies are denied until a visitor makes an affirmative choice. Activation does not replace a site-specific cookie and vendor audit.', 'cinderwell-cookie-consent' ) . '</p>';
        if ( ! $settings['privacy_page_id'] || 'publish' !== get_post_status( $settings['privacy_page_id'] ) ) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Publish and select a complete privacy/cookie policy before launch. The consent banner will not output a broken or private policy link.', 'cinderwell-cookie-consent' ) . '</p></div>';
        }
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="cinderwell_save_cookie_consent">';
        wp_nonce_field( 'cinderwell_save_cookie_consent' );
        \Cinderwell\Admin_Fields::render_table( $this->fields(), $settings, 'cookie_consent', 'cw-cookie-consent' );
        submit_button( __( 'Save Cookie Consent Settings', 'cinderwell-cookie-consent' ) );
        echo '</form></div>';
        echo '<div class="card" style="max-width:820px"><h2>' . esc_html__( 'Implementation checklist', 'cinderwell-cookie-consent' ) . '</h2><ol>';
        echo '<li>' . esc_html__( 'Inventory every cookie, pixel, SDK, iframe, external font, and local-storage use on the production site.', 'cinderwell-cookie-consent' ) . '</li>';
        echo '<li>' . esc_html__( 'Register every optional script and stylesheet with a consent category.', 'cinderwell-cookie-consent' ) . '</li>';
        echo '<li>' . esc_html__( 'List the technologies, purposes, providers, durations, and legal bases in the privacy/cookie policy.', 'cinderwell-cookie-consent' ) . '</li>';
        echo '<li>' . esc_html__( 'Test first visit, accept, reject, granular choices, withdrawal, expiration, and consent-version changes without an existing cookie.', 'cinderwell-cookie-consent' ) . '</li>';
        echo '</ol><p><strong>' . esc_html__( 'The plugin cannot block code that bypasses WordPress and is not marked for consent.', 'cinderwell-cookie-consent' ) . '</strong></p></div>';
    }

    private function fields() {
        $pages = [ 0 => __( 'No privacy-policy link', 'cinderwell-cookie-consent' ) ];
        foreach ( get_pages( [ 'sort_column' => 'post_title' ] ) as $page ) {
            $pages[ $page->ID ] = $page->post_title;
        }
        $cookie_help = __( 'One cookie name per line. Use a trailing * for a prefix, such as _ga*. Matching cookies are removed when this category is rejected or withdrawn.', 'cinderwell-cookie-consent' );
        return [
            'enabled' => [
                'label'          => __( 'Consent interface', 'cinderwell-cookie-consent' ),
                'type'           => 'checkbox',
                'default'        => true,
                'checkbox_label' => __( 'Enable prior blocking and visitor consent controls', 'cinderwell-cookie-consent' ),
            ],
            'heading' => [
                'label'   => __( 'Heading', 'cinderwell-cookie-consent' ),
                'default' => self::defaults()['heading'],
            ],
            'message' => [
                'label'   => __( 'Message', 'cinderwell-cookie-consent' ),
                'type'    => 'textarea',
                'default' => self::defaults()['message'],
            ],
            'privacy_page_id' => [
                'label'   => __( 'Privacy policy', 'cinderwell-cookie-consent' ),
                'type'    => 'select',
                'options' => $pages,
                'default' => (string) get_option( 'wp_page_for_privacy_policy' ),
                'sanitize_callback' => static function ( $value ) {
                    return absint( $value );
                },
            ],
            'consent_version' => [
                'label'       => __( 'Consent version', 'cinderwell-cookie-consent' ),
                'default'     => '1',
                'description' => __( 'Change this value after materially changing vendors or purposes to request fresh consent.', 'cinderwell-cookie-consent' ),
            ],
            'expiry_days' => [
                'label'       => __( 'Remember choice', 'cinderwell-cookie-consent' ),
                'type'        => 'number',
                'default'     => 180,
                'description' => __( 'Days, from 1–365.', 'cinderwell-cookie-consent' ),
            ],
            'preferences_names' => [ 'label' => __( 'Preference cookies', 'cinderwell-cookie-consent' ), 'type' => 'textarea', 'description' => $cookie_help ],
            'analytics_names'   => [ 'label' => __( 'Analytics cookies', 'cinderwell-cookie-consent' ), 'type' => 'textarea', 'description' => $cookie_help ],
            'marketing_names'   => [ 'label' => __( 'Marketing cookies', 'cinderwell-cookie-consent' ), 'type' => 'textarea', 'description' => $cookie_help ],
        ];
    }

    public function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }
        $content = '<p class="privacy-policy-tutorial">' . esc_html__( 'Review and edit this suggested text to accurately name every technology and provider used by this site.', 'cinderwell-cookie-consent' ) . '</p>';
        $content .= '<p>' . esc_html__( 'This site stores a strictly necessary consent-preference cookie named “cw_consent”. It records the categories a visitor accepted or rejected, the consent notice version, and the time of the choice. Optional technologies are withheld until the visitor consents to their category. Visitors can review or withdraw choices using the Cookie settings control.', 'cinderwell-cookie-consent' ) . '</p>';
        wp_add_privacy_policy_content( __( 'Cinderwell Cookie Consent', 'cinderwell-cookie-consent' ), wp_kses_post( wpautop( $content ) ) );
    }
}
