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
		// Only intercept FCRM API calls
		if (!self::is_fcrm_api_url($url)) {
			return $preempt;
		}

		// Skip caching if disabled
		if (!Cache_Manager::is_caching_enabled()) {
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
		$fcrm_endpoints = [
			'api.firehawkcrm.com',
			'us-central1-fcrm-e17b0.cloudfunctions.net'
		];

		foreach ($fcrm_endpoints as $endpoint) {
			if (strpos($url, $endpoint) !== false) {
				return true;
			}
		}

		return false;
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
