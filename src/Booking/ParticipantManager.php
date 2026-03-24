<?php

namespace RINAC\Booking;

/**
 * Gestiona validación y totalizadores de participantes.
 */
class ParticipantManager {

    /**
     * Punto de entrada para hooks futuros.
     *
     * @return void
     */
    public function register(): void {
        // Sin hooks en este paso. Se integrará con flujo checkout en pasos posteriores.
    }

    /**
     * Normaliza participantes enviados desde request.
     *
     * Formato esperado:
     * - array( array( 'participant_type_id' => 123, 'qty' => 2 ), ... )
     *
     * @param mixed $participants_raw Datos crudos.
     * @return array
     */
    public function normalizeParticipants( $participants_raw ): array {
        if ( ! is_array( $participants_raw ) ) {
            return array();
        }

        $normalized = array();

        foreach ( $participants_raw as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $participant_type_id = isset( $item['participant_type_id'] ) ? absint( $item['participant_type_id'] ) : 0;
            $qty = isset( $item['qty'] ) ? absint( $item['qty'] ) : 0;

            if ( $participant_type_id <= 0 || $qty <= 0 ) {
                continue;
            }

            $normalized[] = array(
                'participant_type_id' => $participant_type_id,
                'qty'                 => $qty,
            );
        }

        return $normalized;
    }

    /**
     * Calcula unidades de capacidad consumidas por participantes.
     *
     * @param array $participants Participantes normalizados.
     * @return float
     */
    public function calculateCapacityUnits( array $participants ): float {
        $units = 0.0;

        foreach ( $participants as $participant ) {
            $participant_type_id = isset( $participant['participant_type_id'] ) ? absint( $participant['participant_type_id'] ) : 0;
            $qty = isset( $participant['qty'] ) ? absint( $participant['qty'] ) : 0;

            if ( $participant_type_id <= 0 || $qty <= 0 ) {
                continue;
            }

            $fraction = (float) get_post_meta( $participant_type_id, '_rinac_pt_capacity_fraction', true );
            if ( $fraction <= 0 ) {
                $fraction = 1.0;
            }

            $units += ( $fraction * $qty );
        }

        return max( 0.0, $units );
    }

    /**
     * Calcula coste adicional por tipos de participantes.
     *
     * @param array $participants Participantes normalizados.
     * @return float
     */
    public function calculateParticipantsExtra( array $participants ): float {
        $extra = 0.0;

        foreach ( $participants as $participant ) {
            $participant_type_id = isset( $participant['participant_type_id'] ) ? absint( $participant['participant_type_id'] ) : 0;
            $qty = isset( $participant['qty'] ) ? absint( $participant['qty'] ) : 0;

            if ( $participant_type_id <= 0 || $qty <= 0 ) {
                continue;
            }

            $price_type = (string) get_post_meta( $participant_type_id, '_rinac_pt_price_type', true );
            $price_value = (float) get_post_meta( $participant_type_id, '_rinac_pt_price_value', true );

            if ( 'free' === $price_type ) {
                continue;
            }

            // En este paso base tratamos percent_base como importe fijo temporal.
            $extra += max( 0.0, $price_value ) * $qty;
        }

        return $extra;
    }
}

