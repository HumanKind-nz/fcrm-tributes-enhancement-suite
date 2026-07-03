<?php
declare(strict_types=1);

/**
 * Plugin uninstall handler.
 *
 * Removes all plugin data from the database when the plugin is deleted
 * from the WordPress admin (not just deactivated).
 *
 * @package FcrmEnhancementSuite
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove module enabled states.
delete_option( 'fcrm_module_optimisation_enabled' );
delete_option( 'fcrm_module_layouts_enabled' );
delete_option( 'fcrm_module_ui_styling_enabled' );
delete_option( 'fcrm_module_styling_enabled' );
delete_option( 'fcrm_module_seo_analytics_enabled' );

// Remove core settings.
delete_option( 'fcrm_conditional_asset_loading' );
delete_option( 'fcrm_active_layout' );
delete_option( 'fcrm_active_single_layout' );

// Remove external modules registry.
delete_option( 'fcrm_external_modules' );

// Remove instant indexing options.
delete_option( 'fcrm_known_tribute_ids' );
delete_option( 'fcrm_indexing_log' );
delete_option( 'fcrm_indexing_daily_count' );
delete_option( 'fcrm_indexnow_api_key' );

// Remove all plugin transients (including GitHub updater cache).
global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	WHERE option_name LIKE '_transient_fcrm_%'
	OR option_name LIKE '_transient_timeout_fcrm_%'"
);

/*
 * WARNING: Uncomment the following section to remove ALL module-specific
 * options (UI styling colours, typography settings, etc.) on uninstall.
 * This is irreversible — all customisation will be lost.
 *
 * $wpdb->query(
 *     "DELETE FROM {$wpdb->options}
 *     WHERE option_name LIKE 'fcrm_enhancement_%'
 *     OR option_name LIKE 'fcrm_ui_%'
 *     OR option_name LIKE 'fcrm_styling_%'"
 * );
 */
