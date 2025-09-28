<?php
namespace FCRM\EnhancementSuite;

/**
 * Cache Manager
 * 
 * Handles intelligent caching for FCRM API responses with selective invalidation
 * and admin controls for cache management.
 */
class Cache_Manager {
	/**
	 * Cache group for FCRM data
	 */
	const CACHE_GROUP = 'fcrm_tributes';

	/**
	 * Cache durations in seconds
	 */
	const CACHE_DURATION_CLIENT_LIST = 1800;    // 30 minutes for client listings
	const CACHE_DURATION_SINGLE_CLIENT = 900;   // 15 minutes for individual clients
	const CACHE_DURATION_STATIC_CONTENT = 7200; // 2 hours for static content
	const CACHE_DURATION_MESSAGES = 300;        // 5 minutes for dynamic content

	/**
	 * Cache key prefixes
	 */
	const PREFIX_CLIENT = 'client_';
	const PREFIX_CLIENT_LIST = 'clients_';
	const PREFIX_MESSAGES = 'messages_';
	const PREFIX_TREES = 'trees_';
	const PREFIX_DONATIONS = 'donations_';

	/**
	 * Get cached client data
	 *
	 * @param string $client_id Client ID
	 * @param int|null $team_index Team index
	 * @param bool $gallery Include gallery
	 * @param bool $extra Include extra data
	 * @return mixed|false Cached data or false if not found
	 */
	public static function get_client($client_id, $team_index = null, $gallery = false, $extra = false) {
		$cache_key = self::build_client_cache_key($client_id, $team_index, $gallery, $extra);
		
		// Try WordPress object cache first (Redis if available)
		$cached_data = wp_cache_get($cache_key, self::CACHE_GROUP);
		
		if (false === $cached_data) {
			// Fallback to transients
			$cached_data = get_transient($cache_key);
		}

		return $cached_data;
	}

	/**
	 * Set cached client data
	 *
	 * @param string $client_id Client ID
	 * @param mixed $data Data to cache
	 * @param int|null $team_index Team index
	 * @param bool $gallery Include gallery
	 * @param bool $extra Include extra data
	 * @return bool Success status
	 */
	public static function set_client($client_id, $data, $team_index = null, $gallery = false, $extra = false) {
		$cache_key = self::build_client_cache_key($client_id, $team_index, $gallery, $extra);
		$duration = self::get_cache_duration('single_client');

		// Store in both object cache and transients for redundancy
		wp_cache_set($cache_key, $data, self::CACHE_GROUP, $duration);
		set_transient($cache_key, $data, $duration);

		return true;
	}

	/**
	 * Get cached client list
	 *
	 * @param array $params Query parameters
	 * @return mixed|false Cached data or false if not found
	 */
	public static function get_client_list($params) {
		$cache_key = self::build_client_list_cache_key($params);
		
		$cached_data = wp_cache_get($cache_key, self::CACHE_GROUP);
		
		if (false === $cached_data) {
			$cached_data = get_transient($cache_key);
		}

		return $cached_data;
	}

	/**
	 * Set cached client list
	 *
	 * @param array $params Query parameters
	 * @param mixed $data Data to cache
	 * @return bool Success status
	 */
	public static function set_client_list($params, $data) {
		$cache_key = self::build_client_list_cache_key($params);
		$duration = self::get_cache_duration('client_list');

		wp_cache_set($cache_key, $data, self::CACHE_GROUP, $duration);
		set_transient($cache_key, $data, $duration);

		return true;
	}

	/**
	 * Get cached tribute messages
	 *
	 * @param string $client_id Client ID
	 * @param array $params Additional parameters
	 * @return mixed|false Cached data or false if not found
	 */
	public static function get_tribute_messages($client_id, $params = []) {
		$cache_key = self::PREFIX_MESSAGES . $client_id . '_' . md5(serialize($params));
		
		$cached_data = wp_cache_get($cache_key, self::CACHE_GROUP);
		
		if (false === $cached_data) {
			$cached_data = get_transient($cache_key);
		}

		return $cached_data;
	}

	/**
	 * Set cached tribute messages
	 *
	 * @param string $client_id Client ID
	 * @param mixed $data Data to cache
	 * @param array $params Additional parameters
	 * @return bool Success status
	 */
	public static function set_tribute_messages($client_id, $data, $params = []) {
		$cache_key = self::PREFIX_MESSAGES . $client_id . '_' . md5(serialize($params));
		$duration = self::get_cache_duration('messages');

		wp_cache_set($cache_key, $data, self::CACHE_GROUP, $duration);
		set_transient($cache_key, $data, $duration);

		return true;
	}

	/**
	 * Clear all FCRM caches
	 *
	 * @return bool Success status
	 */
	public static function clear_all_cache() {
		global $wpdb;

		// Clear object cache group
		wp_cache_flush_group(self::CACHE_GROUP);

		// Clear all FCRM transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::CACHE_GROUP . '%',
				'_transient_timeout_' . self::CACHE_GROUP . '%'
			)
		);

		// Clear individual client transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::PREFIX_CLIENT . '%',
				'_transient_timeout_' . self::PREFIX_CLIENT . '%'
			)
		);

		return true;
	}

	/**
	 * Clear cache for specific client
	 *
	 * @param string $client_id Client ID
	 * @return bool Success status
	 */
	public static function clear_client_cache($client_id) {
		global $wpdb;

		// Clear object cache entries for this client
		$patterns = [
			self::PREFIX_CLIENT . $client_id . '_*',
			self::PREFIX_MESSAGES . $client_id . '_*',
			self::PREFIX_TREES . $client_id . '_*',
			self::PREFIX_DONATIONS . $client_id . '_*'
		];

		foreach ($patterns as $pattern) {
			wp_cache_delete($pattern, self::CACHE_GROUP);
		}

		// Clear transients for this client
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::PREFIX_CLIENT . $client_id . '%',
				'_transient_timeout_' . self::PREFIX_CLIENT . $client_id . '%'
			)
		);

		return true;
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache statistics
	 */
	public static function get_cache_stats() {
		global $wpdb;

		$transient_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . self::CACHE_GROUP . '%'
			)
		);

		return [
			'transient_count' => (int) $transient_count,
			'cache_group' => self::CACHE_GROUP,
			'redis_available' => function_exists('wp_cache_supports') && wp_cache_supports('flush_group')
		];
	}

	/**
	 * Build cache key for individual client
	 *
	 * @param string $client_id Client ID
	 * @param int|null $team_index Team index
	 * @param bool $gallery Include gallery
	 * @param bool $extra Include extra data
	 * @return string Cache key
	 */
	private static function build_client_cache_key($client_id, $team_index = null, $gallery = false, $extra = false) {
		$key_parts = [
			self::CACHE_GROUP,
			self::PREFIX_CLIENT,
			$client_id
		];

		if ($team_index !== null) {
			$key_parts[] = 'team_' . $team_index;
		}

		if ($gallery) {
			$key_parts[] = 'gallery';
		}

		if ($extra) {
			$key_parts[] = 'extra';
		}

		return implode('_', $key_parts);
	}

	/**
	 * Build cache key for client list
	 *
	 * @param array $params Query parameters
	 * @return string Cache key
	 */
	private static function build_client_list_cache_key($params) {
		// Sort parameters for consistent cache keys
		ksort($params);
		
		return self::CACHE_GROUP . '_' . self::PREFIX_CLIENT_LIST . md5(serialize($params));
	}

	/**
	 * Check if caching is enabled
	 *
	 * @return bool Whether caching is enabled
	 */
	public static function is_caching_enabled() {
		// Prefer the new option if present
		$enabled_new = get_option('fcrm_enhancement_optimisation_enable_caching', null);
		if ($enabled_new !== null) {
			$enabled = (bool) $enabled_new;
		} else {
			// Fallback to legacy UI option
			$enabled = (bool) get_option('fcrm_cache_enabled', true);
		}
		return apply_filters('fcrm_enhancement_caching_enabled', $enabled);
	}

	/**
	 * Get cache duration for specific content type
	 *
	 * @param string $type Content type
	 * @return int Cache duration in seconds
	 */
	public static function get_cache_duration($type) {
		$defaults = [
			'client_list' => self::CACHE_DURATION_CLIENT_LIST,
			'single_client' => self::CACHE_DURATION_SINGLE_CLIENT,
			'static_content' => self::CACHE_DURATION_STATIC_CONTENT,
			'messages' => self::CACHE_DURATION_MESSAGES
		];

		// Get duration from settings if available
		$setting_key = 'fcrm_enhancement_optimisation_cache_duration_' . $type;
		$duration = get_option($setting_key, $defaults[$type] ?? self::CACHE_DURATION_SINGLE_CLIENT);

		return apply_filters('fcrm_enhancement_cache_duration_' . $type, $duration);
	}
} 