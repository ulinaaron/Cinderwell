<?php
/**
 * Loop block render callback.
 *
 * @package Cinderwell
 */

defined( 'ABSPATH' ) || exit;

$query_mode         = 'inherit' === sanitize_key( $attributes['queryMode'] ?? 'custom' ) ? 'inherit' : 'custom';
$inherited_query    = 'inherit' === $query_mode && isset( $GLOBALS['wp_query'] ) && $GLOBALS['wp_query'] instanceof WP_Query;
$requested_post_type = sanitize_key( $attributes['postType'] ?? 'post' ) ?: 'post';
$post_type_object    = get_post_type_object( $requested_post_type );
$post_type_allowed   = $post_type_object && $post_type_object->public;
$post_type_allowed   = apply_filters( 'cinderwell_loop_post_type_allowed', $post_type_allowed, $post_type_object, $attributes );
$post_type           = $inherited_query ? 'any' : ( $post_type_allowed ? $post_type_object->name : '__cinderwell_unavailable' );
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

$taxonomy = $inherited_query ? '' : sanitize_key( $attributes['taxonomy'] ?? '' );
$term_id  = absint( $attributes['termId'] ?? 0 );
if ( $taxonomy && ! $term_id && is_tax( $taxonomy ) ) {
    $queried_term = get_queried_object();
    $term_id      = $queried_term instanceof WP_Term ? $queried_term->term_id : 0;
}
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
if ( $inherited_query ) {
    $loop_query = clone $GLOBALS['wp_query'];
    $loop_query->rewind_posts();
} else {
    $query_args = apply_filters( 'cinderwell_loop_query_args', $query_args, $attributes );
    $loop_query = new WP_Query( $query_args );
}

$background        = sanitize_key( $attributes['background'] ?? 'white' );
$allowed_surfaces  = \Cinderwell\Design_Tokens::get_color_slugs( 'background' );
$background        = in_array( $background, $allowed_surfaces, true ) ? $background : 'white';
$columns           = min( 4, max( 1, absint( $attributes['columns'] ?? 3 ) ) );
$link_behavior     = in_array( $attributes['linkBehavior'] ?? 'page', [ 'page', 'none' ], true ) ? $attributes['linkBehavior'] : 'page';
$link_behavior     = apply_filters( 'cinderwell_loop_link_behavior', $link_behavior, $post_type, $attributes );
$variation         = sanitize_key( $attributes['variation'] ?? '' );
$requested_layout  = sanitize_key( $attributes['layout'] ?? 'cards' ) ?: 'cards';
$layout_variations = \Cinderwell\Block_Variations::get( 'cinderwell/loop' );
$layout_definition = $layout_variations[ $requested_layout ] ?? ( $layout_variations['cards'] ?? reset( $layout_variations ) );
$resolved_layout   = is_array( $layout_definition ) ? $layout_definition['slug'] : 'cards';
$layout_callback   = is_array( $layout_definition ) ? $layout_definition['render_item_callback'] : null;

if ( is_array( $layout_definition ) && $layout_definition['style_handle'] && wp_style_is( $layout_definition['style_handle'], 'registered' ) ) {
    wp_enqueue_style( $layout_definition['style_handle'] );
}

$link_class        = 'none' === $link_behavior ? 'none' : 'page';
$wrapper_classes   = "cinderwell-loop cinderwell-loop--bg-{$background} cinderwell-loop--cols-{$columns} cinderwell-loop--links-{$link_class}";
$wrapper_classes  .= $variation ? " cinderwell-loop--{$variation}" : '';
$wrapper_classes  .= " cinderwell-loop--layout-{$resolved_layout}";
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
$allowed_text_colors = \Cinderwell\Design_Tokens::get_color_slugs( 'text' );
if ( in_array( $text_color, $allowed_text_colors, true ) ) {
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
$display_taxonomy    = $taxonomy && is_object_in_taxonomy( $post_type, $taxonomy ) ? $taxonomy : ( ! $inherited_query && is_object_in_taxonomy( $post_type, 'category' ) ? 'category' : '' );

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
                <?php $item_index = 0; ?>
                <?php while ( $loop_query->have_posts() ) : $loop_query->the_post(); ?>
                    <?php
                    $post_id    = get_the_ID();
                    $permalink  = get_permalink( $post_id );
                    $title      = get_the_title( $post_id );
                    $item_post_type = get_post_type( $post_id );
                    $item_taxonomy  = $display_taxonomy ?: ( $inherited_query && is_object_in_taxonomy( $item_post_type, 'category' ) ? 'category' : '' );
                    $item_terms     = $item_taxonomy ? get_the_term_list( $post_id, $item_taxonomy, '', ', ' ) : '';
                    $type_object    = get_post_type_object( $item_post_type );
                    $type_label     = $type_object ? $type_object->labels->singular_name : '';
                    $excerpt_text   = wp_trim_words( get_the_excerpt( $post_id ), $excerpt_length );
                    $item_class = 'cinderwell-loop__item';
                    if ( 'featured-lead' === $resolved_layout && 0 === $item_index ) {
                        $item_class .= ' cinderwell-loop__item--featured';
                    }
                    ob_start();
                    ?>
                    <article <?php post_class( $item_class, $post_id ); ?>>
                        <?php if ( ! empty( $attributes['showFeaturedImage'] ) && has_post_thumbnail( $post_id ) ) : ?>
                            <?php if ( 'page' === $link_behavior ) : ?>
                                <a class="cinderwell-loop__image cinderwell-loop__image--<?php echo esc_attr( $image_aspect ); ?>" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
                            <?php else : ?>
                                <div class="cinderwell-loop__image cinderwell-loop__image--<?php echo esc_attr( $image_aspect ); ?>">
                            <?php endif; ?>
                                <?php echo get_the_post_thumbnail( $post_id, 'large', [ 'loading' => 'lazy', 'alt' => get_post_meta( get_post_thumbnail_id( $post_id ), '_wp_attachment_image_alt', true ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php if ( 'page' === $link_behavior ) : ?>
                                </a>
                            <?php else : ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="cinderwell-loop__content">
                            <?php if ( ! empty( $attributes['showContentType'] ) && $type_label ) : ?>
                                <span class="cinderwell-loop__content-type"><?php echo esc_html( $type_label ); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $attributes['showTerms'] ) && $item_terms ) : ?>
                                <div class="cinderwell-loop__terms"><?php echo wp_kses_post( $item_terms ); ?></div>
                            <?php endif; ?>
                            <?php if ( ! empty( $attributes['showDate'] ) ) : ?>
                                <time class="cinderwell-loop__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $post_id ) ); ?>"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></time>
                            <?php endif; ?>
                            <<?php echo esc_attr( "h{$item_heading_level}" ); ?> class="cinderwell-loop__title<?php echo esc_attr( $text_style_class( 'itemTitle' ) ); ?>">
                                <?php if ( 'page' === $link_behavior ) : ?>
                                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html( $title ); ?>
                                <?php endif; ?>
                            </<?php echo esc_attr( "h{$item_heading_level}" ); ?>>
                            <?php do_action( 'cinderwell_loop_item_after_title', $post_id, $attributes ); ?>
                            <?php if ( ! empty( $attributes['showExcerpt'] ) && '' !== trim( $excerpt_text ) ) : ?>
                                <p class="cinderwell-loop__excerpt<?php echo esc_attr( $text_style_class( 'excerpt' ) ); ?>"><?php echo esc_html( $excerpt_text ); ?></p>
                            <?php endif; ?>
                            <?php if ( 'page' === $link_behavior && ! empty( $attributes['showReadMore'] ) && $read_more_label ) : ?>
                                <a class="cinderwell-loop__read-more" href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $read_more_label ); ?><span class="screen-reader-text"><?php echo esc_html( ': ' . $title ); ?></span></a>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php
                    $item_html = ob_get_clean();
                    $context   = [
                        'index'            => $item_index,
                        'layout'           => $resolved_layout,
                        'post_id'          => $post_id,
                        'permalink'        => $permalink,
                        'title'            => $title,
                        'terms_html'       => $item_terms,
                        'image_aspect'     => $image_aspect,
                        'link_behavior'    => $link_behavior,
                        'read_more_label'  => $read_more_label,
                        'item_heading_tag' => "h{$item_heading_level}",
                    ];

                    if ( is_callable( $layout_callback ) ) {
                        $custom_item_html = call_user_func( $layout_callback, get_post( $post_id ), $attributes, $context );
                        if ( is_string( $custom_item_html ) && '' !== trim( $custom_item_html ) ) {
                            $item_html = $custom_item_html;
                        }
                    }

                    /**
                     * Filters one rendered item for a specific Loop layout.
                     *
                     * The dynamic hook suffix is the sanitized layout slug.
                     *
                     * @param string  $item_html  Item HTML.
                     * @param WP_Post $post       Current post.
                     * @param array   $attributes Block attributes.
                     * @param array   $context    Normalized rendering context.
                     */
                    $item_html = apply_filters( "cinderwell_loop_item_html_{$resolved_layout}", $item_html, get_post( $post_id ), $attributes, $context );
                    /**
                     * Filters one rendered Cinderwell Loop item.
                     *
                     * @param string  $item_html  Item HTML.
                     * @param WP_Post $post       Current post.
                     * @param array   $attributes Block attributes.
                     */
                    echo apply_filters( 'cinderwell_loop_item_html', $item_html, get_post( $post_id ), $attributes, $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ++$item_index;
                    ?>
                <?php endwhile; ?>
            </div>

            <?php if ( $show_pagination && $loop_query->max_num_pages > 1 ) : ?>
                <nav class="cinderwell-loop__pagination" aria-label="<?php echo esc_attr( $inherited_query ? __( 'Search results pagination', 'cinderwell' ) : __( 'Loop pagination', 'cinderwell' ) ); ?>">
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
