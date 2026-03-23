<?php

namespace RINAC\Ajax;

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
        // TODO Fase 4: integrar AvailabilityManager.
        $productId = 0;
        if ( isset( $_POST['product_id'] ) ) {
            $productId = absint( $_POST['product_id'] );
        } elseif ( isset( $_GET['product_id'] ) ) {
            $productId = absint( $_GET['product_id'] );
        } elseif ( isset( $_REQUEST['product_id'] ) ) {
            $productId = absint( $_REQUEST['product_id'] );
        }

        wp_send_json_success(
            array(
                'product_id'   => $productId,
                'availability' => array(),
                'hint'          => esc_html__( 'Availability stub (pendiente de implementar).', 'rinac' ),
            )
        );
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
        // TODO Fase 6/7: persistir booking y/o crear order meta.
        $productId = 0;
        if ( isset( $_POST['product_id'] ) ) {
            $productId = absint( $_POST['product_id'] );
        } elseif ( isset( $_GET['product_id'] ) ) {
            $productId = absint( $_GET['product_id'] );
        } elseif ( isset( $_REQUEST['product_id'] ) ) {
            $productId = absint( $_REQUEST['product_id'] );
        }

        wp_send_json_success(
            array(
                'product_id' => $productId,
                'status'     => 'ok',
                'request_id' => wp_generate_uuid4(),
                'hint'       => esc_html__( 'Create booking request stub (pendiente).', 'rinac' ),
            )
        );
    }

}

