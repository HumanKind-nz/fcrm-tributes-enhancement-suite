![Plugin Header](assets/plugin-header.png)

# FireHawkCRM Tributes - Enhancement Suite

A enhancement suite for the FireHawkCRM Tributes WordPress plugin that initially combines performance optimisations, custom styling, and loading animations into one unified solution.

---

## Quick Start

1. Activate the plugin from the WordPress Plugins menu.
2. Navigate to the "FH Tributes Enhancements" menu in the WordPress admin panel.
3. Configure the performance, styling, and loading animation settings to suit your needs.
4. Save your changes and refresh your site to see the enhancements.

---

## Features

### Performance Optimisation
- Conditionally loads scripts and styles only on tribute-related pages (Not Side-wide).
- Optionally disables flower delivery functionality (disabled by default).
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

## Installation

1. Download the latest release from the GitHub repository.
2. Upload to your WordPress site through the Plugins menu or via FTP.
3. Activate the plugin through the WordPress admin interface.
4. Configure settings under "FH Enhancements" in the admin menu.

---

## Configuration

### Performance Settings
- Navigate to FH Tributes Enhancements → Performance.
- Enable/disable performance optimisations.
- Configure flower delivery functionality.

### Styling Settings
- Navigate to FH Tributes Enhancements → Custom Styles.
- Use the colour pickers to customise various elements.
- Save changes to apply new styles.
- Use the "Reset Colours" button to restore defaults.

### Loading Animation Settings
- Navigate to FH Tributes Enhancements → Loading Animation.
- Enable/disable the loading animation.
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
│   └── class-github-updater.php          # Auto-update from Github module
├── README.md                             # Documentation
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

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Credits

- Developed by [Weave Digital Studio](https://weave.co.nz) in New Zealand.
- FireHawkCRM and their WordPress tributes plugin are from [FireHawk Funerals](https://firehawkfunerals.com).

![Plugin Icon](icon-256x256.png)

---

## Changelog

### v1.1.1 (2024-11-25) - Auto-Update Version
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
