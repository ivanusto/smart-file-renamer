=== Smart File Renamer ===
Contributors: ivanusto
Tags: upload, files, rename, special characters, seo
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.2.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically renames uploaded files containing accents and special characters to improve SEO and maintain consistency.

== Description ==

Smart File Renamer is a WordPress plugin that automatically sanitizes uploaded file names into clean, SEO-friendly slugs. It uses WordPress's built-in `remove_accents()` to transliterate over 200 Latin diacritics to their ASCII equivalents, then strips remaining special characters and normalizes separators.

= Key Features =

* Transliterates Latin accented characters (200+) using WordPress core
* Normalizes spaces and underscores to hyphens
* Strips all remaining non-ASCII characters
* Converts file names to lowercase
* Collapses consecutive hyphens
* Safe fallback name when the entire file name is stripped
* Optional date prefix (YYYY-MM-DD) for chronological file organization
* Simple settings interface under Settings → File Renamer

= Use Cases =

* Multilingual websites uploading files with accented names
* Media-heavy websites requiring consistent slug-style naming
* Educational institutions and international businesses

This plugin's functionality is also integrated into Omni Webmaster & SEO Suite, an all-in-one webmaster toolkit by the same author available on WordPress.org: https://wordpress.org/plugins/omni-webmaster-seo-suite/ — do not run both at once; the suite automatically yields to this standalone plugin to avoid renaming files twice.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/smart-file-renamer`, or install directly through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → File Renamer** to configure the plugin.

== Frequently Asked Questions ==

= What happens to existing files? =

The plugin only affects new uploads. Existing files will not be renamed.

= What happens if the file name becomes empty after sanitization? =

The plugin generates a safe fallback name using the current Unix timestamp (e.g. `file-1718000000.jpg`).

= Does it support CJK (Chinese, Japanese, Korean) characters? =

CJK characters are stripped from the file name since there is no standard ASCII transliteration. Consider renaming such files before uploading.

= Can I customize the renaming format? =

You can enable or disable the date prefix. Additional format options may be added in future releases.

== Screenshots ==

1. Plugin settings page

== Changelog ==

= 1.2.3 =
* Fixed: WordPress 7.1 renamed every sub-size twice. Its client-side media processing generates sub-sizes in the browser and posts them back one at a time to `/wp/v2/media/{id}/sideload`, along with companion files such as the HEIC original of a converted photo or the video an animated GIF becomes. Those go through the same upload prefilter as a normal upload, but the name the browser sends is already derived from the stored base name, so the date prefix was applied a second time: `photo-150x150.jpg` was stored as `2026-08-20-2026-08-20-photo-150x150-1.jpg`. The trailing `-1` came with it, because a name that no longer begins with the attachment's base name stops core's `filter_wp_unique_filename()` from stripping the collision suffix. Sideloaded files are now left alone.
* Sites with the date prefix option disabled were unaffected: the remaining rules are idempotent, so a name that has already been normalized comes back unchanged.
* Tested against WordPress 7.1.

= 1.2.2 =
* Fixed: renaming was hooked to the global `sanitize_file_name` filter, which WordPress, themes, and plugins run over every string they treat as a file name — not just uploads. Generated cache files came back altered (underscores turned into hyphens, non-Latin characters stripped entirely), so any code that writes a file under one name and reads it back under another silently failed. On one site this surfaced as the theme's header and main menu rendering nothing at all.
* Renaming now runs on `wp_handle_upload_prefilter` and `wp_handle_sideload_prefilter`, so only files that are genuinely being uploaded or sideloaded are touched. The renaming rules and the date prefix option are unchanged.

= 1.2.1 =
* Settings are no longer deleted on plugin deactivation; cleanup now happens only when the plugin is uninstalled (new uninstall.php).
* Fixed broken Plugin URI / Author URI links in the plugin header (wrong GitHub username).
* Updated license metadata in readme.txt to GPLv2 or later to match the plugin header and bundled LICENSE.
* Noted the Omni Webmaster & SEO Suite integration (now live on WordPress.org) in the readme.

= 1.2.0 =
* Code optimization: convert file extensions to lowercase during sanitization to avoid server/browser path mismatches on case-sensitive OS environments.
* Enforced singleton typed properties for PHP 7.4+ type safety.
* Added default English and Traditional Chinese README files.

= 1.1.0 =
* Replaced custom character map with WordPress built-in `remove_accents()` (200+ Latin diacritics)
* Added safe fallback name when file name is fully stripped
* Fixed trailing-dot bug when uploading files without an extension
* Added underscore-to-hyphen normalization
* Added `sanitize_callback` to `register_setting()` for proper input validation
* Refactored to singleton pattern; removed global variable
* Bumped minimum PHP requirement to 7.4

= 1.0.0 =
* Initial release
* Basic file renaming functionality
* Settings page with date prefix option

== Upgrade Notice ==

= 1.1.0 =
Improved transliteration, edge-case fixes, and code quality improvements. No database changes required.

== License ==

This plugin is released under the GNU General Public License v2.0 or later.
See: https://www.gnu.org/licenses/gpl-2.0.html
