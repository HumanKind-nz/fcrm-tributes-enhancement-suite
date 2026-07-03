<?php
/**
 * Plugin lifecycle hooks and shared AJAX handlers.
 *
 * Handles activation/deactivation, dependency checks, conflict notices,
 * cache AJAX endpoints, text domain loading, and front-end admin assets.
 *
 * @package FcrmEnhancementSuite
 */
declare( strict_types=1 );

namespace FcrmEnhancementSuite\Hooks;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin activation callback.
 */
function activation(): void {
	flush_rewrite_rules();
}

/**
 * Plugin deactivation callback.
 */
function deactivation(): void {
	flush_rewrite_rules();
}

/**
 * Add a "Settings" link to the plugin row on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array Modified action links.
 */
function add_plugin_action_links( array $links ): array {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=fcrm-enhancements' ) ),
		esc_html__( 'Settings', 'fcrm-enhancement-suite' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Check for required plugin dependencies.
 */
function check_plugin_dependencies(): void {
	// Check if FCRM Tributes plugin is active by checking for required classes.
	// This works with any plugin slug (fcrm-tributes, fcrm-tributes-2.2.0, fcrm-tributes-2.3.1, etc.)
	$required_classes = [ 'Fcrm_Tributes_Api', 'Single_Tribute' ];
	$fcrm_tributes_active = true;

	foreach ( $required_classes as $class ) {
		if ( ! class_exists( $class ) ) {
			$fcrm_tributes_active = false;
			break;
		}
	}

	if ( ! $fcrm_tributes_active ) {
		// Not installed or not activated.
		echo '<div class="notice notice-error is-dismissible">';
		echo '<p><strong>FireHawk Tributes Enhancement Suite:</strong> This plugin requires the FireHawkCRM Tributes plugin to function.</p>';
		echo '<p>Please install and activate a FireHawkCRM Tributes plugin (any version). Without it, this enhancement suite will not work.</p>';
		echo '<p><a href="' . esc_url( admin_url( 'plugins.php' ) ) . '" class="button button-primary">View Plugins</a></p>';
		echo '</div>';
	}
}

/**
 * Check for conflicts with standalone plugins.
 */
function check_plugin_conflicts(): void {
	$conflicts = [];

	// Check for standalone Plausible plugin.
	if ( in_array( 'fcrm-plausible-analytics/fcrm-plausible-analytics.php',
				apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$conflicts[] = [
			'plugin'  => 'FCRM Plausible Analytics',
			'message' => 'The standalone FCRM Plausible Analytics plugin is active. Please deactivate it to avoid conflicts with the integrated SEO & Analytics module.'
		];
	}

	// Check for standalone SEOPress plugin.
	if ( in_array( 'fcrm-seopress/fcrm-seopress.php',
				apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$conflicts[] = [
			'plugin'  => 'FCRM SEOPress Integration',
			'message' => 'The standalone FCRM SEOPress Integration plugin is active. Please deactivate it to avoid conflicts with the integrated SEO & Analytics module.'
		];
	}

	// Display conflict notices.
	foreach ( $conflicts as $conflict ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php echo esc_html( $conflict['plugin'] ); ?> Conflict:</strong>
				<?php echo esc_html( $conflict['message'] ); ?>
			</p>
		</div>
		<?php
	}
}

/**
 * AJAX handler: clear all cached API responses.
 */
function ajax_clear_cache(): void {
	// Verify nonce and permissions.
	if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['nonce'], 'fcrm_clear_cache' ) ) {
		wp_die( 'Unauthorised' );
	}

	$success = false;
	if ( class_exists( 'FcrmEnhancementSuite\\Cache_Manager' ) ) {
		$success = \FcrmEnhancementSuite\Cache_Manager::clear_all_cache();
	}

	wp_send_json( [
		'success' => $success,
		'message' => $success ? 'Cache cleared successfully' : 'Failed to clear cache'
	] );
}

/**
 * AJAX handler: retrieve cache statistics.
 */
function ajax_get_cache_stats(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Insufficient permissions' ) );
	}

	if ( ! class_exists( 'FcrmEnhancementSuite\\Cache_Manager' ) ) {
		wp_send_json_error( 'Cache manager not available' );
	}

	$stats = \FcrmEnhancementSuite\Cache_Manager::get_cache_stats();
	wp_send_json_success( $stats );
}

/**
 * Load the plugin text domain for translations.
 */
function load_textdomain(): void {
	load_plugin_textdomain(
		'fcrm-enhancement-suite',
		false,
		dirname( plugin_basename( FCRM_ENHANCEMENT_SUITE_FILE ) ) . '/languages/'
	);
}

/**
 * Enqueue front-end assets (cache controls for logged-in admins on tribute pages).
 */
function enqueue_frontend_assets(): void {
	// Only enqueue cache controls on tribute-related pages for admin users.
	if ( current_user_can( 'manage_options' ) && \FcrmEnhancementSuite\TributeDetection\is_tribute_page() ) {
		wp_enqueue_script(
			'fcrm-cache-controls',
			FCRM_ENHANCEMENT_SUITE_URL . 'assets/js/admin/cache-controls.js',
			[ 'jquery' ],
			FCRM_ENHANCEMENT_SUITE_VERSION,
			true
		);

		// Localise script with nonce.
		wp_localize_script( 'fcrm-cache-controls', 'fcrmCacheNonce', wp_create_nonce( 'fcrm_clear_cache' ) );
	}

	// NOTE: Individual modules handle their own asset loading via wp_enqueue_scripts hooks.
	// This prevents unnecessary function calls on non-tribute pages.
}

/**
 * Output admin-defined custom CSS in a scoped <style> on tribute pages.
 *
 * Mode-independent (works under both modern and FireHawk-original layouts) and
 * printed late in <head> so it can override the layout CSS. Scoped to tribute
 * pages, matching the plugin's conditional-styling approach. The value is
 * sanitised on save; the closing-tag strip here is belt-and-braces.
 */
function output_custom_css(): void {
	if ( ! \FcrmEnhancementSuite\TributeDetection\is_tribute_page() ) {
		return;
	}

	$css = trim( (string) \FcrmEnhancementSuite\Helpers\get_setting( 'styling_custom_css', '' ) );
	if ( '' === $css ) {
		return;
	}

	$css = str_ireplace( '</style', '', $css );

	// CSS is intentionally output raw inside a <style> tag (escaping would break
	// it); it is sanitised on save and admin-only (manage_options).
	echo "\n<style id=\"fcrm-custom-css\">\n" . $css . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

// Register all hooks.
register_activation_hook( FCRM_ENHANCEMENT_SUITE_FILE, __NAMESPACE__ . '\activation' );
register_deactivation_hook( FCRM_ENHANCEMENT_SUITE_FILE, __NAMESPACE__ . '\deactivation' );

add_filter(
	'plugin_action_links_' . plugin_basename( FCRM_ENHANCEMENT_SUITE_FILE ),
	__NAMESPACE__ . '\add_plugin_action_links'
);

add_action( 'admin_notices', __NAMESPACE__ . '\check_plugin_dependencies' );
add_action( 'admin_notices', __NAMESPACE__ . '\check_plugin_conflicts' );
add_action( 'wp_ajax_fcrm_clear_cache', __NAMESPACE__ . '\ajax_clear_cache' );
add_action( 'wp_ajax_fcrm_get_cache_stats', __NAMESPACE__ . '\ajax_get_cache_stats' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\load_textdomain' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_frontend_assets' );
add_action( 'wp_head', __NAMESPACE__ . '\output_custom_css', 999 );
