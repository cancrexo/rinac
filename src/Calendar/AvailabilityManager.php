<?php

namespace RINAC\Calendar;

use RINAC\Models\ReservaProduct;

/**
 * Calcula disponibilidad/capacidad para productos `rinac_reserva`.
 */
class AvailabilityManager {

    /**
     * Obtiene disponibilidad de un producto en un rango.
     *
     * @param int   $product_id ID del producto.
     * @param int   $start_ts Timestamp de inicio.
     * @param int   $end_ts Timestamp de fin.
     * @param array $context Contexto opcional (slot, turno, etc.).
     * @return array
     */
    public function getAvailability( int $product_id, int $start_ts, int $end_ts, array $context = array() ): array {
        if ( $start_ts <= 0 || $end_ts <= 0 || $end_ts <= $start_ts ) {
            return array(
                'available'           => false,
                'message'             => esc_html__( 'Rango de fechas inválido.', 'rinac' ),
                'requested_capacity'  => 0,
                'occupied_capacity'   => 0,
                'remaining_capacity'  => 0,
                'effective_capacity'  => 0,
                'booking_mode'        => 'date',
                'product_id'          => $product_id,
            );
        }

        $product = $this->getReservaProduct( $product_id );
        if ( ! $product ) {
            return array(
                'available'           => false,
                'message'             => esc_html__( 'Producto reservable no válido.', 'rinac' ),
                'requested_capacity'  => 0,
                'occupied_capacity'   => 0,
                'remaining_capacity'  => 0,
                'effective_capacity'  => 0,
                'booking_mode'        => 'date',
                'product_id'          => $product_id,
            );
        }

        $cache_key = $this->buildCacheKey( $product_id, $start_ts, $end_ts, $context );
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $requested_capacity = isset( $context['requested_capacity'] ) ? max( 1, absint( $context['requested_capacity'] ) ) : 1;
        $slot_id = isset( $context['slot_id'] ) ? absint( $context['slot_id'] ) : 0;
        $turno_id = isset( $context['turno_id'] ) ? absint( $context['turno_id'] ) : 0;

        $booking_mode = $this->getProductMetaString( $product_id, '_rinac_booking_mode', 'date' );
        $effective_capacity = $this->resolveEffectiveCapacity( $product_id, $slot_id, $turno_id );
        $occupied_capacity = $this->getOccupiedCapacity( $product_id, $start_ts, $end_ts, $slot_id, $turno_id );
        $remaining_capacity = max( 0, $effective_capacity - $occupied_capacity );
        $available = $remaining_capacity >= $requested_capacity;

        $result = array(
            'available'           => $available,
            'message'             => $available
                ? esc_html__( 'Disponibilidad encontrada.', 'rinac' )
                : esc_html__( 'No hay capacidad suficiente para el rango solicitado.', 'rinac' ),
            'requested_capacity'  => $requested_capacity,
            'occupied_capacity'   => $occupied_capacity,
            'remaining_capacity'  => $remaining_capacity,
            'effective_capacity'  => $effective_capacity,
            'booking_mode'        => $booking_mode,
            'product_id'          => $product_id,
            'start_ts'            => $start_ts,
            'end_ts'              => $end_ts,
            'slot_id'             => $slot_id,
            'turno_id'            => $turno_id,
        );

        set_transient( $cache_key, $result, MINUTE_IN_SECONDS * 5 );

        return $result;
    }

    /**
     * Obtiene capacidad ocupada por reservas solapadas.
     *
     * @param int $product_id Producto.
     * @param int $start_ts Inicio.
     * @param int $end_ts Fin.
     * @param int $slot_id Slot.
     * @param int $turno_id Turno.
     * @return int
     */
    private function getOccupiedCapacity( int $product_id, int $start_ts, int $end_ts, int $slot_id, int $turno_id ): int {
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'     => '_rinac_booking_product_id',
                'value'   => $product_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
            array(
                'key'     => '_rinac_booking_start_ts',
                'value'   => $end_ts,
                'compare' => '<',
                'type'    => 'NUMERIC',
            ),
            array(
                'key'     => '_rinac_booking_end_ts',
                'value'   => $start_ts,
                'compare' => '>',
                'type'    => 'NUMERIC',
            ),
        );

        if ( $slot_id > 0 ) {
            $meta_query[] = array(
                'key'     => '_rinac_booking_slot_id',
                'value'   => $slot_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }

        if ( $turno_id > 0 ) {
            $meta_query[] = array(
                'key'     => '_rinac_booking_turno_id',
                'value'   => $turno_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }

        $bookings = get_posts(
            array(
                'post_type'      => 'rinac_booking',
                'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => $meta_query,
            )
        );

        $occupied = 0;
        foreach ( $bookings as $booking_id ) {
            $units = (int) get_post_meta( $booking_id, '_rinac_booking_units', true );
            $occupied += max( 1, $units );
        }

        return $occupied;
    }

    /**
     * Resuelve capacidad efectiva aplicando límites globales y por slot/turno.
     *
     * @param int $product_id Producto.
     * @param int $slot_id Slot.
     * @param int $turno_id Turno.
     * @return int
     */
    private function resolveEffectiveCapacity( int $product_id, int $slot_id, int $turno_id ): int {
        $base_capacity = (int) get_post_meta( $product_id, '_rinac_base_capacity', true );
        $global_max = (int) get_post_meta( $product_id, '_rinac_capacity_total_max', true );

        $capacity_candidates = array_filter(
            array(
                $base_capacity > 0 ? $base_capacity : 0,
                $global_max > 0 ? $global_max : 0,
            )
        );

        if ( $slot_id > 0 ) {
            $slot_capacity = (int) get_post_meta( $slot_id, '_rinac_capacity_max', true );
            if ( $slot_capacity > 0 ) {
                $capacity_candidates[] = $slot_capacity;
            }
        }

        if ( $turno_id > 0 ) {
            $turno_capacity = (int) get_post_meta( $turno_id, '_rinac_capacity_max', true );
            if ( $turno_capacity > 0 ) {
                $capacity_candidates[] = $turno_capacity;
            }
        }

        if ( empty( $capacity_candidates ) ) {
            return 1;
        }

        return max( 1, min( $capacity_candidates ) );
    }

    /**
     * Obtiene un producto reservable válido.
     *
     * @param int $product_id Producto.
     * @return object|null
     */
    private function getReservaProduct( int $product_id ): ?object {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return null;
        }

        $wc_get_product_callable = 'wc_get_product';
        $product = $wc_get_product_callable( $product_id );
        if ( $product instanceof ReservaProduct ) {
            return $product;
        }

        if ( is_object( $product ) && method_exists( $product, 'get_type' ) && 'rinac_reserva' === (string) $product->get_type() ) {
            return $product;
        }

        return null;
    }

    /**
     * Lee una cadena de meta con fallback.
     *
     * @param int    $post_id Post.
     * @param string $meta_key Meta key.
     * @param string $default Valor por defecto.
     * @return string
     */
    private function getProductMetaString( int $post_id, string $meta_key, string $default ): string {
        $value = (string) get_post_meta( $post_id, $meta_key, true );
        if ( '' === $value ) {
            return $default;
        }

        return $value;
    }

    /**
     * Crea clave de caché por producto/rango/contexto.
     *
     * @param int   $product_id Producto.
     * @param int   $start_ts Inicio.
     * @param int   $end_ts Fin.
     * @param array $context Contexto.
     * @return string
     */
    private function buildCacheKey( int $product_id, int $start_ts, int $end_ts, array $context ): string {
        $key_payload = array(
            'product_id' => $product_id,
            'start_ts'   => $start_ts,
            'end_ts'     => $end_ts,
            'slot_id'    => isset( $context['slot_id'] ) ? absint( $context['slot_id'] ) : 0,
            'turno_id'   => isset( $context['turno_id'] ) ? absint( $context['turno_id'] ) : 0,
            'requested'  => isset( $context['requested_capacity'] ) ? absint( $context['requested_capacity'] ) : 1,
        );

        return 'rinac_av_' . md5( wp_json_encode( $key_payload ) );
    }
}

