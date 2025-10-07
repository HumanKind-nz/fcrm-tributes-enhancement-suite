<?php
namespace FCRM\EnhancementSuite;

/**
 * Loader Module
 * 
 * Handles loading animation functionality for FirehawkCRM Tributes.
 */
class Loader extends Enhancement_Base {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('loader', 'Loading Animation');
	}

	/**
	 * Initialize the module
	 */
	protected function init(): void {
		if (!$this->is_enabled()) {
			return;
		}

		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
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
			'spinner_color' => '#b1a357'
		];
		return $defaults[$key] ?? null;
	}

	/**
	 * Register module settings
	 */
	public function register_settings(): void {
		$this->register_common_settings();
	
		register_setting(
			'fcrm_enhancement_loader',
			$this->get_option_prefix() . 'spinner_color',
			[
				'type' => 'string',
				'default' => '#b1a357',
				'sanitize_callback' => 'sanitize_hex_color',
				'show_in_rest' => true,
			]
		);
	}

	/**
	 * Render module settings page
	 */
	public function render_settings(): void {
		?>
		<div class="wrap">
			<form method="post" action="options.php">
				<?php
				settings_fields('fcrm_enhancement_loader');
				$this->render_common_settings();
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__('Spinner Colour', 'fcrm-enhancement-suite'); ?></th>
						<td>
							<input type="text" 
								   name="<?php echo esc_attr($this->get_option_prefix() . 'spinner_color'); ?>" 
								   value="<?php echo esc_attr($this->get_option('spinner_color')); ?>" 
								   class="color-picker" 
								   data-alpha="true" />
							<p class="description">
								<?php 
								echo wp_kses(
									__('Select the spinner colour that complements your site’s design.<br><br>This feature adds a loading animation displayed while the tribute data is being fetched via the API. <br>It enhances the user experience by visually indicating progress, reducing the likelihood of users navigating away.', 'fcrm-enhancement-suite'),
									['br' => []] 
								); 
								?>
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
	 * Check if current page needs loader
	 *
	 * @return bool
	 */
	private function needs_loader(): bool {
		// Check if we're on the designated tribute search page
		if (is_page(get_option('fcrm_tributes_search_page_id'))) {
			return true;
		}

		// Check for specific grid shortcodes
		global $post;
		if ($post && is_a($post, 'WP_Post')) {
			$shortcodes = [
				'show_crm_tributes_grid',
				'show_crm_tributes_large_grid'
			];
			
			$pattern = get_shortcode_regex($shortcodes);
			if (preg_match('/' . $pattern . '/', $post->post_content)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets(string $hook_suffix): void {
		if ('toplevel_page_fcrm-enhancements' !== $hook_suffix) {
			return;
		}
	
		// Color picker is already enqueued by parent class
		wp_add_inline_script(
			'wp-color-picker',
			'
			jQuery(document).ready(function($) {
				$(".color-picker").wpColorPicker({
					change: function(event, ui) {
						// Optional: Add live preview functionality
						$(this).val(ui.color.toString());
						$(this).trigger("change");
					}
				});
			});
			'
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets(): void {
		if (!$this->needs_loader()) {
			return;
		}

		// Enqueue loader script
		wp_enqueue_script(
			'fcrm-loader',
			FCRM_ENHANCEMENT_URL . 'assets/js/frontend/loader.js',
			['jquery'],
			FCRM_ENHANCEMENT_VERSION,
			true
		);

		// Enqueue loader styles
		wp_enqueue_style(
			'fcrm-loader',
			FCRM_ENHANCEMENT_URL . 'assets/css/loader.css',
			[],
			FCRM_ENHANCEMENT_VERSION
		);

		// Add inline styles for spinner color
		$spinner_color = $this->get_option('spinner_color');
		if ($spinner_color) {
			wp_add_inline_style(
				'fcrm-loader',
				".loading-animation::after { border-top-color: {$spinner_color}; }"
			);
		}
	}
}