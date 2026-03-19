<?php
namespace FCRM\EnhancementSuite;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Instant Indexing
 *
 * Detects new tributes and submits them to search engine indexing APIs
 * for faster discovery. Supports Google Indexing API and IndexNow
 * (Bing, Yandex, DuckDuckGo).
 *
 * Detection uses a dual approach:
 * - WP-Cron scheduled event (hourly) for reliable baseline detection
 * - Cache intercept for near-instant detection on high-traffic sites
 *
 * Compatible with standard WP-Cron and GridPane's gp-cron (WP-CLI based).
 *
 * @package FCRM_Enhancement_Suite
 */
class Instant_Indexing {

	/**
	 * Cron hook name
	 */
	const CRON_HOOK = 'fcrm_check_new_tributes';

	/**
	 * Option key for known tribute IDs
	 */
	const KNOWN_TRIBUTES_OPTION = 'fcrm_known_tribute_ids';

	/**
	 * Option key for indexing log
	 */
	const INDEXING_LOG_OPTION = 'fcrm_indexing_log';

	/**
	 * Option key for daily submission count
	 */
	const DAILY_COUNT_OPTION = 'fcrm_indexing_daily_count';

	/**
	 * Option key for IndexNow API key
	 */
	const INDEXNOW_KEY_OPTION = 'fcrm_indexnow_api_key';

	/**
	 * Maximum log entries to keep
	 */
	const MAX_LOG_ENTRIES = 50;

	/**
	 * Initialize instant indexing
	 */
	public static function init() {
		// Register cron schedule
		add_action(self::CRON_HOOK, [__CLASS__, 'check_for_new_tributes']);

		// Schedule cron if not already scheduled
		add_action('init', [__CLASS__, 'schedule_cron']);

		// Register settings
		add_action('admin_init', [__CLASS__, 'register_settings']);

		// AJAX handlers for admin
		add_action('wp_ajax_fcrm_test_indexing', [__CLASS__, 'ajax_test_indexing']);
		add_action('wp_ajax_fcrm_clear_indexing_log', [__CLASS__, 'ajax_clear_indexing_log']);
		add_action('wp_ajax_fcrm_check_new_tributes', [__CLASS__, 'ajax_check_new_tributes']);
		add_action('wp_ajax_fcrm_reset_known_tributes', [__CLASS__, 'ajax_reset_known_tributes']);

		// Serve IndexNow verification file
		add_action('parse_request', [__CLASS__, 'maybe_serve_indexnow_key'], 1);

		// Clean up on deactivation
		register_deactivation_hook(FCRM_ENHANCEMENT_SUITE_PLUGIN_FILE, [__CLASS__, 'deactivate']);
	}

	/**
	 * Schedule the cron event if not already scheduled
	 */
	public static function schedule_cron() {
		if (!self::is_any_indexing_enabled()) {
			// Unschedule if indexing is disabled
			$timestamp = wp_next_scheduled(self::CRON_HOOK);
			if ($timestamp) {
				wp_unschedule_event($timestamp, self::CRON_HOOK);
			}
			return;
		}

		if (!wp_next_scheduled(self::CRON_HOOK)) {
			wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
		}
	}

	/**
	 * Clean up on plugin deactivation
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled(self::CRON_HOOK);
		if ($timestamp) {
			wp_unschedule_event($timestamp, self::CRON_HOOK);
		}
	}

	/**
	 * Check if any indexing method is enabled
	 *
	 * @return bool
	 */
	private static function is_any_indexing_enabled() {
		return self::is_google_indexing_enabled() || self::is_indexnow_enabled();
	}

	/**
	 * Check if Google Indexing API is enabled
	 *
	 * @return bool
	 */
	private static function is_google_indexing_enabled() {
		return (bool) get_option('fcrm_indexing_google_enabled', false);
	}

	/**
	 * Check if IndexNow is enabled
	 *
	 * @return bool
	 */
	private static function is_indexnow_enabled() {
		return (bool) get_option('fcrm_indexing_indexnow_enabled', false);
	}

	/**
	 * Check for new tributes (called by WP-Cron)
	 */
	public static function check_for_new_tributes() {
		if (!class_exists('Fcrm_Tributes_Api')) {
			return;
		}

		if (!self::is_any_indexing_enabled()) {
			return;
		}

		self::log_debug('Cron check for new tributes started');

		// Increase timeout — same fix as sitemap generator
		$timeout_filter = self::get_timeout_filter();
		add_filter('http_request_args', $timeout_filter, 10, 2);

		try {
			// Fetch tributes from all sitemap pages
			$page_count = Sitemap_Generator::get_sitemap_page_count();
			$all_tributes = [];

			for ($page = 1; $page <= $page_count; $page++) {
				// API uses 0-based offset: page 1 = offset 0
				$tributes = \Fcrm_Tributes_Api::get_tributes_sitemap($page - 1);

				// Handle API response formats
				// FireHawk returns object with ->results property
				if (is_object($tributes) && isset($tributes->results)) {
					$tributes = $tributes->results;
				} elseif (is_object($tributes) && isset($tributes->clients)) {
					$tributes = $tributes->clients;
				} elseif (empty($tributes) || !is_array($tributes)) {
					continue;
				}

				foreach ($tributes as $tribute) {
					$all_tributes[] = $tribute;
				}
			}

			if (empty($all_tributes)) {
				return;
			}

			// Get current tribute IDs
			$current_ids = [];
			foreach ($all_tributes as $tribute) {
				if (isset($tribute->id)) {
					$current_ids[] = $tribute->id;
				}
			}

			if (empty($current_ids)) {
				return;
			}

			// Compare with known IDs
			$known_ids = get_option(self::KNOWN_TRIBUTES_OPTION, []);

			if (empty($known_ids)) {
				// First run — store current IDs without submitting
				update_option(self::KNOWN_TRIBUTES_OPTION, $current_ids, false);
				self::log_debug('First run — stored ' . count($current_ids) . ' known tribute IDs');
				return;
			}

			// Find new tributes
			$new_ids = array_diff($current_ids, $known_ids);

			if (!empty($new_ids)) {
				self::log_debug('Found ' . count($new_ids) . ' new tributes');

				// Build URLs for new tributes
				$new_urls = [];
				foreach ($all_tributes as $tribute) {
					if (isset($tribute->id) && in_array($tribute->id, $new_ids, true)) {
						$url = self::build_tribute_url($tribute);
						if (!empty($url)) {
							$new_urls[] = $url;
						}
					}
				}

				// Submit to indexing APIs
				if (!empty($new_urls)) {
					self::submit_urls($new_urls);
				}

				// Update known IDs
				update_option(self::KNOWN_TRIBUTES_OPTION, $current_ids, false);
			}

			// Check for removed tributes
			$removed_ids = array_diff($known_ids, $current_ids);
			if (!empty($removed_ids)) {
				// Update known IDs even if no new ones
				update_option(self::KNOWN_TRIBUTES_OPTION, $current_ids, false);
				self::log_debug(count($removed_ids) . ' tributes removed from list');
			}

		} catch (\Exception $e) {
			self::log_debug('Error checking for new tributes: ' . $e->getMessage());
		}

		remove_filter('http_request_args', $timeout_filter, 10);
	}

	/**
	 * Submit URLs to enabled indexing APIs
	 *
	 * @param array $urls Array of URLs to submit
	 */
	public static function submit_urls($urls) {
		if (empty($urls)) {
			return;
		}

		// Submit to IndexNow first (no quota concerns — batch submit all)
		if (self::is_indexnow_enabled()) {
			self::submit_to_indexnow($urls);
		}

		// Submit to Google Indexing API (with daily quota)
		if (self::is_google_indexing_enabled()) {
			$daily_count = self::get_daily_submission_count();
			$daily_limit = (int) get_option('fcrm_indexing_google_quota', 200);

			$urls_to_submit = array_slice($urls, 0, max(0, $daily_limit - $daily_count));

			if (empty($urls_to_submit)) {
				self::log_entry('warning', 'Google daily quota reached (' . $daily_limit . '). ' . count($urls) . ' URLs skipped for Google.');
			} else {
				self::submit_to_google($urls_to_submit);
			}
		}
	}

	/**
	 * Submit URLs to Google Indexing API
	 *
	 * @param array $urls URLs to submit
	 */
	private static function submit_to_google($urls) {
		$credentials = get_option('fcrm_indexing_google_credentials', '');

		if (empty($credentials)) {
			self::log_entry('error', 'Google Indexing: No service account credentials configured');
			return;
		}

		$credentials_data = json_decode($credentials, true);

		if (empty($credentials_data) || !isset($credentials_data['client_email']) || !isset($credentials_data['private_key'])) {
			self::log_entry('error', 'Google Indexing: Invalid service account credentials');
			return;
		}

		// Get access token
		$access_token = self::get_google_access_token($credentials_data);

		if (empty($access_token)) {
			self::log_entry('error', 'Google Indexing: Failed to obtain access token');
			return;
		}

		$success_count = 0;
		$error_count = 0;

		foreach ($urls as $url) {
			$response = wp_remote_post(
				'https://indexing.googleapis.com/v3/urlNotifications:publish',
				[
					'headers' => [
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $access_token,
					],
					'body' => wp_json_encode([
						'url'  => $url,
						'type' => 'URL_UPDATED',
					]),
					'timeout' => 15,
				]
			);

			if (is_wp_error($response)) {
				$error_count++;
				self::log_debug('Google Indexing error for ' . $url . ': ' . $response->get_error_message());
			} else {
				$code = wp_remote_retrieve_response_code($response);
				if ($code === 200) {
					$success_count++;
				} else {
					$error_count++;
					$body = wp_remote_retrieve_body($response);
					self::log_debug('Google Indexing HTTP ' . $code . ' for ' . $url . ': ' . $body);
				}
			}
		}

		self::increment_daily_count($success_count);
		self::log_entry(
			$error_count > 0 ? 'warning' : 'success',
			'Google Indexing: ' . $success_count . '/' . count($urls) . ' URLs submitted'
			. ($error_count > 0 ? ' (' . $error_count . ' errors)' : '')
		);
	}

	/**
	 * Get Google OAuth2 access token using service account credentials
	 *
	 * @param array $credentials Service account credentials
	 * @return string|false Access token or false on failure
	 */
	private static function get_google_access_token($credentials) {
		// Check for cached token (valid for ~55 minutes)
		$cached_token = get_transient('fcrm_google_indexing_token');
		if ($cached_token) {
			return $cached_token;
		}

		$now = time();
		$expiry = $now + 3600;

		// Build JWT header
		$header = wp_json_encode([
			'alg' => 'RS256',
			'typ' => 'JWT',
		]);

		// Build JWT claims
		$claims = wp_json_encode([
			'iss'   => $credentials['client_email'],
			'scope' => 'https://www.googleapis.com/auth/indexing',
			'aud'   => 'https://oauth2.googleapis.com/token',
			'iat'   => $now,
			'exp'   => $expiry,
		]);

		// Encode header and claims
		$header_encoded = self::base64url_encode($header);
		$claims_encoded = self::base64url_encode($claims);
		$signing_input = $header_encoded . '.' . $claims_encoded;

		// Sign with private key
		$private_key = openssl_pkey_get_private($credentials['private_key']);

		if (!$private_key) {
			self::log_debug('Google OAuth: Failed to load private key');
			return false;
		}

		$signature = '';
		$sign_result = openssl_sign($signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256);

		if (!$sign_result) {
			self::log_debug('Google OAuth: Failed to sign JWT');
			return false;
		}

		$jwt = $signing_input . '.' . self::base64url_encode($signature);

		// Exchange JWT for access token
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			[
				'body' => [
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $jwt,
				],
				'timeout' => 15,
			]
		);

		if (is_wp_error($response)) {
			self::log_debug('Google OAuth error: ' . $response->get_error_message());
			return false;
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['access_token'])) {
			// Cache token for 55 minutes (expires at 60)
			set_transient('fcrm_google_indexing_token', $body['access_token'], 3300);
			return $body['access_token'];
		}

		self::log_debug('Google OAuth: No access token in response: ' . wp_remote_retrieve_body($response));
		return false;
	}

	/**
	 * Submit URLs to IndexNow API
	 *
	 * @param array $urls URLs to submit
	 */
	private static function submit_to_indexnow($urls) {
		$api_key = self::get_or_create_indexnow_key();

		if (empty($api_key)) {
			self::log_entry('error', 'IndexNow: Failed to generate API key');
			return;
		}

		$host = wp_parse_url(home_url(), PHP_URL_HOST);

		$response = wp_remote_post(
			'https://api.indexnow.org/indexnow',
			[
				'headers' => [
					'Content-Type' => 'application/json; charset=utf-8',
				],
				'body' => wp_json_encode([
					'host'    => $host,
					'key'     => $api_key,
					'urlList' => array_values($urls),
				]),
				'timeout' => 15,
			]
		);

		if (is_wp_error($response)) {
			self::log_entry('error', 'IndexNow: ' . $response->get_error_message());
			return;
		}

		$code = wp_remote_retrieve_response_code($response);

		if ($code === 200 || $code === 202) {
			self::log_entry('success', 'IndexNow: ' . count($urls) . ' URLs submitted to Bing/Yandex/DuckDuckGo');
		} else {
			$body = wp_remote_retrieve_body($response);
			self::log_entry('error', 'IndexNow: HTTP ' . $code . ' — ' . $body);
		}
	}

	/**
	 * Get or create the IndexNow API key
	 *
	 * @return string API key
	 */
	public static function get_or_create_indexnow_key() {
		$key = get_option(self::INDEXNOW_KEY_OPTION, '');

		if (empty($key)) {
			$key = wp_generate_uuid4();
			$key = str_replace('-', '', $key); // IndexNow prefers no hyphens
			update_option(self::INDEXNOW_KEY_OPTION, $key, false);
		}

		return $key;
	}

	/**
	 * Serve the IndexNow verification key file
	 *
	 * @param \WP $wp WordPress request object
	 */
	public static function maybe_serve_indexnow_key($wp) {
		if (!self::is_indexnow_enabled()) {
			return;
		}

		$request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
		$path = wp_parse_url($request_uri, PHP_URL_PATH);
		$path = ltrim($path, '/');

		$key = get_option(self::INDEXNOW_KEY_OPTION, '');

		if (empty($key)) {
			return;
		}

		if ($path === $key . '.txt') {
			if (!headers_sent()) {
				status_header(200);
				header('Content-Type: text/plain; charset=UTF-8');
			}
			echo $key;
			exit;
		}
	}

	/**
	 * Build a tribute URL from a tribute object
	 *
	 * @param object $tribute Tribute object
	 * @return string URL
	 */
	private static function build_tribute_url($tribute) {
		$search_page_id = get_option('fcrm_tributes_search_page_id');
		$single_page_id = get_option('fcrm_tributes_single_page_id');
		$use_readable = get_option('fcrm_tributes_readable_permalinks', false);

		$base_url = '';
		if ($single_page_id) {
			$base_url = get_permalink($single_page_id);
		} elseif ($search_page_id) {
			$base_url = get_permalink($search_page_id);
		}

		if (empty($base_url)) {
			$base_url = home_url('/tributes/');
		}

		$base_url = trailingslashit($base_url);

		return Sitemap_Generator::build_tribute_url($tribute, $base_url, $use_readable);
	}

	/**
	 * Get daily submission count (resets at midnight)
	 *
	 * @return int
	 */
	private static function get_daily_submission_count() {
		$data = get_transient(self::DAILY_COUNT_OPTION);

		if (false === $data) {
			return 0;
		}

		return (int) $data;
	}

	/**
	 * Increment daily submission count
	 *
	 * @param int $count Number to add
	 */
	private static function increment_daily_count($count) {
		$current = self::get_daily_submission_count();
		$new_count = $current + $count;

		// Set transient that expires at midnight
		$seconds_until_midnight = strtotime('tomorrow') - time();
		set_transient(self::DAILY_COUNT_OPTION, $new_count, $seconds_until_midnight);
	}

	/**
	 * Add an entry to the indexing log
	 *
	 * @param string $type    Entry type: success, error, warning, info
	 * @param string $message Log message
	 */
	private static function log_entry($type, $message) {
		$log = get_option(self::INDEXING_LOG_OPTION, []);

		if (!is_array($log)) {
			$log = [];
		}

		// Add new entry at the beginning
		array_unshift($log, [
			'type'      => $type,
			'message'   => $message,
			'timestamp' => current_time('mysql'),
		]);

		// Keep only the last N entries
		$log = array_slice($log, 0, self::MAX_LOG_ENTRIES);

		update_option(self::INDEXING_LOG_OPTION, $log, false);

		// Also log to debug log
		self::log_debug('[' . $type . '] ' . $message);
	}

	/**
	 * Register admin settings
	 */
	public static function register_settings() {
		// Google Indexing API
		register_setting('fcrm_enhancement_seo_analytics', 'fcrm_indexing_google_enabled');
		register_setting('fcrm_enhancement_seo_analytics', 'fcrm_indexing_google_credentials', [
			'type' => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
		]);
		register_setting('fcrm_enhancement_seo_analytics', 'fcrm_indexing_google_quota', [
			'type' => 'integer',
			'default' => 200,
			'sanitize_callback' => 'absint',
		]);

		// IndexNow
		register_setting('fcrm_enhancement_seo_analytics', 'fcrm_indexing_indexnow_enabled');
	}

	/**
	 * AJAX handler: Test indexing by submitting the site's tribute page URL
	 */
	public static function ajax_test_indexing() {
		check_ajax_referer('fcrm_indexing_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$test_url = home_url('/tributes/');
		$results = [];

		if (self::is_google_indexing_enabled()) {
			$credentials = get_option('fcrm_indexing_google_credentials', '');
			$credentials_data = json_decode($credentials, true);

			if (!empty($credentials_data)) {
				$token = self::get_google_access_token($credentials_data);
				$results['google'] = $token ? 'Authentication successful' : 'Authentication failed';
			} else {
				$results['google'] = 'No credentials configured';
			}
		}

		if (self::is_indexnow_enabled()) {
			$key = self::get_or_create_indexnow_key();
			$results['indexnow'] = 'API key: ' . substr($key, 0, 8) . '...';
		}

		if (empty($results)) {
			wp_send_json_error('No indexing methods enabled');
		}

		wp_send_json_success($results);
	}

	/**
	 * AJAX handler: Check for new tributes now (manual trigger)
	 */
	public static function ajax_check_new_tributes() {
		check_ajax_referer('fcrm_indexing_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		if (!class_exists('Fcrm_Tributes_Api')) {
			wp_send_json_error('FireHawk API not available');
		}

		$dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';

		// Increase timeout — same fix as sitemap generator
		$timeout_filter = self::get_timeout_filter();
		add_filter('http_request_args', $timeout_filter, 10, 2);

		try {
			// Fetch tributes from all sitemap pages
			$page_count = Sitemap_Generator::get_sitemap_page_count();
			$all_tributes = [];

			for ($page = 1; $page <= $page_count; $page++) {
				$response = \Fcrm_Tributes_Api::get_tributes_sitemap($page - 1);

				if (is_object($response) && isset($response->results)) {
					$tributes = $response->results;
				} elseif (is_object($response) && isset($response->clients)) {
					$tributes = $response->clients;
				} elseif (is_array($response)) {
					$tributes = $response;
				} else {
					continue;
				}

				foreach ($tributes as $tribute) {
					$all_tributes[] = $tribute;
				}
			}

			// Get current IDs
			$current_ids = [];
			foreach ($all_tributes as $tribute) {
				if (isset($tribute->id)) {
					$current_ids[] = $tribute->id;
				}
			}

			$known_ids = get_option(self::KNOWN_TRIBUTES_OPTION, []);
			$new_ids = empty($known_ids) ? $current_ids : array_diff($current_ids, $known_ids);

			// Build URLs for new tributes
			$new_urls = [];
			foreach ($all_tributes as $tribute) {
				if (isset($tribute->id) && in_array($tribute->id, $new_ids, true)) {
					$url = self::build_tribute_url($tribute);
					$name = '';
					if (isset($tribute->firstName)) {
						$name .= $tribute->firstName;
					}
					if (isset($tribute->lastName)) {
						$name .= ' ' . $tribute->lastName;
					}
					if (!empty($url)) {
						$new_urls[] = [
							'url'  => $url,
							'name' => trim($name),
						];
					}
				}
			}

			$result = [
				'total_tributes' => count($current_ids),
				'known_tributes' => count($known_ids),
				'new_tributes'   => count($new_ids),
				'new_urls'       => array_slice($new_urls, 0, 20), // Show first 20
				'first_run'      => empty($known_ids),
				'dry_run'        => $dry_run,
			];

			if (!$dry_run && !empty($new_ids)) {
				// Actually submit URLs
				$submit_urls = array_column($new_urls, 'url');
				self::submit_urls($submit_urls);

				// Update known IDs
				update_option(self::KNOWN_TRIBUTES_OPTION, $current_ids, false);

				$result['submitted'] = true;
				self::log_entry('info', 'Manual check: ' . count($new_ids) . ' new tributes found and submitted');
			} elseif (empty($known_ids)) {
				// First run — just store known IDs
				update_option(self::KNOWN_TRIBUTES_OPTION, $current_ids, false);
				self::log_entry('info', 'Manual check: First run — stored ' . count($current_ids) . ' known tribute IDs');
			}

			remove_filter('http_request_args', $timeout_filter, 10);
			wp_send_json_success($result);

		} catch (\Exception $e) {
			remove_filter('http_request_args', $timeout_filter, 10);
			wp_send_json_error('Error: ' . $e->getMessage());
		}
	}

	/**
	 * AJAX handler: Reset known tributes (forces re-detection on next check)
	 */
	public static function ajax_reset_known_tributes() {
		check_ajax_referer('fcrm_indexing_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		delete_option(self::KNOWN_TRIBUTES_OPTION);
		self::log_entry('info', 'Known tributes list reset — next check will re-detect all tributes');
		wp_send_json_success('Known tributes reset');
	}

	/**
	 * AJAX handler: Clear indexing log
	 */
	public static function ajax_clear_indexing_log() {
		check_ajax_referer('fcrm_indexing_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		update_option(self::INDEXING_LOG_OPTION, [], false);
		wp_send_json_success('Log cleared');
	}

	/**
	 * Render the instant indexing settings section
	 */
	public static function render_settings() {
		$google_enabled = get_option('fcrm_indexing_google_enabled', false);
		$google_credentials = get_option('fcrm_indexing_google_credentials', '');
		$google_quota = get_option('fcrm_indexing_google_quota', 200);
		$indexnow_enabled = get_option('fcrm_indexing_indexnow_enabled', false);
		$indexnow_key = self::get_or_create_indexnow_key();
		$log = get_option(self::INDEXING_LOG_OPTION, []);
		$daily_count = self::get_daily_submission_count();
		?>
		<div class="settings-section">
			<h3>⚡ Instant Indexing</h3>
			<div class="section-content">
				<p>Automatically notify search engines when new tributes are published, so they appear in search results faster.</p>

				<div class="notice notice-info inline" style="margin-bottom: 15px;">
					<p><strong>How it works:</strong> The plugin checks for new tributes hourly via WP-Cron (compatible with GridPane's gp-cron) and submits new URLs to Google and Bing/Yandex for immediate indexing.</p>
				</div>

				<!-- Google Indexing API -->
				<h4 style="margin-top: 20px;">Google Indexing API</h4>
				<table class="form-table">
					<tr>
						<th scope="row">Enable Google Indexing</th>
						<td>
							<label class="toggle-switch">
								<input type="checkbox"
									   name="fcrm_indexing_google_enabled"
									   value="1"
									   <?php checked($google_enabled, 1); ?>>
								<span class="toggle-slider"></span>
							</label>
							<p class="description">Submit new tribute URLs to Google for faster indexing. Requires a Google Cloud service account with Indexing API access.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Service Account Credentials</th>
						<td>
							<textarea name="fcrm_indexing_google_credentials"
									  rows="4"
									  class="large-text code"
									  placeholder='Paste your Google service account JSON credentials here...'
							><?php echo esc_textarea($google_credentials); ?></textarea>
							<p class="description">
								Paste the full JSON contents of your Google Cloud service account key file.
								<a href="https://developers.google.com/search/apis/indexing-api/v3/prereqs" target="_blank" rel="noopener noreferrer">Setup guide</a>
								<br><em>Credentials are stored in your WordPress database. Ensure your database access is properly secured.</em>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Daily Quota</th>
						<td>
							<input type="number"
								   name="fcrm_indexing_google_quota"
								   value="<?php echo esc_attr($google_quota); ?>"
								   min="1"
								   max="10000"
								   class="small-text" />
							<span class="description">requests per day (Google default: 200). Today's usage: <strong><?php echo esc_html($daily_count); ?></strong></span>
						</td>
					</tr>
				</table>

				<!-- IndexNow -->
				<h4 style="margin-top: 20px;">IndexNow (Bing, Yandex, DuckDuckGo)</h4>
				<table class="form-table">
					<tr>
						<th scope="row">Enable IndexNow</th>
						<td>
							<label class="toggle-switch">
								<input type="checkbox"
									   name="fcrm_indexing_indexnow_enabled"
									   value="1"
									   <?php checked($indexnow_enabled, 1); ?>>
								<span class="toggle-slider"></span>
							</label>
							<p class="description">Submit new tribute URLs to Bing, Yandex, and DuckDuckGo via IndexNow protocol. No setup required — API key is auto-generated.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">API Key</th>
						<td>
							<code><?php echo esc_html($indexnow_key); ?></code>
							<p class="description">Auto-generated. Verification file served at <code><?php echo esc_url(home_url('/' . $indexnow_key . '.txt')); ?></code></p>
						</td>
					</tr>
				</table>

				<!-- Test & Status -->
				<h4 style="margin-top: 20px;">Status & Log</h4>
				<table class="form-table">
					<tr>
						<th scope="row">Check for New Tributes</th>
						<td>
							<button type="button" id="fcrm-check-tributes-dry" class="button button-primary">
								Check Now (Dry Run)
							</button>
							<button type="button" id="fcrm-check-tributes-submit" class="button" style="margin-left: 10px;">
								Check & Submit
							</button>
							<button type="button" id="fcrm-reset-known" class="button" style="margin-left: 10px;">
								Reset Known List
							</button>
							<p class="description">
								<strong>Dry Run</strong> shows what would be submitted without sending. <strong>Check & Submit</strong> detects and submits new URLs to enabled APIs. <strong>Reset</strong> clears the known tributes list so the next check treats all tributes as new.
							</p>
							<div id="fcrm-check-results" style="margin-top: 10px; display: none;"></div>
						</td>
					</tr>
					<tr>
						<th scope="row">Other Actions</th>
						<td>
							<button type="button" id="fcrm-test-indexing" class="button">
								Test API Connection
							</button>
							<button type="button" id="fcrm-clear-indexing-log" class="button" style="margin-left: 10px;">
								Clear Log
							</button>
							<span id="fcrm-indexing-test-result" style="margin-left: 10px;"></span>
						</td>
					</tr>
				</table>

				<?php if (!empty($log)): ?>
				<div class="fcrm-indexing-log" style="margin-top: 15px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background: #f9f9f9;">
					<table class="widefat striped" style="margin: 0;">
						<thead>
							<tr>
								<th style="width: 150px;">Time</th>
								<th style="width: 70px;">Status</th>
								<th>Message</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach (array_slice($log, 0, 10) as $entry): ?>
							<tr>
								<td style="font-size: 12px;"><?php echo esc_html($entry['timestamp']); ?></td>
								<td>
									<?php
									$status_icons = [
										'success' => '✅',
										'error'   => '❌',
										'warning' => '⚠️',
										'info'    => 'ℹ️',
									];
									echo $status_icons[$entry['type']] ?? '•';
									?>
								</td>
								<td style="font-size: 13px;"><?php echo esc_html($entry['message']); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php else: ?>
					<p class="description" style="margin-top: 15px;">No indexing activity yet. New entries will appear here when tributes are detected and submitted.</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * URL-safe base64 encoding
	 *
	 * @param string $data Data to encode
	 * @return string Encoded string
	 */
	private static function base64url_encode($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * Get a timeout filter for sitemap API calls
	 *
	 * FireHawk's default 5s timeout is too short for fetching 500 tributes
	 * from Cloud Functions cold starts.
	 *
	 * @return \Closure Filter callback (add/remove with http_request_args)
	 */
	private static function get_timeout_filter() {
		return function ($args, $url) {
			if (strpos($url, '/api/tributes/') !== false) {
				$args['timeout'] = 30;
			}
			return $args;
		};
	}

	/**
	 * Debug logging helper
	 *
	 * @param string $message Log message
	 */
	private static function log_debug($message) {
		$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
		if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[FCRM_ES Indexing] ' . $message);
		}
	}
}
