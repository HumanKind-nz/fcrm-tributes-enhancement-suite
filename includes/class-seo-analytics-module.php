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
        
        // Get the version - match Plausible plugin's approach (v2.4.2+)
        $version = \Plausible\Analytics\WP\Helpers::proxy_enabled() && 
                   file_exists(\Plausible\Analytics\WP\Helpers::get_js_path()) 
                   ? filemtime(\Plausible\Analytics\WP\Helpers::get_js_path()) 
                   : $this->get_plausible_plugin_version();
        
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
     * Get Plausible Analytics plugin version
     * 
     * Matches the approach used in Plausible plugin v2.4.2+
     * Checks for constant first (backward compatibility), then falls back to plugin data
     * 
     * @return string|null Plugin version or null if not found
     */
    private function get_plausible_plugin_version(): ?string {
        // Check for constant first (backward compatibility with older versions)
        if (defined('PLAUSIBLE_ANALYTICS_VERSION')) {
            return PLAUSIBLE_ANALYTICS_VERSION;
        }

        // Use the same approach as Plausible plugin v2.4.2+
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Try to get plugin file path from constant (if defined in v2.4.2+)
        $plugin_file = null;
        if (defined('PLAUSIBLE_ANALYTICS_PLUGIN_FILE')) {
            $plugin_file = PLAUSIBLE_ANALYTICS_PLUGIN_FILE;
        } else {
            // Fallback: try to find the plugin file
            $plugin_file = WP_PLUGIN_DIR . '/plausible-analytics/plausible-analytics.php';
            if (!file_exists($plugin_file)) {
                // Try alternative path
                $plugin_file = WP_PLUGIN_DIR . '/plausible/plausible-analytics.php';
            }
        }

        if ($plugin_file && file_exists($plugin_file)) {
            $data = get_plugin_data($plugin_file);
            return $data['Version'] ?? null;
        }

        return null;
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
            <h3>🗺️ Tribute Sitemap</h3>
            <div class="section-content">
                <?php
                $seo_plugin = $this->detect_active_seo_plugin();
                $seo_label = $seo_plugin ? ucfirst($seo_plugin) : 'None detected';
                ?>
                <p>Generates XML sitemaps for all tribute pages and registers them with your SEO plugin. Works with SEOPress, Yoast SEO, RankMath, and WordPress native sitemaps.</p>

                <div class="notice notice-info inline" style="margin-bottom: 15px;">
                    <p><strong>Active SEO plugin:</strong> <?php echo esc_html($seo_label); ?>.
                    Tribute sitemaps are also directly accessible at <code>/fhf_tributes_sitemap_1.xml</code> regardless of which SEO plugin is active.</p>
                </div>

                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Tribute Sitemap</th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       name="fcrm_enhancement_seo_analytics_enable_sitemap"
                                       value="1"
                                       <?php checked(get_option('fcrm_enhancement_seo_analytics_enable_sitemap', 1), 1); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">
                                Generate tribute sitemaps and register them with your SEO plugin's sitemap index.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">View Sitemap</th>
                        <td>
                            <a href="<?php echo esc_url(home_url('/fhf_tributes_sitemap_1.xml')); ?>"
                               target="_blank"
                               class="button">
                                View Tribute Sitemap
                            </a>
                            <?php if ($seo_plugin): ?>
                            <a href="<?php echo esc_url(home_url('/sitemaps.xml')); ?>"
                               target="_blank"
                               class="button"
                               style="margin-left: 5px;">
                                View Sitemap Index
                            </a>
                            <?php endif; ?>
                            <p class="description">
                                View your tribute sitemap to verify tribute pages are included.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
        // Render instant indexing settings
        if (class_exists('FCRM\\EnhancementSuite\\Instant_Indexing')) {
            \FCRM\EnhancementSuite\Instant_Indexing::render_settings();
        }
        ?>

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
     * Register sitemap integration
     *
     * Sitemap XML generation and SEO plugin registration is now handled
     * by the Sitemap_Generator class. This method is kept for backward
     * compatibility but delegates to the new system.
     */
    public function register_sitemap_integration(): void {
        // Sitemap generation and SEO plugin registration is now handled by
        // FCRM\EnhancementSuite\Sitemap_Generator which is initialized in the main plugin file.
        // It registers with SEOPress, Yoast, RankMath, and WordPress native sitemaps.
    }

    /**
     * Detect active SEO plugin
     *
     * @return string|false 'seopress', 'yoast', 'rankmath', or false
     */
    private function detect_active_seo_plugin() {
        if (defined('SEOPRESS_VERSION') || function_exists('seopress_get_service')) {
            return 'seopress';
        }
        if (defined('WPSEO_VERSION')) {
            return 'yoast';
        }
        if (class_exists('RankMath')) {
            return 'rankmath';
        }
        return false;
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
            'defaultImageUrl' => FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/default-social-share.jpg',
            'indexingNonce'   => wp_create_nonce('fcrm_indexing_nonce'),
        ]);
    }
} 