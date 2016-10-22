<?php

namespace mag_products_integration;

/**
 * Class Mag_Shortcode
 *
 * @since 1.0.0
 *
 * @package mag_products_integration
 */
class Shortcode {

	/**
	 * Render the products list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode parameter.
	 * @param string $content Currently not used.
	 *
	 * @return string Products list HTML.
	 */
	public function do_shortcode( $atts, $content = "" ) {
		$html = '';

		if ( plugin_instance()->is_ready() ) {
			$atts = shortcode_atts( array(
				'limit'        => '12',
				'title'        => 'h2',
				'class'        => '',
				'sku'          => '',
				'category'     => '',
				'name'         => '',
				'store'        => get_option( 'mag_products_integration_default_store_code', '' ),
				'width'        => '100%',
				'target'       => '',
				'dir'          => 'desc',
				'order'        => 'entity_id',
				'prefix'       => '',
				'suffix'       => ' $',
				'image_width'  => '',
				'image_height' => '',
				'hide_image'   => false
			), $atts, 'magento' );

			$atts               = $this->sanitize_shortcode_atts( $atts );
			$magento_parameters = new Magento_Parameters( $atts );
			$shortcode_id       = sha1( $magento_parameters->to_string() );

			$products = plugin_instance()->get_cache()->get_cached_products( $shortcode_id );
			if ( $products === false ) {
				$products = plugin_instance()->get_magento()->get_products( $magento_parameters );
				if ( plugin_instance()->get_cache()->is_enabled() ) {
					plugin_instance()->get_cache()->set_cached_products( $products, $shortcode_id );
				}
			}

			$html = $this->get_products_html( $products, $atts );
		}

		return $html;
	}

	protected function sanitize_shortcode_atts( $atts ) {
		$atts['hide_image'] = filter_var( $atts['hide_image'], FILTER_VALIDATE_BOOLEAN );

		return $atts;
	}

	/**
	 * Generate the HTML of the products.
	 *
	 * @since 1.3.0
	 *
	 * @param array $products An array of Product objects.
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string The HTML to render.
	 */
	public function get_products_html( $products = array(), $atts ) {
		ob_start();
		if ( ! empty( $products ) ) {
			if ( isset( $products['messages'] ) && isset( $products['messages']['error'] ) && is_array( $products['messages']['error'] ) ) {
				echo '<div id="magento-products" class="errors"><ul>';
				foreach ( $products['messages']['error'] as $message ) {
					echo '<li>' . esc_html( $message['code'] ) . ' : ' . esc_html( $message['message'] ) . '</li>';
				}
				echo '</ul></div>';
			} else {
				do_action( 'mag_products_integration_before_products' );
				echo '<div class="magento-wrapper' . ( ( ! empty( $atts['class'] ) ) ? ' ' . esc_attr( $atts['class'] ) : '' ) . '"><ul class="products">';
				/** @var Product $product */
				foreach ( $products as $product ) {
					echo '<li class="product">';

					if ( ! empty( $product->image_url ) && ! $atts['hide_image'] ) {
						do_action( 'mag_products_integration_before_image', $product );
						$image = '<div class="image">';
						$image .= '<a' . ( ( ! empty( $atts['target'] ) ) ? ' target="' . esc_attr( $atts['target'] ) . '"' : '' ) . ' href="' . esc_url( $product->url ) . '">';
						$image .= '<img style="width:' . esc_attr( $atts['width'] ) . '" src="' . esc_html( $product->image_url ) . '" alt="' . esc_html( $product->name ) . '" />';
						$image .= '</a></div>';
						$image = apply_filters( 'mag_products_integration_product_image', $image, $product, $atts['width'], $atts['image_width'], $atts['image_height'] );
						echo $image;
						do_action( 'mag_products_integration_after_image', $product );
					}
					do_action( 'mag_products_integration_before_title', $product );
					echo '<' . esc_attr( $atts['title'] ) . ' class="name">';
					echo '<a' . ( ( ! empty( $atts['target'] ) ) ? ' target="' . esc_attr( $atts['target'] ) . '"' : '' ) . ' href="' . esc_url( $product->url ) . '">';
					echo apply_filters( 'mag_products_integration_product_name', esc_html( $product->name ), $product->name );
					echo '</a>';
					echo '</' . esc_attr( $atts['title'] ) . '>';
					do_action( 'mag_products_integration_after_title', $product );
					if ( ! empty( $product->short_description ) ) {
						do_action( 'mag_products_integration_before_short_description', $product );
						echo apply_filters( 'mag_products_integration_product_short_description', '<div class="short-description"><p>' . esc_html( $product->short_description ) . '</p></div>', $product->short_description );
						do_action( 'mag_products_integration_after_short_description', $product );
					}
					if ( $product->final_price_without_tax > 0 ) {
						do_action( 'mag_products_integration_before_price', $product );
						echo '<div class="price">';
						echo '<span class="current-price">';
						echo apply_filters( 'mag_products_integration_product_final_price_without_tax', esc_attr( $atts['prefix'] ) . esc_html( number_format( $product->final_price_without_tax, 2 ) ) . esc_attr( $atts['suffix'] ), $atts['prefix'], $product->final_price_without_tax, $atts['suffix'] );
						echo '</span>';
						if ( $product->regular_price_without_tax != $product->final_price_without_tax && $product->regular_price_without_tax > 0 ) {
							echo '<span class="regular-price">';
							echo apply_filters( 'mag_products_integration_product_regular_price_without_tax', esc_attr( $atts['prefix'] ) . esc_html( number_format( $product->regular_price_without_tax, 2 ) ) . esc_attr( $atts['suffix'] ), $atts['prefix'], $product->regular_price_without_tax, $atts['suffix'] );
							echo '</span>';
						}
						echo '</div>';
						do_action( 'mag_products_integration_after_price', $product );
					}
					do_action( 'mag_products_integration_before_add_to_cart_button', $product );
					echo '<div class="url">';
					if ( $product->is_in_stock && $product->type_id == 'simple' ) {
						echo apply_filters( 'mag_products_integration_product_buy_it_now_button', '<a class="buy-it-now" href="' . esc_html( $product->buy_now_url ) . '">' . __( 'Buy it now', plugin_instance()->textdomain ) . '</a>', $product->buy_now_url );
					} else {
						echo apply_filters( 'mag_products_integration_product_view_details_button', '<a class="view-details" href="' . esc_html( $product->url ) . '">' . __( 'View details', plugin_instance()->textdomain ) . '</a>', $product->url );
					}
					echo '</div>';
					do_action( 'mag_products_integration_after_add_to_cart_button', $product );
					echo '</li>';
				}
				echo '</ul></div>';
				do_action( 'mag_products_integration_after_products' );
				if ( plugin_instance()->get_admin()->use_jquery_script() ) {
					echo '<script type="text/javascript">var max = -1; jQuery(".magento-wrapper ul > li").each(function() { var h = jQuery(this).height(); max = h > max ? h : max; }); jQuery(".magento-wrapper ul > li").css({height: max+"px"});</script>';
				}
			}
		} else {
			do_action( 'mag_products_integration_no_products_found' );
		}
		$content = ob_get_clean();

		return $content;
	}

}
