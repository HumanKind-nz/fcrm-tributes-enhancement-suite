![HumanKind Funeral Notices](https://weave-hk-github.b-cdn.net/humankind/plugin-header.png)

# FireHawkCRM Tributes Enhancement Suite

A WordPress plugin that makes FireHawk Tributes load faster, work better with search engines, and provide a smoother experience for visitors remembering loved ones.

Originally built for our own funeral websites at HumanKind and Weave Digital Studio, we’ve made it available for other developers and funeral homes using FireHawk.

---

## Why It Exists

The FireHawk Tributes plugin adds about 431 kilobytes of files and makes 42 additional requests to every page of your website, whether you’re running GeneratePress, Astra, Divi, or any custom WordPress theme. This happens on your homepage, about page, contact page, and every other page, even when those pages don’t display any tributes.

In short, it loads the full toolbox everywhere — dozens of scripts and styles for galleries, date pickers, form validation, and social sharing — even when those tools aren’t needed.

The Enhancement Suite fixes this by intelligently detecting which pages actually use tribute functionality and only loading the necessary files. It also adds caching for the FireHawk API, which dramatically reduces load times on single tribute pages.

Another challenge with the default FireHawk Tributes plugin is that it offers no easy way to adjust button colours, fonts, or layouts without writing custom CSS. For non-developers, even small changes—like matching a funeral home’s brand colours require code edits or developer help. The Enhancement Suite adds some visual styling controls so buttons, text and tribute pages can be personalised without touching any code.

---

## What It Does

### Smarter Performance

The suite prevents unnecessary files from loading across your entire website. Instead of every page carrying the burden of tribute-related scripts and styles, only the pages that display tributes receive these resources. Tribute pages, tribute search pages, and pages using the tribute shortcodes load exactly what they need, while your homepage and standard pages stay lean and fast.

The caching system stores tribute data from the FireHawk API using either Redis (on compatible hosting) or WordPress transients. When someone visits a tribute page for the first time, the suite fetches data from FireHawk’s servers - typically taking three to five seconds. When the next visitor views that same tribute, the page loads from cache in under half a second. This makes a big difference for families accessing tributes during high-traffic periods after a funeral service.

---

### Multiple Layouts and Visual Control

The standard FireHawk Tributes plugin provides a single grid layout. If you want to change button colours, text styling, or spacing, you normally need to write custom CSS and add it to your theme. For many funeral homes, that means calling a developer for even small visual changes.

The Enhancement Suite adds three additional professional grid layouts, giving you four design options without touching code. It also includes a full styling interface with colour pickers and typography controls, so you can adjust colours, text, card backgrounds, shadows, and spacing directly in the WordPress admin. These changes apply instantly and work across all modern browsers and devices.

This allows you to show clients different layout options, adjust colours for special occasions or seasonal themes, and maintain consistent branding across tribute pages — without developer intervention.

---

### Improved Loading Experience

The suite adds a subtle loading animation that appears while tribute data is being fetched from the FireHawk system. This prevents the blank-screen effect that can make visitors think something has broken. The loading indicator reassures users that the page is working and content is on the way - a small detail that makes the experience more professional and comforting, especially during emotionally sensitive moments.

---

### Performance Results

On a typical funeral home website, the Enhancement Suite reduces the amount of data visitors download by over eighty percent, making pages load up to four times faster.  
The table below shows results from testing on a standard WordPress site using GeneratePress with NGINX and Redis hosting. Your actual results will vary depending on theme and hosting, but every site benefits from loading only what’s needed.

| Metric | Without Suite | With Suite | Improvement |
|--------|---------------|------------|-------------|
| Total Requests | 57 | 15 | ~74% fewer |
| Page Size | 519 KB | 88 KB | ~83% smaller |
| Page Load Time | 537 ms | 144 ms | ~73% faster |
| Largest Contentful Paint | 536 ms | 135 ms | ~75% faster |

These results translate to real-world gains: faster access to tribute pages, better search visibility, and lower hosting resource usage.

---

### SEO and Analytics Integration

The suite helps search engines index tribute pages correctly and ensures they appear properly when shared on social media. 
The FireHawk Tributes plugin already includes built-in support for Yoast SEOs. Because we use SEOPress across our projects, the Enhancement Suite adds full SEOPress integration, ensuring tribute pages appear in XML sitemaps and include correct metadata for search and social sharing.

For analytics, it integrates with Plausible Analytics to provide lightweight, privacy-friendly tracking that’s cookie-free and GDPR compliant. These integrations activate only when the corresponding plugins are installed, so you never load unnecessary features. Plausible account required.

---

### Recent Updates

Recent updates improve compatibility with newer FireHawk versions and add dual-layout demos, sitemap support, and smarter caching. The suite now adapts automatically across FireHawk releases, reducing the need for manual configuration.

---

## Installation

1. Download the latest release from [GitHub Releases](https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/releases/latest).  
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, and upload the ZIP file.  
3. Activate the plugin.  
4. Open **FH Enhancement Suite** in your WordPress admin to enable or disable modules.

**Note:** The FireHawkCRM Tributes plugin must be installed and active first. The Enhancement Suite will automatically detect and integrate with it.

---

## Configuration

The suite includes separate modules for performance optimisation, caching, layouts, SEO, and analytics. Each module can be toggled on or off from the dashboard, allowing you to use only what you need.

The cache time-to-live defaults to fifteen minutes, balancing freshness with performance. You can adjust this setting or clear the cache manually at any time.

Layouts and styles can be chosen directly through shortcodes, making it easy to test designs or show clients options:

```php
[show_crm_tributes_grid layout="modern-grid" limit="6"]
[show_crm_tribute id="123" layout="modern-hero"]
```

Available grid layouts: `modern-grid`, `elegant-grid`, `gallery-grid`, and `minimal`.  
Available single tribute layouts: `modern-hero` and `enhanced-classic`.  
To keep the original FireHawk look, use `layout="firehawk"` or `layout="default"`.

---

## Hosting Compatibility

The Enhancement Suite works on any WordPress hosting environment. It’s optimised for Redis-enabled NGINX setups such as GridPane, RunCloud, Spinup, CloudAvatar and similar platforms.  
If Redis isn’t available, it automatically falls back to WordPress transients for caching.

For best results, tribute pages with the grids and single tributes, should be excluded from server-level page caching to prevent outdated content being served. Most hosting panels make this simple through their caching settings.

---

## For Developers

Developers can extend the suite easily thanks to its modular architecture. Each module (performance, layouts, styling, SEO, analytics) runs independently, so you can enable or disable features without affecting others.

The suite works seamlessly with Redis object caching and NGINX. It requires WordPress 5.0+ and PHP 7.4+.  
Caching operates at the HTTP request level, intercepting FireHawk API calls and serving cached responses when available.

You can detect tribute pages in your own code using:

```php
if ( FCRM_Enhancement_Suite::is_tribute_page() ) {
    // Custom logic for tribute pages
}
```

Cache operations can be automated or run via WP-CLI for deployment workflows. The asset management system automatically detects tribute pages through URL parameters, search pages, and shortcode use.

---

## Support

Developers can report issues or request features through [GitHub Issues](https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/issues).  
For general support, email [support@weave.co.nz](mailto:support@weave.co.nz).

When reporting issues, please include:
- WordPress version  
- PHP version  
- Hosting environment  
- Active modules  

The Enhancement Suite dashboard provides helpful debug information for troubleshooting.

---

## Credits

Built with care in Aotearoa New Zealand by  
[Human Kind Funeral Websites](https://humankindwebsites.com) and [Weave Digital Studio](https://weave.co.nz).  

With thanks to [FireHawk Funerals](https://firehawkfunerals.com) for the base Tributes plugin and CRM platform.

---

## License

GPL-3.0 or later — the same open-source license used by WordPress.  
See the LICENSE file for details.
