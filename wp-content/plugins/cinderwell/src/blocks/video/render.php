<?php
/**
 * Server-rendered Cinderwell Video block.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

$source_type = 'embed' === ( $attributes['sourceType'] ?? 'upload' ) ? 'embed' : 'upload';
$aspect     = sanitize_key( $attributes['aspect'] ?? 'wide' );
$fit        = 'cover' === ( $attributes['fit'] ?? 'contain' ) ? 'cover' : 'contain';
$width      = sanitize_key( $attributes['width'] ?? 'wide' );
$classes    = 'cinderwell-video cinderwell-video--aspect-' . $aspect;

foreach ( [ 'desktop', 'tablet', 'mobile' ] as $breakpoint ) {
	foreach ( [ 'top', 'bottom' ] as $edge ) {
		$value = sanitize_key( $attributes['spacingResponsive'][ $breakpoint ][ $edge ] ?? '' );
		if ( $value ) {
			$classes .= ' cw-spacing-' . $breakpoint . '-' . $edge . '-' . $value;
		}
	}
}

$caption_id = wp_unique_id( 'cinderwell-video-caption-' );
$caption    = wp_kses_post( $attributes['caption'] ?? '' );
$wrapper    = get_block_wrapper_attributes( [ 'class' => $classes ] );
?>
<figure <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cinderwell-video__inner" style="max-width:var(--cw-width-<?php echo esc_attr( $width ); ?>)">
		<div class="cinderwell-video__frame">
			<?php if ( 'embed' === $source_type ) : ?>
				<?php
				$embed_url = esc_url_raw( $attributes['embedUrl'] ?? '' );
				$embed     = $embed_url ? wp_oembed_get( $embed_url, [ 'width' => 1280 ] ) : '';
				if ( $embed ) {
					echo wp_kses( $embed, [
						'iframe' => [
							'src' => true, 'title' => true, 'width' => true, 'height' => true,
							'frameborder' => true, 'allow' => true, 'allowfullscreen' => true,
							'loading' => true, 'referrerpolicy' => true,
						],
					] );
				} elseif ( $embed_url ) {
					printf( '<p class="cinderwell-video__fallback"><a href="%1$s">%2$s</a></p>', esc_url( $embed_url ), esc_html__( 'Watch video', 'cinderwell' ) );
				}
				?>
			<?php else : ?>
				<?php $video_url = esc_url( $attributes['videoUrl'] ?? '' ); ?>
				<?php if ( $video_url ) : ?>
					<video
						src="<?php echo $video_url; ?>"
						<?php if ( ! empty( $attributes['posterUrl'] ) ) : ?>poster="<?php echo esc_url( $attributes['posterUrl'] ); ?>"<?php endif; ?>
						<?php if ( $caption ) : ?>aria-describedby="<?php echo esc_attr( $caption_id ); ?>"<?php endif; ?>
						<?php if ( ! empty( $attributes['controls'] ) ) : ?>controls<?php endif; ?>
						<?php if ( ! empty( $attributes['autoplay'] ) ) : ?>autoplay<?php endif; ?>
						<?php if ( ! empty( $attributes['loop'] ) ) : ?>loop<?php endif; ?>
						<?php if ( ! empty( $attributes['muted'] ) || ! empty( $attributes['autoplay'] ) ) : ?>muted<?php endif; ?>
						playsinline
						preload="<?php echo esc_attr( $attributes['preload'] ?? 'metadata' ); ?>"
						style="object-fit:<?php echo esc_attr( $fit ); ?>"
					>
						<?php if ( ! empty( $attributes['captionsUrl'] ) ) : ?>
							<track kind="captions" src="<?php echo esc_url( $attributes['captionsUrl'] ); ?>" srclang="<?php echo esc_attr( $attributes['captionsLanguage'] ?? 'en' ); ?>" label="<?php echo esc_attr( $attributes['captionsLabel'] ?? __( 'English', 'cinderwell' ) ); ?>" default>
						<?php endif; ?>
					</video>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php if ( $caption ) : ?><figcaption id="<?php echo esc_attr( $caption_id ); ?>" class="cinderwell-video__caption"><?php echo $caption; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figcaption><?php endif; ?>
	</div>
</figure>
