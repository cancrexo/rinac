<?php

namespace rinac\Booking;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase base del producto WooCommerce de tipo "Reserva".
 *
 * En fases posteriores se añadirán metadatos específicos (fecha(s), slot/turno, participantes, recursos)
 * y se ajustará el pricing dinámico.
 */
// @intelephense-ignore-next-line
final class RinacReservationProduct extends \WC_Product_Simple {
    public function get_type(): string {
        return 'rinac_reserva';
    }
}

