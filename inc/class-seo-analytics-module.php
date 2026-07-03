<?php
declare(strict_types=1);

/**
 * SEO & Analytics Module
 *
 * Handles SEOPress integration and Plausible Analytics integration
 * for FCRM Tributes enhancement.
 *
 * @package FcrmEnhancementSuite
 */

if (!defined('ABSPATH')) {
    exit;
}

use function FcrmEnhancementSuite\Helpers\get_setting;

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
        return get_setting('seo_enable_plausible', false);
    }

    /**
     * Check if SEOPress integration is enabled
     */
    private function is_seopress_enabled(): bool {
        return get_setting('seo_enable_seopress', false);
    }

    /**
     * Check if Plausible Analytics plugin is available
     */
    private function is_plausible_available(): bool {
        return \FcrmEnhancementSuite\Status\is_plausible_active();
    }

    /**
     * Check if SEOPress plugin is available
     */
    private function is_seopress_available(): bool {
        return \FcrmEnhancementSuite\Status\is_seopress_active();
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
        add_filter('seopress_titles_canonical', [$this, 'custom_seopress_canonical']);

        // SEOPress already emits a full set of OG tags for tribute pages, so
        // FireHawk's own block is redundant and produces duplicate
        // og:url/og:title/og:type/og:description. Drop FireHawk's block.
        $this->remove_firehawk_duplicate_meta();
    }

    /**
     * Unhook FireHawk's single-tribute OG meta block from wp_head.
     *
     * FireHawk registers `service_details_setmeta` on wp_head for single
     * tributes. With the SEOPress integration active we provide a single,
     * cleaner set of tags, so FireHawk's duplicate is removed.
     *
     * Matched by method name rather than instance so it stays version-agnostic:
     * if FireHawk ever renames the method this simply no-ops (the duplication
     * returns) instead of breaking.
     */
    private function remove_firehawk_duplicate_meta(): void {
        global $wp_filter;

        if (empty($wp_filter['wp_head'])) {
            return;
        }

        foreach ($wp_filter['wp_head']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $id => $callback) {
                $fn = $callback['function'] ?? null;
                if (is_array($fn) && isset($fn[1]) && 'service_details_setmeta' === $fn[1]) {
                    unset($wp_filter['wp_head']->callbacks[$priority][$id]);
                }
            }
        }
    }

    /**
     * Resolve the social share (og:image) URL for a tribute.
     *
     * Priority: the deceased's photo (when the "use tribute photo" setting is on
     * and a photo exists), then the configured branded image, then the plugin
     * default. Overridable via the `fcrm_tribute_og_image` filter.
     *
     * @param object|null $client FireHawk client object, or null to detect it.
     * @return string Image URL.
     */
    private function get_tribute_social_image($client = null): string {
        if (null === $client) {
            $single_tribute = new Single_Tribute();
            $single_tribute->detectClient();
            $client = $single_tribute->getClient();
        }

        $configured = get_setting('seo_seopress_social_image');
        $fallback   = $configured ?: FCRM_ENHANCEMENT_SUITE_URL . 'assets/images/default-social-share.jpg';

        $image = $fallback;

        // Prefer the deceased's photo when enabled and available.
        if (get_setting('seo_seopress_use_tribute_photo', true) && $client && !empty($client->displayImage)) {
            $image = $client->displayImage;
        }

        /**
         * Filter the tribute social share (og:image) URL.
         *
         * @param string      $image  Chosen image URL.
         * @param object|null $client FireHawk client object (null if unavailable).
         */
        return (string) apply_filters('fcrm_tribute_og_image', $image, $client);
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
            
            $meta_image = $this->get_tribute_social_image($client);
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
            $meta_image = get_setting('seo_seopress_social_image') ?: FCRM_ENHANCEMENT_SUITE_URL . 'assets/images/default-social-share.jpg';
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

        $custom_image = $this->get_tribute_social_image();

        return '<meta property="og:image" content="' . esc_url($custom_image) . '" />';
    }

    /**
     * Point the canonical at the individual tribute URL.
     *
     * SEOPress defaults the canonical to the underlying page permalink (the
     * single-page-location, e.g. /funeral/), so every tribute would canonical
     * to the same shared URL and individual notices get consolidated out of the
     * index. We override it to the tribute's own pretty URL, matching og:url.
     *
     * Only applies to an actual single tribute (a detected client); grid/listing
     * pages keep SEOPress's default canonical.
     *
     * FireHawk has an equivalent fix but only for Yoast (wpseo_canonical), so it
     * never fires on SEOPress sites — this fills that gap.
     *
     * @param string $canonical Full <link rel="canonical"> tag from SEOPress.
     * @return string
     */
    public function custom_seopress_canonical($canonical) {
        if (!$this->is_tribute_page()) {
            return $canonical;
        }

        $single_tribute = new Single_Tribute();
        $single_tribute->detectClient();

        // No detected client means a listing/grid page, not a single tribute.
        if (!$single_tribute->getClient()) {
            return $canonical;
        }

        $url = $single_tribute->getPageUrl();
        if (empty($url)) {
            return $canonical;
        }

        // FireHawk's getPageUrl() can append an empty ?tid= (team group index).
        // Strip it so the canonical stays clean; keep tid only when it has a value.
        if (preg_match('/[?&]tid=(&|$)/', $url)) {
            $url = remove_query_arg('tid', $url);
        }

        return '<link rel="canonical" href="' . esc_url($url) . '">';
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
        
        $customSuffix = get_setting('seo_seopress_title_suffix', 'Tribute');
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
        // Settings are now managed by the consolidated settings system.
    }

    /**
     * Render settings page
     */
    public function render_settings(): void {
        // Settings UI is now managed by the consolidated React settings page.
    }

    /**
     * Check if current page is tribute-related
     */
    protected function is_tribute_page(): bool {
        // Use the standardised method from main plugin
        return \FcrmEnhancementSuite\TributeDetection\is_tribute_page();
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
        // FcrmEnhancementSuite\Sitemap_Generator which is initialized in the main plugin file.
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
        // Admin scripts are now managed by the consolidated React settings page.
    }
} 