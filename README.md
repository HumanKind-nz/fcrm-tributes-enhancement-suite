# FireHawk CRM Tributes Optimiser

Optimises the FireHawk CRM Tributes WordPress plugin by intelligently managing script loading and features.

## Description

This plugin enhances the performance of the FireHawk CRM Tributes WordPress plugin by:

1. **Smart Asset Loading**
   - Conditionally loads scripts and styles only on tribute-related pages
   - Preserves theme versions of common libraries (Bootstrap, FontAwesome)
   - Removes unnecessary DNS prefetch hints
   - Cleans up hardcoded scripts

2. **Feature Management**
   - Optional disable of flower delivery functionality
   - Configurable through simple admin interface
   - No code modifications required

3. **Performance Optimisations**
   - Static caching for tribute page detection
   - Optimised regex patterns for script removal
   - Efficient asset handling
   - Proper WordPress hooks usage

## Requirements

- WordPress 6.0 or higher
- PHP 7.2 or higher
- FireHawk CRM Tributes plugin (v2.0.1.12 or higher)

## Installation

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure options in Settings → FCRM Optimiser

## Configuration

The plugin provides two main configuration options:

1. **Script Optimisation**
   - Enable/disable selective script loading
   - When enabled, FCRM Tributes scripts only load on tribute-related pages

2. **Flower Delivery**
   - Optionally disable flower delivery functionality
   - Prevents all flower delivery related files from loading

## Credits

FireHawk CRM and their WordPress tributes plugin are from [FireHawk Funerals](https://firehawkfunerals.com)

## License

MIT License - See LICENSE file for details.

## Changelog

### 1.0.0
- Complete rewrite with expanded optimisation features
- Added admin interface for configuration
- Smart asset loading system
- Improved performance optimisations
- Better compatibility with theme assets