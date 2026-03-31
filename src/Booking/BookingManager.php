<?php

namespace RINAC\Booking;

use WP_Error;

/**
 * Gestión básica de reservas: validación de participantes y recursos.
 *
 * En esta versión inicial solo se encarga de:
 * - Validar que los IDs de participantes/recursos:
 *   - existen,
 *   - están activos,
 *   - están permitidos en el producto.
 * - Normalizar los datos recibidos en estructuras coherentes.
 *
 * La persistencia en `rinac_booking` y el enlace con pedidos WooCommerce
 * se añadirá en fases posteriores.
 */
class BookingManager {

    /**
     * Valida participantes y recursos para un producto reservado.
     *
     * @param mixed      $product      Instancia del producto (idealmente `\WC_Product` o `ReservaProduct`).
     * @param array      $participants Datos de participantes solicitados.
     *                                  Espera forma:
     *                                  [
     *                                      [ 'id' => int, 'qty' => int ],
     *                                      ...
     *                                  ]
     * @param array      $resources    Datos de recursos solicitados.
     *                                  Espera forma:
     *                                  [
     *                                      [ 'id' => int, 'qty' => int ],
     *                                      ...
     *                                  ]
     *
     * @return array|WP_Error
     *   - array con claves:
     *     - 'participants' => array normalizado
     *     - 'resources'    => array normalizado
     *   - o `WP_Error` con código/mensajes de validación.
     */
    public function validateBookingRequest( $product, array $participants, array $resources ) {
        $product_id = is_object( $product ) && method_exists( $product, 'get_id' )
            ? (int) $product->get_id()
            : 0;

        $allowed_participant_ids = $this->normalizeIdArray( get_post_meta( $product_id, '_rinac_allowed_participant_types', true ) );
        $allowed_resource_ids    = $this->normalizeIdArray( get_post_meta( $product_id, '_rinac_allowed_resources', true ) );

        $normalized_participants = array();
        $normalized_resources    = array();
        $errors                  = array();

        // Validación de participantes.
        foreach ( $participants as $item ) {
            $participant_id = isset( $item['id'] ) ? (int) $item['id'] : 0;
            $qty            = isset( $item['qty'] ) ? (int) $item['qty'] : 0;

            if ( $participant_id <= 0 || $qty <= 0 ) {
                continue;
            }

            if ( ! in_array( $participant_id, $allowed_participant_ids, true ) ) {
                $errors[] = sprintf(
                    /* translators: 1: participant id. */
                    esc_html__( 'Tipo de participante no permitido para el producto (ID %d).', 'rinac' ),
                    $participant_id
                );
                continue;
            }

            $post = get_post( $participant_id );
            if ( ! $post || 'rinac_participant' !== $post->post_type ) {
                $errors[] = sprintf(
                    /* translators: 1: participant id. */
                    esc_html__( 'Tipo de participante inválido (ID %d).', 'rinac' ),
                    $participant_id
                );
                continue;
            }

            $is_active = (int) get_post_meta( $participant_id, '_rinac_pt_is_active', true );
            if ( 1 !== $is_active ) {
                $errors[] = sprintf(
                    /* translators: 1: participant id. */
                    esc_html__( 'Tipo de participante inactivo (ID %d).', 'rinac' ),
                    $participant_id
                );
                continue;
            }

            $min_qty = (int) get_post_meta( $participant_id, '_rinac_pt_min_qty', true );
            $max_qty = (int) get_post_meta( $participant_id, '_rinac_pt_max_qty', true );

            if ( $min_qty > 0 && $qty < $min_qty ) {
                $errors[] = sprintf(
                    /* translators: 1: participant id, 2: minimum. */
                    esc_html__( 'Cantidad para tipo de participante %1$d por debajo del mínimo (%2$d).', 'rinac' ),
                    $participant_id,
                    $min_qty
                );
                continue;
            }

            if ( $max_qty > 0 && $qty > $max_qty ) {
                $errors[] = sprintf(
                    /* translators: 1: participant id, 2: maximum. */
                    esc_html__( 'Cantidad para tipo de participante %1$d por encima del máximo (%2$d).', 'rinac' ),
                    $participant_id,
                    $max_qty
                );
                continue;
            }

            $normalized_participants[] = array(
                'id'  => $participant_id,
                'qty' => $qty,
            );
        }

        // Validación de recursos.
        foreach ( $resources as $item ) {
            $resource_id = isset( $item['id'] ) ? (int) $item['id'] : 0;
            $qty         = isset( $item['qty'] ) ? (int) $item['qty'] : 0;

            if ( $resource_id <= 0 || $qty <= 0 ) {
                continue;
            }

            if ( ! in_array( $resource_id, $allowed_resource_ids, true ) ) {
                $errors[] = sprintf(
                    /* translators: 1: resource id. */
                    esc_html__( 'Recurso no permitido para el producto (ID %d).', 'rinac' ),
                    $resource_id
                );
                continue;
            }

            $post = get_post( $resource_id );
            if ( ! $post || 'rinac_resource' !== $post->post_type ) {
                $errors[] = sprintf(
                    /* translators: 1: resource id. */
                    esc_html__( 'Recurso inválido (ID %d).', 'rinac' ),
                    $resource_id
                );
                continue;
            }

            $is_active = (int) get_post_meta( $resource_id, '_rinac_resource_is_active', true );
            if ( 1 !== $is_active ) {
                $errors[] = sprintf(
                    /* translators: 1: resource id. */
                    esc_html__( 'Recurso inactivo (ID %d).', 'rinac' ),
                    $resource_id
                );
                continue;
            }

            $min_qty = (int) get_post_meta( $resource_id, '_rinac_resource_min_qty', true );
            $max_qty = (int) get_post_meta( $resource_id, '_rinac_resource_max_qty', true );

            if ( $min_qty > 0 && $qty < $min_qty ) {
                $errors[] = sprintf(
                    /* translators: 1: resource id, 2: minimum. */
                    esc_html__( 'Cantidad para recurso %1$d por debajo del mínimo (%2$d).', 'rinac' ),
                    $resource_id,
                    $min_qty
                );
                continue;
            }

            if ( $max_qty > 0 && $qty > $max_qty ) {
                $errors[] = sprintf(
                    /* translators: 1: resource id, 2: maximum. */
                    esc_html__( 'Cantidad para recurso %1$d por encima del máximo (%2$d).', 'rinac' ),
                    $resource_id,
                    $max_qty
                );
                continue;
            }

            $normalized_resources[] = array(
                'id'  => $resource_id,
                'qty' => $qty,
            );
        }

        if ( ! empty( $errors ) ) {
            return new WP_Error(
                'rinac_booking_validation_failed',
                esc_html__( 'La solicitud de reserva no cumple las reglas de negocio.', 'rinac' ),
                array(
                    'errors' => $errors,
                )
            );
        }

        return array(
            'participants' => $normalized_participants,
            'resources'    => $normalized_resources,
        );
    }

    /**
     * Normaliza un meta valor (string/array) a array de IDs.
     *
     * @param mixed $value Valor original.
     * @return array<int>
     */
    private function normalizeIdArray( $value ): array {
        if ( is_array( $value ) ) {
            $ids = array_map( 'absint', $value );
        } elseif ( is_string( $value ) && '' !== $value ) {
            $ids = array( absint( $value ) );
        } else {
            $ids = array();
        }

        $ids = array_filter(
            $ids,
            static function ( int $id ): bool {
                return $id > 0;
            }
        );

        return array_values( array_unique( $ids ) );
    }
}

