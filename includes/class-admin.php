<?php

namespace mag_products_integration;

/**
 * Class Admin
 *
 * @since 1.0.0
 *
 * @package mag_products_integration
 */
class Admin {

	/** @var string $installation_path Magento plugin install path. */
	protected $installation_path = '/wordpress/plugin/verify';

	/** @var string $stores_path Magento plugin stores path. */
	protected $stores_path = '/wordpress/plugin/stores';

	public function __construct() {
		$this->init();
	}

	/**
	 * This function is executed in the admin area.
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_ajax_script' ) );
		add_action( 'wp_ajax_verify_magento_module_installation', array(
			$this,
			'verify_magento_module_installation'
		) );
		add_action( 'wp_ajax_get_available_stores', array( $this, 'get_available_stores' ) );
		add_action( 'wp_ajax_dismiss_module_notice', array( $this, 'dismiss_module_notice' ) );
		add_action( 'wp_ajax_flush_cache', array( $this, 'flush_cache' ) );
		$this->verify_settings();
	}

	/**
	 * Dismiss missing Magento module notice shown on every page.
	 *
	 * @since 1.2.0
	 */
	public function dismiss_module_notice() {
		update_option( 'mag_products_integration_dismiss_module_notice', true );
	}

	/**
	 * Flush cache storage (transient)
	 *
	 * @since 1.2.2
	 */
	public function flush_cache() {
		plugin_instance()->get_cache()->force_update_cache();

		wp_send_json( array(
			'message' => __( 'The cache storage has been flushed.', plugin_instance()->textdomain )
		) );
		wp_die();
	}

	/**
	 * AJAX function executed to get all Magento's store codes.
	 *
	 * Output response in JSON.
	 *
	 * @since 1.0.0
	 */
	public function get_available_stores() {
		$rest_api_url       = parse_url( get_option( 'mag_products_integration_rest_api_url' ) );
		$magento_stores_url = $rest_api_url['scheme'] . '://' . $rest_api_url['host'] . preg_replace( '/\/api\/rest\/?/', '', $rest_api_url['path'] ) . $this->stores_path;
		$response           = wp_remote_get( $magento_stores_url );
		$json_response      = json_decode( $response['body'] );
		update_option( 'mag_products_integration_stores_code', serialize( $json_response->stores ) );
		update_option( 'mag_products_integration_default_store_code', $json_response->default_store );

		ob_start();

		$this->page();

		$html = ob_get_contents();
		ob_end_clean();

		$html = preg_replace( '#name="_wp_http_referer" value="([^"]+)"#i', 'name="_wp_http_referer" value="' . esc_html( $_POST['referer'] ) . '"', $html );

		$json_data = array( 'html' => $html );
		wp_send_json( $json_data );
		wp_die();
	}

	/**
	 * AJAX function executed to verify if the Magento module is installed.
	 *
	 * Output response in JSON.
	 *
	 * @since 1.0.0
	 */
	public function verify_magento_module_installation() {
		update_option( 'mag_products_integration_dismiss_module_notice', false );
		$rest_api_url       = parse_url( get_option( 'mag_products_integration_rest_api_url' ) );
		$random_code        = wp_generate_password( 16, false, false );
		$magento_module_url = $rest_api_url['scheme'] . '://' . $rest_api_url['host'] . preg_replace( '/\/api\/rest\/?/', '', $rest_api_url['path'] ) . $this->installation_path . '/code/' . $random_code;
		$response           = wp_remote_get( $magento_module_url );
		$json_data          = array();
		if ( ! $response instanceof \WP_Error && is_array( $response ) && $response['response']['code'] == 200 && ( $json_response = json_decode( $response['body'] ) ) ) {
			if ( $json_response->code == $random_code ) {
				update_option( 'mag_products_integration_magento_module_installed', 1 );
				$json_data = array(
					'installed' => 1,
					'message'   => __( 'Magento module installation successfully verified.', plugin_instance()->textdomain )
				);
			}
		} else {
			$json_data = array(
				'installed' => 0,
				'message'   => __( 'Unable to verify the Magento module installation. Make sure to <strong>Flush Magento Cache</strong>!', plugin_instance()->textdomain )
			);
		}
		wp_send_json( $json_data );
		wp_die();
	}

	/**
	 * Enqueue AJAX JavaScript script for the plugin admin page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Hook executed which allow us to target a specific admin page.
	 */
	public function load_ajax_script( $hook ) {
		wp_enqueue_script( 'ajax-notice', plugins_url( '/assets/js/notice.min.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		if ( preg_match( '/^toplevel_page_mag-products-integration/i', $hook ) ) {
			wp_enqueue_script( 'ajax-script', plugins_url( '/assets/js/script.min.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		}
		wp_localize_script( 'ajax-notice', 'ajax_object', array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
		) );
		wp_localize_script( 'ajax-script', 'ajax_object', array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
		) );
	}

	/**
	 * Register settings for the plugin admin page.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting( 'mag_products_integration', 'mag_products_integration_rest_api_url', array(
			$this,
			'validate_rest_api_url'
		) );
		register_setting( 'mag_products_integration', 'mag_products_integration_jquery_script' );
		register_setting( 'mag_products_integration', 'mag_products_integration_cache_enabled', array(
			$this,
			'validate_cache_enabled'
		) );
		register_setting( 'mag_products_integration', 'mag_products_integration_cache_lifetime', array(
			$this,
			'validate_cache_lifetime'
		) );
	}

	public function validate_cache_enabled( $cache_enabled ) {
		if ( empty( $cache_enabled ) ) {
			plugin_instance()->get_cache()->force_update_cache();
		}

		return $cache_enabled;
	}

	/**
	 * Make sure that the lifetime is not altered.
	 *
	 * If the selected lifetime is different from the current, update to expire option value.
	 *
	 * @since 1.2.0
	 *
	 * @return string Validated lifetime
	 */
	public function validate_cache_lifetime( $mag_products_integration_cache_lifetime ) {
		$valid_values = array(
			HOUR_IN_SECONDS,
			6 * HOUR_IN_SECONDS,
			12 * HOUR_IN_SECONDS,
			DAY_IN_SECONDS,
			3 * DAY_IN_SECONDS,
			WEEK_IN_SECONDS,
			YEAR_IN_SECONDS
		);

		$current_lifetime = plugin_instance()->get_cache()->get_lifetime();
		if ( $mag_products_integration_cache_lifetime != $current_lifetime ) {
			plugin_instance()->get_cache()->update_expiration( time() + $mag_products_integration_cache_lifetime );
		}

		if ( ! in_array( $mag_products_integration_cache_lifetime, $valid_values ) ) {
			$mag_products_integration_cache_lifetime = Cache::DEFAULT_CACHE_LIFETIME;
		}

		return $mag_products_integration_cache_lifetime;
	}

	/**
	 * Validate the syntax of the Magento REST API URL and verify if it's a valid API endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mag_products_integration_rest_api_url URL of the Magento REST API endpoint.
	 *
	 * @return string The URL if everything is fine, empty string otherwise.
	 */
	public function validate_rest_api_url( $mag_products_integration_rest_api_url ) {
		$valid = false;
		if ( get_option( 'mag_products_integration_rest_api_url_validated' ) && $mag_products_integration_rest_api_url == get_option( 'mag_products_integration_rest_api_url', null ) ) {
			return $mag_products_integration_rest_api_url;
		} else {
			update_option( 'mag_products_integration_rest_api_url_validated', 0 );
			update_option( 'mag_products_integration_magento_module_installed', 0 );
		}
		if ( ! filter_var( $mag_products_integration_rest_api_url, FILTER_VALIDATE_URL ) ) {
			add_settings_error( 'mag_products_integration', 'mag_products_integration_rest_api_url', sprintf( __( 'The URL "%s" is invalid.', plugin_instance()->textdomain ), $mag_products_integration_rest_api_url ) );

			return '';
		}

		$response = wp_remote_get( $mag_products_integration_rest_api_url, array(
			'headers' => array(
				'Accept' => 'application/json'
			)
		) );

		if ( $response instanceof \WP_Error ) {
			add_settings_error( 'mag_products_integration', 'mag_products_integration_rest_api_url', _n( 'The given URL is valid but something went wrong while the plugin was trying to connect to the API. Please verify the error below.', 'The given URL is valid but something went wrong while the plugin was trying to connect to the API. Please verify the errors below.', count( $response->errors ), plugin_instance()->textdomain ) );
			foreach ( $response->get_error_messages() as $error ) {
				add_settings_error( 'mag_products_integration', 'mag_products_integration_rest_api_url', $error );
			}
		} elseif ( is_array( $response ) && ! empty( $response['body'] ) ) {
			$decoded_array = json_decode( $response['body'], true );
			if ( $decoded_array !== null ) {
				$valid = true;
				add_settings_error( 'mag_products_integration', 'mag_products_integration_rest_api_url', __( 'The API URL has been successfully validated.', plugin_instance()->textdomain ), 'updated' );
			} else {
				add_settings_error( 'mag_products_integration', 'mag_products_integration_rest_api_url', __( 'The URL is not a valid API endpoint.', plugin_instance()->textdomain ) );
			}
		} else {
			add_settings_error( 'mag_products_integration', 'mag_products_integration_rest_api_url', __( 'The URL is not a valid API endpoint.', plugin_instance()->textdomain ) );
		}

		update_option( 'mag_products_integration_rest_api_url_validated', intval( $valid ) );

		return $mag_products_integration_rest_api_url;
	}

	/**
	 * Render the admin configuration page
	 *
	 * @since 1.0.0
	 */
	public function page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Magento Settings', plugin_instance()->textdomain ); ?></h2>
			<?php settings_errors(); ?>

			<p><?php _e( 'You have to <strong>enable REST API</strong> first in your Magento store and <strong>give the product API Resources</strong> to your Guest role. Otherwise, it will be impossible to retreive your products.', plugin_instance()->textdomain ); ?>
			</p>

			<p style="color: #b50000; font-weight: bold;"><?php _e( 'Magento module is optional. If you are not using it, make sure to use the cache to reduce page load time.', plugin_instance()->textdomain ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'mag_products_integration' ); ?>
				<?php do_settings_sections( 'mag_products_integration' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Magento REST API URL', plugin_instance()->textdomain ); ?></th>
						<td><input type="text" class="regular-text" name="mag_products_integration_rest_api_url"
						           value="<?php echo esc_attr( get_option( 'mag_products_integration_rest_api_url' ) ); ?>"/>

							<p class="description"><?php _e( 'Do not forget to <strong>put the trailing slash</strong>. Ex: http://yourmagentostore.com/api/rest/', plugin_instance()->textdomain ); ?></p>
						</td>
					</tr>

					<?php if ( plugin_instance()->is_ready() && ! plugin_instance()->is_module_installed() ): ?>
						<tr valign="top">
							<th scope="row"></th>
							<td>
								<a href="#"
								   id="verify-magento-module"><?php _e( 'Verify Magento module installation and get available stores', plugin_instance()->textdomain ); ?>
									&#8594;</a></td>
						</tr>
					<?php elseif ( plugin_instance()->is_ready() && plugin_instance()->is_module_installed() ): ?>
						<tr valign="top">
							<th scope="row"><?php _e( 'Magento module installed', plugin_instance()->textdomain ); ?></th>
							<td><?php _e( 'Yes', plugin_instance()->textdomain ); ?></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Available stores code', plugin_instance()->textdomain ); ?></th>
							<td><?php echo esc_html( implode( ', ', unserialize( get_option( 'mag_products_integration_stores_code', array() ) ) ) ); ?></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Default store code', plugin_instance()->textdomain ); ?></th>
							<td><?php echo esc_html( get_option( 'mag_products_integration_default_store_code', '' ) ); ?></td>
						</tr>
					<?php endif; ?>

					<tr valign="top">
						<th scope="now"><?php _e( 'Enable cache', plugin_instance()->textdomain ); ?></th>
						<td>
							<input type="checkbox"
							       name="mag_products_integration_cache_enabled"<?php echo plugin_instance()->get_cache()->is_enabled() ? ' checked' : ''; ?> />
						</td>
					</tr>

					<?php if ( plugin_instance()->get_cache()->is_enabled() ): ?>
						<tr valign="top">
							<th scope="now"><?php _e( 'Cache lifetime', plugin_instance()->textdomain ); ?></th>
							<td>
								<?php $this->display_cache_lifetime_html( get_option( 'mag_products_integration_cache_lifetime', Cache::DEFAULT_CACHE_LIFETIME ) ); ?>
							</td>
						</tr>
					<?php endif; ?>

					<tr valign="top">
						<th scope="row"><?php _e( 'Use jQuery script', plugin_instance()->textdomain ); ?></th>
						<td><input type="checkbox"<?php echo $this->use_jquery_script() ? ' checked' : ''; ?>
						           name="mag_products_integration_jquery_script"
						           value="<?php echo esc_attr( get_option( 'mag_products_integration_rest_api_url' ) ); ?>"/>

							<span
								class="description"><?php _e( 'Automatically adjust height of all products block.', plugin_instance()->textdomain ); ?></span>
						</td>
					</tr>
				</table>

				<?php if ( ! plugin_instance()->get_cache()->is_enabled() ): ?>
					<input type="hidden" name="mag_products_integration_cache_lifetime"
					       value="<?php echo plugin_instance()->get_cache()->get_lifetime(); ?>"/>
				<?php endif; ?>

				<p class="submit">
					<?php submit_button( null, 'primary', 'submit', false ); ?>
					<?php submit_button( __( 'Flush cache', plugin_instance()->textdomain ), 'secondary', 'flush-cache', false ); ?>
				</p>
			</form>
			<p><?php _e( 'For developers: <a target="_blank" href="http://magentowp.santerref.com/documentation.html"><strong>actions</strong> and <strong>filters</strong> documentation</a>.', plugin_instance()->textdomain ); ?></p>
		</div>
		<?php
	}

	/**
	 * Display <select> for cache lifetime
	 *
	 * @since 1.2.0
	 *
	 * @param int $default Default lifetime to be selected
	 */
	protected function display_cache_lifetime_html( $default_lifetime = Cache::DEFAULT_CACHE_LIFETIME ) {
		// Compatibility with 1.2.1
		if ( $default_lifetime == 'indefinite' ) {
			$default_lifetime = YEAR_IN_SECONDS;
		}
		$options = array(
			array( 'lifetime' => HOUR_IN_SECONDS, 'label' => __( '1 hour', plugin_instance()->textdomain ) ),
			array( 'lifetime' => 6 * HOUR_IN_SECONDS, 'label' => __( '6 hours', plugin_instance()->textdomain ) ),
			array( 'lifetime' => 12 * HOUR_IN_SECONDS, 'label' => __( '12 hours', plugin_instance()->textdomain ) ),
			array( 'lifetime' => DAY_IN_SECONDS, 'label' => __( '1 day', plugin_instance()->textdomain ) ),
			array( 'lifetime' => 3 * DAY_IN_SECONDS, 'label' => __( '3 days', plugin_instance()->textdomain ) ),
			array( 'lifetime' => WEEK_IN_SECONDS, 'label' => __( '1 week', plugin_instance()->textdomain ) ),
			array( 'lifetime' => YEAR_IN_SECONDS, 'label' => __( '1 year', plugin_instance()->textdomain ) )
		);

		$html = '<select name="mag_products_integration_cache_lifetime">';
		foreach ( $options as $option ) {
			$html .= '<option value="' . $option['lifetime'] . '"';
			if ( $option['lifetime'] == $default_lifetime ) {
				$html .= ' selected';
			}
			$html .= '>' . $option['label'] . '</option>';
		}
		$html .= '</select>';

		echo $html;
	}

	/**
	 * Tells if the jquery script is enabled or disabled
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function use_jquery_script() {
		return get_option( 'mag_products_integration_jquery_script', true );
	}

	/**
	 * Show the Plugin not configured notice.
	 *
	 * @since 1.0.0
	 */
	public function notify_plugin_not_ready() {
		?>
		<div class="error notice is-dismissible">
			<p><?php echo sprintf( __( 'Please <a href="%s">configure Magento plugin</a> before using the shortcode.', plugin_instance()->textdomain ), admin_url( 'admin.php?page=mag-products-integration%2Fclass.mag-products-integration-admin.php' ) ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show the Magento module not installed notice.
	 *
	 * @since 1.0.0
	 */
	public function notify_magento_module_not_verified() {
		?>
		<div class="error notice is-dismissible">
			<p><?php _e( 'Please verify Magento module installation and load available stores. <a id="dismiss-module-notice" href="#">Dismiss this notice, I am not going to use the Magento module.</a>', plugin_instance()->textdomain ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show notices if the plugin is not ready or the magento module not installed.
	 *
	 * @since 1.0.0
	 */
	public function verify_settings() {
		if ( ! plugin_instance()->is_ready() ) {
			add_action( 'admin_notices', array( $this, 'notify_plugin_not_ready' ) );
		} elseif ( ! plugin_instance()->is_module_installed() ) {
			$dismiss_module_notice = get_option( 'mag_products_integration_dismiss_module_notice', false );
			if ( ! $dismiss_module_notice ) {
				add_action( 'admin_notices', array( $this, 'notify_magento_module_not_verified' ) );
			}
		}
	}

	/**
	 * Create new Magento admin menu.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		add_menu_page(
			__( 'Magento', plugin_instance()->textdomain ),
			__( 'Magento', plugin_instance()->textdomain ),
			'manage_options',
			__FILE__,
			array( plugin_instance()->get_admin(), 'page' ),
			plugins_url( 'assets/images/icon-16x16.png', dirname( __FILE__ ) )
		);
	}
}
