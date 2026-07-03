![HumanKind Funeral Notices](https://weave-hk-github.b-cdn.net/humankind/plugin-header.png)

# FireHawkCRM Tributes Enhancement Suite

Performance, layout, and styling enhancements for the **FireHawkCRM Tributes** plugin. It loads FireHawk's assets only on pages that need them, caches the FireHawk API, adds extra tribute layouts, and gives non-technical staff colour and typography controls in the WordPress admin.

Built for our own funeral sites built by HumanKind and Weave Digital Studio, and shared for other developers and funeral homes running FireHawkCRM and WordPress.

> Requires the **FireHawkCRM Tributes** plugin to be installed and active. The suite detects it automatically and never modifies it.

---

## What it does

- **Conditional asset loading.** FireHawk enqueues around 431&nbsp;KB of scripts and styles on every page of the site. The suite detects which pages actually show tributes and dequeues those assets everywhere else, so your homepage and standard pages stay lean.
- **API caching.** Caches FireHawk API responses in Redis where available, falling back to WordPress transients. The first visit to a tribute fetches from FireHawk; later visits serve from cache.
- **Design Layouts.** Four new grid layouts (`modern-grid`, `elegant-grid`, `gallery-grid`, `minimal`) plus the `enhanced-classic` single-tribute layout, versus FireHawk's one of each.
- **Styling controls.** Colour pickers, border, shadow, radius, and typography controls in the admin, scoped to tribute pages only. No theme CSS required.
- **SEO and analytics.** Tribute XML sitemaps registered with SEOPress, Yoast, RankMath, or WordPress native; instant indexing via the Google Indexing API and IndexNow; optional Plausible analytics. Each activates only when its plugin is present.

### Measured performance

Tested on GeneratePress with NGINX and Redis. Results vary by theme and host.

| Metric | Without suite | With suite | Change |
|--------|---------------|------------|--------|
| Total requests | 57 | 15 | ~74% fewer |
| Page size | 519&nbsp;KB | 88&nbsp;KB | ~83% smaller |
| Page load time | 537&nbsp;ms | 144&nbsp;ms | ~73% faster |
| Largest Contentful Paint | 536&nbsp;ms | 135&nbsp;ms | ~75% faster |

On the single tribute page, where the gallery, livestream, and messages still need FireHawk's scripts, the suite trims roughly 330&nbsp;KB of libraries those features never use (an add-to-calendar widget, a date-range picker, a second carousel library, and a text-fitting helper). Everything the page actually uses is kept.

---

## Requirements

- WordPress 6.6 or newer
- PHP 8.1 or newer
- FireHawkCRM Tributes plugin, installed and active

---

## Installation

1. Download the latest release from [GitHub Releases](https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/releases/latest).
2. In WordPress, go to **Plugins → Add New → Upload Plugin** and upload the ZIP.
3. Activate the plugin.
4. Open **FH Enhancement Suite** in the admin to set the layout mode and styling.

Settings from an earlier v2.x install migrate automatically on first load.

---

## Shortcodes

The suite automatically overrides FireHawk's two tribute shortcodes and adds a `layout` option. All standard FireHawk attributes still work and are passed through.

### `[show_crm_tributes_grid]`

| Attribute | Default | Description |
|-----------|---------|-------------|
| `layout` | global setting (`modern-grid`) | Grid layout: `modern-grid`, `elegant-grid`, `gallery-grid`, `minimal`, or `firehawk` for the original. |
| `limit` | `6` | Number of tributes shown. |
| `search` | off | Set to show the search form fixed open above the grid. |

Standard FireHawk grid attributes such as `size`, `sort-by-service`, `display-service`, and `range-months` are passed straight through.

### `[show_crm_tribute]`

| Attribute | Default | Description |
|-----------|---------|-------------|
| `id` | from URL | Tribute ID. Falls back to the `id` query parameter. |
| `layout` | global setting (`enhanced-classic`) | Single layout: `enhanced-classic`, or `default` for the original FireHawk layout. |

```php
[show_crm_tributes_grid layout="elegant-grid" limit="9"]
[show_crm_tribute id="123"]
```

### Custom CSS

The Styling tab has a **Custom CSS (Advanced)** box. CSS entered there is output in a scoped `<style>` on tribute pages only, after the layout styles so it can override them, and is included in the settings export. Target the layout classes, for example `.fcrm-tribute-card` or `.fcrm-enhanced-classic`.

---

## Hosting

Works on any WordPress host. Optimised for Redis-enabled NGINX hosting, and falls back to WordPress transients when Redis isn't available.

Exclude tribute grid and single pages from server-level page caching so updated content isn't served stale. Most hosting panels make this straightforward.

---

## For developers

Detect tribute pages in your own code:

```php
if ( \FcrmEnhancementSuite\TributeDetection\is_tribute_page() ) {
    // Custom logic for tribute pages.
}
```

Settings live in a single `fcrm_enhancement_suite_settings` option, exposed over the REST API and edited through a React admin built with `@wordpress/components`. Caching operates at the HTTP request level, intercepting FireHawk API calls and serving cached responses when available.

---

## Support

Report issues or request features through [GitHub Issues](https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/issues). For general support, email [support@weave.co.nz](mailto:support@weave.co.nz).

When reporting an issue, include your WordPress and PHP versions, hosting environment, and the debug information from the Status tab.

---

## Credits

Built in Aotearoa New Zealand by [Human Kind Funeral Websites](https://humankindwebsites.com) and [Weave Digital Studio](https://weave.co.nz).

With thanks to [FireHawk Funerals](https://firehawkfunerals.com) for the base Tributes plugin and CRM platform.

---

## Licence

GPL-2.0-or-later, the same open-source licence used by WordPress. See the LICENSE file for details.
