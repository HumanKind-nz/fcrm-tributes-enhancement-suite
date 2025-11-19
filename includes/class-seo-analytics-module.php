<?php
/**
 * SEO & Analytics Module
 *
 * Handles SEOPress integration and Plausible Analytics integration
 * for FCRM Tributes enhancement.
 *
 * @package FCRM_Enhancement_Suite
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FCRM_SEO_Analytics_Module
 */
class FCRM_SEO_Analytics_Module {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('template_redirect', [$this, 'maybe_init_integrations']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Register sitemap integration - hook on init to ensure SEOPress is loaded
        add_action('init', [$this, 'register_sitemap_integration'], 20);
    }

    /**
     * Maybe initialize integrations on tribute pages
     */
    public function maybe_init_integrations(): void {
        if (!$this->is_tribute_page()) {
            return;
        }

        // Initialize Plausible if enabled and plugin available
        if ($this->is_plausible_enabled() && $this->is_plausible_available()) {
            $this->init_plausible_integration();
        }

        // Initialize SEOPress if enabled and plugin available
        if ($this->is_seopress_enabled() && $this->is_seopress_available()) {
            $this->init_seopress_integration();
        }
    }

    /**
     * Check if Plausible integration is enabled
     */
    private function is_plausible_enabled(): bool {
        return get_option('fcrm_enhancement_seo_analytics_enable_plausible', false);
    }

    /**
     * Check if SEOPress integration is enabled
     */
    private function is_seopress_enabled(): bool {
        return get_option('fcrm_enhancement_seo_analytics_enable_seopress', false);
    }

    /**
     * Check if Plausible Analytics plugin is available
     */
    private function is_plausible_available(): bool {
        return class_exists('Plausible\Analytics\WP\Helpers');
    }

    /**
     * Check if SEOPress plugin is available
     */
    private function is_seopress_available(): bool {
        return function_exists('seopress_get_service') || defined('SEOPRESS_VERSION');
    }

    /**
     * Initialize Plausible Analytics integration
     */
    private function init_plausible_integration(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_plausible_scripts']);
    }

    /**
     * Initialize SEOPress integration
     */
    private function init_seopress_integration(): void {
        add_action('wp_head', [$this, 'add_seopress_meta_tags']);
        add_filter('seopress_titles_title', [$this, 'custom_seopress_titles_title']);
        add_filter('seopress_titles_desc', [$this, 'custom_seopress_titles_desc']);
        add_filter('seopress_social_og_thumb', [$this, 'custom_seopress_og_image']);
    }

    /**
     * Enqueue Plausible Analytics scripts on tribute pages
     */
    public function enqueue_plausible_scripts(): void {
        if (!$this->is_plausible_available()) {
            return;
        }

        // Get the Plausible Analytics script URL
        $script_url = \Plausible\Analytics\WP\Helpers::get_js_url(true);
        
        // Get the version
        $version = \Plausible\Analytics\WP\Helpers::proxy_enabled() && 
                   file_exists(\Plausible\Analytics\WP\Helpers::get_js_path()) 
                   ? filemtime(\Plausible\Analytics\WP\Helpers::get_js_path()) 
                   : PLAUSIBLE_ANALYTICS_VERSION;
        
        // Enqueue the Plausible Analytics script
        wp_enqueue_script(
            'plausible-analytics',
            $script_url,
            [],
            $version,
            apply_filters('plausible_load_js_in_footer', false)
        );

        // Add the inline script for goal tracking
        wp_add_inline_script(
            'plausible-analytics',
            'window.plausible = window.plausible || function() { (window.plausible.q = window.plausible.q || []).push(arguments) }'
        );
    }

    /**
     * Add SEOPress meta tags for tribute pages
     */
    public function add_seopress_meta_tags(): void {
        if (!$this->is_seopress_available()) {
            return;
        }

        $seopress_service = seopress_get_service('MetaTags');
        if (!$seopress_service) {
            return;
        }

        $single_tribute = new Single_Tribute();
        $single_tribute->detectClient();
        $client = $single_tribute->getClient();

        if ($client) {
            $meta_title = $this->get_custom_meta_title($single_tribute);
            $meta_description = isset($client->content) ? strip_tags($client->content) : 
                              "Tribute for " . (isset($client->firstName) ? $client->firstName : '') . 
                              " " . (isset($client->lastName) ? $client->lastName : '');
            
            // Clean the description
            $meta_description = $this->clean_meta_content($meta_description);
            
            $meta_image = get_option('fcrm_enhancement_seo_analytics_seopress_social_image', 
                                   FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/default-social-share.jpg');
            $current_url = $single_tribute->getPageUrl();

            // Set the SEOPress meta tags
            $seopress_service->setTitle($meta_title);
            $seopress_service->setDescription($meta_description);
            $seopress_service->setOgTitle($meta_title);
            $seopress_service->setOgDescription($meta_description);
            $seopress_service->setOgImage($meta_image);
            $seopress_service->setOgUrl($current_url);
        } else {
            // Handle the case where client data is not available
            $meta_title = get_bloginfo('name') . ' - Tribute';
            $meta_description = 'This tribute page is currently unavailable.';
            $meta_image = get_option('fcrm_enhancement_seo_analytics_seopress_social_image',
                                   FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/default-social-share.jpg');
            $current_url = home_url();

            // Set default SEOPress meta tags
            $seopress_service->setTitle($meta_title);
            $seopress_service->setDescription($meta_description);
            $seopress_service->setOgTitle($meta_title);
            $seopress_service->setOgDescription($meta_description);
            $seopress_service->setOgImage($meta_image);
            $seopress_service->setOgUrl($current_url);
        }
    }

    /**
     * Customize SEOPress title
     */
    public function custom_seopress_titles_title($title) {
        if (!$this->is_tribute_page()) {
            return $title;
        }

        $single_tribute = new Single_Tribute();
        $single_tribute->detectClient();
        return $this->get_custom_meta_title($single_tribute);
    }

    /**
     * Customize SEOPress description
     */
    public function custom_seopress_titles_desc($desc) {
        if (!$this->is_tribute_page()) {
            return $desc;
        }

        $single_tribute = new Single_Tribute();
        $single_tribute->detectClient();
        $client = $single_tribute->getClient();
        
        if ($client) {
            $desc = isset($client->content) ? strip_tags($client->content) : 
                   "Tribute for " . (isset($client->firstName) ? $client->firstName : '') . 
                   " " . (isset($client->lastName) ? $client->lastName : '');
            $desc = $this->clean_meta_content($desc);
        } else {
            $desc = 'This tribute page is currently unavailable.';
        }

        return $desc;
    }

    /**
     * Customize SEOPress Open Graph image
     */
    public function custom_seopress_og_image($image) {
        if (!$this->is_tribute_page()) {
            return $image;
        }

        $custom_image = get_option('fcrm_enhancement_seo_analytics_seopress_social_image',
                                 FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/default-social-share.jpg');
        
        return '<meta property="og:image" content="' . esc_url($custom_image) . '" />';
    }

    /**
     * Get custom meta title for tribute
     */
    private function get_custom_meta_title($single_tribute): string {
        $client = $single_tribute->getClient();
        
        if ($client) {
            $clientName = isset($client->fullName) ? $client->fullName : 
                         (isset($client->firstName) ? $client->firstName : '') . ' ' . 
                         (isset($client->lastName) ? $client->lastName : '');
            $clientName = $this->clean_meta_content($clientName);
        } else {
            $clientName = 'Tribute';
        }
        
        $customSuffix = get_option('fcrm_enhancement_seo_analytics_seopress_title_suffix', 'Tribute');
        $siteTitle = get_bloginfo('name');
        
        return $clientName . ' - ' . $customSuffix . ' | ' . $siteTitle;
    }

    /**
     * Clean meta content (remove quotes, newlines, extra spaces)
     */
    private function clean_meta_content(string $content): string {
        $content = str_replace('"', "'", $content); // Replace double quotes with single quotes
        $content = str_replace(["\r", "\n"], ' ', $content); // Remove newlines
        $content = preg_replace('/\s+/', ' ', trim($content)); // Remove extra spaces
        return $content;
    }

    /**
     * Register module settings
     */
    public function register_settings(): void {
        // Plausible Analytics settings
        register_setting('fcrm_enhancement_seo_analytics', 'fcrm_enhancement_seo_analytics_enable_plausible');

        // SEOPress settings
        register_setting('fcrm_enhancement_seo_analytics', 'fcrm_enhancement_seo_analytics_enable_seopress');

        register_setting(
            'fcrm_enhancement_seo_analytics',
            'fcrm_enhancement_seo_analytics_seopress_social_image',
            [
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw'
            ]
        );

        register_setting(
            'fcrm_enhancement_seo_analytics',
            'fcrm_enhancement_seo_analytics_seopress_title_suffix',
            [
                'type' => 'string',
                'default' => 'Tribute',
                'sanitize_callback' => 'sanitize_text_field'
            ]
        );

        // Sitemap settings
        register_setting('fcrm_enhancement_seo_analytics', 'fcrm_enhancement_seo_analytics_enable_sitemap');
    }

    /**
     * Render settings page
     */
    public function render_settings(): void {
        ?>
        <div class="settings-section">
            <h3>📊 Plausible Analytics</h3>
            <div class="section-content">
                <?php if ($this->is_plausible_available()): ?>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <div style="flex-shrink: 0;">
                            <a href="https://plausible.io" target="_blank" rel="noopener noreferrer">
                                <img src="<?php echo esc_url(FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/plausible-logo.svg'); ?>" 
                                     alt="Plausible Analytics" 
                                     style="height: 32px; width: auto;" />
                            </a>
                        </div>
                        <div>
                            <p style="margin: 0; line-height: 1.4;">Privacy-focused, lightweight analytics for tribute pages. Plausible is GDPR compliant and doesn't use cookies.</p>
                        </div>
                    </div>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Plausible Analytics</th>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           name="fcrm_enhancement_seo_analytics_enable_plausible" 
                                           value="1" 
                                           <?php checked(get_option('fcrm_enhancement_seo_analytics_enable_plausible', 0), 1); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <p class="description">Load Plausible Analytics tracking on tribute pages only</p>
                            </td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p><strong>Plausible Analytics plugin not detected.</strong> Install and activate the Plausible Analytics plugin to enable this feature.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="settings-section">
            <h3>🔍 SEOPress Integration</h3>
            <div class="section-content">
                <?php if ($this->is_seopress_available()): ?>
                    <p>Enhanced SEO and social media meta tags for tribute pages using SEOPress instead of the default Yoast SEO integration.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable SEOPress Integration</th>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           name="fcrm_enhancement_seo_analytics_enable_seopress" 
                                           value="1" 
                                           <?php checked(get_option('fcrm_enhancement_seo_analytics_enable_seopress', 0), 1); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <p class="description">Replace default Yoast SEO integration with SEOPress support</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Title Suffix</th>
                            <td>
                                <input type="text" 
                                       name="fcrm_enhancement_seo_analytics_seopress_title_suffix" 
                                       value="<?php echo esc_attr(get_option('fcrm_enhancement_seo_analytics_seopress_title_suffix', 'Tribute')); ?>" 
                                       class="regular-text" />
                                <p class="description">Text appended to tribute names in page titles</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Default Social Share Image</th>
                            <td>
                                <?php
                                $image_url = get_option('fcrm_enhancement_seo_analytics_seopress_social_image', '');
                                $default_image = FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/default-social-share.jpg';
                                $display_image = $image_url ?: $default_image;
                                ?>
                                <div class="social-image-upload-wrapper">
                                    <div class="image-preview-container">
                                        <img id="social-image-preview" 
                                             src="<?php echo esc_url($display_image); ?>" 
                                             alt="Social share image preview" 
                                             style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;" />
                                    </div>
                                    
                                    <div class="image-controls" style="margin-top: 10px;">
                                        <input type="hidden" 
                                               id="social-image-url" 
                                               name="fcrm_enhancement_seo_analytics_seopress_social_image" 
                                               value="<?php echo esc_attr($image_url); ?>" />
                                        
                                        <button type="button" 
                                                id="upload-social-image" 
                                                class="button">
                                            <?php echo $image_url ? 'Change Image' : 'Upload Image'; ?>
                                        </button>
                                        
                                        <?php if ($image_url): ?>
                                            <button type="button" 
                                                    id="remove-social-image" 
                                                    class="button" 
                                                    style="margin-left: 10px;">
                                                Use Default
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="description">
                                        Default image used for social media sharing when tribute has no image. 
                                        Recommended size: 1200x630 pixels for optimal social media display.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p><strong>SEOPress plugin not detected.</strong> Install and activate SEOPress to enable this feature.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="settings-section">
            <h3>🗺️ SEOPress Sitemap Integration</h3>
            <div class="section-content">
                <?php $seopress_active = $this->is_seopress_available(); ?>

                <?php if ($seopress_active): ?>
                    <p>Automatically add all tribute pages to your SEOPress XML sitemap for better search engine indexing.</p>

                    <div class="notice notice-info inline" style="margin-bottom: 15px;">
                        <p><strong>Note:</strong> Yoast SEO sitemap integration is already handled by the Firehawk CRM plugin. This setting only applies to SEOPress.</p>
                    </div>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable SEOPress Sitemap</th>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox"
                                           name="fcrm_enhancement_seo_analytics_enable_sitemap"
                                           value="1"
                                           <?php checked(get_option('fcrm_enhancement_seo_analytics_enable_sitemap', 1), 1); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <p class="description">
                                    Add all tribute pages to your SEOPress sitemap automatically.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">View Sitemap</th>
                            <td>
                                <a href="<?php echo esc_url(home_url('/sitemaps.xml')); ?>"
                                   target="_blank"
                                   class="button">
                                    View SEOPress Sitemap
                                </a>
                                <p class="description">
                                    View your sitemap to verify tribute pages are included.
                                </p>
                            </td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p><strong>SEOPress plugin not detected.</strong> Install and activate SEOPress to enable sitemap integration.</p>
                        <p><em>Note: Yoast SEO sitemap integration is already handled by the Firehawk CRM plugin.</em></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Show conflicts if any standalone plugins are active
        $conflicts = $this->check_plugin_conflicts();
        if (!empty($conflicts)): ?>
            <div class="settings-section">
                <h3>⚠️ Plugin Conflicts</h3>
                <div class="section-content">
                    <?php foreach ($conflicts as $conflict): ?>
                        <div class="notice notice-error inline">
                            <p><strong><?php echo esc_html($conflict['plugin']); ?>:</strong> <?php echo esc_html($conflict['message']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Check if current page is tribute-related
     */
    protected function is_tribute_page(): bool {
        // Use the standardised method from main plugin
        return FCRM_Tributes_Enhancement_Suite::is_tribute_page();
    }

    /**
     * Register sitemap integration for SEOPress
     *
     * Note: Yoast SEO sitemap integration is already handled by Firehawk plugin.
     * This method only adds SEOPress support.
     */
    public function register_sitemap_integration(): void {
        // Check if sitemap integration is enabled (checkbox value is '1' or 1 when checked)
        $sitemap_enabled = get_option('fcrm_enhancement_seo_analytics_enable_sitemap', 1);

        if (!$sitemap_enabled || $sitemap_enabled === '0') {
            return;
        }

        // Only register SEOPress - Yoast is handled by Firehawk core plugin
        if ($this->is_seopress_available()) {
            add_filter('seopress_sitemaps_external_link', [$this, 'add_tributes_to_seopress_sitemap'], 10, 1);
        }
    }

    /**
     * Detect active SEO plugin
     *
     * Note: Only detects SEOPress. Yoast is already handled by Firehawk core plugin.
     *
     * @return string|false 'seopress' or false
     */
    private function detect_active_seo_plugin() {
        $seopress_active = defined('SEOPRESS_VERSION') || function_exists('seopress_get_service');

        if ($seopress_active) {
            return 'seopress';
        }

        return false;
    }

    /**
     * Add FireHawk tribute sitemap files to SEOPress sitemap index
     *
     * Adds references to the FireHawk CRM generated sitemap files
     * (e.g., /fhf_tributes_sitemap_1.xml) to the SEOPress sitemap index.
     * This matches the Yoast SEO integration approach.
     *
     * Supports both old and new FireHawk API structures:
     * - Old API (api.firehawkcrm.com): Uses /api/tributes/count, calculates ceil(count/500) pages
     * - New API (Australian endpoints): Uses /api/tributes/sitemap-count, returns page count directly
     *
     * @param array $external_links Existing external links
     * @return array Modified external links array
     */
    public function add_tributes_to_seopress_sitemap($external_links): array {
        // Ensure we have an array
        if (!is_array($external_links)) {
            $external_links = array();
        }

        // Check if FireHawk CRM Tributes API is available
        if (!class_exists('Fcrm_Tributes_Api')) {
            return $external_links;
        }

        try {
            $sitemap_count = 1; // Default to 1 sitemap page
            $is_new_api = \FCRM\EnhancementSuite\API_Interceptor::is_new_api_structure();

            if ($is_new_api) {
                // New API structure - sitemap-count endpoint returns page count directly
                $count_response = Fcrm_Tributes_Api::get_tributes_count();

                if (is_object($count_response) && isset($count_response->count)) {
                    $sitemap_count = (int) $count_response->count;
                } elseif (is_numeric($count_response)) {
                    $sitemap_count = (int) $count_response;
                }
            } else {
                // Old API structure - calculate from tribute count
                $count_response = Fcrm_Tributes_Api::get_tributes_count();
                $tribute_count = 0;

                if (is_object($count_response) && isset($count_response->count)) {
                    $tribute_count = (int) $count_response->count;
                } elseif (is_numeric($count_response)) {
                    $tribute_count = (int) $count_response;
                }

                // Calculate number of sitemap pages (500 tributes per page)
                $sitemap_count = ($tribute_count > 0) ? ceil($tribute_count / 500) : 1;
            }

            // Add each FireHawk tribute sitemap file to SEOPress index
            // SEOPress 9.2 expects: array with 'sitemap_url' and 'sitemap_last_mod' keys
            for ($i = 1; $i <= $sitemap_count; $i++) {
                $sitemap_entry = array(
                    'sitemap_url'      => get_site_url() . '/fhf_tributes_sitemap_' . $i . '.xml',
                    'sitemap_last_mod' => date('c'), // ISO 8601 format
                );

                $external_links[] = $sitemap_entry;
            }

        } catch (Exception $e) {
            // Silently fail - don't break the sitemap
        }

        return $external_links;
    }

    /**
     * Check for conflicts with standalone plugins
     */
    public function check_plugin_conflicts(): array {
        $conflicts = [];

        // Check for standalone Plausible plugin
        if (in_array('fcrm-plausible-analytics/fcrm-plausible-analytics.php', 
                    apply_filters('active_plugins', get_option('active_plugins')))) {
            $conflicts[] = [
                'plugin' => 'FCRM Plausible Analytics',
                'message' => 'The standalone FCRM Plausible Analytics plugin is active. Please deactivate it to avoid conflicts.'
            ];
        }

        // Check for standalone SEOPress plugin
        if (in_array('fcrm-seopress/fcrm-seopress.php', 
                    apply_filters('active_plugins', get_option('active_plugins')))) {
            $conflicts[] = [
                'plugin' => 'FCRM SEOPress Integration',
                'message' => 'The standalone FCRM SEOPress Integration plugin is active. Please deactivate it to avoid conflicts.'
            ];
        }

        return $conflicts;
    }

    /**
     * Enqueue admin scripts for media uploader
     */
    public function enqueue_admin_scripts($hook): void {
        // Only enqueue on our settings page
        if (strpos($hook, 'fcrm-enhancements') === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'fcrm-seo-analytics-admin',
            FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/js/admin/seo-analytics-admin.js',
            ['jquery'],
            FCRM_ENHANCEMENT_SUITE_VERSION,
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('fcrm-seo-analytics-admin', 'fcrmSeoAnalyticsAdmin', [
            'defaultImageUrl' => FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/default-social-share.jpg'
        ]);
    }
} 