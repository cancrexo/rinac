<?php

namespace RINAC\Models;

use WC_Product;

/**
 * Producto WooCommerce de tipo `rinac_reserva`.
 */
class ReservaProduct extends WC_Product {

    /**
     * Constructor.
     *
     * @param mixed $product Producto.
     */
    public function __construct( $product = 0 ) {
        parent::__construct( $product );
    }

    /**
     * Tipo de producto.
     *
     * @return string
     */
    public function get_type() {
        return 'rinac_reserva';
    }
}

