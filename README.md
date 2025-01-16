![Plugin Header](assets/plugin-header.png)

# FireHawkCRM Tributes - Enhancement Suite

A enhancement suite for the FireHawkCRM Tributes WordPress plugin that initially combines performance optimisations, custom styling, and loading animations into one unified solution. Originally for internal use by Weave Digital Studio / Human Kind.

This enhancement suite is part of a small family of complimentary plugins we've developed to extend FireHawkCRM Tributes functionality for our use building funeral websites in WordPress.
For a complete tribute management solution and CRM, consider trying FireHawkCRM and our other add-ons:

- [FCRM SEOPress Integration](https://github.com/weavedigitalstudio/fcrm-seopress): 
If you use SEOPress on your WordPress site, this replaces the current bundled Yoast SEO integration of the FireHawkCRM Tributes plugin with added support for SEOPress and SEOPress Pro. Meta titles and tags are then controlled by SEOPress.
- [FCRM Plausible Analytics](https://github.com/weavedigitalstudio/fcrm-plausible-analytics): 
Integration for FireHawkCRM Tributes plugin which adds Plausible Analytics tracking code to the individual funerals/tribute pages. Plausible is a privacy-focused analytics to track tribute engagement while respecting visitor privacy.

---

## ⚠️ Important Notice

This plugin is primarily developed for internal use at Weave Digital Studio & Human Kind and with our  funeral websites we build. While we're making it available publicly, please note:

- Features and updates are driven by our specific needs and client requirements
- Testing is conducted only within our controlled environments
- We cannot guarantee compatibility with all WordPress setups or themes
- No official support is provided for external users
- Use in production environments outside our ecosystem is at your own risk

We encourage you to test thoroughly in a staging environment before any production use.

---

## Features

### Performance Optimisation
- Conditionally loads scripts and styles only on tribute-related pages (Not Side-wide).
- Optionally disables flower delivery functionality site-wide if not used/offered (disabled by default).
- Removes unnecessary DNS prefetch hints.
- Optimised asset handling for better performance.

### Custom Styling
- Customise tribute styling colours through an intuitive admin interface.
- Full colour picker with opacity support for box shadows.
- Style various elements including:
  - Buttons (primary and secondary).
  - Links.
  - Focus states.
  - Grid layouts.
  - Pagination.
- Easy reset to default colours.

### Loading Animation
- Automatic loading spinner for tribute grids loading single tributes.
- Customisable spinner colour.
- Automatically activates on:
  - Tribute search pages.
  - Pages with tribute grid short-codes.
- Improves user experience during page transitions.

---

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- FireHawkCRM Tributes plugin (must be installed and activated)

---

## Installation from GitHub

When installing this plugin from GitHub:

1. Go to the [Releases](https://github.com/your-repo/fcrm-tributes-enhancement-suite/releases) page
2. Download the latest release ZIP file
3. Extract the ZIP file on your computer
4. Rename the extracted folder to remove the version number  
   (e.g., from `fcrm-tributes-enhancement-suite-1.3.0` to `fcrm-tributes-enhancement-suite`)
5. Create a new ZIP file from the renamed folder
6. In your WordPress admin panel, go to Plugins → Add New → Upload Plugin
7. Upload your new ZIP file and activate the plugin
8. Plugin should then auto-update moving forward if there are any changes.

**Note**: The folder renaming step is necessary for WordPress to properly handle plugin updates and functionality.

---

## Configuration

### Performance Settings
- Navigate to FH Tributes Enhancements → Performance.
- Enable/disable performance optimisations.
- Enable/disable flower delivery functionality.
- Enable/disable bootstrap.js library, if a conflict is present.

### Styling Settings
- Navigate to FH Tributes Enhancements → Custom Styles.
- Use the colour pickers to customise various elements.
- Save changes to apply new styles.
- Use the "Reset Colours" button to restore defaults.

### Loading Animation Settings
- Navigate to FH Tributes Enhancements → Loading Animation.
- Enable/disable the loading animation used when tributes load from the grid.
- Customise the spinner colour.

---

## Shortcode Support

The enhancement suite automatically detects pages using these shortcodes:
- `[show_crm_tributes_grid]`
- `[show_crm_tributes_large_grid]`

---

## Developers

### File Structure
```
fcrm-tributes-enhancement-suite/
├── assets/
│   ├── plugin-header.png   	      # Plugin header image
│   ├── css/
│   │   ├── admin/
│   │   │   └── alpha-picker.css      # Alpha color picker styles
│   │   │   └── admin-styles.css      # Styles for the plugin admin
│   │   ├── enhancement.css           # Main styling for tributes
│   │   └── loader.css                # Loading animation styles
│   └── js/
│       ├── admin/
│       │   └── alpha-color-picker.js  # Alpha color picker functionality
│       └── frontend/
│           └── loader.js              # Loading animation functionality
├── includes/
│   ├── class-fcrm-enhancement-base.php   # Base class for all modules
│   ├── class-fcrm-optimisation.php       # Performance optimisation module
│   ├── class-fcrm-styling.php            # Custom styling module
│   └── class-fcrm-loader.php             # Loading animation module
│   └── class-update-checker.php          # Auto-update from Github module
│   └── class-fcrm-flower-delivery-disabler.php  # Flower delivery removal module
├── README.md                             # Documentation
├── CHANGELOG.md                          # Changelog
├── LICENSE                               
└── fcrm-tributes-enhancement-suite.php   # Main plugin file
└── icon-256x256.png   			  # Plugin square icon
```


### Filters & Actions
The plugin provides several filters and actions for developers to extend functionality:

#### Filters

```php
// Modify performance optimisation settings
apply_filters('fcrm_enhancement_optimisation_settings', $settings);

// Modify whether flowers are disabled
apply_filters('fcrm_enhancement_disable_flowers', $disabled);

// Modify which scripts are optimised
apply_filters('fcrm_enhancement_script_patterns', $script_patterns);

// Modify style variables
apply_filters('fcrm_enhancement_style_variables', $variables);

// Modify default colors
apply_filters('fcrm_enhancement_default_colors', $colors);

// Modify loader behaviour
apply_filters('fcrm_enhancement_loader_needed', $needs_loader, $post_id);

// Modify loader color
apply_filters('fcrm_enhancement_loader_color', $color);
```

#### Actions

```php
// Fired before optimisation runs
do_action('fcrm_enhancement_before_optimise');

// Fired after optimisation runs
do_action('fcrm_enhancement_after_optimise');

// Fired before styles are generated
do_action('fcrm_enhancement_before_styles');

// Fired after styles are generated
do_action('fcrm_enhancement_after_styles');

// Fired before loader is added
do_action('fcrm_enhancement_before_loader');

// Fired after loader is added
do_action('fcrm_enhancement_after_loader');
```
#### Example Usage

```php
// Modify the default spinner color
add_filter('fcrm_enhancement_loader_color', function($color) {
	return '#FF0000'; // Change spinner to red
});

// Add custom scripts to optimisation
add_filter('fcrm_enhancement_script_patterns', function($patterns) {
	$patterns[] = 'my-custom-script';
	return $patterns;
});

// Do something after optimisation runs
add_action('fcrm_enhancement_after_optimise', function() {
	// Your code here
});
```


## Support

For support and bug reports, please use the GitHub issues system:
1. Check if your issue has already been reported.
2. Use the issue templates provided.
3. Provide as much detail as possible.

This plugin has no direct affiliation with [FireHawk Funerals](https://firehawkfunerals.com/). If you encounter any issues or have any requests, try us at [Weave Digital Studio](mailto:support@weave.co.nz) or log an issue on [Github](https://github.com/weavedigitalstudio/fcrm-tributes-enhancement-suite/issues)

---

## License

This project is licensed under the GPL-3.0+ License. See the [LICENSE](LICENSE) file for details.

---

## Credits

- Developed by [Weave Digital Studio](https://weave.co.nz) in New Zealand.
- FireHawkCRM and their WordPress tributes plugin are from [FireHawk Funerals](https://firehawkfunerals.com).

![Plugin Icon](icon-256x256.png)

---

## Changelog

### v1.3.0 (2025-01-15) - Performance Optimisations
- Completely rebuilt flower delivery disabling functionality for better performance and reliability
- Implemented new system to properly remove flower delivery features from all pages when disabled
- Optimised style loading to only enqueue CSS on tribute pages and pages containing tribute shortcodes
- Improved performance by preventing unnecessary style loading across non-tribute pages
- Optimised code to prevent unnecessary script loading and improve site performance
- Styling change for streaming and social share button colour defaults
- Updated README
- Minor bug fixes and tweaks to auto plugin updates


### v1.1.1 (2024-11-25) - Plugin Auto-Update Version
- Added automatic updates via WordPress dashboard
- Integrated GitHub releases for version control
- Update notifications now include release notes
- No configuration required for update functionality

### v1.1.0 (Initial Public Release)
- Performance optimisation features
- Custom styling interface
- Loading animation functionality
- WordPress 6.0+ compatibility
- PHP 8.0+ support
