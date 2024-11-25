<?php
/**
 * GitHub Plugin Updater
 * 
 * Enables automatic updates from GitHub releases for WordPress plugins.
 * Requires plugin header to include "GitHub Plugin URI".
 */

if (!class_exists('GitHubPluginUpdater')) {
	class GitHubPluginUpdater {
		private $file;
		private $plugin;
		private $basename;
		private $active;
		private $github_response;
		private $github_url;
		private $authorize_token;

		public function __construct($file, $github_url, $authorize_token = null) {
			$this->file = $file;
			$this->github_url = $github_url;
			$this->authorize_token = $authorize_token;
			$this->basename = plugin_basename($file);

			// Load plugin data
			$this->plugin = get_file_data($this->file, array(
				'Name' => 'Plugin Name',
				'PluginURI' => 'Plugin URI',
				'Description' => 'Description',
				'Author' => 'Author',
				'AuthorURI' => 'Author URI',
				'Version' => 'Version',
			));
			
			add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
			add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
			add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
		}

		private function get_repository_info() {
			if (is_null($this->github_response)) {
				$request_uri = sprintf('https://api.github.com/repos/%s/releases/latest', $this->github_url);
				
				$args = array(
					'headers' => array(
						'User-Agent' => 'WordPress-GitHub-Updater' // GitHub API requires a User-Agent
					),
				);

				if ($this->authorize_token) {
					$args['headers']['Authorization'] = "Bearer {$this->authorize_token}";
				}

				$response = wp_remote_get($request_uri, $args);

				if (is_wp_error($response)) {
					error_log('GitHub Plugin Updater: Failed to fetch repository info: ' . $response->get_error_message());
					return false;
				}

				$body = wp_remote_retrieve_body($response);
				$decoded = json_decode($body);

				if (isset($decoded->tag_name)) {
					$this->github_response = $decoded;
				} else {
					error_log('GitHub Plugin Updater: Invalid response from GitHub API');
				}
			}
		}

		public function modify_transient($transient) {
			if (property_exists($transient, 'checked')) {
				if ($checked = $transient->checked) {
					$this->get_repository_info();

					if (isset($this->github_response->tag_name) && version_compare($this->github_response->tag_name, $checked[$this->basename], 'gt')) {
						$plugin = array(
							'url' => $this->plugin['PluginURI'],
							'slug' => current(explode('/', $this->basename)),
							'package' => $this->github_response->zipball_url,
							'new_version' => $this->github_response->tag_name
						);

						$transient->response[$this->basename] = (object) $plugin;
					}
				}
			}

			return $transient;
		}

		public function plugin_popup($result, $action, $args) {
			if ($action !== 'plugin_information') {
				return $result;
			}

			if (!empty($args->slug) && $args->slug === current(explode('/', $this->basename))) {
				$this->get_repository_info();

				if (isset($this->github_response->tag_name)) {
					$plugin = array(
						'name'              => $this->plugin['Name'],
						'slug'              => $this->basename,
						'version'           => $this->github_response->tag_name,
						'author'            => $this->plugin['Author'],
						'author_profile'    => $this->plugin['AuthorURI'],
						'last_updated'      => $this->github_response->published_at,
						'homepage'          => $this->plugin['PluginURI'],
						'short_description' => $this->plugin['Description'],
						'sections'          => array(
							'Description'   => $this->plugin['Description'],
							'Updates'       => $this->github_response->body,
						),
						'download_link'     => $this->github_response->zipball_url
					);

					return (object) $plugin;
				}
			}
			return $result;
		}

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