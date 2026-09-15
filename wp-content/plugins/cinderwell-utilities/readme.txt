=== Cinderwell Site Utilities ===
Contributors: stevens
Tags: cinderwell, admin, utilities, duplicate, svg, comments, feeds
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Requires Plugins: cinderwell
Stable tag: 0.1.1
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
* Disable Comments — site-wide comment disabling
* Disable Feeds — disable all RSS/Atom/RDF feeds
* Disable Smaller Components — bundle of micro-disablers (emoji, embed, jQuery Migrate, etc.)

== Installation ==
1. Upload to wp-content/plugins/cinderwell-utilities/
2. Activate
3. Configure in Cinderwell > Site Utilities

== Changelog ==
= 0.1.1 =
* Rebuilt Media Replacement on supported WordPress attachment fields and meta boxes.
* Preserve attachment URLs by requiring the same file extension.
* Add capability checks, safety backups, error handling, and stale thumbnail cleanup.

= 0.1.0 =
* Initial release
