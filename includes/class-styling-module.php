<?php
/**
 * FCRM Styling Module
 * 
 * Comprehensive colour and styling overrides for FireHawk Tributes
 * Based on the original styling system with all colour options
 */

if (!defined('ABSPATH')) {
    exit;
}

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
        
        
        // Only load functionality if the module is enabled
        if (!get_option('fcrm_module_styling_enabled', false)) {
            return;
        }
        
        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'handle_reset_colors']);
    }
    

    public function register_settings(): void {
        // Register ALL settings that appear in the form
        $settings_to_register = [
            'fcrm_styling_primary-color',
            'fcrm_styling_secondary-color', 
            'fcrm_styling_primary-button',
            'fcrm_styling_primary-button-text',
            'fcrm_styling_primary-button-hover',
            'fcrm_styling_primary-button-hover-text',
            'fcrm_styling_secondary-button',
            'fcrm_styling_secondary-button-text',
            'fcrm_styling_secondary-button-border',
            'fcrm_styling_secondary-button-hover',
            'fcrm_styling_secondary-button-hover-text',
            'fcrm_styling_secondary-button-hover-border',
            'fcrm_styling_focus-border-color',  // ← This was missing!
            'fcrm_styling_card-background',
            'fcrm_styling_primary-shadow',
            'fcrm_styling_focus-shadow-color',
            'fcrm_styling_link-color',
            'fcrm_styling_border_radius',
            'fcrm_styling_grid_border_radius'
        ];
        
        foreach ($settings_to_register as $setting) {
            register_setting('fcrm_enhancement_styling', $setting);
        }
    }

    /**
     * Get all settings with defaults (like UI module)
     */
    private function get_all_settings(): array {
        $settings = [];
        foreach ($this->color_settings as $key => $label) {
            $option_name = 'fcrm_styling_' . $key;
            $settings[$option_name] = get_option($option_name, $this->get_default_value($key));
        }
        
        // Add border radius settings
        $settings['fcrm_styling_border_radius'] = get_option('fcrm_styling_border_radius', '6px');
        $settings['fcrm_styling_grid_border_radius'] = get_option('fcrm_styling_grid_border_radius', '18px');
        
        
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
        $settings = $this->get_all_settings();
        ?>
        <div class="settings-section">
            <h3>🎨 FireHawk Layout Styling</h3>
            <div class="section-content">
                <p><strong>Customize colors and styles for the original FireHawk Tributes layout.</strong></p>
                <p><em>Note: This module only works when the "Modern Layout Templates" module is disabled. When using modern layouts, please use the "Universal UI Styling" module instead.</em></p>
            </div>
        </div>

        <div class="settings-section">
            <h3>🎨 Colour Settings</h3>
            <div class="section-content">
                <p>Customise every aspect of the FireHawk Tributes colour scheme. All colours support transparency (alpha channel).</p>
                
                <table class="form-table">
                    <?php foreach ($this->color_settings as $key => $label): 
                        $option_name = 'fcrm_styling_' . $key;
                    ?>
                        <tr>
                            <th scope="row"><?php echo $label; ?></th>
                            <td>
                                <input type="text" 
                                    id="<?php echo esc_attr($option_name); ?>"
                                    name="<?php echo esc_attr($option_name); ?>"
                                    value="<?php echo esc_attr($settings[$option_name]); ?>"
                                    class="alpha-color-control"
                                />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="settings-section">
            <h3>📐 Border Radius Settings</h3>
            <div class="section-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">Grid Card Border Radius</th>
                        <td>
                            <input type="text" 
                                id="fcrm_styling_grid_border_radius"
                                name="fcrm_styling_grid_border_radius"
                                value="<?php echo esc_attr($settings['fcrm_styling_grid_border_radius']); ?>"
                                class="regular-text"
                            />
                            <p class="description">Specify the border-radius for grid cards (e.g., 10px, 20px, 0).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Button Border Radius</th>
                        <td>
                            <input type="text" 
                                id="fcrm_styling_border_radius"
                                name="fcrm_styling_border_radius"
                                value="<?php echo esc_attr($settings['fcrm_styling_border_radius']); ?>"
                                class="regular-text"
                            />
                            <p class="description">Specify the border-radius for buttons (e.g., 10px, 20px, 0).</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="info-card">
            <h4>💡 Styling Tips</h4>
            <p>These comprehensive colour overrides allow you to match the FireHawk Tributes exactly to your website's design. All colours support transparency, and changes are applied immediately to tribute pages.</p>
        </div>

        
        <div class="info-card">
            <h4>🔄 Reset Options</h4>
            <p>Reset all colours and styling to the default values.</p>
            <p><em>Note: Reset functionality will be available after saving works properly.</em></p>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook_suffix): void {
        // Check if we're on the FCRM enhancements page
        if (strpos($hook_suffix, 'fcrm-enhancements') === false) {
            return;
        }

        // Only enqueue on styling tab
        $current_tab = $_GET['tab'] ?? 'dashboard';
        if ($current_tab !== 'styling') {
            return;
        }

        // Enqueue WordPress color picker and dependencies
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker', ['jquery'], false, true);
        
        // Initialize color picker with simple script
        wp_add_inline_script(
            'wp-color-picker',
            'jQuery(document).ready(function($) {
                $(".alpha-color-control").wpColorPicker({
                    change: function(event, ui) {
                        $(this).val(ui.color.toString()).trigger("change");
                    },
                    clear: function() {
                        $(this).val("").trigger("change");
                    }
                });
            });',
            'after'
        );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void {
        // Check if this is a tribute page
        if (!$this->is_tribute_page()) {
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
     * Check if current page is tribute-related
     */
    private function is_tribute_page(): bool {
        $is_tribute = false;
        
        // Check for tribute single post type
        if (isset($_GET['id']) && is_singular() && get_post_type() === 'tribute') {
            $is_tribute = true;
        }
        
        // Check if we're on the designated tribute search page
        if (is_page(get_option('fcrm_tributes_search_page_id'))) {
            $is_tribute = true;
        }
        
        // Check for tribute shortcodes
        if ($this->has_tribute_shortcode()) {
            $is_tribute = true;
        }
        
        return $is_tribute;
    }

    /**
     * Check if current post contains tribute shortcodes
     */
    private function has_tribute_shortcode(): bool {
        global $post;
        if (!$post || !is_a($post, 'WP_Post')) {
            return false;
        }

        $shortcodes = [
            'show_crm_tribute',
            'show_crm_tributes_grid',
            'show_crm_tributes_large_grid',
            'show_crm_tributes_carousel',
            'show_crm_tribute_search',
            'show_crm_tribute_search_bar'
        ];

        $pattern = get_shortcode_regex($shortcodes);
        return preg_match('/' . $pattern . '/', $post->post_content);
    }

    /**
     * Generate direct CSS with actual color values and maximum specificity
     */
    private function generate_direct_css(): string {
        $css = '';
        
        // Get all the color values
        $primary_button = get_option('fcrm_styling_primary-button');
        $primary_button_text = get_option('fcrm_styling_primary-button-text');
        $primary_button_hover = get_option('fcrm_styling_primary-button-hover');
        $primary_button_hover_text = get_option('fcrm_styling_primary-button-hover-text');
        $secondary_button = get_option('fcrm_styling_secondary-button');
        $secondary_button_text = get_option('fcrm_styling_secondary-button-text');
        $secondary_button_border = get_option('fcrm_styling_secondary-button-border');
        $secondary_button_hover = get_option('fcrm_styling_secondary-button-hover');
        $secondary_button_hover_text = get_option('fcrm_styling_secondary-button-hover-text');
        $secondary_button_hover_border = get_option('fcrm_styling_secondary-button-hover-border');
        $card_background = get_option('fcrm_styling_card-background');
        $focus_border = get_option('fcrm_styling_focus-border-color');
        $primary_shadow = get_option('fcrm_styling_primary-shadow');
        $focus_shadow = get_option('fcrm_styling_focus-shadow-color');
        $link_color = get_option('fcrm_styling_link-color');
        $border_radius = get_option('fcrm_styling_border_radius');
        $grid_border_radius = get_option('fcrm_styling_grid_border_radius');
        
        // Only generate CSS if we have colors set that differ from defaults
        $has_custom_colors = false;
        foreach ($this->color_settings as $key => $label) {
            $value = get_option('fcrm_styling_' . $key);
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
        if (!isset($_POST['fcrm_reset_colors_nonce']) || !wp_verify_nonce($_POST['fcrm_reset_colors_nonce'], 'fcrm_reset_colors')) {
            return;
        }
        
        if (isset($_POST['fcrm_reset_colors']) && $_POST['fcrm_reset_colors'] === '1') {
            // Reset all colour settings to defaults
            foreach ($this->color_settings as $key => $label) {
                update_option('fcrm_styling_' . $key, $this->get_default_value($key));
            }
            
            // Reset border radius settings
            update_option('fcrm_styling_border_radius', '6px');
            update_option('fcrm_styling_grid_border_radius', '18px');
            
            // Redirect back to the styling tab with a success message
            wp_redirect(add_query_arg([
                'page' => 'fcrm-enhancements', 
                'tab' => 'styling', 
                'colors_reset' => 'true'
            ], admin_url('admin.php')));
            exit;
        }
    }
}