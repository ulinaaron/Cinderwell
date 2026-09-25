# Cinderwell

A governed WordPress foundation with a curated block kit, design-system
constraints, native code-defined content fields, and focused add-ons. Content
editors get clean, accessible controls without exposing raw implementation
details.

Optional bundled modules add reusable content systems and token-based entrance
animations without increasing the default frontend footprint.

## Requirements

- WordPress 6.3+
- PHP 7.4+
- Node.js 20.9+

## Installation

1. Clone the [Cinderwell ecosystem repository](https://github.com/ulinaaron/Cinderwell) and locate this plugin in `wp-content/plugins/cinderwell/`
2. Install dependencies in this directory: `npm ci`
3. Build: `npm run build`
4. Copy this plugin directory, including `build/`, into your WordPress site's `wp-content/plugins/cinderwell/` and activate it

## Development

```bash
npm install
npm run start    # Watch mode
npm run build    # Production build
npm run lint:js  # Lint JavaScript
npm run lint:css # Lint CSS
```

## Blocks

| Block | Description |
|---|---|
| Hero | Page-level hero with eyebrow, heading, image, buttons, and image or video backgrounds |
| Body | Rich text body with optional eyebrow, heading, byline, pullquote |
| CTA | Call-to-action with eyebrow, heading, body, buttons |
| Card Grid | Responsive cards with optional imagery, icons, labels, and actions |
| Icon List | Semantic lists with a shared default icon and per-item overrides |
| Image + Text | Side-by-side image and text content |
| Quote | Blockquote with attribution, byline, context |
| Testimonials | Multiple testimonials with optional portraits, responsive cards, or an accessible carousel |
| Gallery | Image gallery with multiple images |
| Image Carousel | Accessible image slider with touch, keyboard, arrows, and indicators |
| Video | Responsive uploaded or embedded video with poster, caption track, and playback controls |
| Map | OpenStreetMap locations with accessible details, branded pins, and Google directions |
| FAQ | Accordion FAQ with FAQPage schema |
| Accordion | Expandable, sortable content panels |
| Tabs | Accessible responsive tabs with block-based content panels |
| Content Slider | Synchronized story copy, action, and media with accessible previous/next controls and a complete non-JavaScript baseline |
| Columns | Native nested layout with draggable Cinderwell blocks, 1–4 columns, relative proportions, and responsive stacking |
| Section | Generic InnerBlocks container with tokenized image or video backgrounds |
| Loop | Dynamic listing with filtering, pagination, and four switchable presentation layouts |
| Page Header | Dynamic Page title with optional description, breadcrumbs, and per-Page overrides |
| Mega Menu | Wide, responsive content panels nested in the native Navigation block |
| Gravity Form | Gravity Forms integration with design token styling |
| Utility Bar | Compact FSE header utility links sourced from Company Details |
| Company Details | Dynamic contact, address, and weekly-hours presentations |

## Atoms

- **Button** — Variants: primary, secondary, ghost, link. Sizes: sm, md, lg
- **Heading** — Standalone heading with level selection (h1-h4)
- **Number** — Statistic or measurable result with an accessible count-up enhancement
- **Image** — Standalone image with required alt text
- **Icon** — Lucide icons via dropdown selection
- **Link** — Styled inline link
- **Divider** — Visual separator with weight options
- **Note** — Styles: disclaimer, caption, footnote, legal

## Design System

CSS custom properties prefixed `--cw-*` cover semantic colors, typography and
line height, spacing, layout gaps and gutters, widths, radii, shadows, images,
and motion. Override them from the Design Tokens sidebar in the block editor.
The color system includes semantic Info, Success, and Danger states, derived
Brand Light and Brand Dark shades, and three optional client accent colors.
These tokens are available in block Background controls; the Text control
measures the site’s current resolved colors and narrows its palette to WCAG AA
foreground pairings for the chosen surface. Changes in the token editor are
recalculated in the live block preview before they are saved.

## Extension API

See `EXTENDING.md` for filter and action hooks.

## Content fields

Cinderwell includes an always-on, code-first field registry for post types,
taxonomies, users, and site-scoped options. Client themes declare stable field
schemas and choose a document-sidebar or classic meta-box presentation;
Cinderwell supplies native storage, sanitization, REST support, Dynamic Data,
and optional sortable/filterable admin columns. ACF remains supported when it
is active, but neither ACF nor Admin Columns is required for a new client site.

## Editor Access

**Cinderwell → Editor Access** provides role-based editor guardrails. Each role
can use a Full Design, Content Editing, Text Only, or Custom preset and can
have individual Cinderwell blocks removed from its inserter. Restricted
controls are hidden and their attribute updates are filtered in Gutenberg;
existing blocks remain registered and continue to render. Administrators
always retain full access.

## Block settings clipboard

Use **Copy settings** and **Paste settings** in a Cinderwell block's Options
menu to reuse compatible layout, appearance, spacing, visibility, animation,
and image-presentation choices. Text, links, media selections, captions, and
dynamic data remain unchanged. Compatible settings can move between related
block types, while unsupported attributes are ignored. The feature respects
Editor Access and can be disabled under **Cinderwell → Settings → Advanced**.
Loop layout slugs and compatible presentation settings are included, while its
content type, filters, ordering, item count, links, and semantic Loop type are
left alone.

## Patterns and variations

Patterns are composition recipes for inserting several configured blocks.
Switchable variations are a presentation layer on an existing block instance:
they do not replace authored content or data choices. Loop includes Cards,
Media List, Minimal List, and Featured Lead. Card Grid includes Raised,
Bordered, Split Rows, Mosaic, Posters, Image Boxes, and Directory. Hero includes Split,
Statement, Editorial, Immersive, and Framed. Client themes can add, replace, or hide
these live definitions through `cinderwell_block_variations`; see
`EXTENDING.md`.

## Add-Ons

Cinderwell → Add-Ons combines independently packaged extensions
from the Cinderwell release manifest with modules bundled in core. Bundled
modules are inert until enabled and remain discoverable if the remote catalog
is temporarily unavailable.

### Teams

The bundled Teams module can use **People / Person** or **Team Members / Team
Member** terminology. It provides:

- an optional `cw_person` content type with profile photo and biography;
- hierarchical People Categories;
- first name, last name, position, and opt-in contact/social fields;
- public profile pages that can be enabled or disabled independently; and
- a focused People variation of the Loop block with category filtering and
  block-level modal, page, or non-interactive profile behavior.

Public profile URLs remain a Teams setting. Each People Loop independently
decides whether to open biographies in an accessible modal, link to those
pages when available, or present a non-interactive directory.

### Portfolio

The bundled Portfolio module provides a `cw_project` content type, hierarchical
Portfolio Categories, configurable project fields, a focused Portfolio Loop
variation, and editable block-template defaults for single items, the archive,
and category archives. Public item URLs can be disabled while content remains
available to loops.

Child themes can override the module templates with standard FSE files:

- `templates/single-cw_project.html`
- `templates/archive-cw_project.html`
- `templates/taxonomy-cw_portfolio_category.html`

The shared `Cinderwell\Admin_Fields` schema renderer/sanitizer powers Teams,
Portfolio, and Company Details settings and item metadata. Future bundled
modules should use it instead of creating another one-off table renderer and
sanitization branch.

### Company Details

The bundled Company Details module stores reusable organization identity,
logos, contact, address, hours, and named or custom social-profile information.
Its structured weekly schedule supports closed days, 24-hour availability,
overnight periods, and split hours. The dynamic Company Details block can show
Hours, Contact, Address, or All Details without copying shared information into
page content.
It can optionally output Organization JSON-LD when another SEO plugin does not
own that schema. Once enabled, stored and derived values—including formatted
address, telephone/email links, logo URLs, and a copyright line—appear in the
block editor's Dynamic Data picker under **Company**. Field definitions can be
changed with the `cinderwell_company_details_fields` filter.

### Locations

The bundled Locations module depends on Company Details and supplies a
`cw_location` content type for branches, offices, campuses, and service areas.
Location pages and archives are optional; entries remain available through the
focused Locations Loop variation when public URLs are disabled. Child themes
can override `templates/single-cw_location.html` and
`templates/archive-cw_location.html`.

### Maps

The dynamic Map block uses locally bundled Leaflet with OpenStreetMap tiles.
Editors locate an address explicitly, then the block stores coordinates so
published pages do not make geocoding requests. Its default variation shows a
single location and popup; the Multiple Locations variation supports ordered
manual pins. Location details remain available as a non-JavaScript fallback,
and directions links are derived from the address for Google Maps.

Tile and geocoding providers can be replaced with
`cinderwell_map_tile_url`, `cinderwell_map_attribution`, and
`cinderwell_map_geocoder_url`. Integrations can provide records through
`cinderwell_map_locations`. Client themes and add-ons may also register map
sources and presentation recipes, extend the sanitized record schema, and add
structured popup or directory content. The editor and frontend resolve records
through the same pipeline. See `EXTENDING.md` for the supported contract.

## Settings portability

Cinderwell → Import / Export moves add-on activation, design tokens,
and module settings between sites in a versioned JSON file. Content, media, and
FSE templates are intentionally excluded. Each bundled add-on also exposes a
reset-to-defaults action from the Add-Ons screen.

## License

GPL-2.0-or-later
