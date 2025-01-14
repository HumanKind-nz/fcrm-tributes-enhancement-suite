<?php
/**
 * Plugin Name: FireHawkCRM Tributes - Enhancement Suite
 * Plugin URI:  https://github.com/weavedigitalstudio/fcrm-tributes-enhancement-suite/
 * Description: An enhancement suite for the FireHawkCRM Tributes plugin, including performance optimisations, custom styling from admin, and loading animations.
 * Version:     1.1.2
 * Author:      Weave Digital Studio, Gareth Bissland
 * Author URI:  https://weave.co.nz/
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * GitHub Plugin URI: weavedigitalstudio/fcrm-tributes-enhancement-suite/
 * Text Domain: fcrm-enhancement-suite
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */
 
 namespace FCRM\EnhancementSuite;
 
// Load the updater class
 require_once plugin_dir_path(__FILE__) . 'includes/class-update-checker.php';
 
 // Initialize the updater
if (is_admin() && class_exists('PluginUpdateChecker')) {
    new PluginUpdateChecker(
        __FILE__,
        'weavedigitalstudio/fcrm-tributes-enhancement-suite'
    );
}
  
 // Prevent direct access
 if (!defined('ABSPATH')) {
	 exit;
 }

 // Plugin constants
 define('FCRM_ENHANCEMENT_VERSION', '1.1.2');
 define('FCRM_ENHANCEMENT_FILE', __FILE__);
 define('FCRM_ENHANCEMENT_PATH', plugin_dir_path(__FILE__));
 define('FCRM_ENHANCEMENT_URL', plugin_dir_url(__FILE__));
 define('FCRM_ENHANCEMENT_BASENAME', plugin_basename(__FILE__));
  
 /**
  * Main plugin class
  */
 class EnhancementSuite {
	 /**
	  * Plugin instance
	  *
	  * @var EnhancementSuite|null
	  */
	 private static $instance = null;
 
	 /**
	  * Active modules
	  *
	  * @var array
	  */
	 private $modules = [];
 
	 /**
	  * Settings tabs
	  *
	  * @var array
	  */
	 private $tabs = [
		  'optimisation' => 'Performance Improvements',
		  'styling' => 'Custom Tribute Styles',
		  'loader' => 'Enable Loading Animation'
	  ];
 
	 /**
	  * Get plugin instance
	  *
	  * @return EnhancementSuite
	  */
	 public static function get_instance(): EnhancementSuite {
		 if (null === self::$instance) {
			 self::$instance = new self();
		 }
		 return self::$instance;
	 }
 
	 /**
	  * Constructor
	  */
	 private function __construct() {
		 $this->check_requirements();
		 $this->load_dependencies();
		 $this->init_modules();
		 $this->setup_hooks();
	 }
 
	 /**
	  * Check if required plugins are active
	  */
	 private function check_requirements(): void {
		 if (!function_exists('is_plugin_active')) {
			 require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		 }
 
		 if (!is_plugin_active('fcrm-tributes/fcrm-tributes.php')) {
			 add_action('admin_notices', [$this, 'requirement_notice']);
			 return;
		 }
	 }
 
	 /**
	  * Load required files
	  */
	 private function load_dependencies(): void {
		 require_once FCRM_ENHANCEMENT_PATH . 'includes/class-fcrm-enhancement-base.php';
		 require_once FCRM_ENHANCEMENT_PATH . 'includes/class-fcrm-optimisation.php';
		 require_once FCRM_ENHANCEMENT_PATH . 'includes/class-fcrm-styling.php';
		 require_once FCRM_ENHANCEMENT_PATH . 'includes/class-fcrm-loader.php';
	 }
 
	 /**
	  * Initialise modules
	  */
	 private function init_modules(): void {
		 $this->modules['optimisation'] = new Optimisation();
		 $this->modules['styling'] = new Styling();
		 $this->modules['loader'] = new Loader();
	 }
 
	 /**
	  * Setup WordPress hooks
	  */
	 private function setup_hooks(): void {
		 add_action('init', [$this, 'register_module_settings']);
		 add_action('admin_init', [$this, 'handle_resets']);
		 add_action('admin_menu', [$this, 'add_admin_menu']);
		 add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		 add_action('admin_notices', [$this, 'admin_notices']);
		 add_filter('plugin_action_links_' . FCRM_ENHANCEMENT_BASENAME, [$this, 'add_plugin_links']);
	 }
 
	 /**
	  * Register settings for all modules
	  */
	 public function register_module_settings(): void {
		 if (!is_admin()) {
			 return;
		 }
		 
		 foreach ($this->modules as $module) {
			 $module->register_settings();
		 }
	 }
 
	 /**
	  * Handle module resets
	  */
	 public function handle_resets(): void {
		 if (!isset($_POST['fcrm_reset_nonce']) || 
			 !wp_verify_nonce($_POST['fcrm_reset_nonce'], 'fcrm_reset_settings')) {
			 return;
		 }
 
		 if (isset($_POST['module']) && isset($this->modules[$_POST['module']])) {
			 $module = $this->modules[$_POST['module']];
			 if (method_exists($module, 'handle_reset')) {
				 $module->handle_reset();
			 }
		 }
	 }
 
	 /**
	  * Get menu icon as base64 data URI
	  *
	  * @return string
	  */
	 private function get_menu_icon(): string {
		 // Replace this with your actual base64 encoded SVG
		 return 'data:image/svg+xml;base64,' . 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIyLjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxMDAwIDEwMDAiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDEwMDAgMTAwMDsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsOiNGRkZGRkY7fQoJLnN0MXtvcGFjaXR5OjAuODU7ZmlsbDp1cmwoI1NWR0lEXzFfKTt9Cgkuc3Qye29wYWNpdHk6MC41NTtmaWxsOiNGRkZGRkY7fQoJLnN0M3tvcGFjaXR5OjAuNjU7ZmlsbDojRkZGRkZGO30KCS5zdDR7ZmlsbDp1cmwoI1NWR0lEXzJfKTt9Cgkuc3Q1e29wYWNpdHk6MC41OTtmaWxsOiNGRkZGRkY7fQoJLnN0NntvcGFjaXR5OjAuNzY7ZmlsbDojRkZGRkZGO30KCS5zdDd7ZmlsbDp1cmwoI1NWR0lEXzNfKTt9Cgkuc3Q4e2ZpbGw6dXJsKCNTVkdJRF80Xyk7fQoJLnN0OXtmaWxsOnVybCgjU1ZHSURfNV8pO30KCS5zdDEwe2ZpbGw6dXJsKCNTVkdJRF82Xyk7fQoJLnN0MTF7ZmlsbDp1cmwoI1NWR0lEXzdfKTt9Cgkuc3QxMntmaWxsOnVybCgjU1ZHSURfOF8pO30KCS5zdDEze2ZpbGw6dXJsKCNTVkdJRF85Xyk7fQoJLnN0MTR7ZmlsbDp1cmwoI1NWR0lEXzEwXyk7fQoJLnN0MTV7ZmlsbDp1cmwoI1NWR0lEXzExXyk7fQoJLnN0MTZ7ZmlsbDp1cmwoI1NWR0lEXzEyXyk7fQoJLnN0MTd7ZmlsbDp1cmwoI1NWR0lEXzEzXyk7fQoJLnN0MTh7ZmlsbDp1cmwoI1NWR0lEXzE0Xyk7fQoJLnN0MTl7ZmlsbDp1cmwoI1NWR0lEXzE1Xyk7fQoJLnN0MjB7b3BhY2l0eTowLjM1O2ZpbGw6I0ZGRkZGRjt9Cgkuc3QyMXtvcGFjaXR5OjAuODU7ZmlsbDp1cmwoI1NWR0lEXzE2Xyk7fQoJLnN0MjJ7b3BhY2l0eTowLjg1O2ZpbGw6dXJsKCNTVkdJRF8xN18pO30KCS5zdDIze29wYWNpdHk6MC41MTtmaWxsOiNGRkZGRkY7fQoJLnN0MjR7ZmlsbDp1cmwoI1NWR0lEXzE4Xyk7fQoJLnN0MjV7ZmlsbDp1cmwoI1NWR0lEXzE5Xyk7fQoJLnN0MjZ7ZmlsbDp1cmwoI1NWR0lEXzIwXyk7fQoJLnN0Mjd7ZmlsbDp1cmwoI1NWR0lEXzIxXyk7fQoJLnN0Mjh7ZmlsbDp1cmwoI1NWR0lEXzIyXyk7fQoJLnN0Mjl7ZmlsbDp1cmwoI1NWR0lEXzIzXyk7fQoJLnN0MzB7ZmlsbDp1cmwoI1NWR0lEXzI0Xyk7fQoJLnN0MzF7ZmlsbDp1cmwoI1NWR0lEXzI1Xyk7fQoJLnN0MzJ7ZmlsbDp1cmwoI1NWR0lEXzI2Xyk7fQoJLnN0MzN7ZmlsbDp1cmwoI1NWR0lEXzI3Xyk7fQoJLnN0MzR7ZmlsbDp1cmwoI1NWR0lEXzI4Xyk7fQoJLnN0MzV7ZmlsbDp1cmwoI1NWR0lEXzI5Xyk7fQoJLnN0MzZ7ZmlsbDp1cmwoI1NWR0lEXzMwXyk7fQoJLnN0Mzd7ZmlsbDp1cmwoI1NWR0lEXzMxXyk7fQoJLnN0Mzh7b3BhY2l0eTowLjg1O2ZpbGw6dXJsKCNTVkdJRF8zMl8pO30KPC9zdHlsZT4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTQ2Ni4yLDU2Mi43Yy00NC44LTguNC03Ny4xLTQzLjEtMTA5LjktNzJjLTgzLjUtNzMuNy0xNTcuNS0xNTIuOS0yMjYuOC0yNDBsLTEuMiw5MC45bC0yNy44LTIyLjRsMjIsNzcuNgoJbC0zNS4zLTE3LjhsMzksNjkuNGwtNDIuOC0xMi45bDU2LjgsNjIuNGwtNDguMi00LjlsNjkuMiw1MC42bC01NS44LDMuOWw3NSwzMy41TDEzMSw1OTIuM2w4MC41LDIwLjhsLTQyLjcsMTcuNmw4MC42LDkuMQoJbC0yNy4xLDE2LjFsNzQuMS0xLjhsLTI3LjUsMjEuOWw3MC4xLTE0LjFMMzA1LDY5NS4ybDc2LjItMjUuOUwzNTUuNiw3MDVsNTYuMS0yOC4zbC0xNSwzNC4xbDYxLjktMzguNWw4LjMsMzguMWwxNi44LDE4LjMKCWwtNDIuNSw0My43bDQuNSwzNS44bDMzLTE0bDIxLjYsMjguOWwyMi4zLTI4LjNsMzIuNiwxNC45bDUuNy0zNS42bC00MS41LTQ0LjhsMTguNC0xOC45bDkuNS0zNy42bDYxLjQsMzguNWwtMTQuNC0zMy45bDU2LjEsMjguNwoJTDYyNC4xLDY3MGw3Ni4zLDI2LjFsLTM0LTMzLjVsNzAuMywxNGwtMjcuNi0yMmw3NC40LDJsLTI3LjUtMTYuMWw4MC45LTguOWwtNDMuMi0xNy43bDgwLjgtMjAuOGwtNTAtMTEuMWw3NS41LTMzLjdsLTU1LjktMy43CglsNjkuMi01MC44bC00OC44LDUuM2w1Ny45LTYyLjhsLTQzLjMsMTIuOGwzOS4xLTY5LjRsLTM1LjYsMThsMjIuNi03OGwtMjgsMjIuMmMwLDAtMS4xLTkwLjgtMS4xLTkwLjljMCwzLjctMTEsMTMuOC0xMy4yLDE2LjYKCWMtOC45LDExLjMtMTguMiwyMi4yLTI3LjEsMzMuNWMtNjgsODYuNi0xNTIuMiwxNzMuMS0yNDQuNSwyMzRjLTEwLjcsNy00NiwzNC01NS4yLDI0LjNjLTYuNC02LjctOC41LTI2LjgtMTEuMy0zNS45CgljMCwwLDEwLjctMy4zLDIxLDIuN2MwLDAsMC02LTUuOS0xMi4yYzAsMC0xNi45LTQtNjAtMS45Yy0yLjcsMC4xLTExLjgsNDUuNS0xMy4xLDUwLjhDNDY2LjgsNTYyLjgsNDY2LjUsNTYyLjcsNDY2LjIsNTYyLjd6Ii8+Cjwvc3ZnPgo=';
	 }
 
	 /**
	  * Add admin menu
	  */
	 public function add_admin_menu(): void {
		 add_menu_page(
			 __('FH Tributes Enhancements', 'fcrm-enhancement-suite'),
			 __('FH Tributes Enhancements', 'fcrm-enhancement-suite'),
			 'manage_options',
			 'fcrm-enhancements',
			 [$this, 'render_admin_page'],
			 $this->get_menu_icon(),
			 100 // Position at bottom
		 );
	 }
 
	 /**
	  * Enqueue admin assets
	  */
	 public function enqueue_admin_assets($hook): void {
		 if ('toplevel_page_fcrm-enhancements' !== $hook) {
			 return;
		 }
	 
		 // Enqueue alpha picker styles
		 wp_enqueue_style(
			 'fcrm-enhancement-admin',
			 FCRM_ENHANCEMENT_URL . 'assets/css/admin/alpha-picker.css',
			 [],
			 FCRM_ENHANCEMENT_VERSION
		 );
	 
		 // Enqueue custom admin styles
		 wp_enqueue_style(
			 'fcrm-enhancement-admin-styles',
			 FCRM_ENHANCEMENT_URL . 'assets/css/admin/admin-styles.css',
			 ['fcrm-enhancement-admin'],  // Make it load after alpha-picker styles
			 FCRM_ENHANCEMENT_VERSION
		 );
	 
		 foreach ($this->modules as $module) {
			 $module->enqueue_admin_assets($hook);
		 }
	 }
 
	 /**
	  * Add plugin action links
	  */
	 public function add_plugin_links($links): array {
		 $plugin_links = [
			 sprintf('<a href="%s">%s</a>', 
				 admin_url('admin.php?page=fcrm-enhancements'), 
				 __('Settings', 'fcrm-enhancement-suite')
			 )
		 ];
		 return array_merge($plugin_links, $links);
	 }
 
	 /**
	  * Display requirement notice
	  */
	 public function requirement_notice(): void {
		 ?>
		 <div class="notice notice-error">
			 <p><?php _e('FirehawkCRM Tributes Enhancement Suite requires the FireHawkCRM Tributes plugin to be installed and activated.', 'fcrm-enhancement-suite'); ?></p>
		 </div>
		 <?php
	 }
 
	 /**
	  * Display admin notices
	  */
	 public function admin_notices(): void {
		 if (!isset($_GET['page']) || $_GET['page'] !== 'fcrm-enhancements') {
			 return;
		 }
	 
		 // Check for settings-updated
		 if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
			 ?>
			 <div class="notice notice-success is-dismissible">
				 <p><?php _e('Settings saved successfully.', 'fcrm-enhancement-suite'); ?></p>
			 </div>
			 <?php
		 }
	 
		 // Check for reset
		 if (isset($_GET['reset']) && $_GET['reset'] === 'true' && isset($_GET['tab'])) {
			 ?>
			 <div class="notice notice-success is-dismissible">
				 <p><?php _e('Settings reset successfully.', 'fcrm-enhancement-suite'); ?></p>
			 </div>
			 <?php
		 }
	 }
 
	 /**
	  * Render admin page
	  */
	 public function render_admin_page(): void {
		 $current_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->tabs)
			 ? sanitize_key($_GET['tab'])
			 : 'optimisation';
		 ?>
		 <div class="wrap">
			 <h1><?php _e('Firehawk Tributes Enhancement Suite', 'fcrm-enhancement-suite'); ?></h1>
			 <?php
			 echo '<p class="plugin-description">';
			 echo esc_html__('Originally developed for internal use, this third-party plugin helps optimise and enhance your website’s functionality with the FH Tributes plugin. It removes unnecessary scripts and styles to improve site performance and includes styling UI options to customise the plugin’s tribute output to better match your site. This plugin has no direct affiliation with FireHawk Funerals.', 'fcrm-enhancement-suite');
			 echo '</p>';
			 ?>
			 <nav class="nav-tab-wrapper">
				 <?php 
				 foreach ($this->tabs as $tab_key => $tab_label): 
					 $tab_url = add_query_arg([
						 'page' => 'fcrm-enhancements',
						 'tab' => $tab_key
					 ], admin_url('admin.php'));
				 ?>
					 <a href="<?php echo esc_url($tab_url); ?>" 
						 class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						 <?php echo esc_html($tab_label); ?>
					 </a>
				 <?php endforeach; ?>
			 </nav>
	 
			 <form method="post" action="options.php">
				 <?php
				 // Only show the global save button on tabs where it's needed
				 if ($current_tab === 'optimisation') {
					 settings_fields('fcrm_enhancement_' . $current_tab);
					 if (isset($this->modules[$current_tab])) {
						 echo '<div class="tab-content">';
						 $this->modules[$current_tab]->render_settings();
						 echo '</div>';
					 }
					 submit_button(__('Save Changes', 'fcrm-enhancement-suite'));
				 } elseif (isset($this->modules[$current_tab])) {
					 // Render the module's settings for tabs with their own save button
					 $this->modules[$current_tab]->render_settings();
				 }
				 ?>
			 </form>
	 
			 <?php
			 // Add reset button for specific tabs
			 if (method_exists($this->modules[$current_tab], 'handle_reset')): ?>
				 <form method="post" style="margin-top: 20px;">
					 <?php wp_nonce_field('fcrm_reset_settings', 'fcrm_reset_nonce'); ?>
					 <input type="hidden" name="module" value="<?php echo esc_attr($current_tab); ?>">
					 <?php submit_button(__('Reset Settings', 'fcrm-enhancement-suite'), 'delete', 'reset', false); ?>
				 </form>
			 <?php endif; ?>
	 
			 <div class="plugin-support">
				 <p>
					 <?php
					 printf(
						 /* Translators: %1$s is a mailto link, %2$s is a GitHub link. */
						 __('Need help or have a request? Contact our support team at %1$s or log an issue on %2$s.', 'fcrm-enhancement-suite'),
						 '<a href="mailto:support@weave.co.nz?subject=FH Tribute Enhancement Plugin Support">support@weave.co.nz</a>',
						 '<a href="https://github.com/weavedigitalstudio/fcrm-tributes-enhancement-suite/issues" target="_blank">GitHub</a>'
					 );
					 ?>
				 </p>
			 </div>
		 </div>
		 <?php
	 }
 }
 
 // Initialise plugin
 add_action('plugins_loaded', function() {
	 EnhancementSuite::get_instance();
 });
 
 // Register activation/deactivation hooks
 register_activation_hook(FCRM_ENHANCEMENT_FILE, function() {
	 // Activation tasks if needed
 });
 
 register_deactivation_hook(FCRM_ENHANCEMENT_FILE, function() {
	 // Deactivation tasks if needed
 });
