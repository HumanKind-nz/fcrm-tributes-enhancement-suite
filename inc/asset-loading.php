<?php
/**
 * Conditional asset loading for FireHawk Tributes.
 *
 * Handles the three-tier asset dequeuing strategy:
 * 1. Non-tribute pages — dequeue ALL FireHawk assets (~431KB saved)
 * 2. Grid pages with enhanced layouts — dequeue redundant assets, keep essentials
 * 3. Single tribute pages — keep ALL assets (gallery/carousel needs Slick, lightGallery)
 *
 * @package FcrmEnhancementSuite
 */
declare( strict_types=1 );

namespace FcrmEnhancementSuite\AssetLoading;

use function FcrmEnhancementSuite\Helpers\get_setting;

defined( 'ABSPATH' ) || exit;

/**
 * Conditionally remove FCRM assets.
 *
 * Simple logic:
 * 1. NOT a tribute page (no shortcode, no ?id) — dequeue ALL FireHawk assets
 * 2. Using Enhanced Grid layout — dequeue redundant FireHawk grid assets
 * 3. Using FireHawk layout OR single tribute — keep everything (passthrough)
 */
function conditional_fcrm_assets(): void {
	// Don't dequeue anything when page builders are active.
	if ( is_page_builder_active() ) {
		return;
	}

	$active_layout = get_setting( 'active_layout', 'modern-grid' );

	// More robust single tribute detection — check multiple sources.
	global $wp;
	$is_single_tribute = isset( $_GET['id'] ) ||
	                     ( get_query_var( 'id' ) !== '' && get_query_var( 'id' ) !== false ) ||
	                     ( isset( $wp->query_vars['id'] ) && $wp->query_vars['id'] !== '' );

	// Check if we're on a tribute page (includes shortcode check).
	$is_tribute_page = \FcrmEnhancementSuite\TributeDetection\is_tribute_page();

	// NOT a tribute page — remove ALL FireHawk assets.
	if ( ! $is_tribute_page ) {
		dequeue_fcrm_assets();
		return;
	}

	// Check if page content contains layout="firehawk" passthrough (for demos/testing).
	$has_firehawk_passthrough = page_has_firehawk_passthrough();

	// If using FireHawk passthrough, keep ALL assets (don't optimise).
	if ( $has_firehawk_passthrough ) {
		return;
	}

	// Using Enhanced Grid layout (and NOT on single tribute) — remove redundant grid assets.
	if ( ! $is_single_tribute ) {
		dequeue_redundant_fcrm_assets();
		return;
	}

	// Single tribute. Our enhanced-classic layout re-skins FireHawk's own runtime
	// (Slick gallery, lightGallery, Bootstrap modals, messages), so we keep those,
	// but it never uses several heavy libraries FireHawk still enqueues. Trim those —
	// but ONLY when our enhanced-classic single layout is genuinely what renders.
	//
	// Two gates: 'firehawk' layout_mode means the Layouts module never loads and
	// FireHawk renders its own single layout; and active_single_layout='default'
	// (no enhanced template) falls back to FireHawk's single layout too. FireHawk's
	// default single layout uses add-to-calendar etc., so we must not trim there.
	$single_layout = get_setting( 'active_single_layout', 'enhanced-classic' );
	if ( 'modern' === \FcrmEnhancementSuite\Status\get_layout_mode()
		&& 'enhanced-classic' === $single_layout ) {
		dequeue_single_tribute_redundant_assets();
	}
}

/**
 * Dequeue FireHawk assets the enhanced single tribute layout never uses.
 *
 * Conservative list — only libraries confirmed unreferenced by the single-tribute
 * runtime (FirehawkCRMTributePage / ServiceTribute), the flower-delivery script,
 * and our enhanced-classic template. The gallery uses Slick (kept), not Glide;
 * there is no date picker (Litepicker), no add-to-calendar button, and no textFit.
 *
 * Kept (still required): jquery, fcrm-tributes (sharer + ajax_var), moment, slick,
 * lightgallery + lg-pager + lg-zoom, bootstrap, shufflejs, select2, clipboard,
 * fontawesome, verify-input, tribute-messages/page/trees/donations, tippy/popper,
 * flower-delivery (+ its jquery-history and jquery-validate dependencies — the
 * flower form calls $.validator.addMethod, so jquery-validate must stay).
 *
 * Expected saving: ~330KB uncompressed (atcb ~196KB, litepicker ~105KB,
 * glidejs ~25KB, textFit ~4KB).
 */
function dequeue_single_tribute_redundant_assets(): void {
	$redundant_styles = [
		'fcrm-tributes-glidejs-core',   // Glide carousel CSS — gallery uses Slick.
		'fcrm-tributes-glidejs-theme',  // Glide theme CSS — unused.
		'add-to-calendar-button',       // ATCB CSS — no calendar button in our layout.
	];

	$redundant_scripts = [
		'fcrm-tributes-glidejs',    // Glide carousel — gallery uses Slick.
		'fcrm-tributes-litepicker', // Date range picker — no picker on single tribute.
		'add-to-calendar-button',   // Add-to-calendar widget — not rendered.
		'fcrm-tributes-textFit',    // Text fitting — grid-only, unused on single.
	];

	foreach ( $redundant_styles as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) ) {
			wp_dequeue_style( $handle );
		}
	}

	foreach ( $redundant_scripts as $handle ) {
		if ( wp_script_is( $handle, 'enqueued' ) ) {
			wp_dequeue_script( $handle );
		}
	}
}

/**
 * Dequeue redundant FCRM assets when Enhanced Layouts are active.
 *
 * Performance optimisation (v2.1.1): When using Enhanced Layouts, we have our own
 * self-contained grid system with custom CSS/JS. This removes FireHawk's redundant
 * assets while preserving critical dependencies (Moment.js, Lodash, jQuery, AJAX).
 */
function dequeue_redundant_fcrm_assets(): void {
	// Redundant CSS files — Enhanced Layouts provide their own styling.
	$redundant_styles = [
		'fcrm-tributes-glidejs-core',      // Carousel — not used in Enhanced Layouts
		'fcrm-tributes-glidejs-theme',     // Carousel theme — not used
		'jquery-slick-nav',                 // Mobile nav — not used
		'select2',                          // Dropdown styling — we use Flatpickr
		'add-to-calendar-button',           // Calendar widget — not in grid layouts
		'fcrm-tributes-jquery-modal',       // Modal styles — not used in grids
		'fcrm-tributes-lightgallery-css',   // Lightbox — not in grid layouts
		'fcrm-tributes'                     // Main FireHawk CSS (46.3KB) — we have our own
	];

	// Redundant JavaScript files — Enhanced Layouts implement own functionality.
	$redundant_scripts = [
		'fcrm-tributes-popperjs',           // Tooltips — not used
		'fcrm-tributes-tippyjs',            // Tooltip library — not used
		'fontawesome',                      // Icons — redundant
		'bootstrap',                        // Grid system — we have CSS Grid
		'shufflejs',                        // Grid filtering — we have own search
		'jquery-history',                   // History API — not needed
		'jquery-validate',                  // Form validation — no forms in grids
		'select2',                          // Dropdown JS — we use Flatpickr instead
		'jquery-slick-carousel',            // Carousel — not used
		'fcrm-tributes-clipboard',          // Copy functionality — not in grids
		'fcrm-tributes-textFit',            // Text fitting — not needed
		'fcrm-tributes-glidejs',            // Carousel library — not used
		'fcrm-tributes-jquery-modal',       // Modal JS — not used
		'fcrm-tributes-litepicker',         // Datepicker — we use Flatpickr
		'add-to-calendar-button',           // Calendar widget — not in grids
		'_',                                // Lodash (25.8KB) — not used in grid layouts
		// NOTE: 'fcrm-tributes' MUST be kept — it provides ajax_var localisation we need.
		'fcrm-tributes-lightgallerys',      // Lightbox — not in grids
		'fcrm-tributes-tribute-messages',   // Messages tab — single tribute only
		'fcrm-tributes-tributes-page',      // Single tribute page — not grid
		'fcrm-tributes-tribute-trees',      // Trees tab — single tribute only
		'fcrm-tributes-tribute-donations',  // Donations tab — single tribute only
		'fcrm-tributes-tributes-grid',      // FireHawk grid JS — we replace entirely
		'fcrm-tributes-verify-input',       // Input verification — no forms
		'lg-pager',                         // Lightbox pager — not used
		'lg-zoom',                          // Lightbox zoom — not used
		'fcrm-tributes-flower-delivery'     // Flowers — disabled separately
	];

	// NOTE: We KEEP these critical FireHawk dependencies for grid layouts:
	// - momentScript (Moment.js) — REQUIRED: Date formatting, filtering, service date checks
	// - fcrm-tributes (fcrm-tributes-public.js) — REQUIRED: Provides ajax_var localisation
	// - jquery — Core dependency
	//
	// We REMOVE on grid pages:
	// - _ (Lodash) — Not used in Enhanced Grid layouts (saves 25.8KB)

	// Dequeue redundant styles.
	foreach ( $redundant_styles as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) ) {
			wp_dequeue_style( $handle );
		}
	}

	// Dequeue redundant scripts.
	foreach ( $redundant_scripts as $handle ) {
		if ( wp_script_is( $handle, 'enqueued' ) ) {
			wp_dequeue_script( $handle );
		}
	}

	// NOTE: We cannot remove sharer.min.js on grid pages because FireHawk uses
	// the same handle 'fcrm-tributes' for both sharer.min.js and fcrm-tributes-public.js
	// The second enqueue (fcrm-tributes-public.js) should overwrite the first,
	// but if both are loading, we must keep them to preserve ajax_var localisation.
	// Attempted savings: 3.5KB (sharer.min.js) — cannot be achieved due to FireHawk's duplicate handle bug.
}

/**
 * Dequeue all FCRM plugin assets (for non-tribute pages).
 */
function dequeue_fcrm_assets(): void {
	// FCRM CSS files (from class-fcrm-tributes-public.php).
	$fcrm_styles = [
		'fcrm-tributes-glidejs-core',
		'fcrm-tributes-glidejs-theme',
		'jquery-slick-nav',
		'select2',
		'add-to-calendar-button',
		'fcrm-tributes-jquery-modal',
		'fcrm-tributes-lightgallery-css',
		'fcrm-tributes'
	];

	// FCRM JavaScript files (from class-fcrm-tributes-public.php).
	$fcrm_scripts = [
		'fcrm-tributes-popperjs',
		'fcrm-tributes-tippyjs',
		'fontawesome',
		'bootstrap',
		'shufflejs',
		'jquery-history',
		'jquery-validate',
		'select2',
		'jquery-slick-carousel',
		'fcrm-tributes-clipboard',
		'fcrm-tributes-textFit',
		'fcrm-tributes-glidejs',
		'fcrm-tributes-jquery-modal',
		'fcrm-tributes-litepicker',
		'add-to-calendar-button',
		'fcrm-tributes', // This handle appears twice in FCRM code.
		'_', // Lodash
		'momentScript',
		'fcrm-tributes-lightgallerys',
		'fcrm-tributes-tribute-messages',
		'fcrm-tributes-tributes-page',
		'fcrm-tributes-tribute-trees',
		'fcrm-tributes-tribute-donations',
		'fcrm-tributes-tributes-grid',
		'fcrm-tributes-verify-input',
		'lg-pager',
		'lg-zoom',
		'fcrm-tributes-flower-delivery'
	];

	// Dequeue all FCRM styles.
	$dequeued_styles = [];
	foreach ( $fcrm_styles as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) ) {
			wp_dequeue_style( $handle );
			$dequeued_styles[] = $handle;
		}
	}

	// Dequeue all FCRM scripts.
	$dequeued_scripts = [];
	foreach ( $fcrm_scripts as $handle ) {
		if ( wp_script_is( $handle, 'enqueued' ) ) {
			wp_dequeue_script( $handle );
			$dequeued_scripts[] = $handle;
		}
	}
}

/**
 * Check if current page content contains layout="firehawk" passthrough.
 *
 * @return bool True if page has FireHawk passthrough shortcode.
 */
function page_has_firehawk_passthrough(): bool {
	global $post;

	if ( ! $post ) {
		return false;
	}

	// Check if post content contains layout="firehawk" or layout='firehawk'.
	if ( stripos( $post->post_content, 'layout="firehawk"' ) !== false ||
	     stripos( $post->post_content, "layout='firehawk'" ) !== false ) {
		return true;
	}

	return false;
}

/**
 * Check if a page builder is currently active.
 *
 * @return bool True if page builder front-end editor is active.
 */
function is_page_builder_active(): bool {
	// Beaver Builder.
	if ( isset( $_GET['fl_builder'] ) || ( class_exists( 'FLBuilderModel' ) && \FLBuilderModel::is_builder_active() ) ) {
		return true;
	}

	// Elementor.
	if ( isset( $_GET['elementor-preview'] ) || ( defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) ) {
		return true;
	}

	// Divi Builder.
	if ( isset( $_GET['et_fb'] ) || function_exists( 'et_fb_is_enabled' ) && et_fb_is_enabled() ) {
		return true;
	}

	// Oxygen Builder.
	if ( isset( $_GET['ct_builder'] ) || ( defined( 'CT_VERSION' ) && isset( $_GET['oxygen_iframe'] ) ) ) {
		return true;
	}

	// Bricks Builder.
	if ( isset( $_GET['bricks'] ) && $_GET['bricks'] === 'run' ) {
		return true;
	}

	return false;
}

// Conditional loading is always on — register the dequeue hook.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\conditional_fcrm_assets', 999 );
