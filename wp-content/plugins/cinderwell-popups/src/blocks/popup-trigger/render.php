<?php
/**
 * Popup Trigger block render callback.
 *
 * @package Cinderwell_Popups
 */

defined( 'ABSPATH' ) || exit;

$popup_id = absint( $attributes['popupId'] ?? 0 );
$variant  = in_array( $attributes['variant'] ?? '', [ 'primary', 'secondary', 'ghost', 'link' ], true ) ? $attributes['variant'] : 'primary';
$size     = in_array( $attributes['size'] ?? '', [ 'sm', 'md', 'lg' ], true ) ? $attributes['size'] : 'md';
$text     = sanitize_text_field( $attributes['text'] ?? __( 'Open popup', 'cinderwell-popups' ) );
$valid    = $popup_id && 'publish' === get_post_status( $popup_id );
$extra_attributes = [
	'class'                    => 'btn btn--' . $variant . ' btn--' . $size . ' cinderwell-popup-trigger',
	'type'                     => 'button',
];
if ( $valid ) {
	$extra_attributes['data-cinderwell-popup'] = (string) $popup_id;
	$extra_attributes['aria-haspopup']         = 'dialog';
	$extra_attributes['aria-controls']         = 'cinderwell-popup-' . $popup_id;
} else {
	$extra_attributes['disabled'] = 'disabled';
}
$wrapper = get_block_wrapper_attributes( $extra_attributes );
?>
<button <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated attributes. ?>><?php echo esc_html( $text ); ?></button>
