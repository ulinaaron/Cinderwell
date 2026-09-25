# Extending Cinderwell

## Client theme architecture

Install `cinderwell-starter` unchanged as the updateable **Cinderwell Base**
parent theme. Each client site should activate a child theme rather than a copy
of the parent:

```css
/*
Theme Name: Client Name
Template: cinderwell-starter
Requires Plugins: cinderwell
Text Domain: client-name
*/
```

The client child theme owns its `theme.json`, brand token overrides, patterns,
header/footer variations, and only the templates that genuinely differ. The
Cinderwell plugin owns blocks and third-party integrations such as WooCommerce,
so those fixes reach every client independently of template customization.

Load one client presentation stylesheet through `enqueue_block_assets` with
`cinderwell-base` as a dependency. WordPress then carries that same asset into
the published page and the iframed block editor. Do not split a presentation
recipe between `wp_enqueue_scripts` and `add_editor_style()`; that makes parity
dependent on editor mode and load order.

Brand values are not stylesheet overrides. Register client defaults with
`cinderwell_token_manifest`; Cinderwell will resolve them into both canvases and
use them in block controls, reset values, palette choices, and contrast logic.
The Cinderwell Starter theme also opts into a core bridge that derives the
WordPress `theme.json` palette, content and wide sizes, canvas background and
text colors, and link color from this same registry. Do not repeat these values
in a child `theme.json`; keep that file for typography or settings that are not
represented by Cinderwell tokens.

Site Editor template changes are database records and outrank parent and child
theme files. Use **Cinderwell → Template Updates** to identify these
records. Export intentional changes into the child theme before resetting the
database customization.

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

Cinderwell registers six stable style handles. Client themes and custom blocks
can depend on the capabilities they use instead of loading the entire design
system stylesheet.

| Handle | Public contract |
|---|---|
| `cinderwell-base` | Design tokens, shared typography, focus states, and layout conventions |
| `cinderwell-actions` | `.btn`, button variants and sizes, and shared button groups |
| `cinderwell-responsive` | Responsive spacing utility classes |
| `cinderwell-media` | Background overlays, image fit/position/aspect utilities, and image radius |
| `cinderwell-forms` | Scoped form controls, labels, help text, validation states, and semantic messages for blocks that render a `.cinderwell-form` wrapper |
| `cinderwell-commerce` | WooCommerce product, cart, checkout, account, form, and notice token mappings; registered only when WooCommerce is active |

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

### Form integrations

Form blocks opt into the shared control recipe instead of copying input styles.
Render the integration inside a `.cinderwell-form` wrapper, add the block name
through `cinderwell_form_style_blocks`, and keep vendor-specific selectors in a
stylesheet that depends on `cinderwell-forms`:

```php
add_filter('cinderwell_form_style_blocks', function($blocks) {
    $blocks[] = 'client/forms-provider';
    return $blocks;
});
```

The wrapper exposes form-level aliases such as `--cw-form-control-height`,
`--cw-form-control-font-size`, `--cw-form-control-background`, `--cw-form-control-border`,
`--cw-form-control-border-focus`, `--cw-form-control-radius`, and
`--cw-form-row-gap`. Override those aliases in a client theme when a form needs
a presentation adjustment; do not restyle every input type independently.
Use `.cinderwell-form__label` and `.cinderwell-form__sublabel` when an adapter
needs to distinguish a field label from a nested or compound-field label.

For split-form compositions, place the existing `cinderwell/gravity-form`
block inside a `cinderwell/column`. The nested block retains its normal editor,
rendering, and conditional asset behavior; Columns does not need a form-specific
content type.

### Utility bar

The `cinderwell/utility-bar` block reads phone, email, contact URL, and social
profiles from the enabled Company Details module. It renders no empty shell when
the module is disabled or none of the selected details have values. Add or
remove the block in the Header template part to control it per theme/site.

A child theme can suppress every Utility Bar instance without replacing the
header template:

```php
add_filter( 'cinderwell_utility_bar_enabled', '__return_false' );
```

Use `cinderwell_utility_bar_content` to amend the resolved `items` and `socials`
arrays for client-specific header utilities.

### Page shell spacing

Themes can apply Cinderwell's page-shell tokens to their own custom templates
without hardcoding layout values. Cinderwell's built-in third-party adapters,
including WooCommerce, apply these tokens themselves:

```css
.client-page-shell {
    padding-block: var(--cw-page-padding-block);
    padding-inline: var(--cw-page-padding-inline);
}
```

`--cw-page-padding-block` defaults to `--cw-spacing-md`, while
`--cw-page-padding-inline` defaults to `--cw-layout-gutter`. Client themes can
override either alias independently without changing component spacing.

## Filter Hooks

### `cinderwell_admin_bar_capability`

Change the capability required to see the shared Cinderwell admin-bar root.
The default is `edit_posts`.

### `cinderwell_render_{block_name}`

Filter the rendered markup of a Cinderwell block. Fires on every render, with
or without a theme override file.

```php
add_filter('cinderwell_render_hero', function($html, $attributes) {
    return '<div class="custom-hero">' . $html . '</div>';
}, 10, 2);
```

### `cinderwell_design_tokens`

Customize resolved design tokens (with locale context). Use this for dynamic or
locale-specific changes. Client-theme brand defaults belong in
`cinderwell_token_manifest` so editor controls, reset values, the palette
registry, contrast calculations, and both canvases share the same baseline.

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
block attributes, and normalized rendering context. A layout-specific dynamic
filter is also available as `cinderwell_loop_item_html_{layout-slug}`.

### `cinderwell_block_variations`

Add, replace, reorder, or hide switchable presentation variations. These are
live code definitions: changing a client theme's CSS or render callback updates
every saved block using the same slug without overwriting instance content or
settings. Patterns remain separate composition recipes and may merely seed a
layout when they insert a block.

```php
add_action( 'wp_enqueue_scripts', function () {
    wp_register_style(
        'client-loop-editorial',
        get_theme_file_uri( 'assets/css/loop-editorial.css' ),
        [ 'cinderwell-base' ],
        wp_get_theme()->get( 'Version' )
    );
} );

add_filter( 'cinderwell_block_variations', function ( $variations, $block_name ) {
    if ( 'cinderwell/loop' !== $block_name ) {
        return $variations;
    }

    $variations['client-editorial'] = [
        'label'       => __( 'Editorial', 'client-theme' ),
        'description' => __( 'A client-specific editorial treatment.', 'client-theme' ),
        'preview'     => 'media-list',
        'custom'      => true,
        'order'       => 50,
        'visible'     => true,
        'attributes'  => [
            'layout'          => 'client-editorial',
            'columns'         => '1',
            'columnsTablet'   => '1',
            'columnsMobile'   => '1',
            'imageAspect'     => 'landscape',
        ],
        'style_handle' => 'client-loop-editorial',
        // Optional: return a complete, trusted <article> string.
        'render_item_callback' => 'client_render_loop_item',
    ];

    // Keep saved Minimal List instances working, but remove it from the picker.
    $variations['minimal-list']['visible'] = false;

    return $variations;
}, 10, 2 );
```

Definitions are keyed by stable, sanitized slugs. Reusing a core slug replaces
that definition. Removing a definition makes saved instances fall back to
the block's default and displays an editor warning; prefer `visible => false`
when existing instances should keep rendering.

Set `custom => true` on every client-theme or add-on presentation recipe. This
is a provenance flag: it tells Cinderwell that the variation is not part of the
default block library. Cinderwell normalizes the value and owns its editor
label, styling, accessibility, and any future behavior. Client themes must not
build a separate badge or editor UI for the flag. Core variations omit the flag
and normalize to `false`.

The flag does not loosen the presentation contract. If a recipe materially
replaces or disables direct presentation choices, register it as a named custom
variation and scope the shared editor/frontend stylesheet to the emitted
`.cinderwell-{block}--layout-{slug}` modifier. Do not hide those constraints in
page-specific CSS or an unsupported `className` attribute.

Loop accepts `layout`,
`columns`, `columnsTablet`, `columnsMobile`, and `imageAspect`. Card Grid
accepts `layout`, `columns`, `columnsTablet`, `columnsMobile`, `imageBoxAspect`,
`imageBoxOverlay`, `imageBoxContentPosition`, `imageBoxContentVisibility`, and
`imageBoxLinkStyle`. Hero accepts
`layout`, `alignment`, `width`, `imageSide`, and `splitGap`. Image + Text
accepts `layout`, `alignment`, `width`, `imageAspect`, `imageFit`, and
`imagePosition`. CTA accepts `layout`, `alignment`, and `width`. Body accepts
`layout`, `width`, and `constrainCopyWidth`; use the last only when the named
presentation requires a wider content measure, such as a proof or statistics
rail. Other
attributes are discarded so a visual variation cannot silently change a query
or authored content.

Optional `style_handle` and `editor_style_handle` values must refer to styles
registered by the client theme or add-on. A `render_item_callback` receives the
current `WP_Post`, block attributes, and rendering context. Its returned HTML
is trusted application code and must be escaped appropriately by its owner.
For a structural variation on any supported static block, `render_callback`
receives the saved block HTML, block attributes, and normalized definition, and
must return the complete rendered block HTML. Prefer CSS against the stable
markup whenever possible so editor and frontend previews naturally stay
aligned.

### `cinderwell_loop_link_behavior`

Change whether Loop item titles and images link to individual pages. Receives
`page` or `none`, the post type, and block attributes.

### `cinderwell_loop_post_type_allowed`

Allow a non-public post type to be queried by an intentionally configured Loop.
Receives the current decision, the post type object (or `null`), and block
attributes. Missing or disabled post types render the empty state rather than
falling back to blog posts.

### `cinderwell_bundled_addons`

Register an opt-in module that ships inside the Cinderwell plugin. Each entry
needs a stable slug, name, bundled distribution type, version, and description.
It may also declare `icon`, `settings_tab`, `dependencies`, `option`,
`defaults_callback`, and `health_callback`. Dependencies are kept enabled on
save; reset controls require both an option name and callable defaults provider.

### `cinderwell_addon_catalog`

Add trusted runtime metadata to any bundled or independently installed add-on.
Independent plugins can provide a Dashicon plus either a Cinderwell
`settings_tab` or complete `settings_url` without requiring that metadata in
the remote release manifest.

### `cinderwell_admin_menu_capability`

Change who can see the shared top-level Cinderwell menu container. Lowering
this capability does not grant access to Cinderwell settings, which always
require `manage_options`; it only lets an add-on place a less-privileged screen
under the shared menu.

### Animation filters

The opt-in Animations module exposes `cinderwell_animation_presets`,
`cinderwell_animation_durations`, and `cinderwell_animation_delays` for changing
the constrained editor choices. Custom preset keys need corresponding client
theme CSS using the `data-cw-animation-effect` value. Timing keys resolve to
`--cw-duration-{key}` tokens.

Use `cinderwell_animation_supported_blocks` to add or remove compatible blocks.
Use `cinderwell_animation_section_selectors` to map a block name to the elements
that should be staggered when **Content sections** is selected:

```php
add_filter( 'cinderwell_animation_section_selectors', function ( $selectors ) {
    $selectors['client/testimonial-grid'] = '.testimonial-grid__items > *';
    return $selectors;
} );

add_filter( 'cinderwell_animation_supported_blocks', function ( $blocks ) {
    $blocks[] = 'client/testimonial-grid';
    return $blocks;
} );
```

### `cinderwell_portfolio_fields`

Add, remove, or replace Portfolio item fields. Definitions use the shared
`Cinderwell\Admin_Fields` schema and support `text`, `textarea`, `email`, `url`,
`tel`, `date`, `number`, `slug`, `select`, `checkbox`, `checkboxes`, `media`,
and `repeater` types. Repeater fields contain their own keyed `fields` schema
and may set `max_items`.

```php
add_filter('cinderwell_portfolio_fields', function($fields) {
    unset($fields['client']);
    $fields['industry'] = [
        'label' => __('Industry', 'client-theme'),
        'type' => 'text',
        'description' => __('Shown with the project details.', 'client-theme'),
        'show_in_loop' => true,
    ];
    return $fields;
});
```

After adding a field, enable it in Cinderwell → Portfolio. Values
are stored as `_cw_project_{field_key}` post meta and exposed to authenticated
editors through REST for the block editor.

### `cinderwell_settings_tabs`

Add a tab to Cinderwell. Each associative entry contains a `label`
and callable `callback`. Set `group` to `overview`, `content`, `design`,
`extensions`, or `maintenance` to place it in the matching navigation section.
Unknown or omitted groups fall back to Extensions. Add-on settings should only
register this tab while their module is active.

### `cinderwell_settings_groups`

Add or rename grouped navigation sections on the Cinderwell settings screen.

### Editor Access filters

`cinderwell_editor_access_control_groups` changes the control groups available
to Custom policies. `cinderwell_editor_access_presets` changes the named role
presets. `cinderwell_editor_access_blocks` adjusts the insertable Cinderwell
block catalog, and `cinderwell_editor_access_policy` filters the policy resolved
for the current user. Access granted by any of a user's roles wins. Keep
administrative access available when extending these filters.

### `cinderwell_company_details_fields`

Add, remove, or replace fields in the opt-in Company Details module using the
shared `Cinderwell\Admin_Fields` schema. Scalar fields with `dynamic` enabled
become Dynamic Data sources named `company_{field_key}`. The module also
provides the combined `company_address` source. Use the `business_hours` field
type with a sanitization callback when an extension needs the shared weekly
schedule editor.

### `cinderwell_module_blocks`

Map a built block-directory slug to the bundled module that owns it. Cinderwell
registers those blocks only while their module is enabled. Existing serialized
block comments remain in page content and become available again if the module
is re-enabled.

### `cinderwell_location_fields`

Add, remove, or replace fields stored on `cw_location` entries. Each field is
also exposed as a `location_{field_key}` Dynamic Data source when rendering or
editing a location.

## Maps and Locations

Maps and Locations expose the same record pipeline to the block editor and the
published frontend. A client theme should extend that pipeline rather than
copying the Map block render file.

### Location registration and queries

Use `cinderwell_location_post_type_args` and
`cinderwell_location_taxonomy_args` to adjust the native WordPress registration
arguments. Use `cinderwell_location_map_query_args` to scope or reorder the
published locations supplied to maps. It receives the query arguments and the
selected location-category term ID.

`cinderwell_location_map_record` filters each Locations record and receives the
record, its `WP_Post`, and the selected term ID. Use
`cinderwell_location_map_records` for final ordering or filtering. Both affect
the editor preview and frontend output.

```php
add_filter('cinderwell_location_map_query_args', function($args, $term_id) {
    $args['posts_per_page'] = 50;
    return $args;
}, 10, 2);
```

### Map records

`cinderwell_map_locations` is the provider-level filter. It receives the
current records and complete Map block attributes. The built-in Locations
provider uses it when the block source is `locations`.

The supported base record keys are `id`, `postId`, `name`, `address`,
`latitude`, `longitude`, `directionsUrl`, `phone`, `url`, `termIds`, `terms`,
`meta`, and `popupHtml`. All records pass through the same sanitization before
they enter REST responses or frontend JSON. `popupHtml` is restricted with
`wp_kses_post`; prefer semantic text and links and preserve meaningful link
labels and keyboard behavior.

Use `cinderwell_map_record` to modify one provider record. New top-level keys
must first be registered with `cinderwell_map_record_schema` and a callable
sanitizer. Small integration values that do not need their own top-level API can
go under `meta`, whose scalar values are sanitized automatically.

```php
add_filter('cinderwell_map_record', function($record, $attributes) {
    if ('locations' === ($attributes['source'] ?? 'manual')) {
        $record['popupHtml'] = '<p><a href="/contact/">Contact this office</a></p>';
        $record['meta']['region_code'] = 'west';
    }
    return $record;
}, 20, 2);
```

For content that belongs only in the non-JavaScript directory, use
`cinderwell_map_directory_item_content`. Return HTML acceptable to
`wp_kses_post`; the filter receives the normalized record and block attributes.

### Custom sources and presentations

`cinderwell_map_sources` registers data sources by slug. A source definition
accepts `label`, `enabled`, and an optional authenticated `editor_endpoint`.
Frontend records are supplied through `cinderwell_map_locations`. By default,
non-manual sources preview through Cinderwell's authenticated
`/cinderwell/v1/map/records` route, which runs the same provider filters and
record sanitization as frontend rendering. Set `editor_endpoint` only when an
integration needs a different authenticated REST route; it must be a same-site
REST path beginning with `/` and return the documented record shape.

```php
add_filter('cinderwell_map_sources', function($sources) {
    $sources['service-centers'] = [
        'label'           => 'Service centers',
        'enabled'         => true,
    ];
    return $sources;
});
```

`cinderwell_map_presentations` registers a presentation slug, label, and one of
the accessible built-in behavior bases: `map`, `directory`, or `locator`. The
custom slug is emitted as
`.cinderwell-map--presentation-{slug}` so its visual treatment can live in the
client child theme while Cinderwell retains the semantic markup, fallback, and
interaction behavior.

```php
add_filter('cinderwell_map_presentations', function($presentations) {
    $presentations['compact-directory'] = [
        'label' => 'Compact directory',
        'base'  => 'directory',
    ];
    return $presentations;
});
```

Use WordPress `registerBlockVariation('cinderwell/map', ...)` in the child
theme's editor script to give editors a named preset for a registered source or
presentation. Removing an optional source does not fatal or expose stale saved
records: its saved Map blocks render nothing until that source is available
again.

### `cinderwell_editor_preview_values`

Provide site-level preview values for custom Dynamic Data sources in the block
editor. Return an associative array keyed by the source name.

### `cinderwell_settings_export_options`

Add an option name to the versioned settings export/import allowlist.

### `cinderwell_settings_export`

Add namespaced data to the complete settings export payload.

### `cinderwell_import_setting`

Customize sanitization for an allowlisted option during import. It receives
the recursively sanitized value, option name, and original decoded value.

### `cinderwell_company_schema`

Adjust the Organization JSON-LD array immediately before output.

### `cinderwell_token_manifest`

Add custom tokens to the manifest or replace the defaults of existing tokens.
This is the client-theme registration point for a brand system; declaring the
same custom properties only in CSS does not update block controls or token
logic.

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

Replace built-in defaults without creating duplicate client-only token names:

```php
add_filter('cinderwell_token_manifest', function($manifest) {
    $client_defaults = [
        'cw_color_brand'          => '#c45f38',
        'cw_color_dark'           => '#123b31',
        'cw_color_text'           => '#17372e',
        'cw_color_bg'             => '#fcfaf3',
        'cw_color_link'           => '#71351f',
        'cw_color_focus'          => '#0a6786',
        'cw_font_heading'         => 'Iowan Old Style, Baskerville, serif',
        'cw_font_body'            => 'Avenir Next, Avenir, sans-serif',
    ];

    foreach ($client_defaults as $key => $value) {
        if (isset($manifest[$key])) {
            $manifest[$key]['default'] = $value;
        }
    }

    return $manifest;
}, 20);
```

Palette display names belong in the manifest too. Changing the `label` updates
both Cinderwell controls and the core WordPress palette without duplicating a
palette entry in `theme.json`:

```php
add_filter('cinderwell_token_manifest', function($manifest) {
    $manifest['cw_color_brand']['default'] = '#466a70';
    $manifest['cw_color_brand']['label'] = __('Weathered Blue', 'client-theme');
    return $manifest;
});
```

Register the complete token contract the client design uses, including surface,
contrast, border, link, focus, button, typography, width, spacing, radius, and
image tokens where applicable. Cinderwell injects the resolved values into the
frontend and the iframed editor. Enqueue variation CSS with
`enqueue_block_assets` and a `cinderwell-base` dependency so the same recipes
render in both canvases.

Brand Light and Brand Dark are calculated from `--cw-color-brand`, so changing
the primary brand color keeps the supporting shades synchronized. Accent 1–3
are optional. Remove them from Cinderwell controls and the Cinderwell Base FSE
palette from a client child theme with:

```php
add_filter('cinderwell_enable_accent_colors', '__return_false');
```

The expanded palette is also exposed to Cinderwell block Background controls.
The adjacent Text controls calculate contrast from the current resolved token
values and only offer normal-text pairings that meet WCAG AA at 4.5:1.

Client colors can join the same registry by adding `palette` metadata. Register
the token once and it becomes available to every shared Cinderwell Background
and Text control, including automatic contrast recalculation:

```php
add_filter('cinderwell_token_manifest', function($manifest) {
    $manifest['cw_color_client_blue'] = [
        'label'       => __('Client Blue', 'client-theme'),
        'type'        => 'color',
        'default'     => '#2463a7',
        'description' => __('Supporting client brand color.', 'client-theme'),
        'palette'     => [
            'slug'     => 'client-blue',
            'contexts' => ['background', 'text'],
        ],
    ];

    return $manifest;
});
```

Use only `background` or `text` when a color should be limited to one context.
Set `automatic_foreground` to `true` only for neutral colors that Cinderwell may
choose as the automatic readable foreground for any registered background.

## Block settings clipboard

New Cinderwell blocks participate automatically when their presentation
attributes use the shared Editor Access groups. The clipboard only applies
attributes that also exist in the target block's registered schema.

Client code can refine the copied or pasted map in JavaScript:

```js
import { addFilter } from '@wordpress/hooks';

addFilter(
    'cinderwell.blockSettingsClipboard.copy',
    'client-theme/clipboard-settings',
    (settings, sourceBlock) => settings
);

addFilter(
    'cinderwell.blockSettingsClipboard.paste',
    'client-theme/clipboard-settings',
    (settings, payload, targetBlock) => settings
);
```

The PHP `cinderwell_enable_block_settings_clipboard` filter can force the
editor utility on or off regardless of the saved Advanced setting.

## Block library profiles and functional groups

Editor Access includes a sitewide Block Library profile. `curated` exposes
Cinderwell and active Cinderwell add-on blocks, `essentials` also exposes a
small set of familiar WordPress content blocks, and `full` leaves the entire
registered library available. The profile affects insertion only; existing
content continues to render and remains editable.

Cinderwell blocks are organized by function instead of package: Layout,
Content, Media, Interactive, and Site. Add-on blocks automatically participate
when their namespace begins with `cinderwell-`. A client integration can place
a block explicitly with the catalog filter:

```php
add_filter('cinderwell_block_library_catalog', function($catalog) {
    $catalog['client/testimonial'] = 'cinderwell-content';
    return $catalog;
});
```

Use `cinderwell_block_library_groups` to register another functional group,
`cinderwell_block_library_profiles` to relabel or extend profile metadata, and
`cinderwell_block_library_essential_blocks` to change the Essentials baseline.
`cinderwell_block_library_external_blocks` can change the blocks listed under
Advanced block exceptions. For context-sensitive policy, use
`cinderwell_block_library_allowed_blocks` or
`cinderwell_block_library_curate_context`.

## Page Header

The Page Header block inherits from `cinderwell_page_header_settings` and then
applies block and Page-level overrides. Client themes can change shipped
defaults without replacing the template:

```php
add_filter('cinderwell_page_header_defaults', function($defaults) {
    $defaults['background'] = 'brand';
    $defaults['spacing'] = 'lg';
    $defaults['post_date'] = true;
    $defaults['post_terms'] = true;
    return $defaults;
});
```

Use `cinderwell_page_header_context` to modify the fully resolved render model
and `cinderwell_page_header_breadcrumbs` to add, remove, or relabel breadcrumb
items. Use `cinderwell_page_header_terms` to replace or reorder the linked term
badges shown on single blog posts. Page-level title overrides affect the visible
H1 only; they do not alter the WordPress title, URL, or SEO metadata.

## Action Hooks

### `cinderwell_register_fields`

Register structured client data with Cinderwell's native content-field service.
The schema owns sanitization, native WordPress meta or option storage, REST
exposure, editor placement, and optional admin-list columns. It does not create
a visual field builder, and a client theme remains the source of truth for the
schema.

```php
add_action('cinderwell_register_fields', function($registry) {
    $registry->register_group('client/property_details', [
        'label'           => __('Property details', 'client-theme'),
        'object_type'     => 'post', // post, term, user, or site.
        'object_subtypes' => ['property'],
        'editor_location' => 'sidebar', // sidebar, normal, or side.
        'capability'      => 'edit_posts',
        'fields'          => [
            'subtitle' => [
                'label'        => __('Subtitle', 'client-theme'),
                'required'     => true,
                'admin_column' => [
                    'sortable' => true,
                ],
            ],
            'property_type' => [
                'label'   => __('Property type', 'client-theme'),
                'type'    => 'select',
                'options' => [
                    'home'       => __('Home', 'client-theme'),
                    'commercial' => __('Commercial', 'client-theme'),
                ],
                'admin_column' => [
                    'filterable' => true,
                ],
            ],
        ],
    ]);
});
```

Read a registered value through the service so storage can evolve without
changing templates:

```php
$subtitle = \Cinderwell\Content_Fields::registry()->get_value(
    'client/property_details',
    'subtitle',
    get_the_ID()
);
```

Post, term, and user fields use native metadata. Site groups use one namespaced
option array per group; set `option_name` when an existing option must be
preserved. Meta keys are derived from the group and field IDs by default. Set a
field's `storage_key` to retain an existing meta key during a migration. Core
types include text, textarea, email, URL, telephone, number,
date, datetime, checkbox, checkbox list, select, media, repeater, business
hours, and post/term/user references (singular or plural).
Groups containing repeater or business-hours fields automatically use the
below-editor meta-box presentation because those structured controls are not
compressed into the document sidebar.

Fields with `dynamic` enabled appear under **Content Fields** in Cinderwell's
Dynamic Data picker. Existing ACF fields continue to appear only when ACF is
active, so ACF remains a compatibility adapter rather than a client-site
dependency. Cinderwell intentionally does not replace ACF's visual builder,
flexible content, or repeatable layout workflows, and it does not recreate
Admin Columns Pro's inline editing, bulk editing, or export tools.

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

### `cinderwell_register_documentation`

Register package-owned documentation without coupling its source to the Help
interface. The directory contains a `manifest.php` with sections and topic
metadata plus Markdown bodies referenced by each topic's `file` value.

```php
add_action('cinderwell_register_documentation', function($registry) {
    $registry->register_directory(
        'client-theme',
        get_stylesheet_directory() . '/help'
    );
});
```

The registry loads manifests only when documentation is requested and reads
Markdown bodies only when a consumer asks for rendered content. Active add-ons
therefore contribute only their own guidance. Topic IDs remain stable across
the Help screen, contextual links, and exported documentation bundles.

Use `visibility => 'internal'` for private client guidance; topics are public by
default. Add a locale before the extension, such as `editing.es_ES.md`, to ship
a translated body alongside the source file. See the Cinderwell Help README for
the complete manifest contract and consumer filters.

### `cinderwell_loop_item_after_title`

Render structured metadata immediately after a Loop item title. Receives the
post ID and block attributes. Teams uses this hook for a person's position.

Portfolio uses the same action for its compact project metadata.

## Portfolio Template Overrides

Portfolio templates are registered as plugin fallbacks. An active child theme
can replace them using WordPress's normal FSE hierarchy:

```
templates/single-cw_project.html
templates/archive-cw_project.html
templates/taxonomy-cw_portfolio_category.html
```

Theme files win over the registered plugin defaults and remain editable in the
Site Editor. No PHP template loader or copied plugin file is required.

## Theme Override Directory

Place custom templates in your theme at:

```
theme/cinderwell/{block-name}/index.html
```

The plugin checks the active theme (so client child themes work) first, then the
parent theme, falling back to plugin defaults. `index.html` may use
`{{content}}` (the block's inner content) and `{{attribute_name}}` (an escaped
scalar attribute value) so each instance renders its own data. Use the
`cinderwell_render_{block}` filter for programmatic changes.
