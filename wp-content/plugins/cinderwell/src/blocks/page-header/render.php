<?php
/**
 * Render the dynamic Page Header block.
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
$data    = \Cinderwell\Page_Header::resolve( $attributes, $post_id );

if ( empty( $data['visible'] ) || empty( $data['title'] ) ) {
	return;
}

$classes = sprintf(
	'cinderwell-page-header cinderwell-page-header--bg-%1$s cinderwell-page-header--align-%2$s cw-spacing-desktop-top-%3$s cw-spacing-desktop-bottom-%3$s',
	sanitize_html_class( $data['background'] ),
	sanitize_html_class( $data['alignment'] ),
	sanitize_html_class( $data['spacing'] )
);
$wrapper = get_block_wrapper_attributes( [ 'class' => $classes ] );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cinderwell-page-header__inner" style="max-width:var(--cw-width-<?php echo esc_attr( $data['width'] ); ?>)">
		<?php if ( ! empty( $data['breadcrumbs'] ) ) : ?>
			<nav class="cinderwell-page-header__breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'cinderwell' ); ?>">
				<ol>
					<?php foreach ( $data['breadcrumbs'] as $index => $item ) : ?>
						<li>
							<?php if ( ! empty( $item['url'] ) && $index < count( $data['breadcrumbs'] ) - 1 ) : ?>
								<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
							<?php else : ?>
								<span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>
		<h1 class="cinderwell-page-header__title"><?php echo esc_html( $data['title'] ); ?></h1>
		<?php if ( ! empty( $data['post_date'] ) || ! empty( $data['terms'] ) ) : ?>
			<div class="cinderwell-page-header__meta">
				<?php if ( ! empty( $data['post_date'] ) ) : ?>
					<time class="cinderwell-page-header__date" datetime="<?php echo esc_attr( $data['post_date']['datetime'] ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Published on ', 'cinderwell' ); ?></span><?php echo esc_html( $data['post_date']['label'] ); ?></time>
				<?php endif; ?>
				<?php if ( ! empty( $data['terms'] ) ) : ?>
					<ul class="cinderwell-page-header__terms" aria-label="<?php esc_attr_e( 'Categories', 'cinderwell' ); ?>">
						<?php foreach ( $data['terms'] as $term ) : ?>
							<li><a href="<?php echo esc_url( $term['url'] ); ?>"><?php echo esc_html( $term['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $data['description'] ) ) : ?>
			<div class="cinderwell-page-header__description"><?php echo wp_kses_post( wpautop( $data['description'] ) ); ?></div>
		<?php endif; ?>
	</div>
</section>
