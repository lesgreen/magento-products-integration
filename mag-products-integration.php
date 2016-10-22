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

namespace mag_products_integration;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require 'autoload.php';

/**
 * Class Plugin
 *
 * @since 1.0.0
 *
 * @package mag_products_integration
 */
abstract class Plugin {

	/** @var \mag_products_integration\Admin $admin Instance of Mag_Admin */
	protected $admin;

	/** @var \mag_products_integration\Shortcode $shortcode Instance of Mag_Shortcode */
	protected $shortcode;

	/** @var \mag_products_integration\Cache $cache Instance of Mag_Cache */
	protected $cache;

	/** @var string Plugin's version */
	public $version = '1.3.0';

	/** @var string Plugin's text domain */
	public $textdomain = 'mag-products-integration';

	/**
	 * Create the instances of $shortcode and $cache.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->shortcode = new Shortcode();
		$this->cache     = new Cache();

		$this->init();
	}

	/**
	 * Initialization of the plugin. Load plugin text domain and execute initialization functions.
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		load_plugin_textdomain( $this->textdomain, false, basename( dirname( __FILE__ ) ) . '/languages' );
		add_shortcode( 'magento', array( $this->shortcode, 'do_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'euqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
	}

	/**
	 * Enqueue plugin's default CSS styles for the products list
	 *
	 * @since 1.0.0
	 */
	public function euqueue_scripts() {
		wp_enqueue_style( 'magento-style', plugins_url( 'assets/css/style.min.css', __FILE__ ), array(), $this->version );
	}

	/**
	 * Add Settings link on the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings = array(
			'<a href="' . admin_url( 'admin.php?page=mag-products-integration/class.mag-products-integration-admin.php' ) . '">' . __( 'Settings' ) . '</a>',
		);

		return array_merge( $settings, $links );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return \mag_products_integration\Admin instance
	 */
	public function get_admin() {
		if ( ! $this->admin instanceof Admin ) {
			$this->admin = new Admin();
		}

		return $this->admin;
	}

	/**
	 * @since 1.2.0
	 *
	 * @return \mag_products_integration\Cache instance
	 */
	public function get_cache() {
		return $this->cache;
	}

	/**
	 * The plugin is ready when a valid API endpoint is available.
	 *
	 * @since 1.0.0
	 *
	 * @return string Valid Magento REST API endpoint or empty string.
	 */
	public function is_ready() {
		$is_ready = get_option( 'mag_products_integration_rest_api_url' );

		return $is_ready;
	}

	/**
	 * Determine if the plugin if fully installed or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the plugin is configured and the Magento module installed, false otherwise.
	 */
	public function is_module_installed() {
		$url_validated      = get_option( 'mag_products_integration_rest_api_url_validated' );
		$default_store_code = get_option( 'mag_products_integration_default_store_code' );
		$module_installed   = get_option( 'mag_products_integration_magento_module_installed' );
		$stores_code        = get_option( 'mag_products_integration_stores_code' );

		return ( $url_validated && ! empty( $default_store_code ) && ! empty( $module_installed ) && ! empty( $stores_code ) );
	}

	/**
	 * @return \mag_products_integration\Magento_Interface
	 */
	public function get_magento() {
		return new Magento();
	}

	/**
	 * Function executed on plugin activation.
	 * Update plugin's options to set default values.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		update_option( 'mag_products_integration_rest_api_url_validated', get_option( 'mag_products_integration_rest_api_url_validated', 0 ) );
		update_option( 'mag_products_integration_stores_code', get_option( 'mag_products_integration_stores_code', '' ) );
		update_option( 'mag_products_integration_default_store_code', get_option( 'mag_products_integration_default_store_code', '' ) );
		update_option( 'mag_products_integration_magento_module_installed', get_option( 'mag_products_integration_magento_module_installed', 0 ) );
	}

	/**
	 * Function executed on plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

	}
}

/**
 * Class Plugin_Singleton
 *
 * @since 1.3.0
 *
 * @package mag_products_integration
 */
final class Plugin_Singleton extends Plugin {

	/** @var Plugin $instance Singleton of Plugin */
	private static $instance;

	/**
	 * @since 1.0.0
	 *
	 * @return Plugin_Singleton singleton
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) || ! self::$instance instanceof self ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}

register_activation_hook( __FILE__, array( '\mag_products_integration\plugin_instance', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\mag_products_integration\plugin_instance', 'deactivate' ) );

/**
 * Create global instance of the plugin. Allows developer to remove/add plugin actions/filters.
 *
 * @since 1.3.0
 *
 * @return Plugin_Singleton
 */
function plugin_instance() {
	static $plugin_instance;

	if ( ! isset( $plugin_instance ) ) {
		$plugin_instance = Plugin_Singleton::get_instance();
	}

	return $plugin_instance;
}

plugin_instance();

if ( is_admin() ) {
	/**
	 * Create global instance of the plugin admin. Allows developer to remove/add plugin actions/filters.
	 *
	 * @since 1.3.0
	 *
	 * @return \mag_products_integration\Admin
	 */
	function admin_instance() {
		static $admin_instance;

		if ( ! isset( $admin_instance ) ) {
			$admin_instance = plugin_instance()->get_admin();
		}

		return $admin_instance;
	}

	//admin_instance();
}
