# Cinderwell — AI Agent Guide

## What is Cinderwell?

Cinderwell is a curated Gutenberg block library for WordPress that enforces design system constraints. Content editors get clean, accessible blocks with only content fields and design-system dropdowns — no raw design controls. Every block follows WCAG 2.1 AA. JS-first block storage, native HTML5/CSS for interactivity, extensible per-client via hooks.

Core structured content uses the code-first `cinderwell_register_fields`
registry. Preserve declared group IDs and storage keys, prefer native post,
term, and user metadata (or a namespaced site option), and treat ACF as an
optional compatibility adapter rather than a required client dependency.

## Architecture Quick Reference

- **Plugin slug:** `cinderwell`
- **PHP namespace:** `Cinderwell`
- **Function prefix:** `cinderwell_`
- **Block namespace:** `cinderwell/{block-name}`
- **Pattern namespace:** `cinderwell/{pattern-name}`
- **Text domain:** `cinderwell`
- **CSS prefix:** `--cw-*`
- **Storage model:** JS `save()` function, not PHP render
- **Interactivity:** Progressive native HTML/CSS with scoped view scripts only where interaction requires them

### File Locations

| What | Where |
|---|---|
| Block definitions | `src/blocks/{name}/` |
| Atom definitions | `src/atoms/{name}/` |
| Shared components | `src/shared/` (`base.css`, `actions.css`, `responsive.css`, `media.css`, `forms.css`, `commerce.css`) |
| Editor-level tools | `src/editor/` |
| Slot atoms | `src/shared/slots/` |
| PHP classes | `includes/` |
| CSS output | `src/{block/atom}/style.css` (compiled to `build/`) |
| Documentation | `README.md`, `CLIENT_GUIDE.md`, `EXTENDING.md`, etc. |
| Settings page | `includes/class-admin-page.php` |
| Update mechanism | `includes/class-update-mechanism.php` |

### Naming Conventions

- Block names: `cinderwell/{block-name}` (kebab-case)
- CSS classes: `cinderwell-{block-name}` with BEM (`cinderwell-hero__inner`)
- CSS variables: `--cw-{category}-{name}`
- PHP classes: `Cinderwell\{Class_Name}` (file: `class-{class-name}.php`)
- Token names: `cw_{category}_{name}` (lowercase, underscores)

## Block Schema Reference

### cinderwell/hero
**Title:** Hero
**Description:** Page-level hero with eyebrow, heading, image, and buttons
**Slots:** eyebrow (string), heading (string), subheading (string), image (int), imageAlt (string, required), caption (string), buttons (array, max 3), footnote (string)
**Design:** Split, Statement, Editorial, Background Image (`immersive` slug), and Framed layouts; spacing, width, image side and gap, alignment, background, and typography
**Defaults:** layout=split, spacing=comfy, width=standard, background=white
**Variation contract:** `layout` changes presentation only. Never replace or clear authored text, buttons, or media when applying a variation.

### cinderwell/page-header
**Title:** Page Header
**Description:** Dynamic Page title with optional description and breadcrumbs
**Content:** Current Page title, optional static or dynamically bound description, Home/ancestor/current breadcrumb trail
**Design:** inherited or explicit background, spacing, width, alignment, and breadcrumb visibility
**Overrides:** Page and post metadata may hide the header or override title, description, background, and breadcrumbs without changing the template
**Defaults:** inherit the Page Headers settings; shipped defaults are light, wide, left-aligned, medium spacing, and breadcrumbs enabled

### cinderwell/body
**Title:** Body
**Description:** Rich text body content with optional eyebrow, heading, byline, pullquote, and buttons
**Slots:** eyebrow (string), heading (string), bodyContent (rich text), byline (string), pullquote (string), buttons (array, max 3), footnote (string)
**Design:** spacing, width, background, text size, text color
**Defaults:** spacing=comfy, width=standard, background=white

### cinderwell/card-grid
**Title:** Card Grid
**Description:** Authored cards for services, features, products, or related content
**Content:** Per-card image or icon, eyebrow, title, description, and button
**Design:** Raised, Bordered, Split Rows, Mosaic, Posters, Image Boxes, and Directory layouts; responsive columns, card surface, spacing, width, background, and typography
**Variation contract:** `layout` changes presentation only. Never replace, reorder, or edit the `cards` array when applying a variation.

### cinderwell/cta
**Title:** CTA
**Description:** Call-to-action section with eyebrow, heading, body, buttons, and footnote
**Slots:** eyebrow (string), heading (string), bodyContent (string), buttons (array, max 3), footnote (string)
**Design:** spacing, width, background, alignment (left/center/right), text size, text color
**Defaults:** spacing=comfy, width=standard, background=white, alignment=center

### cinderwell/image-text
**Title:** Image + Text
**Description:** Side-by-side image and text content
**Slots:** eyebrow (string), heading (string), image (int), imageAlt (string, required), caption (string), bodyContent (rich text), buttons (array, max 3), footnote (string)
**Design:** spacing, width, background, alignment (left/right), text size, text color
**Defaults:** spacing=comfy, width=standard, background=white, alignment=left

### cinderwell/quote
**Title:** Quote
**Description:** Blockquote with attribution, byline, and context
**Slots:** quote (string), attribution (string), byline (string), context (string)
**Design:** spacing, width, background, alignment (left/center/right), text size, text color
**Schema:** `Quotation` with `author`
**Defaults:** spacing=comfy, width=standard, background=white, alignment=left

### cinderwell/testimonials
**Title:** Testimonials
**Description:** Multiple client testimonials shown as responsive cards or a progressively enhanced carousel
**Slots:** optional eyebrow and heading, testimonial quote, person name, optional role and organization, optional portrait, optional footnote
**Design:** responsive columns, card color, photo shape, spacing, width, background, typography
**Accessibility:** semantic figure/blockquote markup, complete horizontal-scroll fallback, keyboard navigation, visible focus, reduced-motion support, and polite slide announcements
**Defaults:** layout=cards, columns=3, width=wide, background=white, card color=white, circular portraits

### cinderwell/gallery
**Title:** Gallery
**Description:** Image gallery with multiple images
**Slots:** eyebrow (string), heading (string), images (array of {id, url, alt}), caption (string), footnote (string)
**Design:** spacing, width, background, text size, text color
**Schema:** `ImageGallery`
**Defaults:** spacing=comfy, width=standard, background=white

### cinderwell/image-carousel
**Title:** Image Carousel
**Description:** Progressively enhanced image slider with native scroll-snap fallback
**Slots:** eyebrow, heading, images (array of {id, url, alt, caption, position}), footnote
**Controls:** optional previous/next buttons and slide indicators; global image fit and aspect ratio
**Accessibility:** named carousel region, labeled slides, keyboard navigation, reduced-motion support, and polite slide announcements
**Schema:** `ImageGallery`
**Defaults:** width=wide, background=white, aspect=wide, fit=cover, arrows and indicators enabled

### cinderwell/map
**Title:** Map
**Description:** OpenStreetMap map with one or more address-based pins
**Data:** ordered locations containing name, address, latitude, and longitude
**Variations:** single location (default) and multiple manually managed locations
**Accessibility:** keyboard-focusable markers, labeled map region, visible focus, safe popup content, and a complete non-JavaScript location fallback
**Performance:** locally bundled Leaflet; map scripts, styles, and tiles load only when the block renders; geocoding occurs only from an explicit editor action
**Extension:** `cinderwell_map_locations` supplies records to the shared editor/frontend pipeline; sources, presentations, record schema, location queries, directory items, tiles, and geocoder services are filterable
**Defaults:** width=wide, height=medium, maximum zoom=14, initial popup enabled for a single location

### cinderwell/faq
**Title:** FAQ
**Description:** Accordion FAQ with FAQPage schema
**Slots:** eyebrow (string), heading (string), items (array of {id, question, answer}), footnote (string)
**Design:** spacing, width, background, text size, text color
**Schema:** `FAQPage` with `Question`/`Answer` items
**Defaults:** spacing=comfy, width=standard, background=white

### cinderwell/loop
**Title:** Loop
**Description:** Dynamic listing for posts and public custom post types
**Query:** post type, taxonomy term, item count, order, order-by, optional pagination
**Content:** eyebrow, heading, featured image, terms, date, title, excerpt, read-more link
**Design:** Cards, Media List, Minimal List, and Featured Lead layouts; responsive 1–4 columns, image aspect, spacing, width, background, text size, text color
**Render:** PHP with `WP_Query`; extensible through `cinderwell_loop_query_args` and `cinderwell_loop_item_html`
**Defaults:** 6 posts, newest first, 3/2/1 columns, width=wide, background=white

Loop `variation` is reserved for semantic data modes such as People,
Portfolio, and Locations. Loop `layout` is the switchable presentation slug.
Never make a layout recipe change query, content, link, visibility, media, or
dynamic-data attributes. Register client layouts through
`cinderwell_block_variations`; keep CSS-only variants on the shared item markup
and use `render_item_callback` only when the structure genuinely differs.

Client-theme and add-on presentation recipes must declare `custom => true` in
their `cinderwell_block_variations` definition. The flag records that the
variation is not a Cinderwell default; core owns its normalization, editor
indicator, styling, accessibility, and future behavior. Do not recreate the
badge or flag behavior in a client theme. A materially constrained presentation
must be a named custom variation scoped to its emitted layout modifier, not an
opaque page-specific override.

Card Grid variations use the same registry and add their frontend modifier at
render time so old static markup remains valid. Use `render_callback` only for
trusted client layouts that cannot be expressed against the stable card markup.

Hero variations use the same runtime modifier and may change only layout,
alignment, width, image side, and split gap.

### cinderwell/accordion
**Title:** Accordion
**Description:** Expandable content panels without FAQ schema
**Slots:** eyebrow, heading, items (array of {id, title, content}), footnote
**Design:** spacing, width, background, text size, text color
**Defaults:** spacing=comfy, width=standard, background=white

### cinderwell/tabs
**Title:** Tabs
**Description:** Accessible tabbed content with block-based panels
**Slots:** eyebrow, heading, nested `cinderwell/tab-item` blocks, footnote. Each Tab Item owns an unrestricted InnerBlocks content area.
**Design:** underline/pills/boxed tab styles; responsive orientation and tab alignment; left/right side placement for vertical tabs; inherited or independent child-block surfaces; spacing, width, background, text size, text color
**Defaults:** horizontal/start on desktop, desktop inheritance on tablet, vertical/stretch on mobile, width=standard, background=white

### cinderwell/columns and cinderwell/column
**Title:** Columns
**Description:** Responsive layout container with native nested-block editing
**Content:** 1–4 `cinderwell/column` children. Each column accepts enabled Cinderwell blocks and supports native dragging, duplication, removal, and List View ordering.
**Design:** Relative 1×/2×/3× column proportions, token gap, vertical alignment, mobile/tablet stacking, optional reverse stack, spacing, width, and background
**Defaults:** two equal columns, medium gap, mobile stacking, wide width, white background

### cinderwell/section
**Title:** Section
**Description:** Generic section container with InnerBlocks
**Slots:** InnerBlocks (accepts any block)
**Design:** spacing, width, background
**Defaults:** spacing=comfy, width=standard, background=white

### cinderwell/mega-menu
**Title:** Mega Menu
**Description:** Wide disclosure panel placed directly inside the native Navigation block
**Slots:** nested `cinderwell/mega-menu-column` blocks; columns accept Link, Button, Note, and Image atoms
**Design:** 2–4 columns, white/light/dark/brand panel surfaces
**Accessibility:** button disclosure semantics, Escape handling, keyboard focus management, reduced-motion support
**Defaults:** 3 columns, white background

### cinderwell/gravity-form
**Title:** Gravity Form
**Description:** Embed a Gravity Forms form with design token styling
**Fields:** formId (number), description (bool), ajax (bool). The Gravity Forms title is always hidden so surrounding Cinderwell content owns the page hierarchy.
**Design:** width only. The block surface and vertical rhythm inherit from its surrounding section.
**Render:** PHP callback using `[gravityform]` shortcode
**Defaults:** width=standard, title=false, description=true, ajax=true

### cinderwell/utility-bar
**Title:** Utility Bar
**Description:** Compact FSE header bar powered by the optional Company Details module
**Fields:** phone, email, contact-link, social-profile visibility and mobile social visibility
**Design:** dark, brand, light, or white token surface
**Render:** PHP callback so Company Details changes update every header immediately; empty data produces no wrapper
**Defaults:** phone and social profiles enabled, dark background

### cinderwell/company-details
**Title:** Company Details
**Description:** Dynamic contact, address, and business-hours presentation powered by the optional Company Details module
**Content:** Hours, Contact, Address, or All Details modes; optional heading, description, phone, email, current status, Contact action, and Directions action
**Design:** Stacked, Card, and Split layouts; spacing, width, alignment, background, and typography
**Render:** PHP callback so shared Company Details changes update every instance; the block is registered only while the module is enabled and empty data produces no wrapper
**Accessibility:** semantic address and definition-list markup, explicit time ranges, keyboard-visible actions, and progressively enhanced open/closed status
**Defaults:** Hours mode, Card layout, light background

## Atom Reference

### Slot Atoms (composed by blocks)
- **Eyebrow** — Small uppercase text, brand color, above headings. Class: `cinderwell-eyebrow`
- **Heading** — h1-h4 with hierarchy tracking. Class: `cinderwell-heading`
- **Subheading** — Supporting text below heading. Class: `cinderwell-subheading`
- **Body** — Rich text container. Class: `cinderwell-body`
- **Caption** — Italic small text, below images. Class: `cinderwell-caption`
- **Byline** — Author/date, below headings. Class: `cinderwell-byline`
- **Pullquote** — Highlighted inline quote. Class: `cinderwell-pullquote`
- **Footnote** — Small text at bottom of block. Class: `cinderwell-footnote`
- **Legal** — Terms/conditions text. Class: `cinderwell-legal`

Composite blocks expose block-wide `textSize` / `textColor` defaults plus a
`textStyles` object for semantic-part overrides. Each part stores token slugs as
`{ size, color }`; its inspector controls must only be shown while that part is
enabled. Keep `auto` as the inheritance-preserving default.

The Gutenberg header includes a pinned Design Tokens management sidebar
registered from `src/editor/index.js`. Its data comes from
`Design_Tokens::get_manifest()` and `Design_Tokens::get_resolved_tokens()`; do
not duplicate token definitions in JavaScript. Updates use the capability-
protected `cinderwell/v1/tokens` REST route and the same sanitizer as the admin
settings screen.

Repeater-based blocks use the shared `SortableItemCard` and `moveArrayItem`
utilities. Preserve drag handles and the Move Up/Down controls together so
reordering remains keyboard accessible.

## Dynamic Data and Conditions

Every Cinderwell block exposes `dynamicData`, `visibility`, and
`visibilityConfig` attributes. `dynamicData` is a map keyed by the text slot
name, for example `{ heading: { source: 'post_title', field: '', fallback: '' } }`.
Use `Data_Sources::get_sources()` and the `cinderwell_data_sources` filter to
add sources; source values are resolved only in the frontend render filter.
Use `cinderwell_conditions` to add or limit visibility rules, and
`cinderwell_condition_evaluate` to evaluate custom rules. Keep visitor-facing
rules safe by default; user-aware rules must be intentionally exposed.

### Utility Atoms (standalone blocks)
- **Button** (`cinderwell/button`) — Native or dynamic link, optional new-tab behavior, variants: primary/secondary/ghost/link, and sizes: sm/md/lg. Classes: `btn btn--{variant} btn--{size}`
- **Heading** (`cinderwell/heading`) — Standalone heading with level selection
- **Number** (`cinderwell/number`) — Statistic or measurable result with an optional viewport-triggered count-up; the authored final value remains available without JavaScript and to assistive technology
- **Image** (`cinderwell/image`) — Standalone image with required alt text
- **Icon** (`cinderwell/icon`) — Searchable Lucide library or Media Library SVG with token size, color, treatment, and alignment
- **Link** (`cinderwell/link`) — Styled inline link with new-tab option
- **Divider** (`cinderwell/divider`) — Weights: light/medium/heavy
- **Note** (`cinderwell/note`) — Styles: disclaimer/caption/footnote/legal

## Design Token Reference

| Token | Default | Controls |
|---|---|---|
| `--cw-color-white` | `#ffffff` | White color |
| `--cw-color-light` | `#f8f5ef` | Light background |
| `--cw-color-dark` | `#1a1a1a` | Dark background |
| `--cw-color-brand` | `#b84c00` | Brand color |
| `--cw-color-brand-light` | Derived | Subtle brand tint |
| `--cw-color-brand-dark` | Derived | Strong brand shade |
| `--cw-color-text` | `#1a1a1a` | Text color |
| `--cw-color-bg` | `#ffffff` | Background color |
| `--cw-color-brand-contrast` | `#ffffff` | Content on brand surfaces |
| `--cw-color-surface` | `#ffffff` | Cards and raised surfaces |
| `--cw-color-muted` | `#666666` | Secondary text |
| `--cw-color-info` | `#005ea8` | Informational states |
| `--cw-color-success` | `#287d3c` | Successful states |
| `--cw-color-danger` | `#b42318` | Error and destructive states |
| `--cw-color-accent-1` | `#6f42c1` | Optional accent |
| `--cw-color-accent-2` | `#007c83` | Optional accent |
| `--cw-color-accent-3` | `#9a6700` | Optional accent |
| `--cw-color-border` | `#d9d6d0` | Borders and dividers |
| `--cw-color-link` | `#b84c00` | Inline links |
| `--cw-color-link-hover` | `#1a1a1a` | Link hover state |
| `--cw-color-focus` | `#b84c00` | Keyboard focus rings |
| `--cw-button-primary-background` | Brand | Primary button background and border |
| `--cw-button-primary-foreground` | Brand contrast | Primary button text and icons |
| `--cw-button-primary-hover-background` | Dark | Primary button hover background and border |
| `--cw-button-primary-hover-foreground` | White | Primary button hover text and icons |
| `--cw-button-secondary-foreground` | Brand | Secondary button text, icons, and border |
| `--cw-button-secondary-hover-background` | Brand | Secondary button hover background and border |
| `--cw-button-secondary-hover-foreground` | Brand contrast | Secondary button hover text and icons |
| `--cw-button-ghost-foreground` | Text | Ghost button text and icons |
| `--cw-button-ghost-background` | 7% ghost foreground | Ghost button background |
| `--cw-button-ghost-hover-background` | 13% ghost foreground | Ghost button hover background |
| `--cw-button-link-foreground` | Link | Link-style button text and icons |
| `--cw-button-link-hover-foreground` | Link hover | Link-style button hover text and icons |
| `--cw-font-heading` | `inherit` | Heading font family |
| `--cw-font-body` | `inherit` | Body font family |
| `--cw-line-height-tight` | `1.2` | Heading line height |
| `--cw-line-height-body` | `1.6` | Body-copy line height |
| `--cw-line-height-relaxed` | `1.7` | Long-form line height |
| `--cw-font-size-xs` | `clamp(0.75rem, 0.72rem + 0.12vw, 0.8125rem)` | Fluid extra-small type |
| `--cw-font-size-sm` | `clamp(0.875rem, 0.82rem + 0.22vw, 1rem)` | Fluid small type |
| `--cw-font-size-md` | `clamp(1rem, 0.95rem + 0.25vw, 1.125rem)` | Fluid body type |
| `--cw-font-size-lg` | `clamp(1.125rem, 1rem + 0.55vw, 1.375rem)` | Fluid lead type |
| `--cw-font-size-xl` | `clamp(1.375rem, 1.2rem + 0.7vw, 1.75rem)` | Fluid small-heading type |
| `--cw-font-size-2xl` | `clamp(1.75rem, 1.45rem + 1.35vw, 2.5rem)` | Fluid heading type |
| `--cw-font-size-3xl` | `clamp(2.25rem, 1.75rem + 2.1vw, 3.25rem)` | Fluid display type |
| `--cw-font-size-4xl` | `clamp(2.75rem, 2rem + 3vw, 4rem)` | Fluid large-display type |
| `--cw-spacing-compact` | `2rem` | Compact spacing |
| `--cw-spacing-comfy` | `4rem` | Comfy spacing |
| `--cw-spacing-airy` | `8rem` | Airy spacing |
| `--cw-gap-sm` | `1rem` | Compact component gap |
| `--cw-gap-md` | `1.5rem` | Default grid gap |
| `--cw-gap-lg` | `2rem` | Spacious layout gap |
| `--cw-layout-gutter` | `1.5rem` | Horizontal content gutter |
| `--cw-width-narrow` | `600px` | Narrow width |
| `--cw-width-standard` | `900px` | Standard width |
| `--cw-width-wide` | `1200px` | Wide width |
| `--cw-width-full` | `100%` | Full width |
| `--cw-radius-sm` | `4px` | Controls and compact surfaces |
| `--cw-radius-md` | `8px` | Cards and larger surfaces |
| `--cw-radius-pill` | `999px` | Pills and circular treatments |
| `--cw-shadow-sm` | `0 2px 8px rgb(0 0 0 / 8%)` | Subtle elevation |
| `--cw-shadow-md` | `0 8px 24px rgb(0 0 0 / 12%)` | Raised-card elevation |
| `--cw-duration-fast` | `100ms` | Immediate feedback |
| `--cw-duration-normal` | `200ms` | Standard transition |
| `--cw-duration-slow` | `300ms` | Larger visual movement |
| `--cw-ease-standard` | `ease` | Standard timing function |

## Extension API Reference

### Filters
- `cinderwell_render_{block_name}` — Override block markup. Params: `$html`, `$attributes`
- `cinderwell_design_tokens` — Customize tokens. Params: `$tokens`, `$locale`
- `cinderwell_gravity_form_args` — GF shortcode args. Params: `$args`, `$form_id`, `$attributes`
- `cinderwell_loop_post_type_allowed` — Allow an intentional non-public Loop source. Params: `$allowed`, `$post_type_object`, `$attributes`
- `cinderwell_loop_link_behavior` — Filter `page`/`none` item linking. Params: `$behavior`, `$post_type`, `$attributes`
- `cinderwell_token_manifest` — Add tokens to manifest. Params: `$manifest`
- `cinderwell_enable_accent_colors` — Show optional Accent 1–3 tokens and FSE presets. Return `false` from a client theme to remove them.
- `cinderwell_sync_theme_json_tokens` — Enable or disable the starter theme bridge that supplies the WordPress palette, content widths, canvas colors, and link color from the Cinderwell registry. Params: `$enabled`, `$theme_json`
- `cinderwell_enable_block_settings_clipboard` — Enable or disable the editor settings clipboard after the saved Advanced preference is resolved.
- `cinderwell_admin_bar_capability` — Change the capability required for the shared admin-bar root

### Actions
- `cinderwell_register_blocks` — Register custom blocks
- `cinderwell_register_patterns` — Register custom patterns
- `cinderwell_admin_bar_menu` — Add add-on nodes beneath the shared Cinderwell admin-bar root
- `cinderwell_loop_item_after_title` — Render structured Loop metadata after an item title

### Optional core modules
- **Teams** (`teams`) — Disabled by default and enabled under Cinderwell → Add-Ons. Registers `cw_person`, `cw_people_category`, profile fields/settings, and the People variation of `cinderwell/loop`. Public profile URLs are independently optional; each People Loop chooses accessible modal, page, or non-interactive profile behavior.
- **Portfolio** (`portfolio`) — Disabled by default and enabled under Cinderwell → Add-Ons. Registers `cw_project`, `cw_portfolio_category`, configurable project fields, a focused Loop variation, and plugin-fallback FSE templates. Keep its standard template slugs so child themes override them from `templates/`.
- Add bundled modules through `cinderwell_bundled_addons`; add settings tabs through `cinderwell_settings_tabs`.
- Use `Admin_Fields` for bundled-module settings and post-meta forms. Field definitions should be schema-driven and filtered so future modules such as Company Details do not duplicate rendering and sanitization.

## Common Tasks

### Adding a new block
1. Create directory in `src/blocks/{name}/`
2. Create `block.json` with attributes and `supports: false` for all design controls
3. Write `index.js` with edit + save functions
4. Write `style.css` and `editor.css`
5. Block auto-discovered by `class-block-loader.php`
6. Test in inserter under "Cinderwell" category

### Adding a new atom
1. Create directory in `src/atoms/{name}/`
2. Create `block.json` and `index.js`
3. Export for use in other blocks if needed
4. Document in AGENTS.md atom reference

### Adding a new pattern
1. Add to `get_patterns()` in `includes/class-pattern-loader.php`
2. Use block markup syntax (`<!-- wp:cinderwell/blockname /-->`)
3. Test in inserter > Patterns

### Adding a new design token
1. Add to `get_manifest()` in `includes/class-design-tokens.php`
2. Token name: `cw_{category}_{name}`, lowercase, underscores
3. Add default CSS to `src/shared/base.css`
4. Document in TOKENS.md

### Extending for a client
1. Add custom blocks via `cinderwell_register_blocks` hook in theme
2. Add custom patterns via `cinderwell_register_patterns` hook
3. Override tokens via `cinderwell_design_tokens` filter
4. Override templates via `theme/cinderwell/{block}/index.html`
5. Configure block availability and editing guardrails via Cinderwell > Editor Access
6. Depend on the public `cinderwell-base`, `cinderwell-actions`, `cinderwell-responsive`, and `cinderwell-media` style handles as needed

The Cinderwell Starter theme declares `cinderwell-design-tokens` support. While
that support is active, core synchronizes the WordPress `theme.json` palette,
content and wide sizes, canvas colors, and link color from the registered token
manifest. Client child themes must not duplicate those values in `theme.json`.
Override a palette name by changing the manifest entry's `label`; expose or hide
a color by changing its `palette` metadata.

New blocks automatically participate in the settings clipboard when their
attributes follow the shared Editor Access grouping conventions. Clipboard
pastes must preserve content, links, media selections, and dynamic data.

## Testing

- `wp-scripts build` must complete without errors
- All blocks must render in inserter
- No design controls must be visible
- axe-core must report zero violations on sample page
- RTL must flip layout correctly

## Gotchas

- Prefer JS `save()` for static content. Use metadata `render` for data-driven blocks such as Loop, Gravity Form, Tabs, and Utility Bar.
- Every block must include all `supports: false` to strip design controls
- Button array is capped at 3 — enforce in editor UI
- Image alt text is required — block saves disabled without it
- Token names must match `/^cw_[a-z0-9_]+$/`
- RTL requires `[dir="rtl"]` selector, not `.rtl` class
- Schema goes in `data-cw-schema` attribute, not separate meta box
- Gravity Forms block needs `GFForms` class check before rendering
- Icon SVGs are inline, not external files — tree-shaking not needed for v0.1

## Patterns

1. `homepage-hero` — Hero + CTA stacked
2. `about-team` — Body + Image/Text + Quote
3. `services-grid` — Section + 3 CTA blocks
4. `contact-form` — Body + Gravity Form
5. `feature-list` — Section + multiple Image/Text
6. `testimonial-row` — Testimonials block with 3 client stories

## Phasing

### v0.1 (current)
All documented blocks, atoms, patterns, design system, extension API, translation, RTL, WCAG 2.1 AA, JSON-LD schema, Gravity Forms, settings page (General tab), documentation.

### v0.2 (next)
Permissions tab GUI, Design Tokens tab GUI, custom tokens via GUI, block variations, print styles.
