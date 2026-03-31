<?php

namespace RINAC\Ajax;

/**
 * Handler centralizado de endpoints AJAX.
 */
class AjaxHandler {

    /**
     * Acción de nonce.
     *
     * @var string
     */
    private string $nonceAction = 'rinac_ajax';

    /**
     * Clave esperada para nonce.
     *
     * @var string
     */
    private string $nonceKey = 'nonce';

    /**
     * Registra endpoints AJAX.
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
     * Router principal del handler.
     *
     * @return void
     */
    public function handle(): void {
        $endpoint = $this->detectEndpoint();

        if ( '' === $endpoint ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Endpoint no informado.', 'rinac' ),
                ),
                400
            );
        }

        $this->verifyNonceOrFail( $endpoint );

        if ( ! $this->userAllowedToCallEndpoint( $endpoint ) ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'No tienes permisos para esta acción.', 'rinac' ),
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
     * Detecta el endpoint desde request o hook actual.
     *
     * @return string
     */
    private function detectEndpoint(): string {
        /** @noinspection PhpUndefinedVariableInspection */
        $post = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();
        /** @noinspection PhpUndefinedVariableInspection */
        $get = isset( $_GET ) && is_array( $_GET ) ? $_GET : array();

        $endpoint = '';

        if ( isset( $post['action'] ) ) {
            $endpoint = sanitize_text_field( wp_unslash( $post['action'] ) );
        } elseif ( isset( $get['action'] ) ) {
            $endpoint = sanitize_text_field( wp_unslash( $get['action'] ) );
        }

        if ( '' === $endpoint ) {
            $current_filter = current_filter();
            if ( is_string( $current_filter ) && '' !== $current_filter ) {
                $endpoint = str_replace( array( 'wp_ajax_', 'wp_ajax_nopriv_' ), '', $current_filter );
            }
        }

        return $endpoint;
    }

    /**
     * Verifica nonce y corta en error si no es válido.
     *
     * @param string $endpoint Endpoint actual.
     * @return void
     */
    private function verifyNonceOrFail( string $endpoint ): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $post = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();
        /** @noinspection PhpUndefinedVariableInspection */
        $get = isset( $_GET ) && is_array( $_GET ) ? $_GET : array();
        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $nonce = '';
        if ( isset( $post[ $this->nonceKey ] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $post[ $this->nonceKey ] ) );
        } elseif ( isset( $get[ $this->nonceKey ] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $get[ $this->nonceKey ] ) );
        } elseif ( isset( $request[ $this->nonceKey ] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $request[ $this->nonceKey ] ) );
        }

        if ( '' === $nonce || ! wp_verify_nonce( $nonce, $this->nonceAction ) ) {
            wp_send_json_error(
                array(
                    'message'  => esc_html__( 'Nonce inválido o expirado.', 'rinac' ),
                    'endpoint' => $endpoint,
                ),
                401
            );
        }
    }

    /**
     * Comprueba permisos de acceso al endpoint.
     *
     * @param string $endpoint Endpoint.
     * @return bool
     */
    private function userAllowedToCallEndpoint( string $endpoint ): bool {
        $read_only_endpoints = array(
            'rinac_get_availability',
            'rinac_get_calendar_events',
        );

        if ( in_array( $endpoint, $read_only_endpoints, true ) ) {
            if ( is_user_logged_in() ) {
                return current_user_can( 'read' );
            }

            return true;
        }

        if ( is_user_logged_in() ) {
            return current_user_can( 'read' );
        }

        return true;
    }

    /**
     * Endpoint de disponibilidad (base).
     *
     * @return void
     */
    private function handleGetAvailability(): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $product_id = isset( $request['product_id'] ) ? absint( $request['product_id'] ) : 0;
        $start = isset( $request['start'] ) ? sanitize_text_field( wp_unslash( (string) $request['start'] ) ) : '';
        $end = isset( $request['end'] ) ? sanitize_text_field( wp_unslash( (string) $request['end'] ) ) : '';
        $slot_id = isset( $request['slot_id'] ) ? absint( $request['slot_id'] ) : 0;

        if ( $product_id <= 0 ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Producto inválido.', 'rinac' ),
                ),
                400
            );
        }

        $availability_manager = new \RINAC\Calendar\AvailabilityManager();
        $availability = $availability_manager->getAvailability(
            $product_id,
            $start,
            $end,
            $slot_id > 0 ? $slot_id : null
        );

        $message = $availability['available'] ? esc_html__( 'Disponibilidad calculada.', 'rinac' ) : esc_html__( 'No hay disponibilidad para la consulta.', 'rinac' );

        wp_send_json_success(
            array(
                'endpoint' => 'rinac_get_availability',
                'product_id' => $product_id,
                'query' => array(
                    'start' => $start,
                    'end'   => $end,
                    'slot_id' => $slot_id > 0 ? $slot_id : null,
                ),
                'data' => $availability,
                'message' => $message,
            )
        );
    }

    /**
     * Endpoint de calendario global (base).
     *
     * @return void
     */
    private function handleGetCalendarEvents(): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $product_id = isset( $request['product_id'] ) ? absint( $request['product_id'] ) : 0;

        wp_send_json_success(
            array(
                'endpoint' => 'rinac_get_calendar_events',
                'product_id' => $product_id,
                'events' => array(),
                'message' => esc_html__( 'Endpoint base de eventos de calendario.', 'rinac' ),
            )
        );
    }

    /**
     * Endpoint de creación de solicitud de reserva (base).
     *
     * @return void
     */
    private function handleCreateBookingRequest(): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $product_id = isset( $request['product_id'] ) ? absint( $request['product_id'] ) : 0;
        $slot_id = isset( $request['slot_id'] ) ? absint( $request['slot_id'] ) : 0;

        if ( $product_id <= 0 ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Producto inválido.', 'rinac' ),
                ),
                400
            );
        }

        $wc_get_product_callable = 'wc_get_product';
        $product = $wc_get_product_callable( $product_id );
        if ( ! $product ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'No se ha encontrado el producto.', 'rinac' ),
                ),
                400
            );
        }

        $raw_participants = isset( $request['participants'] ) && is_array( $request['participants'] )
            ? $request['participants']
            : array();
        $raw_resources = isset( $request['resources'] ) && is_array( $request['resources'] )
            ? $request['resources']
            : array();

        $participants = array();
        foreach ( $raw_participants as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            $qty = isset( $item['qty'] ) ? absint( $item['qty'] ) : 0;
            if ( $id > 0 && $qty > 0 ) {
                $participants[] = array(
                    'id'  => $id,
                    'qty' => $qty,
                );
            }
        }

        $resources = array();
        foreach ( $raw_resources as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            $qty = isset( $item['qty'] ) ? absint( $item['qty'] ) : 0;
            if ( $id > 0 && $qty > 0 ) {
                $resources[] = array(
                    'id'  => $id,
                    'qty' => $qty,
                );
            }
        }

        $booking_manager = new \RINAC\Booking\BookingManager();
        $validation = $booking_manager->validateBookingRequest( $product, $participants, $resources );

        if ( is_wp_error( $validation ) ) {
            wp_send_json_error(
                array(
                    'endpoint' => 'rinac_create_booking_request',
                    'message'  => $validation->get_error_message(),
                    'errors'   => $validation->get_error_data( 'rinac_booking_validation_failed' )['errors'] ?? array(),
                ),
                400
            );
        }

        wp_send_json_success(
            array(
                'endpoint' => 'rinac_create_booking_request',
                'status' => 'ok',
                'request_id' => wp_generate_uuid4(),
                'payload' => array(
                    'product_id' => $product_id,
                    'slot_id' => $slot_id,
                    'participants' => $validation['participants'],
                    'resources'    => $validation['resources'],
                ),
                'message' => esc_html__( 'Solicitud de reserva validada (sin persistir).', 'rinac' ),
            )
        );
    }
}
