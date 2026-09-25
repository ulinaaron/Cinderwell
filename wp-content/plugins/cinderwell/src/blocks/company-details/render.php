<?php
/**
 * Company Details dynamic block.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

if ( ! \Cinderwell\Addons::is_enabled( 'company-details' ) ) {
	return;
}

$settings = \Cinderwell\Company_Details::get_settings();
$schedule = \Cinderwell\Company_Details::get_hours_schedule( $settings );
$modes    = [ 'hours', 'contact', 'address', 'all' ];
$layouts  = [ 'stacked', 'card', 'split' ];
$mode     = sanitize_key( $attributes['contentMode'] ?? 'hours' );
$mode     = in_array( $mode, $modes, true ) ? $mode : 'hours';
$layout   = sanitize_key( $attributes['layout'] ?? 'card' );
$layout   = in_array( $layout, $layouts, true ) ? $layout : 'card';

$mode_sections = [
	'hours'   => [ 'hours', 'status', 'phone', 'contact' ],
	'contact' => [ 'description', 'address', 'phone', 'email', 'contact', 'directions' ],
	'address' => [ 'address', 'phone', 'contact', 'directions' ],
	'all'     => [ 'description', 'address', 'phone', 'email', 'hours', 'status', 'contact', 'directions' ],
];
$section_attributes = [
	'description' => 'showDescription',
	'address'     => 'showAddress',
	'phone'       => 'showPhone',
	'email'       => 'showEmail',
	'hours'       => 'showHours',
	'status'      => 'showStatus',
	'contact'     => 'showContact',
	'directions'  => 'showDirections',
];
$shown = static function ( $section ) use ( $attributes, $mode, $mode_sections, $section_attributes ) {
	return in_array( $section, $mode_sections[ $mode ], true ) && ! empty( $attributes[ $section_attributes[ $section ] ] );
};

$address_lines = array_filter( [
	trim( ( $settings['address_line_1'] ?? '' ) . ( ! empty( $settings['address_line_2'] ) ? ', ' . $settings['address_line_2'] : '' ) ),
	trim( ( $settings['locality'] ?? '' ) . ( ! empty( $settings['region'] ) ? ', ' . $settings['region'] : '' ) . ( ! empty( $settings['postal_code'] ) ? ' ' . $settings['postal_code'] : '' ) ),
	$settings['country'] ?? '',
] );

$has_hours      = $shown( 'hours' ) && ( $schedule || ! empty( $settings['hours'] ) );
$has_contact    = ( $shown( 'address' ) && $address_lines ) || ( $shown( 'phone' ) && ! empty( $settings['phone'] ) ) || ( $shown( 'email' ) && ! empty( $settings['email'] ) );
$has_actions    = ( $shown( 'contact' ) && ! empty( $settings['contact_url'] ) ) || ( $shown( 'directions' ) && ! empty( $settings['directions_url'] ) );
$has_description = $shown( 'description' ) && ! empty( $settings['description'] );

if ( ! $has_hours && ! $has_contact && ! $has_actions && ! $has_description ) {
	return;
}

$allowed_backgrounds = \Cinderwell\Design_Tokens::get_color_slugs( 'background' );
$background = sanitize_key( $attributes['background'] ?? 'light' );
$background = in_array( $background, $allowed_backgrounds, true ) ? $background : 'light';
$alignment  = sanitize_key( $attributes['alignment'] ?? 'left' );
$alignment  = in_array( $alignment, [ 'left', 'center', 'right' ], true ) ? $alignment : 'left';
$width      = sanitize_key( $attributes['width'] ?? 'standard' );
$width      = in_array( $width, [ 'narrow', 'standard', 'wide', 'full' ], true ) ? $width : 'standard';
$classes    = "cinderwell-company-details cinderwell-company-details--mode-{$mode} cinderwell-company-details--layout-{$layout} cinderwell-company-details--bg-{$background} cinderwell-company-details--align-{$alignment}";

foreach ( [ 'desktop', 'tablet', 'mobile' ] as $breakpoint ) {
	foreach ( [ 'top', 'bottom' ] as $edge ) {
		$value = sanitize_key( $attributes['spacingResponsive'][ $breakpoint ][ $edge ] ?? '' );
		if ( in_array( $value, [ 'none', 'xs', 'sm', 'md', 'lg', 'xl' ], true ) ) {
			$classes .= " cw-spacing-{$breakpoint}-{$edge}-{$value}";
		}
	}
}

$text_size = sanitize_key( $attributes['textSize'] ?? 'auto' );
if ( in_array( $text_size, [ 'sm', 'md', 'lg' ], true ) ) {
	$classes .= " cinderwell-text-size-{$text_size}";
}
$text_color = sanitize_key( $attributes['textColor'] ?? 'auto' );
if ( in_array( $text_color, \Cinderwell\Design_Tokens::get_color_slugs( 'text' ), true ) ) {
	$classes .= " cinderwell-text-color-{$text_color}";
}

$default_headings = [
	'hours'   => __( 'Business hours', 'cinderwell' ),
	'contact' => __( 'Contact us', 'cinderwell' ),
	'address' => __( 'Visit us', 'cinderwell' ),
	'all'     => __( 'Company details', 'cinderwell' ),
];
$heading       = trim( wp_strip_all_tags( $attributes['heading'] ?? '' ) ) ?: $default_headings[ $mode ];
$heading_level = min( 6, max( 2, absint( $attributes['headingLevel'] ?? 2 ) ) );
$heading_tag   = 'h' . $heading_level;
$heading_style = is_array( $attributes['textStyles']['heading'] ?? null ) ? $attributes['textStyles']['heading'] : [];
$heading_class = 'cinderwell-company-details__heading';
if ( in_array( $heading_style['size'] ?? '', [ 'xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl' ], true ) ) {
	$heading_class .= ' cinderwell-text-size-' . sanitize_key( $heading_style['size'] );
}
if ( in_array( $heading_style['color'] ?? '', \Cinderwell\Design_Tokens::get_color_slugs( 'text' ), true ) ) {
	$heading_class .= ' cinderwell-text-color-' . sanitize_key( $heading_style['color'] );
}

$open_now = $schedule ? \Cinderwell\Company_Details::is_open_now( $schedule ) : null;
$wrapper  = get_block_wrapper_attributes( [ 'class' => $classes ] );
$utc_offset_minutes = (int) floor( ( new \DateTimeImmutable( 'now', wp_timezone() ) )->getOffset() / 60 );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cinderwell-company-details__inner" style="max-width:var(--cw-width-<?php echo esc_attr( $width ); ?>)">
		<?php if ( ! empty( $attributes['showHeading'] ) ) : ?>
			<<?php echo esc_attr( $heading_tag ); ?> class="<?php echo esc_attr( $heading_class ); ?>"><?php echo esc_html( $heading ); ?></<?php echo esc_attr( $heading_tag ); ?>>
		<?php endif; ?>
		<?php if ( $has_description ) : ?>
			<p class="cinderwell-company-details__description"><?php echo esc_html( $settings['description'] ); ?></p>
		<?php endif; ?>
		<div class="cinderwell-company-details__content">
			<?php if ( $has_contact ) : ?>
				<div class="cinderwell-company-details__contact">
					<?php if ( $shown( 'address' ) && $address_lines ) : ?>
						<address><?php echo wp_kses( implode( '<br>', array_map( 'esc_html', $address_lines ) ), [ 'br' => [] ] ); ?></address>
					<?php endif; ?>
					<?php if ( $shown( 'phone' ) && ! empty( $settings['phone'] ) ) : ?>
						<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $settings['phone'] ) ); ?>"><?php echo esc_html( $settings['phone'] ); ?></a>
					<?php endif; ?>
					<?php if ( $shown( 'email' ) && ! empty( $settings['email'] ) ) : ?>
						<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $settings['email'] ) ); ?>"><?php echo esc_html( $settings['email'] ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $has_hours ) : ?>
				<div class="cinderwell-company-details__schedule">
					<?php if ( $shown( 'status' ) && null !== $open_now ) : ?>
						<p
							class="cinderwell-company-details__status<?php echo $open_now ? ' is-open' : ''; ?>"
							role="status"
							aria-live="polite"
							aria-atomic="true"
							data-cw-company-status
							data-schedule="<?php echo esc_attr( wp_json_encode( $schedule ) ); ?>"
							data-timezone="<?php echo esc_attr( wp_timezone_string() ); ?>"
							data-utc-offset="<?php echo esc_attr( $utc_offset_minutes ); ?>"
							data-open-label="<?php esc_attr_e( 'Open now', 'cinderwell' ); ?>"
							data-closed-label="<?php esc_attr_e( 'Closed', 'cinderwell' ); ?>"
						><?php echo esc_html( $open_now ? __( 'Open now', 'cinderwell' ) : __( 'Closed', 'cinderwell' ) ); ?></p>
					<?php endif; ?>

					<?php if ( $schedule ) : ?>
						<dl class="cinderwell-company-details__hours">
							<?php foreach ( \Cinderwell\Company_Details::get_weekdays() as $day => $day_label ) : ?>
								<?php if ( empty( $schedule[ $day ] ) ) continue; ?>
								<?php $details = $schedule[ $day ]; ?>
								<div class="cinderwell-company-details__hours-row">
									<dt><?php echo esc_html( $day_label ); ?></dt>
									<dd>
										<?php if ( 'closed' === $details['status'] ) : ?>
											<?php esc_html_e( 'Closed', 'cinderwell' ); ?>
										<?php elseif ( 'all_day' === $details['status'] ) : ?>
											<?php esc_html_e( 'Open 24 hours', 'cinderwell' ); ?>
										<?php else : ?>
											<?php foreach ( $details['periods'] as $index => $period ) : ?>
												<?php if ( $index ) : ?><span class="cinderwell-company-details__period-separator">, </span><?php endif; ?>
												<time datetime="<?php echo esc_attr( $period['opens'] ); ?>"><?php echo esc_html( \Cinderwell\Company_Details::format_business_time( $period['opens'] ) ); ?></time>
												<span aria-hidden="true"> – </span><span class="screen-reader-text"><?php esc_html_e( 'to', 'cinderwell' ); ?> </span>
												<time datetime="<?php echo esc_attr( $period['closes'] ); ?>"><?php echo esc_html( \Cinderwell\Company_Details::format_business_time( $period['closes'] ) ); ?></time>
											<?php endforeach; ?>
										<?php endif; ?>
										<?php if ( ! empty( $details['note'] ) ) : ?><small><?php echo esc_html( $details['note'] ); ?></small><?php endif; ?>
									</dd>
								</div>
							<?php endforeach; ?>
						</dl>
					<?php elseif ( ! empty( $settings['hours'] ) ) : ?>
						<p class="cinderwell-company-details__hours-note"><?php echo nl2br( esc_html( $settings['hours'] ) ); ?></p>
					<?php endif; ?>
					<?php if ( $schedule && ! empty( $settings['hours'] ) ) : ?>
						<p class="cinderwell-company-details__hours-note"><?php echo nl2br( esc_html( $settings['hours'] ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $has_actions ) : ?>
			<div class="cinderwell-company-details__actions cinderwell-buttons">
				<?php if ( $shown( 'contact' ) && ! empty( $settings['contact_url'] ) ) : ?>
					<a class="btn btn--primary btn--md" href="<?php echo esc_url( $settings['contact_url'] ); ?>"><?php echo esc_html( $attributes['contactLabel'] ?? __( 'Contact us', 'cinderwell' ) ); ?></a>
				<?php endif; ?>
				<?php if ( $shown( 'directions' ) && ! empty( $settings['directions_url'] ) ) : ?>
					<a class="btn btn--secondary btn--md" href="<?php echo esc_url( $settings['directions_url'] ); ?>"><?php echo esc_html( $attributes['directionsLabel'] ?? __( 'Get directions', 'cinderwell' ) ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
