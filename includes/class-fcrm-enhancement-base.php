<?php
namespace FCRM\EnhancementSuite;

/**
 * Base Module Class
 * 
 * Provides common functionality for all enhancement modules.
 */
abstract class Enhancement_Base {
	/**
	 * Module ID 
	 *
	 * @var string
	 */
	protected $module_id;

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $module_name;

	/**
	 * Option prefix for all module settings
	 * 
	 * @var string
	 */
	protected $option_prefix;

	/**
	 * Constructor
	 *
	 * @param string $module_id Unique identifier for the module
	 * @param string $module_name Display name for the module
	 */
	public function __construct($module_id, $module_name) {
		$this->module_id = $module_id;
		$this->module_name = $module_name;
		$this->option_prefix = 'fcrm_enhancement_' . $this->module_id . '_';
	
		$this->init();
	}

	/**
	 * Initialize the module
	 * 
	 * Child classes should override this to set up their specific hooks and functionality
	 */
	abstract protected function init(): void;

	/**
	 * Get module option
	 *
	 * @param string $key Option key
	 * @param mixed $default Default value
	 * @return mixed
	 */
	protected function get_option(string $key, $default = null) {
		return get_option($this->option_prefix . $key, $default ?? $this->get_default_value($key));
	}

	/**
	 * Get default value for option
	 *
	 * @param string $key Option key
	 * @return mixed
	 */
	protected function get_default_value(string $key): mixed {
		return null;
	}

	/**
	 * Register module settings
	 * 
	 * Child classes should override this to register their specific settings
	 */
	abstract public function register_settings(): void;

	/**
	 * Render module settings page
	 * 
	 * Child classes should override this to render their specific settings
	 */
	abstract public function render_settings(): void;

	/**
	 * Handle module activation tasks
	 */
	public function activate(): void {
		// Base activation tasks
	}

	/**
	 * Handle module deactivation tasks
	 */
	public function deactivate(): void {
		// Base deactivation tasks
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets(string $hook_suffix): void {
		if ('toplevel_page_fcrm-enhancements' !== $hook_suffix) {
			return;
		}
	
		// Enqueue WordPress color picker
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker', array('jquery'));
	}

	/**
	 * Enqueue module frontend assets
	 */
	public function enqueue_frontend_assets(): void {
		// Base frontend asset enqueuing
	}

	/**
	 * Get option prefix
	 *
	 * @return string
	 */
	public function get_option_prefix(): string {
		return $this->option_prefix;
	}

	/**
	 * Get module ID
	 *
	 * @return string
	 */
	public function get_module_id(): string {
		return $this->module_id;
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_module_name(): string {
		return $this->module_name;
	}

	/**
	 * Sanitise boolean (NZ English)
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public function sanitise_boolean($value): bool {
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Check if module is enabled
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->get_option('enabled', true);
	}

	/**
	 * Register common settings
	 * 
	 * Registers settings that are common to all modules
	 */
	protected function register_common_settings(): void {
		register_setting(
			'fcrm_enhancement_' . $this->module_id,
			$this->option_prefix . 'enabled',
			[
				'type' => 'boolean',
				'default' => true,
				'sanitize_callback' => [$this, 'sanitise_boolean']
			]
		);
	}

	/**
	 * Render common settings fields
	 * 
	 * Renders settings fields that are common to all modules
	 */
	protected function render_common_settings(): void {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html($this->module_name); ?> Status</th>
				<td>
					<label>
						<input type="checkbox" 
							   name="<?php echo esc_attr($this->option_prefix . 'enabled'); ?>" 
							   value="1" 
							   <?php checked($this->is_enabled()); ?>>
						<?php echo esc_html__('Enable this module', 'fcrm-enhancement-suite'); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}
}