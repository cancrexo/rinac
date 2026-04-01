<?php

namespace RINAC\Calendar;

/**
 * Calcula disponibilidad y capacidad restante para reservas.
 *
 * Nota: en esta primera implementación se calcula la capacidad efectiva
 * (global + slot) y la ocupación intenta inferirla desde `rinac_booking`
 * si existen las meta keys esperadas. Si no existen, la ocupación es 0.
 */
class AvailabilityManager {

    /**
     * TTL de caché para disponibilidad.
     *
     * @var int
     */
    private int $cacheTtlSeconds = 10 * MINUTE_IN_SECONDS;

    /**
     * Calcula disponibilidad para un producto dentro de un rango.
     *
     * @param int $product_id ID del producto `rinac_reserva`.
     * @param string $start Fecha/datetime (según booking_mode).
     * @param string $end Fecha/datetime (según booking_mode).
     * @param int|null $slot_id Slot opcional (si aplica).
     *
     * @return array<string,mixed>
     */
    public function getAvailability( int $product_id, string $start, string $end, ?int $slot_id = null ): array {
        $product_id = max( 0, $product_id );
        if ( $product_id <= 0 ) {
            return array(
                'available' => false,
                'remaining_capacity' => 0,
                'slots' => array(),
            );
        }

        $booking_mode = (string) get_post_meta( $product_id, 'rinac_booking_mode', true );
        if ( '' === $booking_mode ) {
            $booking_mode = 'date';
        }

        $allowed_slots = get_post_meta( $product_id, '_rinac_allowed_slots', true );
        $allowed_slots = $this->normalizeIdArray( $allowed_slots );

        $slot_ids = $allowed_slots;
        if ( null !== $slot_id && $slot_id > 0 ) {
            $slot_ids = in_array( $slot_id, $allowed_slots, true ) ? array( $slot_id ) : array();
        }

        $base_capacity = (int) get_post_meta( $product_id, '_rinac_base_capacity', true );
        $capacity_total_max = (int) get_post_meta( $product_id, '_rinac_capacity_total_max', true );
        $capacity_min_booking = (int) get_post_meta( $product_id, '_rinac_capacity_min_booking', true );

        $effective_global_capacity = $this->calculateEffectiveGlobalCapacity( $base_capacity, $capacity_total_max );

        // Si el modo no depende de slots, devolvemos un único bloque.
        if ( ! $this->bookingModeUsesSlots( $booking_mode ) ) {
            $occupied_equivalent = $this->calculateOccupiedEquivalentForGlobal( $product_id, $start, $end );
            $remaining_capacity = max( 0, $effective_global_capacity - $occupied_equivalent );
            $available = $this->isAvailableForCapacity( $remaining_capacity, $capacity_min_booking );

            return array(
                'available' => $available,
                'remaining_capacity' => $remaining_capacity,
                'booking_mode' => $booking_mode,
                'query' => array(
                    'start' => $start,
                    'end' => $end,
                ),
                'slots' => array(),
            );
        }

        $transient_key = $this->buildAvailabilityTransientKey( $product_id, $start, $end, $booking_mode, $slot_ids );
        $cached = get_transient( $transient_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        $slots = array();
        $available_any = false;

        foreach ( $slot_ids as $slot_id_iter ) {
            $slot_id_iter = (int) $slot_id_iter;
            if ( $slot_id_iter <= 0 ) {
                continue;
            }

            $slot_is_active_raw = get_post_meta( $slot_id_iter, '_rinac_slot_is_active', true );
            $slot_is_active = ( '' === $slot_is_active_raw ) ? 1 : (int) $slot_is_active_raw;
            if ( 0 === $slot_is_active ) {
                continue;
            }

            $slot_capacity_max = (int) get_post_meta( $slot_id_iter, '_rinac_capacity_max', true );
            $effective_slot_capacity = $this->calculateEffectiveSlotCapacity(
                $effective_global_capacity,
                $slot_capacity_max
            );

            $occupied_equivalent = $this->calculateOccupiedEquivalentForSlot(
                $product_id,
                $slot_id_iter,
                $start,
                $end,
                $booking_mode
            );

            $remaining_capacity = max( 0.0, (float) $effective_slot_capacity - (float) $occupied_equivalent );
            $available = $this->isAvailableForCapacity( (float) $remaining_capacity, (float) $capacity_min_booking );

            if ( $available ) {
                $available_any = true;
            }

            $slots[] = array(
                'slot_id' => $slot_id_iter,
                'effective_capacity' => (float) $effective_slot_capacity,
                'remaining_capacity' => (float) $remaining_capacity,
                'available' => $available,
            );
        }

        // Para no romper el contrato básico, también devolvemos un remaining agregado.
        $remaining_aggregate = 0.0;
        foreach ( $slots as $slot ) {
            if ( isset( $slot['remaining_capacity'] ) && is_numeric( $slot['remaining_capacity'] ) ) {
                $remaining_aggregate = max( $remaining_aggregate, (float) $slot['remaining_capacity'] );
            }
        }

        $result = array(
            'available' => $available_any,
            'remaining_capacity' => $remaining_aggregate,
            'booking_mode' => $booking_mode,
            'query' => array(
                'start' => $start,
                'end' => $end,
            ),
            'slots' => $slots,
        );

        set_transient( $transient_key, $result, $this->cacheTtlSeconds );

        return $result;
    }

    /**
     * Comprueba si el booking_mode depende de slots.
     *
     * @param string $booking_mode
     * @return bool
     */
    private function bookingModeUsesSlots( string $booking_mode ): bool {
        $slot_modes = array(
            'slot_dia',
            'date_range_same_time',
        );

        return in_array( $booking_mode, $slot_modes, true );
    }

    /**
     * Capacidad efectiva global (producto).
     *
     * @param int $base_capacity
     * @param int $capacity_total_max
     * @return int
     */
    private function calculateEffectiveGlobalCapacity( int $base_capacity, int $capacity_total_max ): int {
        if ( $base_capacity <= 0 && $capacity_total_max <= 0 ) {
            return 0;
        }

        if ( $base_capacity <= 0 ) {
            return $capacity_total_max;
        }

        if ( $capacity_total_max <= 0 ) {
            return $base_capacity;
        }

        return min( $base_capacity, $capacity_total_max );
    }

    /**
     * Capacidad efectiva del slot.
     *
     * @param int $effective_global_capacity
     * @param int $slot_capacity_max
     * @return int
     */
    private function calculateEffectiveSlotCapacity( int $effective_global_capacity, int $slot_capacity_max ): int {
        if ( $effective_global_capacity <= 0 ) {
            return 0;
        }

        if ( $slot_capacity_max > 0 ) {
            return min( $effective_global_capacity, $slot_capacity_max );
        }

        return $effective_global_capacity;
    }

    /**
     * Decide si una capacidad restante permite crear reserva.
     *
     * @param int $remaining_capacity
     * @param int $capacity_min_booking
     * @return bool
     */
    private function isAvailableForCapacity( float $remaining_capacity, float $capacity_min_booking ): bool {
        if ( $capacity_min_booking > 0.0 ) {
            return $remaining_capacity >= $capacity_min_booking;
        }

        return $remaining_capacity > 0;
    }

    /**
     * Ocupación equivalente para un slot.
     *
     * Espera meta keys en `rinac_booking` (si existen):
     * - `_rinac_booking_product_id`
     * - `_rinac_booking_slot_id`
     * - `_rinac_booking_equivalent_qty`
     *
     * @return int
     */
    private function calculateOccupiedEquivalentForSlot(
        int $product_id,
        int $slot_id,
        string $start,
        string $end,
        string $booking_mode
    ): float {
        $equivalent_qty = 0.0;

        $query = new \WP_Query(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'publish', 'pending', 'private' ),
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => '_rinac_booking_product_id',
                        'value' => $product_id,
                        'compare' => '=',
                        'type' => 'NUMERIC',
                    ),
                    array(
                        'key' => '_rinac_booking_slot_id',
                        'value' => $slot_id,
                        'compare' => '=',
                        'type' => 'NUMERIC',
                    ),
                ),
            )
        );

        if ( empty( $query->posts ) ) {
            return 0;
        }

        foreach ( $query->posts as $booking_id ) {
            if ( ! $this->shouldCountBookingForOccupancy( (int) $booking_id ) ) {
                continue;
            }
            if ( ! $this->bookingOverlapsRange( (int) $booking_id, $start, $end ) ) {
                continue;
            }
            $qty = get_post_meta( $booking_id, '_rinac_booking_equivalent_qty', true );
            if ( is_numeric( $qty ) ) {
                $equivalent_qty += (float) $qty;
            }
        }

        return $equivalent_qty;
    }

    /**
     * Ocupación equivalente global (cuando el booking_mode no usa slots).
     *
     * En esta fase, devolvemos 0 si no hay meta contract implementado.
     *
     * @return int
     */
    private function calculateOccupiedEquivalentForGlobal( int $product_id, string $start, string $end ): float {
        $equivalent_qty = 0.0;

        $query = new \WP_Query(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'publish', 'pending', 'private' ),
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => '_rinac_booking_product_id',
                        'value' => $product_id,
                        'compare' => '=',
                        'type' => 'NUMERIC',
                    ),
                ),
            )
        );

        if ( empty( $query->posts ) ) {
            return 0;
        }

        foreach ( $query->posts as $booking_id ) {
            if ( ! $this->shouldCountBookingForOccupancy( (int) $booking_id ) ) {
                continue;
            }
            if ( ! $this->bookingOverlapsRange( (int) $booking_id, $start, $end ) ) {
                continue;
            }
            $qty = get_post_meta( $booking_id, '_rinac_booking_equivalent_qty', true );
            if ( is_numeric( $qty ) ) {
                $equivalent_qty += (float) $qty;
            }
        }

        return $equivalent_qty;
    }

    /**
     * Normaliza un meta valor (que puede ser string o array) a array de IDs.
     *
     * @param mixed $value
     * @return array<int>
     */
    private function normalizeIdArray( $value ): array {
        if ( ! is_array( $value ) ) {
            if ( is_string( $value ) && '' !== $value ) {
                return array( absint( $value ) );
            }

            return array();
        }

        $ids = array_map( 'absint', $value );
        $ids = array_filter( $ids, function ( int $id ): bool {
            return $id > 0;
        } );

        return array_values( array_unique( $ids ) );
    }

    /**
     * Genera una clave de transient determinística.
     *
     * @param int $product_id
     * @param string $start
     * @param string $end
     * @param string $booking_mode
     * @param array<int> $slot_ids
     * @return string
     */
    private function buildAvailabilityTransientKey(
        int $product_id,
        string $start,
        string $end,
        string $booking_mode,
        array $slot_ids
    ): string {
        $slot_part = implode( ',', array_map( 'intval', $slot_ids ) );
        $raw = $product_id . '|' . $booking_mode . '|' . $start . '|' . $end . '|' . $slot_part;

        return 'rinac_availability_' . md5( $raw );
    }

    /**
     * Define si una reserva debe impactar ocupación.
     *
     * @param int $booking_id
     * @return bool
     */
    private function shouldCountBookingForOccupancy( int $booking_id ): bool {
        if ( $booking_id <= 0 ) {
            return false;
        }

        $booking_status = (string) get_post_meta( $booking_id, '_rinac_booking_status', true );
        if ( 'cancelled' === $booking_status || 'expired' === $booking_status ) {
            return false;
        }

        if ( 'hold' === $booking_status ) {
            $expires_at = (int) get_post_meta( $booking_id, '_rinac_hold_expires_at', true );
            if ( $expires_at > 0 && $expires_at < time() ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Comprueba solape de rango de reserva.
     *
     * @param int $booking_id
     * @param string $query_start
     * @param string $query_end
     * @return bool
     */
    private function bookingOverlapsRange( int $booking_id, string $query_start, string $query_end ): bool {
        $booking_start = (string) get_post_meta( $booking_id, '_rinac_booking_start', true );
        $booking_end = (string) get_post_meta( $booking_id, '_rinac_booking_end', true );

        if ( '' === $query_start || '' === $query_end ) {
            return true;
        }
        if ( '' === $booking_start || '' === $booking_end ) {
            return true;
        }

        return $booking_start <= $query_end && $booking_end >= $query_start;
    }
}

