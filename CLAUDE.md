# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

A WordPress plugin that enhances the proprietary **FireHawkCRM Tributes** plugin used by funeral home websites in NZ/AU. It requires FireHawk Tributes to be installed and active — it extends and optimises it, never modifies it directly.

Three core problems solved: **performance** (conditional asset loading reduces 431KB→88KB on non-tribute pages), **layouts** (4 grid + 2 single tribute layouts vs FireHawk's 1 each), and **customisation** (colour pickers, typography controls for non-technical funeral home staff).

Emotionally sensitive context — tribute pages must never fail during high-traffic periods around funeral services. Mobile-heavy usage.

## Local Development Environment

- **Local site:** WordPress Studio at `https://firehawk.wp.local/`
- **Debug log:** `../../debug.log` (i.e., `wp-content/debug.log`)
- **FireHawk Tributes plugin** is installed and configured on the local site for testing
- **Screenshots:** `screenshots/` folder — used for sharing visual feedback

## Build & Development

No build pipeline. Pure WordPress plugin — PHP, CSS, JS served directly. No npm, composer, webpack, or transpilation.

- **Deploy:** GitHub releases with built-in auto-update checker (`class-update-checker.php`)
- **CI:** `.github/workflows/release.yml`
- **No automated tests.** All testing is manual on live funeral home sites.
- **Version:** Update `FCRM_ENHANCEMENT_SUITE_VERSION` constant in main plugin file header

## Architecture

### Module System

All feature modules implement `FCRM_Module` interface → extend `Enhancement_Base` abstract class → are independently enabled/disabled via `fcrm_module_{name}_enabled` options. Settings are preserved when modules are disabled.

**Orchestrator:** `fcrm-tributes-enhancement-suite.php` — main class `FCRM_Enhancement_Suite` (bootstraps modules, registers admin menu, handles conditional asset loading)

**Modules** (in `includes/`):
| Module | Class | Purpose |
|--------|-------|---------|
| Optimisation | `FCRM_Optimisation_Module` | Conditional asset loading, flower delivery toggle |
| Layouts | `FCRM_Layouts_Module` | Grid + single tribute layout overrides via shortcodes |
| UI Styling | `FCRM_UI_Styling_Module` | CSS custom property system for design controls |
| Styling | `FCRM_Styling_Module` | Colour customisation for original FireHawk layout |
| SEO/Analytics | `FCRM_SEO_Analytics_Module` | SEOPress sitemap + Plausible Analytics integration |

**Infrastructure** (in `includes/`):
| Class | Purpose |
|-------|---------|
| `FCRM_API_Interceptor` | Caches FireHawk API responses via `pre_http_request` filter |
| `FCRM_Cache_Manager` | Redis-first with WordPress transient fallback |
| `Tribute_URL_Fixer` | Pretty URL parsing edge cases |
| `FCRM_Flower_Delivery_Disabler` | Optionally removes flower shop functionality |
| `PluginUpdateChecker` | GitHub-based auto-updates |

### FireHawk Integration Points

The plugin never modifies FireHawk code. All integration is via WordPress hooks:

| Hook | Priority | Purpose |
|------|----------|---------|
| `init` | 5 | URL fixing (before FireHawk's priority 10) |
| `init` | 20 | Shortcode override (after FireHawk registers at 10) |
| `pre_http_request` | 10 | API response caching intercept |
| `wp_enqueue_scripts` | 999 | Three-tier asset dequeuing (after FireHawk enqueues at 10) |

**Three-tier asset loading:**
1. Non-tribute pages → dequeue ALL FireHawk assets (~431KB saved)
2. Grid pages with enhanced layouts → dequeue redundant assets, keep essentials
3. Single tribute pages → keep ALL assets (gallery/carousel needs Slick, lightGallery)

**Page detection:** `FCRM_Enhancement_Suite::is_tribute_page()` is the single source of truth — checks query params, designated search page, and 6 tribute shortcodes. Result cached per request in a static property.

**FireHawk dependency detection:** Via class existence (`Fcrm_Tributes_Api`, `Single_Tribute`), never hardcoded paths. The FireHawk plugin folder is version-named (e.g., `fcrm-tributes-2.2.0/`).

### Templates

- **Grid layouts:** `templates/layouts/{modern-grid,elegant-grid,gallery-grid,minimal}.php`
- **Single tribute layouts:** `templates/layouts/single/{enhanced-classic,modern-hero}.php`
- **Shared:** `templates/layouts/partials/unified-search.php`

### Dynamic Styling

Options stored as `fcrm_ui_{category}_{property}`. CSS generated inline as `<style>:root { --fcrm-ui-primary: #value; }</style>`, scoped to tribute pages only.

## Code Conventions

- WordPress Coding Standards — tabs for indentation
- File naming: `class-{name}.php`, class names: `FCRM_Module_Name`
- All functions/classes/variables prefixed with `fcrm_` or `FCRM_`
- Text domain: `'fcrm-enhancement-suite'`
- Always sanitize input (`sanitize_text_field`, `wp_verify_nonce`), always escape output (`esc_html`, `esc_url`, `esc_attr`)
- All AJAX handlers must check nonces and `manage_options` capability
- CSS scoped via wrapper classes: `.fcrm-modern-grid`, `.fcrm-elegant-grid`, etc. to avoid conflicts with FireHawk

## Key Constraints

- Must work across FireHawk versions (2.0.1.12, 2.2.0, 2.3.1+) without hardcoded paths
- Page builders must not break (Elementor, Beaver Builder, Divi, Oxygen, Bricks)
- Redis is optional — always fall back to transients
- Single tribute pages need gallery/carousel JS — cannot dequeue Slick/lightGallery there
- First image LCP critical: use `fetchpriority="high"`, no lazy load on first images
- API interceptor pattern matching: tribute messages endpoint must match *before* the broader single client pattern (v2.2.5 bug fix)
- Cache keys format: `fcrm_api_cache_{md5($url . $params)}`

## FireHawk API Reference

API client: `Fcrm_Tributes_Api` (static methods). Auth token in `fcrm_tributes_auth_token` option, sent as `x-access-token` header.

Key methods: `get_client()`, `get_clients()`, `get_tribute_messages()`, `get_tribute_trees()`, `get_tribute_donations()`, `get_tributes_sitemap()`

Cached endpoints: `/api/client/tributes` (list), `/api/client/tributes/{id}` (single), `/api/client/file-number/{number}`, `/api/client/tributes/{id}/messages`

See `CONTEXT.md` for full FireHawk API response structures, endpoint details, version differences, and known quirks.

## Known Technical Debt

- `templates/layouts/single/enhanced-classic.php` is 60KB — needs refactoring/splitting
- No automated tests
- `class-fcrm-external-module-registry.php` is a stub placeholder
