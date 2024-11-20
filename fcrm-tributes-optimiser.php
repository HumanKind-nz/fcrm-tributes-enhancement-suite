<?php
/**
 * Plugin Name:       FireHawkCRM Tributes - Optimiser
 * Plugin URI:        https://github.com/weavedigitalstudio/fcrm-tributes-optimiser
 * Description:       Optimises FCRM Tributes plugin by controlling script loading and managing features.
 * Version:           1.0.0
 * Author:            Gareth Bissland, Weave Digital Studio
 * Author URI:        https://weave.co.nz/
 * Text Domain:       fcrm-tributes-optimiser
 * License:           MIT
 * GitHub Plugin URI: weavedigitalstudio/fcrm-tributes-optimiser
 * Primary Branch:    main
 * Requires at least: 6.0
 * Requires PHP:      7.2
 * 
 * This plugin optimises the FCRM Tributes plugin in several ways:
 * 1. Conditionally loads scripts and styles only on tribute-related pages
 * 2. Optionally disables flower delivery functionality
 * 3. Removes unnecessary DNS prefetch hints
 * 4. Cleans up hardcoded scripts
 * 
 * Performance optimisations include:
 * - Static caching for tribute page detection
 * - Optimised regex patterns for script removal
 * - Efficient asset handling
 * - Proper WordPress hooks usage
 */

namespace FCRM\Optimiser;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if FCRM Tributes is active
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (!is_plugin_active('fcrm-tributes/fcrm-tributes.php')) {
    return;
}

/**
 * Main plugin class
 * 
 * Handles all functionality for optimizing the FCRM Tributes plugin.
 * Uses static caching and optimized patterns for better performance.
 */
class Plugin {
    /**
     * Plugin instance
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Plugin name
     *
     * @var string
     */
    private $plugin_name = 'fcrm-tributes-optimiser';

    /**
     * Plugin version
     *
     * @var string
     */
    private $version = '1.0.0';

    /**
     * Option prefix for all plugin settings
     * 
     * Used to avoid conflicts with other plugins
     *
     * @var string
     */
    private const OPTION_PREFIX = 'fcrm_optimiser_';

    /**
     * Get plugin instance
     * 
     * Implements singleton pattern for efficiency
     *
     * @return Plugin|null
     */
    public static function get_instance(): ?Plugin {
        if (!class_exists('Fcrm_Tributes_Public')) {
            return null;
        }
        
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     * 
     * Sets up all necessary WordPress hooks and initializes the plugin.
     */
    private function __construct() {
        // Initialize admin settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Handle flower delivery dependencies first
        if ($this->get_option('disable_flowers')) {
            $this->handle_flower_delivery();
        }

        // Only add optimization hooks if enabled
        if ($this->get_option('enabled')) {
            add_action('wp_enqueue_scripts', array($this, 'optimise_assets'), 999);
            add_action('wp_head', array($this, 'remove_hardcoded_scripts'), 0);
            add_filter('wp_resource_hints', array($this, 'remove_dns_prefetch'), 10, 2);
        }
    }

    /**
     * Get plugin option with prefix
     *
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get_option(string $key, $default = null) {
        return get_option(self::OPTION_PREFIX . $key, $default ?? $this->get_default_value($key));
    }

    /**
     * Get default value for option
     *
     * @param string $key Option key
     * @return mixed
     */
    protected function get_default_value(string $key) {
        $defaults = array(
            'enabled' => true,
            'disable_flowers' => false
        );
        return $defaults[$key] ?? null;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_options_page(
            __('FCRM Tributes Optimiser', 'fcrm-tributes-optimiser'),
            __('FCRM Optimiser', 'fcrm-tributes-optimiser'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_admin_page')
        );
    }

    /**
     * Register plugin settings
     * 
     * Includes sanitization callbacks for security
     */
    public function register_settings(): void {
        register_setting(
            $this->plugin_name,
            self::OPTION_PREFIX . 'enabled',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean'
            )
        );

        register_setting(
            $this->plugin_name,
            self::OPTION_PREFIX . 'disable_flowers',
            array(
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean'
            )
        );
    }

    /**
     * Display admin settings page
     * 
     * All output is escaped for security
     */
    public function display_admin_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('FCRM Tributes Optimiser Settings', 'fcrm-tributes-optimiser'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->plugin_name);
                do_settings_sections($this->plugin_name);
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Script Optimisation', 'fcrm-tributes-optimiser'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="<?php echo esc_attr(self::OPTION_PREFIX . 'enabled'); ?>" 
                                       value="1" 
                                       <?php checked($this->get_option('enabled')); ?>>
                                <?php echo esc_html__('Enable script and style optimisation', 'fcrm-tributes-optimiser'); ?>
                            </label>
                            <p class="description">
                                <?php echo esc_html__('When enabled, FCRM Tributes scripts and styles will only load on tribute-related pages.', 'fcrm-tributes-optimiser'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Disable Flower Delivery', 'fcrm-tributes-optimiser'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="<?php echo esc_attr(self::OPTION_PREFIX . 'disable_flowers'); ?>" 
                                       value="1" 
                                       <?php checked($this->get_option('disable_flowers')); ?>>
                                <?php echo esc_html__('Completely disable flower delivery functionality', 'fcrm-tributes-optimiser'); ?>
                            </label>
                            <p class="description">
                                <?php echo esc_html__('This will prevent all flower delivery related files from loading. Changes take effect after saving and refreshing the page.', 'fcrm-tributes-optimiser'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle flower delivery dependency removal
     */
    private function handle_flower_delivery(): void {
        remove_action('plugins_loaded', array('Fcrm_Tributes', 'load_dependencies'));
        add_action('plugins_loaded', array($this, 'custom_load_dependencies'), 20);
    }

    /**
     * Custom dependency loader - excludes flower delivery
     */
    public function custom_load_dependencies(): void {
        if (!class_exists('Fcrm_Tributes')) {
            return;
        }

        $plugin = new \Fcrm_Tributes();

        // Load core dependencies but skip flower delivery
        $base_path = plugin_dir_path(dirname(__FILE__)) . 'fcrm-tributes/';
        $required_files = array(
            'includes/class-fcrm-tributes-loader.php',
            'includes/class-fcrm-tributes-i18n.php',
            'includes/fcrm-api.php',
            'admin/class-fcrm-tributes-admin.php',
            'public/class-single-tribute-type.php',
            'public/class-fcrm-tributes-public.php'
        );

        foreach ($required_files as $file) {
            if (file_exists($base_path . $file)) {
                require_once $base_path . $file;
            }
        }

        $plugin->get_loader()->run();
    }

    /**
     * Check if current page is tribute-related
     * 
     * Uses static caching to prevent multiple checks in the same request.
     * Specifically checks for tribute post type and valid pages.
     *
     * @return bool
     */
    private function is_tribute_page(): bool {
        static $is_tribute = null;

        // Return cached result if already checked in this request
        if ($is_tribute !== null) {
            return $is_tribute;
        }

        // Check for tribute single post type
        if (isset($_GET['id']) && is_singular() && get_post_type() === 'tribute') {
            $is_tribute = true;
            return true;
        }

        // Check if we're on the designated tribute search page
        if (is_page(get_option('fcrm_tributes_search_page_id'))) {
            $is_tribute = true;
            return true;
        }

        // Check for tribute shortcodes in content
        if ($this->has_tribute_shortcode()) {
            $is_tribute = true;
            return true;
        }

        $is_tribute = false;
        return false;
    }

    /**
     * Check if current post contains tribute shortcodes
     * 
     * Uses WordPress core shortcode detection for reliability
     *
     * @return bool
     */
    private function has_tribute_shortcode(): bool {
        global $post;
        if (!$post || !is_a($post, 'WP_Post')) {
            return false;
        }

        $shortcodes = array(
            'show_crm_tribute',
            'show_crm_tributes_grid',
            'show_crm_tributes_large_grid',
            'show_crm_tributes_carousel',
            'show_crm_tribute_search',
            'show_crm_tribute_search_bar'
        );

        // Use WordPress core function for reliable shortcode detection
        $pattern = get_shortcode_regex($shortcodes);
        return preg_match('/' . $pattern . '/', $post->post_content);
    }

    /**
     * Optimize asset loading
     * 
     * Selectively removes scripts and styles on non-tribute pages
     * Preserves theme versions of common libraries
     */
    public function optimise_assets(): void {
        if (!class_exists('Fcrm_Tributes_Public')) {
            return;
        }

        if (!$this->is_tribute_page()) {
            global $wp_scripts, $wp_styles;

            // Define scripts to remove based on specific patterns
            $script_patterns = array(
                // Core functionality scripts
                'shufflejs',      // Used for tribute grid layouts
                'jquery-history', // Used for browser history management
                'jquery-validate', // Form validation
                
                // UI Enhancement scripts
                'select2',        // Enhanced select boxes
                'jquery-slick-carousel', // Carousel functionality
                
                // Feature-specific scripts
                'add-to-calendar-button', // Calendar integration
                'momentScript',   // Date/time handling
                
                // Gallery scripts
                'lg-pager',      // Lightgallery pager
                'lg-zoom',       // Lightgallery zoom
                
                // Utility libraries
                '_'              // Lodash utility library
            );

            // Process and remove scripts
            foreach ($wp_scripts->registered as $handle => $script) {
                // Special handling for FontAwesome - only remove FCRM's version
                if ($handle === 'fontawesome' && strpos($script->src, 'kit.fontawesome.com/0b4429dff6.js') !== false) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                    continue;
                }

                // Special handling for Bootstrap - only remove if from FCRM
                if ($handle === 'bootstrap' && strpos($script->src, '/fcrm-tributes/') !== false) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                    continue;
                }

                // Remove other FCRM scripts
                if (
                    strpos($handle, 'fcrm') !== false || 
                    strpos($script->src, 'unpkg.com') !== false ||
                    in_array($handle, $script_patterns) ||
                    (strpos($script->src, '/fcrm-tributes/') !== false && 
                    !in_array($handle, array('bootstrap', 'fontawesome')))
                ) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }

            // Define styles to remove
            $style_patterns = array(
                'select2',           // Select2 UI styles
                'jquery-slick-nav',  // Navigation styles
                'add-to-calendar-button' // Calendar button styles
            );

            // Process and remove styles
            foreach ($wp_styles->registered as $handle => $style) {
                // Special handling for Bootstrap styles
                if ($handle === 'bootstrap' && strpos($style->src, '/fcrm-tributes/') !== false) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                    continue;
                }

                // Remove styles matching our criteria
                if (
                    strpos($handle, 'fcrm') !== false || 
                    in_array($handle, $style_patterns) ||
                    (strpos($style->src, '/fcrm-tributes/') !== false && 
                    $handle !== 'bootstrap')
                ) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }
    }
        
    /**
     * Remove DNS prefetch hints
     * 
     * @param array $hints Hints array
     * @param string $relation_type Relation type
     * @return array
     */
    public function remove_dns_prefetch(array $hints, string $relation_type): array {
        if ($relation_type === 'dns-prefetch') {
            return array_filter($hints, function ($hint) {
                return !in_array($hint, ['//unpkg.com']);
            });
        }
        return $hints;
    }
    
    /**
     * Remove hardcoded scripts using optimized regex pattern
     */
    public function remove_hardcoded_scripts(): void {
        ob_start(function ($output) {
            // Combined pattern with non-capturing group for better performance
            $pattern = '#<script[^>]*src=["\']https://unpkg\.com/(?:@popperjs/core@2|tippy\.js@6)[^>]*></script>#';
            return preg_replace($pattern, '', $output);
        });
    }
    
    /**
     * Plugin activation hook
     */
    public static function activate(): void {
        add_option(self::OPTION_PREFIX . 'enabled', true);
        add_option(self::OPTION_PREFIX . 'disable_flowers', false);
    }
    
    /**
     * Plugin deactivation hook
     */
    public static function deactivate(): void {
        delete_option(self::OPTION_PREFIX . 'enabled');
        delete_option(self::OPTION_PREFIX . 'disable_flowers');
    }
    }
    
    // Register activation and deactivation hooks
    register_activation_hook(__FILE__, array(__NAMESPACE__ . '\Plugin', 'activate'));
    register_deactivation_hook(__FILE__, array(__NAMESPACE__ . '\Plugin', 'deactivate'));
    
    // Initialize the optimizer only if FCRM Tributes exists
    if (class_exists('Fcrm_Tributes_Public')) {
        add_action('plugins_loaded', function () {
            Plugin::get_instance();
        });
    }