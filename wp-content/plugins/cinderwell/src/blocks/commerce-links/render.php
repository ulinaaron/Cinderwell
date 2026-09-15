<?php
/**
 * Lightweight commerce header links.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_cart_url' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
    return;
}

$show_account = ! isset( $attributes['showAccount'] ) || (bool) $attributes['showAccount'];
$show_cart    = ! isset( $attributes['showCart'] ) || (bool) $attributes['showCart'];
$show_count   = ! isset( $attributes['showCount'] ) || (bool) $attributes['showCount'];

if ( ! $show_account && ! $show_cart ) {
    return;
}

$cart_count = 0;
if ( function_exists( 'WC' ) && WC()->cart ) {
    $cart_count = (int) WC()->cart->get_cart_contents_count();
}

$wrapper_attributes = get_block_wrapper_attributes(
    [
        'class'      => 'cinderwell-commerce-links',
        'aria-label' => __( 'Commerce', 'cinderwell' ),
    ]
);
?>
<nav <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php if ( $show_account ) : ?>
        <a class="cinderwell-commerce-links__link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php echo esc_attr( is_user_logged_in() ? __( 'My account', 'cinderwell' ) : __( 'Log in', 'cinderwell' ) ); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="3"></circle><path d="M5.5 20c.35-4 2.5-6 6.5-6s6.15 2 6.5 6"></path></svg>
        </a>
    <?php endif; ?>
    <?php if ( $show_cart ) : ?>
        <a class="cinderwell-commerce-links__link cinderwell-commerce-links__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php echo esc_attr( sprintf( _n( 'Cart, %d item', 'Cart, %d items', $cart_count, 'cinderwell' ), $cart_count ) ); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H6"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg>
            <?php if ( $show_count && $cart_count > 0 ) : ?>
                <span class="cinderwell-commerce-links__count" aria-hidden="true"><?php echo esc_html( $cart_count ); ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>
</nav>
