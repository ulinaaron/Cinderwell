=== Cinderwell ===
Contributors: stevensinc
Tags: wordpress, foundation, gutenberg, blocks, design-system
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.88
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A governed WordPress foundation with a curated block kit, design system, client theme layer, and focused add-on ecosystem.

== Description ==

Cinderwell is a governed WordPress foundation for building maintainable client sites. Its curated block kit, design tokens, client theme layer, structured content modules, and focused add-ons work together without turning every site into a custom platform.

= Features =

* Purpose-built page, content, media, navigation, and commerce blocks
* Design system tokens via CSS custom properties
* Role-based Editor Access presets and block availability
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

= 0.1.88 =
* Expose the standard section layout, background, typography, spacing, conditions, and visibility controls to add-on blocks.

= 0.1.84 =
* Add a dedicated Testimonials block with optional portraits, responsive card columns, and a progressively enhanced accessible carousel.

= 0.1.83 =
* Keep facet controls mounted during live filtering so keyboard focus and text entry remain stable.
* Add persistent result announcements, loading state semantics, reduced-motion handling, and stale-request cancellation.

= 0.1.82 =
* Add an opt-in, provider-neutral facet framework with shareable URLs and conditionally loaded live filtering.
* Add configurable search, taxonomy, and sorting facets to the Cinderwell Loop block.

= 0.1.78 =
* Replace the block-centric General screen with an ecosystem dashboard.
* Move registered blocks and patterns into a dedicated Block Kit tab.
* Include active Cinderwell add-on blocks in the Block Kit inventory.

= 0.1.71 =
* Make Gravity Forms transparent and flush with surrounding content, remove section-like spacing controls, and hide the duplicate form title.

= 0.1.70 =
* Rebalance the fluid heading tokens for more readable hierarchy across blocks, themes, and responsive layouts.
* Preserve semantic typography scales when editors size an individual heading or subheading.

= 0.1.69 =
* Extend named presentation variations to Image + Text and CTA blocks with Editor Access integration and stable frontend modifier classes.

= 0.1.68 =
* Add inherited post date and category metadata to single-post Page Headers, with site, template, and per-post controls.

= 0.1.67 =
* Add Magazine, Image Overlay, Alternating, and numbered Index Loop variations with responsive editor previews.

= 0.1.66 =
* Present Loop card categories as accessible, token-driven badges in the editor and front end.

= 0.1.65 =
* Present Company Details sections in a single full-width column for easier scanning and editing.

= 0.1.64 =
* Show live resolved-color swatches beside inherited and customized button token recipes.

= 0.1.63 =
* Redesign Design Tokens with searchable groups, live previews, clearer inherited/custom states, editable hex values, and per-token resets.

= 0.1.62 =
* Unify the complete Cinderwell settings interface with a shared responsive shell, cards, navigation, switches, subtabs, and save states.

= 0.1.61 =
* Use reusable segmented pills and token color chips for Page Header global defaults.

= 0.1.60 =
* Match Page Header controls to Cinderwell's segmented pills and token color chips, and expose title overrides in the selected block.

= 0.1.59 =
* Add a dynamic Page Header block with inherited site defaults, page-level overrides, optional descriptions, and accessible breadcrumbs.

= 0.1.58 =
* Keep Background Image Hero spacing inside its viewport-based height instead of adding it to the section height.

= 0.1.57 =
* Normalize Hero output to one alignment modifier so legacy saved classes cannot override the current editor setting.

= 0.1.56 =
* Rename the Immersive Hero variation to Background Image and make centered content its default recipe.

= 0.1.55 =
* Make Immersive Hero alignment position the complete content region as well as its text and buttons.

= 0.1.54 =
* Remove duplicate Immersive Hero inner padding so the shared responsive spacing controls remain authoritative.

= 0.1.53 =
* Make the Immersive Hero image cover the full Hero canvas while keeping content constrained and media controls accessible.

= 0.1.52 =
* Invert Primary buttons on the Brand background so the action remains visually distinct.

= 0.1.51 =
* Automatically switch transparent Secondary button text and borders to the surface's computed AA foreground when needed.

= 0.1.50 =
* Add dedicated Primary, Secondary, Ghost, and Link button design tokens.
* Keep button variants consistent across Heroes, CTAs, cards, images, and colored surfaces.

= 0.1.49 =
* Use each extended background token's computed AA foreground for Hero and CTA button fills, outlines, links, and hover states.

= 0.1.48 =
* Remove Editorial Hero's extra section padding so responsive spacing controls remain authoritative.

= 0.1.47 =
* Drive the Editorial Hero image blend from the shared surface token so semantic, derived, accent, and client colors work automatically.

= 0.1.46 =
* Blend the Editorial Hero background token into its image and reverse the transition with image position.

= 0.1.45 =
* Use Top and Bottom image positions for Statement Heroes and correct Editorial image/content stacking.

= 0.1.44 =
* Add Split, Statement, Editorial, Immersive, and Framed Hero variations with content-safe switching.

= 0.1.43 =
* Deepen the Poster card gradient slightly for stronger image and text separation.

= 0.1.42 =
* Keep Poster card Add image, Replace, and Remove controls clickable above the visual overlay.

= 0.1.41 =
* Make the Posters Card Grid variation honor the selected card color for its surface, gradient, text, icons, and actions.

= 0.1.40 =
* Expand Card Grid variations with Split Rows, Mosaic, Posters, and Directory compositions.

= 0.1.39 =
* Add Raised, Bordered, Horizontal, and Featured Card Grid variations without changing authored card content.

= 0.1.38 =
* Add a child-theme-extensible presentation variation registry and four switchable Loop layouts.
* Preserve Loop query and content settings while switching or copying layouts.

= 0.1.37 =
* Restore consistent separators between every Cinderwell inspector panel.

= 0.1.36 =
* Add a Company Details-powered Utility Bar block for FSE headers.
* Collapse complex header navigation before logo, menu, account, and cart controls wrap.

= 0.1.35 =
* Add a permission-aware settings clipboard for compatible Cinderwell blocks, with an Advanced settings opt-out.
* Keep Cinderwell Settings ahead of editor-facing add-on screens in the admin menu.

= 0.1.34 =
* Restore Content Width for split Heroes and use it as the inner alignment grid while keeping the image full-bleed.

= 0.1.33 =
* Clarify the locked Hero content-width control with consistent disabled pills and a compact informational notice.

= 0.1.32 =
* Clarify split Hero width behavior, add tokenized content-gap controls, and make image-right layouts stack image-first on mobile.

= 0.1.31 =
* Apply Hero alignment to legacy saved markup at render time so content and buttons follow editor choices without manual recovery.

= 0.1.30 =
* Add a dedicated Hero image-side control and an edge-to-edge split layout with independent content alignment.

= 0.1.29 =
* Keep an unselected Hero image placeholder compact and reserve the 50/50 layout for an actual selected image.

= 0.1.28 =
* Add complete Hero alignment, align Hero and CTA action groups with their content, and use a responsive 50/50 Hero layout when an image is present.

= 0.1.27 =
* Map Gravity Forms informational, confirmation, and validation messages to the Cinderwell info, success, and danger tokens.

= 0.1.26 =
* Add a manifest-driven color registry with live WCAG AA contrast detection for edited and client-defined palette tokens.

= 0.1.25 =
* Fix responsive Hero spacing serialization and retain compatibility with previously saved Hero markup.

= 0.1.24 =
* Expose the expanded semantic, derived-brand, muted, and optional accent palette in block Background controls with contrast-safe text and component states.

= 0.1.23 =
* Add semantic status colors, derived brand shades, and optional client accent tokens with an FSE palette opt-out.

= 0.1.22 =
* Fully expose the semantic Muted color token and use it for Loop dates and other secondary content.

= 0.1.21 =
* Replace the detached Loop transform menu with a polished, reversible Loop type chooser inside the Cinderwell inspector.

= 0.1.20 =
* Add Animations as an opt-in bundled module with token-based presets for whole blocks and content sections.

= 0.1.19 =
* Add contextual Help documentation for enabled Teams, Portfolio, Company Details, and Locations modules.

= 0.1.18 =
* Add a safe shared admin-menu capability hook for independently packaged editor-facing add-ons.

= 0.1.17 =
* Add role-based Editor Access presets, custom control groups, and per-role block availability.
* Add Company Details and Locations as opt-in core modules.
* Add dependent add-ons, readiness status, reset controls, and settings import/export.
* Add reusable media and repeater fields to the shared admin form utility.

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
