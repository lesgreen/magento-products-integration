<?php

namespace mag_products_integration;

class Magento_Parameters {

	protected $sku = array();

	protected $category_id = '';

	protected $name = array();

	protected $store = '';

	protected $dir = 'asc';

	protected $order = '';

	protected $limit = 0;

	protected $image_width = 0;

	protected $image_height = 0;

	public function __construct( $shortcode_atts ) {
		foreach ( $shortcode_atts as $key => $att ) {
			$shortcode_atts[ $key ] = trim( $att );
		}

		$this->set_sku( $shortcode_atts['sku'] );
		$this->set_category_id( $shortcode_atts['category'] );
		$this->set_name( $shortcode_atts['name'] );
		$this->set_store( $shortcode_atts['store'] );
		$this->set_dir( $shortcode_atts['dir'] );
		$this->set_order( $shortcode_atts['order'] );
		$this->set_limit( $shortcode_atts['limit'] );
		$this->set_image_width( $shortcode_atts['image_width'] );
		$this->set_image_height( $shortcode_atts['image_height'] );
	}

	protected function set_sku( $sku ) {
		$this->sku = array_filter( explode( ',', $sku ) );
	}

	protected function set_category_id( $category_id ) {
		$this->category_id = ( is_numeric( $category_id ) && $category_id > 0 ) ? intval( $category_id ) : '';
	}

	protected function set_name( $name ) {
		$this->name = (string) $name;
	}

	protected function set_store( $store ) {
		$this->store = (string) $store;
	}

	protected function set_dir( $dir ) {
		$this->dir = ( preg_match( '/^(asc|desc)$/i', $dir ) ) ? strtolower( $dir ) : '';
	}

	protected function set_order( $order ) {
		$this->order = (string) $order;
	}

	protected function set_limit( $limit ) {
		$this->limit = ( is_numeric( $limit ) && $limit > 0 ) ? intval( $limit ) : 0;
	}

	protected function set_image_width( $image_width ) {
		$this->image_width = ( is_numeric( $image_width ) && $image_width > 0 ) ? intval( $image_width ) : 0;
	}

	protected function set_image_height( $image_height ) {
		$this->image_height = ( is_numeric( $image_height ) && $image_height > 0 ) ? intval( $image_height ) : 0;
	}

	public function get_sku() {
		return $this->sku;
	}

	public function get_category_id() {
		return $this->category_id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_store() {
		return $this->store;
	}

	public function get_dir() {
		return $this->dir;
	}

	public function get_order() {
		return $this->order;
	}

	public function get_limit() {
		return $this->limit;
	}

	public function get_image_width() {
		return $this->image_width;
	}

	public function get_image_height() {
		return $this->image_height;
	}

	public function to_string() {
		return serialize( $this );
	}

}