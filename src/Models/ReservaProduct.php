<?php

namespace RINAC\Models;

/**
 * Tipo de producto reservado de RINAC.
 */
class ReservaProduct extends \WC_Product {

    /**
     * Devuelve el identificador del tipo WooCommerce.
     *
     * @return string
     */
    public function get_type(): string {
        return 'rinac_reserva';
    }
}
