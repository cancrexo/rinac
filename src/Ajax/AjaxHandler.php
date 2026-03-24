<?php

namespace RINAC\Ajax;

use RINAC\Booking\ParticipantManager;
use RINAC\Booking\ResourceManager;
use RINAC\Calendar\AvailabilityManager;

/**
 * @var array<mixed> $_POST
 * @var array<mixed> $_GET
 * @var array<mixed> $_REQUEST
 */

/**
 * Handler centralizado de endpoints AJAX.
 *
 * Regla: un único enruta por `action` (nombre del endpoint).
 */
class AjaxHandler {

    /**
     * Nonce action (para `wp_verify_nonce`).
     *
     * @var string
     */
    private string $nonceAction = 'rinac_ajax';

    /**
     * Nonce key esperada en request.
     *
     * @var string
     */
    private string $nonceKey = 'nonce';

    /**
     * Registra los endpoints AJAX.
     *
     * @return void
     */
    public function register(): void {
        $endpoints = array(
            'rinac_get_availability',
            'rinac_get_calendar_events',
            'rinac_create_booking_request',
        );

        foreach ( $endpoints as $endpoint ) {
            add_action( 'wp_ajax_' . $endpoint, array( $this, 'handle' ) );
            add_action( 'wp_ajax_nopriv_' . $endpoint, array( $this, 'handle' ) );
        }
    }

    /**
     * Entry point del handler.
     *
     * @return void
     */
    public function handle(): void {
        $endpoint = '';
        if ( isset( $_POST['action'] ) ) {
            $endpoint = sanitize_text_field( wp_unslash( $_POST['action'] ) );
        } elseif ( isset( $_GET['action'] ) ) {
            $endpoint = sanitize_text_field( wp_unslash( $_GET['action'] ) );
        }

        // En admin-ajax.php, `action` suele venir del parámetro `action` o del nombre del hook.
        // Si no está presente, intentamos inferir desde el hook actual (fallback).
        if ( '' === $endpoint ) {
            if ( isset( $_POST['endpoint'] ) ) {
                $endpoint = sanitize_text_field( wp_unslash( $_POST['endpoint'] ) );
            } elseif ( isset( $_GET['endpoint'] ) ) {
                $endpoint = sanitize_text_field( wp_unslash( $_GET['endpoint'] ) );
            }
        }

        $this->verifyNonceOrFail( $endpoint );

        if ( ! $this->userAllowedToCallEndpoint( $endpoint ) ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'No tienes permisos para realizar esta acción.', 'rinac' ),
                ),
                403
            );
        }

        switch ( $endpoint ) {
            case 'rinac_get_availability':
                $this->handleGetAvailability();
                break;

            case 'rinac_get_calendar_events':
                $this->handleGetCalendarEvents();
                break;

            case 'rinac_create_booking_request':
                $this->handleCreateBookingRequest();
                break;

            default:
                wp_send_json_error(
                    array(
                        'message' => esc_html__( 'Endpoint no reconocido.', 'rinac' ),
                    ),
                    400
                );
        }
    }

    /**
     * Comprueba nonce.
     *
     * @param string $endpoint Endpoint.
     * @return void
     */
    private function verifyNonceOrFail( string $endpoint ): void {
        $nonce = '';

        /** @noinspection PhpUndefinedVariableInspection */
        $post = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();

        /** @noinspection PhpUndefinedVariableInspection */
        $get = isset( $_GET ) && is_array( $_GET ) ? $_GET : array();

        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        if ( isset( $post[ $this->nonceKey ] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $post[ $this->nonceKey ] ) );
        } elseif ( isset( $get[ $this->nonceKey ] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $get[ $this->nonceKey ] ) );
        } elseif ( isset( $request[ $this->nonceKey ] ) ) {
            // fallback por si el cliente envía nonce por otro método.
            $nonce = sanitize_text_field( wp_unslash( $request[ $this->nonceKey ] ) );
        }

        if ( '' === $nonce || ! wp_verify_nonce( $nonce, $this->nonceAction ) ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Nonce inválido o expirado.', 'rinac' ),
                    'endpoint' => $endpoint,
                ),
                401
            );
        }
    }

    /**
     * Capability check (no bloquea anónimos si el endpoint es de lectura).
     *
     * @param string $endpoint Endpoint.
     * @return bool
     */
    private function userAllowedToCallEndpoint( string $endpoint ): bool {
        $readOnly = in_array(
            $endpoint,
            array( 'rinac_get_availability', 'rinac_get_calendar_events' ),
            true
        );

        if ( $readOnly ) {
            // Para anónimos permitimos lectura siempre que el nonce sea válido.
            if ( is_user_logged_in() ) {
                return current_user_can( 'read' );
            }

            return true;
        }

        // Para acciones que "crean" (booking request) permitimos acceso si:
        // - usuario logueado y con capacidad mínima, o
        // - anónimo (frontend) pero con nonce válido.
        if ( is_user_logged_in() ) {
            return current_user_can( 'read' );
        }

        return true;
    }

    /**
     * Availability (stub).
     *
     * @return void
     */
    private function handleGetAvailability(): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $post = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();

        /** @noinspection PhpUndefinedVariableInspection */
        $get = isset( $_GET ) && is_array( $_GET ) ? $_GET : array();

        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $productId = 0;
        if ( isset( $post['product_id'] ) ) {
            $productId = absint( $post['product_id'] );
        } elseif ( isset( $get['product_id'] ) ) {
            $productId = absint( $get['product_id'] );
        } elseif ( isset( $request['product_id'] ) ) {
            $productId = absint( $request['product_id'] );
        }

        $startRaw = '';
        if ( isset( $post['start'] ) ) {
            $startRaw = sanitize_text_field( wp_unslash( $post['start'] ) );
        } elseif ( isset( $get['start'] ) ) {
            $startRaw = sanitize_text_field( wp_unslash( $get['start'] ) );
        } elseif ( isset( $request['start'] ) ) {
            $startRaw = sanitize_text_field( wp_unslash( $request['start'] ) );
        }

        $endRaw = '';
        if ( isset( $post['end'] ) ) {
            $endRaw = sanitize_text_field( wp_unslash( $post['end'] ) );
        } elseif ( isset( $get['end'] ) ) {
            $endRaw = sanitize_text_field( wp_unslash( $get['end'] ) );
        } elseif ( isset( $request['end'] ) ) {
            $endRaw = sanitize_text_field( wp_unslash( $request['end'] ) );
        }

        $startTs = $this->normalizeDateToTimestamp( $startRaw, strtotime( 'today' ) ?: time() );
        $endTs = $this->normalizeDateToTimestamp( $endRaw, strtotime( '+1 day', $startTs ) ?: ( $startTs + DAY_IN_SECONDS ) );

        $context = array(
            'slot_id'            => isset( $request['slot_id'] ) ? absint( $request['slot_id'] ) : 0,
            'turno_id'           => isset( $request['turno_id'] ) ? absint( $request['turno_id'] ) : 0,
            'requested_capacity' => isset( $request['requested_capacity'] ) ? absint( $request['requested_capacity'] ) : 1,
        );

        $availability = ( new AvailabilityManager() )->getAvailability(
            $productId,
            $startTs,
            $endTs,
            $context
        );

        wp_send_json_success(
            array(
                'product_id'   => $productId,
                'availability' => $availability,
            )
        );
    }

    /**
     * Convierte fecha recibida a timestamp con fallback.
     *
     * @param string $raw Valor original.
     * @param int    $fallback Fallback.
     * @return int
     */
    private function normalizeDateToTimestamp( string $raw, int $fallback ): int {
        if ( '' === $raw ) {
            return $fallback;
        }

        if ( 1 === preg_match( '/^\d+$/', $raw ) ) {
            $parsedInt = (int) $raw;
            if ( $parsedInt > 0 ) {
                return $parsedInt;
            }
        }

        $parsed = strtotime( $raw );
        if ( false === $parsed ) {
            return $fallback;
        }

        return (int) $parsed;
    }

    /**
     * Calendar events (stub).
     *
     * @return void
     */
    private function handleGetCalendarEvents(): void {
        // TODO Fase 5: integrar datos reales.
        $events = array();

        wp_send_json_success(
            array(
                'events' => $events,
                'hint'   => esc_html__( 'Events stub (pendiente de implementar).', 'rinac' ),
            )
        );
    }

    /**
     * Crear petición de reserva (stub).
     *
     * @return void
     */
    private function handleCreateBookingRequest(): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $post = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();

        /** @noinspection PhpUndefinedVariableInspection */
        $get = isset( $_GET ) && is_array( $_GET ) ? $_GET : array();

        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $productId = 0;
        if ( isset( $post['product_id'] ) ) {
            $productId = absint( $post['product_id'] );
        } elseif ( isset( $get['product_id'] ) ) {
            $productId = absint( $get['product_id'] );
        } elseif ( isset( $request['product_id'] ) ) {
            $productId = absint( $request['product_id'] );
        }

        if ( $productId <= 0 ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Producto inválido.', 'rinac' ),
                ),
                400
            );
        }

        $participantManager = new ParticipantManager();
        $resourceManager = new ResourceManager();

        $participantsRaw = $this->extractStructuredArray( $request, 'participants' );
        $resourcesRaw = $this->extractStructuredArray( $request, 'resources' );

        $participants = $participantManager->normalizeParticipants( $participantsRaw );
        $resources = $resourceManager->normalizeResources( $resourcesRaw );

        if ( ! $resourceManager->validateResourcesForProduct( $productId, $resources ) ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Uno o más recursos no están permitidos para este producto.', 'rinac' ),
                ),
                400
            );
        }

        $participants_capacity_units = $participantManager->calculateCapacityUnits( $participants );
        $participants_extra = $participantManager->calculateParticipantsExtra( $participants );
        $resources_extra = $resourceManager->calculateResourcesExtra( $resources );

        $base_price = (float) get_post_meta( $productId, '_price', true );
        $estimated_total = max( 0.0, $base_price + $participants_extra + $resources_extra );

        wp_send_json_success(
            array(
                'product_id'                  => $productId,
                'status'                      => 'ok',
                'request_id'                  => wp_generate_uuid4(),
                'participants'                => $participants,
                'resources'                   => $resources,
                'participants_capacity_units' => $participants_capacity_units,
                'participants_extra'          => $participants_extra,
                'resources_extra'             => $resources_extra,
                'estimated_total'             => $estimated_total,
                'hint'                        => esc_html__( 'Solicitud validada (persistencia en pasos posteriores).', 'rinac' ),
            )
        );
    }

    /**
     * Extrae un array estructurado desde request (array o JSON).
     *
     * @param array  $request Datos request.
     * @param string $key Clave.
     * @return array
     */
    private function extractStructuredArray( array $request, string $key ): array {
        if ( ! isset( $request[ $key ] ) ) {
            return array();
        }

        $value = $request[ $key ];
        if ( is_array( $value ) ) {
            return $value;
        }

        if ( is_string( $value ) ) {
            $decoded = json_decode( wp_unslash( $value ), true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return array();
    }

}

