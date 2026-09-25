Extend supported Cinderwell block presentations with
`cinderwell_block_variations`. A variation sets presentation attributes only,
leaving authored content, media, links, queries, and visibility intact when an
editor switches layouts.

```php
add_filter( 'cinderwell_block_variations', function ( $variations, $block_name ) {
    if ( 'cinderwell/hero' !== $block_name ) {
        return $variations;
    }

    $variations['client-feature'] = [
        'label'       => __( 'Client Feature', 'client-theme' ),
        'description' => __( 'A client-owned feature treatment.', 'client-theme' ),
        'preview'     => 'hero-editorial',
        'custom'      => true,
        'attributes'  => [
            'layout'    => 'client-feature',
            'alignment' => 'left',
            'width'     => 'wide',
            'imageSide' => 'right',
        ],
    ];

    return $variations;
}, 10, 2 );
```

Use `custom => true` for every presentation recipe supplied by a client theme
or add-on. Cinderwell owns the resulting editor indicator and behavior; the
client only declares provenance. Core variations omit the flag and normalize
to `false`.

When a recipe materially replaces direct presentation choices, make that
constraint explicit through the registered variation. Scope its shared
editor/frontend CSS to the emitted
`.cinderwell-hero--layout-client-feature` modifier rather than a page slug or
unsupported custom class. Register the same stylesheet through
`enqueue_block_assets` so the editor canvas and published page stay aligned.

Body variations may set `layout`, `width`, and `constrainCopyWidth`. When a
recipe such as a proof rail needs its children to use the full inner width,
register `constrainCopyWidth => false` instead of defeating the standard copy
measure only with frontend CSS.
