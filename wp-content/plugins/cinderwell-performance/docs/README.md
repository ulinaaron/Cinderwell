# Cinderwell Performance

Two opt-in modules for performance optimization:

1. **Performance** — output cleanup, image hints, and resource hints
2. **Hydration** — Intersection Observer-based down-the-page hydration of interactive blocks

## Default State

Almost everything is **OFF by default**. The plugin is safe to activate without configuration — zero behavior change unless you enable a feature.

## Performance Module

| Feature | Default | Risk |
|---|---|---|
| HTML cleanup | ON | Low |
| Image async decoding | ON | None |
| Image lazy loading | ON | Low |
| Image fetchpriority | OFF | Medium |
| Width/height injection | OFF | Medium |
| Resource hints | OFF | Low |

CSS is deliberately left in WordPress's block-aware loading pipeline. Combining
stylesheets defeats conditional block loading and can change cascade order or
break relative asset URLs.

## Hydration Module

| Feature | Default |
|---|---|
| Master toggle | OFF |
| Auto-instrument interactive blocks | (when master ON) |
| rootMargin | 200px |
| Safe mode | ON (when master ON) |
| SEO bot bypass | ON (when master ON) |

## Interactive Blocks (auto-instrumented)

Button, Popup Trigger, FAQ, Gallery/Carousel, Tabs, Accordion, Video embed, Gravity Form, Two Column

## Static Blocks (NOT instrumented)

Header, Divider, Note, Eyebrow, Hero, Body, CTA, Quote, Image + Text, Section

## Per-Block Override

```html
<div data-cw-hydrate="false">   <!-- force immediate -->
<div data-cw-hydrate="true">    <!-- force deferred -->
```

## Fallbacks

- **No JS** = no hydration = server-side HTML works
- **No IntersectionObserver** = hydrate all at load
- **JS error after hydration** = safe mode triggers, hydrate all remaining immediately
- **SEO bot** = bypass observer, hydrate immediately
