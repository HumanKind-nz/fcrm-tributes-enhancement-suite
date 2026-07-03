<?php
declare(strict_types=1);

/**
 * FCRM UI Styling Module
 * 
 * Provides comprehensive styling customization options for all layout modules.
 * Generates CSS custom properties based on admin settings to allow easy branding.
 * 
 * @package FcrmEnhancementSuite
 * @subpackage UI_Styling
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use function FcrmEnhancementSuite\Helpers\get_setting;

class FCRM_UI_Styling_Module {
    
    /**
     * Default styling options
     */
    protected $default_settings = [
        // Global Colors
        'fcrm_ui_primary_color' => '#2563eb',
        'fcrm_ui_primary_text_color' => '#ffffff',
        'fcrm_ui_primary_button_text_color' => '#ffffff',
        'fcrm_ui_secondary_color' => '#64748b',
        'fcrm_ui_accent_color' => '#d4af37',
        'fcrm_ui_background_color' => '#ffffff',
        'fcrm_ui_card_background' => '#ffffff',
        'fcrm_ui_text_color' => '#1e293b',
        'fcrm_ui_border_color' => '#e2e8f0',
        
        // Layout & Spacing
        'fcrm_ui_border_radius' => '8',
        'fcrm_ui_control_radius' => '8',
        'fcrm_ui_border_width' => '1',
        'fcrm_ui_card_shadow' => 'subtle',
        'fcrm_ui_grid_gap' => '1.5',
        'fcrm_ui_card_padding' => '1.5',
        'fcrm_ui_grid_max_width' => '1200',
        
        // Typography
        'fcrm_ui_font_inherit' => true,
        'fcrm_ui_font_family' => 'system',
        'fcrm_ui_font_size_scale' => '100',
        'fcrm_ui_elegant_use_serif' => true,
        
        // Layout-specific
        'fcrm_ui_elegant_gold_color' => '#d4af37',
        'fcrm_ui_gallery_overlay_opacity' => '85',
        'fcrm_ui_list_photo_size' => 'medium',
        'fcrm_ui_modern_hover_lift' => true,
        
        // Preset scheme
        'fcrm_ui_color_scheme' => 'default'
    ];


    public function __construct() {
        // Initialize styling if module is enabled
        if ($this->is_enabled()) {
            add_action('wp_enqueue_scripts', [$this, 'output_custom_css'], 100);
        }

        // Register admin settings
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Check if UI styling module is enabled
     */
    public function is_enabled(): bool {
        return 'modern' === \FcrmEnhancementSuite\Status\get_layout_mode();
    }

    /**
     * Register all styling settings
     */
    public function register_settings(): void {
        // Settings now registered centrally in inc/settings-page.php.
    }

    /**
     * Enqueue custom styling scripts for admin
     */
    public function enqueue_admin_assets(): void {
        // Admin assets now handled by React settings app.
    }

    /**
     * Get GeneratePress Global Colors for color picker palette
     */
    private function get_generatepress_colors(): array {
        $colors = [];

        // Check if GeneratePress is active and has global colors
        if (function_exists('generate_get_option')) {
            // Get GeneratePress color settings
            $gp_settings = get_option('generate_settings', []);

            // Common GeneratePress color options
            $color_options = [
                'accent_color' => generate_get_option('accent_color'),
                'contrast_color' => generate_get_option('contrast_color'),
                'header_background_color' => generate_get_option('header_background_color'),
                'header_text_color' => generate_get_option('header_text_color'),
                'navigation_background_color' => generate_get_option('navigation_background_color'),
                'navigation_text_color' => generate_get_option('navigation_text_color'),
                'footer_background_color' => generate_get_option('footer_background_color'),
                'footer_text_color' => generate_get_option('footer_text_color'),
            ];

            // Filter out empty values and duplicates
            foreach ($color_options as $color) {
                if (!empty($color) && $color !== '#' && !in_array($color, $colors)) {
                    $colors[] = $color;
                }
            }
        }

        // Fallback: Check for GeneratePress Premium global colors (stored differently)
        if (empty($colors) && function_exists('generatepress_get_color_setting')) {
            $premium_colors = get_option('generate_secondary_nav_settings', []);
            if (isset($premium_colors['colors']) && is_array($premium_colors['colors'])) {
                foreach ($premium_colors['colors'] as $color) {
                    if (!empty($color) && $color !== '#' && !in_array($color, $colors)) {
                        $colors[] = $color;
                    }
                }
            }
        }

        return $colors;
    }

    /**
     * Generate and output custom CSS
     *
     * Uses wp_add_inline_style() to ensure our CSS comes AFTER the layout CSS files,
     * allowing our color overrides to properly cascade.
     */
    public function output_custom_css(): void {
        if (!$this->should_output_styles()) {
            return;
        }

        $css = $this->generate_custom_css();
        if (!empty($css)) {
            // Check if the shared stylesheet is registered/enqueued
            if (wp_style_is('fcrm-layouts-shared', 'registered') || wp_style_is('fcrm-layouts-shared', 'enqueued')) {
                // Attach inline CSS to the shared base stylesheet to ensure it loads after all layout CSS
                wp_add_inline_style('fcrm-layouts-shared', $css);
            } else {
                // Fallback: output as inline style tag if shared CSS isn't loaded yet
                add_action('wp_head', function() use ($css) {
                    echo "<style id='fcrm-ui-custom-styles'>\n" . $css . "\n</style>\n";
                }, 999);
            }
        }
    }

    /**
     * Check if we should output custom styles
     */
    private function should_output_styles(): bool {
        // Check if we're on a page that uses layouts
        $layouts_module = new FCRM_Layouts_Module();
        return $layouts_module->is_enabled() && $this->is_tribute_page();
    }

    /**
     * Generate custom CSS from settings
     */
    private function generate_custom_css(): string {
        $settings = $this->get_all_settings();

        $css = "/* FCRM UI Custom Styling */\n";
        $css .= ":root {\n";

        // Global CSS Custom Properties
        $css .= "  --fcrm-ui-primary: " . $settings['fcrm_ui_primary_color'] . ";\n";
        $css .= "  --fcrm-ui-primary-text: " . $settings['fcrm_ui_primary_text_color'] . ";\n";
        $css .= "  --fcrm-ui-primary-button-text: " . $settings['fcrm_ui_primary_button_text_color'] . ";\n";
        $css .= "  --fcrm-ui-secondary: " . $settings['fcrm_ui_secondary_color'] . ";\n";
        $css .= "  --fcrm-ui-accent: " . $settings['fcrm_ui_accent_color'] . ";\n";
        $css .= "  --fcrm-ui-background: " . $settings['fcrm_ui_background_color'] . ";\n";
        $css .= "  --fcrm-ui-card-bg: " . $settings['fcrm_ui_card_background'] . ";\n";
        $css .= "  --fcrm-ui-text: " . $settings['fcrm_ui_text_color'] . ";\n";
        $css .= "  --fcrm-ui-border: " . $settings['fcrm_ui_border_color'] . ";\n";
        
        // Layout Properties
        $css .= "  --fcrm-ui-radius: " . $settings['fcrm_ui_border_radius'] . "px;\n";
        $css .= "  --fcrm-ui-control-radius: " . $settings['fcrm_ui_control_radius'] . "px;\n";
        $css .= "  --fcrm-ui-border-width: " . $settings['fcrm_ui_border_width'] . "px;\n";
        $css .= "  --fcrm-ui-grid-gap: " . $settings['fcrm_ui_grid_gap'] . "rem;\n";
        $css .= "  --fcrm-ui-card-padding: " . $settings['fcrm_ui_card_padding'] . "rem;\n";
        $css .= "  --fcrm-ui-grid-max-width: " . $settings['fcrm_ui_grid_max_width'] . "px;\n";
        
        // Typography
        if (!$settings['fcrm_ui_font_inherit']) {
            $css .= "  --fcrm-ui-font: " . $this->get_font_stack($settings['fcrm_ui_font_family']) . ";\n";
        }
        $scale = $settings['fcrm_ui_font_size_scale'] / 100;
        $css .= "  --fcrm-ui-font-scale: " . $scale . ";\n";
        
        // Layout-specific
        $css .= "  --fcrm-ui-elegant-gold: " . $settings['fcrm_ui_elegant_gold_color'] . ";\n";
        $css .= "  --fcrm-ui-gallery-opacity: " . ($settings['fcrm_ui_gallery_overlay_opacity'] / 100) . ";\n";
        
        // Shadow definitions
        $css .= "  --fcrm-ui-shadow: " . $this->get_shadow_css($settings['fcrm_ui_card_shadow']) . ";\n";
        
        $css .= "}\n\n";
        
        // Elegant Layout Comprehensive Override
        $css .= "/* Elegant Layout Color Override */\n";
        $css .= ".fcrm-elegant-grid {\n";
        
        // Override gold colors (always, not just when changed)
        $css .= "  --elegant-gold: var(--fcrm-ui-elegant-gold) !important;\n";
        $css .= "  --elegant-gold-light: color-mix(in srgb, var(--fcrm-ui-elegant-gold) 80%, white) !important;\n";
        $css .= "  --elegant-gold-dark: color-mix(in srgb, var(--fcrm-ui-elegant-gold) 80%, black) !important;\n";
        
        // Override grey scale to use theme colors
        $css .= "  --elegant-grey-50: var(--fcrm-ui-background) !important;\n";
        $css .= "  --elegant-grey-100: color-mix(in srgb, var(--fcrm-ui-border) 30%, var(--fcrm-ui-background)) !important;\n";
        $css .= "  --elegant-grey-200: var(--fcrm-ui-border) !important;\n";
        $css .= "  --elegant-grey-300: color-mix(in srgb, var(--fcrm-ui-border) 70%, var(--fcrm-ui-text)) !important;\n";
        $css .= "  --elegant-grey-400: var(--fcrm-ui-secondary) !important;\n";
        $css .= "  --elegant-grey-500: color-mix(in srgb, var(--fcrm-ui-secondary) 80%, var(--fcrm-ui-text)) !important;\n";
        $css .= "  --elegant-grey-600: color-mix(in srgb, var(--fcrm-ui-secondary) 60%, var(--fcrm-ui-text)) !important;\n";
        $css .= "  --elegant-grey-700: color-mix(in srgb, var(--fcrm-ui-secondary) 40%, var(--fcrm-ui-text)) !important;\n";
        $css .= "  --elegant-grey-800: var(--fcrm-ui-text) !important;\n";
        $css .= "  --elegant-grey-900: color-mix(in srgb, var(--fcrm-ui-text) 80%, black) !important;\n";
        
        // Override white colors
        $css .= "  --elegant-white: var(--fcrm-ui-card-bg) !important;\n";
        $css .= "  --elegant-off-white: color-mix(in srgb, var(--fcrm-ui-card-bg) 98%, var(--fcrm-ui-border)) !important;\n";
        
        $css .= "}\n\n";
        
        // Apply styles to all layouts
        $css .= $this->get_layout_overrides($settings);
        
        // Date input styling for better contrast and readability
        $css .= "/* Date Input Styling */\n";
        $css .= "input[type=\"date\"] {\n";
        $css .= "  color: var(--fcrm-ui-text) !important;\n";
        $css .= "  font-weight: 500 !important;\n";
        $css .= "}\n";
        $css .= "input[type=\"date\"]::-webkit-calendar-picker-indicator {\n";
        $css .= "  opacity: 0.7;\n";
        $css .= "  cursor: pointer;\n";
        $css .= "  filter: invert(0.5);\n";
        $css .= "}\n";
        $css .= "input[type=\"date\"]::-webkit-calendar-picker-indicator:hover {\n";
        $css .= "  opacity: 1;\n";
        $css .= "  filter: invert(0.3);\n";
        $css .= "}\n";
        $css .= "/* Firefox date input styling */\n";
        $css .= "input[type=\"date\"]::-moz-placeholder {\n";
        $css .= "  opacity: 1;\n";
        $css .= "  color: var(--fcrm-ui-secondary) !important;\n";
        $css .= "}\n\n";
        
        // Date picker proportions for better spacing
        $css .= "/* Search Layout Proportions */\n";
        $css .= ".name-search-modern, .name-search-elegant, .name-search-gallery {\n";
        $css .= "  flex: 2 !important;\n";
        $css .= "  min-width: 250px !important;\n";
        $css .= "}\n";
        $css .= ".date-picker-modern, .date-picker-elegant, .date-picker-gallery {\n";
        $css .= "  flex: 1.5 !important;\n";
        $css .= "  min-width: 200px !important;\n";
        $css .= "}\n";
        $css .= "/* Minimal layout has different structure */\n";
        $css .= ".fcrm-minimal-search .input-group.date-search {\n";
        $css .= "  flex: 1.5 !important;\n";
        $css .= "  min-width: 200px !important;\n";
        $css .= "}\n";
        $css .= ".fcrm-minimal-search .input-group.name-search {\n";
        $css .= "  flex: 2 !important;\n";
        $css .= "  min-width: 250px !important;\n";
        $css .= "}\n\n";

        // Card styles - minimal layout items only (modern cards need no padding for edge-to-edge images)
        $css .= "/* Card Overrides */\n";
        $css .= ".minimal-tribute-item {\n";
        $css .= "  background-color: var(--fcrm-ui-card-bg) !important;\n";
        $css .= "  border-radius: var(--fcrm-ui-radius) !important;\n";
        $css .= "  border: var(--fcrm-ui-border-width) solid var(--fcrm-ui-border) !important;\n";
        $css .= "  box-shadow: var(--fcrm-ui-shadow) !important;\n";
        $css .= "  padding: var(--fcrm-ui-card-padding) !important;\n";
        $css .= "}\n\n";

        // Modern cards - different structure, no padding needed
        $css .= "/* Modern Grid Cards - No padding for edge-to-edge images */\n";
        $css .= ".fcrm-tribute-card {\n";
        $css .= "  background-color: var(--fcrm-ui-card-bg) !important;\n";
        $css .= "  border-radius: var(--fcrm-ui-radius) !important;\n";
        $css .= "  border: var(--fcrm-ui-border-width) solid var(--fcrm-ui-border) !important;\n";
        $css .= "  box-shadow: var(--fcrm-ui-shadow) !important;\n";
        $css .= "}\n\n";

        // Button and interactive elements
        $css .= "/* Interactive Elements */\n";
        $css .= ".modern-search-btn, .elegant-search-btn, .gallery-search-btn, .minimal-search-btn,\n";
        $css .= ".load-more-btn, .view-details-button {\n";
        $css .= "  background-color: var(--fcrm-ui-primary) !important;\n";
        $css .= "  border-radius: var(--fcrm-ui-control-radius) !important;\n";
        $css .= "  color: var(--fcrm-ui-primary-button-text) !important;\n";
        $css .= "}\n";
        $css .= ".modern-search-btn:hover, .elegant-search-btn:hover, .gallery-search-btn:hover, .minimal-search-btn:hover,\n";
        $css .= ".load-more-btn:hover, .view-details-button:hover {\n";
        $css .= "  background-color: color-mix(in srgb, var(--fcrm-ui-primary) 80%, black) !important;\n";
        $css .= "  color: var(--fcrm-ui-primary-button-text) !important;\n";
        $css .= "}\n\n";
        $css .= "/* Search fields follow the button/field radius */\n";
        $css .= ".fcrm-unified-search .input-group {\n";
        $css .= "  border-radius: var(--fcrm-ui-control-radius) !important;\n";
        $css .= "}\n\n";

        // Links
        $css .= "/* Links */\n";
        $css .= ".tribute-name-link, .tribute-link {\n";
        $css .= "  color: var(--fcrm-ui-text) !important;\n";
        $css .= "}\n";
        $css .= ".tribute-name-link:hover, .tribute-link:hover {\n";
        $css .= "  color: var(--fcrm-ui-primary) !important;\n";
        $css .= "}\n\n";
        
        // Gallery overlay opacity
        if ($settings['fcrm_ui_gallery_overlay_opacity'] != 85) {
            $css .= "/* Gallery Overlay Opacity Override */\n";
            $css .= ".gallery-overlay {\n";
            $css .= "  background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.2) 40%, rgba(0, 0, 0, var(--fcrm-ui-gallery-opacity)) 100%) !important;\n";
            $css .= "}\n\n";
        }
        
        // List view photo size
        if ($settings['fcrm_ui_list_photo_size'] !== 'medium') {
            $size = $this->get_list_photo_size($settings['fcrm_ui_list_photo_size']);
            $css .= "/* List View Photo Size Override */\n";
            $css .= ".minimal-tribute-item .tribute-photo,\n";
            $css .= ".minimal-tribute-item .tribute-photo-placeholder {\n";
            $css .= "  width: {$size}px !important;\n";
            $css .= "  height: {$size}px !important;\n";
            $css .= "}\n\n";
        }

        // Grid gaps
        $css .= "/* Grid Gap Overrides */\n";
        $css .= ".tributes-grid, .gallery-tributes-grid {\n";
        $css .= "  gap: var(--fcrm-ui-grid-gap) !important;\n";
        $css .= "}\n\n";

        // Single Tribute Button Styling (Enhanced Classic & Modern Hero)
        // Only add if we're on a single tribute page
        if (isset($_GET['id'])) {
            $css .= "/* Single Tribute Enhanced Classic Button Styling */\n";
            $css .= ".fcrm-enhanced-classic {\n";
            $css .= "  --enhanced-primary: var(--fcrm-ui-primary) !important;\n";
            $css .= "  --enhanced-primary-hover: color-mix(in srgb, var(--fcrm-ui-primary) 80%, black) !important;\n";
            $css .= "  --enhanced-border: var(--fcrm-ui-border) !important;\n";
            $css .= "  --enhanced-border-hover: color-mix(in srgb, var(--fcrm-ui-border) 70%, var(--fcrm-ui-text)) !important;\n";
            $css .= "  --enhanced-nav-bg: color-mix(in srgb, var(--fcrm-ui-background) 98%, var(--fcrm-ui-border)) !important;\n";
            $css .= "  --enhanced-nav-hover: color-mix(in srgb, var(--fcrm-ui-background) 95%, var(--fcrm-ui-border)) !important;\n";
            $css .= "  --enhanced-nav-active: var(--fcrm-ui-primary) !important;\n";
            $css .= "  /* Override ALL FireHawk CSS variables for complete control */\n";
            $css .= "  --fcrm-link-color: var(--fcrm-ui-primary) !important;\n";
            $css .= "  --fcrm-primary-button: var(--fcrm-ui-primary) !important;\n";
            $css .= "  --fcrm-primary-button-text: var(--fcrm-ui-primary-button-text) !important;\n";
            $css .= "  --fcrm-primary-button-hover: color-mix(in srgb, var(--fcrm-ui-primary) 85%, black) !important;\n";
            $css .= "  --fcrm-primary-button-hover-text: var(--fcrm-ui-primary-button-text) !important;\n";
            $css .= "  --fcrm-focus-shadow-color: color-mix(in srgb, var(--fcrm-ui-primary) 25%, transparent) !important;\n";
            $css .= "  --fcrm-focus-border-color: color-mix(in srgb, var(--fcrm-ui-primary) 50%, white) !important;\n";
            $css .= "}\n\n";

            $css .= "/* Modern Hero Button Styling */\n";
            $css .= ".fcrm-modern-hero {\n";
            $css .= "  --hero-primary: var(--fcrm-ui-primary) !important;\n";
            $css .= "  --hero-primary-hover: color-mix(in srgb, var(--fcrm-ui-primary) 80%, black) !important;\n";
            $css .= "  --hero-accent: var(--fcrm-ui-accent) !important;\n";
            $css .= "}\n\n";
        }

        return $css;
    }

    /**
     * Get all current settings with defaults
     */
    private function get_all_settings(): array {
        $settings = [];
        // Map from old fcrm_ui_* keys to new consolidated keys (strip 'fcrm_' prefix).
        foreach ($this->default_settings as $key => $default) {
            $new_key = str_replace('fcrm_', '', $key);
            $settings[$key] = get_setting($new_key, $default);
        }
        return $settings;
    }

    /**
     * Get font stack based on selection
     */
    private function get_font_stack(string $font): string {
        switch ($font) {
            case 'system':
                return '-apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif';
            case 'serif':
                return 'Georgia, "Times New Roman", serif';
            case 'modern':
                return '"Inter", -apple-system, BlinkMacSystemFont, sans-serif';
            case 'traditional':
                return '"Crimson Text", Georgia, serif';
            default:
                return 'inherit';
        }
    }

    /**
     * Get shadow CSS based on selection
     */
    private function get_shadow_css(string $shadow): string {
        switch ($shadow) {
            case 'none':
                return 'none';
            case 'subtle':
                return '0 1px 3px rgba(0, 0, 0, 0.1)';
            case 'medium':
                return '0 4px 12px rgba(0, 0, 0, 0.15)';
            case 'elevated':
                return '0 8px 25px rgba(0, 0, 0, 0.2)';
            default:
                return '0 1px 3px rgba(0, 0, 0, 0.1)';
        }
    }

    /**
     * Generate layout-specific CSS overrides
     */
    private function get_layout_overrides(array $settings): string {
        $css = "";
        
        // Global layout styles
        $css .= "/* Global Layout Overrides */\n";
        $css .= ".fcrm-modern-grid, .fcrm-elegant-grid, .fcrm-gallery-grid, .fcrm-minimal-layout {\n";
        $css .= "  background-color: var(--fcrm-ui-background) !important;\n";
        $css .= "  max-width: none !important;\n";
        $css .= "  width: 100% !important;\n";
        if (!$settings['fcrm_ui_font_inherit']) {
            $css .= "  font-family: var(--fcrm-ui-font) !important;\n";
        }
        $css .= "  font-size: calc(1rem * var(--fcrm-ui-font-scale));\n";
        $css .= "  color: var(--fcrm-ui-text);\n";
        $css .= "}\n\n";
        
        // Content containers with max-width (no padding - page builder handles this)
        $css .= "/* Content Container Max Width */\n";
        $css .= ".fcrm-modern-grid-container, .fcrm-elegant-grid-container, .fcrm-gallery-grid-container, .minimal-tributes-list {\n";
        $css .= "  max-width: var(--fcrm-ui-grid-max-width) !important;\n";
        $css .= "  margin: 0 auto !important;\n";
        $css .= "}\n\n";

        // Search containers with reasonable max-width (no padding - page builder handles this)
        $css .= "/* Search Container Max Width */\n";
        $css .= ".fcrm-modern-search, .fcrm-elegant-search, .fcrm-gallery-search, .fcrm-minimal-search {\n";
        $css .= "  max-width: 1000px !important;\n";
        $css .= "  margin: 0 auto 2rem auto !important;\n";
        $css .= "}\n\n";

        // Card styles - different handling for different layout types
        $css .= "/* Card Overrides */\n";
        $css .= ".minimal-tribute-item {\n";
        $css .= "  background-color: var(--fcrm-ui-card-bg) !important;\n";
        $css .= "  border-radius: var(--fcrm-ui-radius) !important;\n";
        $css .= "  border: var(--fcrm-ui-border-width) solid var(--fcrm-ui-border) !important;\n";
        $css .= "  box-shadow: var(--fcrm-ui-shadow) !important;\n";
        $css .= "  padding: var(--fcrm-ui-card-padding) !important;\n";
        $css .= "}\n\n";

        // Modern cards - different structure, no padding needed for edge-to-edge images
        $css .= "/* Modern Grid Cards - No padding for edge-to-edge images */\n";
        $css .= ".fcrm-tribute-card {\n";
        $css .= "  background-color: var(--fcrm-ui-card-bg) !important;\n";
        $css .= "  border-radius: var(--fcrm-ui-radius) !important;\n";
        $css .= "  border: var(--fcrm-ui-border-width) solid var(--fcrm-ui-border) !important;\n";
        $css .= "  box-shadow: var(--fcrm-ui-shadow) !important;\n";
        $css .= "}\n\n";

        // Button and interactive elements
        $css .= "/* Interactive Elements */\n";
        $css .= ".modern-search-btn, .elegant-search-btn, .gallery-search-btn, .minimal-search-btn,\n";
        $css .= ".load-more-btn, .view-details-button {\n";
        $css .= "  background-color: var(--fcrm-ui-primary) !important;\n";
        $css .= "  border-radius: var(--fcrm-ui-control-radius) !important;\n";
        $css .= "  color: var(--fcrm-ui-primary-button-text) !important;\n";
        $css .= "}\n";
        $css .= ".modern-search-btn:hover, .elegant-search-btn:hover, .gallery-search-btn:hover, .minimal-search-btn:hover,\n";
        $css .= ".load-more-btn:hover, .view-details-button:hover {\n";
        $css .= "  background-color: color-mix(in srgb, var(--fcrm-ui-primary) 80%, black) !important;\n";
        $css .= "  color: var(--fcrm-ui-primary-button-text) !important;\n";
        $css .= "}\n\n";
        
        // Links
        $css .= "/* Links */\n";
        $css .= ".tribute-name-link, .tribute-link {\n";
        $css .= "  color: var(--fcrm-ui-text) !important;\n";
        $css .= "}\n";
        $css .= ".tribute-name-link:hover, .tribute-link:hover {\n";
        $css .= "  color: var(--fcrm-ui-primary) !important;\n";
        $css .= "}\n\n";
        
        // Gallery overlay opacity
        if ($settings['fcrm_ui_gallery_overlay_opacity'] != 85) {
            $css .= "/* Gallery Overlay Opacity Override */\n";
            $css .= ".gallery-overlay {\n";
            $css .= "  background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.2) 40%, rgba(0, 0, 0, var(--fcrm-ui-gallery-opacity)) 100%) !important;\n";
            $css .= "}\n\n";
        }
        
        // List view photo size
        if ($settings['fcrm_ui_list_photo_size'] !== 'medium') {
            $size = $this->get_list_photo_size($settings['fcrm_ui_list_photo_size']);
            $css .= "/* List View Photo Size Override */\n";
            $css .= ".minimal-tribute-item .tribute-photo,\n";
            $css .= ".minimal-tribute-item .tribute-photo-placeholder {\n";
            $css .= "  width: {$size}px !important;\n";
            $css .= "  height: {$size}px !important;\n";
            $css .= "}\n\n";
        }
        
        // Grid gaps
        $css .= "/* Grid Gap Overrides */\n";
        $css .= ".tributes-grid, .gallery-tributes-grid {\n";
        $css .= "  gap: var(--fcrm-ui-grid-gap) !important;\n";
        $css .= "}\n\n";
        
        return $css;
    }

    /**
     * Get list photo size in pixels
     */
    private function get_list_photo_size(string $size): int {
        switch ($size) {
            case 'small': return 45;
            case 'medium': return 60;
            case 'large': return 80;
            default: return 60;
        }
    }

    /**
     * Check if current page shows tributes
     */
    private function is_tribute_page(): bool {
        // Use the standardised tribute page detection from main plugin
        return \FcrmEnhancementSuite\TributeDetection\is_tribute_page();
    }


    /**
     * Render admin settings
     */
    public function render_settings(): void {
        // Settings now rendered by React admin UI.
    }
} 