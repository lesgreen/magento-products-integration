<?php

namespace mag_products_integration;

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
