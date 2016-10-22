<?php

namespace mag_products_integration;

class Get_Parameter {

	protected $key;

	protected $value;

	public function __construct( $key, $value ) {
		$this->key   = $key;
		$this->value = $value;
	}

	public function to_string() {
		return $this->key . '=' . urlencode( $this->value );
	}
}