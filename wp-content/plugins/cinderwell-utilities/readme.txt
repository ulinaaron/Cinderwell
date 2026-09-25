=== Cinderwell Site Utilities ===
Contributors: stevens
Tags: cinderwell, admin, utilities, updates, duplicate, svg, comments, feeds, sendgrid, email
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: cinderwell
Stable tag: 0.4.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Admin and site quality-of-life modules for Cinderwell. Each module is independently toggleable.

== Description ==

A grab-bag of admin and site quality-of-life modules. Each one is independently toggleable in the Cinderwell admin. Enable only what you need.

**Modules:**
* Content Duplication — one-click duplicate from list view, edit screen, or admin bar
* Content Order — drag-and-drop ordering for hierarchical post types
* Taxonomy Terms Order — drag-and-drop ordering for taxonomy terms
* Media Replacement — replace media files while keeping the same URL and ID
* Allow SVGs — enable SVG uploads with automatic sanitization
* Login Branding — use the Company Details logo and name on the WordPress login screen
* Search Visibility Status — show a compact admin-bar status when search-engine indexing is discouraged
* Plugin Update Control — disable plugin auto-updates and lock selected plugins against manual updates
* Mail Delivery — route WordPress email through SendGrid with tests and a metadata-only log
* Disable Comments — site-wide comment disabling
* Disable Feeds — disable all RSS/Atom/RDF feeds
* Disable Smaller Components — bundle of micro-disablers (emoji, embed, jQuery Migrate, etc.)

== Installation ==
1. Upload to wp-content/plugins/cinderwell-utilities/
2. Activate
3. Configure in Cinderwell > Site Utilities

== Changelog ==
= 0.4.0 =
* Replace parallel module maps with one filterable, self-describing module registry.
* Use one field schema for defaults, form saves, REST updates, and settings imports.
* Add explicit versioned settings migrations and move the plugin lock rename out of read-time logic.
* Include Site Utilities in Cinderwell settings exports with schema-owned import sanitization.
* Keep the admin-only Site Utilities configuration out of WordPress's autoloaded options.

= 0.3.3 =
* Remove the persistent search-visibility banner from admin screens.
* Keep search visibility available in the admin bar and Site Utilities settings.

= 0.3.2 =
* Simplify Plugin Update Control to one concise status and management action.

= 0.3.1 =
* Rename plugin freeze controls to clearer lock and unlock language.
* Save Site Utilities settings before opening the Plugins screen from Plugin Update Control.

= 0.3.0 =
* Add opt-in Plugin Update Control with globally disabled plugin auto-updates.
* Add per-plugin and bulk lock controls that block manual updates while preserving update notices.

= 0.2.1 =
* Add the authenticated SendGrid sending domain to Mail Delivery settings.
* Validate that the configured From address uses the sending domain or one of its subdomains.

= 0.2.0 =
* Add opt-in SendGrid delivery for all WordPress mail.
* Add sandbox validation, real test sends, and a Cinderwell Mail screen.
* Add a metadata-only mail log with filtering, pagination, clearing, and 30-day retention.
* Add a provider adapter contract for future mail services.

= 0.1.4 =
* Support SVG Company Details logos in the login-branding preview and WordPress login screen.

= 0.1.3 =
* Keep the search-visibility admin notice out of the Cinderwell Help header while retaining the admin-bar status.

= 0.1.2 =
* Add opt-in Company Details login branding.
* Add opt-in search-engine visibility warnings in the admin bar and dashboard.
* Redesign the utility settings screen with clearer states, grouped controls, live counts, and responsive layouts.

= 0.1.1 =
* Rebuilt Media Replacement on supported WordPress attachment fields and meta boxes.
* Preserve attachment URLs by requiring the same file extension.
* Add capability checks, safety backups, error handling, and stale thumbnail cleanup.

= 0.1.0 =
* Initial release
