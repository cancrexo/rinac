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
     * @param array      $context      Contexto adicional de cálculo.
     *                                  Claves opcionales:
     *                                  - 'days' => int
     *                                  - 'nights' => int
     *
     * @return array|WP_Error
     *   - array con claves:
     *     - 'participants' => array normalizado
     *     - 'resources'    => array normalizado
     *     - 'pricing'      => resumen de precio preliminar
     *     - 'capacity'     => resumen de capacidad equivalente
     *   - o `WP_Error` con código/mensajes de validación.
     */
    public function validateBookingRequest( $product, array $participants, array $resources, array $context = array() ) {
        $product_id = is_object( $product ) && method_exists( $product, 'get_id' )
            ? (int) $product->get_id()
            : 0;

        $allowed_participant_ids = $this->normalizeIdArray( get_post_meta( $product_id, '_rinac_allowed_participant_types', true ) );
        $allowed_resource_ids    = $this->normalizeIdArray( get_post_meta( $product_id, '_rinac_allowed_resources', true ) );

        $normalized_participants = array();
        $normalized_resources    = array();
        $errors                  = array();
        $participants_units      = 0;
        $capacity_equivalent     = 0.0;
        $participants_subtotal   = 0.0;
        $resources_subtotal      = 0.0;

        $days = isset( $context['days'] ) ? max( 1, (int) $context['days'] ) : 1;
        $nights = isset( $context['nights'] ) ? max( 1, (int) $context['nights'] ) : 1;
        $start = isset( $context['start'] ) ? (string) $context['start'] : '';
        $end = isset( $context['end'] ) ? (string) $context['end'] : '';
        $slot_id = isset( $context['slot_id'] ) ? (int) $context['slot_id'] : 0;
        $booking_mode = (string) get_post_meta( $product_id, 'rinac_booking_mode', true );
        if ( '' === $booking_mode ) {
            $booking_mode = 'date';
        }
        $base_capacity = (int) get_post_meta( $product_id, '_rinac_base_capacity', true );
        $capacity_total_max = (int) get_post_meta( $product_id, '_rinac_capacity_total_max', true );
        $capacity_min_booking = (float) get_post_meta( $product_id, '_rinac_capacity_min_booking', true );
        $effective_global_capacity = $this->calculateEffectiveGlobalCapacity( $base_capacity, $capacity_total_max );

        // Validación de participantes.
        foreach ( $participants as $item ) {
            $participant_id = isset( $item['id'] ) ? (int) $item['id'] : 0;
            $qty            = isset( $item['qty'] ) ? (int) $item['qty'] : 0;

            if ( $participant_id <= 0 || $qty <= 0 ) {
                continue;
            }

            if ( ! in_array( $participant_id, $allowed_participant_ids, true ) ) {
                $this->addError( $errors, 'participant_not_allowed', sprintf(
                    /* translators: 1: participant id. */
                    esc_html__( 'Tipo de participante no permitido para el producto (ID %d).', 'rinac' ),
                    $participant_id
                ), array( 'id' => $participant_id ) );
                continue;
            }

            $post = get_post( $participant_id );
            if ( ! $post || 'rinac_participant' !== $post->post_type ) {
                $this->addError( $errors, 'participant_invalid', sprintf(
                    /* translators: 1: participant id. */
                    esc_html__( 'Tipo de participante inválido (ID %d).', 'rinac' ),
                    $participant_id
                ), array( 'id' => $participant_id ) );
                continue;
            }

            $is_active = (int) get_post_meta( $participant_id, '_rinac_pt_is_active', true );
            if ( 1 !== $is_active ) {
                $this->addError( $errors, 'participant_inactive', sprintf(
                    /* translators: 1: participant id. */
                    esc_html__( 'Tipo de participante inactivo (ID %d).', 'rinac' ),
                    $participant_id
                ), array( 'id' => $participant_id ) );
                continue;
            }

            $min_qty = (int) get_post_meta( $participant_id, '_rinac_pt_min_qty', true );
            $max_qty = (int) get_post_meta( $participant_id, '_rinac_pt_max_qty', true );

            if ( $min_qty > 0 && $qty < $min_qty ) {
                $this->addError( $errors, 'participant_below_min', sprintf(
                    /* translators: 1: participant id, 2: minimum. */
                    esc_html__( 'Cantidad para tipo de participante %1$d por debajo del mínimo (%2$d).', 'rinac' ),
                    $participant_id,
                    $min_qty
                ), array( 'id' => $participant_id, 'qty' => $qty, 'min_qty' => $min_qty ) );
                continue;
            }

            if ( $max_qty > 0 && $qty > $max_qty ) {
                $this->addError( $errors, 'participant_above_max', sprintf(
                    /* translators: 1: participant id, 2: maximum. */
                    esc_html__( 'Cantidad para tipo de participante %1$d por encima del máximo (%2$d).', 'rinac' ),
                    $participant_id,
                    $max_qty
                ), array( 'id' => $participant_id, 'qty' => $qty, 'max_qty' => $max_qty ) );
                continue;
            }

            $price_type = (string) get_post_meta( $participant_id, '_rinac_pt_price_type', true );
            $price_value = (float) get_post_meta( $participant_id, '_rinac_pt_price_value', true );
            $price_value = max( 0.0, $price_value );
            if ( '' === $price_type ) {
                $price_type = 'free';
            }
            $line_total = $this->calculateParticipantLineTotal( $price_type, $price_value, $qty );

            $capacity_fraction = (float) get_post_meta( $participant_id, '_rinac_pt_capacity_fraction', true );
            if ( $capacity_fraction <= 0 ) {
                $capacity_fraction = 1.0;
            }

            $normalized_participants[] = array(
                'id'                => $participant_id,
                'qty'               => $qty,
                'price_type'        => $price_type,
                'price_value'       => $price_value,
                'line_total'        => $line_total,
                'capacity_fraction' => $capacity_fraction,
            );

            $participants_units += $qty;
            $capacity_equivalent += $qty * $capacity_fraction;
            $participants_subtotal += $line_total;
        }

        // Validación de recursos.
        foreach ( $resources as $item ) {
            $resource_id = isset( $item['id'] ) ? (int) $item['id'] : 0;
            $qty         = isset( $item['qty'] ) ? (int) $item['qty'] : 0;

            if ( $resource_id <= 0 || $qty <= 0 ) {
                continue;
            }

            if ( ! in_array( $resource_id, $allowed_resource_ids, true ) ) {
                $this->addError( $errors, 'resource_not_allowed', sprintf(
                    /* translators: 1: resource id. */
                    esc_html__( 'Recurso no permitido para el producto (ID %d).', 'rinac' ),
                    $resource_id
                ), array( 'id' => $resource_id ) );
                continue;
            }

            $post = get_post( $resource_id );
            if ( ! $post || 'rinac_resource' !== $post->post_type ) {
                $this->addError( $errors, 'resource_invalid', sprintf(
                    /* translators: 1: resource id. */
                    esc_html__( 'Recurso inválido (ID %d).', 'rinac' ),
                    $resource_id
                ), array( 'id' => $resource_id ) );
                continue;
            }

            $is_active = (int) get_post_meta( $resource_id, '_rinac_resource_is_active', true );
            if ( 1 !== $is_active ) {
                $this->addError( $errors, 'resource_inactive', sprintf(
                    /* translators: 1: resource id. */
                    esc_html__( 'Recurso inactivo (ID %d).', 'rinac' ),
                    $resource_id
                ), array( 'id' => $resource_id ) );
                continue;
            }

            $min_qty = (int) get_post_meta( $resource_id, '_rinac_resource_min_qty', true );
            $max_qty = (int) get_post_meta( $resource_id, '_rinac_resource_max_qty', true );

            if ( $min_qty > 0 && $qty < $min_qty ) {
                $this->addError( $errors, 'resource_below_min', sprintf(
                    /* translators: 1: resource id, 2: minimum. */
                    esc_html__( 'Cantidad para recurso %1$d por debajo del mínimo (%2$d).', 'rinac' ),
                    $resource_id,
                    $min_qty
                ), array( 'id' => $resource_id, 'qty' => $qty, 'min_qty' => $min_qty ) );
                continue;
            }

            if ( $max_qty > 0 && $qty > $max_qty ) {
                $this->addError( $errors, 'resource_above_max', sprintf(
                    /* translators: 1: resource id, 2: maximum. */
                    esc_html__( 'Cantidad para recurso %1$d por encima del máximo (%2$d).', 'rinac' ),
                    $resource_id,
                    $max_qty
                ), array( 'id' => $resource_id, 'qty' => $qty, 'max_qty' => $max_qty ) );
                continue;
            }

            $resource_type = (string) get_post_meta( $resource_id, '_rinac_resource_type', true );
            if ( '' === $resource_type ) {
                $resource_type = 'addon';
            }
            if ( ! $this->isResourceAllowedByBookingMode( $resource_type, $booking_mode ) ) {
                $this->addError(
                    $errors,
                    'resource_mode_incompatible',
                    sprintf(
                        /* translators: 1: resource id, 2: booking mode. */
                        esc_html__( 'Recurso %1$d incompatible con el modo de reserva "%2$s".', 'rinac' ),
                        $resource_id,
                        $booking_mode
                    ),
                    array(
                        'id' => $resource_id,
                        'resource_type' => $resource_type,
                        'booking_mode' => $booking_mode,
                    )
                );
                continue;
            }

            $price_policy = (string) get_post_meta( $resource_id, '_rinac_resource_price_policy', true );
            $price_value = (float) get_post_meta( $resource_id, '_rinac_resource_price_value', true );
            $price_value = max( 0.0, $price_value );
            if ( '' === $price_policy ) {
                $price_policy = 'none';
            }
            $line_total = $this->calculateResourceLineTotal(
                $price_policy,
                $price_value,
                $qty,
                $participants_units,
                $days,
                $nights
            );

            $normalized_resources[] = array(
                'id'           => $resource_id,
                'qty'          => $qty,
                'resource_type'=> $resource_type,
                'price_policy' => $price_policy,
                'price_value'  => $price_value,
                'line_total'   => $line_total,
            );

            $resources_subtotal += $line_total;
        }

        $remaining_capacity_before = (float) $effective_global_capacity;
        if ( '' !== $start && '' !== $end && class_exists( '\RINAC\Calendar\AvailabilityManager' ) ) {
            $availability_manager = new \RINAC\Calendar\AvailabilityManager();
            $availability = $availability_manager->getAvailability( $product_id, $start, $end, $slot_id > 0 ? $slot_id : null );
            if ( isset( $availability['remaining_capacity'] ) && is_numeric( $availability['remaining_capacity'] ) ) {
                $remaining_capacity_before = (float) $availability['remaining_capacity'];
            }
            if ( $slot_id > 0 && isset( $availability['slots'] ) && is_array( $availability['slots'] ) ) {
                foreach ( $availability['slots'] as $slot_info ) {
                    if ( isset( $slot_info['slot_id'] ) && (int) $slot_info['slot_id'] === $slot_id ) {
                        if ( isset( $slot_info['remaining_capacity'] ) && is_numeric( $slot_info['remaining_capacity'] ) ) {
                            $remaining_capacity_before = (float) $slot_info['remaining_capacity'];
                        }
                        break;
                    }
                }
            }
        }

        if ( $effective_global_capacity <= 0 ) {
            $this->addError(
                $errors,
                'capacity_not_configured',
                esc_html__( 'El producto no tiene capacidad global configurada.', 'rinac' ),
                array(
                    'effective_global_capacity' => (float) $effective_global_capacity,
                )
            );
        }

        if ( $capacity_equivalent > (float) $effective_global_capacity && $effective_global_capacity > 0 ) {
            $this->addError(
                $errors,
                'capacity_exceeded_total',
                esc_html__( 'La capacidad solicitada supera la capacidad global del producto.', 'rinac' ),
                array(
                    'requested_equivalent' => round( $capacity_equivalent, 2 ),
                    'effective_global_capacity' => (float) $effective_global_capacity,
                )
            );
        }

        if ( $capacity_equivalent > $remaining_capacity_before ) {
            $this->addError(
                $errors,
                'insufficient_capacity',
                esc_html__( 'Capacidad insuficiente para la selección solicitada.', 'rinac' ),
                array(
                    'requested_equivalent' => round( $capacity_equivalent, 2 ),
                    'remaining_capacity' => round( $remaining_capacity_before, 2 ),
                )
            );
        }

        $remaining_capacity_after = max( 0.0, $remaining_capacity_before - $capacity_equivalent );
        if ( $capacity_min_booking > 0 && $remaining_capacity_after < $capacity_min_booking ) {
            $this->addError(
                $errors,
                'capacity_min_booking_violation',
                esc_html__( 'La reserva deja una capacidad restante inferior al mínimo permitido.', 'rinac' ),
                array(
                    'remaining_after' => round( $remaining_capacity_after, 2 ),
                    'capacity_min_booking' => round( $capacity_min_booking, 2 ),
                )
            );
        }

        if ( ! empty( $errors ) ) {
            return new WP_Error(
                'rinac_booking_validation_failed',
                esc_html__( 'La solicitud de reserva no cumple las reglas de negocio.', 'rinac' ),
                array(
                    'errors' => $errors,
                    'error_messages' => $this->errorMessagesFromDetails( $errors ),
                )
            );
        }

        return array(
            'participants' => $normalized_participants,
            'resources'    => $normalized_resources,
            'pricing'      => array(
                'subtotal_participants' => round( $participants_subtotal, 2 ),
                'subtotal_resources'    => round( $resources_subtotal, 2 ),
                'total_estimated'       => round( $participants_subtotal + $resources_subtotal, 2 ),
            ),
            'capacity'     => array(
                'participants_units' => $participants_units,
                'equivalent_total'   => round( $capacity_equivalent, 2 ),
                'effective_global_capacity' => (float) $effective_global_capacity,
                'remaining_capacity_before' => round( $remaining_capacity_before, 2 ),
                'remaining_capacity_after'  => round( $remaining_capacity_after, 2 ),
                'capacity_min_booking'      => round( $capacity_min_booking, 2 ),
                'booking_mode'              => $booking_mode,
            ),
        );
    }

    /**
     * Capacidad efectiva global (misma regla que AvailabilityManager).
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
     * Regla base: recursos de tipo "unit" se permiten solo en modo "unidad_rango".
     */
    private function isResourceAllowedByBookingMode( string $resource_type, string $booking_mode ): bool {
        if ( 'unit' !== $resource_type ) {
            return true;
        }
        return 'unidad_rango' === $booking_mode;
    }

    /**
     * Añade error estructurado de negocio.
     *
     * @param array<int,array<string,mixed>> $errors
     * @param string $code
     * @param string $message
     * @param array<string,mixed> $context
     * @return void
     */
    private function addError( array &$errors, string $code, string $message, array $context = array() ): void {
        $errors[] = array(
            'code' => $code,
            'message' => $message,
            'context' => $context,
        );
    }

    /**
     * Compatibilidad con consumidores antiguos de errores (solo string).
     *
     * @param array<int,array<string,mixed>> $errors
     * @return array<int,string>
     */
    private function errorMessagesFromDetails( array $errors ): array {
        $messages = array();
        foreach ( $errors as $error ) {
            if ( isset( $error['message'] ) && is_string( $error['message'] ) ) {
                $messages[] = $error['message'];
            }
        }
        return $messages;
    }

    /**
     * Cálculo de línea por participante.
     *
     * @param string $price_type  Tipo de precio (`free`, `fixed`).
     * @param float  $price_value Valor unitario.
     * @param int    $qty         Cantidad.
     * @return float
     */
    private function calculateParticipantLineTotal( string $price_type, float $price_value, int $qty ): float {
        if ( $qty <= 0 ) {
            return 0.0;
        }

        if ( 'fixed' === $price_type ) {
            return $price_value * $qty;
        }

        return 0.0;
    }

    /**
     * Cálculo de línea por recurso según política.
     *
     * @param string $price_policy       Política (`none`, `fixed`, `per_person`, `per_day`, `per_night`).
     * @param float  $price_value        Valor unitario.
     * @param int    $qty                Cantidad de recurso.
     * @param int    $participants_units Número total de participantes (unidades).
     * @param int    $days               Días (mínimo 1).
     * @param int    $nights             Noches (mínimo 1).
     * @return float
     */
    private function calculateResourceLineTotal(
        string $price_policy,
        float $price_value,
        int $qty,
        int $participants_units,
        int $days,
        int $nights
    ): float {
        if ( $qty <= 0 ) {
            return 0.0;
        }

        switch ( $price_policy ) {
            case 'fixed':
                return $price_value * $qty;
            case 'per_person':
                return $price_value * $qty * max( 1, $participants_units );
            case 'per_day':
                return $price_value * $qty * max( 1, $days );
            case 'per_night':
                return $price_value * $qty * max( 1, $nights );
            case 'none':
            default:
                return 0.0;
        }
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

