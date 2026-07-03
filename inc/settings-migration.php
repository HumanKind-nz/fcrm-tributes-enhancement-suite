<?php
/**
 * One-time migration from individual wp_options to consolidated settings object.
 *
 * Runs on `init` (front-end included, not just wp-admin) so the conversion
 * happens on the very first request after an upgrade, before any styling is
 * rendered. Without this, a v2→v3 upgrade would show default styling to
 * front-end visitors until an admin page load triggered the migration. Once
 * complete, a flag option ensures it never does the work again.
 *
 * @package FcrmEnhancementSuite
 */

declare( strict_types=1 );

namespace FcrmEnhancementSuite\SettingsMigration;

use function FcrmEnhancementSuite\Helpers\get_defaults;

use const FcrmEnhancementSuite\Helpers\OPTION_NAME;

defined( 'ABSPATH' ) || exit;

/**
 * Migration version key. Bump this to re-run migration if needed.
 */
const MIGRATION_VERSION = '1';

/**
 * Map of new consolidated key => old individual option key.
 *
 * Keys not listed here keep their defaults from helpers.php.
 *
 * @return array<string, string>
 */
function get_key_map(): array {
	return [
		// Module toggles.
		'module_optimisation_enabled'  => 'fcrm_module_optimisation_enabled',
		'module_layouts_enabled'       => 'fcrm_module_layouts_enabled',
		'module_ui_styling_enabled'    => 'fcrm_module_ui_styling_enabled',
		'module_styling_enabled'       => 'fcrm_module_styling_enabled',
		'module_seo_analytics_enabled' => 'fcrm_module_seo_analytics_enabled',

		// Performance / caching.
		'cache_enabled'                => 'fcrm_cache_enabled',
		'cache_duration_client_list'   => 'fcrm_cache_duration_client_list',
		'cache_duration_single_client' => 'fcrm_cache_duration_single_client',
		'cache_duration_messages'      => 'fcrm_cache_duration_messages',
		'debug_logging'                => 'fcrm_debug_logging',

		// Spinner / animations.
		'spinner_color'                => 'fcrm_spinner_color',

		// Layouts.
		'active_layout'                => 'fcrm_active_layout',
		'active_single_layout'         => 'fcrm_active_single_layout',
		'layout_grid_columns'          => 'fcrm_layout_grid_columns',
		'layout_card_style'            => 'fcrm_layout_card_style',
		'layout_header_style'          => 'fcrm_layout_header_style',
		'layout_sidebar_enabled'       => 'fcrm_layout_sidebar_enabled',
		'layout_responsive_breakpoints' => 'fcrm_layout_responsive_breakpoints',
		'layout_default_page_size'     => 'fcrm_layout_default_page_size',
		'layout_load_more_size'        => 'fcrm_layout_load_more_size',

		// UI Styling.
		'ui_primary_color'             => 'fcrm_ui_primary_color',
		'ui_primary_text_color'        => 'fcrm_ui_primary_text_color',
		'ui_primary_button_text_color' => 'fcrm_ui_primary_button_text_color',
		'ui_secondary_color'           => 'fcrm_ui_secondary_color',
		'ui_accent_color'              => 'fcrm_ui_accent_color',
		'ui_background_color'          => 'fcrm_ui_background_color',
		'ui_card_background'           => 'fcrm_ui_card_background',
		'ui_text_color'                => 'fcrm_ui_text_color',
		'ui_border_color'              => 'fcrm_ui_border_color',
		'ui_border_radius'             => 'fcrm_ui_border_radius',
		'ui_border_width'              => 'fcrm_ui_border_width',
		'ui_card_shadow'               => 'fcrm_ui_card_shadow',
		'ui_grid_gap'                  => 'fcrm_ui_grid_gap',
		'ui_card_padding'              => 'fcrm_ui_card_padding',
		'ui_grid_max_width'            => 'fcrm_ui_grid_max_width',
		'ui_font_inherit'              => 'fcrm_ui_font_inherit',
		'ui_font_family'               => 'fcrm_ui_font_family',
		'ui_font_size_scale'           => 'fcrm_ui_font_size_scale',
		'ui_elegant_use_serif'         => 'fcrm_ui_elegant_use_serif',
		'ui_elegant_gold_color'        => 'fcrm_ui_elegant_gold_color',
		'ui_gallery_overlay_opacity'   => 'fcrm_ui_gallery_overlay_opacity',
		'ui_list_photo_size'           => 'fcrm_ui_list_photo_size',
		'ui_modern_hover_lift'         => 'fcrm_ui_modern_hover_lift',
		'ui_color_scheme'              => 'fcrm_ui_color_scheme',

		// FireHawk Styling (note: hyphens → underscores).
		'styling_primary_color'              => 'fcrm_styling_primary-color',
		'styling_secondary_color'            => 'fcrm_styling_secondary-color',
		'styling_primary_button'             => 'fcrm_styling_primary-button',
		'styling_primary_button_text'        => 'fcrm_styling_primary-button-text',
		'styling_primary_button_hover'       => 'fcrm_styling_primary-button-hover',
		'styling_primary_button_hover_text'  => 'fcrm_styling_primary-button-hover-text',
		'styling_secondary_button'           => 'fcrm_styling_secondary-button',
		'styling_secondary_button_text'      => 'fcrm_styling_secondary-button-text',
		'styling_secondary_button_border'    => 'fcrm_styling_secondary-button-border',
		'styling_secondary_button_hover'     => 'fcrm_styling_secondary-button-hover',
		'styling_secondary_button_hover_text' => 'fcrm_styling_secondary-button-hover-text',
		'styling_secondary_button_hover_border' => 'fcrm_styling_secondary-button-hover-border',
		'styling_focus_border_color'         => 'fcrm_styling_focus-border-color',
		'styling_card_background'            => 'fcrm_styling_card-background',
		'styling_primary_shadow'             => 'fcrm_styling_primary-shadow',
		'styling_focus_shadow_color'         => 'fcrm_styling_focus-shadow-color',
		'styling_link_color'                 => 'fcrm_styling_link-color',
		'styling_border_radius'              => 'fcrm_styling_border_radius',
		'styling_grid_border_radius'         => 'fcrm_styling_grid_border_radius',

		// SEO & Analytics.
		'seo_enable_plausible'         => 'fcrm_enhancement_seo_analytics_enable_plausible',
		'seo_enable_seopress'          => 'fcrm_enhancement_seo_analytics_enable_seopress',
		'seo_seopress_title_suffix'    => 'fcrm_enhancement_seo_analytics_seopress_title_suffix',
		'seo_seopress_social_image'    => 'fcrm_enhancement_seo_analytics_seopress_social_image',
		'seo_enable_sitemap'           => 'fcrm_enhancement_seo_analytics_enable_sitemap',

		// Instant Indexing.
		'indexing_google_enabled'      => 'fcrm_indexing_google_enabled',
		'indexing_google_credentials'  => 'fcrm_indexing_google_credentials',
		'indexing_google_quota'        => 'fcrm_indexing_google_quota',
		'indexing_indexnow_enabled'    => 'fcrm_indexing_indexnow_enabled',
	];
}

/**
 * Run the settings migration if it has not been performed yet.
 */
function maybe_migrate(): void {
	$migrated_version = get_option( 'fcrm_settings_migrated', '' );

	if ( MIGRATION_VERSION === $migrated_version ) {
		return;
	}

	// Don't migrate if the consolidated option already exists (fresh install after migration era).
	if ( false !== get_option( OPTION_NAME ) && '' !== $migrated_version ) {
		update_option( 'fcrm_settings_migrated', MIGRATION_VERSION );
		return;
	}

	$defaults     = get_defaults();
	$key_map      = get_key_map();
	$migrated     = [];
	$found_legacy = false; // True if ANY old individual option exists (i.e. not a fresh install).

	foreach ( $key_map as $new_key => $old_option ) {
		$old_value = get_option( $old_option, null );

		if ( null === $old_value ) {
			// No old option stored — use default.
			$migrated[ $new_key ] = $defaults[ $new_key ];
			continue;
		}

		$found_legacy = true;

		// Cast to match the default type.
		$default_type = $defaults[ $new_key ];

		if ( is_bool( $default_type ) ) {
			$migrated[ $new_key ] = (bool) $old_value;
		} elseif ( is_int( $default_type ) ) {
			$migrated[ $new_key ] = (int) $old_value;
		} else {
			$migrated[ $new_key ] = (string) $old_value;
		}
	}

	// Derive layout_mode ONLY for sites with genuine legacy state, BEFORE the
	// default-fill below. An upgrading site keeps its current rendering: layouts
	// toggle on → 'modern'; otherwise → 'firehawk' (FireHawk passthrough, which
	// was the old default-off behaviour). A TRUE fresh install has no legacy
	// options at all, so we leave layout_mode unset here and let the default-fill
	// assign the 'modern' default — a new site must not start in legacy layout.
	if ( $found_legacy ) {
		$migrated['layout_mode'] = ! empty( $migrated['module_layouts_enabled'] ) ? 'modern' : 'firehawk';
	}

	// Fill any keys not in the migration map with defaults.
	foreach ( $defaults as $key => $default ) {
		if ( ! array_key_exists( $key, $migrated ) ) {
			$migrated[ $key ] = $default;
		}
	}

	// Also check legacy flower delivery option.
	$legacy_flowers = get_option( 'fcrm_disable_flowers', null );
	$legacy_flowers_new = get_option( 'fcrm_enhancement_optimisation_disable_flowers', null );
	// Flower delivery is now always disabled — no setting needed.

	// Also check legacy caching option.
	$legacy_caching = get_option( 'fcrm_enhancement_optimisation_enable_caching', null );
	if ( null !== $legacy_caching ) {
		$migrated['cache_enabled'] = (bool) $legacy_caching;
	}

	update_option( OPTION_NAME, $migrated, true );
	update_option( 'fcrm_settings_migrated', MIGRATION_VERSION );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[FCRM_ES] Settings migration completed. Migrated ' . count( $key_map ) . ' settings.' );
	}
}

add_action( 'init', __NAMESPACE__ . '\maybe_migrate', 5 );

/**
 * Derive the new layout_mode from the deprecated module toggles, once.
 *
 * Idempotent: only writes when layout_mode is not yet present in the option,
 * so it safely covers both freshly-migrated and already-migrated sites.
 */
function maybe_set_layout_mode(): void {
	$option = get_option( OPTION_NAME, [] );

	if ( is_array( $option ) && array_key_exists( 'layout_mode', $option ) ) {
		return; // Already set — nothing to do.
	}

	if ( ! is_array( $option ) ) {
		$option = [];
	}

	$layouts_on = ! empty( $option['module_layouts_enabled'] );
	$option['layout_mode'] = $layouts_on ? 'modern' : 'firehawk';

	update_option( OPTION_NAME, $option, true );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[FCRM_ES] layout_mode derived as ' . $option['layout_mode'] );
	}
}

add_action( 'init', __NAMESPACE__ . '\maybe_set_layout_mode', 6 );
