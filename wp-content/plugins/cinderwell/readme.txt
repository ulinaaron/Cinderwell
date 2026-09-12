=== Cinderwell ===
Contributors: stevensinc
Tags: gutenberg, blocks, design-system, accessibility
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.16
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A curated Gutenberg block library with design system constraints.

== Description ==

Cinderwell is a curated Gutenberg block library that enforces design system constraints. Content editors get clean, accessible blocks with only content fields and design-system dropdowns — no raw design controls.

= Features =

* Purpose-built page, content, media, navigation, and commerce blocks
* Design system tokens via CSS custom properties
* WCAG 2.1 AA accessible by default
* JSON-LD structured data for FAQ and Quote blocks
* Extensible via WordPress hooks and filters
* RTL support
* Translation-ready

== Installation ==

1. Upload the `cinderwell` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Run `npm install && npm run build` in the plugin directory

== Changelog ==

= 0.1.16 =
* Move WooCommerce page-shell ownership into the plugin and normalize responsive legacy product grids.
* Add FSE template-override diagnostics and parent-theme update support.

= 0.1.15 =
* Add tokenized login and account-dashboard layouts for WooCommerce.

= 0.1.12 =
* Add a conditional WooCommerce token adapter for native store blocks and templates.

= 0.1.11 =
* Show the shared link editor beside buttons and links while their text is being edited.

= 0.1.0 =
* Initial release
* 10 blocks, 7 atoms, 6 patterns
* Design system with CSS custom properties
* Extension API with filters and actions
* Settings page with General tab
* Schema aggregator for JSON-LD
* Gravity Forms integration
