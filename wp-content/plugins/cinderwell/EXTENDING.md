# Extending Cinderwell

## Admin bar integrations

Cinderwell provides a shared admin-bar root for add-ons. Add nodes during
`cinderwell_admin_bar_menu`; use the supplied root ID rather than relying on a
hard-coded parent value.

```php
add_action( 'cinderwell_admin_bar_menu', function ( $admin_bar, $root_id, $context ) {
    $admin_bar->add_node( [
        'id'     => 'my-cinderwell-addon',
        'parent' => $root_id,
        'title'  => __( 'My add-on', 'my-text-domain' ),
        'href'   => admin_url( 'admin.php?page=my-addon' ),
    ] );
}, 10, 3 );
```

The `$context` array includes `is_admin`, `is_frontend`, `queried_object_id`,
and `request_path`. The root is shown to users with `edit_posts` by default;
filter `cinderwell_admin_bar_capability` to change that policy.

## Public Style Capabilities

Cinderwell registers four stable style handles. Client themes and custom blocks
can depend on the capabilities they use instead of loading the entire design
system stylesheet.

| Handle | Public contract |
|---|---|
| `cinderwell-base` | Design tokens, shared typography, focus states, and layout conventions |
| `cinderwell-actions` | `.btn`, button variants and sizes, and shared button groups |
| `cinderwell-responsive` | Responsive spacing utility classes |
| `cinderwell-media` | Background overlays, image fit/position/aspect utilities, and image radius |

Attach client styling to an existing Cinderwell block with
`wp_enqueue_block_style()` so it remains conditional:

```php
add_action('init', function() {
    wp_enqueue_block_style('cinderwell/hero', [
        'handle' => 'client-cinderwell-hero',
        'src'    => get_theme_file_uri('assets/css/blocks/hero.css'),
        'path'   => get_theme_file_path('assets/css/blocks/hero.css'),
        'deps'   => ['cinderwell-base', 'cinderwell-actions'],
        'ver'    => wp_get_theme()->get('Version'),
    ]);
});
```

Custom block metadata may also reference registered capability handles:

```json
{
    "style": [
        "file:./style-index.css",
        "cinderwell-actions",
        "cinderwell-media"
    ]
}
```

Treat these handles, the documented `--cw-*` properties, and documented utility
classes as public API. Complex selectors inside Cinderwell block styles remain
implementation details.

## Filter Hooks

### `cinderwell_admin_bar_capability`

Change the capability required to see the shared Cinderwell admin-bar root.
The default is `edit_posts`.

### `cinderwell_render_{block_name}`

Override block markup.

```php
add_filter('cinderwell_render_hero', function($html, $attributes) {
    return '<div class="custom-hero">' . $html . '</div>';
}, 10, 2);
```

### `cinderwell_design_tokens`

Customize design tokens (with locale context).

```php
add_filter('cinderwell_design_tokens', function($tokens, $locale) {
    $tokens['cw_color_brand'] = '#1e3a5f';
    if (str_starts_with($locale, 'ar')) {
        $tokens['cw_font_heading'] = '"Noto Sans Arabic", sans-serif';
    }
    return $tokens;
}, 10, 2);
```

### `cinderwell_gravity_form_args`

Customize Gravity Forms shortcode arguments.

```php
add_filter('cinderwell_gravity_form_args', function($args, $form_id, $attributes) {
    if ($form_id === 5) {
        $args['cssClass'] = 'compact-form';
    }
    return $args;
}, 10, 3);
```

### `cinderwell_loop_query_args`

Customize the `WP_Query` arguments used by the Loop block. Receives the query
arguments and block attributes.

### `cinderwell_loop_item_html`

Customize one rendered Loop item. Receives the item HTML, current `WP_Post`,
and block attributes.

### `cinderwell_token_manifest`

Add custom tokens to the manifest.

```php
add_filter('cinderwell_token_manifest', function($manifest) {
    $manifest['cw_button_radius'] = [
        'label' => 'Button border radius',
        'type' => 'text',
        'default' => '4px',
    ];
    return $manifest;
});
```

## Action Hooks

### `cinderwell_admin_bar_menu`

Add admin-bar nodes for an add-on. See the complete example at the top of this
guide. Receives the admin bar, Cinderwell root ID, and current request context.

### `cinderwell_register_blocks`

Register custom blocks.

```php
add_action('cinderwell_register_blocks', function() {
    register_block_type('client-testimonial', []);
});
```

### `cinderwell_register_patterns`

Register custom patterns.

```php
add_action('cinderwell_register_patterns', function() {
    register_block_pattern('cinderwell-client-feature', []);
});
```

## Theme Override Directory

Place custom templates in your theme at:

```
theme/cinderwell/{block-name}/index.html
```

The plugin checks the theme directory first, falling back to plugin defaults.
