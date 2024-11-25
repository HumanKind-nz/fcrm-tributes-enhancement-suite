<?php
namespace FCRM\EnhancementSuite;

/**
 * Styling Module
 * 
 * Handles custom colour configuration and styling for FirehawkCRM Tributes.
 */
class Styling extends Enhancement_Base {
	/**
	 * Available colour settings with their labels
	 *
	 * @var array
	 */
	private $color_settings = [
		'primary-color'              => 'Primary Colour',
		'secondary-color'            => 'Secondary Colour',
		'primary-button'             => 'Search &amp; Primary Button Colour',
		'primary-button-text'        => 'Search &amp; Primary Button Text Colour',
		'primary-button-hover'       => 'Search &amp; Primary Button Hover Colour',
		'primary-button-hover-text'  => 'Search &amp; Primary Button Hover Text Colour',
		'secondary-button'           => 'Secondary Button Colour',
		'secondary-button-text'      => 'Secondary Button Text Colour',
		'secondary-button-hover'     => 'Secondary Button Hover Colour',
		'secondary-button-hover-text'=> 'Secondary Button Hover Text Colour',
		'focus-border-color'         => 'Grid Card Border Colour',
		'card-background'            => 'Grid Card Background Colour',
		'primary-shadow'             => 'Grid Card Box Shading',
		'focus-shadow-color'         => 'Focus Shadow Colour',
		'link-color'                 => 'Link Colour'
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('styling', 'Custom Styling');
	}

	/**
	 * Initialize the module
	 */
	protected function init(): void {
		if (!$this->is_enabled()) {
			return;
		}

		// Enqueue styles and scripts
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 99);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

		// Hook into admin_init for reset functionality
		add_action('admin_init', [$this, 'handle_reset']);
	}

	/**
	 * Get default value for option
	 *
	 * @param string $key Option key
	 * @return mixed
	 */
	protected function get_default_value(string $key): mixed {
		$defaults = [
			'primary-color'              => '#FFFFFF',
			'secondary-color'            => '#000000',
			'primary-button'             => '#007BFF',
			'primary-button-text'        => '#FFFFFF',
			'primary-button-hover'       => '#0056B3',
			'primary-button-hover-text'  => '#FFFFFF',
			'secondary-button'           => '#6C757D',
			'secondary-button-text'      => '#FFFFFF',
			'secondary-button-hover'     => '#5A6268',
			'secondary-button-hover-text'=> '#FFFFFF',
			'focus-border-color'         => '#007BFF',
			'card-background'            => '#FFFFFF',
			'primary-shadow'             => 'rgba(0, 0, 0, 0.1)',
			'focus-shadow-color'         => '#80BDFF',
			'link-color'                 => '#0000EE',
			'border-radius'              => '6px',
			'grid-border-radius'         => '18px', 
		];
	
		return $defaults[$key] ?? null;
	}

	/**
	 * Register module settings
	 */
	public function register_settings(): void {
		$this->register_common_settings();
	
		// Register color settings
		foreach ($this->color_settings as $key => $label) {
			register_setting(
				'fcrm_enhancement_styling',
				$this->get_option_prefix() . $key,
				[
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'default' => $this->get_default_value($key)
				]
			);
		}
	
		// Register border-radius settings
		register_setting(
			'fcrm_enhancement_styling',
			$this->get_option_prefix() . 'border-radius',
			[
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => $this->get_default_value('border-radius')
			]
		);
	
		register_setting(
			'fcrm_enhancement_styling',
			$this->get_option_prefix() . 'grid-border-radius',
			[
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => $this->get_default_value('grid-border-radius')
			]
		);
	}

	/**
	 * Render module settings page
	 */
	public function render_settings(): void {
		$this->render_common_settings();
		?>
		<form method="post" action="options.php">
			<?php settings_fields('fcrm_enhancement_styling'); ?>
			
			<div class="fcrm-color-settings">
				<h2><?php echo esc_html__('Colour Settings', 'fcrm-enhancement-suite'); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ($this->color_settings as $key => $label): ?>
						<tr>
							<th scope="row"><?php echo esc_html($label); ?></th>
							<td>
								<div class="alpha-color-picker-wrap">
									<input type="text" 
										id="<?php echo esc_attr($this->get_option_prefix() . $key); ?>"
										name="<?php echo esc_attr($this->get_option_prefix() . $key); ?>"
										value="<?php echo esc_attr($this->get_option($key)); ?>"
										class="alpha-color-control"
										data-alpha="true"
										data-show-opacity="true"
									/>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
	
					<!-- New Grid Card Border Radius Field -->
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr($this->get_option_prefix() . 'grid-border-radius'); ?>">
								<?php echo esc_html__('Grid Card Border Radius', 'fcrm-enhancement-suite'); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								id="<?php echo esc_attr($this->get_option_prefix() . 'grid-border-radius'); ?>"
								name="<?php echo esc_attr($this->get_option_prefix() . 'grid-border-radius'); ?>"
								value="<?php echo esc_attr($this->get_option('grid-border-radius') ?? $this->get_default_value('grid-border-radius')); ?>"
								class="regular-text"
							/>
							<p class="description">
								<?php echo esc_html__('Specify the border-radius for grid cards (e.g., 10px, 20px, 0).', 'fcrm-enhancement-suite'); ?>
							</p>
						</td>
					</tr>
	
					<!-- Existing Button Border Radius Field -->
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr($this->get_option_prefix() . 'border-radius'); ?>">
								<?php echo esc_html__('Button Border Radius', 'fcrm-enhancement-suite'); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								id="<?php echo esc_attr($this->get_option_prefix() . 'border-radius'); ?>"
								name="<?php echo esc_attr($this->get_option_prefix() . 'border-radius'); ?>"
								value="<?php echo esc_attr($this->get_option('border-radius') ?? $this->get_default_value('border-radius')); ?>"
								class="regular-text"
							/>
							<p class="description">
								<?php echo esc_html__('Specify the border-radius for buttons (e.g., 10px, 20px, 0).', 'fcrm-enhancement-suite'); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
	
			<?php submit_button(__('Save Settings', 'fcrm-enhancement-suite')); ?>
		</form>
		
		<div class="reset-defaults" style="margin-top: 20px;">
			<form method="post">
				<?php wp_nonce_field('fcrm_reset_to_defaults', 'fcrm_reset_nonce'); ?>
				<input type="hidden" name="fcrm_styling_reset_to_defaults" value="true" />
				<?php submit_button(__('Reset to Defaults', 'fcrm-enhancement-suite'), 'secondary', 'reset-to-defaults', false); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets($hook_suffix): void {
		if ('toplevel_page_fcrm-enhancements' !== $hook_suffix) {
			return;
		}

		// Enqueue WordPress color picker and dependencies
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker', array('jquery'), false, true);
		
		// Enqueue alpha color picker
		wp_enqueue_script(
			'alpha-color-picker',
			FCRM_ENHANCEMENT_URL . 'assets/js/admin/alpha-color-picker.js',
			['wp-color-picker', 'jquery', 'jquery-ui-slider'],
			FCRM_ENHANCEMENT_VERSION,
			true
		);

		// Add custom CSS for alpha slider
		wp_add_inline_style('wp-color-picker', $this->get_alpha_picker_css());

		// Initialize alpha color picker
		wp_add_inline_script(
			'alpha-color-picker',
			$this->get_alpha_picker_init_script(),
			'after'
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets(): void {
		wp_enqueue_style(
			'fcrm-enhancement-styles',
			FCRM_ENHANCEMENT_URL . 'assets/css/enhancement.css',
			[],
			FCRM_ENHANCEMENT_VERSION
		);

		$custom_css = $this->generate_dynamic_css();
		wp_add_inline_style('fcrm-enhancement-styles', $custom_css);
	}

	/**
	 * Generate dynamic CSS with custom variables
	 */
	private function generate_dynamic_css(): string {
		$css = ":root {";
	
		// Add color variables
		foreach ($this->color_settings as $key => $label) {
			$value = $this->get_option($key);
			if (!empty($value)) {
				$css .= "--fcrm-{$key}: {$value};";
			}
		}
	
		// Add border radius
		$border_radius = $this->get_option('border-radius');
		if (!empty($border_radius)) {
			$css .= "--fcrm-border-radius: {$border_radius};";
		}
	
		$grid_border_radius = $this->get_option('grid-border-radius');
		if (!empty($grid_border_radius)) {
			$css .= "--fcrm-grid-border-radius: {$grid_border_radius};";
		}
	
		$css .= "}";
		return $css;
	}

	/**
	 * Get alpha color picker CSS
	 */
	private function get_alpha_picker_css(): string {
		ob_start();
		require FCRM_ENHANCEMENT_PATH . 'assets/css/admin/alpha-picker.css';
		return ob_get_clean();
	}

	/**
	 * Get alpha color picker initialization script
	 */
	private function get_alpha_picker_init_script(): string {
		return "jQuery(document).ready(function($) {
			$('.alpha-color-control').each(function() {
				var \$input = $(this);

				// Avoid duplicate initialization
				if (\$input.data('alphaColorPickerInitialized')) {
					return;
				}

				\$input.data('alphaColorPickerInitialized', true);

				var value = \$input.val();
				var alpha = 100;

				if (value && value.match(/rgba/)) {
					alpha = Math.floor(value.replace(/^.*,(.+)\)/, '$1') * 100);
				}

				\$input.alphaColorPicker({
					clear: function() {
						\$input.val('').trigger('change');
					},
					change: function(event, ui) {
						setTimeout(function() {
							\$input.trigger('change');
						}, 100);
					},
					defaultColor: false,
					showAlpha: true,
					alpha: alpha
				});
			});
		});";
	}

	/**
	 * Handle resetting options
	 */
	public function handle_reset(): void {
		if (!isset($_POST['fcrm_reset_nonce']) || !wp_verify_nonce($_POST['fcrm_reset_nonce'], 'fcrm_reset_to_defaults')) {
			return;
		}
	
		if (isset($_POST['fcrm_styling_reset_to_defaults']) && $_POST['fcrm_styling_reset_to_defaults'] === 'true') {
			// Reset color settings
			foreach ($this->color_settings as $key => $label) {
				update_option($this->get_option_prefix() . $key, $this->get_default_value($key));
			}
			
			// Reset border radius
			update_option($this->get_option_prefix() . 'border-radius', $this->get_default_value('border-radius'));
			
			// Reset grid border radius
			update_option($this->get_option_prefix() . 'grid-border-radius', $this->get_default_value('grid-border-radius'));
	
			wp_redirect(add_query_arg(['page' => 'fcrm-enhancements', 'tab' => 'styling', 'reset' => 'true'], admin_url('admin.php')));
			exit;
		}
	}

	/**
	 * Module activation tasks
	 */
	public function activate(): void {
		parent::activate();
	}

	/**
	 * Module deactivation tasks
	 */
	public function deactivate(): void {
		parent::deactivate();
		foreach ($this->color_settings as $key => $label) {
			delete_option($this->get_option_prefix() . $key);
		}
		delete_option($this->get_option_prefix() . 'border-radius');
		delete_option($this->get_option_prefix() . 'grid-border-radius'); 
	}
}