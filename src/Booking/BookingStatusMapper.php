<?php

namespace RINAC\Booking;

/**
 * Mapea estados de pedido a estados internos de reserva.
 */
class BookingStatusMapper {

    /**
     * Devuelve estado de reserva para un estado de pedido WooCommerce.
     *
     * @param string $order_status
     * @return string
     */
    public function mapOrderStatusToBookingStatus( string $order_status ): string {
        $map = array(
            'pending' => 'hold',
            'on-hold' => 'hold',
            'processing' => 'confirmed',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'failed' => 'cancelled',
            'refunded' => 'cancelled',
        );

        return isset( $map[ $order_status ] ) ? $map[ $order_status ] : 'hold';
    }

    /**
     * Indica si debe liberarse capacidad para este estado.
     *
     * @param string $order_status
     * @return bool
     */
    public function shouldReleaseCapacity( string $order_status ): bool {
        return in_array( $order_status, array( 'cancelled', 'failed', 'refunded' ), true );
    }

    /**
     * Estado de post recomendado en función del estado del pedido.
     *
     * @param string $order_status
     * @return string
     */
    public function mapOrderStatusToPostStatus( string $order_status ): string {
        if ( $this->shouldReleaseCapacity( $order_status ) ) {
            return 'draft';
        }

        if ( in_array( $order_status, array( 'processing', 'completed' ), true ) ) {
            return 'publish';
        }

        return 'pending';
    }
}
