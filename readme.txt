=== Smart File Renamer ===
Contributors: ivanlin
Tags: upload, files, rename, special characters, seo
Requires at least: 5.0
Tested up to: 6.7.1
Stable tag: 1.1.0
Requires PHP: 7.4
License: Apache-2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0

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

This plugin is one of the origin projects of Omni Webmaster & SEO Suite, an all-in-one webmaster toolkit by the same author that consolidates and optimizes these standalone plugins: https://github.com/ivanusto/omni-webmaster-seo-suite

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

This plugin is released under the Apache License 2.0.
See: http://www.apache.org/licenses/LICENSE-2.0
