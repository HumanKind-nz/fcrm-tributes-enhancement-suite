<?php
declare(strict_types=1);

namespace FCRM\EnhancementSuite;

/**
 * Base Module Class
 * 
 * Provides common functionality for all enhancement modules.
 * Implements modern PHP practices and robust error handling.
 */
abstract class Enhancement_Base implements Module_Interface {
	/**
	 * Module ID 
	 */
	protected readonly string $module_id;

	/**
	 * Module name
	 */
	protected readonly string $module_name;

	/**
	 * Option prefix for all module settings
	 */
	protected readonly string $option_prefix;

	/**
	 * Module version for cache busting
	 */
	protected string $version = '1.0.0';

	/**
	 * Constructor
	 *
	 * @param string $module_id Unique identifier for the module
	 * @param string $module_name Display name for the module
	 */
	public function __construct(string $module_id, string $module_name) {
		$this->module_id = $module_id;
		$this->module_name = $module_name;
		$this->option_prefix = 'fcrm_enhancement_' . $this->module_id . '_';
	
		try {
			$this->init();
		} catch (\Exception $e) {
			$this->log_error('Module initialization failed', $e);
		}
	}

	/**
	 * Initialize the module
	 * 
	 * Child classes should override this to set up their specific hooks and functionality
	 */
	abstract protected function init(): void;

	/**
	 * Get module option with type safety
	 *
	 * @param string $key Option key
	 * @param mixed $default Default value
	 * @return mixed
	 */
	protected function get_option(string $key, mixed $default = null): mixed {
		$value = get_option($this->option_prefix . $key, $default ?? $this->get_default_value($key));
		
		// Apply filters for extensibility
		return apply_filters("fcrm_enhancement_{$this->module_id}_option_{$key}", $value);
	}

	/**
	 * Set module option with validation
	 *
	 * @param string $key Option key
	 * @param mixed $value Option value
	 * @return bool Success status
	 */
	protected function set_option(string $key, mixed $value): bool {
		// Allow filtering before saving
		$value = apply_filters("fcrm_enhancement_{$this->module_id}_set_option_{$key}", $value);
		
		return update_option($this->option_prefix . $key, $value);
	}

	/**
	 * Delete module option
	 *
	 * @param string $key Option key
	 * @return bool Success status
	 */
	protected function delete_option(string $key): bool {
		return delete_option($this->option_prefix . $key);
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
		try {
			// Base activation tasks
			$this->log_info("Module {$this->module_id} activated");
		} catch (\Exception $e) {
			$this->log_error('Module activation failed', $e);
		}
	}

	/**
	 * Handle module deactivation tasks
	 */
	public function deactivate(): void {
		try {
			// Base deactivation tasks
			$this->log_info("Module {$this->module_id} deactivated");
		} catch (\Exception $e) {
			$this->log_error('Module deactivation failed', $e);
		}
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
		wp_enqueue_script('wp-color-picker', ['jquery']);
	}

	/**
	 * Enqueue module frontend assets
	 */
	public function enqueue_frontend_assets(): void {
		// Base frontend asset enqueuing
	}

	/**
	 * Get option prefix
	 */
	public function get_option_prefix(): string {
		return $this->option_prefix;
	}

	/**
	 * Get module ID
	 */
	public function get_module_id(): string {
		return $this->module_id;
	}

	/**
	 * Get module name
	 */
	public function get_module_name(): string {
		return $this->module_name;
	}

	/**
	 * Get module version
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Sanitise boolean (NZ English)
	 */
	public function sanitise_boolean(mixed $value): bool {
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Sanitise integer with bounds
	 */
	protected function sanitise_integer(mixed $value, int $min = 0, int $max = PHP_INT_MAX): int {
		$int_value = (int) $value;
		return max($min, min($max, $int_value));
	}

	/**
	 * Sanitise string with length limits
	 */
	protected function sanitise_string(mixed $value, int $max_length = 255): string {
		$string_value = sanitize_text_field((string) $value);
		return substr($string_value, 0, $max_length);
	}

	/**
	 * Check if module is enabled
	 */
	public function is_enabled(): bool {
		return $this->get_option('enabled', true);
	}

	/**
	 * Check if current user can manage this module
	 */
	protected function current_user_can_manage(): bool {
		return current_user_can('manage_options');
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

	/**
	 * Log error message
	 */
	protected function log_error(string $message, ?\Exception $exception = null): void {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			$log_message = "[FCRM Enhancement {$this->module_id}] ERROR: {$message}";
			
			if ($exception) {
				$log_message .= " - Exception: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}";
			}
			
			error_log($log_message);
		}
	}

	/**
	 * Log info message
	 */
	protected function log_info(string $message): void {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log("[FCRM Enhancement {$this->module_id}] INFO: {$message}");
		}
	}

	/**
	 * Handle AJAX requests with error handling
	 */
	protected function handle_ajax_request(callable $callback, string $action_name): void {
		try {
			// Verify nonce
			if (!wp_verify_nonce($_POST['nonce'] ?? '', $action_name)) {
				wp_send_json_error(['message' => 'Invalid nonce']);
				return;
			}

			// Check permissions
			if (!$this->current_user_can_manage()) {
				wp_send_json_error(['message' => 'Insufficient permissions']);
				return;
			}

			// Execute callback
			$result = $callback();
			wp_send_json_success($result);

		} catch (\Exception $e) {
			$this->log_error("AJAX request failed for {$action_name}", $e);
			wp_send_json_error(['message' => 'Request failed']);
		}
	}

	/**
	 * Get all module options
	 */
	public function get_all_options(): array {
		global $wpdb;
		
		$options = [];
		$prefix = $this->option_prefix;
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$prefix . '%'
			)
		);
		
		foreach ($results as $row) {
			$key = str_replace($prefix, '', $row->option_name);
			$options[$key] = maybe_unserialize($row->option_value);
		}
		
		return $options;
	}

	/**
	 * Reset all module options to defaults
	 */
	public function reset_to_defaults(): bool {
		try {
			global $wpdb;
			
			// Delete all module options
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$this->option_prefix . '%'
				)
			);
			
			$this->log_info("Module {$this->module_id} reset to defaults");
			return true;
			
		} catch (\Exception $e) {
			$this->log_error('Failed to reset module to defaults', $e);
			return false;
		}
	}
}