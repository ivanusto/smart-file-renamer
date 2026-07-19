# Smart File Renamer (智能檔案重新命名)

[English Version README](README.md)

上傳檔案時自動進行名稱清理與標準化，轉換成乾淨且符合 SEO 的檔名。支援將拉丁變音符號轉換為對應字母、將分隔符號統一為連字號（hyphen）、清除所有非 ASCII 字元，並將檔名與副檔名轉換為小寫，以確保在各類作業系統伺服器上的跨平台相容性。

![Version](https://img.shields.io/badge/version-1.1.0-blue) ![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-21759b) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4) ![License](https://img.shields.io/badge/license-Apache--2.0-green)

---

## 主要功能

- **拉丁變音符號轉譯**：整合 WordPress 內建的 `remove_accents()`，將 200 多個拉丁變音符號（例如 `á` 轉 `a`、`ü` 轉 `u`）轉譯為對應的 ASCII 字母。
- **全小寫標準化**：將檔名與副檔名（例如將大寫 `.PNG` 轉為小寫 `.png`）皆標準化為小寫，避免 Linux (Nginx/Apache) 等區分大小寫的伺服器環境發生路徑對應錯誤。
- **分隔符號統一化**：將空格與底線（`_`）替換成連字號（`-`），利於 SEO 優化。
- **連續連字號收縮**：自動合併連續的連字號（例如 `--` 轉 `-`），並修剪檔名首尾的連字號。
- **安全備用機制**：若檔名經清理後變成空值（例如全中文/韓文/日文檔名被移除後），自動以目前 Unix 時間戳記作為備用名稱（例如 `file-1718000000.png`）。
- **可選日期前綴**：可於後台 **設定 → File Renamer** 啟用 `YYYY-MM-DD-` 前綴，以時間先後順序整理上傳的媒體檔案。

---

## 整合與相關專案

本外掛是 **Omni Webmaster & SEO Suite** 生態系統中的獨立組件。

如果您需要更完整的網站效能與 SEO 站長工具，推薦使用：
- **[Omni Webmaster & SEO Suite](https://github.com/ivanusto/omni-webmaster-seo-suite)** — 整合了進階 RSS 控制、HTML head 清理、Robots Meta 客製化、全面停用留言、選擇性縮圖停用與清理、Google 翻譯中文標題轉英文網址以及 Meta Pixel 追蹤的完整整合外掛。

生態系統中的其他獨立外掛：
- **[modern-rss-image-feed](https://github.com/ivanusto/modern-rss-image-feed)** — 為 RSS 訂閱源提供現代圖片格式（WebP、AVIF）支援，並使用 `media:group` 正確分組。

---

## 系統需求

- WordPress 5.0 或更高版本
- PHP 7.4 或更高版本

---

## 安裝步驟

1. 下載外掛檔案。
2. 將外掛資料夾上傳至 `/wp-content/plugins/smart-file-renamer` 目錄。
3. 在 WordPress 後台的 **外掛** 頁面啟用本外掛。
4. 前往 **設定 → File Renamer** 進行細部設定。

---

## 授權條款

本專案採用 Apache License 2.0 授權。詳細資訊請參閱 [LICENSE](LICENSE) 檔案或程式碼頂部說明。
