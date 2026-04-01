<?php

namespace RINAC\Ajax;

/**
 * Handler centralizado de endpoints AJAX.
 */
class AjaxHandler {
    private const API_VERSION = '1.0.0';

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
            'rinac_quote_booking',
            'rinac_create_booking_request',
            'rinac_get_allowed_participants',
            'rinac_get_allowed_resources',
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
            case 'rinac_quote_booking':
                $this->handleQuoteBooking();
                break;
            case 'rinac_create_booking_request':
                $this->handleCreateBookingRequest();
                break;
            case 'rinac_get_allowed_participants':
                $this->handleGetAllowedParticipants();
                break;
            case 'rinac_get_allowed_resources':
                $this->handleGetAllowedResources();
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
            'rinac_get_allowed_participants',
            'rinac_get_allowed_resources',
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
            $this->sendApiError( 'rinac_get_availability', 'invalid_product', esc_html__( 'Producto inválido.', 'rinac' ), 400 );
        }

        $availability_manager = new \RINAC\Calendar\AvailabilityManager();
        $availability = $availability_manager->getAvailability(
            $product_id,
            $start,
            $end,
            $slot_id > 0 ? $slot_id : null
        );

        $message = $availability['available'] ? esc_html__( 'Disponibilidad calculada.', 'rinac' ) : esc_html__( 'No hay disponibilidad para la consulta.', 'rinac' );

        $this->sendApiSuccess(
            'rinac_get_availability',
            array(
                'product_id' => $product_id,
                'query' => array(
                    'start' => $start,
                    'end'   => $end,
                    'slot_id' => $slot_id > 0 ? $slot_id : null,
                ),
                'data' => $availability,
            ),
            $message
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

        $this->sendApiSuccess(
            'rinac_get_calendar_events',
            array(
                'product_id' => $product_id,
                'events' => array(),
            ),
            esc_html__( 'Endpoint base de eventos de calendario.', 'rinac' )
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

        $parsed = $this->parseBookingPayload( $request );
        $product_id = $parsed['product_id'];
        $slot_id = $parsed['slot_id'];
        $start = $parsed['start'];
        $end = $parsed['end'];
        $validation = $parsed['validation'];

        if ( is_wp_error( $validation ) ) {
            $validation_data = $validation->get_error_data( 'rinac_booking_validation_failed' );
            $this->sendApiError(
                'rinac_create_booking_request',
                'validation_failed',
                $validation->get_error_message(),
                400,
                array(
                    'errors' => is_array( $validation_data ) ? ( $validation_data['errors'] ?? array() ) : array(),
                    'error_messages' => is_array( $validation_data ) ? ( $validation_data['error_messages'] ?? array() ) : array(),
                )
            );
        }

        $hold_token = isset( $request['hold_token'] ) ? sanitize_text_field( wp_unslash( (string) $request['hold_token'] ) ) : '';
        $hold_response = array();
        if ( '' !== $hold_token && class_exists( '\RINAC\Concurrency\HoldManager' ) ) {
            $hold_manager = new \RINAC\Concurrency\HoldManager();
            $confirmed_hold = $hold_manager->confirmHold( $hold_token );
            if ( is_wp_error( $confirmed_hold ) ) {
                $this->sendApiError( 'rinac_create_booking_request', 'hold_confirmation_failed', $confirmed_hold->get_error_message(), 409 );
            }
            $hold_response = $confirmed_hold;
        }

        $this->sendApiSuccess(
            'rinac_create_booking_request',
            array(
                'request_id' => wp_generate_uuid4(),
                'product_id' => $product_id,
                'slot_id' => $slot_id,
                'participants' => $validation['participants'],
                'resources'    => $validation['resources'],
                'pricing'      => $validation['pricing'],
                'capacity'     => $validation['capacity'],
                'hold'         => $hold_response,
            ),
            esc_html__( 'Solicitud de reserva validada (sin persistir).', 'rinac' )
        );
    }

    /**
     * Endpoint de quote con bloqueo temporal.
     *
     * @return void
     */
    private function handleQuoteBooking(): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $hold_token = isset( $request['hold_token'] ) ? sanitize_text_field( wp_unslash( (string) $request['hold_token'] ) ) : '';
        if ( '' !== $hold_token && class_exists( '\RINAC\Concurrency\HoldManager' ) ) {
            $hold_manager = new \RINAC\Concurrency\HoldManager();
            $refreshed_hold = $hold_manager->refreshCartHold( $hold_token );

            if ( ! is_wp_error( $refreshed_hold ) ) {
                $this->sendApiSuccess(
                    'rinac_quote_booking',
                    array(
                        'request_id' => wp_generate_uuid4(),
                        'hold' => $refreshed_hold,
                        'refreshed' => true,
                    ),
                    esc_html__( 'Hold de carrito refrescado.', 'rinac' )
                );
            }
        }

        $parsed = $this->parseBookingPayload( $request );

        $validation = $parsed['validation'];
        if ( is_wp_error( $validation ) ) {
            $validation_data = $validation->get_error_data( 'rinac_booking_validation_failed' );
            $this->sendApiError(
                'rinac_quote_booking',
                'validation_failed',
                $validation->get_error_message(),
                400,
                array(
                    'errors' => is_array( $validation_data ) ? ( $validation_data['errors'] ?? array() ) : array(),
                    'error_messages' => is_array( $validation_data ) ? ( $validation_data['error_messages'] ?? array() ) : array(),
                )
            );
        }

        if ( ! class_exists( '\RINAC\Concurrency\HoldManager' ) ) {
            $this->sendApiError( 'rinac_quote_booking', 'hold_manager_unavailable', esc_html__( 'HoldManager no disponible.', 'rinac' ), 500 );
        }

        $hold_manager = new \RINAC\Concurrency\HoldManager();
        $hold = $hold_manager->createHold(
            $parsed['product_id'],
            $parsed['slot_id'],
            $parsed['start'],
            $parsed['end'],
            (float) ( $validation['capacity']['equivalent_total'] ?? 0.0 )
        );

        if ( is_wp_error( $hold ) ) {
            $this->sendApiError( 'rinac_quote_booking', 'hold_create_failed', $hold->get_error_message(), 409 );
        }

        $this->sendApiSuccess(
            'rinac_quote_booking',
            array(
                'request_id' => wp_generate_uuid4(),
                'product_id' => $parsed['product_id'],
                'slot_id' => $parsed['slot_id'],
                'participants' => $validation['participants'],
                'resources' => $validation['resources'],
                'pricing' => $validation['pricing'],
                'capacity' => $validation['capacity'],
                'hold' => $hold,
            ),
            esc_html__( 'Quote validada y bloqueo temporal creado.', 'rinac' )
        );
    }

    /**
     * Parsea payload de booking y ejecuta validación base.
     *
     * @param array<string,mixed> $request
     * @return array<string,mixed>
     */
    private function parseBookingPayload( array $request ): array {
        $product_id = isset( $request['product_id'] ) ? absint( $request['product_id'] ) : 0;
        $slot_id = isset( $request['slot_id'] ) ? absint( $request['slot_id'] ) : 0;
        $start = isset( $request['start'] ) ? sanitize_text_field( wp_unslash( (string) $request['start'] ) ) : '';
        $end = isset( $request['end'] ) ? sanitize_text_field( wp_unslash( (string) $request['end'] ) ) : '';

        if ( $product_id <= 0 ) {
            return array(
                'product_id' => 0,
                'slot_id' => $slot_id,
                'start' => $start,
                'end' => $end,
                'validation' => new \WP_Error( 'rinac_invalid_product', esc_html__( 'Producto inválido.', 'rinac' ) ),
            );
        }

        $wc_get_product_callable = 'wc_get_product';
        $product = $wc_get_product_callable( $product_id );
        if ( ! $product ) {
            return array(
                'product_id' => $product_id,
                'slot_id' => $slot_id,
                'start' => $start,
                'end' => $end,
                'validation' => new \WP_Error( 'rinac_product_not_found', esc_html__( 'No se ha encontrado el producto.', 'rinac' ) ),
            );
        }

        $raw_participants = isset( $request['participants'] ) && is_array( $request['participants'] )
            ? $request['participants']
            : array();
        $raw_resources = isset( $request['resources'] ) && is_array( $request['resources'] )
            ? $request['resources']
            : array();
        $days = isset( $request['days'] ) ? absint( $request['days'] ) : 1;
        $nights = isset( $request['nights'] ) ? absint( $request['nights'] ) : 1;

        $participants = array();
        foreach ( $raw_participants as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            $qty = isset( $item['qty'] ) ? absint( $item['qty'] ) : 0;
            if ( $id > 0 && $qty > 0 ) {
                $participants[] = array( 'id' => $id, 'qty' => $qty );
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
                $resources[] = array( 'id' => $id, 'qty' => $qty );
            }
        }

        $booking_manager = new \RINAC\Booking\BookingManager();
        $validation = $booking_manager->validateBookingRequest(
            $product,
            $participants,
            $resources,
            array(
                'days' => $days,
                'nights' => $nights,
                'start' => $start,
                'end' => $end,
                'slot_id' => $slot_id,
            )
        );

        return array(
            'product_id' => $product_id,
            'slot_id' => $slot_id,
            'start' => $start,
            'end' => $end,
            'validation' => $validation,
        );
    }

    /**
     * Respuesta API de éxito, estable y versionada.
     *
     * @param string $endpoint
     * @param array<string,mixed> $payload
     * @param string $message
     * @return void
     */
    private function sendApiSuccess( string $endpoint, array $payload, string $message ): void {
        wp_send_json_success(
            array(
                'api_version' => self::API_VERSION,
                'endpoint' => $endpoint,
                'status' => 'ok',
                'payload' => $payload,
                'message' => $message,
            )
        );
    }

    /**
     * Respuesta API de error, estable y versionada.
     *
     * @param string $endpoint
     * @param string $code
     * @param string $message
     * @param int $http_status
     * @param array<string,mixed> $context
     * @return void
     */
    private function sendApiError( string $endpoint, string $code, string $message, int $http_status = 400, array $context = array() ): void {
        wp_send_json_error(
            array(
                'api_version' => self::API_VERSION,
                'endpoint' => $endpoint,
                'status' => 'error',
                'error' => array(
                    'code' => $code,
                    'message' => $message,
                    'context' => $context,
                ),
            ),
            $http_status
        );
    }

    /**
     * Devuelve tipos de participante permitidos para un producto.
     *
     * @return void
     */
    private function handleGetAllowedParticipants(): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $product_id = isset( $request['product_id'] ) ? absint( $request['product_id'] ) : 0;
        if ( $product_id <= 0 ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Producto inválido.', 'rinac' ),
                ),
                400
            );
        }

        $allowed_ids = get_post_meta( $product_id, '_rinac_allowed_participant_types', true );
        if ( ! is_array( $allowed_ids ) ) {
            $allowed_ids = array();
        }
        $allowed_ids = array_values( array_unique( array_filter( array_map( 'absint', $allowed_ids ) ) ) );

        $items = array();
        foreach ( $allowed_ids as $id ) {
            $post = get_post( $id );
            if ( ! $post || 'rinac_participant' !== $post->post_type ) {
                continue;
            }

            $is_active = (int) get_post_meta( $id, '_rinac_pt_is_active', true );
            if ( 1 !== $is_active ) {
                continue;
            }

            $items[] = array(
                'id'                => $id,
                'label'             => get_post_meta( $id, '_rinac_pt_label', true ) ?: get_the_title( $id ),
                'capacity_fraction' => (float) get_post_meta( $id, '_rinac_pt_capacity_fraction', true ),
                'price_type'        => (string) get_post_meta( $id, '_rinac_pt_price_type', true ),
                'price_value'       => (float) get_post_meta( $id, '_rinac_pt_price_value', true ),
                'min_qty'           => (int) get_post_meta( $id, '_rinac_pt_min_qty', true ),
                'max_qty'           => (int) get_post_meta( $id, '_rinac_pt_max_qty', true ),
                'is_active'         => 1,
                'sort_order'        => (int) get_post_meta( $id, '_rinac_pt_sort_order', true ),
            );
        }

        wp_send_json_success(
            array(
                'endpoint'   => 'rinac_get_allowed_participants',
                'product_id' => $product_id,
                'items'      => $items,
                'message'    => esc_html__( 'Tipos de participante permitidos obtenidos.', 'rinac' ),
            )
        );
    }

    /**
     * Devuelve recursos permitidos para un producto.
     *
     * @return void
     */
    private function handleGetAllowedResources(): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();

        $product_id = isset( $request['product_id'] ) ? absint( $request['product_id'] ) : 0;
        if ( $product_id <= 0 ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Producto inválido.', 'rinac' ),
                ),
                400
            );
        }

        $allowed_ids = get_post_meta( $product_id, '_rinac_allowed_resources', true );
        if ( ! is_array( $allowed_ids ) ) {
            $allowed_ids = array();
        }
        $allowed_ids = array_values( array_unique( array_filter( array_map( 'absint', $allowed_ids ) ) ) );

        $items = array();
        foreach ( $allowed_ids as $id ) {
            $post = get_post( $id );
            if ( ! $post || 'rinac_resource' !== $post->post_type ) {
                continue;
            }

            $is_active = (int) get_post_meta( $id, '_rinac_resource_is_active', true );
            if ( 1 !== $is_active ) {
                continue;
            }

            $items[] = array(
                'id'           => $id,
                'label'        => get_post_meta( $id, '_rinac_resource_label', true ) ?: get_the_title( $id ),
                'resource_type'=> (string) get_post_meta( $id, '_rinac_resource_type', true ),
                'price_policy' => (string) get_post_meta( $id, '_rinac_resource_price_policy', true ),
                'price_value'  => (float) get_post_meta( $id, '_rinac_resource_price_value', true ),
                'min_qty'      => (int) get_post_meta( $id, '_rinac_resource_min_qty', true ),
                'max_qty'      => (int) get_post_meta( $id, '_rinac_resource_max_qty', true ),
                'is_active'    => 1,
                'sort_order'   => (int) get_post_meta( $id, '_rinac_resource_sort_order', true ),
            );
        }

        wp_send_json_success(
            array(
                'endpoint'   => 'rinac_get_allowed_resources',
                'product_id' => $product_id,
                'items'      => $items,
                'message'    => esc_html__( 'Recursos permitidos obtenidos.', 'rinac' ),
            )
        );
    }
}
