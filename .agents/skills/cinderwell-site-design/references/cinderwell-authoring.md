# Cinderwell authoring

## Constraints

- Assemble marketing and demonstration pages with `cinderwell/*` blocks.
- Do not compose pages from Core Group, Columns, Cover, or Custom HTML blocks.
- Put site-specific presentation in the active child theme or a plugin extending Cinderwell.
- Keep the starter theme and core plugin free of client-specific styling.
- Treat missing expressive capability as a product-gap decision, not permission for one-off markup.

## Inspect before composing

Block capabilities evolve. Before a substantial build, inspect registered `block.json` files, available variations, child-theme patterns, and the active rendered site. Do not assume this inventory is current merely because this reference lists it.

Before adding CSS for any requested change, classify it as one of four things:

1. an existing block attribute or inspector control;
2. an exposed Cinderwell token;
3. presentation that belongs in a named custom variation;
4. a reusable product gap.

Do not implement categories 1 or 2 as CSS. If the desired result requires only a native width, spacing, color, alignment, or button setting, store that attribute in the block and include it in the variation defaults for future insertions. Use variation CSS only for category 3. Record category 4 rather than hiding it behind broad child-theme overrides.

Current composition families include:

- Structure: Section, Columns, Column, Body, Heading, Divider.
- Storytelling: Hero, Image + Text, Card Grid, Gallery, Image Carousel, Content Slider, Video, Quote, Testimonials.
- Explanation: Icon List, Accordion, FAQ, Tabs, Note.
- Conversion: CTA, Button, Link, Form, Gravity Form, Company Details, Map.
- Discovery and dynamic content: Loop, Mega Menu, Page Header, Header Search.
- Add-on content: Events, Portal, Popups, and other enabled modules.

## Mapping design intent

- Use Hero for page-level orientation and meaningful visual accompaniment. Configure responsive focal positioning for directional imagery. When a single crop cannot work, use Cinderwell visibility settings for coordinated desktop and mobile Hero instances rather than accepting a broken focal point.
- Use Image + Text for editorial storytelling, process, people, environments, and alternating narrative rhythm.
- Use Card Grid when items genuinely share a repeated schema; do not use it as the default for unrelated ideas.
- Use Body for substantial prose with a controlled measure.
- Use Number for measurable proof, statistics, and outcomes. Compose repeated Number blocks inside Cinderwell Columns for metric rails; do not embed a hand-authored statistics grid inside Body.
- Use Icon List for scannable benefits, requirements, services, or checklist content.
- Use Quote or Testimonials for attributed proof; select cards or carousel based on content volume and context.
- Use Content Slider when each synchronized item has its own narrative copy, action, and corresponding media. Use Image Carousel for primarily visual browsing and Tabs for topic switching; neither is a substitute for a story-and-media sequence. Keep every slide available in the non-JavaScript baseline, avoid autoplay by default, label the region and controls, announce the selected slide, and test keyboard operation and focus visibility.
- Use Loop for real post types and collections rather than manually recreating dynamic content.
- Use CTA for a section-level conclusion, not every ordinary link.
- Use Section and Columns to establish structure while preserving Cinderwell controls and semantics.
- Use Divider for an actual visual transition, not to manufacture vertical space.

## Variations and child-theme styling

A presentation variation should express a reusable visual treatment while preserving the base block's content model, semantics, inspector conventions, responsive behavior, and accessibility. Scope its CSS to the variation and child theme. Avoid fragile selectors tied to editor-generated class names.

Every variation registered by a client theme or add-on must declare
`custom => true`. This is only a provenance declaration. Cinderwell core owns
normalization and the consistent editor indicator; never implement a separate
client-side badge or custom-variation UI. Core variations omit the flag and
normalize to `false`.

Use variations to carry the site's structural signature into the editor. A distinctive treatment should be selectable and previewable as a named Cinderwell presentation—not exist only as an unexplained frontend override. Suitable variation responsibilities include alignment-grid behavior, repeated numbering, image framing, proof rails, and concluding CTA treatments. Keep the content schema owned by the base block.

Variation attributes are defaults applied when the author selects the recipe; do not reassert those same values in variation CSS. Width, spacing, background, text color, type size, alignment, overlay, and other existing controls must remain live unless the recipe genuinely cannot function without owning them. If a variation must own a setting, register it through the core `controlled` map, keyed by the attribute or shared control group, with a short author-facing reason. The inspector must then disable the affected control and show that it is controlled by the active variation. Never leave an enabled control whose output is defeated by stronger CSS.

```php
'controlled' => [
	'width' => __( 'This composition requires a full-width reading field.', 'client-theme' ),
	'typography' => __( 'Type treatment is part of this editorial recipe.', 'client-theme' ),
],
```

Use `controlled` sparingly. Prefer ordinary registered attributes whenever the existing block contract can express the design; a custom variation is not permission to replace working inspector behavior.

When custom CSS is necessary:

- register client values as Cinderwell token defaults by filtering `cinderwell_token_manifest` and replacing the relevant manifest entries' `default` values. Cover the complete brand contract used by the site: palette, foregrounds, borders, links, focus, buttons, typography, widths, spacing, radii, and image treatment as applicable;
- register client-facing palette names by replacing the same manifest entries' `label` values. On the Cinderwell Starter theme, core derives the WordPress palette, content and wide sizes, canvas colors, and link color from this registry; do not duplicate those values in the child `theme.json`;
- before removing duplicated values from an existing child `theme.json`, compare them with the token manifest. Treat every mismatch as hidden design drift: intentionally move the desired value into the manifest or discard it, rather than assuming the two sources were equivalent;
- use manifest `label` values for author-facing palette names and manifest `palette` metadata for whether a token appears in color controls. Hide an optional color by changing its manifest exposure, not by rebuilding a partial palette in `theme.json`;
- register layout decisions such as the shared page gutter through their Cinderwell tokens instead of creating parallel child-theme variables. Full-width sections, headers, and full-width block attributes should resolve against one gutter source;
- do not treat `:root` declarations in the client stylesheet as token registration. CSS-only values leave block controls, reset actions, the color registry, automatic contrast, and editor tools on Cinderwell's built-in defaults;
- use registered Cinderwell tokens in variation CSS instead of repeating raw brand values throughout recipes;
- consume semantic component tokens in component rules. For example, primary buttons must use `--cw-button-primary-*`; do not restyle them from `--cw-color-brand` after registering a distinct action color;
- give horizontal gutters one owner. Inspect the computed padding on the block root and inner container before adding either; a full-width attribute plus duplicated outer and inner gutters is a product gap, not a cue to layer more width overrides;
- audit variation CSS for `!important`, fixed colors, fixed type sizes, fixed widths, and fixed spacing that duplicate inspector controls. Remove those overrides or explicitly register the corresponding control as `controlled` with an explanation;
- when a presentation materially replaces or disables direct block controls, register that constraint as a named custom variation and scope it to the emitted `.cinderwell-{block}--layout-{slug}` modifier instead of hiding it in page-specific CSS;
- enqueue the client presentation stylesheet on `enqueue_block_assets` with `cinderwell-base` as a dependency so WordPress carries one shared stylesheet into both the frontend and the iframed editor. Do not maintain separate frontend/editor recipe files, and do not assume `add_editor_style()` reached a modern iframe without inspecting its loaded assets;
- verify one representative heading, link, primary button, secondary button, form control, and dark-surface element with computed frontend styles before composing the full site; check both the resolved token and the actual component. If parent defaults still win, locate the winning selector and repair the cascade contract instead of accumulating page-specific specificity;
- verify the resolved theme data and editor settings, not merely that the canvas looks approximately correct. Confirm `WP_Theme_JSON_Resolver::get_theme_data()` contains the manifest-backed palette and widths, `get_block_editor_settings()` exposes the intended client labels and colors, and `wp_get_global_stylesheet()` bridges WordPress presets to the matching `--cw-*` variables;
- verify the actual computed colors on every custom surface; child-theme token assumptions can be displaced by preset or block styles, so a named presentation recipe may need explicit contrast-safe foreground and background values;
- preserve consistent inner gutters across full-width sections, headers, footers, and narrow breakpoints;
- preserve editor/frontend parity;
- test absence, short content, long content, and optional media;
- test narrow layouts and zoom;
- respect reduced motion and user contrast needs;
- do not change document order merely to achieve a visual arrangement.

## Programmatic block authoring and recovery safety

When generating or repairing WordPress block content through PHP, serialize parsed block arrays with `serialize_blocks()` and pass the result to `wp_update_post()` or `wp_insert_post()` through `wp_slash()`. WordPress unslashes post data before storage; omitting `wp_slash()` can strip JSON escape characters from rich-text attributes, invalidate otherwise correct Cinderwell blocks, and turn “Attempt Recovery” into visible encoded markup.

For static blocks, changing the comment-delimited attributes does not regenerate the saved HTML produced by the block's `save()` function. Prefer changing such attributes through Gutenberg and saving normally. If programmatic mutation is required, update the matching saved `innerHTML` and `innerContent` deterministically or use the block's JavaScript serializer; then verify the block in Gutenberg. A comment/markup mismatch is invalid even when the frontend appears correct.

Do not store arbitrary `className` attributes on Cinderwell blocks whose `supports.customClassName` is false. Express substantial presentation through a registered `layout` variation and its child-theme stylesheet. After any programmatic write, reopen the page in Gutenberg and verify:

- no block offers “Attempt Recovery”;
- every intended named variation class appears in the editor canvas;
- the client stylesheet and resolved token CSS are present in the editor iframe;
- representative editor computed styles match the published frontend;
- saving and reopening the page does not change block validity.

## Content fidelity

Use realistic names, descriptions, projects, testimonials, posts, and imagery in demonstrations. Avoid generic filler that conceals whether the composition can support real content. Ensure image alternatives describe the image's purpose in that page context.
