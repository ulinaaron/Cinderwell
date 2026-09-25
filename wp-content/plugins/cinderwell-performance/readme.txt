=== Cinderwell Performance ===
Contributors: stevens
Tags: cinderwell, performance, hydration, lighthouse, optimization
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Requires Plugins: cinderwell
Stable tag: 0.1.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block-aware performance optimization. HTML cleanup, image hints, resource hints, and down-the-page hydration. All features opt-in.

== Description ==

Two opt-in modules for WordPress performance:

**Performance Module:**
* HTML cleanup — remove type attributes, empty paragraphs, trailing whitespace
* Image optimizations — async decoding, lazy loading, fetch priority, dimension injection
* Resource hints — preconnect and dns-prefetch
CSS remains in WordPress's block-aware loading pipeline so only required block styles load and their cascade order is preserved.

**Hydration Module:**
* Intersection Observer-based deferred hydration of interactive blocks
* Safe mode auto-disables on JS errors
* SEO bot bypass — crawlers get full HTML immediately
* Per-block override via data attributes

Almost everything is OFF by default. Zero behavior change unless you enable a feature.

== Installation ==
1. Upload to wp-content/plugins/cinderwell-performance/
2. Activate
3. Configure in Cinderwell → Performance

== Changelog ==
= 0.1.2 =
* Adopt the shared Cinderwell settings interface and save-state controls.

= 0.1.1 =
* Remove unsafe CSS combining and preserve WordPress block-aware stylesheet loading

= 0.1.0 =
* Initial release
