# Cinderwell SEO

A focused Cinderwell add-on for search metadata, social sharing, explainable content analysis, native WordPress sitemap controls, and minimal structured data.

## Principles

- WordPress remains the title, canonical, robots, and sitemap foundation.
- Focus phrases support analysis and are never emitted as obsolete meta keywords.
- Search visibility describes local configuration, not confirmed search-engine indexing.
- Another SEO provider puts the add-on into safe standby without deleting data.
- Content-owning add-ons can enforce a stricter search policy without depending on Cinderwell SEO.
- All editor guidance is advisory and never blocks publishing.

## Members Portal integration

Members Portal owns the privacy decision for whole-page restrictions and its
login, registration, and account utility pages. Cinderwell SEO reflects that
decision with a locked noindex state, omits those pages from SEO scoring, and
suppresses descriptions, social metadata, and page-level schema. Public pages
that only contain a Member Only block remain indexable.

## Extension points

- `cinderwell_seo_supported_post_types`
- `cinderwell_seo_supported_taxonomies`
- `cinderwell_seo_conflicting_providers`
- `cinderwell_seo_document`
- `cinderwell_seo_analysis_checks`
- `cinderwell_seo_schema_graph`

Use `cinderwell_seo_get_document()` and `cinderwell_seo_get_search_visibility()` for theme or add-on integrations.

## Current schema coverage

- `WebSite` for the site.
- `WebPage` for public pages and supported custom content.
- `Article` for posts.
- `Organization` references when Company Details organization schema is enabled.
- `cinderwell_seo_schema_graph` for client-theme and add-on extensions.

Specialized Product, Event, Person, breadcrumb, and similar schema are intentionally not generated yet.
