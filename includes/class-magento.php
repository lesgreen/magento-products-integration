<?php

namespace mag_products_integration;

class Magento implements Magento_Interface {

	protected $url_parameters = array();

	protected $filters_count = 1;

	public function get_products( Magento_Parameters $parameters ) {
		$request_url = $this->get_base_url();

		if ( $parameters->get_category_id() ) {
			$this->url_parameters[] = new Get_Parameter( 'category_id', $parameters->get_category_id() );
		}

		if ( $parameters->get_limit() ) {
			$this->url_parameters[] = new Get_Parameter( 'limit', $parameters->get_limit() );
		}

		if ( $parameters->get_order() ) {
			$this->url_parameters[] = new Get_Parameter( 'order', $parameters->get_order() );
		}

		if ( $parameters->get_dir() ) {
			$this->url_parameters[] = new Get_Parameter( 'dir', $parameters->get_dir() );
		}

		if ( $parameters->get_image_width() ) {
			$this->url_parameters[] = new Get_Parameter( 'image_width', $parameters->get_image_width() );
		}

		if ( $parameters->get_image_height() ) {
			$this->url_parameters[] = new Get_Parameter( 'image_height', $parameters->get_image_height() );
		}

		if ( $parameters->get_sku() ) {
			$this->url_parameters[] = new Get_Parameter( 'filter[' . $this->filters_count . '][attribute]', 'sku' );
			foreach ( $parameters->get_sku() as $key => $sku ) {
				$this->url_parameters[] = new Get_Parameter( 'filter[' . $this->filters_count . '][in][' . $key . ']', $sku );
			}
			$this->filters_count ++;
		}

		if ( $parameters->get_name() ) {
			$this->url_parameters[] = new Get_Parameter( 'filter[' . $this->filters_count . '][attribute]', 'name' );
			$this->url_parameters[] = new Get_Parameter( 'filter[' . $this->filters_count . '][like]', $parameters->get_name() );
			$this->filters_count ++;
		}

		if ( $parameters->get_store() ) {
			$this->url_parameters[] = new Get_Parameter( '__store', $parameters->get_store() );
		}

		if ( ! empty( $this->url_parameters ) ) {
			$request_url .= '?' . implode( '&', array_map( function ( Get_Parameter $parameter ) {
					return $parameter->to_string();
				}, $this->url_parameters ) );
		}

		$products     = array();
		$products_arr = array();

		$response = wp_remote_get( $request_url );
		if ( $response['response']['code'] == 200 ) {
			$products_arr = json_decode( $response['body'], true );
		}

		$magento_module_installed = get_option( 'mag_products_integration_magento_module_installed', 0 );

		foreach ( $products_arr as $product_arr ) {
			if ( ! $magento_module_installed ) {
				$this->get_missing_attributes( $product_arr, $parameters->get_store() );
			}

			$product = new Product();

			$product->name                      = $product_arr['name'];
			$product->image_url                 = $product_arr['image_url'];
			$product->url                       = $product_arr['url'];
			$product->short_description         = $product_arr['short_description'];
			$product->regular_price_without_tax = $product_arr['regular_price_without_tax'];
			$product->final_price_without_tax   = $product_arr['final_price_without_tax'];
			$product->type_id                   = $product_arr['type_id'];
			$product->is_in_stock               = $product_arr['is_in_stock'];
			$product->buy_now_url               = $product_arr['buy_now_url'];

			$products[] = $product;
		}

		return $products;
	}

	protected function get_missing_attributes( &$product_arr, $store ) {
		$request_url = $this->get_base_url() . '/' . $product_arr['entity_id'];
		$request_url .= ! empty( $store ) ? '?___store=' . urlencode( $store ) : '';
		$response = wp_remote_get( $request_url );
		if ( $response['response']['code'] == 200 ) {
			$full_product_arr           = json_decode( $response['body'], true );
			$product_arr['url']         = $full_product_arr['url'];
			$product_arr['is_in_stock'] = $full_product_arr['is_in_stock'];
			$product_arr['type_id']     = $full_product_arr['type_id'];
			$product_arr['image_url']   = $full_product_arr['image_url'];
			if ( isset( $product_arr['buy_now_url'] ) ) {
				$product_arr['buy_now_url'] = $full_product_arr['buy_now_url'];
			} else {
				$product_arr['buy_now_url'] = '';
			}
		}
	}

	protected function get_base_url() {
		$base_url = get_option( 'mag_products_integration_rest_api_url' );
		if ( substr( $base_url, - 1 ) !== '/' ) {
			$base_url .= '/';
		}

		return $base_url . 'products';
	}

}