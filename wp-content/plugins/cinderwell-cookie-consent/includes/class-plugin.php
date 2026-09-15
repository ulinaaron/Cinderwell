<?php
/**
 * Add-on coordinator.
 *
 * @package Cinderwell_Cookie_Consent
 */

namespace Cinderwell_Cookie_Consent;

defined( 'ABSPATH' ) || exit;

class Plugin {

    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'cinderwell_help_sections', [ $this, 'add_help_section' ] );
        add_filter( 'cinderwell_help_topics', [ $this, 'add_help_topics' ] );

        new Settings();
        new Assets();
        new Frontend();
    }

    public function add_help_section( $sections ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $sections;
        }
        $sections['cookie-consent'] = [
            'title'       => __( 'Cookie consent', 'cinderwell-cookie-consent' ),
            'description' => __( 'Configure visitor choices and maintain accurate disclosures.', 'cinderwell-cookie-consent' ),
            'order'       => 60,
        ];
        return $sections;
    }

    public function add_help_topics( $topics ) {
        $topics['cookie-consent-setup'] = [
            'section'    => 'cookie-consent',
            'title'      => __( 'Configure cookie consent', 'cinderwell-cookie-consent' ),
            'summary'    => __( 'Set the consent message, expiry, categories, and privacy page.', 'cinderwell-cookie-consent' ),
            'icon'       => 'dashicons-privacy',
            'order'      => 10,
            'capability' => 'manage_options',
            'content'    => sprintf(
                wp_kses_post( __( '<p>Open <a href="%s"><strong>Cinderwell → Cookie Consent</strong></a>. Confirm the public message, consent lifetime, privacy-policy page, and the technologies listed under each optional category.</p><p>Increase the consent version whenever the purposes or vendors change and visitors need to make a fresh choice.</p>', 'cinderwell-cookie-consent' ) ),
                esc_url( admin_url( 'admin.php?page=cinderwell&tab=cookie-consent' ) )
            ),
        ];
        $topics['cookie-consent-audit'] = [
            'section'    => 'cookie-consent',
            'title'      => __( 'Audit consent before launch', 'cinderwell-cookie-consent' ),
            'summary'    => __( 'Verify optional technologies are blocked and accurately disclosed.', 'cinderwell-cookie-consent' ),
            'icon'       => 'dashicons-search',
            'order'      => 20,
            'capability' => 'manage_options',
            'content'    => __( '<p>Test in a clean browser with no saved consent. Reject optional categories and verify that analytics, marketing, embedded media, cookies, and local storage stay inactive. Then grant each category and confirm only its documented technologies load.</p><p>The add-on supplies consent controls, but legal compliance also depends on an accurate vendor inventory, policy, legal basis, audience, and jurisdiction review.</p>', 'cinderwell-cookie-consent' ),
        ];
        return $topics;
    }
}
