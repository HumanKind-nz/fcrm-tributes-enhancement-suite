# 🔥 FireHawk Tributes Enhancement Suite 

**The ultimate enhancement suite for FireHawkCRM Tributes & WordPress** - performance optimisations, modern layouts, comprehensive caching, and integrated alternative SEO & analytics. Built by devs, for devs who want their funeral websites to perform well.

> Originally built for internal use at Human Kind Funeral Websites / Weave Digital Studio, but we figured other devs might find it useful as well.

![Plugin Header](assets/plugin-header.png)

---

## 🚀 What's New in v2.0+?

As we used FireHawk more we've rewritten this whole thing for v2.0. Here's what you get now:

### 🎯 **Enhanced Performance**
- **FCRM Asset Dequeuing**: Prevents FCRM from loading 28+ CSS/JS files (Bootstrap, Moment, Lodash, etc.) on non-tribute pages - **saves 500KB-2MB per page load**
- **Intelligent Page Detection**: Only loads the FH tribute assets on actual tribute pages, search pages, or pages with tribute shortcodes
- Script optimisation and flower delivery code disabling. (We added this as our clients don't use the Flower Delivery features as it was always added) 
- Redis object cache compatibility and smart asset management

### ⚡ **API Caching System**
- **3-5 second page loads → under 500ms** (seriously)
- Redis support with intelligent fallback to WordPress transients
- HTTP request interception for transparent caching
- Works with VPS Hosting panels, fast NGINX page & Object caching or any Redis-enabled hosting.
- Please get in touch if you need other WordPress hosting support. 

### 📋 Modern Layout Templates 
- Card-based responsive layouts that don't look like they're from 2010
- 4 card styles for each layout: Standard, Elevated, Outlined, Minimal
- **Full-card click**: Entire card area is clickable with keyboard accessibility support
- Configurable grid columns (3 or 4)
- Advanced search with HTML5 date pickers and real-time filtering (work in progress)
- Mobile-first design with proper accessibility

### 🎛️ **Universal UI Styling**
- 5 professional colour schemes (or roll your own to match your WordPress Theme)
- Typography controls for headings and body text
- Layout-specific styling options
- Live CSS generation - no more manual file editing

### 📊 **SEO & Analytics Integration**
- **Plausible Analytics** integration (privacy-focused, GDPR compliant). This is what we recommend. This adds the tracking code to each tribute page.
- **SEOPress** enhanced meta tags and social sharing. FH Tributes supports Yoast out of the box. This adds support for SEOPress (again what we recommend)
- Smart plugin detection - only shows options when base plugins are available
- WordPress media uploader for social share images

### 🥝 **Module Based & Extendable**
- We've added a module system so we can extend this plugin easily with extra additions or more layout options. If there is something you've like added, please get in touch.

---

## 🛠️ Installation 

### Download & Install (Recommended)
The easiest way to install the Enhancement Suite:

1. **Download the latest release**: Go to [GitHub Releases](https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/releases/latest)
2. **Download**: Click `fcrm-tributes-enhancement-suite.zip` (the ready-to-install WordPress plugin)
3. **Install**: Upload via WordPress admin: **Plugins → Add New → Upload Plugin**
4. **Activate**: Enable the plugin and configure modules from the dashboard

**✅ Auto-Updates**: The plugin will automatically check for updates and notify you when new versions are available. Updates can be installed directly from your WordPress admin.

**⚠️ Requirements**: This plugin requires the **FireHawkCRM Tributes** plugin to be installed and activated first.

### Manual Installation (Advanced)
```bash
# Download latest release
wget https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/releases/latest/download/fcrm-tributes-enhancement-suite.zip

# Extract and rename folder  
unzip fcrm-tributes-enhancement-suite.zip
mv fcrm-tributes-enhancement-suite-* fcrm-tributes-enhancement-suite

# Zip it back up for WordPress
zip -r fcrm-tributes-enhancement-suite.zip fcrm-tributes-enhancement-suite/
```

Then upload via WordPress admin: **Plugins → Add New → Upload**

---

## ⚙️ Configuration

Each module can be enabled/disabled independently from the main dashboard:

**Admin Menu**: `FH Enhancement Suite` (positioned below SEOPress)

### 🚀 Performance Optimisations
- **FCRM Asset Dequeuing**: Major performance fix that prevents FCRM from loading unnecessary files on non-tribute pages
  - Automatically dequeues 28+ CSS/JS files (Bootstrap, Moment.js, Lodash, etc.) 
  - Only loads assets where actually needed (tribute pages, search pages, shortcode pages)
  - Saves 500KB-2MB+ per page load on homepages and other standard pages
  - Enabled by default for immediate performance improvement
- **API response caching** (Redis/transient with configurable TTL)
- **Debug logging toggle** for troubleshooting cache hits/misses and tribute detection
- Script loading optimisation and flower delivery functionality removal
- Bootstrap.js conflict resolution

### 📱 Modern Layout Templates
- Override default FCRM grid layouts
- Card-based responsive design
- Configurable columns and spacing
- Advanced search interface

### 🎨 Universal UI Styling  
- 5 built-in colour schemes for fun or match your theme.
- Custom typography controls
- Layout-specific options
- Live CSS generation

### 📊 SEO & Analytics
- Plausible Analytics integration
- SEOPress enhanced meta tags
- Social media optimisation
- Privacy-focused tracking

### 🎭 Style Overrides (Legacy) 
**This is for styling the default FH Tribute grid, if you don't want to use the new layouts**
- Individual colour customisation
- Button and link styling
- Focus states and shadows
- For when you need granular control

---

## 🧑‍💻 For the Developers

### Architecture Overview

```
├── includes/
│   ├── interface-fcrm-module.php          # Module interface contract
│   ├── class-fcrm-enhancement-base.php    # Base functionality
│   ├── class-optimisation-module.php      # Performance optimisations
│   ├── class-layouts-module.php           # Modern layout templates  
│   ├── class-ui-styling-module.php        # Universal styling system
│   ├── class-seo-analytics-module.php     # SEO & analytics integration
│   ├── class-styling-module.php           # Legacy style overrides
│   ├── class-fcrm-cache-manager.php       # Cache management
│   └── class-fcrm-api-interceptor.php     # HTTP request interception
├── assets/
│   ├── css/
│   │   ├── admin/                         # Admin interface styles
│   │   └── layouts/                       # Layout-specific CSS
│   └── js/
│       ├── admin/                         # Admin functionality
│       └── frontend/                      # Frontend enhancements
└── templates/
    └── layouts/                           # Layout template files
```

### Module System

**Note**: The v2.0.0 module system is currently being implemented. Current modules use a simpler approach:

```php
class FCRM_My_Module {
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    public function register_settings(): void {
        // Register module settings
    }
    
    public function render_settings(): void {
        // Admin settings interface
    }
    
    public function enqueue_frontend_assets(): void {
        // Frontend asset loading
    }
    
    public function enqueue_admin_assets($hook_suffix): void {
        // Admin asset loading
    }
}
```

### Extending the Suite (Current Implementation)

Want to add your own module? Here's the current approach:

Register your module:
```php
// In your plugin or theme
add_action('init', function() {
    $external_modules = get_option('fcrm_external_modules', []);
    $external_modules['my_custom'] = [
        'name' => 'My Custom Module',
        'description' => 'Does awesome stuff',
        'version' => '1.0.0',
        'file' => __FILE__,
        'class' => 'My_Custom_Module',
        'enabled' => false
    ];
    update_option('fcrm_external_modules', $external_modules);
});
```

### Caching System

The caching system works at the HTTP level, intercepting requests to the FCRM API:

```php
// Cache configuration
$cache_config = [
    'ttl' => 15 * MINUTE_IN_SECONDS,  // 15 minutes
    'redis_enabled' => class_exists('Redis'),
    'fallback' => 'transients'
];

// Manual cache operations
FCRM\EnhancementSuite\Cache_Manager::clear_all_cache();
$stats = FCRM\EnhancementSuite\Cache_Manager::get_cache_stats();
```

### Available Hooks (v2.0.0)

**Current Working Hooks:**

```php
// Tribute page detection (used by all modules)
if (FCRM_Enhancement_Suite::is_tribute_page()) {
    // Your tribute-specific code
    // This is the primary way to detect if current page should load tribute assets
}
```

**Note**: Many v1.x filters have been removed during the v2.0.0 rewrite. The following filters from the README are **not currently implemented**:
- ~~`fcrm_layouts_template_vars`~~ - Removed during rewrite
- ~~`fcrm_cache_ttl`~~ - Removed during rewrite

**Settings Access:**
```php
// Access module settings directly
$cache_enabled = get_option('fcrm_cache_enabled', 1);
$conditional_loading = get_option('fcrm_conditional_asset_loading', 1);
$layout_style = get_option('fcrm_layout_card_style', 'standard');
```

### Performance Notes

**Before Enhancement Suite**: 3-5 second page loads  
**After Enhancement Suite**: < 500ms page loads  

The caching system intercepts API calls at the HTTP level, so even complex tribute grids load instantly after the first request.

**FCRM Asset Dequeuing**: The suite intelligently detects tribute pages and only loads FCRM assets where needed:
- ✅ Loads on tribute single pages (with `id` parameter)
- ✅ Loads on designated tribute search pages 
- ✅ Loads on pages containing tribute shortcodes
- ❌ Blocks loading on standard WordPress pages, homepage, etc.
- Prevents 1-2MB+ of unnecessary CSS/JS from loading on non-tribute pages

---

## 🤝 Compatibility

**WordPress**: 5.0+  
**PHP**: 7.4+  
**Required**: FireHawkCRM Tributes plugin  

**Hosting Compatibility**:
- ✅ VPS Hosting / NGINX stack (Redis optimised)
- ⚠️ WP Engine (untested - may work with their object cache) Happy to test.
- ✅ Standard WordPress NGINX hosting
- ✅ Multisite networks (please don't use Multisite 😉)
- ✅ Any Redis-enabled hosting

**Plugin Compatibility**:
- 🔌 Plausible Analytics (enhanced integration)
- 🔌 SEOPress (enhanced meta tagging for the tributes)
- ⚠️ Our original standalone FCRM Plausible/SEOPress plugins (conflicts - disable them)

---

## 🐛 Support & Issues

**For Developers**: [GitHub Issues](https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/issues)  
**For General Support**: [support@humankindwebsites.com](mailto:support@humankindwebsites.com)

**Please include**:
- WordPress & PHP versions
- Hosting environment
- Active modules
- Debug info from `FH Enhancement Suite → Dashboard`

---

## ⚠️ Important Notes

- **Internal-first development**: Features are driven by our client needs at Human Kind Funeral Websites and Weave Digital Studio
- **Test in staging**: We can't guarantee compatibility with every theme/plugin combo
- **Performance testing**: Done primarily on our own NGINX high performance cloud hosting with Redis
- **No official support**: For external users - community support via GitHub issues

---

## 📝 Changelog

### v2.0.1 (2025-09-18) - Latest Stable Release
**Recent Updates:**
- ⚡ Enhanced API caching with better route classification
- 🎯 Improved single tribute page routing and ID detection
- 🖱️ Full-card click functionality across all layout templates (entire card area clickable)
- 🔧 Debug logging toggle in admin interface for easier troubleshooting
- 🎨 Updated UI icons and improved admin styling

### v2.0.0 (2025-06-06) - Complete Rewrite
**Major Changes:**
- 🚀 Complete architectural rewrite with a new modular system
- ⚡ New API caching system (3-5s → <500ms page loads)
- 🎯 **FCRM Asset Dequeuing**: Fixes major performance issue where FCRM loaded 28+ files on every page (saves 500KB-2MB+ per page load)
- 🎨 Modern layout templates with responsive card designs
- 🎛️ Universal UI styling system
- 📊 Integrated our SEO & Analytics modules (Plausible + SEOPress)
- 🛡️ Enhanced security and WordPress standards compliance
- 📱 admin interface redesign
- 🔧 Extensible framework for custom modules

**Breaking Changes:**
- Most v1.x filters and actions have been replaced, sorry.
- Admin interface completely redesigned
- File structure significantly changed
- Settings format updated (automatic migration included)

**Migration Path:**
- Backup your site before upgrading
- Old settings will be automatically migrated
- Test all customisations in staging first

### v1.3.0 (2025-01-15) - Performance Optimisations
- Improved flower delivery disabling
- Enhanced script loading optimisation
- Better tribute page detection

---

## 👥 Credits

**Built with ❤️ in New Zealand by:**
- [Human Kind Funerals](https://humankindwebsites.com) - Funeral websites by Weave Digital
- [Weave Digital Studio](https://weave.co.nz) - WordPress development in New Zealand

**Special thanks to:**
- [FireHawk Funerals](https://firehawkfunerals.com) for the base Tributes plugin. If you need a CRM for your Funeral Business.
- The WordPress community for excellent documentation
- Coffee ☕ for making this possible

---

## 📄 License

GPL-3.0+ - Because WordPress. See [LICENSE](LICENSE) for details.

---

**Enjoying the Enhancement Suite?** Give us a ⭐ on GitHub and let other devs know!
