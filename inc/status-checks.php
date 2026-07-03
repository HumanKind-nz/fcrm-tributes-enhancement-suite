<?php
/**
 * Capability status checks — single source of truth for "is this feature
 * active / available / configured". Drives both the Dashboard status page
 * and the grey-out logic inside individual settings tabs.
 *
 * @package FcrmEnhancementSuite
 */

declare( strict_types=1 );

namespace FcrmEnhancementSuite\Status;

use function FcrmEnhancementSuite\Helpers\get_setting;

defined( 'ABSPATH' ) || exit;

/**
 * Current layout mode. 'modern' uses our card layouts; 'firehawk' passes
 * through to the original FireHawk layout (with optional colour overrides).
 */
function get_layout_mode(): string {
	$mode = (string) get_setting( 'layout_mode', 'modern' );
	return in_array( $mode, [ 'modern', 'firehawk' ], true ) ? $mode : 'modern';
}

function is_modern_layouts_active(): bool {
	return 'modern' === get_layout_mode();
}

/** UI styling controls only apply when modern layouts render. */
function is_ui_styling_applicable(): bool {
	return is_modern_layouts_active();
}

/** FireHawk Tributes plugin present (we still depend on it in v3.x). */
function is_firehawk_plugin_active(): bool {
	return class_exists( 'Fcrm_Tributes_Api' ) || class_exists( 'Single_Tribute' );
}

function firehawk_token_present(): bool {
	return '' !== trim( (string) get_option( 'fcrm_tributes_auth_token', '' ) );
}

function is_seopress_active(): bool {
	return defined( 'SEOPRESS_VERSION' ) || function_exists( 'seopress_get_service' );
}

function is_seopress_integration_on(): bool {
	return is_seopress_active() && (bool) get_setting( 'seo_enable_seopress', false );
}

function is_plausible_active(): bool {
	return class_exists( 'Plausible\\Analytics\\WP\\Helpers' );
}

function is_plausible_configured(): bool {
	return is_plausible_active() && (bool) get_setting( 'seo_enable_plausible', false );
}

/** Best-effort Redis detection for the status line. */
function is_redis_available(): bool {
	if ( function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache() ) {
		return class_exists( 'Redis' ) || defined( 'WP_REDIS_HOST' );
	}
	return false;
}

function get_cache_backend(): string {
	return is_redis_available() ? 'redis' : 'transient';
}

/** Flower delivery is disabled site-wide unless the filter re-enables it. */
function is_flower_delivery_disabled(): bool {
	return (bool) apply_filters( 'fcrm_disable_flower_delivery', true );
}

/**
 * Build the Dashboard status rows. Each row is self-detected, never a stored
 * toggle. `state`: ok = active/healthy, info = active but neutral,
 * warning = wants attention, inactive = intentionally off.
 *
 * @return array<int, array{key:string,label:string,state:string,detail:string}>
 */
function get_status_summary(): array {
	$rows = [];

	$rows[] = [
		'key'    => 'layout',
		'label'  => __( 'Layout system', 'fcrm-enhancement-suite' ),
		'state'  => 'ok',
		'detail' => is_modern_layouts_active()
			? __( 'Modern layouts active', 'fcrm-enhancement-suite' )
			: __( 'FireHawk original layout (legacy)', 'fcrm-enhancement-suite' ),
	];

	$rows[] = [
		'key'    => 'cache',
		'label'  => __( 'Performance / cache', 'fcrm-enhancement-suite' ),
		'state'  => 'ok',
		'detail' => 'redis' === get_cache_backend()
			? __( 'Active (Redis object cache detected)', 'fcrm-enhancement-suite' )
			: __( 'Active (transient fallback)', 'fcrm-enhancement-suite' ),
	];

	$rows[] = [
		'key'    => 'seopress',
		'label'  => __( 'SEOPress integration', 'fcrm-enhancement-suite' ),
		'state'  => is_seopress_active() ? ( is_seopress_integration_on() ? 'ok' : 'info' ) : 'inactive',
		'detail' => is_seopress_active()
			? ( is_seopress_integration_on()
				? __( 'Detected and enabled', 'fcrm-enhancement-suite' )
				: __( 'Detected — enable it on the SEO & Analytics tab', 'fcrm-enhancement-suite' ) )
			: __( 'SEOPress not active', 'fcrm-enhancement-suite' ),
	];

	$rows[] = [
		'key'    => 'plausible',
		'label'  => __( 'Plausible Analytics', 'fcrm-enhancement-suite' ),
		'state'  => is_plausible_active() ? ( is_plausible_configured() ? 'ok' : 'info' ) : 'inactive',
		'detail' => is_plausible_active()
			? ( is_plausible_configured()
				? __( 'Detected and enabled', 'fcrm-enhancement-suite' )
				: __( 'Detected — enable it on the SEO & Analytics tab', 'fcrm-enhancement-suite' ) )
			: __( 'Plausible not active', 'fcrm-enhancement-suite' ),
	];

	$firehawk_active = is_firehawk_plugin_active();
	$firehawk_token  = firehawk_token_present();
	$rows[] = [
		'key'    => 'firehawk',
		'label'  => __( 'FireHawk connection', 'fcrm-enhancement-suite' ),
		'state'  => ( $firehawk_active && $firehawk_token ) ? 'ok' : 'warning',
		'detail' => $firehawk_active
			? ( $firehawk_token
				? __( 'FireHawk Tributes plugin active, API token present', 'fcrm-enhancement-suite' )
				: __( 'FireHawk plugin active, but no API token is set', 'fcrm-enhancement-suite' ) )
			: __( 'FireHawk Tributes plugin not detected', 'fcrm-enhancement-suite' ),
	];

	$rows[] = [
		'key'    => 'flowers',
		'label'  => __( 'Flower delivery', 'fcrm-enhancement-suite' ),
		'state'  => is_flower_delivery_disabled() ? 'info' : 'ok',
		'detail' => is_flower_delivery_disabled()
			? __( 'Disabled site-wide', 'fcrm-enhancement-suite' )
			: __( 'Enabled (via filter)', 'fcrm-enhancement-suite' ),
	];

	return $rows;
}
