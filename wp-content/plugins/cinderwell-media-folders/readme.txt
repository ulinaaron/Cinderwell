=== Cinderwell Media Folders ===
Contributors: stevens
Tags: cinderwell, media, folders, happyfiles
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Requires Plugins: cinderwell
Stable tag: 0.2.6
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Folder/collection system for the WordPress media library. Imports from HappyFiles and WP Media Folder.

== Description ==

Add folder organization to your media library without changing attachment URLs. Create and search nested folders, move single or selected media with drag-and-drop, filter media pickers, and migrate safely from HappyFiles or WP Media Folder.

Folders are a private organizational taxonomy. Moving a file never moves the physical upload or changes content that already uses it.

== Installation ==
1. Upload to wp-content/plugins/cinderwell-media-folders/
2. Activate
3. Use Media → Library to manage folders

== Changelog ==
= 0.2.6 =
* Initialize the folder layout as soon as complete Media Library markup is parsed

= 0.2.5 =
* Initialize the Media Library folder layout as soon as its footer script executes

= 0.2.4 =
* Fixed Move to folder resetting to Bulk actions before it could be applied

= 0.2.3 =
* Made list-view folder moves use WordPress's registered bulk-action lifecycle
* Added keyboard submission support and a clear warning when no media is selected

= 0.2.2 =
* Added child-folder tiles to grid views and Ctrl/Command-click multi-selection
* Integrated list-view folder moves into the native bulk-actions menu
* Removed the redundant active-folder toolbar chip

= 0.2.1 =
* Added a native bulk-move workflow for selected media in grid and list views

= 0.2.0 =
* Added searchable, collapsible folder tree with inline create, rename, subfolder, and delete actions
* Added bulk drag-and-drop filing and an Unfiled destination
* Added list-view and media-modal filtering
* New uploads inherit the folder currently being viewed
* Fixed nested and repeat imports from HappyFiles and WP Media Folder
* Hardened REST permissions and per-attachment authorization
* Refined Cinderwell settings and import experience

= 0.1.0 =
* Initial release
