<?php
/**
 * FCRM Optimisation Module
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCRM_Optimisation_Module {
    
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Note: Conditional asset loading is now handled by the main plugin class
        // to ensure it works regardless of module enable/disable state
    }

    public function register_settings(): void {
        // Core optimisation settings
        register_setting('fcrm_enhancement_optimisation', 'fcrm_cache_enabled');
        register_setting('fcrm_enhancement_optimisation', 'fcrm_disable_flowers');
        register_setting('fcrm_enhancement_optimisation', 'fcrm_script_optimisation');
        register_setting('fcrm_enhancement_optimisation', 'fcrm_conditional_asset_loading');
        
        // Detailed caching settings
        register_setting('fcrm_enhancement_optimisation', 'fcrm_cache_duration_client_list');
        register_setting('fcrm_enhancement_optimisation', 'fcrm_cache_duration_single_client');
        register_setting('fcrm_enhancement_optimisation', 'fcrm_cache_duration_messages');
        
        // Debug logging (gates [FCRM_ES] logs)
        register_setting('fcrm_enhancement_optimisation', 'fcrm_debug_logging');
        
        // Loading and animation settings
        register_setting('fcrm_enhancement_optimisation', 'fcrm_spinner_style');
        register_setting('fcrm_enhancement_optimisation', 'fcrm_spinner_size');
        register_setting('fcrm_enhancement_optimisation', 'fcrm_spinner_color');
        register_setting('fcrm_enhancement_optimisation', 'fcrm_smooth_animations');
    }

    public function render_settings(): void {
        ?>
        <div class="settings-section">
            <h3>🚀 API Caching</h3>
            <div class="section-content">
                <p>Dramatically improve tribute page loading times by caching API responses. This is safe to enable and will significantly reduce page load times.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable API Caching</th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" name="fcrm_cache_enabled" value="1" <?php checked(get_option('fcrm_cache_enabled', 1), 1); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Cache FCRM API responses to improve page load times. Cache is automatically cleared when tributes are updated.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client List Cache Duration</th>
                        <td>
                            <input type="number" 
                                name="fcrm_cache_duration_client_list" 
                                value="<?php echo esc_attr(get_option('fcrm_cache_duration_client_list', 1800)); ?>" 
                                min="60" 
                                max="7200" 
                                step="60" />
                            <span>seconds</span>
                            <p class="description">How long to cache tribute listings (default: 1800 seconds / 30 minutes)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Single Tribute Cache Duration</th>
                        <td>
                            <input type="number" 
                                name="fcrm_cache_duration_single_client" 
                                value="<?php echo esc_attr(get_option('fcrm_cache_duration_single_client', 900)); ?>" 
                                min="60" 
                                max="3600" 
                                step="60" />
                            <span>seconds</span>
                            <p class="description">How long to cache individual tribute pages (default: 900 seconds / 15 minutes)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Messages Cache Duration</th>
                        <td>
                            <input type="number" 
                                name="fcrm_cache_duration_messages" 
                                value="<?php echo esc_attr(get_option('fcrm_cache_duration_messages', 300)); ?>" 
                                min="60" 
                                max="1800" 
                                step="60" />
                            <span>seconds</span>
                            <p class="description">How long to cache tribute messages and dynamic content (default: 300 seconds / 5 minutes)</p>
                        </td>
                    </tr>
                </table>
                
                <?php
                // Show cache statistics if caching is enabled
                if (get_option('fcrm_cache_enabled', 1)) {
                    // Check if Cache_Manager class exists
                    if (class_exists('FCRM\\EnhancementSuite\\Cache_Manager')) {
                        $stats = \FCRM\EnhancementSuite\Cache_Manager::get_cache_stats();
                        ?>
                        <h4>Cache Statistics</h4>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">Cached Items</th>
                                <td>
                                    <strong><?php echo esc_html($stats['transient_count']); ?></strong> items currently cached
                                    <?php if ($stats['redis_available']): ?>
                                        <span style="color: #46b450;">✓ Redis Object Cache Active</span>
                                    <?php else: ?>
                                        <span style="color: #ffb900;">⚠ Using Database Transients</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Cache Management</th>
                                <td>
                                    <button type="button" class="button" onclick="fcrmClearAllCache()" style="margin-right: 10px;">
                                        Clear All Cache
                                    </button>
                                    <p class="description">
                                        Clear all cached tribute data. Cache will be rebuilt automatically as pages are visited.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <script>
                        function fcrmClearAllCache() {
                            if (!confirm('Are you sure you want to clear all FCRM cache? This will temporarily slow down tribute pages until the cache is rebuilt.')) {
                                return;
                            }
                            
                            jQuery.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'fcrm_clear_cache',
                                    nonce: '<?php echo wp_create_nonce('fcrm_clear_cache'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        alert('Cache cleared successfully!');
                                        location.reload();
                                    } else {
                                        alert('Failed to clear cache: ' + response.message);
                                    }
                                },
                                error: function() {
                                    alert('Network error while clearing cache');
                                }
                            });
                        }
                        </script>
                        <?php
                    } else {
                        ?>
                        <p><em>Cache statistics will be available when the cache system is fully initialized.</em></p>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

        <div class="settings-section">
            <h3>🧪 Debugging</h3>
            <div class="section-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Debug Logging</th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" name="fcrm_debug_logging" value="1" <?php checked(get_option('fcrm_debug_logging', 0), 1); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">When enabled and WP_DEBUG is true, the plugin writes diagnostic lines prefixed with [FCRM_ES] to wp-content/debug.log. Disable on production unless troubleshooting.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="settings-section">
            <h3>🛠️ Script Optimisation</h3>
            <div class="section-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">FCRM Asset Dequeuing</th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" name="fcrm_conditional_asset_loading" value="1" <?php checked(get_option('fcrm_conditional_asset_loading', 1), 1); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description"><strong>🎯 Major Performance Fix:</strong> Prevents FCRM plugin from loading 28+ CSS/JS files (Bootstrap, Moment, Lodash, etc.) on non-tribute pages. <strong>Saves ~430KB per page load.</strong> Only loads assets where actually needed.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enhancement Suite Optimisations</th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" name="fcrm_script_optimisation" value="1" <?php checked(get_option('fcrm_script_optimisation', 1), 1); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">General script optimisations for the Enhancement Suite itself (loading animations, etc.).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Disable Flower Delivery</th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" name="fcrm_disable_flowers" value="1" <?php checked(get_option('fcrm_disable_flowers', 0), 1); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Completely disable flower delivery functionality and remove related scripts.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="settings-section">
            <h3>⚡ Loading & Animations</h3>
            <div class="section-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">Loading Spinner Style</th>
                        <td>
                            <select name="fcrm_spinner_style">
                                <option value="primary" <?php selected(get_option('fcrm_spinner_style', 'primary'), 'primary'); ?>>Primary (Default)</option>
                                <option value="modern" <?php selected(get_option('fcrm_spinner_style'), 'modern'); ?>>Modern</option>
                                <option value="minimal" <?php selected(get_option('fcrm_spinner_style'), 'minimal'); ?>>Minimal</option>
                                <option value="pulse" <?php selected(get_option('fcrm_spinner_style'), 'pulse'); ?>>Pulse</option>
                                <option value="dots" <?php selected(get_option('fcrm_spinner_style'), 'dots'); ?>>Dots</option>
                            </select>
                            <p class="description">Choose the loading spinner style for tribute pages.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Spinner Size</th>
                        <td>
                            <select name="fcrm_spinner_size">
                                <option value="sm" <?php selected(get_option('fcrm_spinner_size'), 'sm'); ?>>Small</option>
                                <option value="default" <?php selected(get_option('fcrm_spinner_size', 'default'), 'default'); ?>>Default</option>
                                <option value="lg" <?php selected(get_option('fcrm_spinner_size'), 'lg'); ?>>Large</option>
                                <option value="xl" <?php selected(get_option('fcrm_spinner_size'), 'xl'); ?>>Extra Large</option>
                            </select>
                            <p class="description">Set the default size for loading spinners.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Spinner Colour</th>
                        <td>
                            <input type="text" 
                                id="fcrm_spinner_color"
                                name="fcrm_spinner_color"
                                value="<?php echo esc_attr(get_option('fcrm_spinner_color', '#667eea')); ?>"
                                class="alpha-color-control"
                            />
                            <p class="description">Choose the colour for loading spinners.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Smooth Animations</th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" name="fcrm_smooth_animations" value="1" <?php checked(get_option('fcrm_smooth_animations', 1), 1); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Enable smooth animations and transitions throughout the site.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="info-card">
            <h4>💡 Performance Tips</h4>
            <p>These optimisations focus on the core performance improvements that matter most: API caching for faster loading, script optimisation to reduce unnecessary loading, and flower delivery disabling to remove unused functionality. The caching system can provide significant performance improvements when properly configured.</p>
            
            <h4>⚠️ Hosting Compatibility</h4>
            <p><strong>Optimised for:</strong> VPS & Cloud hosting with Redis Object Cache and NGINX page caching.</p>
            <p><strong>May not work optimally with:</strong> OpenLiteSpeed (OLS) servers, shared hosting without Redis, or hosting providers with aggressive caching that conflicts with our API interception.</p>
            <p><em>If you experience issues, try disabling API caching and using only script optimisation features.</em></p>
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

        // Only enqueue on optimisation tab
        $current_tab = $_GET['tab'] ?? 'dashboard';
        if ($current_tab !== 'optimisation') {
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
        // Use the standardised tribute page detection from main plugin
        if (!FCRM_Enhancement_Suite::is_tribute_page()) {
            return;
        }

        // Only load spinner on grid pages (not single tribute pages)
        $is_single_tribute = isset($_GET['id']);
        $is_grid_page = !$is_single_tribute; // Works on all tribute grid pages including FireHawk passthrough

        if ($is_grid_page && get_option('fcrm_smooth_animations', 1)) {
            // Inline spinner CSS with user settings (respects color, size, style)
            wp_add_inline_style('wp-block-library', $this->generate_spinner_css());

            // Inline spinner JavaScript
            wp_add_inline_script('jquery', $this->get_spinner_javascript());
        }
    }

    /**
     * Generate spinner CSS based on settings
     * Includes navigation spinner overlay for tribute card clicks
     */
    private function generate_spinner_css(): string {
        $spinner_color = get_option('fcrm_spinner_color', '#3498db');
        $spinner_size = get_option('fcrm_spinner_size', 'default');
        $spinner_style = get_option('fcrm_spinner_style', 'primary');
        $smooth_animations = get_option('fcrm_smooth_animations', 1);

        $sizes = [
            'sm' => '30px',
            'default' => '50px',
            'lg' => '60px',
            'xl' => '80px'
        ];

        $size = $sizes[$spinner_size] ?? $sizes['default'];
        $border_width = $spinner_size === 'sm' ? '3px' : '4px';
        $animation_duration = $smooth_animations ? '0.8s' : '0.6s';
        $transition_speed = $smooth_animations ? '0.3s ease' : '0.15s ease';

        $css = "
        /* FCRM Navigation Spinner - Shows when clicking tribute cards */
        .fcrm-navigating {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity {$transition_speed}, visibility {$transition_speed};
        }

        .fcrm-navigating.active {
            opacity: 1;
            visibility: visible;
        }

        .fcrm-navigating::after {
            content: '';
            width: {$size};
            height: {$size};
            border: {$border_width} solid rgba(0, 0, 0, 0.1);
            border-top: {$border_width} solid {$spinner_color};
            border-radius: 50%;
            animation: fcrm-spin {$animation_duration} linear infinite;
        }
        ";

        // Add style-specific variations
        switch ($spinner_style) {
            case 'modern':
                $css .= "
                .fcrm-navigating::after {
                    border-color: rgba(0, 0, 0, 0.05);
                    border-top-color: {$spinner_color};
                    border-right-color: {$spinner_color};
                }
                ";
                break;

            case 'minimal':
                $css .= "
                .fcrm-navigating::after {
                    border-color: transparent;
                    border-top-color: {$spinner_color};
                }
                ";
                break;

            case 'pulse':
                $css .= "
                .fcrm-navigating::after {
                    border-color: {$spinner_color};
                    animation: fcrm-pulse {$animation_duration} ease-in-out infinite;
                }
                @keyframes fcrm-pulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.5; transform: scale(0.9); }
                }
                ";
                break;

            case 'dots':
                $css .= "
                .fcrm-navigating::after {
                    border: none;
                    width: calc({$size} / 5);
                    height: calc({$size} / 5);
                    background: {$spinner_color};
                    animation: fcrm-dots {$animation_duration} ease-in-out infinite;
                    box-shadow:
                        calc({$size} / 3) 0 0 {$spinner_color},
                        calc({$size} / 1.5) 0 0 {$spinner_color};
                }
                @keyframes fcrm-dots {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.3; }
                }
                ";
                break;
        }

        $css .= "
        @keyframes fcrm-spin {
            to { transform: rotate(360deg); }
        }
        ";

        return $css;
    }

    /**
     * Generate spinner JavaScript for grid layouts
     */
    public function get_spinner_javascript(): string {
        return "
        // Create spinner overlay once
        let spinnerOverlay = null;

        function showNavigationSpinner() {
            if (!spinnerOverlay) {
                spinnerOverlay = jQuery('<div class=\"fcrm-navigating\"></div>');
                jQuery('body').append(spinnerOverlay);
            }
            // Small delay prevents flashing on fast connections
            setTimeout(() => {
                if (spinnerOverlay) {
                    spinnerOverlay.addClass('active');
                    jQuery('body').css('overflow', 'hidden');
                }
            }, 100);
        }

        function hideNavigationSpinner() {
            if (spinnerOverlay) {
                spinnerOverlay.removeClass('active');
                jQuery('body').css('overflow', '');
            }
        }

        // Show spinner when clicking tribute cards
        jQuery(document).on('click', '.fcrm-tribute-card[data-detail-url], .minimal-tribute-item[data-detail-url]', function(e) {
            const url = jQuery(this).attr('data-detail-url');
            if (url && url !== '#' && !e.target.closest('a')) {
                showNavigationSpinner();
            }
        });

        // Also show spinner when clicking tribute links directly
        jQuery(document).on('click', 'a[href*=\"?id=\"], a.tribute-name-link, a.tribute-image-link, a.elegant-name-link, a.gallery-name-link', function(e) {
            if (!e.ctrlKey && !e.metaKey) {
                showNavigationSpinner();
            }
        });

        // Hide spinner when navigating back with browser back button (bfcache)
        jQuery(window).on('pageshow', function(e) {
            if (e.originalEvent && e.originalEvent.persisted) {
                // Page was restored from bfcache, hide spinner
                hideNavigationSpinner();
            }
        });

        // Also hide spinner on page load (safety fallback)
        jQuery(window).on('load', function() {
            setTimeout(hideNavigationSpinner, 100);
        });
        ";
    }

} 