<?php
/**
 * Plugin Name:       FireHawkCRM Tribute plugin - Custom Overrides
 * Plugin URI:        https://github.com/weavedigitalstudio/fcrm-tributes-custom-overrides
 * Description:       Overrides specific dependencies in the FCRM Tributes plugin.
 * Version:           0.0.2
 * Author:            Gareth Bissland, Weave Digital Studio
 * License:           
 * GitHub Plugin URI: https://github.com/weavedigitalstudio/fcrm-tributes-custom-overrides
 * Primary Branch:    main
 * Requires at least: 6.0
 * Requires PHP:      7.2
 */

/*
Changelog:
Version 0.0.2
- Initial release for testing
*/
 
 // Prevent direct access to the file
 if (!defined('ABSPATH')) {
	 exit;
 }
 
 // Hook into the Fcrm_Tributes class to override dependencies
 add_action('plugins_loaded', 'custom_fcrm_override_dependencies', 20);
 
 function custom_fcrm_override_dependencies() {
	 // Check if Fcrm_Tributes is active and loaded
	 if (class_exists('Fcrm_Tributes')) {
		 // Remove the flower delivery dependency
		 remove_action('plugins_loaded', array('Fcrm_Tributes', 'load_dependencies'));
		 // Add custom loader to skip flower delivery class
		 add_action('plugins_loaded', 'custom_load_dependencies', 20);
	 }
 }
 
 function custom_load_dependencies() {
	 $plugin = new Fcrm_Tributes();
 
	 // Only load the dependencies you need, excluding the flower delivery class
	 require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fcrm-tributes-loader.php';
	 require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fcrm-tributes-i18n.php';
	 require_once plugin_dir_path(dirname(__FILE__)) . 'includes/fcrm-api.php';
	 require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-fcrm-tributes-admin.php';
	 require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-single-tribute-type.php';
	 require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-fcrm-tributes-public.php';
	 // Do NOT load flower delivery class
	 // require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-fcrm-tributes-flower-delivery.php';
 
	 $plugin->get_loader()->run();
 }
