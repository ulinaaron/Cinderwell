# Cinderwell Performance — Client Guide

## How to Use

1. Activate the plugin (nothing happens yet)
2. Enable one feature at a time
3. Visit your site, verify nothing broke
4. Repeat for each feature

## Performance Features

- **HTML Cleanup** — removes unnecessary `type` attributes, empty `<p>` tags, trailing whitespace. Safe, ON by default.
- **Async Decoding** — adds `decoding="async"` to images. No visual impact, ON by default.
- **Lazy Loading** — adds `loading="lazy"` to below-fold images. ON by default. LCP image is never lazy-loaded.
- **Fetch Priority** — adds `fetchpriority="high"` to LCP image. OFF by default — can conflict with some CDNs.
- **Width/Height Injection** — reads dimensions from the media library and injects them. OFF by default — risky with responsive images.
- **Resource Hints** — inject `<link rel="preconnect">` or `<link rel="dns-prefetch">`. OFF by default.
- **CSS Delivery** — WordPress and Cinderwell keep block styles separate so each page only loads what it needs. No CSS-combining option is provided.

## Test Hydration

1. Enable hydration
2. Visit a page with multiple interactive blocks (FAQ, Tabs, Gallery)
3. Open DevTools → Performance tab
4. Verify TBT (Total Blocking Time) is lower
5. Verify interactive blocks still work when you scroll to them

## Common Issues

**Images look broken:** Width/height injection got wrong dimensions, disable it.

**Layout shifts on page load:** CLS caused by missing dimensions. Either enable width/height injection or add explicit dimensions in the editor.

**Popups or accordions don't open:** The block was deferred but hydration didn't trigger. Add `data-cw-hydrate="false"` to force immediate hydration.

**Site breaks with hydration on:** Safe mode should auto-disable. If not, disable hydration and check the browser console for errors.
