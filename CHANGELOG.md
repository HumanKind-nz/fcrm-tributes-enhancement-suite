# Changelog
All notable changes to the FirehawkCRM Tributes Enhancement Suite will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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