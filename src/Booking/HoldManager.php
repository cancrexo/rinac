<?php

namespace RINAC\Booking;

/**
 * Gestiona bloqueos temporales (quote/hold) para evitar sobreserva.
 */
class HoldManager {

    /**
     * TTL por defecto del hold en segundos (15 min).
     */
    private const DEFAULT_TTL = 900;

    /**
     * Crea un hold temporal y devuelve su token.
     *
     * @param array $payload Datos del hold.
     * @param int   $ttl_seconds TTL opcional.
     * @return array
     */
    public function createHold( array $payload, int $ttl_seconds = self::DEFAULT_TTL ): array {
        $product_id = isset( $payload['product_id'] ) ? absint( $payload['product_id'] ) : 0;
        if ( $product_id <= 0 ) {
            return array(
                'ok'      => false,
                'message' => esc_html__( 'No se puede crear el bloqueo: producto inválido.', 'rinac' ),
            );
        }

        $token = wp_generate_uuid4();
        $ttl = max( 60, $ttl_seconds );
        $expires_at = time() + $ttl;

        $data = array(
            'token'              => $token,
            'product_id'         => $product_id,
            'start_ts'           => isset( $payload['start_ts'] ) ? (int) $payload['start_ts'] : 0,
            'end_ts'             => isset( $payload['end_ts'] ) ? (int) $payload['end_ts'] : 0,
            'slot_id'            => isset( $payload['slot_id'] ) ? absint( $payload['slot_id'] ) : 0,
            'turno_id'           => isset( $payload['turno_id'] ) ? absint( $payload['turno_id'] ) : 0,
            'requested_capacity' => isset( $payload['requested_capacity'] ) ? max( 1, absint( $payload['requested_capacity'] ) ) : 1,
            'estimated_total'    => isset( $payload['estimated_total'] ) ? (float) $payload['estimated_total'] : 0.0,
            'created_at'         => time(),
            'expires_at'         => $expires_at,
        );

        set_transient( $this->buildHoldKey( $token ), $data, $ttl );
        $this->addTokenToProductIndex( $product_id, $token );

        return array(
            'ok'         => true,
            'token'      => $token,
            'expires_at' => $expires_at,
            'ttl'        => $ttl,
            'data'       => $data,
        );
    }

    /**
     * Suma unidades bloqueadas en holds activos para mismo producto/rango/contexto.
     *
     * @param int    $product_id Producto.
     * @param int    $start_ts Inicio.
     * @param int    $end_ts Fin.
     * @param int    $slot_id Slot.
     * @param int    $turno_id Turno.
     * @param string $exclude_token Token opcional a excluir.
     * @return int
     */
    public function getActiveHeldUnits(
        int $product_id,
        int $start_ts,
        int $end_ts,
        int $slot_id = 0,
        int $turno_id = 0,
        string $exclude_token = ''
    ): int {
        $tokens = $this->getProductIndexTokens( $product_id );
        if ( empty( $tokens ) ) {
            return 0;
        }

        $held_units = 0;
        $valid_tokens = array();

        foreach ( $tokens as $token ) {
            if ( ! is_string( $token ) || '' === $token ) {
                continue;
            }

            if ( '' !== $exclude_token && $exclude_token === $token ) {
                $valid_tokens[] = $token;
                continue;
            }

            $hold = get_transient( $this->buildHoldKey( $token ) );
            if ( ! is_array( $hold ) ) {
                continue;
            }

            $valid_tokens[] = $token;

            if ( $this->isHoldExpired( $hold ) ) {
                continue;
            }

            $hold_start = isset( $hold['start_ts'] ) ? (int) $hold['start_ts'] : 0;
            $hold_end = isset( $hold['end_ts'] ) ? (int) $hold['end_ts'] : 0;
            if ( $hold_start <= 0 || $hold_end <= 0 ) {
                continue;
            }

            // Solape temporal.
            if ( $hold_start >= $end_ts || $hold_end <= $start_ts ) {
                continue;
            }

            // Coincidencia de contexto slot/turno (si ambos lados informan).
            $hold_slot = isset( $hold['slot_id'] ) ? absint( $hold['slot_id'] ) : 0;
            $hold_turno = isset( $hold['turno_id'] ) ? absint( $hold['turno_id'] ) : 0;
            if ( $slot_id > 0 && $hold_slot > 0 && $slot_id !== $hold_slot ) {
                continue;
            }
            if ( $turno_id > 0 && $hold_turno > 0 && $turno_id !== $hold_turno ) {
                continue;
            }

            $held_units += isset( $hold['requested_capacity'] ) ? max( 1, absint( $hold['requested_capacity'] ) ) : 1;
        }

        // Limpieza de índice de tokens huérfanos/caducados.
        $this->setProductIndexTokens( $product_id, $valid_tokens );

        return $held_units;
    }

    /**
     * Valida que el token exista y corresponda a la solicitud.
     *
     * @param string $token Token.
     * @param array  $request_scope Scope esperado.
     * @return array
     */
    public function validateHoldToken( string $token, array $request_scope ): array {
        if ( '' === $token ) {
            return array(
                'ok'      => false,
                'message' => esc_html__( 'Falta hold_token para confirmar la reserva.', 'rinac' ),
            );
        }

        $hold = get_transient( $this->buildHoldKey( $token ) );
        if ( ! is_array( $hold ) ) {
            return array(
                'ok'      => false,
                'message' => esc_html__( 'El hold_token no existe o ya expiró.', 'rinac' ),
            );
        }

        if ( $this->isHoldExpired( $hold ) ) {
            delete_transient( $this->buildHoldKey( $token ) );
            return array(
                'ok'      => false,
                'message' => esc_html__( 'El hold_token ha expirado.', 'rinac' ),
            );
        }

        $expected_product = isset( $request_scope['product_id'] ) ? absint( $request_scope['product_id'] ) : 0;
        $expected_start = isset( $request_scope['start_ts'] ) ? (int) $request_scope['start_ts'] : 0;
        $expected_end = isset( $request_scope['end_ts'] ) ? (int) $request_scope['end_ts'] : 0;

        if ( $expected_product > 0 && $expected_product !== (int) $hold['product_id'] ) {
            return array(
                'ok'      => false,
                'message' => esc_html__( 'El hold_token no corresponde al producto.', 'rinac' ),
            );
        }

        if ( $expected_start > 0 && $expected_start !== (int) $hold['start_ts'] ) {
            return array(
                'ok'      => false,
                'message' => esc_html__( 'El hold_token no corresponde al inicio solicitado.', 'rinac' ),
            );
        }

        if ( $expected_end > 0 && $expected_end !== (int) $hold['end_ts'] ) {
            return array(
                'ok'      => false,
                'message' => esc_html__( 'El hold_token no corresponde al fin solicitado.', 'rinac' ),
            );
        }

        return array(
            'ok'   => true,
            'hold' => $hold,
        );
    }

    /**
     * Consume y elimina un hold al confirmar reserva.
     *
     * @param string $token Token.
     * @return void
     */
    public function consumeHold( string $token ): void {
        if ( '' === $token ) {
            return;
        }

        $hold = get_transient( $this->buildHoldKey( $token ) );
        if ( is_array( $hold ) && isset( $hold['product_id'] ) ) {
            $this->removeTokenFromProductIndex( absint( $hold['product_id'] ), $token );
        }

        delete_transient( $this->buildHoldKey( $token ) );
    }

    /**
     * Construye key de transient para hold.
     *
     * @param string $token Token.
     * @return string
     */
    private function buildHoldKey( string $token ): string {
        return 'rinac_hold_' . md5( $token );
    }

    /**
     * Key de índice de holds por producto.
     *
     * @param int $product_id Producto.
     * @return string
     */
    private function buildProductIndexKey( int $product_id ): string {
        return 'rinac_hold_idx_' . $product_id;
    }

    /**
     * Obtiene tokens indexados para un producto.
     *
     * @param int $product_id Producto.
     * @return array
     */
    private function getProductIndexTokens( int $product_id ): array {
        $tokens = get_transient( $this->buildProductIndexKey( $product_id ) );
        if ( ! is_array( $tokens ) ) {
            return array();
        }

        return array_values( array_unique( array_filter( $tokens, 'is_string' ) ) );
    }

    /**
     * Guarda tokens indexados para un producto.
     *
     * @param int   $product_id Producto.
     * @param array $tokens Tokens.
     * @return void
     */
    private function setProductIndexTokens( int $product_id, array $tokens ): void {
        set_transient(
            $this->buildProductIndexKey( $product_id ),
            array_values( array_unique( array_filter( $tokens, 'is_string' ) ) ),
            self::DEFAULT_TTL
        );
    }

    /**
     * Añade token al índice de producto.
     *
     * @param int    $product_id Producto.
     * @param string $token Token.
     * @return void
     */
    private function addTokenToProductIndex( int $product_id, string $token ): void {
        $tokens = $this->getProductIndexTokens( $product_id );
        $tokens[] = $token;
        $this->setProductIndexTokens( $product_id, $tokens );
    }

    /**
     * Elimina token del índice de producto.
     *
     * @param int    $product_id Producto.
     * @param string $token Token.
     * @return void
     */
    private function removeTokenFromProductIndex( int $product_id, string $token ): void {
        $tokens = $this->getProductIndexTokens( $product_id );
        $tokens = array_values(
            array_filter(
                $tokens,
                function ( string $item ) use ( $token ): bool {
                    return $item !== $token;
                }
            )
        );
        $this->setProductIndexTokens( $product_id, $tokens );
    }

    /**
     * Determina si un hold está expirado.
     *
     * @param array $hold Hold.
     * @return bool
     */
    private function isHoldExpired( array $hold ): bool {
        $expires_at = isset( $hold['expires_at'] ) ? (int) $hold['expires_at'] : 0;
        if ( $expires_at <= 0 ) {
            return true;
        }

        return time() >= $expires_at;
    }
}

