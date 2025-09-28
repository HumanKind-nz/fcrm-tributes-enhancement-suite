# 📝 Changelog

All notable changes to the FirehawkCRM Tributes Enhancement Suite will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v2.0.2 (2025-09-28)

### 🎨 Improved
- **Modern Card Layout**: Enhanced visual design with edge-to-edge images and deeper shadows inspired by HumanKind design
- **UI Polish**: Removed View Details buttons - entire cards now clickable for cleaner, more intuitive interface
- **Typography**: Increased date text size and improved contrast with customizable secondary colors from UI styling system
- **Color System**: Date colors now use CSS custom properties instead of hardcoded values across all layouts
- **Card Styling**: Fixed UI styling module conflicts - modern cards no longer inherit inappropriate padding from minimal layout styles
- **Visual Hierarchy**: Better spacing, enhanced shadows, and improved responsive design for professional appearance

## v2.0.1 (2025-09-18)

### 🔧 Fixed
- **API Caching**: Correctly classify file-number route `/api/client/file-number/{number}` as client_by_number to ensure distinct cache keys
- **Single Pages**: Sync readable-permalink slug to main WP_Query and prefer `get_query_var('id')` for reliable ID detection
- **Layout Routing**: In enhanced-classic and modern-hero layouts, refetch by file-number when slug's file-number doesn't match loaded client

### ✨ Added
- **Full-Card Click**: Make entire card clickable across all layout templates with keyboard support and proper focus styling
- **Enhanced Logging**: Expanded API interceptor logs with better debugging information
- **Debug Toggle**: Added debug logging toggle in admin interface for easier troubleshooting

### 🎨 Improved
- **UI Icons**: Replaced write-message icon and aligned SVGs for better visual consistency
- **Admin Interface**: Enhanced styling and user experience in plugin dashboard

## v2.0.0 - 2025-06-06 - Complete Rewrite & Major Performance Improvements

### 🚀 Major Features Added

#### FCRM Asset Dequeuing - Major Performance Fix
- **Fixed critical performance issue**: FCRM plugin was loading 28+ CSS/JS files on every page
- **Asset intelligence**: Now only loads tribute assets on actual tribute pages, search pages, and shortcode pages
- **Massive savings**: Prevents 1-2MB+ of unnecessary assets loading on homepages and standard pages
- **Smart detection**: Automatically detects tribute pages using multiple criteria (ID parameter, post type, search pages, shortcodes)
- **Default enabled**: Works immediately after installation for instant performance improvement

#### Modern Layout Templates System
- **4 responsive layout templates**: Modern Grid, Elegant Grid, Gallery Grid, List view
- **Card-based design**: Professional, mobile-first responsive layouts that replace outdated FCRM defaults
- **Advanced search interface**: HTML5 date pickers, real-time filtering, case-insensitive search (work in progress)
- **Configurable grid**: 3 or 4 columns
- **4 card styles**: Standard, Elevated, Outlined, Minimal with consistent spacing
- **WCAG 2.1 AA compliant**: Proper accessibility, keyboard navigation, screen reader support

#### Universal UI Styling System
- **5 professional color schemes**: Just for fun, or pick our own colours to match your website.
- **Live CSS generation**: No more manual file editing - styles are generated dynamically
- **Typography controls**: Separate heading and body font customization
- **Layout-specific options**: Different styling per layout template

#### API Caching System
- **HTTP request interception**: Transparent caching of FCRM API calls
- **Redis support**: Optimal performance with Redis-enabled hosting
- **Intelligent fallback**: WordPress transients when Redis unavailable
- **Configurable TTL**: Separate cache durations for different content types
- **Cache statistics**: Real-time monitoring of cache performance
- **Smart invalidation**: Automatic cache clearing when content updates

#### SEO & Analytics Integration
- **Plausible Analytics**: Privacy-focused, GDPR-compliant analytics integration. We recommend Plausible. This adds tracking for each tribute.
- **SEOPress enhancement**: Ads meta tags and social sharing optimisation if you use SEOPress (we do).
- **Social media optimisation**: Custom share images, Open Graph tags

### 🎛️ Architecture Improvements

#### Modern Plugin Architecture
- **Modular system**: Independent modules now that can be enabled/disabled
- **Enhanced security**: WordPress coding standards compliance, proper sanitisation
- **Modern PHP practices**: Type declarations, namespace usage, error handling
- **Mobile-first admin**: Completely redesigned admin interface
- **Dashboard overview**: Centralized module management with status indicators

#### Performance Optimisations
- **Conditional asset loading**: Context-aware script/style loading
- **Optimized tribute detection**: Efficient page identification logic
- **Script optimization**: Reduced JavaScript footprint
- **CSS optimization**: Minified stylesheets, smart loading
- **Redis compatibility**: Full object cache integration

### 🔧 Developer Features

#### Extensible Framework
- **Module interface**: Standardised module development
- **External module support**: Third-party module registration system
- **Hook system**: Standardized tribute page detection
- **Settings API**: Consistent settings management across modules
- **Debug support**: Comprehensive logging and debugging tools

#### Enhanced Compatibility
- **WordPress 5.0+**: Full support for modern WordPress
- **PHP 7.4+**: Modern PHP version requirements
- **Multisite support**: Full WordPress multisite compatibility if you dare to use Multisite.
- **Hosting agnostic**: Works on any WordPress hosting environment
- **Redis optimized**: Enhanced performance on Redis-enabled hosting

### ⚙️ Configuration Improvements

#### User Experience
- **Dashboard-first design**: Centralised module management
- **Instant feedback**: Real-time status indicators and notifications
- **Smart defaults**: Optimal settings work out of the box
- **Progressive enhancement**: Features activate as needed
- **Conflict detection**: Automatic detection of conflicting plugins

#### Settings Migration
- **Automatic migration**: v1.x settings automatically upgraded
- **Backward compatibility**: Existing customizations preserved where possible
- **Clean uninstall**: Proper cleanup of settings and cache data

### 🛡️ Security & Standards

#### WordPress Standards Compliance
- **Coding standards**: Full WPCS compliance
- **Security hardening**: Proper input sanitization, nonce verification
- **Escape output**: All user data properly escaped
- **Capability checks**: Proper permission validation
- **SQL injection prevention**: Prepared statements and safe queries

#### Error Handling
- **Graceful degradation**: Plugin continues working if features fail
- **Comprehensive logging**: Detailed error tracking for debugging
- **User-friendly errors**: Clear error messages for administrators
- **Recovery mechanisms**: Automatic recovery from common issues

### ⚠️ Breaking Changes (sorry)

#### Code Changes
- **API changes**: Most v1.x filters and actions removed/changed
- **File structure**: Complete reorganization of plugin files
- **Class names**: Updated class naming for consistency
- **Hook names**: Standardized hook naming conventions

#### Settings Changes
- **Settings format**: New settings structure (automatically migrated)
- **Option names**: Some option names changed for consistency from v1.x
- **Module system**: Settings now organized by module

#### Template Changes
- **Layout templates**: Complete rewrite of layout system from v1.x
- **CSS classes**: Updated CSS class names for consistency
- **HTML structure**: Modern semantic HTML structure

### 📋 Migration Notes

#### Upgrading from v1.x
1. **Backup recommended**: Full site backup before upgrading
2. **Settings migration**: Automatic on first activation
3. **Custom CSS**: May need updating due to new class names
4. **Custom hooks**: Check custom code against new hook system
5. **Testing required**: Test all customizations in staging environment

#### Post-Upgrade Steps
1. **Review module settings**: Verify all modules configured correctly
2. **Test performance**: Confirm asset dequeuing working properly
3. **Check layouts**: Verify layout templates displaying correctly
4. **Clear caches**: Clear any site caching after upgrade

### 📊 Performance Improvements

#### Before v2.0.0
- 3-5 second page loads on tribute pages
- FCRM assets loaded on every page (1-2MB+)
- Limited caching, high server load
- Outdated layouts, poor mobile experience

#### After v2.0.0
- <500ms page loads with caching
- Assets only loaded where needed (major bandwidth savings)
- Intelligent caching reduces server load by 80%+
- Modern responsive layouts, excellent mobile experience

### 🔄 Future Compatibility

#### Planned Features
- Enhanced module interface system
- Advanced layout builder
- Performance monitoring dashboard
- Custom CSS editor with syntax highlighting

#### Deprecation Notices
- Legacy filter system will be fully removed in v3.0.0 probably.
- Old CSS class names deprecated (still working but will be removed)
- Manual cache clearing methods deprecated in favor of automated system

## v1.3.0 - Performance Optimisations
- Completely rebuilt flower delivery disabling functionality for better performance and reliability. Our clients don't use the flower delivery.
- Implemented new system to properly remove flower delivery features from all pages when disabled
- Optimised style loading to only enqueue CSS on tribute pages and pages containing tribute shortcodes
- Improved performance by preventing unnecessary style loading across non-tribute pages
- Optimised code to prevent unnecessary script loading and improve site performance
- Styling change for streaming and social share button colour defaults
- Minor bug fixes and tweaks to auto plugin updates

## v1.1.2
- Minor tweak to auto update code

## v1.1.1 - Auto-Update Feature Release

### What's New
- Added GitHub-based automatic updates
- Plugin updates can now be managed directly from the WordPress dashboard
- Update notifications will show release notes and version details

### Technical Details
- Implemented GitHub releases integration for version control
- Added automatic version checking against GitHub repository
- Integrated with WordPress native update system
- Added error logging for update process debugging

### Notes
- No settings changes required
- Updates will appear in your regular WordPress updates dashboard
- Requires no additional configuration


## [1.1.0] - 2024-11-23
### Added
- Initial release combining three separate plugins
- Performance and loading optimisation features
  - Conditional script/style loading
  - Flower delivery functionality (disabled by default)
  - DNS prefetch handling
  - Script cleanup
- Custom styling features
  - Colour picker with opacity support
  - Comprehensive style controls
  - Style reset functionality
- Loading animation features
  - Automatic grid detection
  - Customisable spinner
  - Smart page detection

### Changed
- Unified admin interface
- Improved page detection logic
- Enhanced asset handling

### Removed
- Individual plugin dependencies
- Legacy page ID requirements
- Unused asset files
