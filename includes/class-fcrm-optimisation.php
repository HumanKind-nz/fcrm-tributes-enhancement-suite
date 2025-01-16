<?php
namespace FCRM\EnhancementSuite;

/**
 * Optimisation Module 
 * 
 * Handles script optimisation and feature management for FirehawkCRM Tributes.
 * Includes controls for Bootstrap and FontAwesome loading, along with general
 * script optimization for better performance.
 */
class Optimisation extends Enhancement_Base {
	/**
	 * Static cache for tribute page detection
	 *
	 * @var bool|null
	 */
	private $is_tribute_page = null;

	/**
	 * Flower delivery disabler instance
	 *
	 * @var Flower_Delivery_Disabler|null
	 */
	private $flower_disabler = null;

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

		// Initialize flower delivery disabler if needed
		if ($this->get_option('disable_flowers')) {
			$this->flower_disabler = new Flower_Delivery_Disabler();
			$this->flower_disabler->init();
		}

		// Add optimization hooks
		add_action('wp_enqueue_scripts', [$this, 'optimise_assets'], 999);
		add_action('wp_head', [$this, 'remove_hardcoded_scripts'], 0);
		add_filter('wp_resource_hints', [$this, 'remove_dns_prefetch'], 10, 2);
	}

	/**
	 * Get default value for option
	 */
	protected function get_default_value(string $key): mixed {
		$defaults = [
			'enabled' => true,
			'disable_flowers' => true,
			'disable_bootstrap' => false,
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

		register_setting(
			'fcrm_enhancement_optimisation',
			$this->get_option_prefix() . 'disable_bootstrap',
			[
				'type' => 'boolean',
				'default' => false,
				'sanitize_callback' => [$this, 'sanitise_boolean']
			]
		);
	}

	/**
	 * Check if current page is tribute-related
	 */
	protected function is_tribute_page(): bool {
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
	 */
	protected function has_tribute_shortcode(): bool {
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
	 * Optimise asset loading
	 */
	public function optimise_assets(): void {
		if (!class_exists('Fcrm_Tributes_Public')) {
			return;
		}
	
		global $wp_scripts, $wp_styles;

		// Handle plugin's bootstrap.js through the disable_bootstrap option
		if ($this->get_option('disable_bootstrap')) {
			if (isset($wp_scripts->registered) && is_array($wp_scripts->registered)) {
				foreach ($wp_scripts->registered as $handle => $script) {
					if (strpos($script->src, '/fcrm-tributes/public/js/bootstrap') !== false) {
						wp_dequeue_script($handle);
						wp_deregister_script($handle);
					}
				}
			}
		}

		// If we're on a tribute page, we're done
		if ($this->is_tribute_page()) {
			return;
		}

		// If we're not on a tribute page, remove all tribute-related scripts and styles
		$scripts_to_remove = [
			'bootstrapjs',         // Bootstrap from tributes plugin
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

		// Remove each script individually
		foreach ($scripts_to_remove as $handle) {
			if (wp_script_is($handle, 'registered')) {
				wp_deregister_script($handle);
			}
			if (wp_script_is($handle, 'enqueued')) {
				wp_dequeue_script($handle);
			}
		}

		// Define styles to remove
		$styles_to_remove = [
			'select2',
			'jquery-slick-nav',
			'add-to-calendar-button'
		];

		// Remove each style individually
		foreach ($styles_to_remove as $handle) {
			if (wp_style_is($handle, 'registered')) {
				wp_deregister_style($handle);
			}
			if (wp_style_is($handle, 'enqueued')) {
				wp_dequeue_style($handle);
			}
		}

		// Remove scripts by URL pattern or handle pattern
		if (isset($wp_scripts->registered) && is_array($wp_scripts->registered)) {
			foreach ($wp_scripts->registered as $handle => $script) {
				if (
					strpos($handle, 'fcrm') !== false || 
					strpos($script->src, 'unpkg.com') !== false || 
					strpos($script->src, 'kit.fontawesome.com') !== false ||
					strpos($script->src, 'bootstrap.js') !== false
				) {
					wp_dequeue_script($handle);
					wp_deregister_script($handle);
				}
			}
		}

		// Remove styles by handle pattern
		if (isset($wp_styles->registered) && is_array($wp_styles->registered)) {
			foreach ($wp_styles->registered as $handle => $style) {
				if (strpos($handle, 'fcrm') !== false) {
					wp_dequeue_style($handle);
					wp_deregister_style($handle);
				}
			}
		}
	}

	/**
	 * Remove DNS prefetch hints
	 */
	public function remove_dns_prefetch(array $hints, string $relation_type): array {
		if ($relation_type === 'dns-prefetch') {
			return array_filter($hints, function ($hint) {
				return !in_array($hint, [
					'//unpkg.com',
					'//kit.fontawesome.com'
				]);
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
			$patterns = [
				'#<script[^>]*src=["\']https://unpkg\.com/(?:@popperjs/core@2|tippy\.js@6)[^>]*></script>#',
				'#<script[^>]*src=["\']https://kit\.fontawesome\.com/[^>]*></script>#'
			];
			return preg_replace($patterns, '', $output);
		});
	}

	/**
	 * Render module settings page
	 */
	public function render_settings(): void {
		$this->render_common_settings();
		?>
		<p style="margin-top:0px;"><?php echo esc_html__('Enabling this will improve site performance by restricting all the scripts and style sheets associated with the FH Tributes to only load on the required tribute pages and not across the whole site', 'fcrm-enhancement-suite'); ?></p>
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
						<?php echo esc_html__('Improve site performance by disabling all the flower delivery related files from loading across the site. Changes will take effect immediately after saving.', 'fcrm-enhancement-suite'); ?>
					</p>
				</td>
			</tr>
		</table>

		<h3 style="margin-top:30px;"><?php echo esc_html__('Developers Only', 'fcrm-enhancement-suite'); ?></h3>
		<p><?php echo esc_html__('These options are for advanced users only. They were added for our internal debugging. Incorrect settings here may cause display issues.', 'fcrm-enhancement-suite'); ?></p>
		
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__('Disable Plugin Bootstrap JS', 'fcrm-enhancement-suite'); ?></th>
				<td>
					<label>
						<input type="checkbox" 
							   name="<?php echo esc_attr($this->get_option_prefix() . 'disable_bootstrap'); ?>" 
							   value="1" 
							   <?php checked($this->get_option('disable_bootstrap')); ?>>
						<?php echo esc_html__('Disable Bootstrap JS loading from the Tributes plugin', 'fcrm-enhancement-suite'); ?>
					</label>
					<p class="description">
						<?php echo esc_html__('Only enable this if your theme includes Bootstrap JS and you\'re experiencing conflicts. This may affect tribute modal functionality.', 'fcrm-enhancement-suite'); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Module activation tasks
	 */
	public function activate(): void {
		parent::activate();
		add_option($this->get_option_prefix() . 'enabled', true);
		add_option($this->get_option_prefix() . 'disable_flowers', true);
		add_option($this->get_option_prefix() . 'disable_bootstrap', false);
	}

	/**
	 * Module deactivation tasks
	 */
	public function deactivate(): void {
		parent::deactivate();
		delete_option($this->get_option_prefix() . 'enabled');
		delete_option($this->get_option_prefix() . 'disable_flowers');
		delete_option($this->get_option_prefix() . 'disable_bootstrap');
	}
}