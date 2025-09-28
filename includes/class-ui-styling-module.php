<?php
/**
 * FCRM UI Styling Module
 * 
 * Provides comprehensive styling customization options for all layout modules.
 * Generates CSS custom properties based on admin settings to allow easy branding.
 * 
 * @package FCRM_Enhancement_Suite
 * @subpackage UI_Styling
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCRM_UI_Styling_Module {
    
    /**
     * Default styling options
     */
    protected $default_settings = [
        // Global Colors
        'fcrm_ui_primary_color' => '#2563eb',
        'fcrm_ui_secondary_color' => '#64748b',
        'fcrm_ui_accent_color' => '#d4af37',
        'fcrm_ui_background_color' => '#ffffff',
        'fcrm_ui_card_background' => '#ffffff',
        'fcrm_ui_text_color' => '#1e293b',
        'fcrm_ui_border_color' => '#e2e8f0',
        
        // Layout & Spacing
        'fcrm_ui_border_radius' => '8',
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

    /**
     * Color scheme presets
     */
    protected $color_schemes = [
        'default' => [
            'name' => 'Default Blue',
            'primary' => '#2563eb',
            'secondary' => '#64748b',
            'accent' => '#d4af37',
            'background' => '#ffffff',
            'card_background' => '#ffffff',
            'text' => '#1e293b',
            'border' => '#e2e8f0'
        ],
        'warm' => [
            'name' => 'Warm Professional',
            'primary' => '#b45309',
            'secondary' => '#78716c',
            'accent' => '#d97706',
            'background' => '#fefdf8',
            'card_background' => '#ffffff',
            'text' => '#292524',
            'border' => '#e7e5e4'
        ],
        'elegant' => [
            'name' => 'Elegant Traditional',
            'primary' => '#581c87',
            'secondary' => '#6b7280',
            'accent' => '#c7a96b',
            'background' => '#fefefe',
            'card_background' => '#ffffff',
            'text' => '#111827',
            'border' => '#e5e7eb'
        ],
        'modern' => [
            'name' => 'Modern Minimal',
            'primary' => '#059669',
            'secondary' => '#4b5563',
            'accent' => '#10b981',
            'background' => '#f9fafb',
            'card_background' => '#ffffff',
            'text' => '#111827',
            'border' => '#d1d5db'
        ],
        'traditional' => [
            'name' => 'Traditional Navy',
            'primary' => '#1e3a8a',
            'secondary' => '#64748b',
            'accent' => '#dc2626',
            'background' => '#ffffff',
            'card_background' => '#ffffff',
            'text' => '#1e293b',
            'border' => '#cbd5e1'
        ]
    ];

    public function __construct() {
        // Initialize styling if module is enabled
        if ($this->is_enabled()) {
            add_action('wp_head', [$this, 'output_custom_css'], 20);
        }
        
        // Register admin settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // AJAX handlers for color scheme changes
        add_action('wp_ajax_fcrm_apply_color_scheme', [$this, 'apply_color_scheme']);
    }

    /**
     * Check if UI styling module is enabled
     */
    public function is_enabled(): bool {
        return (bool) get_option('fcrm_module_ui_styling_enabled', false);
    }

    /**
     * Register all styling settings
     */
    public function register_settings(): void {
        foreach ($this->default_settings as $setting => $default) {
            register_setting('fcrm_enhancement_ui_styling', $setting);
        }
    }

    /**
     * Enqueue custom styling scripts for admin
     */
    public function enqueue_admin_assets(): void {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        
        wp_enqueue_script(
            'fcrm-ui-styling-admin',
            FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/js/ui-styling-admin.js',
            ['jquery', 'wp-color-picker'],
            FCRM_ENHANCEMENT_SUITE_VERSION,
            true
        );
        
        wp_localize_script('fcrm-ui-styling-admin', 'fcrm_ui_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fcrm_ui_styling'),
            'schemes' => $this->color_schemes
        ]);
    }

    /**
     * Generate and output custom CSS
     */
    public function output_custom_css(): void {
        if (!$this->should_output_styles()) {
            return;
        }

        $css = $this->generate_custom_css();
        if (!empty($css)) {
            echo "<style id='fcrm-ui-custom-styles'>\n" . $css . "\n</style>\n";
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
        $css .= "  --fcrm-ui-secondary: " . $settings['fcrm_ui_secondary_color'] . ";\n";
        $css .= "  --fcrm-ui-accent: " . $settings['fcrm_ui_accent_color'] . ";\n";
        $css .= "  --fcrm-ui-background: " . $settings['fcrm_ui_background_color'] . ";\n";
        $css .= "  --fcrm-ui-card-bg: " . $settings['fcrm_ui_card_background'] . ";\n";
        $css .= "  --fcrm-ui-text: " . $settings['fcrm_ui_text_color'] . ";\n";
        $css .= "  --fcrm-ui-border: " . $settings['fcrm_ui_border_color'] . ";\n";
        
        // Layout Properties
        $css .= "  --fcrm-ui-radius: " . $settings['fcrm_ui_border_radius'] . "px;\n";
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
        $css .= "  box-shadow: var(--fcrm-ui-shadow) !important;\n";
        $css .= "}\n\n";
        
        // Button and interactive elements
        $css .= "/* Interactive Elements */\n";
        $css .= ".modern-search-btn, .elegant-search-btn, .gallery-search-btn, .minimal-search-btn,\n";
        $css .= ".load-more-btn, .view-details-button {\n";
        $css .= "  background-color: var(--fcrm-ui-primary) !important;\n";
        $css .= "  border-radius: var(--fcrm-ui-radius) !important;\n";
        $css .= "  color: white !important;\n";
        $css .= "}\n";
        $css .= ".modern-search-btn:hover, .elegant-search-btn:hover, .gallery-search-btn:hover, .minimal-search-btn:hover,\n";
        $css .= ".load-more-btn:hover, .view-details-button:hover {\n";
        $css .= "  background-color: color-mix(in srgb, var(--fcrm-ui-primary) 80%, black) !important;\n";
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
     * Get all current settings with defaults
     */
    private function get_all_settings(): array {
        $settings = [];
        foreach ($this->default_settings as $key => $default) {
            $settings[$key] = get_option($key, $default);
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
        
        // Content containers with max-width
        $css .= "/* Content Container Max Width */\n";
        $css .= ".fcrm-modern-grid-container, .fcrm-elegant-grid-container, .fcrm-gallery-grid-container, .minimal-tributes-list {\n";
        $css .= "  max-width: var(--fcrm-ui-grid-max-width) !important;\n";
        $css .= "  margin: 0 auto !important;\n";
        $css .= "  padding-left: 2rem !important;\n";
        $css .= "  padding-right: 2rem !important;\n";
        $css .= "}\n\n";
        
        // Search containers with reasonable max-width
        $css .= "/* Search Container Max Width */\n";
        $css .= ".fcrm-modern-search, .fcrm-elegant-search, .fcrm-gallery-search, .fcrm-minimal-search {\n";
        $css .= "  max-width: 1000px !important;\n";
        $css .= "  margin: 0 auto 2rem auto !important;\n";
        $css .= "  padding-left: 2rem !important;\n";
        $css .= "  padding-right: 2rem !important;\n";
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
        $css .= "  box-shadow: var(--fcrm-ui-shadow) !important;\n";
        $css .= "}\n\n";
        
        // Button and interactive elements
        $css .= "/* Interactive Elements */\n";
        $css .= ".modern-search-btn, .elegant-search-btn, .gallery-search-btn, .minimal-search-btn,\n";
        $css .= ".load-more-btn, .view-details-button {\n";
        $css .= "  background-color: var(--fcrm-ui-primary) !important;\n";
        $css .= "  border-radius: var(--fcrm-ui-radius) !important;\n";
        $css .= "  color: white !important;\n";
        $css .= "}\n";
        $css .= ".modern-search-btn:hover, .elegant-search-btn:hover, .gallery-search-btn:hover, .minimal-search-btn:hover,\n";
        $css .= ".load-more-btn:hover, .view-details-button:hover {\n";
        $css .= "  background-color: color-mix(in srgb, var(--fcrm-ui-primary) 80%, black) !important;\n";
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
        return FCRM_Enhancement_Suite::is_tribute_page();
    }

    /**
     * Apply color scheme via AJAX
     */
    public function apply_color_scheme(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('fcrm_ui_styling', 'nonce');
        
        $scheme = sanitize_text_field($_POST['scheme']);
        
        if (!isset($this->color_schemes[$scheme])) {
            wp_send_json_error('Invalid color scheme');
        }
        
        $colors = $this->color_schemes[$scheme];
        
        // Update all color settings
        update_option('fcrm_ui_primary_color', $colors['primary']);
        update_option('fcrm_ui_secondary_color', $colors['secondary']);
        update_option('fcrm_ui_accent_color', $colors['accent']);
        update_option('fcrm_ui_background_color', $colors['background']);
        update_option('fcrm_ui_card_background', $colors['card_background']);
        update_option('fcrm_ui_text_color', $colors['text']);
        update_option('fcrm_ui_border_color', $colors['border']);
        update_option('fcrm_ui_color_scheme', $scheme);
        
        wp_send_json_success(['message' => 'Color scheme applied successfully']);
    }

    /**
     * Render admin settings
     */
    public function render_settings(): void {
        $settings = $this->get_all_settings();
        ?>
        <div class="fcrm-ui-styling-settings">
            
            <!-- Color Scheme Presets -->
            <div class="settings-section">
                <h3>🎨 Color Scheme Presets</h3>
                <div class="section-content">
                    <p><strong>Quick apply professional color schemes, then customize individual colors below.</strong></p>
                    <div class="color-scheme-presets">
                        <?php foreach ($this->color_schemes as $key => $scheme): ?>
                            <div class="scheme-preset" data-scheme="<?php echo esc_attr($key); ?>">
                                <div class="scheme-preview">
                                    <div class="color-swatch" style="background-color: <?php echo esc_attr($scheme['primary']); ?>"></div>
                                    <div class="color-swatch" style="background-color: <?php echo esc_attr($scheme['secondary']); ?>"></div>
                                    <div class="color-swatch" style="background-color: <?php echo esc_attr($scheme['accent']); ?>"></div>
                                    <div class="color-swatch" style="background-color: <?php echo esc_attr($scheme['background']); ?>"></div>
                                </div>
                                <h4><?php echo esc_html($scheme['name']); ?></h4>
                                <button type="button" class="button apply-scheme" data-scheme="<?php echo esc_attr($key); ?>">
                                    Apply Scheme
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Global Colors -->
            <div class="settings-section">
                <h3>🌈 Color Customization</h3>
                <div class="section-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Primary Color</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_ui_primary_color" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_primary_color']); ?>"
                                       class="fcrm-color-picker" />
                                <p class="description">Used for buttons, links, and interactive elements.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Secondary Color</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_ui_secondary_color" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_secondary_color']); ?>"
                                       class="fcrm-color-picker" />
                                <p class="description">Used for secondary text and subtle elements.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Accent Color</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_ui_accent_color" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_accent_color']); ?>"
                                       class="fcrm-color-picker" />
                                <p class="description">Used for special highlights and featured elements.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Background Color</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_ui_background_color" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_background_color']); ?>"
                                       class="fcrm-color-picker" />
                                <p class="description">Main background color for tribute pages.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Background</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_ui_card_background" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_card_background']); ?>"
                                       class="fcrm-color-picker" />
                                <p class="description">Background color for tribute cards.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Text Color</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_ui_text_color" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_text_color']); ?>"
                                       class="fcrm-color-picker" />
                                <p class="description">Main text color.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Border Color</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_ui_border_color" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_border_color']); ?>"
                                       class="fcrm-color-picker" />
                                <p class="description">Color for borders and dividers.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Layout & Spacing -->
            <div class="settings-section">
                <h3>📐 Layout & Spacing</h3>
                <div class="section-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Border Radius</th>
                            <td>
                                <input type="range" 
                                       name="fcrm_ui_border_radius" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_border_radius']); ?>"
                                       min="0" max="20" step="1"
                                       class="fcrm-range-slider" />
                                <span class="range-value"><?php echo esc_html($settings['fcrm_ui_border_radius']); ?>px</span>
                                <p class="description">Corner rounding for cards and buttons (0px = square, 20px = very rounded).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Border Width</th>
                            <td>
                                <input type="range" 
                                       name="fcrm_ui_border_width" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_border_width']); ?>"
                                       min="0" max="5" step="1"
                                       class="fcrm-range-slider" />
                                <span class="range-value"><?php echo esc_html($settings['fcrm_ui_border_width']); ?>px</span>
                                <p class="description">Thickness of borders around cards.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Shadow</th>
                            <td>
                                <select name="fcrm_ui_card_shadow">
                                    <option value="none" <?php selected($settings['fcrm_ui_card_shadow'], 'none'); ?>>None</option>
                                    <option value="subtle" <?php selected($settings['fcrm_ui_card_shadow'], 'subtle'); ?>>Subtle</option>
                                    <option value="medium" <?php selected($settings['fcrm_ui_card_shadow'], 'medium'); ?>>Medium</option>
                                    <option value="elevated" <?php selected($settings['fcrm_ui_card_shadow'], 'elevated'); ?>>Elevated</option>
                                </select>
                                <p class="description">Shadow intensity for tribute cards.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Grid Gap</th>
                            <td>
                                <input type="range" 
                                       name="fcrm_ui_grid_gap" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_grid_gap']); ?>"
                                       min="0.5" max="3" step="0.25"
                                       class="fcrm-range-slider" />
                                <span class="range-value"><?php echo esc_html($settings['fcrm_ui_grid_gap']); ?>rem</span>
                                <p class="description">Space between tribute cards in grid layouts.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Card Padding</th>
                            <td>
                                <input type="range" 
                                       name="fcrm_ui_card_padding" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_card_padding']); ?>"
                                       min="0.5" max="3" step="0.25"
                                       class="fcrm-range-slider" />
                                <span class="range-value"><?php echo esc_html($settings['fcrm_ui_card_padding']); ?>rem</span>
                                <p class="description">Internal padding within tribute cards.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Grid Max Width</th>
                            <td>
                                <input type="range" 
                                       name="fcrm_ui_grid_max_width" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_grid_max_width']); ?>"
                                       min="800" max="1600" step="50"
                                       class="fcrm-range-slider" />
                                <span class="range-value"><?php echo esc_html($settings['fcrm_ui_grid_max_width']); ?>px</span>
                                <p class="description">Maximum width of tribute content (backgrounds are always full-width). Set to 1600px for ultra-wide displays.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Typography -->
            <div class="settings-section">
                <h3>🔤 Typography</h3>
                <div class="section-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Inherit Theme Fonts</th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="fcrm_ui_font_inherit" 
                                           value="1" 
                                           <?php checked($settings['fcrm_ui_font_inherit']); ?> />
                                    Use theme's font settings
                                </label>
                                <p class="description">When enabled, tribute layouts will use your theme's font choices.</p>
                            </td>
                        </tr>
                        <tr class="font-family-row" style="<?php echo $settings['fcrm_ui_font_inherit'] ? 'display: none;' : ''; ?>">
                            <th scope="row">Font Family</th>
                            <td>
                                <select name="fcrm_ui_font_family">
                                    <option value="system" <?php selected($settings['fcrm_ui_font_family'], 'system'); ?>>System Default</option>
                                    <option value="serif" <?php selected($settings['fcrm_ui_font_family'], 'serif'); ?>>Serif (Georgia)</option>
                                    <option value="modern" <?php selected($settings['fcrm_ui_font_family'], 'modern'); ?>>Modern (Inter)</option>
                                    <option value="traditional" <?php selected($settings['fcrm_ui_font_family'], 'traditional'); ?>>Traditional (Crimson Text)</option>
                                </select>
                                <p class="description">Font family for tribute layouts (only when not inheriting).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Font Size Scale</th>
                            <td>
                                <input type="range" 
                                       name="fcrm_ui_font_size_scale" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_font_size_scale']); ?>"
                                       min="80" max="120" step="5"
                                       class="fcrm-range-slider" />
                                <span class="range-value"><?php echo esc_html($settings['fcrm_ui_font_size_scale']); ?>%</span>
                                <p class="description">Scale all font sizes up or down (100% = normal).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Elegant Layout Typography</th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="fcrm_ui_elegant_use_serif" 
                                           value="1" 
                                           <?php checked($settings['fcrm_ui_elegant_use_serif']); ?> />
                                    Use serif font for Elegant layout names
                                </label>
                                <p class="description">Elegant layout will use Georgia serif for tribute names for a classic feel.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Layout-Specific Options -->
            <div class="settings-section">
                <h3>🎛️ Layout-Specific Options</h3>
                <div class="section-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Elegant Gold Color</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_ui_elegant_gold_color" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_elegant_gold_color']); ?>"
                                       class="fcrm-color-picker" />
                                <p class="description">Gold accent color specifically for the Elegant layout.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Gallery Overlay Opacity</th>
                            <td>
                                <input type="range" 
                                       name="fcrm_ui_gallery_overlay_opacity" 
                                       value="<?php echo esc_attr($settings['fcrm_ui_gallery_overlay_opacity']); ?>"
                                       min="60" max="95" step="5"
                                       class="fcrm-range-slider" />
                                <span class="range-value"><?php echo esc_html($settings['fcrm_ui_gallery_overlay_opacity']); ?>%</span>
                                <p class="description">How dark the text overlay appears on Gallery layout photos.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">List View Photo Size</th>
                            <td>
                                <select name="fcrm_ui_list_photo_size">
                                    <option value="small" <?php selected($settings['fcrm_ui_list_photo_size'], 'small'); ?>>Small (45px)</option>
                                    <option value="medium" <?php selected($settings['fcrm_ui_list_photo_size'], 'medium'); ?>>Medium (60px)</option>
                                    <option value="large" <?php selected($settings['fcrm_ui_list_photo_size'], 'large'); ?>>Large (80px)</option>
                                </select>
                                <p class="description">Size of circular photos in List View layout.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Modern Grid Hover Effects</th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="fcrm_ui_modern_hover_lift" 
                                           value="1" 
                                           <?php checked($settings['fcrm_ui_modern_hover_lift']); ?> />
                                    Enable card lift effect on hover
                                </label>
                                <p class="description">Modern grid cards will lift slightly when hovered.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="info-card">
                <h4>🎨 UI Styling Active</h4>
                <p>Custom styling will override the default layout colors and spacing. All changes are applied using CSS custom properties for optimal performance.</p>
            </div>
        </div>

        <style>
        .fcrm-ui-styling-settings .color-scheme-presets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .scheme-preset {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            background: #fff;
        }
        
        .scheme-preview {
            display: flex;
            gap: 4px;
            margin-bottom: 0.5rem;
            justify-content: center;
        }
        
        .color-swatch {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid #ddd;
        }
        
        .scheme-preset h4 {
            margin: 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .apply-scheme {
            font-size: 0.8rem;
        }
        
        .fcrm-range-slider {
            width: 200px;
            margin-right: 1rem;
        }
        
        .range-value {
            font-weight: bold;
            color: #0073aa;
        }
        
        .font-family-row {
            transition: opacity 0.3s ease;
        }
        </style>
        <?php
    }
} 