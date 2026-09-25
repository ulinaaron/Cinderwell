# Cinderwell Marketing

Site-specific child theme for the Cinderwell product and demonstration site.

The updateable `cinderwell-starter` parent owns the FSE template hierarchy and
minimal site frame. This child theme owns the Cinderwell site's navigation,
footer content, product-specific tokens, and presentation CSS.

Public product marketing lives at the site root. Demonstration content lives
under `/demo/` and uses the `header-demo` template part. WooCommerce routes,
account/cart controls, and demo child pages share that scoped header; the
marketing header intentionally contains no commerce controls.

Marketing pages must be assembled from Cinderwell blocks. Expressive layouts
should be implemented through existing block settings, Cinderwell presentation
variations, or child-defined variations registered through
`cinderwell_block_variations`. The child theme must not introduce a parallel
page-building layer based on Core layout blocks or Custom HTML.

## Site variation vocabulary

The child theme currently registers these site-owned recipes:

- Hero: Product Stage, Manifesto
- Card Grid: Metrics, Ecosystem
- Image + Text: Product Showcase
- Two Column: Handoff
- Slot Layout: Architecture
- CTA: Closing Statement

Reusable Product Stage, Metrics Rail, and Product Showcase patterns live in
`patterns/`. Their content is composed only from Cinderwell blocks; the pattern
is an assembly shortcut, not a separate layout system.
