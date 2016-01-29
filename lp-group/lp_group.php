<?php
/*
/**
 * Plugin Name: NuCLO
 * Plugin URI: https://allnuclo.com/
 * Description: This plugin adds user groups to WordPress.
 * Version: 1.0.0
 * Author: kapil Sharma
 * Author URI: https://allnuclo.com/
 * License: GPL2
 */


if(!class_exists('lp_group'))
{
	class lp_group
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			// Register custom post types
			require_once(sprintf("%s/post-types/lp-group-pt.php", dirname(__FILE__)));
			$Post_Type_Template = new Groups();

			$plugin = plugin_basename(__FILE__);
		} // END public function __construct

		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
			// Do nothing
		} // END public static function activate
				/**
		 * Deactivate the plugin
		 */
		public static function deactivate()
		{
			// Do nothing
		} // END public static function deactivate

		// Add the settings link to the plugins page

	} // END class lp_group
} // END if(!class_exists('lp_group'))

if(class_exists('lp_group'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('lp_group', 'activate'));
	register_deactivation_hook(__FILE__, array('lp_group', 'deactivate'));

	// instantiate the plugin class
	$lp_group = new lp_group();

}
