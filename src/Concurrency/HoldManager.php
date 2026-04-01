<?php

namespace RINAC\Concurrency;

use WP_Error;

/**
 * Gestiona bloqueos temporales (quote/hold) para evitar sobre-reservas.
 */
class HoldManager {

    /**
     * TTL por defecto del bloqueo (segundos).
     *
     * @var int
     */
    private int $defaultTtl = 15 * MINUTE_IN_SECONDS;

    /**
     * Crea un hold temporal en `rinac_booking`.
     *
     * @param int $product_id
     * @param int $slot_id
     * @param string $start
     * @param string $end
     * @param float $equivalent_qty
     * @param int $ttl_seconds
     * @return array<string,mixed>|WP_Error
     */
    public function createHold(
        int $product_id,
        int $slot_id,
        string $start,
        string $end,
        float $equivalent_qty,
        int $ttl_seconds = 0
    ) {
        $this->cleanupExpiredHolds();
        $ttl = $ttl_seconds > 0 ? $ttl_seconds : $this->defaultTtl;
        $expires_at = time() + $ttl;
        $token = wp_generate_uuid4();

        $booking_id = wp_insert_post(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => 'pending',
                'post_title' => sprintf(
                    /* translators: 1: product id, 2: token. */
                    __( 'Hold producto #%1$d (%2$s)', 'rinac' ),
                    $product_id,
                    substr( $token, 0, 8 )
                ),
            ),
            true
        );

        if ( is_wp_error( $booking_id ) || ! is_numeric( $booking_id ) ) {
            return new WP_Error(
                'rinac_hold_create_failed',
                esc_html__( 'No se pudo crear el bloqueo temporal.', 'rinac' )
            );
        }

        $booking_id = (int) $booking_id;
        update_post_meta( $booking_id, '_rinac_booking_product_id', $product_id );
        update_post_meta( $booking_id, '_rinac_booking_slot_id', $slot_id );
        update_post_meta( $booking_id, '_rinac_booking_start', $start );
        update_post_meta( $booking_id, '_rinac_booking_end', $end );
        update_post_meta( $booking_id, '_rinac_booking_equivalent_qty', max( 0.0, $equivalent_qty ) );
        update_post_meta( $booking_id, '_rinac_booking_status', 'hold' );
        update_post_meta( $booking_id, '_rinac_hold_token', $token );
        update_post_meta( $booking_id, '_rinac_hold_expires_at', $expires_at );

        $this->invalidateAvailabilityTransients();

        return array(
            'booking_id' => $booking_id,
            'hold_token' => $token,
            'expires_at' => $expires_at,
            'ttl_seconds' => $ttl,
        );
    }

    /**
     * Confirma un hold existente.
     *
     * @param string $token
     * @return array<string,mixed>|WP_Error
     */
    public function confirmHold( string $token ) {
        $this->cleanupExpiredHolds();
        $hold = $this->getHoldByToken( $token );
        if ( is_wp_error( $hold ) ) {
            return $hold;
        }

        $booking_id = (int) $hold['booking_id'];
        update_post_meta( $booking_id, '_rinac_booking_status', 'confirmed' );
        delete_post_meta( $booking_id, '_rinac_hold_expires_at' );

        wp_update_post(
            array(
                'ID' => $booking_id,
                'post_status' => 'private',
            )
        );

        $this->invalidateAvailabilityTransients();

        return array(
            'booking_id' => $booking_id,
            'hold_token' => $token,
            'status' => 'confirmed',
        );
    }

    /**
     * Obtiene hold por token y valida expiración.
     *
     * @param string $token
     * @return array<string,mixed>|WP_Error
     */
    public function getHoldByToken( string $token ) {
        if ( '' === $token ) {
            return new WP_Error( 'rinac_hold_token_missing', esc_html__( 'Falta hold_token.', 'rinac' ) );
        }

        $query = new \WP_Query(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'pending', 'private', 'publish' ),
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => '_rinac_hold_token',
                        'value' => $token,
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( empty( $query->posts ) ) {
            return new WP_Error( 'rinac_hold_not_found', esc_html__( 'No existe bloqueo activo para este token.', 'rinac' ) );
        }

        $booking_id = (int) $query->posts[0];
        $booking_status = (string) get_post_meta( $booking_id, '_rinac_booking_status', true );
        if ( 'hold' !== $booking_status ) {
            return new WP_Error( 'rinac_hold_not_active', esc_html__( 'El bloqueo ya no está en estado hold.', 'rinac' ) );
        }

        $expires_at = (int) get_post_meta( $booking_id, '_rinac_hold_expires_at', true );
        if ( $expires_at > 0 && $expires_at < time() ) {
            wp_update_post( array( 'ID' => $booking_id, 'post_status' => 'draft' ) );
            update_post_meta( $booking_id, '_rinac_booking_status', 'expired' );
            $this->invalidateAvailabilityTransients();
            return new WP_Error( 'rinac_hold_expired', esc_html__( 'El bloqueo temporal ha expirado.', 'rinac' ) );
        }

        return array(
            'booking_id' => $booking_id,
            'product_id' => (int) get_post_meta( $booking_id, '_rinac_booking_product_id', true ),
            'slot_id' => (int) get_post_meta( $booking_id, '_rinac_booking_slot_id', true ),
            'start' => (string) get_post_meta( $booking_id, '_rinac_booking_start', true ),
            'end' => (string) get_post_meta( $booking_id, '_rinac_booking_end', true ),
            'equivalent_qty' => (float) get_post_meta( $booking_id, '_rinac_booking_equivalent_qty', true ),
            'expires_at' => $expires_at,
        );
    }

    /**
     * Limpia holds expirados.
     *
     * @return void
     */
    public function cleanupExpiredHolds(): void {
        $query = new \WP_Query(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'pending', 'private', 'publish' ),
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => '_rinac_booking_status',
                        'value' => 'hold',
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( empty( $query->posts ) ) {
            return;
        }

        $updated = false;
        foreach ( $query->posts as $booking_id ) {
            $booking_id = (int) $booking_id;
            $expires_at = (int) get_post_meta( $booking_id, '_rinac_hold_expires_at', true );
            if ( $expires_at > 0 && $expires_at < time() ) {
                wp_update_post( array( 'ID' => $booking_id, 'post_status' => 'draft' ) );
                update_post_meta( $booking_id, '_rinac_booking_status', 'expired' );
                $updated = true;
            }
        }

        if ( $updated ) {
            $this->invalidateAvailabilityTransients();
        }
    }

    /**
     * Limpia caché de disponibilidad.
     */
    private function invalidateAvailabilityTransients(): void {
        global $wpdb;
        if ( ! $wpdb || ! isset( $wpdb->options ) ) {
            return;
        }

        $like_transient = $wpdb->esc_like( '_transient_rinac_availability_' ) . '%';
        $like_timeout = $wpdb->esc_like( '_transient_timeout_rinac_availability_' ) . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $like_transient,
                $like_timeout
            )
        );
    }
}
