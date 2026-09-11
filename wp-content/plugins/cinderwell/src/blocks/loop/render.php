<?php
/**
 * Loop block render callback.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

$post_type_object = get_post_type_object( $attributes['postType'] ?? 'post' );
$post_type        = $post_type_object && $post_type_object->public ? $post_type_object->name : 'post';
$posts_per_page   = min( 24, max( 1, absint( $attributes['postsPerPage'] ?? 6 ) ) );
$order            = 'asc' === strtolower( $attributes['order'] ?? '' ) ? 'ASC' : 'DESC';
$allowed_orderby  = [ 'date', 'modified', 'title', 'menu_order' ];
$order_by         = in_array( $attributes['orderBy'] ?? '', $allowed_orderby, true ) ? $attributes['orderBy'] : 'date';
$show_pagination  = ! empty( $attributes['showPagination'] );
$current_page     = max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) );

$query_args = [
    'post_type'           => $post_type,
    'post_status'         => 'publish',
    'posts_per_page'      => $posts_per_page,
    'order'               => $order,
    'orderby'             => $order_by,
    'paged'               => $show_pagination ? $current_page : 1,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => ! $show_pagination,
];

$taxonomy = sanitize_key( $attributes['taxonomy'] ?? '' );
$term_id  = absint( $attributes['termId'] ?? 0 );
if ( $taxonomy && $term_id && taxonomy_exists( $taxonomy ) && is_object_in_taxonomy( $post_type, $taxonomy ) ) {
    $query_args['tax_query'] = [
        [
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => [ $term_id ],
        ],
    ];
}

/**
 * Filters the query arguments used by the Cinderwell Loop block.
 *
 * @param array $query_args Query arguments.
 * @param array $attributes Block attributes.
 */
$query_args = apply_filters( 'cinderwell_loop_query_args', $query_args, $attributes );
$loop_query = new WP_Query( $query_args );

$background        = sanitize_key( $attributes['background'] ?? 'white' );
$allowed_surfaces  = [ 'white', 'light', 'dark', 'brand' ];
$background        = in_array( $background, $allowed_surfaces, true ) ? $background : 'white';
$columns           = min( 4, max( 1, absint( $attributes['columns'] ?? 3 ) ) );
$wrapper_classes   = "cinderwell-loop cinderwell-loop--bg-{$background} cinderwell-loop--cols-{$columns}";
$spacing_values    = [ 'none', 'xs', 'sm', 'md', 'lg', 'xl' ];
$responsive_values = $attributes['spacingResponsive'] ?? [];

foreach ( [ 'tablet', 'mobile' ] as $breakpoint ) {
    $attribute_key = 'tablet' === $breakpoint ? 'columnsTablet' : 'columnsMobile';
    $column_value  = absint( $attributes[ $attribute_key ] ?? 0 );
    if ( $column_value >= 1 && $column_value <= 4 ) {
        $wrapper_classes .= " cinderwell-loop--{$breakpoint}-cols-{$column_value}";
    }
}

foreach ( [ 'desktop', 'tablet', 'mobile' ] as $breakpoint ) {
    foreach ( [ 'top', 'bottom' ] as $edge ) {
        $value = $responsive_values[ $breakpoint ][ $edge ] ?? '';
        if ( in_array( $value, $spacing_values, true ) ) {
            $wrapper_classes .= " cw-spacing-{$breakpoint}-{$edge}-{$value}";
        }
    }
}

$text_size  = sanitize_key( $attributes['textSize'] ?? 'auto' );
$text_color = sanitize_key( $attributes['textColor'] ?? 'auto' );
if ( in_array( $text_size, [ 'sm', 'md', 'lg' ], true ) ) {
    $wrapper_classes .= " cinderwell-text-size-{$text_size}";
}
if ( in_array( $text_color, [ 'text', 'brand', 'dark', 'light', 'white' ], true ) ) {
    $wrapper_classes .= " cinderwell-text-color-{$text_color}";
}

$wrapper_options = [ 'class' => $wrapper_classes ];
$background_url  = ! empty( $attributes['backgroundImage'] ) ? esc_url_raw( $attributes['backgroundImageUrl'] ?? '' ) : '';
if ( $background_url ) {
    $fit_options      = [ 'cover', 'contain', 'auto' ];
    $position_options = [
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
    $fit              = in_array( $attributes['backgroundImageFit'] ?? '', $fit_options, true ) ? $attributes['backgroundImageFit'] : 'cover';
    $position_key     = sanitize_key( $attributes['backgroundImagePosition'] ?? 'center' );
    $position         = $position_options[ $position_key ] ?? 'center center';
    $overlay          = in_array( $attributes['backgroundOverlay'] ?? '', [ 'none', 'light', 'medium', 'strong' ], true ) ? $attributes['backgroundOverlay'] : 'none';
    $wrapper_options['class'] .= " cw-has-background-image cw-background-overlay-{$overlay}";
    $wrapper_options['style']  = sprintf( 'background-image:url(%s);background-size:%s;background-position:%s;background-repeat:no-repeat', esc_url( $background_url ), $fit, $position );
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_options );
$width               = sanitize_key( $attributes['width'] ?? 'wide' );
$width               = in_array( $width, [ 'narrow', 'standard', 'wide', 'full' ], true ) ? $width : 'wide';
$image_aspect        = sanitize_key( $attributes['imageAspect'] ?? 'landscape' );
$image_aspect        = in_array( $image_aspect, [ 'landscape', 'square', 'portrait' ], true ) ? $image_aspect : 'landscape';
$excerpt_length      = min( 60, max( 8, absint( $attributes['excerptLength'] ?? 24 ) ) );
$read_more_label     = sanitize_text_field( $attributes['readMoreLabel'] ?? __( 'Read more', 'cinderwell' ) );
$display_taxonomy    = $taxonomy && is_object_in_taxonomy( $post_type, $taxonomy ) ? $taxonomy : ( is_object_in_taxonomy( $post_type, 'category' ) ? 'category' : '' );

$text_style_class = static function ( $part ) use ( $attributes ) {
    $style   = is_array( $attributes['textStyles'][ $part ] ?? null ) ? $attributes['textStyles'][ $part ] : [];
    $classes = [];
    $size    = sanitize_key( $style['size'] ?? 'auto' );
    $color   = sanitize_key( $style['color'] ?? 'auto' );
    if ( in_array( $size, [ 'sm', 'md', 'lg' ], true ) ) {
        $classes[] = "cinderwell-text-size-{$size}";
    }
    if ( in_array( $color, [ 'text', 'brand', 'dark', 'light', 'white' ], true ) ) {
        $classes[] = "cinderwell-text-color-{$color}";
    }
    return $classes ? ' ' . implode( ' ', $classes ) : '';
};
$heading_level      = min( 6, max( 1, absint( $attributes['headingLevel'] ?? 2 ) ) );
$item_heading_level = min( 6, max( 2, absint( $attributes['itemHeadingLevel'] ?? 3 ) ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="cinderwell-loop__inner" style="max-width:var(--cw-width-<?php echo esc_attr( $width ); ?>)">
        <?php if ( ! empty( $attributes['showEyebrow'] ) && ! empty( $attributes['eyebrow'] ) ) : ?>
            <span class="cinderwell-eyebrow<?php echo esc_attr( $text_style_class( 'eyebrow' ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $attributes['eyebrow'] ) ); ?></span>
        <?php endif; ?>
        <?php if ( ! empty( $attributes['showHeading'] ) && ! empty( $attributes['heading'] ) ) : ?>
            <<?php echo esc_attr( "h{$heading_level}" ); ?> class="cinderwell-heading<?php echo esc_attr( $text_style_class( 'heading' ) ); ?>"><?php echo wp_kses_post( $attributes['heading'] ); ?></<?php echo esc_attr( "h{$heading_level}" ); ?>>
        <?php endif; ?>

        <?php if ( $loop_query->have_posts() ) : ?>
            <div class="cinderwell-loop__grid">
                <?php while ( $loop_query->have_posts() ) : $loop_query->the_post(); ?>
                    <?php
                    $post_id    = get_the_ID();
                    $permalink  = get_permalink( $post_id );
                    $title      = get_the_title( $post_id );
                    $item_terms = $display_taxonomy ? get_the_term_list( $post_id, $display_taxonomy, '', ', ' ) : '';
                    ob_start();
                    ?>
                    <article <?php post_class( 'cinderwell-loop__item', $post_id ); ?>>
                        <?php if ( ! empty( $attributes['showFeaturedImage'] ) && has_post_thumbnail( $post_id ) ) : ?>
                            <a class="cinderwell-loop__image cinderwell-loop__image--<?php echo esc_attr( $image_aspect ); ?>" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
                                <?php echo get_the_post_thumbnail( $post_id, 'large', [ 'loading' => 'lazy', 'alt' => get_post_meta( get_post_thumbnail_id( $post_id ), '_wp_attachment_image_alt', true ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </a>
                        <?php endif; ?>
                        <div class="cinderwell-loop__content">
                            <?php if ( ! empty( $attributes['showTerms'] ) && $item_terms ) : ?>
                                <div class="cinderwell-loop__terms"><?php echo wp_kses_post( $item_terms ); ?></div>
                            <?php endif; ?>
                            <?php if ( ! empty( $attributes['showDate'] ) ) : ?>
                                <time class="cinderwell-loop__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $post_id ) ); ?>"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></time>
                            <?php endif; ?>
                            <<?php echo esc_attr( "h{$item_heading_level}" ); ?> class="cinderwell-loop__title<?php echo esc_attr( $text_style_class( 'itemTitle' ) ); ?>"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></<?php echo esc_attr( "h{$item_heading_level}" ); ?>>
                            <?php if ( ! empty( $attributes['showExcerpt'] ) ) : ?>
                                <p class="cinderwell-loop__excerpt<?php echo esc_attr( $text_style_class( 'excerpt' ) ); ?>"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), $excerpt_length ) ); ?></p>
                            <?php endif; ?>
                            <?php if ( ! empty( $attributes['showReadMore'] ) && $read_more_label ) : ?>
                                <a class="cinderwell-loop__read-more" href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $read_more_label ); ?><span class="screen-reader-text"><?php echo esc_html( ': ' . $title ); ?></span></a>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php
                    $item_html = ob_get_clean();
                    /**
                     * Filters one rendered Cinderwell Loop item.
                     *
                     * @param string  $item_html  Item HTML.
                     * @param WP_Post $post       Current post.
                     * @param array   $attributes Block attributes.
                     */
                    echo apply_filters( 'cinderwell_loop_item_html', $item_html, get_post( $post_id ), $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                <?php endwhile; ?>
            </div>

            <?php if ( $show_pagination && $loop_query->max_num_pages > 1 ) : ?>
                <nav class="cinderwell-loop__pagination" aria-label="<?php esc_attr_e( 'Loop pagination', 'cinderwell' ); ?>">
                    <?php
                    echo wp_kses_post( paginate_links( [
                        'current'   => $current_page,
                        'total'     => $loop_query->max_num_pages,
                        'type'      => 'list',
                        'prev_text' => __( 'Previous', 'cinderwell' ),
                        'next_text' => __( 'Next', 'cinderwell' ),
                    ] ) );
                    ?>
                </nav>
            <?php endif; ?>
        <?php else : ?>
            <p class="cinderwell-loop__empty"><?php echo esc_html( $attributes['emptyMessage'] ?? __( 'No items found.', 'cinderwell' ) ); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php wp_reset_postdata(); ?>
