<?php
declare( strict_types=1 );

namespace FcrmEnhancementSuite\TributeDetection;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for tribute page detection.
 *
 * Checks query params, designated search page, and 6 tribute shortcodes.
 * Result is cached per request via a static variable.
 *
 * @return bool
 */
function is_tribute_page(): bool {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$is_tribute = false;

	// Check for tribute single post type.
	if ( isset( $_GET['id'] ) && is_singular() && get_post_type() === 'tribute' ) {
		$is_tribute = true;
	}

	// Check if we're on the designated tribute search page.
	$search_page_id = get_option( 'fcrm_tributes_search_page_id' );
	if ( ! $is_tribute && $search_page_id && is_page( $search_page_id ) ) {
		$current_page = get_post( $search_page_id );
		$page_slug    = $current_page ? $current_page->post_name : '';

		$excluded_pages = [ 'sample-page', 'hello-world', 'privacy-policy' ];
		if ( ! in_array( $page_slug, $excluded_pages, true ) ) {
			$is_tribute = true;
		}
	}

	// Check for tribute shortcodes.
	if ( ! $is_tribute && has_tribute_shortcode() ) {
		$is_tribute = true;
	}

	/**
	 * Filter the final tribute-page detection result.
	 *
	 * Escape hatch for any context the built-in detection can't see — e.g. a grid
	 * placed in a widget, a theme template, or a homepage built with a page builder
	 * that stores content outside post_content. Return true to treat the current
	 * request as a tribute page (assets kept + enhanced layout enqueued).
	 *
	 * @param bool $is_tribute Whether the current request is a tribute page.
	 */
	$is_tribute = (bool) apply_filters( 'fcrm_is_tribute_page', $is_tribute );

	$cache = $is_tribute;
	return $cache;
}

/**
 * The tribute shortcode tags this plugin treats as "tribute page" signals.
 *
 * @return string[]
 */
function get_tribute_shortcode_tags(): array {
	return [
		'show_crm_tribute',
		'show_crm_tributes_grid',
		'show_crm_tributes_large_grid',
		'show_crm_tributes_carousel',
		'show_crm_tribute_search',
		'show_crm_tribute_search_bar',
	];
}

/**
 * Check if the current post contains tribute shortcodes.
 *
 * Scans the post content first (covers the classic/Gutenberg editor and Divi,
 * which store shortcodes in post_content). Falls back to page-builder layout
 * data for builders that keep content in post meta — Beaver Builder
 * (_fl_builder_data) and Elementor (_elementor_data) — so a grid placed via a
 * builder module on a homepage is still detected.
 *
 * Detection only ever flips false → true: it cannot mark a page as a tribute
 * page unless a tribute shortcode is genuinely present, so the asset
 * optimisation on non-tribute pages is unaffected.
 *
 * @return bool
 */
function has_tribute_shortcode(): bool {
	global $post;
	if ( ! $post || ! is_a( $post, 'WP_Post' ) ) {
		return false;
	}

	$shortcodes = get_tribute_shortcode_tags();

	// Fast path: shortcode present in post content (classic/Gutenberg/Divi).
	$pattern = get_shortcode_regex( $shortcodes );
	if ( (bool) preg_match( '/' . $pattern . '/', $post->post_content ) ) {
		return true;
	}

	// Fallback: scan page-builder layout data stored in post meta.
	return builder_meta_has_tribute_shortcode( $post->ID, $shortcodes );
}

/**
 * Detect a tribute shortcode inside page-builder layout data.
 *
 * Beaver Builder and Elementor store module content in post meta rather than
 * post_content, so a literal shortcode tag won't appear in the post body. We
 * scan the raw serialised/JSON meta for any of the shortcode tags. A simple
 * substring match is enough — the tags are unique to this plugin, so a match
 * means the grid is genuinely placed on the page.
 *
 * @param int      $post_id    Post ID.
 * @param string[] $shortcodes Tribute shortcode tags to look for.
 * @return bool
 */
function builder_meta_has_tribute_shortcode( int $post_id, array $shortcodes ): bool {
	$meta_keys = [
		'_fl_builder_data', // Beaver Builder (published layout).
		'_elementor_data',  // Elementor.
	];

	foreach ( $meta_keys as $meta_key ) {
		$data = get_post_meta( $post_id, $meta_key, true );
		if ( empty( $data ) ) {
			continue;
		}

		// Normalise to a searchable string (BB stores serialised arrays/objects).
		$haystack = is_string( $data ) ? $data : maybe_serialize( $data );

		foreach ( $shortcodes as $tag ) {
			if ( stripos( $haystack, $tag ) !== false ) {
				return true;
			}
		}
	}

	return false;
}
