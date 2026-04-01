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
            'partially-refunded' => 'partially_refunded',
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

        return 'private';
    }

    /**
     * Resuelve transición completa considerando contexto financiero del pedido.
     *
     * @param string $order_status
     * @param mixed $order
     * @return array{booking_status:string,post_status:string,release_capacity:bool}
     */
    public function resolveTransition( string $order_status, $order = null ): array {
        $normalized_status = $order_status;
        if ( 'refunded' === $order_status && $this->isPartialRefund( $order ) ) {
            $normalized_status = 'partially-refunded';
        }

        $booking_status = $this->mapOrderStatusToBookingStatus( $normalized_status );
        $release_capacity = $this->shouldReleaseCapacity( $normalized_status );
        $post_status = $this->mapOrderStatusToPostStatus( $normalized_status );

        return array(
            'booking_status' => $booking_status,
            'post_status' => $post_status,
            'release_capacity' => $release_capacity,
        );
    }

    /**
     * Determina si el pedido está parcialmente reembolsado.
     *
     * @param mixed $order
     * @return bool
     */
    private function isPartialRefund( $order ): bool {
        if ( ! $order || ! is_object( $order ) ) {
            return false;
        }
        if ( ! method_exists( $order, 'get_total' ) || ! method_exists( $order, 'get_total_refunded' ) ) {
            return false;
        }

        $order_total = (float) $order->get_total();
        $refunded_total = (float) $order->get_total_refunded();
        if ( $order_total <= 0.0 || $refunded_total <= 0.0 ) {
            return false;
        }

        return $refunded_total < $order_total;
    }
}
