# Smart File Renamer

[繁體中文說明](README.zh-TW.md)

Automatically sanitize and rename uploaded files into clean, SEO-friendly names. It transliterates Latin diacritics, normalizes separators, strips non-ASCII characters, and lowercases both filenames and extensions to ensure compatibility across all operating systems.

![Version](https://img.shields.io/badge/version-1.2.2-blue) ![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-21759b) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4) ![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

---

## Key Features

- **Accents Transliteration**: Uses WordPress built-in `remove_accents()` to map 200+ Latin accented characters (e.g. `á` -> `a`, `ü` -> `u`) to their ASCII equivalents.
- **Consistent Lowercase**: Converts both filenames and file extensions to lowercase to prevent file path mismatches on case-sensitive hosting servers (e.g. Nginx on Linux).
- **Separator Normalization**: Automatically converts spaces and underscores into hyphens (`-`).
- **Consecutive Hyphen Collapsing**: Replaces multiple hyphens with a single hyphen and strips them from the beginning and end of the filename.
- **Safe Fallback**: Generates a safe name (e.g., `file-1718000000.png`) if the entire filename consists of non-ASCII characters (like Chinese/Japanese/Korean) that get stripped.
- **Optional Date Prefix**: Organize files chronologically with a toggleable `YYYY-MM-DD` prefix under **Settings → File Renamer**.

---

## Ecosystem & Related Projects

This plugin is a standalone companion in the **Omni Webmaster & SEO Suite** ecosystem.

If you are looking for an all-in-one performance and SEO toolkit, consider using:
- **[Omni Webmaster & SEO Suite](https://wordpress.org/plugins/omni-webmaster-seo-suite/)** ([GitHub](https://github.com/ivanusto/omni-webmaster-seo-suite)) — A comprehensive suite that consolidates advanced RSS controls, HTML head cleanup, robots customization, comment disabling, selective thumbnail pruning, upload file renaming (this plugin's feature), upload image resizing, Google Translate URL slug conversion, and Meta Pixel tracking into a single settings panel. Do not run both at once — the suite automatically yields to this standalone plugin to avoid renaming files twice.

Other standalone modules in the ecosystem:
- **[modern-rss-image-feed](https://github.com/ivanusto/modern-rss-image-feed)** — Add modern image formats (WebP, AVIF) support to RSS feeds with proper grouping.

---

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

---

## Installation

1. Download the plugin files.
2. Upload the plugin folder to the `/wp-content/plugins/smart-file-renamer` directory.
3. Activate the plugin through the **Plugins** screen in WordPress.
4. Go to **Settings → File Renamer** to configure options.

---

## License / 授權條款

This project is licensed under the [GNU General Public License v2.0 or later (GPL-2.0-or-later)](LICENSE).