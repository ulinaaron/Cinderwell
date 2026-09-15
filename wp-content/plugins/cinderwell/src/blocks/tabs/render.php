<?php
/**
 * Server-rendered tabs wrapper.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

$allowed_backgrounds = \Cinderwell\Design_Tokens::get_color_slugs( 'background' );
$allowed_text_colors = \Cinderwell\Design_Tokens::get_color_slugs( 'text' );
$allowed_styles      = [ 'underline', 'pills', 'boxed' ];
$allowed_orientation = [ 'horizontal', 'vertical' ];
$allowed_positions   = [ 'start', 'center', 'end', 'stretch' ];
$allowed_vertical_sides = [ 'left', 'right' ];
$background          = in_array( $attributes['background'] ?? '', $allowed_backgrounds, true ) ? $attributes['background'] : 'white';
$tab_style           = in_array( $attributes['tabStyle'] ?? '', $allowed_styles, true ) ? $attributes['tabStyle'] : 'underline';
$orientation         = in_array( $attributes['orientation'] ?? '', $allowed_orientation, true ) ? $attributes['orientation'] : 'horizontal';
$tab_position        = in_array( $attributes['tabPosition'] ?? '', $allowed_positions, true ) ? $attributes['tabPosition'] : 'start';
$vertical_side       = in_array( $attributes['verticalSide'] ?? '', $allowed_vertical_sides, true ) ? $attributes['verticalSide'] : 'left';
$classes             = [
	'cinderwell-tabs',
	'cinderwell-tabs--bg-' . $background,
	'cinderwell-tabs--style-' . $tab_style,
	'cinderwell-tabs--orientation-' . $orientation,
	'cinderwell-tabs--vertical-' . $vertical_side,
	'cinderwell-tabs--position-' . $tab_position,
];

if ( 'individual' === ( $attributes['childBackgroundMode'] ?? 'inherit' ) ) {
	$classes[] = 'cinderwell-tabs--children-own-backgrounds';
}

foreach ( [ 'tablet' => 'Tablet', 'mobile' => 'Mobile' ] as $breakpoint => $suffix ) {
	$responsive_orientation = $attributes[ 'orientation' . $suffix ] ?? 'auto';
	if ( in_array( $responsive_orientation, $allowed_orientation, true ) ) {
		$classes[] = 'cinderwell-tabs--' . $breakpoint . '-orientation-' . $responsive_orientation;
	}
	$responsive_position = $attributes[ 'tabPosition' . $suffix ] ?? 'auto';
	if ( in_array( $responsive_position, $allowed_positions, true ) ) {
		$classes[] = 'cinderwell-tabs--' . $breakpoint . '-position-' . $responsive_position;
	}
}

$spacing_values = [ 'none', 'xs', 'sm', 'md', 'lg', 'xl' ];
foreach ( [ 'desktop', 'tablet', 'mobile' ] as $breakpoint ) {
	foreach ( [ 'top', 'bottom' ] as $edge ) {
		$value = $attributes['spacingResponsive'][ $breakpoint ][ $edge ] ?? '';
		if ( in_array( $value, $spacing_values, true ) ) {
			$classes[] = 'cw-spacing-' . $breakpoint . '-' . $edge . '-' . $value;
		}
	}
}

if ( in_array( $attributes['textSize'] ?? '', [ 'sm', 'md', 'lg' ], true ) ) {
	$classes[] = 'cinderwell-text-size-' . $attributes['textSize'];
}
if ( in_array( $attributes['textColor'] ?? '', $allowed_text_colors, true ) ) {
	$classes[] = 'cinderwell-text-color-' . $attributes['textColor'];
}

$wrapper_style = '';
if ( ! empty( $attributes['backgroundImage'] ) && ! empty( $attributes['backgroundImageUrl'] ) ) {
	$fit_map      = [ 'cover' => 'cover', 'contain' => 'contain' ];
	$position_map = [
		'center'       => 'center center',
		'top'          => 'center top',
		'bottom'       => 'center bottom',
		'left'         => 'left center',
		'right'        => 'right center',
		'top-left'     => 'left top',
		'top-right'    => 'right top',
		'bottom-left'  => 'left bottom',
		'bottom-right' => 'right bottom',
	];
	$overlay       = in_array( $attributes['backgroundOverlay'] ?? '', [ 'none', 'soft', 'medium', 'strong' ], true ) ? $attributes['backgroundOverlay'] : 'none';
	$classes[]     = 'cw-has-background-image';
	$classes[]     = 'cw-background-overlay-' . $overlay;
	$wrapper_style = sprintf(
		'background-image:url("%s");background-size:%s;background-position:%s;background-repeat:no-repeat;',
		esc_url_raw( $attributes['backgroundImageUrl'] ),
		$fit_map[ $attributes['backgroundImageFit'] ?? '' ] ?? 'cover',
		$position_map[ $attributes['backgroundImagePosition'] ?? '' ] ?? 'center center'
	);
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => implode( ' ', $classes ),
		'style' => $wrapper_style ?: null,
	]
);
$text_styles = is_array( $attributes['textStyles'] ?? null ) ? $attributes['textStyles'] : [];
$part_class  = static function ( $slot ) use ( $text_styles, $allowed_text_colors ) {
	$style   = is_array( $text_styles[ $slot ] ?? null ) ? $text_styles[ $slot ] : [];
	$classes = [];
	if ( in_array( $style['size'] ?? '', [ 'sm', 'md', 'lg' ], true ) ) {
		$classes[] = 'cinderwell-text-size-' . $style['size'];
	}
	if ( in_array( $style['color'] ?? '', $allowed_text_colors, true ) ) {
		$classes[] = 'cinderwell-text-color-' . $style['color'];
	}
	return $classes ? ' ' . implode( ' ', $classes ) : '';
};
$inner_blocks = $block instanceof WP_Block ? ( $block->parsed_block['innerBlocks'] ?? [] ) : [];
$tab_items    = array_values(
	array_filter(
		$inner_blocks,
		static function ( $inner_block ) {
			return 'cinderwell/tab-item' === ( $inner_block['blockName'] ?? '' );
		}
	)
);
$tabs_id = wp_unique_id( 'cw-tabs-' );
$width   = in_array( $attributes['width'] ?? '', [ 'narrow', 'standard', 'wide', 'full' ], true ) ? $attributes['width'] : 'standard';
$heading_level = min( 6, max( 1, absint( $attributes['headingLevel'] ?? 2 ) ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-cw-tabs>
	<div class="cinderwell-tabs__inner" style="max-width:var(--cw-width-<?php echo esc_attr( $width ); ?>)">
		<?php if ( ! empty( $attributes['showEyebrow'] ) && ! empty( $attributes['eyebrow'] ) ) : ?>
			<span class="cinderwell-eyebrow<?php echo esc_attr( $part_class( 'eyebrow' ) ); ?>"><?php echo wp_kses_post( $attributes['eyebrow'] ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $attributes['showHeading'] ) && ! empty( $attributes['heading'] ) ) : ?>
				<<?php echo esc_attr( "h{$heading_level}" ); ?> class="cinderwell-heading<?php echo esc_attr( $part_class( 'heading' ) ); ?>"><?php echo wp_kses_post( $attributes['heading'] ); ?></<?php echo esc_attr( "h{$heading_level}" ); ?>>
		<?php endif; ?>
		<div class="cinderwell-tabs__layout">
			<div class="cinderwell-tabs__tablist" role="tablist" aria-label="<?php esc_attr_e( 'Tabbed content', 'cinderwell' ); ?>">
				<?php foreach ( $tab_items as $index => $tab_item ) : ?>
					<?php $label = wp_strip_all_tags( $tab_item['attrs']['label'] ?? sprintf( __( 'Tab %d', 'cinderwell' ), $index + 1 ) ); ?>
					<button type="button" id="<?php echo esc_attr( $tabs_id . '-tab-' . $index ); ?>" class="cinderwell-tabs__tab<?php echo 0 === $index ? ' is-active' : ''; ?><?php echo esc_attr( $part_class( 'tabLabel' ) ); ?>" role="tab" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="cinderwell-tabs__panels"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</div>
		<?php if ( ! empty( $attributes['showFootnote'] ) && ! empty( $attributes['footnote'] ) ) : ?>
			<p class="cinderwell-footnote<?php echo esc_attr( $part_class( 'footnote' ) ); ?>"><?php echo wp_kses_post( $attributes['footnote'] ); ?></p>
		<?php endif; ?>
	</div>
</section>
