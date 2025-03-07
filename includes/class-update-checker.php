<?php
/**
 * Plugin Update Checker
 *
 * Enables automatic updates from GitHub releases for WordPress plugins.
 * Features:
 * - GitHub API integration with rate limit protection
 * - Response caching to minimize API calls (4 hour cache, 1 hour for errors)
 * - Detailed error logging
 * - Optional authentication token support
 */

namespace FCRM\EnhancementSuite;

if (!class_exists('FCRM\EnhancementSuite\PluginUpdateChecker')) {
    class PluginUpdateChecker {
        private $file;            // Full path to plugin file
        private $plugin;          // Plugin metadata
        private $basename;        // Plugin basename
        private $github_response; // Cached GitHub API response
        private $github_url;      // GitHub repository URL (username/repository)
        private $authorize_token; // Optional GitHub API token
        
        // Cache settings
        private const CACHE_DURATION = 4; // Hours
        private const ERROR_CACHE_DURATION = 1; // Hour
        
        // Icons
        private const ICON_SMALL = "https://weave-hk-github.b-cdn.net/humankind/icon-128x128.png";
        private const ICON_LARGE = "https://weave-hk-github.b-cdn.net/humankind/icon-256x256.png";

        /**
         * Initialize the update checker with plugin file and GitHub repository
         *
         * @param string $file Full path to main plugin file
         * @param string $github_url GitHub repository in format 'username/repository'
         * @param string|null $authorize_token Optional GitHub API token
         */
        public function __construct($file, $github_url, $authorize_token = null) {
            $this->file = $file;
            $this->github_url = $github_url;
            $this->authorize_token = $authorize_token;
            $this->basename = plugin_basename($file);

            // Hook into WordPress update system
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
            add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
            add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        }
        
        /**
         * Initialize as a singleton instance
         *
         * @param string $file Full path to main plugin file
         * @param string $github_url GitHub repository in format 'username/repository'
         * @param string|null $authorize_token Optional GitHub API token 
         * @return PluginUpdateChecker Instance
         */
        public static function init($file, $github_url, $authorize_token = null) {
            static $instance = null;
            
            if ($instance === null) {
                $instance = new self($file, $github_url, $authorize_token);
            }
            
            return $instance;
        }
        
        /**
         * Get plugin data only when needed
         *
         * @return array Plugin data
         */
        private function get_plugin_data() {
            if (empty($this->plugin)) {
                // Load plugin metadata from the main plugin file
                $this->plugin = get_file_data($this->file, array(
                    'Name'        => 'Plugin Name',
                    'PluginURI'   => 'Plugin URI',
                    'Description' => 'Description',
                    'Author'      => 'Author',
                    'AuthorURI'   => 'Author URI',
                    'Version'     => 'Version',
                ));
            }
            
            return $this->plugin;
        }
        
        /**
         * Get cache key for this plugin
         *
         * @return string Cache key
         */
        private function get_cache_key() {
            return 'fcrm_github_update_' . md5($this->github_url);
        }

        /**
         * Fetch repository information from GitHub with caching
         * 
         * @return object|false GitHub API response or false on failure
         */
        private function get_repository_info() {
            // Check if we already fetched the information
            if (!is_null($this->github_response)) {
                return $this->github_response;
            }

            // Check for cached response
            $cache_key = $this->get_cache_key();
            $cached_response = get_transient($cache_key);
            
            if (false !== $cached_response) {
                // Check if this is an error response
                if (is_array($cached_response) && isset($cached_response['status']) && $cached_response['status'] === 'error') {
                    return false; // Return false but don't make a new request
                }
                
                $this->github_response = $cached_response;
                return $cached_response;
            }

            // Prepare the API request
            $request_uri = sprintf('https://api.github.com/repos/%s/releases/latest', $this->github_url);
            $args = array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                )
            );

            // Add authorization token if provided
            if ($this->authorize_token) {
                $args['headers']['Authorization'] = "Bearer {$this->authorize_token}";
            }

            // Make the API request
            $response = wp_remote_get($request_uri, $args);

            // Handle potential errors
            if (is_wp_error($response)) {
                error_log(sprintf(
                    'Plugin Update Checker: API request failed for %s. Error: %s',
                    $this->get_plugin_data()['Name'],
                    $response->get_error_message()
                ));
                
                // Cache error response
                set_transient($cache_key, ['status' => 'error'], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS);
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                error_log(sprintf(
                    'Plugin Update Checker: GitHub API returned code %d for %s',
                    $response_code,
                    $this->get_plugin_data()['Name']
                ));
                
                // Cache error response
                set_transient($cache_key, ['status' => 'error'], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS);
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body);

            if (!isset($decoded->tag_name)) {
                error_log(sprintf(
                    'Plugin Update Checker: Invalid response format for %s',
                    $this->get_plugin_data()['Name']
                ));
                
                // Cache error response
                set_transient($cache_key, ['status' => 'error'], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS);
                return false;
            }
            
            // Look for the attached asset instead of using zipball_url directly
            if (isset($decoded->assets) && !empty($decoded->assets)) {
                $decoded->zipball_url = $decoded->assets[0]->browser_download_url ?? $decoded->zipball_url;
            }

            // Cache the response for 4 hours
            set_transient($cache_key, $decoded, self::CACHE_DURATION * HOUR_IN_SECONDS);
            $this->github_response = $decoded;
            
            return $decoded;
        }

        /**
         * Check if an update is available
         *
         * @param object $transient WordPress update transient
         * @return object Modified update transient
         */
        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }

            $repository_info = $this->get_repository_info();
            if ($repository_info === false) {
                return $transient;
            }

            $current_version = $transient->checked[$this->basename];
            $latest_version = ltrim($repository_info->tag_name, 'v'); // Remove 'v' prefix if present

            if (version_compare($latest_version, $current_version, 'gt')) {
                $plugin = array(
                    'url' => $this->get_plugin_data()['PluginURI'],
                    'slug' => current(explode('/', $this->basename)),
                    'package' => $repository_info->zipball_url,
                    'new_version' => $latest_version,
                    'tested' => get_bloginfo('version'),
                    'icons' => [
                        "1x" => self::ICON_SMALL,
                        "2x" => self::ICON_LARGE,
                    ],
                );

                $transient->response[$this->basename] = (object) $plugin;
            }

            return $transient;
        }

        /**
         * Populate plugin information in the update popup
         */
        public function plugin_popup($result, $action, $args) {
            if ($action !== 'plugin_information') {
                return $result;
            }

            if (!empty($args->slug) && $args->slug === current(explode('/', $this->basename))) {
                $repository_info = $this->get_repository_info();
                
                if ($repository_info === false) {
                    return $result;
                }
                
                $plugin_data = $this->get_plugin_data();

                $plugin_info = array(
                    'name'              => $plugin_data['Name'],
                    'slug'              => $this->basename,
                    'version'           => ltrim($repository_info->tag_name, 'v'),
                    'author'            => $plugin_data['Author'],
                    'author_profile'    => $plugin_data['AuthorURI'],
                    'last_updated'      => $repository_info->published_at,
                    'homepage'          => $plugin_data['PluginURI'],
                    'short_description' => $plugin_data['Description'],
                    'sections'          => array(
                        'Description'   => $plugin_data['Description'],
                        'Updates'       => $repository_info->body,
                    ),
                    'download_link'     => $repository_info->zipball_url,
                    'icons' => [
                        "1x" => self::ICON_SMALL,
                        "2x" => self::ICON_LARGE,
                    ],
                );

                return (object) $plugin_info;
            }

            return $result;
        }

        /**
         * Handle the plugin installation after download
         */
        public function after_install($response, $hook_extra, $result) {
            global $wp_filesystem;

            $install_directory = plugin_dir_path($this->file);
            $wp_filesystem->move($result['destination'], $install_directory);
            $result['destination'] = $install_directory;

            if (is_plugin_active($this->basename)) {
                activate_plugin($this->basename);
            }

            return $result;
        }
    }
}
