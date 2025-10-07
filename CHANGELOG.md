# Changelog

All notable changes to the FireHawkCRM Tributes Enhancement Suite are documented here.
This project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## v2.1.3 (2025-10-06)

- Adjusted UI styling controls and fixed inconsistent hover and font styles.
- Improved navigation spinner reliability across all tribute grid layouts.
- Updated single tribute layouts to use unified colour variables.
- Corrected documentation and performance figures for asset dequeuing.

---

## v2.1.2 (2025-10-05)

### Performance
- Removed lazy loading from the first nine grid images and added `fetchpriority="high"` to the first image to improve Largest Contentful Paint (LCP).
- Conditionally dequeued ~27 KB of unused FireHawk assets on grid pages.
- Deferred Flatpickr loading until a user focuses the date input field (~45 KB saved on initial load).
- Moved spinner CSS/JS inline to reduce HTTP requests.

### Fixes
- Corrected single-tribute layout CSS loading on grid pages.
- Fixed infinite spinner when navigating back from a single tribute to the grid (browser back cache handling).
- Ensured `ajax_var` localisation remains available for grid functionality.

---

## v2.1.0 (2025-10-03)

### Added
- Version-agnostic API detection.
- Per-shortcode `layout` attribute to allow side-by-side comparisons of layouts during testing.
- SEOPress sitemap integration for automatic inclusion of tribute pages (Yoast support is handled by FireHawk core).
- Integration with FireHawk v2.3.1+ caching via `$activeTribute` when available.

### Improved
- Single tribute templates now reuse cached FireHawk data when possible to reduce API calls.
- Enhanced compatibility across FireHawk versions.
- Added SEOPress settings showing tribute count and sitemap links in the admin area.

---

## v2.0.2 (2025-09-28)

### Improved
- Updated modern card layout with edge-to-edge images and improved spacing.
- Removed “View Details” buttons; entire cards are now clickable.
- Improved typography contrast and font sizing.
- Converted hardcoded date colours to CSS custom properties.
- Fixed style conflicts across layout modules.
- Refined spacing and responsiveness for all layouts.

---

## v2.0.1 (2025-09-18)

### Added
- Full-card click support across all templates with keyboard focus handling.
- Extended API logging and debug toggles for troubleshooting.

### Fixed
- Correctly classified `/api/client/file-number/{number}` routes for caching.
- Improved single tribute slug handling and query detection.
- Fixed layout re-fetching when file-number and slug mismatch occurred.

### Improved
- Replaced and aligned SVG icons for consistency.
- Refined admin dashboard design and readability.

---

## v2.0.0 (2025-06-06) – Complete Rewrite

This release was a full architectural rebuild focused on performance, layout flexibility, and caching.

### Added
- Conditional asset loading to prevent FireHawk scripts and styles from loading on non-tribute pages.
- New layout system with four grid styles and four card variations.
- Caching layer for FireHawk API requests with Redis support and WordPress transient fallback.
- Modern styling system with dynamic colour and typography controls.
- Improved accessibility (WCAG 2.1 AA compliance).

### Improved
- Modernised PHP codebase using namespaces, strict types, and WordPress coding standards.
- Reorganised admin dashboard with modular controls for each feature set.
- Optimised asset detection and loading across all page contexts.
- Added Redis compatibility and intelligent cache invalidation.
- Enhanced SEOPress and Plausible Analytics integrations.

### Migration Notes
- Existing settings migrate automatically on first activation.
- Clear all caches after upgrading.
- Review any custom CSS or template overrides.

---

## v1.x Summary (2024–2025)

- Introduced GitHub-based automatic updates.
- Added conditional loading for FireHawk scripts and styles.
- Introduced caching and flower delivery removal options.
- Added styling controls with colour picker and reset options.
- Improved loading animation and spinner detection.
- Combined legacy modules into a unified plugin architecture.

---

**Built with care in Aotearoa New Zealand by Human Kind Funeral Websites and Weave Digital Studio.**
