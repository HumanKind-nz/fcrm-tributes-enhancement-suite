<?php
/**
 * Plugin Update Checker
 *
 * Enables automatic updates from GitHub releases for WordPress plugins.
 * Features:
 * - GitHub API integration with rate limit protection
 * - Response caching to minimize API calls
 * - Detailed error logging
 * - Optional authentication token support
 */

if (!class_exists('PluginUpdateChecker')) {
    class PluginUpdateChecker {
        private $file;           // Full path to plugin file
        private $plugin;         // Plugin metadata
        private $basename;       // Plugin basename
        private $github_response; // Cached GitHub API response
        private $github_url;     // GitHub repository URL (username/repository)
        private $authorize_token; // Optional GitHub API token
        private $cache_key;      // Transient cache key for API responses

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
            $this->cache_key = 'github_update_' . md5($github_url);

            // Load plugin metadata from the main plugin file
            $this->plugin = get_file_data($this->file, array(
                'Name'        => 'Plugin Name',
                'PluginURI'   => 'Plugin URI',
                'Description' => 'Description',
                'Author'      => 'Author',
                'AuthorURI'   => 'Author URI',
                'Version'     => 'Version',
            ));

            // Hook into WordPress update system
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
            add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
            add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
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
            $cached_response = get_transient($this->cache_key);
            if (false !== $cached_response) {
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
                    $this->plugin['Name'],
                    $response->get_error_message()
                ));
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                error_log(sprintf(
                    'Plugin Update Checker: GitHub API returned code %d for %s',
                    $response_code,
                    $this->plugin['Name']
                ));
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body);

            if (!isset($decoded->tag_name)) {
                error_log(sprintf(
                    'Plugin Update Checker: Invalid response format for %s',
                    $this->plugin['Name']
                ));
                return false;
            }

            // Cache the response for 6 hours
            set_transient($this->cache_key, $decoded, 6 * HOUR_IN_SECONDS);
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
                    'url' => $this->plugin['PluginURI'],
                    'slug' => current(explode('/', $this->basename)),
                    'package' => $repository_info->zipball_url,
                    'new_version' => $latest_version,
                    'tested' => get_bloginfo('version'),
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

                $plugin_info = array(
                    'name'              => $this->plugin['Name'],
                    'slug'              => $this->basename,
                    'version'           => ltrim($repository_info->tag_name, 'v'),
                    'author'            => $this->plugin['Author'],
                    'author_profile'    => $this->plugin['AuthorURI'],
                    'last_updated'      => $repository_info->published_at,
                    'homepage'          => $this->plugin['PluginURI'],
                    'short_description' => $this->plugin['Description'],
                    'sections'          => array(
                        'Description'   => $this->plugin['Description'],
                        'Updates'       => $repository_info->body,
                    ),
                    'download_link'     => $repository_info->zipball_url
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
