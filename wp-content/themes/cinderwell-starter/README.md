# Cinderwell Base

Cinderwell Base is the updateable FSE parent theme for sites built with the
Cinderwell plugin. Keep this directory unmodified on client sites. Brand and
project-specific work belongs in a child theme.

The directory slug remains `cinderwell-starter` for backwards compatibility,
so client themes use that value in their `Template` header.

## Minimal client child theme

Create `wp-content/themes/client-name/style.css`:

```css
/*
Theme Name: Client Name
Template: cinderwell-starter
Requires Plugins: cinderwell
Text Domain: client-name
*/
```

Create `wp-content/themes/client-name/theme.json` and override only the visual
decisions owned by the client. WordPress merges this file over the parent
theme's settings and styles.

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "styles": {
    "color": {
      "background": "var(--cw-color-bg)",
      "text": "var(--cw-color-text)"
    }
  }
}
```

Use the `cinderwell_design_tokens` filter or the Cinderwell token UI for the
client palette, typography, widths, and spacing. Add child-theme `parts/`,
`templates/`, and `patterns/` only when the client intentionally differs from
the parent.

When a child theme adds a frontend stylesheet, make the parent handle an
explicit dependency and enqueue the child stylesheet after the parent:

```php
add_action( 'wp_enqueue_scripts', static function () {
	wp_enqueue_style(
		'client-name',
		get_stylesheet_directory_uri() . '/assets/css/client-name.css',
		[ 'cinderwell-starter-main', 'cinderwell-base' ],
		'1.0.0'
	);
}, 20 );
```

This order is part of the client-theme contract: Cinderwell Base supplies the
fallback tokens first, then the child theme owns brand token overrides and
presentation recipes without specificity escalation.

Load the same client stylesheet in the block editor so its tokens and block
variations match the published site:

```php
add_action( 'after_setup_theme', static function () {
	add_editor_style( 'assets/css/client-name.css' );
}, 20 );
```

## FSE update rule

Site Editor customizations are stored in the database and take precedence over
both parent and child theme files. Before deployment, export intentional
template changes into the client child theme and reset the database version.
The Cinderwell **Settings → Template Updates** screen identifies these update
blockers.

## Page headers

The default Page template includes the dynamic Cinderwell Page Header block.
Its visual defaults live in **Cinderwell → Page Headers**. Editors can hide or
customize it for one Page from that Page's document sidebar without creating a
template override. Client themes can replace `templates/page.html` when a
different structural treatment is intentional.

## Utility bar

The default Header template part includes a Cinderwell Utility Bar. It reads
live phone, email, contact, and social values from Company Details and outputs
nothing until one of its enabled values is available. Remove the block from the
Header template part to turn it off for a site, or use the
`cinderwell_utility_bar_enabled` filter in a child theme to suppress it globally.

## Ownership boundary

- Cinderwell plugin: blocks, tokens, accessibility, responsive behavior,
  WooCommerce integration, and shared structural CSS.
- Cinderwell Base: WordPress template hierarchy and default site frame.
- Client child theme: brand expression, patterns, template parts, and explicit
  project-specific template overrides.

The parent deliberately overrides WooCommerce's `page-checkout` template only
to preserve the normal site header and footer. Checkout fields, order summary,
validation, and commerce styling remain owned by WooCommerce and the Cinderwell
plugin adapter. A client may override `templates/page-checkout.html` when a
distraction-free checkout is an intentional project requirement.
