# Cinderwell

A curated Gutenberg block library with design system constraints. Content editors get clean, accessible blocks with only content fields and design-system dropdowns — no raw design controls.

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
| Hero | Page-level hero with eyebrow, heading, image, buttons |
| Body | Rich text body with optional eyebrow, heading, byline, pullquote |
| CTA | Call-to-action with eyebrow, heading, body, buttons |
| Card Grid | Responsive cards with optional imagery, icons, labels, and actions |
| Icon List | Semantic lists with a shared default icon and per-item overrides |
| Image + Text | Side-by-side image and text content |
| Quote | Blockquote with attribution, byline, context |
| Gallery | Image gallery with multiple images |
| Image Carousel | Accessible image slider with touch, keyboard, arrows, and indicators |
| FAQ | Accordion FAQ with FAQPage schema |
| Accordion | Expandable, sortable content panels |
| Tabs | Accessible responsive tabs with block-based content panels |
| Slot Layout | Controlled grid assembled from approved slot types |
| Two Column | Two-column layout with independent content |
| Section | Generic container with InnerBlocks |
| Loop | Dynamic post and custom-post-type listing with filtering and pagination |
| Mega Menu | Wide, responsive content panels nested in the native Navigation block |
| Gravity Form | Gravity Forms integration with design token styling |

## Atoms

- **Button** — Variants: primary, secondary, ghost, link. Sizes: sm, md, lg
- **Heading** — Standalone heading with level selection (h1-h4)
- **Image** — Standalone image with required alt text
- **Icon** — Lucide icons via dropdown selection
- **Link** — Styled inline link
- **Divider** — Visual separator with weight options
- **Note** — Styles: disclaimer, caption, footnote, legal

## Design System

CSS custom properties prefixed `--cw-*` cover semantic colors, typography and
line height, spacing, layout gaps and gutters, widths, radii, shadows, images,
and motion. Override them from the Design Tokens sidebar in the block editor.

## Extension API

See `EXTENDING.md` for filter and action hooks.

## License

GPL-2.0-or-later
