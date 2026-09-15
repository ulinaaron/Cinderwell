<?php
/**
 * Utility Bar dynamic render callback.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

$enabled = apply_filters( 'cinderwell_utility_bar_enabled', true, $attributes );
if ( ! $enabled || ! \Cinderwell\Addons::is_enabled( 'company-details' ) ) {
    return;
}

$settings = \Cinderwell\Company_Details::get_settings();
$items    = [];

if ( ! empty( $attributes['showPhone'] ) && ! empty( $settings['phone'] ) ) {
    $items['phone'] = [
        'label' => $settings['phone'],
        'url'   => 'tel:' . preg_replace( '/[^0-9+]/', '', $settings['phone'] ),
    ];
}

if ( ! empty( $attributes['showEmail'] ) && ! empty( $settings['email'] ) ) {
    $items['email'] = [
        'label' => $settings['email'],
        'url'   => 'mailto:' . sanitize_email( $settings['email'] ),
    ];
}

if ( ! empty( $attributes['showContact'] ) && ! empty( $settings['contact_url'] ) ) {
    $items['contact'] = [
        'label' => sanitize_text_field( $attributes['contactLabel'] ?? __( 'Contact', 'cinderwell' ) ),
        'url'   => $settings['contact_url'],
    ];
}

$socials = [];
if ( ! empty( $attributes['showSocials'] ) ) {
    $network_labels = [
        'facebook'  => __( 'Facebook', 'cinderwell' ),
        'instagram' => __( 'Instagram', 'cinderwell' ),
        'linkedin'  => __( 'LinkedIn', 'cinderwell' ),
        'youtube'   => __( 'YouTube', 'cinderwell' ),
        'x'         => __( 'X', 'cinderwell' ),
    ];

    foreach ( $network_labels as $key => $label ) {
        if ( ! empty( $settings[ $key ] ) ) {
            $socials[] = [ 'label' => $label, 'url' => $settings[ $key ] ];
        }
    }

    foreach ( (array) ( $settings['social_profiles'] ?? [] ) as $profile ) {
        $label = sanitize_text_field( $profile['label'] ?? '' );
        $url   = esc_url_raw( $profile['url'] ?? '' );
        if ( $label && $url ) {
            $socials[] = [ 'label' => $label, 'url' => $url ];
        }
    }
}

/**
 * Filters resolved Utility Bar content before it is rendered.
 *
 * @param array $content    Contact items and social profiles.
 * @param array $attributes Block attributes.
 * @param array $settings   Resolved Company Details settings.
 */
$content = apply_filters( 'cinderwell_utility_bar_content', [
    'items'   => $items,
    'socials' => $socials,
], $attributes, $settings );
$items   = is_array( $content['items'] ?? null ) ? $content['items'] : [];
$socials = is_array( $content['socials'] ?? null ) ? $content['socials'] : [];

if ( ! $items && ! $socials ) {
    return;
}

$allowed_backgrounds = [ 'dark', 'brand', 'light', 'white' ];
$background          = sanitize_key( $attributes['background'] ?? 'dark' );
$background          = in_array( $background, $allowed_backgrounds, true ) ? $background : 'dark';
$classes             = "cinderwell-utility-bar cinderwell-utility-bar--bg-{$background}";
if ( ! empty( $attributes['showSocialsMobile'] ) ) {
    $classes .= ' cinderwell-utility-bar--socials-mobile';
}
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => $classes ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="cinderwell-utility-bar__inner">
        <?php if ( $items ) : ?>
            <div class="cinderwell-utility-bar__contact">
                <?php foreach ( $items as $item ) : ?>
                    <?php if ( ! empty( $item['label'] ) && ! empty( $item['url'] ) ) : ?>
                        <a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ( $socials ) : ?>
            <nav class="cinderwell-utility-bar__socials" aria-label="<?php esc_attr_e( 'Company social profiles', 'cinderwell' ); ?>">
                <?php foreach ( $socials as $social ) : ?>
                    <?php if ( ! empty( $social['label'] ) && ! empty( $social['url'] ) ) : ?>
                        <a href="<?php echo esc_url( $social['url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $social['label'] ); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>
