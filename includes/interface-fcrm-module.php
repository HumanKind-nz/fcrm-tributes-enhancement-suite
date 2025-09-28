<?php
declare(strict_types=1);

namespace FCRM\EnhancementSuite;

/**
 * Module Interface
 * 
 * Defines the contract that all enhancement modules must implement.
 * Ensures consistent behaviour across all modules.
 */
interface Module_Interface {
	/**
	 * Get module ID
	 */
	public function get_module_id(): string;

	/**
	 * Get module name
	 */
	public function get_module_name(): string;

	/**
	 * Get module version
	 */
	public function get_version(): string;

	/**
	 * Check if module is enabled
	 */
	public function is_enabled(): bool;

	/**
	 * Register module settings
	 */
	public function register_settings(): void;

	/**
	 * Render module settings page
	 */
	public function render_settings(): void;

	/**
	 * Handle module activation
	 */
	public function activate(): void;

	/**
	 * Handle module deactivation
	 */
	public function deactivate(): void;

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets(string $hook_suffix): void;

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets(): void;

	/**
	 * Get all module options
	 */
	public function get_all_options(): array;

	/**
	 * Reset module to defaults
	 */
	public function reset_to_defaults(): bool;
} 