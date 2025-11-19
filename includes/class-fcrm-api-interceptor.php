<?php
namespace FCRM\EnhancementSuite;

/**
 * API Interceptor
 * 
 * Intercepts FCRM API calls and adds intelligent caching layer
 * without modifying the original FCRM plugin code.
 */
class API_Interceptor {
	/**
	 * Initialize the interceptor
	 */
	public static function init() {
		// Hook into WordPress HTTP API before requests are made
		add_filter('pre_http_request', [__CLASS__, 'intercept_api_request'], 10, 3);
		
		// Add cache clearing hooks
		add_action('wp_ajax_fcrm_clear_cache', [__CLASS__, 'ajax_clear_cache']);
		add_action('wp_ajax_fcrm_clear_client_cache', [__CLASS__, 'ajax_clear_client_cache']);
		
		// Add admin bar cache controls
		add_action('admin_bar_menu', [__CLASS__, 'add_admin_bar_cache_controls'], 100);
		
		// Add cache warming on tribute creation/update (if we can detect it)
		add_action('fcrm_tribute_updated', [__CLASS__, 'warm_client_cache'], 10, 1);
		
		// Connect caching enabled setting to Cache_Manager
		add_filter('fcrm_enhancement_caching_enabled', [__CLASS__, 'is_caching_enabled_from_settings']);
	}

	/**
	 * Intercept HTTP requests to FCRM API
	 *
	 * @param false|array|WP_Error $preempt Response to short-circuit with
	 * @param array $args Request arguments
	 * @param string $url Request URL
	 * @return false|array|WP_Error Modified response or false to continue
	 */
	public static function intercept_api_request($preempt, $args, $url) {
		// Debug: Log all HTTP requests to see if filter is working
		$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
		if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
			if (strpos($url, 'api') !== false) {
				error_log('[FCRM_ES] HTTP request detected: ' . $url);
			}
		}

		// Only intercept FCRM API calls
		if (!self::is_fcrm_api_url($url)) {
			return $preempt;
		}

		// Skip caching if disabled
		if (!Cache_Manager::is_caching_enabled()) {
			if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[FCRM_ES] Caching is DISABLED - skipping cache for: ' . $url);
			}
			return $preempt;
		}

		// Parse the API call type and parameters
		$api_info = self::parse_api_url($url, $args);
		
		if (!$api_info) {
			return $preempt;
		}

		// Try to get cached response
		$cached_response = self::get_cached_response($api_info);
		
		$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
		if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
			$debug_id = isset($api_info['client_id']) ? $api_info['client_id'] : ($api_info['file_number'] ?? '');
			$ti = $api_info['team_index'] ?? '';
			$g = !empty($api_info['gallery']) ? '1' : '0';
			$ex = !empty($api_info['extra']) ? '1' : '0';
			$hit = ($cached_response !== false ? 'HIT' : 'MISS');
			error_log('[FCRM_ES] API intercept type=' . $api_info['type'] . ' id=' . $debug_id . ' team=' . $ti . ' g=' . $g . ' ex=' . $ex . ' url=' . $url . ' cache=' . $hit);
			if ($hit === 'HIT') {
				error_log('[FCRM_ES] API cache key=' . print_r($api_info, true));
			}
		}
		
		if ($cached_response !== false) {
			// Return cached response in WordPress HTTP API format
			return [
				'headers' => [],
				'body' => json_encode($cached_response),
				'response' => [
					'code' => 200,
					'message' => 'OK'
				],
				'cookies' => [],
				'filename' => null,
				'http_response' => null
			];
		}

		// If no cache hit, let the request proceed normally
		// We'll cache the response in a separate hook
		add_filter('http_response', [__CLASS__, 'cache_api_response'], 10, 3);
		
		return $preempt;
	}

	/**
	 * Cache API response after successful request
	 *
	 * @param array $response HTTP response
	 * @param array $args Request arguments
	 * @param string $url Request URL
	 * @return array Unmodified response
	 */
	public static function cache_api_response($response, $args, $url) {
		// Only cache FCRM API responses
		if (!self::is_fcrm_api_url($url)) {
			return $response;
		}

		// Only cache successful responses
		if (wp_remote_retrieve_response_code($response) !== 200) {
			return $response;
		}

		$api_info = self::parse_api_url($url, $args);
		
		if (!$api_info) {
			return $response;
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body);

		if ($data) {
			self::cache_response($api_info, $data);
		}

		// Remove this filter to avoid caching other requests
		remove_filter('http_response', [__CLASS__, 'cache_api_response'], 10);

		return $response;
	}

	/**
	 * Check if URL is an FCRM API endpoint
	 *
	 * @param string $url Request URL
	 * @return bool Whether this is an FCRM API URL
	 */
	private static function is_fcrm_api_url($url) {
		static $cached_endpoints = null;

		// Cache endpoint detection for performance
		if ($cached_endpoints === null) {
			$cached_endpoints = self::detect_fcrm_endpoints();
		}

		foreach ($cached_endpoints as $endpoint) {
			if (strpos($url, $endpoint) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect FCRM API endpoints from Firehawk plugin configuration
	 *
	 * Auto-detects endpoints by reading Firehawk's API configuration via reflection.
	 * Falls back to known patterns if auto-detection fails.
	 *
	 * @return array Array of API endpoint hostnames
	 */
	private static function detect_fcrm_endpoints() {
		$endpoints = [];
		$detection_method = 'unknown';

		// Method 1: Auto-detect from Firehawk plugin (PRIMARY)
		if (class_exists('Fcrm_Tributes_Api')) {
			try {
				$reflection = new \ReflectionClass('Fcrm_Tributes_Api');

				// Get main API URL
				if ($reflection->hasProperty('apiUrl')) {
					$apiUrl_prop = $reflection->getProperty('apiUrl');
					$apiUrl_prop->setAccessible(true);
					$apiUrl = $apiUrl_prop->getValue();

					if ($apiUrl) {
						$host = parse_url($apiUrl, PHP_URL_HOST);
						if ($host) {
							$endpoints[] = $host;
						}
					}
				}

				// Get services API URL (Google Cloud Functions)
				if ($reflection->hasProperty('servicesApiUrl')) {
					$servicesUrl_prop = $reflection->getProperty('servicesApiUrl');
					$servicesUrl_prop->setAccessible(true);
					$servicesUrl = $servicesUrl_prop->getValue();

					if ($servicesUrl) {
						$host = parse_url($servicesUrl, PHP_URL_HOST);
						if ($host) {
							$endpoints[] = $host;
						}
					}
				}

				if (!empty($endpoints)) {
					$detection_method = 'reflection';
				}
			} catch (Exception $e) {
				// Log error if debug enabled
				$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
				if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[FCRM_ES] Failed to auto-detect API endpoints via reflection: ' . $e->getMessage());
				}
			}
		}

		// Method 2: Fallback to known patterns (SECONDARY)
		if (empty($endpoints)) {
			$endpoints = [
				// v2.2.0 Production endpoints
				'api.firehawkcrm.com',
				'us-central1-fcrm-e17b0.cloudfunctions.net',

				// v2.3.1 UAT endpoints
				'api.ivcuat.firehawkfunerals.com',
				'australia-southeast1-firehawk-ivc-test.cloudfunctions.net',

				// v2.3.1 Dev endpoints
				'api.ivcdev.firehawkfunerals.com',
				'australia-southeast1-firehawk-ivc-dev.cloudfunctions.net',

				// Broad pattern for future Google Cloud Functions
				'cloudfunctions.net'
			];
			$detection_method = 'fallback';
		}

		// Remove duplicates and empty values
		$endpoints = array_unique(array_filter($endpoints));

		// Debug logging
		$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
		if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[FCRM_ES] API endpoints detected: ' . implode(', ', $endpoints) . ' (source: ' . $detection_method . ')');
		}

		return $endpoints;
	}

	/**
	 * Detect if FireHawk is using new API structure
	 *
	 * Detection strategy: Check if the API supports the new sitemap-count endpoint.
	 * Falls back to checking for known endpoint patterns as a secondary method.
	 *
	 * New API structure: /api/tributes/sitemap-count (returns number of sitemap pages)
	 * Old API structure: /api/tributes/count (returns total number of tributes)
	 *
	 * @return bool True if using new API structure, false if using old
	 */
	public static function is_new_api_structure() {
		static $cached_result = null;

		// Return cached result if already detected
		if ($cached_result !== null) {
			return $cached_result;
		}

		// Method 1: Check if Fcrm_Tributes_Api has get_tributes_sitemap_count method
		if (class_exists('Fcrm_Tributes_Api')) {
			// Check if the new sitemap-count method exists
			if (method_exists('Fcrm_Tributes_Api', 'get_tributes_sitemap_count')) {
				$cached_result = true;
				return $cached_result;
			}

			// Method 2: Check API URL patterns as fallback
			// This catches cases where the method might have a different name
			try {
				$reflection = new \ReflectionClass('Fcrm_Tributes_Api');

				if ($reflection->hasProperty('apiUrl')) {
					$apiUrl_prop = $reflection->getProperty('apiUrl');
					$apiUrl_prop->setAccessible(true);
					$apiUrl = $apiUrl_prop->getValue();

					if ($apiUrl) {
						// Known new API patterns (will expand as we discover more)
						$new_api_patterns = [
							'ivcuat.firehawkfunerals.com',
							'ivcdev.firehawkfunerals.com',
							'australia-southeast1-',
						];

						foreach ($new_api_patterns as $pattern) {
							if (strpos($apiUrl, $pattern) !== false) {
								$cached_result = true;
								return $cached_result;
							}
						}

						// Known old API patterns
						$old_api_patterns = [
							'api.firehawkcrm.com',
							'us-central1-fcrm-e17b0',
						];

						foreach ($old_api_patterns as $pattern) {
							if (strpos($apiUrl, $pattern) !== false) {
								$cached_result = false;
								return $cached_result;
							}
						}
					}
				}
			} catch (Exception $e) {
				// Log error if debug enabled
				$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
				if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[FCRM_ES] Failed to detect API structure via reflection: ' . $e->getMessage());
				}
			}
		}

		// Default to old API structure for safety (works with existing production sites)
		$cached_result = false;
		return $cached_result;
	}

	/**
	 * Parse API URL to determine call type and parameters
	 *
	 * @param string $url Request URL
	 * @param array $args Request arguments
	 * @return array|false API information or false if not parseable
	 */
	private static function parse_api_url($url, $args) {
		// Parse different FCRM API endpoints
		
		// Client by file number: /api/client/file-number/{number}
		if (preg_match('/\/api\/client\/file-number\/([^\/\?]+)/', $url, $matches)) {
			$file_number = $matches[1];
			$query_params = [];
			
			$parsed_url = parse_url($url);
			if (isset($parsed_url['query'])) {
				parse_str($parsed_url['query'], $query_params);
			}
			
			return [
				'type' => 'client_by_number',
				'file_number' => $file_number,
				'team_index' => $query_params['teamGroupIndex'] ?? null,
				'gallery' => isset($query_params['gallery']),
				'extra' => isset($query_params['tribute']),
				'params' => $query_params
			];
		}
		
		// Single client: /api/client/{id} (exclude file-number path)
		if (preg_match('/\/api\/client\/([^\/\?]+)/', $url, $matches) && $matches[1] !== 'file-number') {
			$client_id = $matches[1];
			$query_params = [];
			
			// Parse query parameters
			$parsed_url = parse_url($url);
			if (isset($parsed_url['query'])) {
				parse_str($parsed_url['query'], $query_params);
			}
			
			return [
				'type' => 'single_client',
				'client_id' => $client_id,
				'team_index' => $query_params['teamGroupIndex'] ?? null,
				'gallery' => isset($query_params['gallery']),
				'extra' => isset($query_params['tribute']),
				'params' => $query_params
			];
		}

		// Client list: /api/clients/
		if (strpos($url, '/api/clients/') !== false) {
			$body_params = [];
			
			if (isset($args['body'])) {
				$body_params = is_string($args['body']) ? json_decode($args['body'], true) : $args['body'];
			}
			
			return [
				'type' => 'client_list',
				'params' => $body_params ?: []
			];
		}

		// Tribute messages: /api/client/{id}/messages
		if (preg_match('/\/api\/client\/([^\/]+)\/messages/', $url, $matches)) {
			$client_id = $matches[1];
			$body_params = [];

			if (isset($args['body'])) {
				$body_params = is_string($args['body']) ? json_decode($args['body'], true) : $args['body'];
			}

			return [
				'type' => 'tribute_messages',
				'client_id' => $client_id,
				'params' => $body_params ?: []
			];
		}

		// Tributes sitemap count: /api/tributes/sitemap-count (new API - returns sitemap page count)
		// Check this FIRST before regular count, since it also contains "count"
		if (strpos($url, '/api/tributes/sitemap-count') !== false) {
			return [
				'type' => 'tributes_sitemap_count',
				'params' => []
			];
		}

		// Tributes count: /api/tributes/count (old API - returns total tribute count)
		if (strpos($url, '/api/tributes/count') !== false) {
			return [
				'type' => 'tributes_count',
				'params' => []
			];
		}

		// Tributes sitemap data: /api/tributes/ POST (v2.2.0 - old API with sitemap params)
		if (strpos($url, '/api/tributes/') !== false && strpos($url, '/count') === false && strpos($url, '/sitemap') === false) {
			$body_params = [];
			if (isset($args['body'])) {
				$body_params = is_string($args['body']) ? json_decode($args['body'], true) : $args['body'];
			}

			// Check if this is a sitemap request
			if (isset($body_params['sitemap']) && $body_params['sitemap'] === true) {
				return [
					'type' => 'tributes_sitemap_data',
					'params' => $body_params
				];
			}
		}

		return false;
	}

	/**
	 * Get cached response for API call
	 *
	 * @param array $api_info API call information
	 * @return mixed|false Cached data or false if not found
	 */
	private static function get_cached_response($api_info) {
		switch ($api_info['type']) {
			case 'single_client':
			case 'client_by_number':
				return Cache_Manager::get_client(
					$api_info['client_id'] ?? $api_info['file_number'],
					$api_info['team_index'],
					$api_info['gallery'],
					$api_info['extra']
				);

			case 'client_list':
				return Cache_Manager::get_client_list($api_info['params']);

			case 'tribute_messages':
				return Cache_Manager::get_tribute_messages($api_info['client_id'], $api_info['params']);

			case 'tributes_count':
				return Cache_Manager::get_tributes_count();

			case 'tributes_sitemap_count':
				return Cache_Manager::get_tributes_sitemap_count();

			default:
				return false;
		}
	}

	/**
	 * Cache API response
	 *
	 * @param array $api_info API call information
	 * @param mixed $data Response data
	 */
	private static function cache_response($api_info, $data) {
		switch ($api_info['type']) {
			case 'single_client':
			case 'client_by_number':
				Cache_Manager::set_client(
					$api_info['client_id'] ?? $api_info['file_number'],
					$data,
					$api_info['team_index'],
					$api_info['gallery'],
					$api_info['extra']
				);
				break;

			case 'client_list':
				Cache_Manager::set_client_list($api_info['params'], $data);
				break;

			case 'tribute_messages':
				Cache_Manager::set_tribute_messages($api_info['client_id'], $data, $api_info['params']);
				break;

			case 'tributes_count':
				Cache_Manager::set_tributes_count($data);
				break;

			case 'tributes_sitemap_count':
				Cache_Manager::set_tributes_sitemap_count($data);
				break;
		}
	}

	/**
	 * AJAX handler to clear all cache
	 */
	public static function ajax_clear_cache() {
		// Verify nonce and permissions
		if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'fcrm_clear_cache')) {
			wp_die('Unauthorised');
		}

		$success = Cache_Manager::clear_all_cache();

		wp_send_json([
			'success' => $success,
			'message' => $success ? 'Cache cleared successfully' : 'Failed to clear cache'
		]);
	}

	/**
	 * AJAX handler to clear specific client cache
	 */
	public static function ajax_clear_client_cache() {
		// Verify nonce and permissions
		if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'fcrm_clear_cache')) {
			wp_die('Unauthorised');
		}

		$client_id = sanitize_text_field($_POST['client_id'] ?? '');
		
		if (empty($client_id)) {
			wp_send_json(['success' => false, 'message' => 'Client ID required']);
		}

		$success = Cache_Manager::clear_client_cache($client_id);

		wp_send_json([
			'success' => $success,
			'message' => $success ? 'Client cache cleared successfully' : 'Failed to clear client cache'
		]);
	}

	/**
	 * Add cache controls to admin bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
	 */
	public static function add_admin_bar_cache_controls($wp_admin_bar) {
		// Only show to users who can manage options
		if (!current_user_can('manage_options')) {
			return;
		}

		// Only show on tribute-related pages
		if (!self::is_tribute_related_page()) {
			return;
		}

		$wp_admin_bar->add_node([
			'id' => 'fcrm-cache',
			'title' => 'FCRM Cache',
			'href' => '#',
		]);

		$wp_admin_bar->add_node([
			'id' => 'fcrm-cache-clear-all',
			'parent' => 'fcrm-cache',
			'title' => 'Clear All Cache',
			'href' => '#',
			'meta' => [
				'onclick' => 'fcrmClearCache(); return false;'
			]
		]);

		// Add client-specific cache clear if on single tribute page
		global $wp;
		if (isset($wp->query_vars['id'])) {
			$client_id = sanitize_text_field($wp->query_vars['id']);
			$wp_admin_bar->add_node([
				'id' => 'fcrm-cache-clear-client',
				'parent' => 'fcrm-cache',
				'title' => 'Clear This Tribute Cache',
				'href' => '#',
				'meta' => [
					'onclick' => "fcrmClearClientCache('{$client_id}'); return false;"
				]
			]);
		}

		// Add cache stats
		$stats = Cache_Manager::get_cache_stats();
		$wp_admin_bar->add_node([
			'id' => 'fcrm-cache-stats',
			'parent' => 'fcrm-cache',
			'title' => "Cached Items: {$stats['transient_count']}",
			'href' => admin_url('admin.php?page=fcrm-enhancements&tab=optimisation'),
		]);
	}

	/**
	 * Check if current page is tribute-related
	 *
	 * @return bool Whether current page is tribute-related
	 */
	private static function is_tribute_related_page() {
		global $post;

		// Check if we're on a tribute single page
		global $wp;
		if (isset($wp->query_vars['id'])) {
			return true;
		}

		// Check if we're on the tribute search page
		if (is_page(get_option('fcrm_tributes_search_page_id'))) {
			return true;
		}

		// Check for tribute shortcodes
		if ($post && is_a($post, 'WP_Post')) {
			$shortcodes = [
				'show_crm_tribute',
				'show_crm_tributes_grid',
				'show_crm_tributes_large_grid',
				'show_crm_tributes_carousel',
				'show_crm_tribute_search',
				'show_crm_tribute_search_bar'
			];

			$pattern = get_shortcode_regex($shortcodes);
			return preg_match('/' . $pattern . '/', $post->post_content);
		}

		return false;
	}

	/**
	 * Warm cache for specific client
	 *
	 * @param string $client_id Client ID
	 */
	public static function warm_client_cache($client_id) {
		// This would be called when we detect a tribute has been updated
		// For now, we'll just clear the cache to force a refresh
		Cache_Manager::clear_client_cache($client_id);
	}

	/**
	 * Check if caching is enabled from settings
	 *
	 * @param bool $enabled Current enabled status
	 * @return bool Whether caching is enabled
	 */
	public static function is_caching_enabled_from_settings($enabled) {
		// Prefer the new option if present, otherwise fall back to legacy UI option
		$opt_new = get_option('fcrm_enhancement_optimisation_enable_caching', null);
		if ($opt_new !== null) {
			return (bool) $opt_new;
		}
		$opt_legacy = get_option('fcrm_cache_enabled', null);
		if ($opt_legacy !== null) {
			return (bool) $opt_legacy;
		}
		return (bool) $enabled;
	}
}
