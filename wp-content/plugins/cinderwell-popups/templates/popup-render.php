<?php
/**
 * Frontend modal template.
 *
 * @var int    $popup_id
 * @var string $title
 * @var string $content
 * @var string $animation
 * @var string $width
 *
 * @package Cinderwell_Popups
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	id="cinderwell-popup-<?php echo esc_attr( $popup_id ); ?>"
	class="cinderwell-modal cinderwell-modal--width-<?php echo esc_attr( $width ); ?> cinderwell-modal--anim-<?php echo esc_attr( $animation ); ?>"
	data-cinderwell-modal="<?php echo esc_attr( $popup_id ); ?>"
	hidden
>
	<div class="cinderwell-modal__overlay" data-cinderwell-popup-close aria-hidden="true"></div>
	<div
		class="cinderwell-modal__dialog"
		role="dialog"
		aria-modal="true"
		aria-labelledby="cinderwell-modal-title-<?php echo esc_attr( $popup_id ); ?>"
		tabindex="-1"
	>
		<header class="cinderwell-modal__header">
			<h2 id="cinderwell-modal-title-<?php echo esc_attr( $popup_id ); ?>" class="cinderwell-modal__title screen-reader-text"><?php echo esc_html( $title ); ?></h2>
			<button type="button" class="cinderwell-modal__close" data-cinderwell-popup-close aria-label="<?php esc_attr_e( 'Close popup', 'cinderwell-popups' ); ?>">
				<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>
		</header>
		<div class="cinderwell-modal__content">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered block content. ?>
		</div>
	</div>
</div>
