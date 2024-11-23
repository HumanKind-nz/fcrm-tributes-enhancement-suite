<?php
namespace FCRM\EnhancementSuite;

/**
 * Optimisation Module 
 * 
 * Handles script optimisation and feature management for FirehawkCRM Tributes.
 */
class Optimisation extends Enhancement_Base {
	/**
	 * Static cache for tribute page detection
	 *
	 * @var bool|null
	 */
	private $is_tribute_page = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('optimisation', 'Performance Optimisations');
	}

	/**
	 * Initialize the module
	 */
	protected function init(): void {
		if (!$this->is_enabled()) {
			return;
		}

		// Handle flower delivery dependencies first
		if ($this->get_option('disable_flowers')) {
			$this->handle_flower_delivery();
		}

		// Add optimization hooks
		add_action('wp_enqueue_scripts', [$this, 'optimise_assets'], 999);
		add_action('wp_head', [$this, 'remove_hardcoded_scripts'], 0);
		add_filter('wp_resource_hints', [$this, 'remove_dns_prefetch'], 10, 2);
	}

	/**
	 * Get default value for option
	 *
	 * @param string $key Option key
	 * @return mixed
	 */
	protected function get_default_value(string $key): mixed {
		$defaults = [
			'enabled' => true,
			'disable_flowers' => true,
		];
		return $defaults[$key] ?? null;
	}

	/**
	 * Register module settings
	 */
	public function register_settings(): void {
		$this->register_common_settings();

		register_setting(
			'fcrm_enhancement_optimisation',
			$this->get_option_prefix() . 'disable_flowers',
			[
				'type' => 'boolean',
				'default' => false,
				'sanitize_callback' => [$this, 'sanitise_boolean']
			]
		);
	}

	/**
	 * Render module settings page
	 */
	public function render_settings(): void {
		$this->render_common_settings();
		?>
		<p style="margin-top:0px;"><?php echo esc_html__('Enabling this will improve site performance by restriciting all the scripts and style sheets associated with the FH Tributes to only load on the required tribute pages and not across the whole site', 'fcrm-enhancement-suite'); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__('Disable Flower Delivery Feature', 'fcrm-enhancement-suite'); ?></th>
				<td>
					<label>
						<input type="checkbox" 
							   name="<?php echo esc_attr($this->get_option_prefix() . 'disable_flowers'); ?>" 
							   value="1" 
							   <?php checked($this->get_option('disable_flowers')); ?>>
						<?php echo esc_html__('Completely disables the flower delivery functionality if it not needed', 'fcrm-enhancement-suite'); ?>
					</label>
					<p class="description">
						<?php echo esc_html__('Improve site performance by disabling all the flower delivery related files from loading across the site. Changes will take effect after saving and refreshing the tribute page.', 'fcrm-enhancement-suite'); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Check if current page is tribute-related
	 *
	 * @return bool
	 */
	private function is_tribute_page(): bool {
		if ($this->is_tribute_page !== null) {
			return $this->is_tribute_page;
		}
	
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
	
		$this->is_tribute_page = $is_tribute;
		return $is_tribute;
	}

	/**
	 * Check if current post contains tribute shortcodes
	 *
	 * @return bool
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
	 * Optimise asset loading (NZ English)
	 */
	public function optimise_assets(): void {
		if (!class_exists('Fcrm_Tributes_Public')) {
			return;
		}
	
		// Skip optimization on tribute pages
		if ($this->is_tribute_page()) {
			return;
		}
	
		global $wp_scripts, $wp_styles;
	
		// Define scripts to remove
		$script_patterns = [
			'shufflejs',
			'jquery-history',
			'jquery-validate',
			'select2',
			'jquery-slick-carousel',
			'add-to-calendar-button',
			'momentScript',
			'lg-pager',
			'lg-zoom',
			'_'
		];
	
		foreach ($wp_scripts->registered as $handle => $script) {
			if (
				strpos($handle, 'fcrm') !== false || 
				strpos($script->src, 'unpkg.com') !== false || 
				in_array($handle, $script_patterns)
			) {
				wp_dequeue_script($handle);
				wp_deregister_script($handle);
			}
		}
	
		$style_patterns = [
			'select2',
			'jquery-slick-nav',
			'add-to-calendar-button'
		];
	
		foreach ($wp_styles->registered as $handle => $style) {
			if (
				strpos($handle, 'fcrm') !== false || 
				in_array($handle, $style_patterns)
			) {
				wp_dequeue_style($handle);
				wp_deregister_style($handle);
			}
		}
	}

	/**
	 * Handle flower delivery dependency removal
	 */
	private function handle_flower_delivery(): void {
		remove_action('plugins_loaded', ['Fcrm_Tributes', 'load_dependencies']);
		add_action('plugins_loaded', [$this, 'custom_load_dependencies'], 20);
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
		$base_path = plugin_dir_path(dirname(FCRM_ENHANCEMENT_FILE)) . 'fcrm-tributes/';
		$required_files = [
			'includes/class-fcrm-tributes-loader.php',
			'includes/class-fcrm-tributes-i18n.php',
			'includes/fcrm-api.php',
			'admin/class-fcrm-tributes-admin.php',
			'public/class-single-tribute-type.php',
			'public/class-fcrm-tributes-public.php'
		];

		foreach ($required_files as $file) {
			if (file_exists($base_path . $file)) {
				require_once $base_path . $file;
			}
		}

		$plugin->get_loader()->run();
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
	 * Remove hardcoded scripts
	 */
	public function remove_hardcoded_scripts(): void {
		if ($this->is_tribute_page()) {
			return;
		}
	
		ob_start(function ($output) {
			$pattern = '#<script[^>]*src=["\']https://unpkg\.com/(?:@popperjs/core@2|tippy\.js@6)[^>]*></script>#';
			return preg_replace($pattern, '', $output);
		});
	}

	/**
	 * Module activation tasks
	 */
	public function activate(): void {
		parent::activate();
		add_option($this->get_option_prefix() . 'enabled', true);
		add_option($this->get_option_prefix() . 'disable_flowers', true);
	}

	/**
	 * Module deactivation tasks
	 */
	public function deactivate(): void {
		parent::deactivate();
		delete_option($this->get_option_prefix() . 'enabled');
		delete_option($this->get_option_prefix() . 'disable_flowers');
	}
}