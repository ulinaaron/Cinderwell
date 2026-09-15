<?php
/**
 * Visitor consent interface.
 *
 * @package Cinderwell_Cookie_Consent
 */

namespace Cinderwell_Cookie_Consent;

defined( 'ABSPATH' ) || exit;

class Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
        // Render before WordPress prints footer scripts so the controller can
        // initialize immediately without a layout flash.
        add_action( 'wp_footer', [ $this, 'render' ], 5 );
    }

    public function enqueue() {
        $settings = Settings::get();
        if ( ! $settings['enabled'] ) {
            return;
        }
        wp_enqueue_style( 'cinderwell-cookie-consent', CINDERWELL_COOKIE_CONSENT_URL . 'assets/consent.css', [ 'cinderwell-base', 'cinderwell-actions' ], CINDERWELL_COOKIE_CONSENT_VERSION );
        wp_enqueue_script( 'cinderwell-cookie-consent', CINDERWELL_COOKIE_CONSENT_URL . 'assets/consent.js', [], CINDERWELL_COOKIE_CONSENT_VERSION, true );
        wp_add_inline_script( 'cinderwell-cookie-consent', 'window.cinderwellConsentSettings=' . wp_json_encode( [
            'cookieName'     => 'cw_consent',
            'version'        => $settings['consent_version'],
            'expiryDays'     => $settings['expiry_days'],
            'cookieNames'    => [
                'preferences' => $this->lines( $settings['preferences_names'] ),
                'analytics'   => $this->lines( $settings['analytics_names'] ),
                'marketing'   => $this->lines( $settings['marketing_names'] ),
            ],
            'secure'         => is_ssl(),
            'globalPrivacyControl' => true,
        ] ) . ';', 'before' );
    }

    public function render() {
        $settings = Settings::get();
        if ( ! $settings['enabled'] ) {
            return;
        }
        $categories = Settings::categories();
        $policy_url = $settings['privacy_page_id'] && 'publish' === get_post_status( $settings['privacy_page_id'] )
            ? get_permalink( $settings['privacy_page_id'] )
            : '';
        ?>
        <div class="cw-consent" data-cw-consent-root hidden>
            <section class="cw-consent__banner" aria-labelledby="cw-consent-title" aria-describedby="cw-consent-description">
                <div class="cw-consent__copy">
                    <h2 id="cw-consent-title"><?php echo esc_html( $settings['heading'] ); ?></h2>
                    <p id="cw-consent-description"><?php echo esc_html( $settings['message'] ); ?>
                        <?php if ( $policy_url ) : ?><a href="<?php echo esc_url( $policy_url ); ?>"><?php esc_html_e( 'Read our privacy policy', 'cinderwell-cookie-consent' ); ?></a>.<?php endif; ?>
                    </p>
                </div>
                <div class="cw-consent__actions">
                    <button class="btn btn--secondary btn--sm" type="button" data-cw-consent-action="reject"><?php esc_html_e( 'Reject optional', 'cinderwell-cookie-consent' ); ?></button>
                    <button class="btn btn--secondary btn--sm" type="button" data-cw-consent-action="customize"><?php esc_html_e( 'Customize', 'cinderwell-cookie-consent' ); ?></button>
                    <button class="btn btn--secondary btn--sm" type="button" data-cw-consent-action="accept"><?php esc_html_e( 'Accept all', 'cinderwell-cookie-consent' ); ?></button>
                </div>
            </section>
        </div>

        <dialog class="cw-consent-dialog" data-cw-consent-dialog aria-labelledby="cw-consent-dialog-title">
            <form method="dialog" data-cw-consent-form>
                <div class="cw-consent-dialog__header">
                    <h2 id="cw-consent-dialog-title"><?php esc_html_e( 'Cookie settings', 'cinderwell-cookie-consent' ); ?></h2>
                    <button type="button" class="cw-consent-dialog__close" data-cw-consent-action="close" aria-label="<?php esc_attr_e( 'Close cookie settings', 'cinderwell-cookie-consent' ); ?>">&times;</button>
                </div>
                <p><?php esc_html_e( 'Choose which optional technologies this site may use. You can change these choices at any time.', 'cinderwell-cookie-consent' ); ?></p>
                <div class="cw-consent-dialog__categories">
                    <?php foreach ( $categories as $key => $category ) : ?>
                        <div class="cw-consent-category">
                            <div>
                                <h3><?php echo esc_html( $category['label'] ); ?></h3>
                                <p><?php echo esc_html( $category['description'] ); ?></p>
                            </div>
                            <label class="cw-consent-toggle">
                                <span class="screen-reader-text"><?php echo esc_html( $category['label'] ); ?></span>
                                <input type="checkbox" name="<?php echo esc_attr( $key ); ?>" <?php checked( ! empty( $category['required'] ) ); ?> <?php disabled( ! empty( $category['required'] ) ); ?>>
                                <span><?php echo ! empty( $category['required'] ) ? esc_html__( 'Always active', 'cinderwell-cookie-consent' ) : esc_html__( 'Allow', 'cinderwell-cookie-consent' ); ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cw-consent__actions">
                    <button class="btn btn--secondary btn--sm" type="button" data-cw-consent-action="reject"><?php esc_html_e( 'Reject optional', 'cinderwell-cookie-consent' ); ?></button>
                    <button class="btn btn--secondary btn--sm" type="submit" data-cw-consent-action="save"><?php esc_html_e( 'Save choices', 'cinderwell-cookie-consent' ); ?></button>
                    <button class="btn btn--secondary btn--sm" type="button" data-cw-consent-action="accept"><?php esc_html_e( 'Accept all', 'cinderwell-cookie-consent' ); ?></button>
                </div>
                <?php if ( $policy_url ) : ?><p><a href="<?php echo esc_url( $policy_url ); ?>"><?php esc_html_e( 'Privacy policy', 'cinderwell-cookie-consent' ); ?></a></p><?php endif; ?>
            </form>
        </dialog>

        <button type="button" class="cw-consent-manage" data-cw-consent-action="customize" hidden><?php esc_html_e( 'Cookie settings', 'cinderwell-cookie-consent' ); ?></button>
        <noscript><p class="cw-consent-noscript"><?php esc_html_e( 'Optional site technologies are disabled because JavaScript is unavailable.', 'cinderwell-cookie-consent' ); ?></p></noscript>
        <?php
    }

    private function lines( $value ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $value );
        return array_values( array_filter( array_map( 'sanitize_text_field', $lines ) ) );
    }
}
