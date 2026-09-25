=== Cinderwell Members Portal ===
Contributors: stevens
Tags: cinderwell, members, login, registration, private
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Requires Plugins: cinderwell
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private member area add-on for Cinderwell. Two registration modes: open with moderation, or admin-issued.

== Description ==

Add a private member area to your WordPress site powered by the Cinderwell block library.

**Registration Modes:**
* Open sign-up with admin moderation
* Admin-issued logins (with welcome email)
* Both at once

**Features:**
* Protect any page or post with one click (meta box toggle)
* Automatically noindex protected and portal utility pages and omit them from WordPress XML sitemaps
* Member Only block for partial content gating
* Login, Register, and Member Profile blocks
* Editable email templates (5 transactional emails)
* Full REST API for external integrations
* WP All Import compatible for bulk member import
* Uses Cinderwell design tokens for consistent styling

== Installation ==

1. Upload to `wp-content/plugins/cinderwell-portal/`
2. Activate the plugin
3. Go to **Cinderwell > Members Portal** to configure

== Frequently Asked Questions ==

= Does it require the Cinderwell plugin? =

Yes. Cinderwell Members Portal is an add-on for the Cinderwell block library.

= Can I import members in bulk? =

Yes. Use WP All Import with the "Create Users" feature. Map `user_email`, `first_name`, `last_name`, and set `role` to `cinderwell_member`.

== Changelog ==

= 0.1.0 =
* Initial release
