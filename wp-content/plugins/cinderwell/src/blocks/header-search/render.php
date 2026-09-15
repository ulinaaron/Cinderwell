<?php
/**
 * Header Search block render callback.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

$label       = sanitize_text_field( $attributes['label'] ?? __( 'Search', 'cinderwell' ) );
$placeholder = sanitize_text_field( $attributes['placeholder'] ?? __( 'Search the site', 'cinderwell' ) );
$button_text = sanitize_text_field( $attributes['buttonText'] ?? __( 'Search', 'cinderwell' ) );
$label       = $label ?: __( 'Search', 'cinderwell' );
$placeholder = $placeholder ?: __( 'Search the site', 'cinderwell' );
$button_text = $button_text ?: __( 'Search', 'cinderwell' );
$panel_id    = wp_unique_id( 'cinderwell-header-search-' );
$wrapper     = get_block_wrapper_attributes( [ 'class' => 'cinderwell-header-search' ] );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<button
		type="button"
		class="cinderwell-header-search__toggle"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $panel_id ); ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
	>
		<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
			<circle cx="11" cy="11" r="6.5"></circle>
			<path d="m16 16 4 4"></path>
		</svg>
	</button>
	<div id="<?php echo esc_attr( $panel_id ); ?>" class="cinderwell-header-search__panel" hidden>
		<form class="cinderwell-header-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="<?php echo esc_attr( $panel_id ); ?>-input"><?php echo esc_html( $label ); ?></label>
			<input
				id="<?php echo esc_attr( $panel_id ); ?>-input"
				class="cinderwell-header-search__input"
				type="search"
				name="s"
				value="<?php echo esc_attr( get_search_query( false ) ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				autocomplete="off"
			>
			<button type="submit" class="btn btn--primary btn--md cinderwell-header-search__submit"><?php echo esc_html( $button_text ); ?></button>
		</form>
	</div>
</div>
