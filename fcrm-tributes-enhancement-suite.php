<?php
/**
 * Plugin Name: FireHawkCRM Tributes Enhancement Suite
 * Plugin URI: https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite
 * Description: Performance optimisations and enhancements for the FireHawkCRM Tributes plugin
 * Version: 2.2.3
 * Author: Weave Digital Studio, Gareth Bissland
 * Author URI: https://weave.co.nz/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fcrm-enhancement-suite
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('FCRM_ENHANCEMENT_SUITE_VERSION', '2.2.3');
define('FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FCRM_ENHANCEMENT_SUITE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FCRM_ENHANCEMENT_SUITE_PLUGIN_FILE', __FILE__);

// Include required classes
require_once plugin_dir_path(__FILE__) . 'includes/interface-fcrm-module.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-fcrm-enhancement-base.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-optimisation-module.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-styling-module.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-layouts-module.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-seo-analytics-module.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-update-checker.php';

// Include cache-related classes if they exist
if (file_exists(plugin_dir_path(__FILE__) . 'includes/class-fcrm-cache-manager.php')) {
	require_once plugin_dir_path(__FILE__) . 'includes/class-fcrm-cache-manager.php';
}
if (file_exists(plugin_dir_path(__FILE__) . 'includes/class-fcrm-api-interceptor.php')) {
	require_once plugin_dir_path(__FILE__) . 'includes/class-fcrm-api-interceptor.php';
}

// Include URL fixer for tribute detection
require_once plugin_dir_path(__FILE__) . 'includes/class-tribute-url-fixer.php';

/**
 * Main plugin class
 */
class FCRM_Enhancement_Suite {
	
	private $modules = [];
	private $tabs = [
		'optimisation' => 'Performance Optimisation',
		'layouts' => 'Modern Layouts',
		'ui_styling' => 'UI Styling',
		'seo_analytics' => 'SEO & Analytics',
		'styling' => 'Style Overrides'
	];

	public function __construct() {
		add_action('init', [$this, 'init']);
		add_action('admin_menu', [$this, 'add_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
		
		// Initialize critical performance optimisations (always enabled, regardless of module state)
		$this->init_critical_optimisations();
		
		// Check for plugin dependencies and conflicts
		add_action('admin_notices', [$this, 'check_plugin_dependencies']);
		add_action('admin_notices', [$this, 'check_plugin_conflicts']);
		
		// Initialize cache system if available
		if (class_exists('FCRM\\EnhancementSuite\\API_Interceptor')) {
			add_action('init', ['FCRM\\EnhancementSuite\\API_Interceptor', 'init']);
		}

		// Initialize tribute URL fixer (always needed for proper tribute detection)
		if (class_exists('FCRM\\EnhancementSuite\\Tribute_URL_Fixer')) {
			add_action('init', ['FCRM\\EnhancementSuite\\Tribute_URL_Fixer', 'init'], 5);
		}
		
		// Add AJAX handlers for cache management
		add_action('wp_ajax_fcrm_clear_cache', [$this, 'ajax_clear_cache']);
		add_action('wp_ajax_fcrm_get_cache_stats', [$this, 'ajax_get_cache_stats']);
		
		// Load text domain
		add_action('plugins_loaded', [$this, 'load_textdomain']);
		
		// Register activation/deactivation hooks
		register_activation_hook(__FILE__, [$this, 'activate']);
		register_deactivation_hook(__FILE__, [$this, 'deactivate']);
		
		// Handle module enable/disable
		add_action('admin_init', [$this, 'handle_module_toggles']);
		
		// Register settings for all modules (so they can be saved even when disabled)
		add_action('admin_init', [$this, 'register_all_module_settings']);
		
		// Initialize GitHub update checker
		if (class_exists('FCRM\\EnhancementSuite\\PluginUpdateChecker')) {
			FCRM\EnhancementSuite\PluginUpdateChecker::init(__FILE__, 'HumanKind-nz/fcrm-tributes-enhancement-suite');
		}
	}

	/**
	 * Initialize critical performance optimisations that should always be available
	 * These work regardless of module enable/disable state
	 */
	private function init_critical_optimisations(): void {
		// Register the setting so it can be saved
		add_action('admin_init', function() {
			register_setting('fcrm_enhancement_optimisation', 'fcrm_conditional_asset_loading');
		});

		// Initialize conditional asset loading if enabled (default: enabled)
		$conditional_loading_enabled = get_option('fcrm_conditional_asset_loading', 1);
		
		if ($conditional_loading_enabled) {
			// Hook late to dequeue FCRM assets after they're enqueued
			add_action('wp_enqueue_scripts', [$this, 'conditional_fcrm_assets'], 999);
		}
	}

	public function init(): void {
		// Load modules
		$this->load_modules();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'fcrm-enhancement-suite',
			false,
			dirname(plugin_basename(__FILE__)) . '/languages/'
		);
	}

	private function load_modules(): void {
		$module_files = [
			'optimisation' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-optimisation-module.php',
			'layouts' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-layouts-module.php',
			'ui_styling' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-ui-styling-module.php',
			'styling' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-styling-module.php',
			'seo_analytics' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-seo-analytics-module.php'
		];

		foreach ($module_files as $key => $file) {
			// Check if module is enabled (default to false for new installations)
			$enabled = get_option('fcrm_module_' . $key . '_enabled', false);
			
			// Debug logging
			
			// Only load if module is enabled
			if (!$enabled) {
				continue;
			}
			
			if (file_exists($file)) {
				require_once $file;
				
				// Handle special naming for modules
				if ($key === 'seo_analytics') {
					$class_name = 'FCRM_SEO_Analytics_Module';
				} else {
					$class_name = 'FCRM_' . ucfirst($key) . '_Module';
				}
				
				
				if (class_exists($class_name)) {
					$this->modules[$key] = new $class_name();
				} else {
				}
			}
		}
		
		// Load external modules
		$this->load_external_modules();
	}
	
	private function load_external_modules(): void {
		$external_modules = get_option('fcrm_external_modules', []);
		
		foreach ($external_modules as $module_key => $module_data) {
			if (!isset($module_data['enabled']) || !$module_data['enabled']) {
				continue;
			}
			
			if (isset($module_data['file']) && file_exists($module_data['file'])) {
				require_once $module_data['file'];
				
				if (isset($module_data['class']) && class_exists($module_data['class'])) {
					$this->modules[$module_key] = new $module_data['class']();
				}
			}
		}
	}

	public function add_admin_menu(): void {
		add_menu_page(
			__('FH Enhancement Suite', 'fcrm-enhancement-suite'),
			__('FH Enhancement Suite', 'fcrm-enhancement-suite'),
			'manage_options',
			'fcrm-enhancements',
			[$this, 'render_admin_page'],
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0ibm9uZSIgdmlld0JveD0iMCAwIDEyMiAxMDYiPjxwYXRoIGZpbGw9IiNGRkYiIGQ9Ik02LjQgMTguN2MwIDE1LjUgNi41IDI5LjUgMTcgMzkuNSAyLTEuNiA0LjItMyA2LjQtNC40YTQ2LjcgNDYuNyAwIDAgMS0xNS44LTM1aDYuNWE0MC40IDQwLjQgMCAwIDAgMjIgMzYgNTEuOCA1MS44IDAgMCAwLTE5LjEgMTEuOGMtMTAuNSA5LjktMTcgMjQtMTcgMzkuNEgwYzAtMTcuMSA3LjEtMzIuNiAxOC41LTQzLjZBNjAuNiA2MC42IDAgMCAxIDAgMTguN2g2LjRabTEwMS42IDBhNDYuNyA0Ni43IDAgMCAxLTQ3IDQ2LjkgNDAuMiA0MC4yIDAgMCAwLTI1IDguNkE0MC40IDQwLjQgMCAwIDAgMjAuNSAxMDZIMTRhNDYuNyA0Ni43IDAgMCAxIDQ3LTQ2LjggNDAuMiA0MC4yIDAgMCAwIDI1LTguNyA0MC4xIDQwLjEgMCAwIDAgMTUuNS0zMS44aDYuNVpNNjEgNzMuMmM1LjkgMCAxMS40IDEuNSAxNi4xIDQuMmguMUEzMi45IDMyLjkgMCAwIDEgOTQgMTA2aC02LjVhMjYuNSAyNi41IDAgMCAwLTUzIDBoLTYuNEEzMi44IDMyLjggMCAwIDEgNjEgNzMuMlptNjEtNTQuNWMwIDE3LjEtNy4xIDMyLjYtMTguNSA0My43QTYwLjYgNjAuNiAwIDAgMSAxMjIgMTA2aC02LjRhNTQuMyA1NC4zIDAgMCAwLTIyLTQzLjYgNTQuMyA1NC4zIDAgMCAwIDIyLTQzLjZoNi40Wm0tMzUuNCA0OEE0Ni43IDQ2LjcgMCAwIDEgMTA4IDEwNmgtNi40YTQwLjQgNDAuNCAwIDAgMC0yMi0zNmMyLjQtLjggNC44LTIgNy4xLTMuMVptLTUyLjEtNDhhMjYuNSAyNi41IDAgMCAwIDUzIDBoNi40YTMyLjggMzIuOCAwIDAgMS00OSAyOC42aC0uMUEzMi45IDMyLjkgMCAwIDEgMjggMTguN2g2LjVaTTYxIDBhMTcuMiAxNy4yIDAgMSAxIDAgMzQuNEExNy4yIDE3LjIgMCAwIDEgNjEgMFptMCA2LjRhMTAuOCAxMC44IDAgMSAwIDEwLjggMTAuOGMwLTYtNC45LTEwLjgtMTAuOC0xMC44WiIvPjwvc3ZnPgo=',
			91
		);
	}

	public function enqueue_admin_assets($hook): void {
		// Only load on our plugin's admin pages
		if ($hook !== 'settings_page_fcrm-enhancements') {
			return;
		}

		wp_enqueue_style(
			'fcrm-enhancement-admin',
			FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/css/admin/module-pages.css',
			[],
			FCRM_ENHANCEMENT_SUITE_VERSION
		);

		wp_enqueue_script(
			'fcrm-enhancement-admin',
			FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/js/admin/admin-scripts.js',
			['jquery'],
			FCRM_ENHANCEMENT_SUITE_VERSION,
			true
		);
		
		// Enqueue UI styling admin assets when on UI styling tab
		$current_tab = $_GET['tab'] ?? 'dashboard';
		if ($current_tab === 'ui_styling' && isset($this->modules['ui_styling'])) {
			$this->modules['ui_styling']->enqueue_admin_assets();
		}
		
	}

	public function enqueue_frontend_assets(): void {
		// Only enqueue cache controls on tribute-related pages for admin users
		if (current_user_can('manage_options') && $this->is_tribute_related_page()) {
			wp_enqueue_script(
				'fcrm-cache-controls',
				FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/js/admin/cache-controls.js',
				['jquery'],
				FCRM_ENHANCEMENT_SUITE_VERSION,
				true
			);
			
			// Localize script with nonce
			wp_localize_script('fcrm-cache-controls', 'fcrmCacheNonce', wp_create_nonce('fcrm_clear_cache'));
		}
		
		// NOTE: Individual modules handle their own asset loading via wp_enqueue_scripts hooks
		// This prevents unnecessary function calls on non-tribute pages
	}

	/**
	 * Standardised tribute page detection method
	 * Used by all modules for consistent page detection
	 * Based on user's proven working logic
	 */
	private static $is_tribute_page_cache = null;
	
	public static function is_tribute_page(): bool {
		// Use caching like the original working method
		if (self::$is_tribute_page_cache !== null) {
			return self::$is_tribute_page_cache;
		}

		$is_tribute = false;

		// Check for tribute single post type (user's proven working logic)
		if (isset($_GET['id']) && is_singular() && get_post_type() === 'tribute') {
			$is_tribute = true;
		}

		// Check if we're on the designated tribute search page (user's working logic)
		$search_page_id = get_option('fcrm_tributes_search_page_id');
		if (!$is_tribute && $search_page_id && is_page($search_page_id)) {
			// Additional validation: prevent common WordPress pages from being treated as tribute search pages
			$current_page = get_post($search_page_id);
			$page_slug = $current_page ? $current_page->post_name : '';
			
			// Don't treat default WordPress pages as tribute search pages
			$excluded_pages = ['sample-page', 'hello-world', 'privacy-policy'];
			if (!in_array($page_slug, $excluded_pages)) {
				$is_tribute = true;
			}
		}

		// Check for tribute shortcodes (user's working logic)
		if (!$is_tribute && self::has_tribute_shortcode()) {
			$is_tribute = true;
		}

		// Cache the result like the original working method
		self::$is_tribute_page_cache = $is_tribute;
		return $is_tribute;
	}

	/**
	 * Check if current post contains tribute shortcodes
	 * Based on user's proven working logic
	 */
	private static function has_tribute_shortcode(): bool {
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
		$has_shortcode = preg_match('/' . $pattern . '/', $post->post_content);
		
		return $has_shortcode;
	}

	private function is_tribute_related_page(): bool {
		// Use the standardised method
		return self::is_tribute_page();
	}

	public function render_admin_page(): void {
		$current_tab = $_GET['tab'] ?? 'dashboard';
		
		if ($current_tab === 'dashboard') {
			$this->render_dashboard();
		} elseif (array_key_exists($current_tab, $this->tabs)) {
			$this->render_legacy_tab_view($current_tab);
		} else {
			$this->render_dashboard();
		}
	}

	private function render_dashboard(): void {
		?>
		<div class="wrap">
			<!-- Modern Header for Dashboard -->
			<div class="fcrm-module-header">
				<div class="header-content">
					<div class="header-left">
						<h1><?php _e('FireHawk Tributes Enhancement Suite', 'fcrm-enhancement-suite'); ?></h1>
						<p class="plugin-description">
							<?php _e('Optimise and enhance your funeral website with performance improvements, custom styling, and modern layouts for the FireHawk Tributes plugin.', 'fcrm-enhancement-suite'); ?>
						</p>
					</div>
					<div class="header-right">
						<div class="plugin-logo">
							<img src="<?php echo esc_url(FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/icon-256x256.png'); ?>" 
								 alt="<?php esc_attr_e('HumanKind - Funeral Websites by Weave', 'fcrm-enhancement-suite'); ?>" 
								 class="logo-image" />
						</div>
					</div>
				</div>
			</div>
			
			<div class="fcrm-module-content">
				<div class="dashboard-section" style="padding-top: 3rem;">
					<h2><?php _e('Enhancement Modules', 'fcrm-enhancement-suite'); ?></h2>
					<p class="section-description"><?php _e('Manage your tribute enhancements and integrations. Each module can be configured independently to match your website\'s needs.', 'fcrm-enhancement-suite'); ?></p>
				</div>
				
				<!-- Module Cards -->
				<div class="module-grid">
					<?php $this->render_enhanced_module_card('optimisation', 'Performance Optimisations', 'Improve site speed by optimising scripts, enabling caching, and optimising asset loading.', ['API Response Caching', 'Script Optimisation', 'Flower Delivery Disabling', 'Redis Cache Support']); ?>
					
					<?php $this->render_enhanced_module_card('layouts', 'Modern Layout Templates', 'Modern, card-based grid and single tribute layouts that are mobile-responsive and accessible. Enables our enhanced layouts system.', ['4 Grid Layouts', '2 Single Tribute Layouts', 'Mobile Responsive', 'WCAG 2.1 AA Compliant']); ?>

					<?php $this->render_enhanced_module_card('ui_styling', 'Universal UI Styling', 'Professional styling system for modern layouts with typography, colours, and spacing controls. Only works when Layouts module is enabled.', ['Custom Colour Controls', 'Typography Settings', 'Layout-Specific Options', 'Live CSS Generation']); ?>

					<?php $this->render_enhanced_module_card('seo_analytics', 'SEO & Analytics', 'Privacy-focused analytics and enhanced SEO integration for tribute pages with Plausible and SEOPress.', ['Plausible Analytics Integration', 'SEOPress SEO Support', 'Social Media Meta Tags', 'GDPR Compliant Analytics']); ?>

					<?php $this->render_enhanced_module_card('styling', 'FireHawk Layout Styling', 'Style the original FireHawk tribute layout with custom colours and borders. Only works when Layouts module is disabled.', ['Colour Customisation', 'Button Styling', 'Border Radius Control', 'Grid & Card Styling']); ?>
				</div>
				
				<!-- External Modules Section -->
				<?php if (empty($external_modules)): ?>
					<div class="external-modules-section">
						<h3>🔧 Extensible Framework</h3>
						<div class="no-external-modules">
							<p class="framework-description">
								This Enhancement Suite is built as an extensible framework for developers who want to create additional 
								enhancements for FireHawkCRM Tributes plugin. The modular architecture allows for seamless integration of custom functionality.
							</p>
							<p class="contact-info">
								<strong>Need a custom enhancement for your funeral website or Firehawk tribute display?</strong><br>
								Our team at Human Kind Funeral Websites specialises in funeral website development and can create bespoke 
								enhancements tailored to your specific requirements.
							</p>
							<div class="contact-actions">
								<a href="mailto:support@humankindwebsites.com" class="contact-button" target="_blank">
									Contact Human Kind Funeral Websites
								</a>
								<a href="https://humankindwebsites.com" class="learn-more-button" target="_blank">
									Learn More About Our Services
								</a>
							</div>
						</div>
					</div>
				<?php else: ?>
					<div class="external-modules-section">
						<h3>🔧 External Modules</h3>
						<div class="external-modules-grid">
							<?php foreach ($external_modules as $key => $module): ?>
								<div class="module-card external">
									<div class="module-header">
										<h4><?php echo esc_html($module['name']); ?></h4>
										<span class="module-version"><?php echo esc_html($module['version']); ?></span>
									</div>
									<div class="module-description">
										<p><?php echo esc_html($module['description']); ?></p>
									</div>
									<div class="module-toggle">
										<form method="post" style="display: inline;">
											<?php wp_nonce_field('fcrm_toggle_external_module', 'fcrm_external_toggle_nonce'); ?>
											<input type="hidden" name="external_module_key" value="<?php echo esc_attr($key); ?>">
											<input type="hidden" name="action" value="<?php echo $module['enabled'] ? 'disable' : 'enable'; ?>">
											<button type="submit" class="external-module-toggle <?php echo $module['enabled'] ? 'enabled' : 'disabled'; ?>">
												<?php echo $module['enabled'] ? 'Disable' : 'Enable'; ?>
											</button>
										</form>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
				
				<!-- Two Column Layout for About and Caching Compatibility -->
				<div class="two-column-layout">
					<div class="column">
						<div class="info-card">
							<h3>About This Plugin</h3>
							<p>This enhancement suite is developed by <strong>Human Kind Funerals</strong> and <strong>Weave Digital Studio</strong> to extend the functionality of the FireHawkCRM Tributes plugin.</p>
							<p>Features include modern responsive layouts, performance optimisations, comprehensive caching, and integrated SEO & analytics tools.</p>
							<ul class="feature-list">
								<li>✅ Modern Grid Layouts with Mobile-First Design</li>
								<li>✅ API Response Caching (Redis Support)</li>
								<li>✅ Integrated Plausible Analytics</li>
								<li>✅ Enhanced SEOPress Integration</li>
								<li>✅ Performance Optimisations</li>
								<li>✅ Professional Admin Interface</li>
							</ul>
						</div>
					</div>
					<div class="column">
						<div class="info-card">
							<h3>Caching Compatibility</h3>
							<p>Our intelligent API caching system is designed to work with various hosting environments:</p>
							<ul class="compatibility-list">
								<li>✅ <strong>Redis Object Cache</strong> - Optimal performance with Redis-enabled hosting</li>
								<li>✅ <strong>WordPress Transients</strong> - Reliable fallback for standard hosting</li>
								<li>✅ <strong>VPS & Cloud Hosts</strong> - Full Redis integration with NGINX page & object cache</li>
								<li>⚠️ <strong>Managed WordPress Hosts</strong> - May work with their caching systems (untested)</li>
								<li>✅ <strong>Standard WordPress</strong> - Works on any WordPress installation</li>
								<li>✅ <strong>Multisite Networks</strong> - Fully compatible with WordPress multisite</li>
							</ul>
							<p class="performance-note">
								<strong>Performance:</strong> Experience significant page load improvements with our caching system when properly configured.
							</p>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Full Width Support Section -->
			<div class="plugin-support-full">
				<div class="support-content">
					<h3><?php _e('Need Help?', 'fcrm-enhancement-suite'); ?></h3>
					<p>
						<?php
						printf(
							/* Translators: %1$s is a mailto link, %2$s is a GitHub link. */
							__('Contact our support team at %1$s or log an issue on %2$s for technical assistance.', 'fcrm-enhancement-suite'),
							'<a href="mailto:support@weave.co.nz?subject=FH Tribute Enhancement Plugin Support">support@weave.co.nz</a>',
							'<a href="https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/issues" target="_blank">GitHub</a>'
						);
						?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
	
	private function render_enhanced_module_card(string $module_key, string $module_name, string $description, array $features): void {
		$enabled = get_option('fcrm_module_' . $module_key . '_enabled', false);
		$module_url = add_query_arg([
			'page' => 'fcrm-enhancements',
			'tab' => $module_key
		], admin_url('admin.php'));
		
		// Get appropriate icon
		$icons = [
			'optimisation' => '⚡',
			'styling' => '🎨', 
			'ui_styling' => '🎛️',
			'layouts' => '📱',
			'seo_analytics' => '📊'
		];
		$icon = $icons[$module_key] ?? '⚙️';
		
		?>
		<div class="enhanced-module-card <?php echo $enabled ? 'active' : ''; ?>">
			<div class="module-status">
				<?php echo $enabled ? 'ACTIVE' : 'INACTIVE'; ?>
			</div>
			<div class="module-icon">
				<?php echo $icon; ?>
			</div>
			<div class="module-content">
				<h3><?php echo esc_html($module_name); ?></h3>
				<p class="module-description"><?php echo esc_html($description); ?></p>
				
				<ul class="module-features">
					<?php foreach ($features as $feature): ?>
						<li>✓ <?php echo esc_html($feature); ?></li>
					<?php endforeach; ?>
				</ul>
				
				<div class="module-actions">
					<form method="post" class="module-toggle-form">
						<?php wp_nonce_field('fcrm_toggle_module', 'fcrm_toggle_nonce'); ?>
						<input type="hidden" name="module_key" value="<?php echo esc_attr($module_key); ?>">
						<input type="hidden" name="action" value="<?php echo $enabled ? 'disable' : 'enable'; ?>">
						<label class="toggle-switch">
							<input type="checkbox" <?php checked($enabled); ?> onchange="this.form.submit();">
							<span class="toggle-slider"></span>
						</label>
					</form>
					
					<?php if ($enabled): ?>
						<a href="<?php echo esc_url($module_url); ?>" class="button button-primary">
							<?php _e('Configure', 'fcrm-enhancement-suite'); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function activate(): void {
		// Activation logic
		flush_rewrite_rules();
	}

	public function deactivate(): void {
		// Deactivation logic
		flush_rewrite_rules();
	}

	/**
	 * Register settings for all modules (even if disabled)
	 * This ensures settings can be saved when configuring modules
	 */
	public function register_all_module_settings(): void {
		// Prevent duplicate registration
		static $registered = false;
		if ($registered) {
			return;
		}
		$registered = true;
		
		// Create instances for ALL modules to register settings (regardless of enabled status)
		$module_files = [
			'optimisation' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-optimisation-module.php',
			'layouts' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-layouts-module.php',
			'ui_styling' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-ui-styling-module.php',
			'styling' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-styling-module.php',
			'seo_analytics' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-seo-analytics-module.php'
		];

		foreach ($module_files as $key => $file) {
			if (file_exists($file)) {
				require_once $file;
				
				// Handle special naming for modules
				if ($key === 'seo_analytics') {
					$class_name = 'FCRM_SEO_Analytics_Module';
				} else {
					$class_name = 'FCRM_' . ucfirst($key) . '_Module';
				}
				
				if (class_exists($class_name)) {
					// Create instance just for settings registration
					$temp_instance = new $class_name();
					// The constructor will handle registering settings via admin_init hook
				}
			}
		}
	}

	/**
	 * Render settings for a module even if not enabled
	 */
	private function render_module_settings_fallback(string $module_key): void {
		echo "<!-- Debug: Fallback rendering for module: $module_key -->\n";
		
		$module_files = [
			'optimisation' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-optimisation-module.php',
			'layouts' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-layouts-module.php',
			'ui_styling' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-ui-styling-module.php',
			'styling' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-styling-module.php',
			'seo_analytics' => FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR . 'includes/class-seo-analytics-module.php'
		];

		if (isset($module_files[$module_key]) && file_exists($module_files[$module_key])) {
			echo "<!-- Debug: Module file found: " . $module_files[$module_key] . " -->\n";
			require_once $module_files[$module_key];
			
			// Handle special naming for modules
			if ($module_key === 'seo_analytics') {
				$class_name = 'FCRM_SEO_Analytics_Module';
			} else {
				$class_name = 'FCRM_' . ucfirst($module_key) . '_Module';
			}
			
			echo "<!-- Debug: Looking for class: $class_name -->\n";
			echo "<!-- Debug: Class exists: " . (class_exists($class_name) ? 'YES' : 'NO') . " -->\n";
			
			if (class_exists($class_name)) {
				$temp_instance = new $class_name();
				echo "<!-- Debug: Instance created -->\n";
				// Register settings for this module if method exists
				if (method_exists($temp_instance, 'register_settings')) {
					$temp_instance->register_settings();
					echo "<!-- Debug: Settings registered -->\n";
				}
				if (method_exists($temp_instance, 'render_settings')) {
					echo "<!-- Debug: Calling render_settings -->\n";
					$temp_instance->render_settings();
					echo "<!-- Debug: render_settings completed -->\n";
				} else {
					echo "<!-- Debug: render_settings method not found -->\n";
				}
			} else {
				echo "<!-- Debug: Class not found -->\n";
			}
		} else {
			echo "<!-- Debug: Module file not found or doesn't exist -->\n";
		}
	}

	public function handle_module_toggles(): void {
		// Debug logging
		if (isset($_POST['fcrm_toggle_nonce'])) {
		}
		
		// Handle core module toggles
		if (isset($_POST['fcrm_toggle_nonce']) && wp_verify_nonce($_POST['fcrm_toggle_nonce'], 'fcrm_toggle_module')) {
			$module_key = sanitize_text_field($_POST['module_key']);
			$action = sanitize_text_field($_POST['action']);
			
			
			if (array_key_exists($module_key, $this->tabs)) {
				$enabled = ($action === 'enable');
				$result = update_option('fcrm_module_' . $module_key . '_enabled', $enabled);
				
				
				// Add success message
				add_action('admin_notices', function() use ($module_key, $enabled) {
					$status = $enabled ? 'enabled' : 'disabled';
					echo '<div class="notice notice-success is-dismissible"><p>Module "' . esc_html($this->tabs[$module_key]) . '" has been ' . $status . '.</p></div>';
				});
				
				wp_redirect(admin_url('admin.php?page=fcrm-enhancements&updated=1'));
				exit;
			}
		}
		
		// Handle external module toggles
		if (isset($_POST['fcrm_external_toggle_nonce']) && wp_verify_nonce($_POST['fcrm_external_toggle_nonce'], 'fcrm_toggle_external_module')) {
			$module_key = sanitize_text_field($_POST['external_module_key']);
			$action = sanitize_text_field($_POST['action']);
			
			$external_modules = get_option('fcrm_external_modules', []);
			if (isset($external_modules[$module_key])) {
				$external_modules[$module_key]['enabled'] = ($action === 'enable');
				update_option('fcrm_external_modules', $external_modules);
				
				wp_redirect(admin_url('admin.php?page=fcrm-enhancements&updated=1'));
				exit;
			}
		}
	}

	private function render_legacy_tab_view(string $current_tab): void {
		?>
		<div class="wrap">
			<!-- Modern Header for Module Pages -->
			<div class="fcrm-module-header">
				<div class="header-content">
					<div class="header-left">
						<h1><?php echo esc_html($this->tabs[$current_tab]); ?></h1>
						<p class="plugin-description">
							<?php _e('Configure the settings for this specific module. Changes will be saved automatically.', 'fcrm-enhancement-suite'); ?>
						</p>
						<a href="<?php echo esc_url(admin_url('admin.php?page=fcrm-enhancements')); ?>" class="back-to-dashboard">
							← <?php _e('Back to Dashboard', 'fcrm-enhancement-suite'); ?>
						</a>
					</div>
					<div class="header-right">
						<div class="plugin-logo">
							<img src="<?php echo esc_url(FCRM_ENHANCEMENT_SUITE_PLUGIN_URL . 'assets/images/icon-256x256.png'); ?>" 
								 alt="<?php esc_attr_e('HumanKind - Funeral Websites by Weave', 'fcrm-enhancement-suite'); ?>" 
								 class="logo-image" />
						</div>
					</div>
				</div>
			</div>
			
			<div class="fcrm-module-content">
				<!-- Tab Content -->
				<div class="tab-content">
					<form method="post" action="options.php">
						<?php
						settings_fields('fcrm_enhancement_' . $current_tab);
						
						// Load module for settings rendering even if not enabled
						if (isset($this->modules[$current_tab])) {
							$this->modules[$current_tab]->render_settings();
						} else {
							// Create temporary instance for settings rendering
							$this->render_module_settings_fallback($current_tab);
						}
						submit_button(__('Save Changes', 'fcrm-enhancement-suite'));
						?>
					</form>
			
					<?php
					// Add reset button for styling tab outside the main form
					if ($current_tab === 'styling'): ?>
						<form method="post" style="margin-top: 20px;">
							<?php wp_nonce_field('fcrm_reset_colors', 'fcrm_reset_colors_nonce'); ?>
							<input type="hidden" name="fcrm_reset_colors" value="1" />
							<?php submit_button(__('Reset All Colors to Defaults', 'fcrm-enhancement-suite'), 'secondary', 'reset_colors', false); ?>
						</form>
					<?php endif; ?>
				</div>
				
				<!-- Support Section -->
				<div class="plugin-support">
					<h3><?php _e('Need Help?', 'fcrm-enhancement-suite'); ?></h3>
					<p>
						<?php
						printf(
							/* Translators: %1$s is a mailto link, %2$s is a GitHub link. */
							__('Need help or have a request? Contact our support team at %1$s or log an issue on %2$s.', 'fcrm-enhancement-suite'),
							'<a href="mailto:support@weave.co.nz?subject=FH Tribute Enhancement Plugin Support">support@weave.co.nz</a>',
							'<a href="https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/issues" target="_blank">GitHub</a>'
						);
						?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_clear_cache(): void {
		// Verify nonce and permissions
		if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'fcrm_clear_cache')) {
			wp_die('Unauthorised');
		}

		$success = false;
		if (class_exists('FCRM\\EnhancementSuite\\Cache_Manager')) {
			$success = \FCRM\EnhancementSuite\Cache_Manager::clear_all_cache();
		}

		wp_send_json([
			'success' => $success,
			'message' => $success ? 'Cache cleared successfully' : 'Failed to clear cache'
		]);
	}

	public function ajax_get_cache_stats(): void {
		if (!current_user_can('manage_options')) {
			wp_die(__('Insufficient permissions'));
		}

		if (!class_exists('FCRM\\EnhancementSuite\\Cache_Manager')) {
			wp_send_json_error('Cache manager not available');
		}

		$stats = \FCRM\EnhancementSuite\Cache_Manager::get_cache_stats();
		wp_send_json_success($stats);
	}

	/**
	 * Check if current page content contains layout="firehawk" passthrough
	 *
	 * @return bool True if page has FireHawk passthrough shortcode
	 */
	private function page_has_firehawk_passthrough(): bool {
		global $post;

		if (!$post) {
			return false;
		}

		// Check if post content contains layout="firehawk" or layout='firehawk'
		if (stripos($post->post_content, 'layout="firehawk"') !== false ||
		    stripos($post->post_content, "layout='firehawk'") !== false) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a page builder is currently active
	 *
	 * @return bool True if page builder frontend editor is active
	 */
	private function is_page_builder_active(): bool {
		// Beaver Builder
		if (isset($_GET['fl_builder']) || (class_exists('FLBuilderModel') && \FLBuilderModel::is_builder_active())) {
			return true;
		}

		// Elementor
		if (isset($_GET['elementor-preview']) || (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->preview->is_preview_mode())) {
			return true;
		}

		// Divi Builder
		if (isset($_GET['et_fb']) || function_exists('et_fb_is_enabled') && et_fb_is_enabled()) {
			return true;
		}

		// Oxygen Builder
		if (isset($_GET['ct_builder']) || (defined('CT_VERSION') && isset($_GET['oxygen_iframe']))) {
			return true;
		}

		// Bricks Builder
		if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
			return true;
		}

		return false;
	}

	/**
	 * Conditionally remove FCRM assets
	 *
	 * Simple logic:
	 * 1. NOT a tribute page (no shortcode, no ?id) → Dequeue ALL Firehawk assets
	 * 2. Using Enhanced Grid layout → Dequeue redundant Firehawk grid assets
	 * 3. Using Firehawk layout OR single tribute → Keep everything (passthrough)
	 */
	public function conditional_fcrm_assets(): void {
		// Don't dequeue anything when page builders are active
		if ($this->is_page_builder_active()) {
			return;
		}

		$active_layout = get_option('fcrm_active_layout', 'modern-grid');

		// More robust single tribute detection - check multiple sources
		global $wp;
		$is_single_tribute = isset($_GET['id']) ||
		                     (get_query_var('id') !== '' && get_query_var('id') !== false) ||
		                     (isset($wp->query_vars['id']) && $wp->query_vars['id'] !== '');

		// Check if we're on a tribute page (includes shortcode check)
		$is_tribute_page = self::is_tribute_page();

		// NOT a tribute page → Remove ALL Firehawk assets
		if (!$is_tribute_page) {
			$this->dequeue_fcrm_assets();
			return;
		}

		// Check if page content contains layout="firehawk" passthrough (for demos/testing)
		$has_firehawk_passthrough = $this->page_has_firehawk_passthrough();

		// If using FireHawk passthrough, keep ALL assets (don't optimize)
		if ($has_firehawk_passthrough) {
			return;
		}

		// Using Enhanced Grid layout (and NOT on single tribute) → Remove redundant grid assets
		if (!$is_single_tribute) {
			$this->dequeue_redundant_fcrm_assets();
			return;
		}

		// Otherwise (single tribute) → Keep all Firehawk assets (passthrough)
		// This preserves Slick carousel for gallery functionality
	}

	/**
	 * Dequeue redundant FCRM assets when Enhanced Layouts are active
	 *
	 * Performance optimization (v2.1.1): When using Enhanced Layouts, we have our own
	 * self-contained grid system with custom CSS/JS. This removes Firehawk's redundant
	 * assets while preserving critical dependencies (Moment.js, Lodash, jQuery, AJAX).
	 *
	 * Expected savings: ~150KB CSS + ~500KB JS = 650KB+ total reduction
	 */
	private function dequeue_redundant_fcrm_assets(): void {
		// Redundant CSS files - Enhanced Layouts provide their own styling
		$redundant_styles = [
			'fcrm-tributes-glidejs-core',      // Carousel - not used in Enhanced Layouts
			'fcrm-tributes-glidejs-theme',     // Carousel theme - not used
			'jquery-slick-nav',                 // Mobile nav - not used
			'select2',                          // Dropdown styling - we use Flatpickr
			'add-to-calendar-button',           // Calendar widget - not in grid layouts
			'fcrm-tributes-jquery-modal',       // Modal styles - not used in grids
			'fcrm-tributes-lightgallery-css',   // Lightbox - not in grid layouts
			'fcrm-tributes'                     // Main Firehawk CSS (46.3KB) - we have our own
		];

		// Redundant JavaScript files - Enhanced Layouts implement own functionality
		$redundant_scripts = [
			'fcrm-tributes-popperjs',           // Tooltips - not used
			'fcrm-tributes-tippyjs',            // Tooltip library - not used
			'fontawesome',                      // Icons - redundant
			'bootstrap',                        // Grid system - we have CSS Grid
			'shufflejs',                        // Grid filtering - we have own search
			'jquery-history',                   // History API - not needed
			'jquery-validate',                  // Form validation - no forms in grids
			'select2',                          // Dropdown JS - we use Flatpickr instead
			'jquery-slick-carousel',            // Carousel - not used
			'fcrm-tributes-clipboard',          // Copy functionality - not in grids
			'fcrm-tributes-textFit',            // Text fitting - not needed
			'fcrm-tributes-glidejs',            // Carousel library - not used
			'fcrm-tributes-jquery-modal',       // Modal JS - not used
			'fcrm-tributes-litepicker',         // Datepicker - we use Flatpickr
			'add-to-calendar-button',           // Calendar widget - not in grids
			'_',                                // Lodash (25.8KB) - not used in grid layouts
			// NOTE: 'fcrm-tributes' MUST be kept - it provides ajax_var localization we need
			'fcrm-tributes-lightgallerys',      // Lightbox - not in grids
			'fcrm-tributes-tribute-messages',   // Messages tab - single tribute only
			'fcrm-tributes-tributes-page',      // Single tribute page - not grid
			'fcrm-tributes-tribute-trees',      // Trees tab - single tribute only
			'fcrm-tributes-tribute-donations',  // Donations tab - single tribute only
			'fcrm-tributes-tributes-grid',      // Firehawk grid JS - we replace entirely
			'fcrm-tributes-verify-input',       // Input verification - no forms
			'lg-pager',                         // Lightbox pager - not used
			'lg-zoom',                          // Lightbox zoom - not used
			'fcrm-tributes-flower-delivery'     // Flowers - disabled separately
		];

		// NOTE: We KEEP these critical Firehawk dependencies for grid layouts:
		// - momentScript (Moment.js) - REQUIRED: Date formatting, filtering, service date checks
		// - fcrm-tributes (fcrm-tributes-public.js) - REQUIRED: Provides ajax_var localization
		// - jquery - Core dependency
		//
		// We REMOVE on grid pages:
		// - _ (Lodash) - Not used in Enhanced Grid layouts (saves 25.8KB)

		// Dequeue redundant styles
		foreach ($redundant_styles as $handle) {
			if (wp_style_is($handle, 'enqueued')) {
				wp_dequeue_style($handle);
			}
		}

		// Dequeue redundant scripts
		foreach ($redundant_scripts as $handle) {
			if (wp_script_is($handle, 'enqueued')) {
				wp_dequeue_script($handle);
			}
		}

		// NOTE: We cannot remove sharer.min.js on grid pages because Firehawk uses
		// the same handle 'fcrm-tributes' for both sharer.min.js and fcrm-tributes-public.js
		// The second enqueue (fcrm-tributes-public.js) should overwrite the first,
		// but if both are loading, we must keep them to preserve ajax_var localization.
		// Attempted savings: 3.5KB (sharer.min.js) - cannot be achieved due to Firehawk's duplicate handle bug
	}

	/**
	 * Dequeue all FCRM plugin assets (for non-tribute pages)
	 */
	private function dequeue_fcrm_assets(): void {
		// FCRM CSS files (from class-fcrm-tributes-public.php)
		$fcrm_styles = [
			'fcrm-tributes-glidejs-core',
			'fcrm-tributes-glidejs-theme', 
			'jquery-slick-nav',
			'select2',
			'add-to-calendar-button',
			'fcrm-tributes-jquery-modal',
			'fcrm-tributes-lightgallery-css',
			'fcrm-tributes'
		];

		// FCRM JavaScript files (from class-fcrm-tributes-public.php)
		$fcrm_scripts = [
			'fcrm-tributes-popperjs',
			'fcrm-tributes-tippyjs',
			'fontawesome',
			'bootstrap',
			'shufflejs',
			'jquery-history',
			'jquery-validate',
			'select2',
			'jquery-slick-carousel',
			'fcrm-tributes-clipboard',
			'fcrm-tributes-textFit',
			'fcrm-tributes-glidejs',
			'fcrm-tributes-jquery-modal',
			'fcrm-tributes-litepicker',
			'add-to-calendar-button',
			'fcrm-tributes', // This handle appears twice in FCRM code
			'_', // Lodash
			'momentScript',
			'fcrm-tributes-lightgallerys',
			'fcrm-tributes-tribute-messages',
			'fcrm-tributes-tributes-page',
			'fcrm-tributes-tribute-trees',
			'fcrm-tributes-tribute-donations',
			'fcrm-tributes-tributes-grid',
			'fcrm-tributes-verify-input',
			'lg-pager',
			'lg-zoom',
			'fcrm-tributes-flower-delivery'
		];

		// Dequeue all FCRM styles
		$dequeued_styles = [];
		foreach ($fcrm_styles as $handle) {
			if (wp_style_is($handle, 'enqueued')) {
				wp_dequeue_style($handle);
				$dequeued_styles[] = $handle;
			}
		}

		// Dequeue all FCRM scripts  
		$dequeued_scripts = [];
		foreach ($fcrm_scripts as $handle) {
			if (wp_script_is($handle, 'enqueued')) {
				wp_dequeue_script($handle);
				$dequeued_scripts[] = $handle;
			}
		}
	}

	/**
	 * Check for required plugin dependencies
	 */
	public function check_plugin_dependencies(): void {
		// Check if FCRM Tributes plugin is active by checking for required classes
		// This works with any plugin slug (fcrm-tributes, fcrm-tributes-2.2.0, fcrm-tributes-2.3.1, etc.)
		$required_classes = ['Fcrm_Tributes_Api', 'Single_Tribute'];
		$fcrm_tributes_active = true;

		foreach ($required_classes as $class) {
			if (!class_exists($class)) {
				$fcrm_tributes_active = false;
				break;
			}
		}

		if (!$fcrm_tributes_active) {
			// Not installed or not activated
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p><strong>FireHawk Tributes Enhancement Suite:</strong> This plugin requires the FireHawkCRM Tributes plugin to function.</p>';
			echo '<p>Please install and activate a FireHawkCRM Tributes plugin (any version). Without it, this enhancement suite will not work.</p>';
			echo '<p><a href="' . esc_url(admin_url('plugins.php')) . '" class="button button-primary">View Plugins</a></p>';
			echo '</div>';
		}
	}

	/**
	 * Check for conflicts with standalone plugins
	 */
	public function check_plugin_conflicts(): void {
		$conflicts = [];

		// Check for standalone Plausible plugin
		if (in_array('fcrm-plausible-analytics/fcrm-plausible-analytics.php', 
					apply_filters('active_plugins', get_option('active_plugins')))) {
			$conflicts[] = [
				'plugin' => 'FCRM Plausible Analytics',
				'message' => 'The standalone FCRM Plausible Analytics plugin is active. Please deactivate it to avoid conflicts with the integrated SEO & Analytics module.'
			];
		}

		// Check for standalone SEOPress plugin
		if (in_array('fcrm-seopress/fcrm-seopress.php', 
					apply_filters('active_plugins', get_option('active_plugins')))) {
			$conflicts[] = [
				'plugin' => 'FCRM SEOPress Integration',
				'message' => 'The standalone FCRM SEOPress Integration plugin is active. Please deactivate it to avoid conflicts with the integrated SEO & Analytics module.'
			];
		}

		// Display conflict notices
		foreach ($conflicts as $conflict) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php echo esc_html($conflict['plugin']); ?> Conflict:</strong>
					<?php echo esc_html($conflict['message']); ?>
				</p>
			</div>
			<?php
		}
	}
}

// Create instance and class alias for backward compatibility
new FCRM_Enhancement_Suite();

// Alias for modules to reference standardised methods
class_alias('FCRM_Enhancement_Suite', 'FCRM_Tributes_Enhancement_Suite'); 
