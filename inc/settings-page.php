<?php
/**
 * Settings page registration and REST API exposure.
 *
 * Renders a minimal wrapper div; the React app (built from src/js/settings/)
 * mounts into it. All UI uses @wordpress/components — no custom CSS needed.
 *
 * @package FcrmEnhancementSuite
 */

declare( strict_types=1 );

namespace FcrmEnhancementSuite\SettingsPage;

use function FcrmEnhancementSuite\Helpers\get_defaults;
use function FcrmEnhancementSuite\Helpers\get_settings;
use function FcrmEnhancementSuite\Status\get_status_summary;
use function FcrmEnhancementSuite\Status\is_seopress_active;
use function FcrmEnhancementSuite\Status\is_plausible_active;
use function FcrmEnhancementSuite\Status\is_ui_styling_applicable;
use function FcrmEnhancementSuite\Status\is_firehawk_plugin_active;

use const FcrmEnhancementSuite\Helpers\OPTION_NAME;

defined( 'ABSPATH' ) || exit;

// Future: an "Integrations" settings section (FireHawk connection at v3.2,
// further data-source adapters at v5.0) mounts inside the React app. The
// menu and enqueue here remain a single page; no separate tab in v3.0.
/**
 * Register the top-level admin menu page.
 */
function add_admin_menu(): void {
	add_menu_page(
		__( 'FH Enhancement Suite', 'fcrm-enhancement-suite' ),
		__( 'FH Enhancement Suite', 'fcrm-enhancement-suite' ),
		'manage_options',
		'fcrm-enhancements',
		__NAMESPACE__ . '\render_settings_page',
		'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0ibm9uZSIgdmlld0JveD0iMCAwIDEyMiAxMDYiPjxwYXRoIGZpbGw9IiNGRkYiIGQ9Ik02LjQgMTguN2MwIDE1LjUgNi41IDI5LjUgMTcgMzkuNSAyLTEuNiA0LjItMyA2LjQtNC40YTQ2LjcgNDYuNyAwIDAgMS0xNS44LTM1aDYuNWE0MC40IDQwLjQgMCAwIDAgMjIgMzYgNTEuOCA1MS44IDAgMCAwLTE5LjEgMTEuOGMtMTAuNSA5LjktMTcgMjQtMTcgMzkuNEgwYzAtMTcuMSA3LjEtMzIuNiAxOC41LTQzLjZBNjAuNiA2MC42IDAgMCAxIDAgMTguN2g2LjRabTEwMS42IDBhNDYuNyA0Ni43IDAgMCAxLTQ3IDQ2LjkgNDAuMiA0MC4yIDAgMCAwLTI1IDguNkE0MC40IDQwLjQgMCAwIDAgMjAuNSAxMDZIMTRhNDYuNyA0Ni43IDAgMCAxIDQ3LTQ2LjggNDAuMiA0MC4yIDAgMCAwIDI1LTguNyA0MC4xIDQwLjEgMCAwIDAgMTUuNS0zMS44aDYuNVpNNjEgNzMuMmM1LjkgMCAxMS40IDEuNSAxNi4xIDQuMmguMUEzMi45IDMyLjkgMCAwIDEgOTQgMTA2aC02LjVhMjYuNSAyNi41IDAgMCAwLTUzIDBoLTYuNEEzMi44IDMyLjggMCAwIDEgNjEgNzMuMlptNjEtNTQuNWMwIDE3LjEtNy4xIDMyLjYtMTguNSA0My43QTYwLjYgNjAuNiAwIDAgMSAxMjIgMTA2aC02LjRhNTQuMyA1NC4zIDAgMCAwLTIyLTQzLjYgNTQuMyA1NC4zIDAgMCAwIDIyLTQzLjZoNi40Wm0tMzUuNCA0OEE0Ni43IDQ2LjcgMCAwIDEgMTA4IDEwNmgtNi40YTQwLjQgNDAuNCAwIDAgMC0yMi0zNmMyLjQtLjggNC44LTIgNy4xLTMuMVptLTUyLjEtNDhhMjYuNSAyNi41IDAgMCAwIDUzIDBoNi40YTMyLjggMzIuOCAwIDAgMS00OSAyOC42aC0uMUEzMi45IDMyLjkgMCAwIDEgMjggMTguN2g2LjVaTTYxIDBhMTcuMiAxNy4yIDAgMSAxIDAgMzQuNEExNy4yIDE3LjIgMCAwIDEgNjEgMFptMCA2LjRhMTAuOCAxMC44IDAgMSAwIDEwLjggMTAuOGMwLTYtNC45LTEwLjgtMTAuOC0xMC44WiIvPjwvc3ZnPgo=',
		91
	);
}

/**
 * Render the settings page wrapper. React mounts into the inner div.
 */
function render_settings_page(): void {
	echo '<div class="wrap"><div id="fcrm-enhancement-settings"></div></div>';
}

/**
 * Enqueue the React settings app on the settings page only.
 *
 * @param string $hook The current admin page hook suffix.
 */
function enqueue_settings_assets( string $hook ): void {
	if ( 'toplevel_page_fcrm-enhancements' !== $hook ) {
		return;
	}

	$asset_file = FCRM_ENHANCEMENT_SUITE_DIR . 'build/settings/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'fcrm-enhancement-settings',
		FCRM_ENHANCEMENT_SUITE_URL . 'build/settings/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_enqueue_style( 'wp-components' );

	// Our settings polish, loaded after wp-components so the scoped overrides win.
	wp_enqueue_style(
		'fcrm-enhancement-settings',
		FCRM_ENHANCEMENT_SUITE_URL . 'build/settings/index.css',
		[ 'wp-components' ],
		$asset['version']
	);

	// Media uploader for social image picker.
	wp_enqueue_media();

	wp_localize_script(
		'fcrm-enhancement-settings',
		'fcrmEnhancementSuite',
		[
			'version'          => FCRM_ENHANCEMENT_SUITE_VERSION,
			'iconUrl'          => FCRM_ENHANCEMENT_SUITE_URL . 'assets/images/icon-256x256.png',
			'defaultSocialImg' => FCRM_ENHANCEMENT_SUITE_URL . 'assets/images/default-social-share.jpg',
			'clearCacheNonce'  => wp_create_nonce( 'fcrm_clear_cache' ),
			'indexingNonce'    => wp_create_nonce( 'fcrm_indexing_nonce' ),
			'status'           => get_status_summary(),
			'capabilities'     => [
				'seopressActive'      => is_seopress_active(),
				'plausibleActive'     => is_plausible_active(),
				'uiStylingApplicable' => is_ui_styling_applicable(),
				'firehawkActive'      => is_firehawk_plugin_active(),
			],
			'themePalette'     => get_theme_color_palette(),
			'defaults'         => get_defaults(),
		]
	);
}

/**
 * Build the colour palette for the settings colour pickers.
 *
 * Merges the active theme's palette (theme.json) with any user-defined custom
 * colours so funeral homes / developers can match brand colours with one click.
 * Falls back to WordPress's default palette only when the theme defines none.
 * `wp_get_global_settings()` is memoised by WordPress, so this is a cheap,
 * single call per page load — no REST round-trip.
 *
 * @return array<int, array{name:string,color:string}>
 */
function get_theme_color_palette(): array {
	if ( ! function_exists( 'wp_get_global_settings' ) ) {
		return [];
	}

	$origins = wp_get_global_settings( [ 'color', 'palette' ] );
	$colors  = [];

	if ( is_array( $origins ) ) {
		if ( ! empty( $origins['theme'] ) || ! empty( $origins['custom'] ) ) {
			// Prefer the theme palette plus any user custom colours.
			foreach ( [ 'theme', 'custom' ] as $origin ) {
				if ( ! empty( $origins[ $origin ] ) && is_array( $origins[ $origin ] ) ) {
					$colors = array_merge( $colors, $origins[ $origin ] );
				}
			}
		} elseif ( ! empty( $origins['default'] ) && is_array( $origins['default'] ) ) {
			// Classic theme with no palette — fall back to core's default.
			$colors = $origins['default'];
		} elseif ( isset( $origins[0] ) ) {
			// Already a flat list.
			$colors = $origins;
		}
	}

	// Some themes (notably GeneratePress) express palette colours as CSS
	// variables — `var(--base-2)` — which can't render in wp-admin or be stored
	// as a concrete value. GeneratePress saves the real hexes in its settings,
	// so build a slug → hex map to resolve them.
	$var_map = get_generatepress_color_map();

	$palette = [];
	$seen    = [];

	foreach ( $colors as $entry ) {
		if ( empty( $entry['color'] ) ) {
			continue;
		}

		$color = (string) $entry['color'];

		// Resolve a `var(--slug)` reference to a concrete hex; skip if we can't.
		if ( preg_match( '/^var\(\s*--([a-z0-9_-]+)\s*\)$/i', $color, $m ) ) {
			$slug = $m[1];
			if ( empty( $var_map[ $slug ] ) ) {
				continue;
			}
			$color = $var_map[ $slug ];
		}

		$key = strtolower( $color );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;

		$palette[] = [
			'name'  => isset( $entry['name'] ) ? (string) $entry['name'] : $color,
			'color' => $color,
		];
	}

	return $palette;
}

/**
 * GeneratePress stores its global colours (the real hexes behind var(--base-2)
 * etc.) in the generate_settings option. Build a slug => hex map so the palette
 * builder can resolve theme CSS-variable colours. Returns [] for other themes.
 *
 * @return array<string, string>
 */
function get_generatepress_color_map(): array {
	$settings = get_option( 'generate_settings' );

	if ( ! is_array( $settings ) || empty( $settings['global_colors'] ) || ! is_array( $settings['global_colors'] ) ) {
		return [];
	}

	$map = [];
	foreach ( $settings['global_colors'] as $entry ) {
		if ( ! empty( $entry['slug'] ) && ! empty( $entry['color'] ) ) {
			$map[ (string) $entry['slug'] ] = (string) $entry['color'];
		}
	}

	return $map;
}

/**
 * Build the REST schema properties array from defaults.
 *
 * @return array<string, array<string, string>>
 */
function get_rest_schema_properties(): array {
	$defaults   = get_defaults();
	$properties = [];

	foreach ( $defaults as $key => $default ) {
		$type = 'string';

		if ( is_bool( $default ) ) {
			$type = 'boolean';
		} elseif ( is_int( $default ) ) {
			$type = 'integer';
		} elseif ( is_float( $default ) ) {
			$type = 'number';
		}

		$properties[ $key ] = [ 'type' => $type ];
	}

	return $properties;
}

/**
 * Register the plugin setting for both admin and REST API contexts.
 */
function register_plugin_settings(): void {
	register_setting(
		'fcrm_enhancement_suite',
		OPTION_NAME,
		[
			'type'              => 'object',
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_settings',
			'default'           => get_defaults(),
			'show_in_rest'      => [
				'schema' => [
					'type'       => 'object',
					'properties' => get_rest_schema_properties(),
				],
			],
		]
	);
}

/**
 * Sanitise settings input.
 *
 * Handles booleans, integers, colour strings (hex + rgba), and plain text.
 *
 * @param mixed $input Raw input from the REST API or form submission.
 * @return array<string, mixed> Sanitised settings.
 */
function sanitize_settings( $input ): array {
	if ( ! is_array( $input ) ) {
		return get_defaults();
	}

	$defaults  = get_defaults();
	$sanitised = [];

	foreach ( $defaults as $key => $default ) {
		if ( ! array_key_exists( $key, $input ) ) {
			$sanitised[ $key ] = $default;
			continue;
		}

		$value = $input[ $key ];

		if ( 'layout_mode' === $key ) {
			// Enum: only 'modern' | 'firehawk'. Anything else canonicalises to
			// the 'modern' default so PHP gating and the React UI never diverge.
			$sanitised[ $key ] = in_array( $value, [ 'modern', 'firehawk' ], true ) ? $value : 'modern';
		} elseif ( 'styling_custom_css' === $key ) {
			// Free-form CSS: preserve newlines (unlike sanitize_text_field) but
			// neutralise any attempt to break out of the <style> wrapper.
			$sanitised[ $key ] = sanitize_custom_css( (string) $value );
		} elseif ( is_bool( $default ) ) {
			$sanitised[ $key ] = (bool) $value;
		} elseif ( is_int( $default ) ) {
			$sanitised[ $key ] = (int) $value;
		} elseif ( str_contains( $key, 'color' ) || str_contains( $key, 'colour' )
			|| str_contains( $key, 'shadow' ) || str_contains( $key, 'button' )
			|| str_contains( $key, 'background' ) || str_contains( $key, 'gold' )
		) {
			// Colour fields: allow hex (#fff, #ffffff) and rgba().
			$sanitised[ $key ] = sanitize_color_value( (string) $value );
		} elseif ( str_contains( $key, 'image' ) || str_contains( $key, 'url' ) ) {
			$sanitised[ $key ] = esc_url_raw( (string) $value );
		} elseif ( str_contains( $key, 'credentials' ) ) {
			// Google credentials JSON — allow but trim.
			$sanitised[ $key ] = trim( (string) $value );
		} else {
			$sanitised[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	return $sanitised;
}

/**
 * Sanitise admin-supplied custom CSS.
 *
 * CSS can't execute scripts, and the field is manage_options-gated, so the only
 * real risk is breaking out of the <style> wrapper. Strip any style/script tags
 * and the legacy CSS-as-script vectors, but otherwise leave the CSS intact
 * (including newlines, which sanitize_text_field would destroy).
 *
 * @param string $css Raw CSS.
 * @return string
 */
function sanitize_custom_css( string $css ): string {
	$css = preg_replace( '#</?\s*(style|script)\b[^>]*>#i', '', $css );
	$css = preg_replace( '#expression\s*\(#i', '', (string) $css );
	$css = str_ireplace( 'javascript:', '', (string) $css );

	return trim( (string) $css );
}

/**
 * Sanitise a colour value (hex or rgba).
 *
 * @param string $value Raw colour string.
 * @return string Sanitised colour or empty string.
 */
function sanitize_color_value( string $value ): string {
	$value = trim( $value );

	if ( '' === $value ) {
		return '';
	}

	// Standard hex: #fff, #ffffff, #ffffffff.
	if ( preg_match( '/^#([0-9a-fA-F]{3,8})$/', $value ) ) {
		return $value;
	}

	// rgba() or rgb().
	if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*[\d.]+\s*)?\)$/', $value ) ) {
		return $value;
	}

	// CSS keyword (e.g. "none" for shadows).
	if ( preg_match( '/^[a-z-]+$/i', $value ) ) {
		return sanitize_text_field( $value );
	}

	return '';
}

// Register hooks.
add_action( 'admin_menu', __NAMESPACE__ . '\add_admin_menu' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_settings_assets' );
add_action( 'admin_init', __NAMESPACE__ . '\register_plugin_settings' );
add_action( 'rest_api_init', __NAMESPACE__ . '\register_plugin_settings' );
