<?php
defined( 'ABSPATH' ) || exit;

define( 'CINDERWELL_MARKETING_VERSION', '0.2.8' );

/**
 * Return the page that anchors the public demonstration area.
 */
function cinderwell_marketing_demo_page_id() {
    static $demo_page_id = null;

    if ( null === $demo_page_id ) {
        $demo_page    = get_page_by_path( 'demo' );
        $demo_page_id = $demo_page instanceof WP_Post ? (int) $demo_page->ID : 0;
    }

    return $demo_page_id;
}

/**
 * Whether the current frontend request belongs to the demonstration site.
 */
function cinderwell_marketing_is_demo_request() {
    if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
        return false;
    }

    $is_commerce = ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
        || ( function_exists( 'is_cart' ) && is_cart() )
        || ( function_exists( 'is_checkout' ) && is_checkout() )
        || ( function_exists( 'is_account_page' ) && is_account_page() );

    if ( $is_commerce ) {
        return true;
    }

    $is_event = post_type_exists( 'cw_event' ) && (
        is_singular( 'cw_event' )
        || is_post_type_archive( 'cw_event' )
        || is_tax( 'cw_event_category' )
    );

    if ( $is_event ) {
        return true;
    }

    $demo_page_id = cinderwell_marketing_demo_page_id();
    $object_id    = get_queried_object_id();
    $is_demo_page = $demo_page_id && $object_id && (
        $object_id === $demo_page_id
        || in_array( $demo_page_id, get_post_ancestors( $object_id ), true )
    );

    return (bool) apply_filters( 'cinderwell_marketing_is_demo_request', $is_demo_page, $object_id, $demo_page_id );
}

/**
 * Swap the shared header part for the demo-specific frame at render time.
 */
add_filter( 'render_block_data', function ( $parsed_block ) {
    if (
        cinderwell_marketing_is_demo_request()
        && 'core/template-part' === ( $parsed_block['blockName'] ?? '' )
        && 'header' === ( $parsed_block['attrs']['slug'] ?? '' )
    ) {
        $parsed_block['attrs']['slug']  = 'header-demo';
        $parsed_block['attrs']['theme'] = 'cinderwell-marketing';
    }

    return $parsed_block;
}, 20 );

add_filter( 'body_class', function ( $classes ) {
    $classes[] = cinderwell_marketing_is_demo_request() ? 'cinderwell-demo' : 'cinderwell-marketing';
    return $classes;
} );

/**
 * Keep retired top-level demo URLs useful after nesting the section.
 */
add_action( 'template_redirect', function () {
    $path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' );
    if ( '' === $path || 0 === strpos( $path, 'demo/' ) ) {
        return;
    }

    $legacy_pages = [
        'about-us',
        'contact-us',
        'insights',
        'people',
        'gravity-form-test',
        'register',
        'my-profile',
        'member-login',
        'members-only-test',
        'shop',
        'cart',
        'checkout',
        'my-account',
        'events',
    ];

    if ( in_array( $path, $legacy_pages, true ) ) {
        wp_safe_redirect( home_url( '/demo/' . trailingslashit( $path ) ), 301 );
        exit;
    }

    foreach ( [ 'product', 'product-category', 'product-tag' ] as $commerce_base ) {
        if ( 0 === strpos( $path, $commerce_base . '/' ) ) {
            wp_safe_redirect( home_url( '/demo/' . trailingslashit( $path ) ), 301 );
            exit;
        }
    }
}, 1 );

/**
 * Frame Event breadcrumbs and archive copy as part of the demonstration site.
 */
add_filter( 'cinderwell_page_header_breadcrumbs', function ( $items, $post_id ) {
    if ( 'cw_event' !== get_post_type( $post_id ) ) {
        return $items;
    }

    return [
        [ 'label' => __( 'Demo', 'cinderwell-marketing' ), 'url' => home_url( '/demo/' ) ],
        [ 'label' => __( 'Events', 'cinderwell-marketing' ), 'url' => get_post_type_archive_link( 'cw_event' ) ],
        [ 'label' => get_the_title( $post_id ), 'url' => '' ],
    ];
}, 30, 2 );

add_filter( 'cinderwell_page_header_context', function ( $data ) {
    if ( is_post_type_archive( 'cw_event' ) ) {
        $data['description'] = __( 'Gather, learn, and connect through upcoming community programs, workshops, and support groups.', 'cinderwell-marketing' );
        $data['breadcrumbs'] = [
            [ 'label' => __( 'Demo', 'cinderwell-marketing' ), 'url' => home_url( '/demo/' ) ],
            [ 'label' => __( 'Events', 'cinderwell-marketing' ), 'url' => '' ],
        ];
    } elseif ( is_tax( 'cw_event_category' ) ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            $data['breadcrumbs'] = [
                [ 'label' => __( 'Demo', 'cinderwell-marketing' ), 'url' => home_url( '/demo/' ) ],
                [ 'label' => __( 'Events', 'cinderwell-marketing' ), 'url' => get_post_type_archive_link( 'cw_event' ) ],
                [ 'label' => $term->name, 'url' => '' ],
            ];
        }
    }

    return $data;
}, 30 );

add_action( 'init', function () {
    register_block_pattern_category( 'cinderwell-marketing', [
        'label' => __( 'Cinderwell Marketing', 'cinderwell-marketing' ),
    ] );
} );

add_action( 'after_setup_theme', function () {
    load_child_theme_textdomain( 'cinderwell-marketing', get_stylesheet_directory() . '/languages' );
    add_editor_style( 'assets/css/marketing.css' );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'cinderwell-marketing',
        get_stylesheet_directory_uri() . '/assets/css/marketing.css',
        [ 'cinderwell-starter-main' ],
        CINDERWELL_MARKETING_VERSION
    );
}, 20 );

// Named product-site treatments are shared by the published page and the
// iframed block editor. Keep their styles separate from the demo site frame.
add_action( 'enqueue_block_assets', function () {
    wp_enqueue_style(
        'cinderwell-marketing-product',
        get_stylesheet_directory_uri() . '/assets/css/product.css',
        [ 'cinderwell-base' ],
        CINDERWELL_MARKETING_VERSION
    );
} );

/**
 * Product-site presentation recipes. Content remains in Cinderwell block
 * attributes; the child theme supplies only named, portable presentations.
 */
add_filter( 'cinderwell_block_variations', function ( $variations, $block_name ) {
    $site_variations = [
        'cinderwell/hero' => [
            'product-canvas' => [
                'label'       => __( 'Product Canvas', 'cinderwell-marketing' ),
                'description' => __( 'A product introduction with a motion or image stage below the story.', 'cinderwell-marketing' ),
                'preview'     => 'hero-product-stage',
                'order'       => 3,
                'custom'      => true,
                'attributes'  => [
                    'width'      => 'wide',
                    'background' => 'light',
                    'alignment'  => 'left',
                    'imageFit'   => 'contain',
                    'imageAspect' => 'auto',
                ],
                'controlled'  => [
                    'typography' => __( 'This product presentation uses its own display type treatment.', 'cinderwell-marketing' ),
                ],
            ],
            'product-stage' => [
                'label'       => __( 'Product Stage', 'cinderwell-marketing' ),
                'description' => __( 'A bold product introduction with a framed interface image.', 'cinderwell-marketing' ),
                'preview'     => 'hero-product-stage',
                'order'       => 5,
                'attributes'  => [
                    'alignment' => 'left',
                    'width'     => 'wide',
                    'imageSide' => 'right',
                    'splitGap'  => 'lg',
                ],
            ],
            'manifesto' => [
                'label'       => __( 'Manifesto', 'cinderwell-marketing' ),
                'description' => __( 'An oversized, type-led statement for a decisive message.', 'cinderwell-marketing' ),
                'preview'     => 'hero-manifesto',
                'order'       => 15,
                'attributes'  => [
                    'alignment' => 'center',
                    'width'     => 'wide',
                    'splitGap'  => 'none',
                ],
            ],
        ],
        'cinderwell/card-grid' => [
            'metrics' => [
                'label'       => __( 'Metrics', 'cinderwell-marketing' ),
                'description' => __( 'A compact proof rail for numbers and short supporting facts.', 'cinderwell-marketing' ),
                'preview'     => 'metrics',
                'order'       => 5,
                'attributes'  => [
                    'columns'       => '4',
                    'columnsTablet' => '2',
                    'columnsMobile' => '1',
                ],
            ],
            'ecosystem' => [
                'label'       => __( 'Ecosystem', 'cinderwell-marketing' ),
                'description' => __( 'A structured directory for product capabilities and add-ons.', 'cinderwell-marketing' ),
                'preview'     => 'ecosystem',
                'order'       => 65,
                'attributes'  => [
                    'columns'       => '3',
                    'columnsTablet' => '2',
                    'columnsMobile' => '1',
                ],
            ],
            'suite' => [
                'label'       => __( 'Suite', 'cinderwell-marketing' ),
                'description' => __( 'A compact catalog for grouped products, add-ons, and integrations.', 'cinderwell-marketing' ),
                'preview'     => 'suite',
                'order'       => 70,
                'attributes'  => [
                    'columns'       => '3',
                    'columnsTablet' => '2',
                    'columnsMobile' => '1',
                ],
            ],
        ],
        'cinderwell/image-text' => [
            'product-showcase' => [
                'label'       => __( 'Product Showcase', 'cinderwell-marketing' ),
                'description' => __( 'A generous interface image paired with focused product copy.', 'cinderwell-marketing' ),
                'preview'     => 'image-text-showcase',
                'order'       => 5,
                'attributes'  => [
                    'alignment'   => 'left',
                    'width'       => 'wide',
                    'imageAspect' => 'landscape',
                    'imageFit'    => 'cover',
                    'imagePosition' => 'center',
                ],
            ],
        ],
        'cinderwell/columns' => [
            'motion-pair' => [
                'label'       => __( 'Motion Pair', 'cinderwell-marketing' ),
                'description' => __( 'A short conceptual clip paired with a focused explanation.', 'cinderwell-marketing' ),
                'order'       => 5,
                'custom'      => true,
                'attributes'  => [
                    'gap'        => 'lg',
                    'width'      => 'wide',
                    'background' => 'white',
                ],
            ],
        ],
        'cinderwell/two-column' => [
            'handoff' => [
                'label'       => __( 'Handoff', 'cinderwell-marketing' ),
                'description' => __( 'Contrasts the editor and developer experience with a strong divide.', 'cinderwell-marketing' ),
                'preview'     => 'two-column-handoff',
                'order'       => 5,
                'attributes'  => [ 'width' => 'wide' ],
            ],
        ],
        'cinderwell/slot-layout' => [
            'architecture' => [
                'label'       => __( 'Architecture', 'cinderwell-marketing' ),
                'description' => __( 'A three-part system map with deliberate hierarchy.', 'cinderwell-marketing' ),
                'preview'     => 'slot-architecture',
                'order'       => 5,
                'attributes'  => [
                    'columns'       => '3',
                    'columnsTablet' => '2',
                    'columnsMobile' => '1',
                    'gap'           => 'md',
                    'width'         => 'wide',
                ],
            ],
        ],
        'cinderwell/cta' => [
            'closing' => [
                'label'       => __( 'Closing Statement', 'cinderwell-marketing' ),
                'description' => __( 'A wide final invitation with emphatic type and actions.', 'cinderwell-marketing' ),
                'preview'     => 'cta-closing',
                'order'       => 5,
                'attributes'  => [
                    'alignment' => 'left',
                    'width'     => 'wide',
                ],
            ],
        ],
    ];

    return isset( $site_variations[ $block_name ] )
        ? array_merge( $variations, $site_variations[ $block_name ] )
        : $variations;
}, 10, 2 );
