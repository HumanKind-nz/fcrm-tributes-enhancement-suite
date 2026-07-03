<?php
/**
 * Settings helpers — single source of truth for defaults and accessors.
 *
 * @package FcrmEnhancementSuite
 */

declare( strict_types=1 );

namespace FcrmEnhancementSuite\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Option name for the consolidated settings object.
 */
const OPTION_NAME = 'fcrm_enhancement_suite_settings';

/**
 * Default settings values.
 *
 * Every key that the REST schema exposes MUST appear here.
 *
 * @return array<string, mixed>
 */
function get_defaults(): array {
	return [
		// ── Deprecated module toggles (retained until v3.1; no longer drive behaviour) ──
		'module_optimisation_enabled'  => false,
		'module_layouts_enabled'       => false,
		'module_ui_styling_enabled'    => false,
		'module_styling_enabled'       => false,
		'module_seo_analytics_enabled' => false,

		// ── Performance / Caching ───────────────────────────────────
		'cache_enabled'                => true,
		'cache_duration_client_list'   => 1800,
		'cache_duration_single_client' => 900,
		'cache_duration_messages'      => 300,
		'debug_logging'                => false,

		// ── Spinner / Animations (Styling tab) ──────────────────────
		'spinner_color'                => '#667eea',

		// ── Layouts ─────────────────────────────────────────────────
		// ── Layout system mode (replaces the old layouts/styling toggles) ──
		'layout_mode'                  => 'modern', // 'modern' | 'firehawk'
		'active_layout'                => 'modern-grid',
		'active_single_layout'         => 'enhanced-classic',
		'layout_grid_columns'          => '3',
		'layout_card_style'            => 'standard',
		'layout_header_style'          => 'standard',
		'layout_sidebar_enabled'       => false,
		'layout_responsive_breakpoints' => true,
		'layout_default_page_size'     => 12,
		'layout_load_more_size'        => 8,

		// ── UI Styling (modern layouts) ─────────────────────────────
		'ui_primary_color'             => '#2563eb',
		'ui_primary_text_color'        => '#ffffff',
		'ui_primary_button_text_color' => '#ffffff',
		'ui_secondary_color'           => '#64748b',
		'ui_accent_color'              => '#d4af37',
		'ui_background_color'          => '#ffffff',
		'ui_card_background'           => '#ffffff',
		'ui_text_color'                => '#1e293b',
		'ui_border_color'              => '#e2e8f0',
		'ui_border_radius'             => '8',
		'ui_control_radius'            => '8',
		'ui_border_width'              => '1',
		'ui_card_shadow'               => 'subtle',
		'ui_grid_gap'                  => '1.5',
		'ui_card_padding'              => '1.5',
		'ui_grid_max_width'            => '1200',
		'ui_font_inherit'              => true,
		'ui_font_family'               => 'system',
		'ui_font_size_scale'           => '100',
		'ui_elegant_use_serif'         => true,
		'ui_elegant_gold_color'        => '#d4af37',
		'ui_gallery_overlay_opacity'   => '85',
		'ui_list_photo_size'           => 'medium',
		'ui_modern_hover_lift'         => true,
		'ui_color_scheme'              => 'default',

		// ── FireHawk Styling (original layout colours) ──────────────
		'styling_primary_color'              => '#FFFFFF',
		'styling_secondary_color'            => '#000000',
		'styling_primary_button'             => '#007BFF',
		'styling_primary_button_text'        => '#FFFFFF',
		'styling_primary_button_hover'       => '#0056B3',
		'styling_primary_button_hover_text'  => '#FFFFFF',
		'styling_secondary_button'           => '#6C757D',
		'styling_secondary_button_text'      => '#FFFFFF',
		'styling_secondary_button_border'    => '#FFFFFF',
		'styling_secondary_button_hover'     => '#FFFFFF',
		'styling_secondary_button_hover_text' => '#6C757D',
		'styling_secondary_button_hover_border' => '#6C757D',
		'styling_focus_border_color'         => '#007BFF',
		'styling_card_background'            => '#FFFFFF',
		'styling_primary_shadow'             => 'rgba(0, 0, 0, 0.1)',
		'styling_focus_shadow_color'         => '#80BDFF',
		'styling_link_color'                 => '#0000EE',
		'styling_border_radius'              => '6px',
		'styling_grid_border_radius'         => '18px',

		// ── Custom CSS (advanced; output scoped to tribute pages) ───
		'styling_custom_css'                 => '',

		// ── SEO & Analytics ─────────────────────────────────────────
		'seo_enable_plausible'         => false,
		'seo_enable_seopress'          => false,
		'seo_seopress_title_suffix'    => 'Tribute',
		'seo_seopress_use_tribute_photo' => true,
		'seo_seopress_social_image'    => '',
		'seo_enable_sitemap'           => true,

		// ── Instant Indexing ────────────────────────────────────────
		'indexing_google_enabled'      => false,
		'indexing_google_credentials'  => '',
		'indexing_google_quota'        => 200,
		'indexing_indexnow_enabled'    => false,
	];
}

/**
 * Retrieve the full settings array, merged with defaults.
 *
 * @return array<string, mixed>
 */
function get_settings(): array {
	$saved = get_option( OPTION_NAME, [] );
	return wp_parse_args( $saved, get_defaults() );
}

/**
 * Retrieve a single setting value.
 *
 * @param string $key     Setting key (without prefix).
 * @param mixed  $default Override the built-in default (optional).
 * @return mixed
 */
function get_setting( string $key, $default = null ) {
	$settings = get_settings();
	$defaults = get_defaults();

	if ( array_key_exists( $key, $settings ) ) {
		return $settings[ $key ];
	}

	return $default ?? ( $defaults[ $key ] ?? null );
}
