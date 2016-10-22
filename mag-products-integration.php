<?php
/*
Plugin Name: Mag Products Integration for WordPress
Plugin URI: https://wordpress.org/plugins/mag-products-integration/
Description: This plugin let you display products of your Magento store, directly in your WordPress. It connects to Magento through the REST API.
Version: 1.3.0
Requires at least: 4.0
Author: Francis Santerre
Author URI: http://santerref.com/
Domain Path: /languages
Text Domain: mag-products-integration
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require 'autoload.php';

register_activation_hook( __FILE__, array( '\mag_products_integration\plugin_instance', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\mag_products_integration\plugin_instance', 'deactivate' ) );

/**
 * Create global instance of the plugin. Allows developer to remove/add plugin actions/filters.
 *
 * @since 1.3.0
 *
 * @return \mag_products_integration\Plugin_Singleton
 */
function mag_products_integration() {
	static $plugin_instance;

	if ( ! isset( $plugin_instance ) ) {
		$plugin_instance = \mag_products_integration\Plugin_Singleton::get_instance();
	}

	return $plugin_instance;
}

mag_products_integration();

if ( is_admin() ) {
	/**
	 * Create global instance of the plugin admin. Allows developer to remove/add plugin actions/filters.
	 *
	 * @since 1.3.0
	 *
	 * @return \mag_products_integration\Admin
	 */
	function mag_products_integration_admin() {
		static $admin_instance;

		if ( ! isset( $admin_instance ) ) {
			$admin_instance = mag_products_integration()->get_admin();
		}

		return $admin_instance;
	}

	mag_products_integration_admin();
}
