<?php
declare(strict_types=1);

/**
 * FCRM Styling Module
 * 
 * Comprehensive colour and styling overrides for FireHawk Tributes
 * Based on the original styling system with all colour options
 */

if (!defined('ABSPATH')) {
    exit;
}

use function FcrmEnhancementSuite\Helpers\get_setting;

class FCRM_Styling_Module {
    
    /**
     * Available colour settings with their labels
     */
    private $color_settings = [
        'primary-color'              => 'Primary Colour',
        'secondary-color'            => 'Secondary Colour',
        'primary-button'             => 'Search &amp; Primary Button Colour',
        'primary-button-text'        => 'Search &amp; Primary Button Text Colour',
        'primary-button-hover'       => 'Search &amp; Primary Button Hover Colour',
        'primary-button-hover-text'  => 'Search &amp; Primary Button Hover Text Colour',
        'secondary-button'           => 'Secondary Button Colour',
        'secondary-button-text'      => 'Secondary Button Text Colour',
        'secondary-button-border'    => 'Secondary Button Border Colour',
        'secondary-button-hover'     => 'Secondary Button Hover Colour',
        'secondary-button-hover-text'=> 'Secondary Button Hover Text Colour',
        'secondary-button-hover-border' => 'Secondary Button Hover Border Colour',
        'focus-border-color'         => 'Grid Card Border Colour',
        'card-background'            => 'Grid Card Background Colour',
        'primary-shadow'             => 'Grid Card Box Shading',
        'focus-shadow-color'         => 'Focus Shadow Colour',
        'link-color'                 => 'Link Colour'
    ];

    public function __construct() {
        
        // Always register settings so they can be saved
        add_action('admin_init', [$this, 'register_settings']);
        
        
        // Only load FireHawk colour overrides when in FireHawk (legacy) layout mode.
        if ( 'firehawk' !== \FcrmEnhancementSuite\Status\get_layout_mode() ) {
            return;
        }
        
        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'handle_reset_colors']);
    }
    

    public function register_settings(): void {
        // Settings now registered centrally in inc/settings-page.php.
    }

    /**
     * Get all settings with defaults (like UI module)
     */
    private function get_all_settings(): array {
        $settings = [];
        foreach ($this->color_settings as $key => $label) {
            // Convert hyphenated key to underscore: "primary-button" → "styling_primary_button"
            $new_key = 'styling_' . str_replace('-', '_', $key);
            $option_name = 'fcrm_styling_' . $key;
            $settings[$option_name] = get_setting($new_key, $this->get_default_value($key));
        }
        $settings['fcrm_styling_border_radius'] = get_setting('styling_border_radius', '6px');
        $settings['fcrm_styling_grid_border_radius'] = get_setting('styling_grid_border_radius', '18px');
        return $settings;
    }

    /**
     * Get default values for colour settings
     */
    private function get_default_value(string $key): string {
        $defaults = [
            'primary-color'              => '#FFFFFF',
            'secondary-color'            => '#000000',
            'primary-button'             => '#007BFF',
            'primary-button-text'        => '#FFFFFF',
            'primary-button-hover'       => '#0056B3',
            'primary-button-hover-text'  => '#FFFFFF',
            'secondary-button'           => '#6C757D',
            'secondary-button-text'      => '#FFFFFF',
            'secondary-button-border'    => '#FFFFFF',
            'secondary-button-hover'     => '#FFFFFF',
            'secondary-button-hover-text'=> '#6C757D',
            'secondary-button-hover-border' => '#6C757D',
            'focus-border-color'         => '#007BFF',
            'card-background'            => '#FFFFFF',
            'primary-shadow'             => 'rgba(0, 0, 0, 0.1)',
            'focus-shadow-color'         => '#80BDFF',
            'link-color'                 => '#0000EE',
            'border-radius'              => '6px',
            'grid-border-radius'         => '18px'
        ];
        
        return $defaults[$key] ?? '#FFFFFF';
    }

    public function render_settings(): void {
        // Settings now rendered by React admin UI.
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook_suffix): void {
        // Admin assets now handled by React settings app.
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void {
        // Check if this is a tribute page
        if (!\FcrmEnhancementSuite\TributeDetection\is_tribute_page()) {
            return;
        }
        
        // Generate direct CSS with actual color values and maximum specificity
        $custom_css = $this->generate_direct_css();
        
        if (!empty($custom_css)) {
            // Add CSS directly to wp_head with maximum priority
            add_action('wp_head', function() use ($custom_css) {
                echo '<style type="text/css" id="fcrm-styling-overrides">' . "\n" . $custom_css . "\n" . '</style>' . "\n";
            }, 99999);
        }
    }

    /**
     * Generate direct CSS with actual color values and maximum specificity
     */
    private function generate_direct_css(): string {
        $css = '';
        
        // Get all the color values
        $primary_button = get_setting('styling_primary_button');
        $primary_button_text = get_setting('styling_primary_button_text');
        $primary_button_hover = get_setting('styling_primary_button_hover');
        $primary_button_hover_text = get_setting('styling_primary_button_hover_text');
        $secondary_button = get_setting('styling_secondary_button');
        $secondary_button_text = get_setting('styling_secondary_button_text');
        $secondary_button_border = get_setting('styling_secondary_button_border');
        $secondary_button_hover = get_setting('styling_secondary_button_hover');
        $secondary_button_hover_text = get_setting('styling_secondary_button_hover_text');
        $secondary_button_hover_border = get_setting('styling_secondary_button_hover_border');
        $card_background = get_setting('styling_card_background');
        $focus_border = get_setting('styling_focus_border_color');
        $primary_shadow = get_setting('styling_primary_shadow');
        $focus_shadow = get_setting('styling_focus_shadow_color');
        $link_color = get_setting('styling_link_color');
        $border_radius = get_setting('styling_border_radius');
        $grid_border_radius = get_setting('styling_grid_border_radius');
        
        // Only generate CSS if we have colors set that differ from defaults
        $has_custom_colors = false;
        foreach ($this->color_settings as $key => $label) {
            $new_key = 'styling_' . str_replace('-', '_', $key);
            $value = get_setting($new_key);
            $default = $this->get_default_value($key);
            if (!empty($value) && $value !== $default) {
                $has_custom_colors = true;
                break;
            }
        }
        
        if (!$has_custom_colors && empty($border_radius) && empty($grid_border_radius)) {
            return '';
        }
        
        $css .= "\n/* FCRM Enhancement Suite - Style Overrides */\n";
        $css .= "/* Generated: " . date('Y-m-d H:i:s') . " */\n";
        
        // Primary button styles with maximum specificity
        if (!empty($primary_button) || !empty($primary_button_text)) {
            $css .= "html body .firehawk-tributes .btn-primary.submit-btn,\n";
            $css .= "html body .fcrm-search-bar .btn.btn-primary.submit-btn {\n";
            if (!empty($primary_button)) {
                $css .= "    background-color: {$primary_button} !important;\n";
                $css .= "    border-color: {$primary_button} !important;\n";
            }
            if (!empty($primary_button_text)) {
                $css .= "    color: {$primary_button_text} !important;\n";
            }
            $css .= "}\n\n";
        }
        
        // Primary button hover with maximum specificity
        if (!empty($primary_button_hover) || !empty($primary_button_hover_text)) {
            $css .= "html body .firehawk-tributes .btn-primary.submit-btn:hover,\n";
            $css .= "html body .fcrm-search-bar .btn.btn-primary.submit-btn:hover {\n";
            if (!empty($primary_button_hover)) {
                $css .= "    background-color: {$primary_button_hover} !important;\n";
                $css .= "    border-color: {$primary_button_hover} !important;\n";
            }
            if (!empty($primary_button_hover_text)) {
                $css .= "    color: {$primary_button_hover_text} !important;\n";
            }
            $css .= "}\n\n";
        }
        
        // Secondary button styles
        if (!empty($secondary_button) || !empty($secondary_button_text) || !empty($secondary_button_border)) {
            $css .= "html body .firehawk-tributes .btn-secondary,\n";
            $css .= "html body .firehawk-tributes .nav-link {\n";
            if (!empty($secondary_button)) {
                $css .= "    background-color: {$secondary_button} !important;\n";
            }
            if (!empty($secondary_button_text)) {
                $css .= "    color: {$secondary_button_text} !important;\n";
            }
            if (!empty($secondary_button_border)) {
                $css .= "    border-color: {$secondary_button_border} !important;\n";
            }
            $css .= "}\n\n";
        }
        
        // Secondary button hover
        if (!empty($secondary_button_hover) || !empty($secondary_button_hover_text) || !empty($secondary_button_hover_border)) {
            $css .= "html body .firehawk-tributes .btn-secondary:hover,\n";
            $css .= "html body .firehawk-tributes .nav-link:hover {\n";
            if (!empty($secondary_button_hover)) {
                $css .= "    background-color: {$secondary_button_hover} !important;\n";
            }
            if (!empty($secondary_button_hover_text)) {
                $css .= "    color: {$secondary_button_hover_text} !important;\n";
            }
            if (!empty($secondary_button_hover_border)) {
                $css .= "    border-color: {$secondary_button_hover_border} !important;\n";
            }
            $css .= "}\n\n";
        }
        
        // Grid card styles with maximum specificity
        if (!empty($card_background) || !empty($focus_border) || !empty($primary_shadow)) {
            $css .= "html body .firehawk-crm.firehawk-crm-large-grid .grid-item,\n";
            $css .= "html body .firehawk-tributes .tribute-card {\n";
            if (!empty($card_background)) {
                $css .= "    background-color: {$card_background} !important;\n";
            }
            if (!empty($focus_border)) {
                $css .= "    border-color: {$focus_border} !important;\n";
            }
            if (!empty($primary_shadow)) {
                $css .= "    box-shadow: 0 2px 10px {$primary_shadow} !important;\n";
            }
            $css .= "}\n\n";
        }
        
        // Focus and hover effects
        if (!empty($focus_shadow)) {
            $css .= "html body .firehawk-crm.firehawk-crm-large-grid .grid-item:hover,\n";
            $css .= "html body .firehawk-crm.firehawk-crm-large-grid .grid-item:focus {\n";
            $css .= "    box-shadow: 0 4px 20px {$focus_shadow} !important;\n";
            $css .= "}\n\n";
        }
        
        // Link colors
        if (!empty($link_color)) {
            $css .= "html body .firehawk-tributes a,\n";
            $css .= "html body .firehawk-tributes .tribute-link {\n";
            $css .= "    color: {$link_color} !important;\n";
            $css .= "}\n\n";
        }
        
        // Border radius with maximum specificity
        if (!empty($border_radius)) {
            $css .= "html body .firehawk-crm-tributes button,\n";
            $css .= "html body .firehawk-crm-tributes .nav-link,\n";
            $css .= "html body .fcrm-search-bar .btn.btn-primary.submit-btn {\n";
            $css .= "    border-radius: {$border_radius} !important;\n";
            $css .= "}\n\n";
        }
        
        if (!empty($grid_border_radius)) {
            $css .= "html body .firehawk-crm.firehawk-crm-large-grid .grid-item,\n";
            $css .= "html body .firehawk-tributes .tribute-card {\n";
            $css .= "    border-radius: {$grid_border_radius} !important;\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }

    /**
     * Handle reset colors functionality
     */
    public function handle_reset_colors(): void {
        // Reset now handled by React settings app via REST API.
    }
}