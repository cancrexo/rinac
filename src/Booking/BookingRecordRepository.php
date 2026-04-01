<?php

namespace RINAC\Booking;

use WP_Error;

/**
 * Repositorio único para lectura/escritura de `rinac_booking`.
 */
class BookingRecordRepository {

    /**
     * Crea una reserva con contrato canónico.
     *
     * @param array<string,mixed> $data
     * @return int|WP_Error
     */
    public function create( array $data ) {
        $payload = $this->normalizePayload( $data );

        $booking_id = wp_insert_post(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => $payload['post_status'],
                'post_title' => $payload['post_title'],
                'post_content' => '',
            ),
            true
        );

        if ( is_wp_error( $booking_id ) || ! is_numeric( $booking_id ) ) {
            return new WP_Error( 'rinac_booking_create_failed', esc_html__( 'No se pudo crear la reserva.', 'rinac' ) );
        }

        $booking_id = (int) $booking_id;
        $this->writeCanonicalMeta( $booking_id, $payload );

        return $booking_id;
    }

    /**
     * Actualiza metadatos canónicos de reserva.
     *
     * @param int $booking_id
     * @param array<string,mixed> $data
     * @return void
     */
    public function update( int $booking_id, array $data ): void {
        if ( $booking_id <= 0 ) {
            return;
        }
        $payload = $this->normalizePayload( $data );
        $this->writeCanonicalMeta( $booking_id, $payload );
    }

    /**
     * Busca reserva por order item id.
     *
     * @param int $order_item_id
     * @return int
     */
    public function findByOrderItemId( int $order_item_id ): int {
        return $this->findSingleByMeta( '_rinac_booking_order_item_id', $order_item_id );
    }

    /**
     * Busca reservas por order id.
     *
     * @param int $order_id
     * @return array<int>
     */
    public function findByOrderId( int $order_id ): array {
        if ( $order_id <= 0 ) {
            return array();
        }

        $query = new \WP_Query(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'publish', 'pending', 'private', 'draft' ),
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => '_rinac_booking_order_id',
                        'value' => $order_id,
                        'compare' => '=',
                        'type' => 'NUMERIC',
                    ),
                ),
            )
        );

        return array_map( 'intval', is_array( $query->posts ) ? $query->posts : array() );
    }

    /**
     * Busca reserva por hold token.
     *
     * @param string $token
     * @return int
     */
    public function findByHoldToken( string $token ): int {
        return $this->findSingleByMeta( '_rinac_hold_token', $token );
    }

    /**
     * Aplica estado interno y estado de post.
     *
     * @param int $booking_id
     * @param string $booking_status
     * @param string $post_status
     * @return void
     */
    public function setStatuses( int $booking_id, string $booking_status, string $post_status ): void {
        if ( $booking_id <= 0 ) {
            return;
        }
        update_post_meta( $booking_id, '_rinac_booking_status', sanitize_key( $booking_status ) );
        wp_update_post(
            array(
                'ID' => $booking_id,
                'post_status' => sanitize_key( $post_status ),
            )
        );
        $this->invalidateAvailabilityTransients();
    }

    /**
     * Normaliza payload al contrato canónico.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalizePayload( array $data ): array {
        $booking_status = isset( $data['booking_status'] ) ? sanitize_key( (string) $data['booking_status'] ) : 'hold';

        return array(
            'post_status' => isset( $data['post_status'] ) ? sanitize_key( (string) $data['post_status'] ) : 'pending',
            'post_title' => isset( $data['post_title'] ) ? sanitize_text_field( (string) $data['post_title'] ) : __( 'Reserva RINAC', 'rinac' ),
            'product_id' => isset( $data['product_id'] ) ? (int) $data['product_id'] : 0,
            'slot_id' => isset( $data['slot_id'] ) ? (int) $data['slot_id'] : 0,
            'order_id' => isset( $data['order_id'] ) ? (int) $data['order_id'] : 0,
            'order_item_id' => isset( $data['order_item_id'] ) ? (int) $data['order_item_id'] : 0,
            'start' => isset( $data['start'] ) ? sanitize_text_field( (string) $data['start'] ) : '',
            'end' => isset( $data['end'] ) ? sanitize_text_field( (string) $data['end'] ) : '',
            'equivalent_qty' => isset( $data['equivalent_qty'] ) ? max( 0.0, (float) $data['equivalent_qty'] ) : 0.0,
            'booking_status' => '' !== $booking_status ? $booking_status : 'hold',
            'hold_token' => isset( $data['hold_token'] ) ? sanitize_text_field( (string) $data['hold_token'] ) : '',
            'hold_expires_at' => isset( $data['hold_expires_at'] ) ? (int) $data['hold_expires_at'] : 0,
        );
    }

    /**
     * Escribe metadatos canónicos.
     *
     * @param int $booking_id
     * @param array<string,mixed> $payload
     * @return void
     */
    private function writeCanonicalMeta( int $booking_id, array $payload ): void {
        update_post_meta( $booking_id, '_rinac_booking_product_id', (int) $payload['product_id'] );
        update_post_meta( $booking_id, '_rinac_booking_slot_id', (int) $payload['slot_id'] );
        update_post_meta( $booking_id, '_rinac_booking_order_id', (int) $payload['order_id'] );
        update_post_meta( $booking_id, '_rinac_booking_order_item_id', (int) $payload['order_item_id'] );
        update_post_meta( $booking_id, '_rinac_booking_start', (string) $payload['start'] );
        update_post_meta( $booking_id, '_rinac_booking_end', (string) $payload['end'] );
        update_post_meta( $booking_id, '_rinac_booking_equivalent_qty', (float) $payload['equivalent_qty'] );
        update_post_meta( $booking_id, '_rinac_booking_status', (string) $payload['booking_status'] );

        if ( '' !== (string) $payload['hold_token'] ) {
            update_post_meta( $booking_id, '_rinac_hold_token', (string) $payload['hold_token'] );
        }
        if ( (int) $payload['hold_expires_at'] > 0 ) {
            update_post_meta( $booking_id, '_rinac_hold_expires_at', (int) $payload['hold_expires_at'] );
        }
    }

    /**
     * Busca un único ID por metakey.
     *
     * @param string $meta_key
     * @param mixed $meta_value
     * @return int
     */
    private function findSingleByMeta( string $meta_key, $meta_value ): int {
        if ( '' === $meta_key || '' === (string) $meta_value ) {
            return 0;
        }

        $query = new \WP_Query(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'publish', 'pending', 'private', 'draft' ),
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => $meta_key,
                        'value' => $meta_value,
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( empty( $query->posts ) ) {
            return 0;
        }

        return (int) $query->posts[0];
    }

    /**
     * Invalida transients de disponibilidad.
     *
     * @return void
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
