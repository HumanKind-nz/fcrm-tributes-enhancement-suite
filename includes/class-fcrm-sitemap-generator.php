<?php
namespace FCRM\EnhancementSuite;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Sitemap Generator
 *
 * Generates XML sitemap content for tribute pages.
 * Intercepts requests to /fhf_tributes_sitemap_N.xml and outputs
 * proper sitemap XML using the FireHawk API.
 *
 * Also registers tribute sitemaps with all major SEO plugins
 * (SEOPress, Yoast, RankMath) and WordPress native sitemaps.
 *
 * @package FCRM_Enhancement_Suite
 */
class Sitemap_Generator {

	/**
	 * Cache key prefix for sitemap XML
	 */
	const CACHE_PREFIX = 'fcrm_sitemap_xml_';

	/**
	 * Cache key prefix for long-lived backup (stale-while-error)
	 */
	const BACKUP_PREFIX = 'fcrm_sitemap_backup_';

	/**
	 * Cache duration for sitemap XML (2 hours)
	 */
	const CACHE_DURATION = 7200;

	/**
	 * Backup cache duration (7 days) — served when API fails
	 */
	const BACKUP_DURATION = 604800;

	/**
	 * Initialize the sitemap generator
	 */
	public static function init() {
		// Intercept sitemap requests before WordPress loads the theme
		add_action('parse_request', [__CLASS__, 'maybe_serve_sitemap'], 1);

		// Register with SEO plugins
		add_action('init', [__CLASS__, 'register_with_seo_plugins'], 20);

		// Register our own rewrite rules as fallback if FireHawk hasn't
		add_action('init', [__CLASS__, 'maybe_register_rewrite_rules'], 15);
	}

	/**
	 * Register rewrite rules for sitemap if FireHawk hasn't already
	 */
	public static function maybe_register_rewrite_rules() {
		// FireHawk registers fhf_tributes_sitemap as a query var
		// We add the rewrite rule as a fallback in case FireHawk's version
		// doesn't work (e.g., when Yoast is not installed)
		add_rewrite_rule(
			'^fhf_tributes_sitemap_([0-9]+)\.xml$',
			'index.php?fhf_tributes_sitemap=$matches[1]',
			'top'
		);

		// Register query var if not already registered by FireHawk
		add_filter('query_vars', function ($vars) {
			if (!in_array('fhf_tributes_sitemap', $vars, true)) {
				$vars[] = 'fhf_tributes_sitemap';
			}
			return $vars;
		});
	}

	/**
	 * Check if this request is for a tribute sitemap and serve it
	 *
	 * @param \WP $wp WordPress request object
	 */
	public static function maybe_serve_sitemap($wp) {
		if (!isset($wp->query_vars['fhf_tributes_sitemap'])) {
			return;
		}

		$page_num = (int) $wp->query_vars['fhf_tributes_sitemap'];

		self::log_debug('Sitemap request detected for page: ' . $page_num);

		if ($page_num < 1) {
			return;
		}

		// Check if FireHawk API is available
		if (!class_exists('Fcrm_Tributes_Api')) {
			self::log_debug('FireHawk API class not available');
			self::serve_empty_sitemap();
			return;
		}

		self::serve_sitemap($page_num);
	}

	/**
	 * Generate and serve sitemap XML for a given page
	 *
	 * @param int $page_num Sitemap page number
	 */
	private static function serve_sitemap($page_num) {
		// Try to get from cache first
		$cache_key = self::CACHE_PREFIX . $page_num;
		$cached_xml = get_transient($cache_key);

		// Also try object cache (Redis)
		if (false === $cached_xml) {
			$cached_xml = wp_cache_get($cache_key, Cache_Manager::CACHE_GROUP);
		}

		if (false !== $cached_xml && !empty($cached_xml)) {
			self::output_xml($cached_xml);
			return;
		}

		// Fetch tributes from FireHawk API
		// API uses 0-based offset: page 1 = offset 0, page 2 = offset 1, etc.
		$from = $page_num - 1;
		if ($from < 0) {
			$from = 0;
		}

		self::log_debug('Calling get_tributes_sitemap(' . $from . ') for page ' . $page_num);

		// Increase timeout for sitemap API calls — FireHawk's default 5s is
		// too short for fetching 500 tributes from Cloud Functions cold starts
		$timeout_filter = function ($args, $url) {
			if (strpos($url, '/api/tributes/') !== false) {
				$args['timeout'] = 30;
			}
			return $args;
		};
		add_filter('http_request_args', $timeout_filter, 10, 2);

		try {
			$response = \Fcrm_Tributes_Api::get_tributes_sitemap($from);
		} catch (\Exception $e) {
			self::log_debug('Sitemap API call threw exception: ' . $e->getMessage());
			$response = null;
		}

		remove_filter('http_request_args', $timeout_filter, 10);

		self::log_debug('API response type: ' . gettype($response) . (is_object($response) ? ' props: ' . implode(',', array_keys(get_object_vars($response))) : ''));

		// Extract tributes from API response
		// FireHawk API returns object with ->results property
		$tributes = [];
		if (is_object($response) && isset($response->results)) {
			$tributes = $response->results;
		} elseif (is_object($response) && isset($response->clients)) {
			$tributes = $response->clients;
		} elseif (is_array($response)) {
			$tributes = $response;
		}

		self::log_debug('Tributes found: ' . count($tributes));

		if (empty($tributes)) {
			// API failed or returned empty — serve stale backup if available
			self::serve_stale_or_empty($page_num, $response);
			return;
		}

		// Build the XML
		$xml = self::build_sitemap_xml($tributes);

		// Cache the generated XML (2 hours)
		set_transient($cache_key, $xml, self::CACHE_DURATION);
		wp_cache_set($cache_key, $xml, Cache_Manager::CACHE_GROUP, self::CACHE_DURATION);

		// Also store a long-lived backup for stale-while-error resilience
		$backup_key = self::BACKUP_PREFIX . $page_num;
		set_transient($backup_key, $xml, self::BACKUP_DURATION);

		self::output_xml($xml);
	}

	/**
	 * Serve stale cached sitemap or empty sitemap as last resort
	 *
	 * When the API fails, this checks for a long-lived backup cache
	 * so Google/Bing still see URLs instead of an empty sitemap.
	 *
	 * @param int   $page_num Sitemap page number
	 * @param mixed $response The failed API response (for logging)
	 */
	private static function serve_stale_or_empty($page_num, $response) {
		// Always log sitemap failures — this is critical for SEO
		$detail = is_null($response) ? 'null (likely timeout)' : gettype($response);
		error_log('[FCRM_ES Sitemap] API returned no tributes for page ' . $page_num . ' — response: ' . $detail);

		// Try long-lived backup cache
		$backup_key = self::BACKUP_PREFIX . $page_num;
		$backup_xml = get_transient($backup_key);

		if (false !== $backup_xml && !empty($backup_xml)) {
			error_log('[FCRM_ES Sitemap] Serving stale backup for page ' . $page_num);
			self::output_xml($backup_xml);
			return;
		}

		error_log('[FCRM_ES Sitemap] No backup available — serving empty sitemap for page ' . $page_num);
		self::serve_empty_sitemap();
	}

	/**
	 * Build sitemap XML from tribute data
	 *
	 * @param array $tributes Array of tribute objects from the API
	 * @return string XML string
	 */
	private static function build_sitemap_xml($tributes) {
		$search_page_id = get_option('fcrm_tributes_search_page_id');
		$single_page_id = get_option('fcrm_tributes_single_page_id');
		$use_readable = get_option('fcrm_tributes_readable_permalinks', false);

		// Get the tribute page slug (matching FireHawk's approach)
		$tribute_page_slug = '';
		if ($single_page_id) {
			$page = get_post($single_page_id);
			if ($page) {
				$tribute_page_slug = $page->post_name;
			}
		}

		// Determine the base URL for tribute pages
		$base_url = '';
		if ($single_page_id) {
			$base_url = get_permalink($single_page_id);
		} elseif ($search_page_id) {
			$base_url = get_permalink($search_page_id);
		}

		// Fallback to site URL + /tributes/
		if (empty($base_url)) {
			$base_url = home_url('/tributes/');
		}

		// Ensure trailing slash
		$base_url = trailingslashit($base_url);

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ($tributes as $tribute) {
			$url = self::build_tribute_url($tribute, $base_url, $use_readable);

			if (empty($url)) {
				continue;
			}

			$lastmod = '';
			if (isset($tribute->updatedAt)) {
				$lastmod = gmdate('c', strtotime($tribute->updatedAt));
			} elseif (isset($tribute->clientDateOfDeath)) {
				$lastmod = gmdate('c', strtotime($tribute->clientDateOfDeath));
			} elseif (isset($tribute->createdAt)) {
				$lastmod = gmdate('c', strtotime($tribute->createdAt));
			}

			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url($url) . "</loc>\n";

			if (!empty($lastmod)) {
				$xml .= "\t\t<lastmod>" . $lastmod . "</lastmod>\n";
			}

			$xml .= "\t\t<changefreq>weekly</changefreq>\n";
			$xml .= "\t\t<priority>0.6</priority>\n";
			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Build the URL for a single tribute
	 *
	 * @param object $tribute Tribute object from API
	 * @param string $base_url Base URL for tribute pages
	 * @param bool   $use_readable Whether to use slug-based URLs
	 * @return string Tribute URL
	 */
	public static function build_tribute_url($tribute, $base_url, $use_readable) {
		if (empty($tribute->id)) {
			return '';
		}

		// Get the tribute page slug for URL building
		$single_page_id = get_option('fcrm_tributes_single_page_id');
		$tribute_page_slug = '';
		if ($single_page_id) {
			$page = get_post($single_page_id);
			if ($page) {
				$tribute_page_slug = $page->post_name;
			}
		}

		// Readable permalinks: build slug from name + file number
		// Matches FireHawk's format_client_permalink() exactly
		if ($use_readable && !empty($tribute->fileNumber)) {
			$url_parts = [];

			if (!empty($tribute->firstName)) {
				// Match FireHawk's clean_string: remove hyphens, spaces, special chars, lowercase
				$url_parts[] = self::clean_url_string($tribute->firstName);
			} else {
				$url_parts[] = '1';
			}

			if (!empty($tribute->lastName)) {
				$url_parts[] = self::clean_url_string($tribute->lastName);
			} else {
				$url_parts[] = '1';
			}

			$file_number = str_replace('/', '%2F', $tribute->fileNumber);
			$url_parts[] = $file_number;

			$permalink = home_url($tribute_page_slug . '/' . implode('-', $url_parts));

			if (isset($tribute->teamGroupIndex)) {
				$permalink .= '?tid=' . $tribute->teamGroupIndex;
			}

			return $permalink;
		}

		// ID-based URL
		if (!empty($tribute_page_slug)) {
			$permalink = home_url($tribute_page_slug . '/' . $tribute->id);

			if (isset($tribute->teamGroupIndex)) {
				$permalink .= '?tid=' . $tribute->teamGroupIndex;
			}

			return $permalink;
		}

		// Fallback: query param URL
		return $base_url . '?id=' . urlencode($tribute->id);
	}

	/**
	 * Clean a string for URL use (matches FireHawk's clean_string function)
	 *
	 * @param string $string Input string
	 * @return string Cleaned string
	 */
	private static function clean_url_string($string) {
		$string = str_replace('-', '', $string);
		$string = preg_replace('/\s+/', '', $string);
		$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
		return strtolower(preg_replace('/-+/', '', $string));
	}

	/**
	 * Serve an empty sitemap (valid XML, no URLs)
	 */
	private static function serve_empty_sitemap() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$xml .= '</urlset>';

		self::output_xml($xml);
	}

	/**
	 * Output XML with proper headers and exit
	 *
	 * @param string $xml XML content
	 */
	private static function output_xml($xml) {
		// Prevent any other output
		if (!headers_sent()) {
			status_header(200);
			header('Content-Type: text/xml; charset=UTF-8');
			header('X-Robots-Tag: noindex, follow');
			header('Cache-Control: public, max-age=3600');
		}

		echo $xml;
		exit;
	}

	/**
	 * Register tribute sitemaps with all active SEO plugins
	 */
	public static function register_with_seo_plugins() {
		$sitemap_enabled = get_option('fcrm_enhancement_seo_analytics_enable_sitemap', 1);

		if (!$sitemap_enabled || $sitemap_enabled === '0') {
			return;
		}

		// SEOPress
		if (function_exists('seopress_get_service') || defined('SEOPRESS_VERSION')) {
			add_filter('seopress_sitemaps_external_link', [__CLASS__, 'add_to_seopress_sitemap'], 10, 1);
		}

		// Yoast SEO
		if (defined('WPSEO_VERSION')) {
			add_filter('wpseo_sitemap_index', [__CLASS__, 'add_to_yoast_sitemap'], 10, 1);
		}

		// RankMath
		if (class_exists('RankMath')) {
			add_filter('rank_math/sitemap/index', [__CLASS__, 'add_to_rankmath_sitemap'], 10, 1);
		}

		// WordPress native sitemaps (WP 5.5+): tribute sitemaps are accessible
		// directly at /fhf_tributes_sitemap_N.xml; no custom provider needed.
	}

	/**
	 * Get the number of sitemap pages
	 *
	 * @return int Number of sitemap pages
	 */
	public static function get_sitemap_page_count() {
		$sitemap_count = 1;

		if (!class_exists('Fcrm_Tributes_Api')) {
			return $sitemap_count;
		}

		try {
			$is_new_api = API_Interceptor::is_new_api_structure();

			if ($is_new_api && method_exists('Fcrm_Tributes_Api', 'get_tributes_sitemap_count')) {
				$count_response = \Fcrm_Tributes_Api::get_tributes_sitemap_count();

				if (is_object($count_response) && isset($count_response->count)) {
					$sitemap_count = (int) $count_response->count;
				} elseif (is_numeric($count_response)) {
					$sitemap_count = (int) $count_response;
				}
			} else {
				$count_response = \Fcrm_Tributes_Api::get_tributes_count();
				$tribute_count = 0;

				if (is_object($count_response) && isset($count_response->count)) {
					$tribute_count = (int) $count_response->count;
				} elseif (is_numeric($count_response)) {
					$tribute_count = (int) $count_response;
				}

				$sitemap_count = ($tribute_count > 0) ? (int) ceil($tribute_count / 500) : 1;
			}
		} catch (\Exception $e) {
			self::log_debug('Failed to get sitemap page count: ' . $e->getMessage());
		}

		return max(1, $sitemap_count);
	}

	/**
	 * Build array of sitemap URLs for SEO plugin registration
	 *
	 * @return array Array of sitemap URL strings
	 */
	private static function get_sitemap_urls() {
		$urls = [];
		$count = self::get_sitemap_page_count();

		for ($i = 1; $i <= $count; $i++) {
			$urls[] = home_url('/fhf_tributes_sitemap_' . $i . '.xml');
		}

		return $urls;
	}

	/**
	 * Add tribute sitemaps to SEOPress sitemap index
	 *
	 * @param array $external_links Existing external links
	 * @return array Modified external links
	 */
	public static function add_to_seopress_sitemap($external_links) {
		if (!is_array($external_links)) {
			$external_links = [];
		}

		$urls = self::get_sitemap_urls();

		foreach ($urls as $url) {
			$external_links[] = [
				'sitemap_url'      => $url,
				'sitemap_last_mod' => gmdate('c'),
			];
		}

		return $external_links;
	}

	/**
	 * Add tribute sitemaps to Yoast sitemap index
	 *
	 * @param string $sitemap_index Yoast sitemap index XML
	 * @return string Modified sitemap index XML
	 */
	public static function add_to_yoast_sitemap($sitemap_index) {
		$urls = self::get_sitemap_urls();

		foreach ($urls as $url) {
			$sitemap_index .= '<sitemap>' . "\n";
			$sitemap_index .= "\t<loc>" . esc_url($url) . '</loc>' . "\n";
			$sitemap_index .= "\t<lastmod>" . gmdate('c') . '</lastmod>' . "\n";
			$sitemap_index .= '</sitemap>' . "\n";
		}

		return $sitemap_index;
	}

	/**
	 * Add tribute sitemaps to RankMath sitemap index
	 *
	 * @param string $sitemap_index RankMath sitemap index XML
	 * @return string Modified sitemap index XML
	 */
	public static function add_to_rankmath_sitemap($sitemap_index) {
		// RankMath uses same format as Yoast
		return self::add_to_yoast_sitemap($sitemap_index);
	}

	/**
	 * Clear sitemap cache (called when tributes are updated)
	 */
	public static function clear_cache() {
		global $wpdb;

		// Clear all sitemap transients (both primary and backup)
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::CACHE_PREFIX . '%',
				'_transient_timeout_' . self::CACHE_PREFIX . '%',
				'_transient_' . self::BACKUP_PREFIX . '%',
				'_transient_timeout_' . self::BACKUP_PREFIX . '%'
			)
		);

		// Clear object cache
		$count = self::get_sitemap_page_count();
		for ($i = 1; $i <= $count; $i++) {
			wp_cache_delete(self::CACHE_PREFIX . $i, Cache_Manager::CACHE_GROUP);
		}
	}

	/**
	 * Debug logging helper
	 *
	 * @param string $message Log message
	 */
	private static function log_debug($message) {
		$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
		if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[FCRM_ES Sitemap] ' . $message);
		}
	}
}
