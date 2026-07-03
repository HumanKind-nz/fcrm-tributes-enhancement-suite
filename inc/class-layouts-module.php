<?php
declare(strict_types=1);

/**
 * FCRM Layouts Module
 * 
 * Provides modern layout designs and templates for tribute pages by overriding FCRM shortcodes
 * 
 * @package FcrmEnhancementSuite
 * @subpackage Layouts
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use function FcrmEnhancementSuite\Helpers\get_setting;
use function FcrmEnhancementSuite\Helpers\get_settings;
use const FcrmEnhancementSuite\Helpers\OPTION_NAME;

class FCRM_Layouts_Module {
    
    /**
     * Available layout options
     */
    protected $available_layouts = [
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
        // 'modern-hero' is incomplete (hero banner only, no tribute body) — hidden
        // from the UI until finished. Template retained at templates/layouts/single/modern-hero.php.
    ];

    /**
     * Module settings
     */
    protected $settings = [
        'fcrm_active_layout' => 'modern-grid',
        'fcrm_active_single_layout' => 'enhanced-classic',
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

            // Enqueue assets for layouts - but with better conditional logic
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 10);
            
            // NOTE: We don't register AJAX handlers - we use FCRM's original handlers
        }
    }

    /**
     * Check if layouts module is enabled
     */
    public function is_enabled(): bool {
        return 'modern' === \FcrmEnhancementSuite\Status\get_layout_mode();
    }

    /**
     * Initialize shortcode overrides
     */
    private function init_shortcode_overrides(): void {
        // Override based on both grid and single layout settings
        $active_layout = get_setting('active_layout', 'modern-grid');
        $active_single_layout = get_setting('active_single_layout', 'enhanced-classic');

        // Always override if layouts module is enabled (no more 'firehawk' option)
        // Simple check - just ensure FCRM is available since we use their JS system
        if (class_exists('Fcrm_Tributes_Api')) {
            add_action('init', [$this, 'override_fcrm_shortcodes'], 20);
        } else {
            // Note: Dependency warnings are now handled centrally by the main plugin class
        }
    }

    /**
     * Override FCRM shortcodes with our modern layouts
     */
    public function override_fcrm_shortcodes(): void {
        $active_layout = get_setting('active_layout', 'modern-grid');
        $active_single_layout = get_setting('active_single_layout', 'enhanced-classic');

        // Always override grid shortcode (no 'firehawk' option anymore)
        remove_shortcode('show_crm_tributes_grid');
        add_shortcode('show_crm_tributes_grid', [$this, 'render_modern_tributes_grid']);

        // Always override single tribute shortcode (always use enhanced layouts)
        remove_shortcode('show_crm_tribute');
        add_shortcode('show_crm_tribute', [$this, 'render_modern_single_tribute']);
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
            'layout' => get_setting('active_layout', 'modern-grid'), // Use global setting as default
            'limit' => 6,
            'image-style' => 'basic',
            'click-action' => 'open',
            'from-today' => null,
            'range-months' => null,
            'range-days' => null,
            'range-hours' => null,
            'team-index' => null,
            'size' => get_setting('layout_default_page_size', 12),
            'sort-by-service' => false,
            'display-service' => true,
            'display-branch' => false,
            'hide-dob' => false,
            'search' => null
        ], $atts);

        // Check if explicitly requesting FireHawk passthrough layout (shortcode-only, for demos/testing)
        if ($attributes['layout'] === 'firehawk') {
            return $this->render_firehawk_passthrough_grid($atts);
        }

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
        $fixedSearch = $attributes['search'] ?? null;

        // Check global settings
        if (get_option('fcrm_tributes_hide_dob') == true) {
            $hideDateOfBirth = true;
        }


        // Use template approach like FCRM does
        ob_start();

        // Use layout from shortcode attribute (overrides global setting)
        $active_layout = $attributes['layout'];

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
     * Render FireHawk passthrough grid layout (for demos/testing)
     *
     * This is a special passthrough mode that allows using layout="firehawk" in shortcodes
     * for side-by-side comparisons and demos. It's NOT available in the admin dropdown -
     * only via explicit shortcode parameter.
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML from FireHawk
     */
    private function render_firehawk_passthrough_grid($atts): string {
        // Temporarily remove our override
        remove_shortcode('show_crm_tributes_grid');

        // Re-register FireHawk's original shortcode
        if (class_exists('Fcrm_Tributes_Public')) {
            $fcrm_public = new Fcrm_Tributes_Public('fcrm-tributes', defined('PLUGIN_NAME_VERSION') ? PLUGIN_NAME_VERSION : '2.3.1');
            add_shortcode('show_crm_tributes_grid', [$fcrm_public, 'shortcode_crm_tributes_grid_display']);
        } else {
            return '<div class="fcrm-error">FireHawk CRM Tributes plugin not found.</div>';
        }

        // Build shortcode string from attributes
        $shortcode = '[show_crm_tributes_grid';
        foreach ($atts as $key => $value) {
            // Exclude 'layout' attribute (no longer needed)
            if ($key !== 'layout' && $value !== null && $value !== '') {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode .= ']';

        // Execute FireHawk's original shortcode
        $output = do_shortcode($shortcode);

        // Restore our override for subsequent shortcodes
        remove_shortcode('show_crm_tributes_grid');
        add_shortcode('show_crm_tributes_grid', [$this, 'render_modern_tributes_grid']);

        return $output;
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

            // Check if single tribute layouts are enabled
            $active_single_layout = get_setting('active_single_layout', 'enhanced-classic');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FCRM_ES] active_single_layout=' . $active_single_layout);
            }

        // Process attributes the same way FCRM does
        $attributes = shortcode_atts([
            'id' => null,
            'name-format' => null,
            'detail-page' => null,
            'layout' => get_setting('active_single_layout', 'enhanced-classic'),
            'image-style' => 'basic',
            'click-action' => 'open',
            'display-service' => true,
            'display-branch' => false,
            'hide-dob' => false
        ], $atts);

        // Use template approach with hybrid strategy
        ob_start();

        // Include our modern single tribute template
        // Use layout from shortcode attribute (overrides global setting)
        $active_single_layout = $attributes['layout'];
        $template_file = $this->get_single_template_file($active_single_layout);

        if (file_exists($template_file)) {
            // Pass all necessary variables to template
            // Check for tribute ID from shortcode attributes or URL
            $tribute_id = $attributes['id'];
            if (empty($tribute_id)) {
                // If no ID in shortcode, try to get from URL fixer
                if (class_exists('FcrmEnhancementSuite\\Tribute_URL_Fixer')) {
                    $tribute_id = FcrmEnhancementSuite\Tribute_URL_Fixer::get_current_tribute_id();
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
        $active_layout = get_setting('active_layout', 'modern-grid');

        // Prepare template variables
        $template_vars = [
            'clients' => $clients,
            'attributes' => $attributes,
            'layout' => $active_layout,
            'grid_columns' => get_setting('layout_grid_columns', '3'),
            'card_style' => get_setting('layout_card_style', 'standard'),
            'header_style' => get_setting('layout_header_style', 'standard'),
            'sidebar_enabled' => get_setting('layout_sidebar_enabled', false),
            'responsive_breakpoints' => get_setting('layout_responsive_breakpoints', true),
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
        $template_dir = FCRM_ENHANCEMENT_SUITE_DIR . 'templates/layouts/';
        return $template_dir . $layout . '.php';
    }

    /**
     * Get single tribute template file path
     */
    private function get_single_template_file(string $layout): string {
        $template_dir = FCRM_ENHANCEMENT_SUITE_DIR . 'templates/layouts/single/';
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

        $active_layout = get_setting('active_layout', 'modern-grid');
        $active_single_layout = get_setting('active_single_layout', 'enhanced-classic');

        // Always enqueue shared styles (no 'firehawk' option anymore)
        wp_enqueue_style(
            'fcrm-layouts-shared',
            FCRM_ENHANCEMENT_SUITE_URL . 'assets/css/layouts/shared-base.css',
            [],
            FCRM_ENHANCEMENT_SUITE_VERSION
        );

        // No spinner enqueued here — the navigation spinner is owned by the
        // always-loaded Optimisation module, which registers it as a dedicated
        // static asset (assets/js/navigation-spinner.js + navigation-spinner.css
        // on the fcrm-navigation-spinner handle). Colour is the only setting.
        // See: class-optimisation-module.php enqueue_frontend_assets().

        // Grid layout styles
        if ($active_layout === 'modern-grid') {
            wp_enqueue_style(
                'fcrm-modern-grid',
                FCRM_ENHANCEMENT_SUITE_URL . 'assets/css/layouts/modern-grid.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        if ($active_layout === 'elegant-grid') {
            wp_enqueue_style(
                'fcrm-elegant-grid',
                FCRM_ENHANCEMENT_SUITE_URL . 'assets/css/layouts/elegant-grid.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        if ($active_layout === 'gallery-grid') {
            wp_enqueue_style(
                'fcrm-gallery-grid',
                FCRM_ENHANCEMENT_SUITE_URL . 'assets/css/layouts/gallery-grid.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        if ($active_layout === 'minimal') {
            wp_enqueue_style(
                'fcrm-minimal',
                FCRM_ENHANCEMENT_SUITE_URL . 'assets/css/layouts/minimal.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        // Single tribute layout styles - ONLY load on single tribute pages (has ?id parameter)
        $is_single_tribute = isset($_GET['id']);

        if ($is_single_tribute && $active_single_layout === 'enhanced-classic') {
            wp_enqueue_style(
                'fcrm-enhanced-classic',
                FCRM_ENHANCEMENT_SUITE_URL . 'assets/css/layouts/enhanced-classic.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        if ($is_single_tribute && $active_single_layout === 'modern-hero') {
            wp_enqueue_style(
                'fcrm-modern-hero',
                FCRM_ENHANCEMENT_SUITE_URL . 'assets/css/layouts/modern-hero.css',
                ['fcrm-layouts-shared'],
                FCRM_ENHANCEMENT_SUITE_VERSION
            );
        }

        // Always enqueue JavaScript for layouts with AJAX functionality
        // Enqueue unified search component for all layouts
        wp_enqueue_script(
            'fcrm-unified-search',
            FCRM_ENHANCEMENT_SUITE_URL . 'assets/js/frontend/unified-search.js',
            ['jquery'],
            FCRM_ENHANCEMENT_SUITE_VERSION,
            true
        );

        // NOTE: We rely on FCRM's own AJAX variable localization (ajax_var)
        // No need to localize our own variables since we use FCRM's handlers
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        // Settings now registered centrally in inc/settings-page.php.
    }

    /**
     * Render admin settings
     */
    public function render_settings(): void {
        // Settings now rendered by React admin UI.
    }

    /**
     * Check if current page is tribute-related
     */
    private function is_tribute_page(): bool {
        // Use the standardised tribute page detection from main plugin
        return \FcrmEnhancementSuite\TributeDetection\is_tribute_page();
    }
} 