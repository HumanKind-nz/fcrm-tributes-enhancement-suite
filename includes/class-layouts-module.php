<?php
/**
 * FCRM Layouts Module
 * 
 * Provides modern layout designs and templates for tribute pages by overriding FCRM shortcodes
 * 
 * @package FCRM_Enhancement_Suite
 * @subpackage Layouts
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCRM_Layouts_Module {
    
    /**
     * Available layout options
     */
    protected $available_layouts = [
        'firehawk' => 'Default FCRM Layout',
        'modern-grid' => 'Modern Grid Layout',
        'elegant-grid' => 'Elegant Grid Layout',
        'minimal' => 'List View Layout',
        'gallery-grid' => 'Gallery Grid Layout'
    ];

    /**
     * Available single tribute layout options
     */
    protected $available_single_layouts = [
        'default' => 'Default FCRM Single Layout',
        'enhanced-classic' => 'Enhanced Classic (Subtle Modern Touches)',
        'modern-hero' => 'Modern Hero Layout'
    ];

    /**
     * Module settings
     */
    protected $settings = [
        'fcrm_active_layout' => 'firehawk',
        'fcrm_active_single_layout' => 'default',
        'fcrm_layout_grid_columns' => '3',
        'fcrm_layout_card_style' => 'standard',
        'fcrm_layout_header_style' => 'standard',
        'fcrm_layout_sidebar_enabled' => false,
        'fcrm_layout_responsive_breakpoints' => true
    ];

    public function __construct() {
        // Always register admin settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Only initialize frontend functionality if module is enabled
        if ($this->is_enabled()) {
            $this->init_shortcode_overrides();
            $this->disable_styling_module();
            
            // Enqueue assets for layouts - but with better conditional logic
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 10);
            
            // NOTE: We don't register AJAX handlers - we use FCRM's original handlers
        }
    }

    /**
     * Check if layouts module is enabled
     */
    public function is_enabled(): bool {
        return (bool) get_option('fcrm_module_layouts_enabled', false);
    }

    /**
     * Initialize shortcode overrides
     */
    private function init_shortcode_overrides(): void {
        // Override based on both grid and single layout settings
        $active_layout = get_option('fcrm_active_layout', 'firehawk');
        $active_single_layout = get_option('fcrm_active_single_layout', 'default');

        // Override if either grid layout or single layout is custom
        if ($active_layout !== 'firehawk' || $active_single_layout !== 'default') {
            // Simple check - just ensure FCRM is available since we use their JS system
            if (class_exists('Fcrm_Tributes_Api')) {
                add_action('init', [$this, 'override_fcrm_shortcodes'], 20);
            } else {
                // Note: Dependency warnings are now handled centrally by the main plugin class
            }
        }
    }

    /**
     * Override FCRM shortcodes with our modern layouts
     */
    public function override_fcrm_shortcodes(): void {
        $active_layout = get_option('fcrm_active_layout', 'firehawk');
        $active_single_layout = get_option('fcrm_active_single_layout', 'default');

        // Only override grid shortcode if grid layout is custom
        if ($active_layout !== 'firehawk') {
            remove_shortcode('show_crm_tributes_grid');
            add_shortcode('show_crm_tributes_grid', [$this, 'render_modern_tributes_grid']);
        }

        // Only override single tribute shortcode if single layout is custom
        if ($active_single_layout !== 'default') {
            remove_shortcode('show_crm_tribute');
            add_shortcode('show_crm_tribute', [$this, 'render_modern_single_tribute']);
        }
    }

    /**
     * Disable styling module when layouts are enabled to prevent conflicts
     */
    private function disable_styling_module(): void {
        // Only disable styling module if we're actually using a custom layout
        $active_layout = get_option('fcrm_active_layout', 'firehawk');
        
        if ($active_layout !== 'firehawk' && get_option('fcrm_module_styling_enabled', false)) {
            update_option('fcrm_module_styling_enabled', false);
            
            // Add admin notice about auto-disable
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>FCRM Enhancement Suite:</strong> Styling module has been automatically disabled because a custom layout is active. When using default layout, you can re-enable the Styling module.</p>';
                echo '</div>';
            });
        }
    }

    /**
     * Render modern tributes grid
     */
    public function render_modern_tributes_grid($atts): string {
        // Simple test to see if this method is being called
        if (current_user_can('manage_options')) {
        }
        
        $attributes = shortcode_atts([
            'name-format' => null,
            'detail-page' => null,
            'layout' => 'basic',
            'limit' => 6,
            'image-style' => 'basic',
            'click-action' => 'open',
            'from-today' => null,
            'range-months' => null,
            'range-days' => null,
            'range-hours' => null,
            'team-index' => null,
            'size' => get_option('fcrm_layout_default_page_size', 12),
            'sort-by-service' => false,
            'display-service' => true,
            'display-branch' => false,
            'hide-dob' => false
        ], $atts);


        // Process attributes the same way FCRM does
        $team = get_option('fcrm_team');
        $size = intval($attributes['size'] ?? 12);
        $sortByService = $attributes['sort-by-service'] === true || $attributes['sort-by-service'] === 'true';
        $nameFormat = $attributes['name-format'];
        $displayServiceInfo = $attributes['display-service'] === true || $attributes['display-service'] === 'true';
        $hideDateOfBirth = $attributes['hide-dob'] === true || $attributes['hide-dob'] === 'true';
        $dateFormat = "";
        $filterDateFormat = "";
        $showFutureTributes = null;
        $showPastTributes = null;
        $branch = $attributes['branch'] ?? null;
        $displayBranch = $attributes['display-branch'] === true || $attributes['display-branch'] === 'true';
        $teamGroupIndex = $attributes['team-index'] ?? null;

        // Check global settings
        if (get_option('fcrm_tributes_hide_dob') == true) {
            $hideDateOfBirth = true;
        }


        // Use template approach like FCRM does
        ob_start();
        
        // Get the active layout setting
        $active_layout = get_option('fcrm_active_layout', 'modern-grid');
        
        // Include our template
        $template_file = $this->get_template_file($active_layout);
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            // Fallback to modern-grid template
            $template_file = $this->get_template_file('modern-grid');
            if (file_exists($template_file)) {
                include $template_file;
            } else {
                echo '<div class="fcrm-error">Layout template not found. Falling back to FCRM.</div>';
                return $this->fallback_to_fcrm_grid($atts);
            }
        }
        
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Render modern single tribute
     * 
     * HYBRID APPROACH IMPLEMENTATION:
     * 
     * This preserves ALL FCRM functionality while modernising visual presentation:
     * - Keeps fcrm-tributes-page.js for messaging
     * - Maintains fcrm-tributes-messages.js for interactions  
     * - Preserves authentication and service buttons
     * - Keeps all AJAX endpoints and data flow
     * - Maintains CSS classes/IDs for JavaScript compatibility
     * - Adds modern styling on top of functional structure
     */
    public function render_modern_single_tribute($atts): string {

        try {
            // Debugging for single tribute rendering
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FCRM_ES] render_modern_single_tribute start');
            }

            // Check if single tribute layouts are enabled (not default)
            $active_single_layout = get_option('fcrm_active_single_layout', 'default');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FCRM_ES] active_single_layout=' . $active_single_layout);
            }


            if ($active_single_layout === 'default') {
                // Use original FCRM single tribute
                return $this->fallback_to_fcrm_single($atts);
            }


        // Process attributes the same way FCRM does
        $attributes = shortcode_atts([
            'id' => null,
            'name-format' => null,
            'detail-page' => null,
            'image-style' => 'basic',
            'click-action' => 'open',
            'display-service' => true,
            'display-branch' => false,
            'hide-dob' => false
        ], $atts);

        // Use template approach with hybrid strategy
        ob_start();

        // Include our modern single tribute template
        $template_file = $this->get_single_template_file($active_single_layout);

        if (file_exists($template_file)) {
            // Pass all necessary variables to template
            // Check for tribute ID from shortcode attributes or URL
            $tribute_id = $attributes['id'];
            if (empty($tribute_id)) {
                // If no ID in shortcode, try to get from URL fixer
                if (class_exists('FCRM\\EnhancementSuite\\Tribute_URL_Fixer')) {
                    $tribute_id = FCRM\EnhancementSuite\Tribute_URL_Fixer::get_current_tribute_id();
                }
                // Also check $_GET directly as a fallback
                if (empty($tribute_id) && !empty($_GET['id'])) {
                    $tribute_id = $_GET['id'];
                }
            }


            // Update attributes with the resolved tribute ID
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FCRM_ES] resolved tribute_id=' . ($tribute_id ?? 'null'));
            }
            $attributes['id'] = $tribute_id;

            $nameFormat = $attributes['name-format'];
            $displayServiceInfo = $attributes['display-service'] === true || $attributes['display-service'] === 'true';
            $hideDateOfBirth = $attributes['hide-dob'] === true || $attributes['hide-dob'] === 'true';

            // Make $attributes available to template
            // Note: $attributes is already in scope for the template
            
            include $template_file;
        } else {
            // Fallback to default if template not found
            ob_end_clean();
            return $this->fallback_to_fcrm_single($atts);
        }

        $content = ob_get_clean();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FCRM_ES] render_modern_single_tribute complete, length=' . strlen($content));
        }
        return $content;

        } catch (Exception $e) {
            // Log error and fallback to FCRM
            error_log('FCRM Enhancement Suite - Single tribute render error: ' . $e->getMessage());

            // Clean any output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }

            return $this->fallback_to_fcrm_single($atts);
        }
    }

    /**
     * Render modern layout template
     */
    private function render_modern_layout(array $clients, array $attributes): string {
        $active_layout = get_option('fcrm_active_layout', 'modern-grid');
        
        // Prepare template variables
        $template_vars = [
            'clients' => $clients,
            'attributes' => $attributes,
            'layout' => $active_layout,
            'grid_columns' => get_option('fcrm_layout_grid_columns', '3'),
            'card_style' => get_option('fcrm_layout_card_style', 'standard'),
            'header_style' => get_option('fcrm_layout_header_style', 'standard'),
            'sidebar_enabled' => get_option('fcrm_layout_sidebar_enabled', false),
            'responsive_breakpoints' => get_option('fcrm_layout_responsive_breakpoints', true),
            'fcrmDefaultImageUrl' => get_option('fcrm_tributes_default_image'),
            'fcrmShowLocation' => $this->should_show_location()
        ];

        // Start output buffering
        ob_start();
        
        // Load the appropriate template
        $template_file = $this->get_template_file($active_layout);
        
        if (file_exists($template_file)) {
            // Extract variables for template
            extract($template_vars);
            include $template_file;
        } else {
            // Fallback to basic template if specific layout not found
            $fallback_template = $this->get_template_file('modern-grid');
            if (file_exists($fallback_template)) {
                extract($template_vars);
                include $fallback_template;
            } else {
                echo '<div class="fcrm-error">Template file not found: ' . $template_file . '</div>';
            }
        }
        
        $result = ob_get_clean();
        return $result;
    }

    /**
     * Get template file path for layout
     */
    private function get_template_file(string $layout): string {
        $template_dir = FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'templates/layouts/';
        return $template_dir . $layout . '.php';
    }

    /**
     * Get single tribute template file path
     */
    private function get_single_template_file(string $layout): string {
        $template_dir = FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'templates/layouts/single/';
        return $template_dir . $layout . '.php';
    }

    /**
     * Get detail page for permalinks
     */
    private function get_detail_page(array $attributes): ?string {
        $detailPage = $attributes['detail-page'] ?? null;
        
        if (!$detailPage) {
            // Use FCRM's method to get tribute page slug
            if (class_exists('Fcrm_Tributes_Public')) {
                $detailPage = Fcrm_Tributes_Public::getTributePageSlug();
            }
        }
        
        return $detailPage;
    }

    /**
     * Format client name (same as FCRM)
     */
    private function format_client_name($client, $format): string {
        $names = [];
        
        if ($format == "display") {
            if (isset($client->firstName)) $names[] = $client->firstName;
            if (isset($client->otherNames)) $names[] = $client->otherNames;
            if (isset($client->lastName)) $names[] = $client->lastName;
            if (isset($client->suffix)) $names[] = $client->suffix;
            return join(" ", $names);
        } else if ($format == "display-full") {
            if (isset($client->firstName)) $names[] = $client->firstName;
            if (isset($client->knownAs)) $names[] = "\"".$client->knownAs."\"";
            if (isset($client->otherNames)) $names[] = $client->otherNames;
            if (isset($client->lastName)) $names[] = $client->lastName;
            if (isset($client->suffix)) $names[] = $client->suffix;
            if (isset($client->postNominalName)) $names[] = $client->postNominalName;
            return join(" ", $names);
        } else {
            if (isset($client->firstName)) $names[] = $client->firstName;
            if (isset($client->otherNames)) $names[] = $client->otherNames;
            if (isset($client->lastName)) $names[] = $client->lastName;
            return join(" ", $names);
        }
    }

    /**
     * Format client permalink (same as FCRM)
     */
    private function format_client_permalink($client, $detailPage): ?string {
        if (!$detailPage || !isset($client->id)) {
            return null;
        }
        
        return $detailPage . '/' . $client->id;
    }

    /**
     * Check if location should be shown
     */
    private function should_show_location(): bool {
        $fcrmShowLocation = get_option('fcrm_tributes_show_location');
        
        if (empty($fcrmShowLocation)) {
            return false;
        } else if ($fcrmShowLocation == false || $fcrmShowLocation == '1') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Fallback to original FCRM grid shortcode
     */
    private function fallback_to_fcrm_grid($atts): string {
        if (class_exists('Fcrm_Tributes_Public')) {
            $fcrm_public = new Fcrm_Tributes_Public('fcrm-tributes', '2.2.0');
            return $fcrm_public->shortcode_crm_tributes_grid_display($atts);
        }
        
        return '<div class="fcrm-error">FCRM Tributes plugin not found.</div>';
    }

    /**
     * Fallback to original FCRM single shortcode
     */
    private function fallback_to_fcrm_single($atts): string {
        if (class_exists('Fcrm_Tributes_Public')) {
            $fcrm_public = new Fcrm_Tributes_Public('fcrm-tributes', '2.2.0');
            return $fcrm_public->shortcode_crm_tribute_display($atts);
        }
        
        return '<div class="fcrm-error">FCRM Tributes plugin not found.</div>';
    }

    /**
     * Enqueue frontend assets for layouts
     */
    public function enqueue_assets(): void {
        // Double-check that module should be loading assets
        if (!$this->is_enabled()) {
            return;
        }
        
        $is_tribute_page = $this->is_tribute_page();
        
        if (!$is_tribute_page) {
            return;
        }

        $active_layout = get_option('fcrm_active_layout', 'firehawk');
        $active_single_layout = get_option('fcrm_active_single_layout', 'default');
        
        // Base shared styles
        if ($active_layout !== 'firehawk' || $active_single_layout !== 'default') {
            wp_enqueue_style(
                'fcrm-layouts-shared',
                FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/layouts/shared-base.css',
                [],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        // Loading spinner (always enqueue on tribute pages)
        wp_enqueue_style(
            'fcrm-loading-spinner',
            FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/frontend/loading-spinner.css',
            [],
            FCRM_ENHANCEMENT_SUITE_VERSION
        );

        wp_enqueue_script(
            'fcrm-loading-spinner',
            FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/js/frontend/loading-spinner.js',
            ['jquery'],
            FCRM_ENHANCEMENT_SUITE_VERSION,
            true
        );

        // Grid layout styles
        if ($active_layout === 'modern-grid') {
            wp_enqueue_style(
                'fcrm-modern-grid',
                FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/layouts/modern-grid.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        if ($active_layout === 'elegant-grid') {
            wp_enqueue_style(
                'fcrm-elegant-grid',
                FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/layouts/elegant-grid.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        if ($active_layout === 'gallery-grid') {
            wp_enqueue_style(
                'fcrm-gallery-grid',
                FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/layouts/gallery-grid.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        if ($active_layout === 'minimal') {
            wp_enqueue_style(
                'fcrm-minimal',
                FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/layouts/minimal.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        // Single tribute layout styles
        if ($active_single_layout === 'enhanced-classic') {
            wp_enqueue_style(
                'fcrm-enhanced-classic',
                FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/layouts/enhanced-classic.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        if ($active_single_layout === 'modern-hero') {
            wp_enqueue_style(
                'fcrm-modern-hero',
                FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/layouts/modern-hero.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        // Enqueue JavaScript for layouts with AJAX functionality (if not default layout)
        if ($active_layout !== 'firehawk') {
            // Enqueue unified search component for all layouts
            wp_enqueue_script(
                'fcrm-unified-search',
                FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/js/frontend/unified-search.js',
                ['jquery'],
                FCRM_ENHANCEMENT_SUITE_VERSION,
                true
            );
            
            // NOTE: We rely on FCRM's own AJAX variable localization (ajax_var)
            // No need to localize our own variables since we use FCRM's handlers
        }
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting('fcrm_enhancement_layouts', 'fcrm_active_layout');
        register_setting('fcrm_enhancement_layouts', 'fcrm_active_single_layout');
        register_setting('fcrm_enhancement_layouts', 'fcrm_layout_grid_columns');
        register_setting('fcrm_enhancement_layouts', 'fcrm_layout_card_style');
        register_setting('fcrm_enhancement_layouts', 'fcrm_layout_header_style');
        register_setting('fcrm_enhancement_layouts', 'fcrm_layout_sidebar_enabled');
        register_setting('fcrm_enhancement_layouts', 'fcrm_layout_responsive_breakpoints');
        register_setting('fcrm_enhancement_layouts', 'fcrm_layout_default_page_size');
        register_setting('fcrm_enhancement_layouts', 'fcrm_layout_load_more_size');
    }

    /**
     * Render admin settings
     */
    public function render_settings(): void {
        ?>
        <div class="settings-section">
            <h3>🎨 Grid Layout</h3>
            <div class="section-content">
                <p><strong>Choose layout design for tribute grids and search results pages.</strong></p>
                <table class="form-table">
                    <tr>
                        <th scope="row">Grid Layout (Tribute Lists)</th>
                        <td>
                            <select name="fcrm_active_layout">
                                <?php foreach ($this->available_layouts as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected(get_option('fcrm_active_layout', 'firehawk'), $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Layout for tribute grids and search results pages.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Card Style</th>
                        <td>
                            <select name="fcrm_layout_card_style">
                                <option value="standard" <?php selected(get_option('fcrm_layout_card_style', 'standard'), 'standard'); ?>>Standard Cards</option>
                                <option value="elevated" <?php selected(get_option('fcrm_layout_card_style'), 'elevated'); ?>>Elevated Cards</option>
                                <option value="outlined" <?php selected(get_option('fcrm_layout_card_style'), 'outlined'); ?>>Outlined Cards</option>
                                <option value="minimal" <?php selected(get_option('fcrm_layout_card_style'), 'minimal'); ?>>Minimal Cards</option>
                            </select>
                            <p class="description">Visual style for card-based layouts.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Grid Columns</th>
                        <td>
                            <select name="fcrm_layout_grid_columns">
                                <option value="3" <?php selected(get_option('fcrm_layout_grid_columns', '3'), '3'); ?>>3 Columns</option>
                                <option value="4" <?php selected(get_option('fcrm_layout_grid_columns'), '4'); ?>>4 Columns</option>
                            </select>
                            <p class="description">Number of columns for grid-based layouts.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Items Per Page</th>
                        <td>
                            <input type="number" 
                                   id="fcrm_layout_default_page_size" 
                                   name="fcrm_layout_default_page_size" 
                                   value="<?php echo esc_attr(get_option('fcrm_layout_default_page_size', 12)); ?>"
                                   min="1" 
                                   max="50" 
                                   class="small-text" />
                            <p class="description">
                                Default number of tributes to show per page before "Load More" button appears. 
                                Can be overridden per shortcode with <code>size="X"</code> attribute.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">"Load More" Page Size</th>
                        <td>
                            <input type="number" 
                                   id="fcrm_layout_load_more_size" 
                                   name="fcrm_layout_load_more_size" 
                                   value="<?php echo esc_attr(get_option('fcrm_layout_load_more_size', 8)); ?>"
                                   min="1" 
                                   max="50" 
                                   class="small-text" />
                            <p class="description">
                                Number of additional tributes to load when "Load More" button is clicked. 
                                Usually smaller than initial page size for better performance.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="alert alert-info" style="margin-top: 1rem;">
                    <strong>Styling & Colours:</strong> To customise colours, fonts, and visual styling for your layouts, please visit the <a href="<?php echo admin_url('admin.php?page=fcrm-enhancements&tab=ui_styling'); ?>">UI Styling module</a>.
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>👤 Single Tribute Layout</h3>
            <div class="section-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">Single Tribute Layout</th>
                        <td>
                            <select name="fcrm_active_single_layout">
                                <?php foreach ($this->available_single_layouts as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected(get_option('fcrm_active_single_layout', 'default'), $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Layout for individual tribute detail pages with messaging and service information.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="settings-section">
            <h3>🖼️ Layout Previews</h3>
            <div class="section-content">
                <div class="layout-previews-grid" style="margin-top: 1rem;">
                    
                    <div class="layout-preview" style="border: 1px solid #ddd; border-radius: 8px; padding: 0.75rem; text-align: center;">
                        <div style="background: #f5f5f5; height: 300px; border-radius: 4px; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #666; background-image: url('<?php echo FCRM_ENHANCEMENT_SUITE_PLUGIN_URL; ?>assets/images/layout-previews/firehawk.png'); background-size: contain; background-repeat: no-repeat; background-position: center;">
                            <?php if (!file_exists(FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'assets/images/layout-previews/firehawk.png')): ?>
                                Default FCRM Preview
                            <?php endif; ?>
                        </div>
                        <h4 style="margin: 0.5rem 0; font-size: 0.9rem;">Default FCRM Layout</h4>
                        <p style="font-size: 0.8rem; color: #666; margin: 0;">Original FireHawk tribute layout</p>
                    </div>

                    <div class="layout-preview" style="border: 1px solid #ddd; border-radius: 8px; padding: 0.75rem; text-align: center;">
                        <div style="background: #f5f5f5; height: 300px; border-radius: 4px; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #666; background-image: url('<?php echo FCRM_ENHANCEMENT_SUITE_PLUGIN_URL; ?>assets/images/layout-previews/modern.png'); background-size: contain; background-repeat: no-repeat; background-position: center;">
                            <?php if (!file_exists(FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'assets/images/layout-previews/modern.png')): ?>
                                Modern Grid Preview
                            <?php endif; ?>
                        </div>
                        <h4 style="margin: 0.5rem 0; font-size: 0.9rem;">Modern Grid Layout</h4>
                        <p style="font-size: 0.8rem; color: #666; margin: 0;">Clean card-based grid with modern spacing</p>
                    </div>

                    <div class="layout-preview" style="border: 1px solid #ddd; border-radius: 8px; padding: 0.75rem; text-align: center;">
                        <div style="background: #f5f5f5; height: 300px; border-radius: 4px; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #666; background-image: url('<?php echo FCRM_ENHANCEMENT_SUITE_PLUGIN_URL; ?>assets/images/layout-previews/elegant.png'); background-size: contain; background-repeat: no-repeat; background-position: center;">
                            <?php if (!file_exists(FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'assets/images/layout-previews/elegant.png')): ?>
                                Elegant Grid Preview
                            <?php endif; ?>
                        </div>
                        <h4 style="margin: 0.5rem 0; font-size: 0.9rem;">Elegant Grid Layout</h4>
                        <p style="font-size: 0.8rem; color: #666; margin: 0;">Sophisticated design with refined styling</p>
                    </div>

                    <div class="layout-preview" style="border: 1px solid #ddd; border-radius: 8px; padding: 0.75rem; text-align: center;">
                        <div style="background: #f5f5f5; height: 300px; border-radius: 4px; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #666; background-image: url('<?php echo FCRM_ENHANCEMENT_SUITE_PLUGIN_URL; ?>assets/images/layout-previews/list.png'); background-size: contain; background-repeat: no-repeat; background-position: center;">
                            <?php if (!file_exists(FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'assets/images/layout-previews/list.png')): ?>
                                List View Preview
                            <?php endif; ?>
                        </div>
                        <h4 style="margin: 0.5rem 0; font-size: 0.9rem;">List View Layout</h4>
                        <p style="font-size: 0.8rem; color: #666; margin: 0;">Compact list format for easy scanning</p>
                    </div>

                    <div class="layout-preview" style="border: 1px solid #ddd; border-radius: 8px; padding: 0.75rem; text-align: center;">
                        <div style="background: #f5f5f5; height: 300px; border-radius: 4px; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #666; background-image: url('<?php echo FCRM_ENHANCEMENT_SUITE_PLUGIN_URL; ?>assets/images/layout-previews/gallery.png'); background-size: contain; background-repeat: no-repeat; background-position: center;">
                            <?php if (!file_exists(FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'assets/images/layout-previews/gallery.png')): ?>
                                Gallery Grid Preview
                            <?php endif; ?>
                        </div>
                        <h4 style="margin: 0.5rem 0; font-size: 0.9rem;">Gallery Grid Layout</h4>
                        <p style="font-size: 0.8rem; color: #666; margin: 0;">Image-focused gallery presentation</p>
                    </div>

                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Note:</strong> Modern layouts automatically disable the Styling module to prevent conflicts. Layout-specific styling is handled by individual CSS files for optimal performance.
        </div>

        <?php
    }

    /**
     * Check if current page is tribute-related
     */
    private function is_tribute_page(): bool {
        // Use the standardised tribute page detection from main plugin
        return FCRM_Enhancement_Suite::is_tribute_page();
    }
} 