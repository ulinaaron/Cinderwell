<?php
/**
 * Map dynamic block.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

$sources          = \Cinderwell\Map::get_sources();
$requested_source = sanitize_key( $attributes['source'] ?? 'manual' );
if ( ! isset( $sources[ $requested_source ] ) && 'manual' !== $requested_source ) {
	return;
}
$source           = isset( $sources[ $requested_source ] ) ? $requested_source : 'manual';
$mode             = 'multiple' === ( $attributes['mode'] ?? '' ) || 'manual' !== $source ? 'multiple' : 'single';
$submitted        = 'manual' === $source && is_array( $attributes['locations'] ?? null ) ? $attributes['locations'] : [];

// A saved Locations map remains valid while its optional data provider is off.
// It intentionally emits no public placeholder and resumes when re-enabled.
if ( empty( $sources[ $source ]['enabled'] ) ) {
	return;
}

/**
 * Filters location records rendered by a Map block.
 *
 * Providers and client themes resolve records through Cinderwell\Map so the
 * editor REST preview and published frontend share one sanitized schema.
 */
$submitted = \Cinderwell\Map::resolve_records( $submitted, $attributes );
if ( 'single' === $mode ) {
	$submitted = array_slice( $submitted, 0, 1 );
}

$locations = array_slice( $submitted, 0, 50 );

if ( ! $locations ) return;

$height = sanitize_key( $attributes['height'] ?? 'medium' );
$height = in_array( $height, [ 'small', 'medium', 'large' ], true ) ? $height : 'medium';
$zoom   = min( 19, max( 1, absint( $attributes['zoom'] ?? 14 ) ) );
$width  = sanitize_key( $attributes['width'] ?? 'wide' );
$width  = in_array( $width, [ 'narrow', 'standard', 'wide', 'full' ], true ) ? $width : 'wide';
$label  = sanitize_text_field( $attributes['mapLabel'] ?? __( 'Location map', 'cinderwell' ) );
$directions_label = sanitize_text_field( $attributes['directionsLabel'] ?? __( 'Get directions', 'cinderwell' ) );
$presentations = \Cinderwell\Map::get_presentations();
$presentation = sanitize_key( $attributes['presentation'] ?? 'map' );
$presentation = isset( $presentations[ $presentation ] ) ? $presentation : 'map';
$presentation_base = $presentations[ $presentation ]['base'] ?? 'map';
if ( 'manual' === $source && 'map' !== $presentation_base ) {
	$presentation = 'map';
	$presentation_base = 'map';
}
$enable_search = 'locator' === $presentation_base && ! empty( $attributes['enableSearch'] );
$enable_geolocation = 'locator' === $presentation_base && ! empty( $attributes['enableGeolocation'] );
$search_label = sanitize_text_field( $attributes['searchLabel'] ?? __( 'Search locations', 'cinderwell' ) );
$geolocation_label = sanitize_text_field( $attributes['geolocationLabel'] ?? __( 'Use my location', 'cinderwell' ) );
$empty_label = sanitize_text_field( $attributes['emptyLabel'] ?? __( 'No locations match your search.', 'cinderwell' ) );
$tile_url = (string) apply_filters( 'cinderwell_map_tile_url', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', $attributes );
$tile_test_url = str_replace( [ '{z}', '{x}', '{y}', '{s}', '{r}' ], [ '0', '0', '0', 'a', '' ], $tile_url );
$tile_parts = wp_parse_url( $tile_test_url );
if ( 'https' !== ( $tile_parts['scheme'] ?? '' ) || empty( $tile_parts['host'] ) || preg_match( '/[\s"\'<>\\\\]/', $tile_url ) ) {
	$tile_url = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
}
$attribution = (string) apply_filters( 'cinderwell_map_attribution', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', $attributes );
$data = [
	'locations'       => $locations,
	'zoom'            => $zoom,
	'openPopup'       => 'single' === $mode && ! empty( $attributes['openPopup'] ),
	'directionsLabel' => $directions_label,
	'tileUrl'         => $tile_url,
	'attribution'     => wp_kses( $attribution, [ 'a' => [ 'href' => true ] ] ),
	'presentation'     => $presentation,
	'presentationBase' => $presentation_base,
	'emptyLabel'      => $empty_label,
	'messages'        => [
		'browserUnsupported' => __( 'Your browser does not support location lookup.', 'cinderwell' ),
		'requesting'         => __( 'Requesting your location…', 'cinderwell' ),
		'sorted'             => __( 'Locations sorted nearest first.', 'cinderwell' ),
		'denied'             => __( 'Location access was not allowed. You can still search the directory.', 'cinderwell' ),
		'unavailable'        => __( 'Your location could not be determined. You can still search the directory.', 'cinderwell' ),
		'singleResult'       => __( '1 location shown.', 'cinderwell' ),
		'multipleResults'    => __( '%d locations shown.', 'cinderwell' ),
		'distance'           => __( '%s miles away', 'cinderwell' ),
	],
];
$classes = 'cinderwell-map cinderwell-map--source-' . $source . ' cinderwell-map--mode-' . $mode . ' cinderwell-map--presentation-' . $presentation . ' cinderwell-map--height-' . $height;
foreach ( [ 'desktop', 'tablet', 'mobile' ] as $breakpoint ) {
	foreach ( [ 'top', 'bottom' ] as $edge ) {
		$value = sanitize_key( $attributes['spacingResponsive'][ $breakpoint ][ $edge ] ?? '' );
		if ( in_array( $value, [ 'none', 'xs', 'sm', 'md', 'lg', 'xl' ], true ) ) {
			$classes .= " cw-spacing-{$breakpoint}-{$edge}-{$value}";
		}
	}
}
$wrapper = get_block_wrapper_attributes( [
	'class'       => $classes,
	'data-cw-map' => '',
] );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cinderwell-map__inner<?php echo 'map' !== $presentation_base ? ' cinderwell-map__inner--directory' : ''; ?>" style="max-width:var(--cw-width-<?php echo esc_attr( $width ); ?>)">
		<?php if ( 'locator' === $presentation_base ) : ?>
			<div class="cinderwell-map__locator-tools cinderwell-form" data-cw-map-tools>
				<?php if ( $enable_search ) : ?>
					<label class="cinderwell-map__search"><span><?php echo esc_html( $search_label ); ?></span><input type="search" data-cw-map-search autocomplete="off"></label>
				<?php endif; ?>
				<?php
				$terms = [];
				foreach ( $submitted as $record ) {
					foreach ( (array) ( $record['terms'] ?? [] ) as $term ) {
						if ( ! empty( $term['id'] ) && ! empty( $term['name'] ) ) $terms[ absint( $term['id'] ) ] = sanitize_text_field( $term['name'] );
					}
				}
				if ( count( $terms ) > 1 ) : asort( $terms ); ?>
					<label class="cinderwell-map__category"><span><?php esc_html_e( 'Location category', 'cinderwell' ); ?></span><select data-cw-map-category><option value=""><?php esc_html_e( 'All locations', 'cinderwell' ); ?></option><?php foreach ( $terms as $term_id => $term_name ) : ?><option value="<?php echo esc_attr( $term_id ); ?>"><?php echo esc_html( $term_name ); ?></option><?php endforeach; ?></select></label>
				<?php endif; ?>
				<?php if ( $enable_geolocation ) : ?><button type="button" class="cinderwell-map__locate" data-cw-map-locate><?php echo esc_html( $geolocation_label ); ?></button><?php endif; ?>
				<p class="cinderwell-map__status" data-cw-map-status role="status" aria-live="polite" aria-atomic="true"></p>
			</div>
		<?php endif; ?>
		<div class="cinderwell-map__canvas cinderwell-map__canvas--<?php echo esc_attr( $height ); ?>" data-cw-map-canvas role="region" aria-label="<?php echo esc_attr( $label ); ?>" tabindex="-1"></div>
		<div class="<?php echo 'map' === $presentation_base ? 'cinderwell-map__fallback' : 'cinderwell-map__directory'; ?>" data-cw-map-list>
			<ul data-cw-map-list-items>
				<?php foreach ( $locations as $location ) : ?>
					<li data-cw-map-item data-location-id="<?php echo esc_attr( $location['id'] ); ?>" data-search="<?php echo esc_attr( strtolower( $location['name'] . ' ' . $location['address'] ) ); ?>" data-terms="<?php echo esc_attr( implode( ',', $location['termIds'] ) ); ?>">
						<?php if ( $location['name'] ) : ?><strong><?php if ( $location['url'] ) : ?><a href="<?php echo esc_url( $location['url'] ); ?>"><?php echo esc_html( $location['name'] ); ?></a><?php else : echo esc_html( $location['name'] ); endif; ?></strong><?php endif; ?>
						<?php if ( $location['address'] ) : ?><address><?php echo nl2br( esc_html( $location['address'] ) ); ?></address><?php endif; ?>
						<?php if ( $location['phone'] ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $location['phone'] ) ); ?>"><?php echo esc_html( $location['phone'] ); ?></a><?php endif; ?>
						<?php echo wp_kses_post( (string) apply_filters( 'cinderwell_map_directory_item_content', '', $location, $attributes ) ); ?>
						<div class="cinderwell-map__location-actions"><button type="button" data-cw-map-show><?php esc_html_e( 'Show on map', 'cinderwell' ); ?></button><?php if ( $location['directionsUrl'] ) : ?><a href="<?php echo esc_url( $location['directionsUrl'] ); ?>"><?php echo esc_html( $directions_label ); ?></a><?php endif; ?></div>
						<span class="cinderwell-map__distance" data-cw-map-distance></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="cinderwell-map__empty" data-cw-map-empty hidden><?php echo esc_html( $empty_label ); ?></p>
		</div>
		<script type="application/json" data-cw-map-data><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
	</div>
</section>
