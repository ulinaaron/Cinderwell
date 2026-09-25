<?php
/**
 * Gravity Form block render callback.
 *
 * @package Cinderwell
 */

$form_id     = $attributes['formId'] ?? 0;
$show_desc   = $attributes['description'] ?? true;
$ajax        = $attributes['ajax'] ?? true;
$width       = $attributes['width'] ?? 'standard';

$wrapper_classes = 'cinderwell-form';

$wrapper_attr = get_block_wrapper_attributes( [ 'class' => $wrapper_classes ] );

if ( ! $form_id ) {
    printf( '<div %s><p>%s</p></div>', $wrapper_attr, esc_html__( 'No form selected.', 'cinderwell' ) );
    return;
}

if ( ! class_exists( 'GFForms' ) ) {
    printf( '<div %s><p>%s</p></div>', $wrapper_attr, esc_html__( 'Gravity Forms is not installed.', 'cinderwell' ) );
    return;
}

$args = [
    'id'          => $form_id,
    'title'       => 'false',
    'description' => $show_desc ? 'true' : 'false',
    'ajax'        => $ajax ? 'true' : 'false',
];

/**
 * Filter Gravity Forms shortcode arguments.
 *
 * @param array $args      Shortcode arguments.
 * @param int   $form_id   Form ID.
 * @param array $attributes Block attributes.
 */
$args = apply_filters( 'cinderwell_gravity_form_args', $args, $form_id, $attributes );

printf(
    '<div %s><div class="cinderwell-form__inner" style="max-width: var(--cw-width-%s)">%s</div></div>',
    $wrapper_attr,
    esc_attr( $width ),
    do_shortcode( sprintf(
        '[gravityform id="%d" title="%s" description="%s" ajax="%s"]',
        absint( $args['id'] ),
        esc_attr( $args['title'] ),
        esc_attr( $args['description'] ),
        esc_attr( $args['ajax'] )
    ) )
);
