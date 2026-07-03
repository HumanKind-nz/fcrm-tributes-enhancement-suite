<?php
/**
 * Plugin Name:       FireHawkCRM Tributes Enhancement Suite
 * Plugin URI:        https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite
 * Description:       Performance optimisations and enhancements for the FireHawkCRM Tributes plugin
 * Version:           3.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Weave Digital Studio, Gareth Bissland
 * Author URI:        https://weave.co.nz/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fcrm-enhancement-suite
 * Domain Path:       /languages
 * GitHub Plugin URI: HumanKind-nz/fcrm-tributes-enhancement-suite
 *
 * @package FcrmEnhancementSuite
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'FCRM_ENHANCEMENT_SUITE_VERSION', '3.0.0' );
define( 'FCRM_ENHANCEMENT_SUITE_FILE', __FILE__ );
define( 'FCRM_ENHANCEMENT_SUITE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FCRM_ENHANCEMENT_SUITE_URL', plugin_dir_url( __FILE__ ) );

// Backward compatibility aliases — remove after all modules are updated.
define( 'FCRM_ENHANCEMENT_SUITE_PLUGIN_DIR', FCRM_ENHANCEMENT_SUITE_DIR );
define( 'FCRM_ENHANCEMENT_SUITE_PLUGIN_URL', FCRM_ENHANCEMENT_SUITE_URL );
define( 'FCRM_ENHANCEMENT_SUITE_PLUGIN_FILE', FCRM_ENHANCEMENT_SUITE_FILE );

// Core includes — always loaded.
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/helpers.php';
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/status-checks.php';
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/settings-migration.php';
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/settings-page.php';
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/tribute-detection.php';
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/asset-loading.php';
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/hooks.php';
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-update-checker.php';
require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-tribute-url-fixer.php';

// Infrastructure — loaded when available.
if ( file_exists( FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-fcrm-cache-manager.php' ) ) {
	require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-fcrm-cache-manager.php';
}

if ( file_exists( FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-fcrm-api-interceptor.php' ) ) {
	require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-fcrm-api-interceptor.php';
}

if ( file_exists( FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-fcrm-sitemap-generator.php' ) ) {
	require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-fcrm-sitemap-generator.php';
}

if ( file_exists( FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-fcrm-instant-indexing.php' ) ) {
	require_once FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-fcrm-instant-indexing.php';
}

// Initialise infrastructure services.
if ( class_exists( 'FcrmEnhancementSuite\\API_Interceptor' ) ) {
	add_action( 'init', [ 'FcrmEnhancementSuite\\API_Interceptor', 'init' ] );
}

if ( class_exists( 'FcrmEnhancementSuite\\Tribute_URL_Fixer' ) ) {
	add_action( 'init', [ 'FcrmEnhancementSuite\\Tribute_URL_Fixer', 'init' ], 5 );
}

if ( class_exists( 'FcrmEnhancementSuite\\Sitemap_Generator' ) ) {
	\FcrmEnhancementSuite\Sitemap_Generator::init();
}

if ( class_exists( 'FcrmEnhancementSuite\\Instant_Indexing' ) ) {
	\FcrmEnhancementSuite\Instant_Indexing::init();
}

// GitHub update checker self-initialises via plugins_loaded hook in class-update-checker.php.

/**
 * Load enabled modules on init.
 */
function fcrm_enhancement_suite_load_modules(): void {
	global $fcrm_enhancement_modules;
	$fcrm_enhancement_modules = [];

	$module_files = [
		'optimisation'  => FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-optimisation-module.php',
		'layouts'       => FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-layouts-module.php',
		'ui_styling'    => FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-ui-styling-module.php',
		'styling'       => FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-styling-module.php',
		'seo_analytics' => FCRM_ENHANCEMENT_SUITE_DIR . 'inc/class-seo-analytics-module.php',
	];

	// Explicit key → class map. Avoids relying on ucfirst() casing
	// (PHP class names are case-insensitive, but this is clearer and removes
	// the need for the old seo_analytics special-case).
	$module_classes = [
		'optimisation'  => 'FCRM_Optimisation_Module',
		'layouts'       => 'FCRM_Layouts_Module',
		'ui_styling'    => 'FCRM_UI_Styling_Module',
		'styling'       => 'FCRM_Styling_Module',
		'seo_analytics' => 'FCRM_SEO_Analytics_Module',
	];

	// Infrastructure modules are always on; they self-gate internally.
	$active = [ 'optimisation', 'seo_analytics' ];

	// Layout modules are chosen by layout_mode.
	if ( 'firehawk' === \FcrmEnhancementSuite\Status\get_layout_mode() ) {
		$active[] = 'styling';
	} else {
		$active[] = 'layouts';
		$active[] = 'ui_styling';
	}

	foreach ( $active as $key ) {
		$file = $module_files[ $key ];

		if ( ! file_exists( $file ) ) {
			continue;
		}

		require_once $file;

		$class_name = $module_classes[ $key ] ?? '';

		if ( '' !== $class_name && class_exists( $class_name ) ) {
			$fcrm_enhancement_modules[ $key ] = new $class_name();
		}
	}

	// Load external modules.
	$external_modules = get_option( 'fcrm_external_modules', [] );

	foreach ( $external_modules as $module_key => $module_data ) {
		if ( empty( $module_data['enabled'] ) ) {
			continue;
		}

		if ( ! empty( $module_data['file'] ) && file_exists( $module_data['file'] ) ) {
			require_once $module_data['file'];

			if ( ! empty( $module_data['class'] ) && class_exists( $module_data['class'] ) ) {
				$fcrm_enhancement_modules[ $module_key ] = new $module_data['class']();
			}
		}
	}
}
add_action( 'init', 'fcrm_enhancement_suite_load_modules' );

/**
 * Get the loaded module instances.
 *
 * @return array<string, object> Module key => module instance pairs.
 */
function fcrm_enhancement_suite_get_loaded_modules(): array {
	global $fcrm_enhancement_modules;
	return $fcrm_enhancement_modules ?? [];
}
