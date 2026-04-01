<?php

namespace RINAC\Core;

use RINAC\Booking\BookingRecordRepository;

/**
 * Menú de administración de RINAC.
 */
class MenuRegistrar {
    private const SETTINGS_OPTION_KEY = 'rinac_settings';
    private BookingRecordRepository $bookingRepository;

    public function __construct() {
        $this->bookingRepository = new BookingRecordRepository();
    }

    /**
     * Registra menú y submenús base.
     *
     * @return void
     */
    public function registerAdminMenu(): void {
        $capability = 'manage_woocommerce';
        $slug = 'rinac_dashboard';

        add_menu_page(
            __( 'RINAC', 'rinac' ),
            __( 'RINAC', 'rinac' ),
            $capability,
            $slug,
            array( $this, 'renderDashboard' ),
            'dashicons-calendar-alt',
            56
        );

        add_submenu_page( $slug, __( 'Dashboard', 'rinac' ), __( 'Dashboard', 'rinac' ), $capability, $slug, array( $this, 'renderDashboard' ) );
        add_submenu_page( $slug, __( 'Productos reservables', 'rinac' ), __( 'Productos reservables', 'rinac' ), $capability, 'rinac_productos_reservables', array( $this, 'renderProductosReservables' ) );
        add_submenu_page( $slug, __( 'Calendario global', 'rinac' ), __( 'Calendario global', 'rinac' ), $capability, 'rinac_calendario_global', array( $this, 'renderCalendarioGlobal' ) );
        add_submenu_page( $slug, __( 'Ajustes', 'rinac' ), __( 'Ajustes', 'rinac' ), $capability, 'rinac_ajustes', array( $this, 'renderAjustes' ) );

        $this->reorderDashboardSubmenu( $slug );
    }

    /**
     * Reordena el submenú principal de RINAC para mantener una UX estable.
     *
     * @param string $parent_slug
     * @return void
     */
    private function reorderDashboardSubmenu( string $parent_slug ): void {
        global $submenu;

        if ( ! isset( $submenu[ $parent_slug ] ) || ! is_array( $submenu[ $parent_slug ] ) ) {
            return;
        }

        $items_by_slug = array();
        foreach ( $submenu[ $parent_slug ] as $item ) {
            if ( ! is_array( $item ) || ! isset( $item[2] ) ) {
                continue;
            }
            $items_by_slug[ (string) $item[2] ] = $item;
        }

        $desired_order = array(
            'rinac_dashboard',
            'rinac_productos_reservables',
            'edit.php?post_type=rinac_slot',
            'edit.php?post_type=rinac_participant',
            'edit.php?post_type=rinac_resource',
            'edit.php?post_type=rinac_booking',
            'rinac_calendario_global',
            'rinac_ajustes',
        );

        $ordered = array();
        foreach ( $desired_order as $submenu_slug ) {
            if ( isset( $items_by_slug[ $submenu_slug ] ) ) {
                $ordered[] = $items_by_slug[ $submenu_slug ];
                unset( $items_by_slug[ $submenu_slug ] );
            }
        }

        // Mantener cualquier elemento extra al final para no perder extensibilidad.
        foreach ( $items_by_slug as $remaining_item ) {
            $ordered[] = $remaining_item;
        }

        $submenu[ $parent_slug ] = $ordered;
    }

    /**
     * Render dashboard.
     *
     * @return void
     */
    public function renderDashboard(): void {
        $bookings_confirmed = $this->countBookingsByBusinessStatus( 'confirmed' );
        $bookings_hold = $this->countBookingsByBusinessStatus( 'hold' );
        $bookings_expired = $this->countBookingsByBusinessStatus( 'expired' );
        $bookings_cancelled = $this->countBookingsByBusinessStatus( 'cancelled' );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'RINAC - Dashboard', 'rinac' ) . '</h1>';
        echo '<p>' . esc_html__( 'Resumen operativo de reservas y configuración.', 'rinac' ) . '</p>';
        echo '<ul style="margin-top:16px;line-height:1.9;">';
        echo '<li><strong>' . esc_html__( 'Reservas confirmadas:', 'rinac' ) . '</strong> ' . esc_html( (string) $bookings_confirmed ) . '</li>';
        echo '<li><strong>' . esc_html__( 'Reservas en hold:', 'rinac' ) . '</strong> ' . esc_html( (string) $bookings_hold ) . '</li>';
        echo '<li><strong>' . esc_html__( 'Reservas expiradas:', 'rinac' ) . '</strong> ' . esc_html( (string) $bookings_expired ) . '</li>';
        echo '<li><strong>' . esc_html__( 'Reservas canceladas:', 'rinac' ) . '</strong> ' . esc_html( (string) $bookings_cancelled ) . '</li>';
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Render productos reservables.
     *
     * @return void
     */
    public function renderProductosReservables(): void {
        $products = get_posts(
            array(
                'post_type' => 'product',
                'post_status' => array( 'publish', 'draft', 'private' ),
                'posts_per_page' => 100,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => array( 'rinac_reserva' ),
                    ),
                ),
            )
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'RINAC - Productos reservables', 'rinac' ) . '</h1>';
        echo '<p>' . esc_html__( 'Listado de productos WooCommerce de tipo rinac_reserva.', 'rinac' ) . '</p>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__( 'ID', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Título', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Estado', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Acción', 'rinac' ) . '</th>';
        echo '</tr></thead><tbody>';
        if ( empty( $products ) ) {
            echo '<tr><td colspan="4">' . esc_html__( 'No hay productos reservables todavía.', 'rinac' ) . '</td></tr>';
        } else {
            foreach ( $products as $product ) {
                $product_id = (int) $product->ID;
                echo '<tr>';
                echo '<td>' . esc_html( (string) $product_id ) . '</td>';
                echo '<td>' . esc_html( get_the_title( $product_id ) ) . '</td>';
                echo '<td>' . esc_html( (string) $product->post_status ) . '</td>';
                echo '<td><a class="button button-small" href="' . esc_url( get_edit_post_link( $product_id ) ?: '' ) . '">' . esc_html__( 'Editar', 'rinac' ) . '</a></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Render calendario global.
     *
     * @return void
     */
    public function renderCalendarioGlobal(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'No tienes permisos para acceder a esta pantalla.', 'rinac' ) );
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $get = isset( $_GET ) && is_array( $_GET ) ? $_GET : array();
        $notice = isset( $get['rinac_notice'] ) ? sanitize_key( (string) $get['rinac_notice'] ) : '';
        $imported = isset( $get['rinac_imported'] ) ? absint( $get['rinac_imported'] ) : 0;

        $bookings = get_posts(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'publish', 'pending', 'private', 'draft' ),
                'posts_per_page' => 50,
                'orderby' => 'date',
                'order' => 'DESC',
            )
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'RINAC - Calendario global', 'rinac' ) . '</h1>';

        if ( 'import_ok' === $notice ) {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html(
                    sprintf(
                        /* translators: %d cantidad de reservas creadas. */
                        __( 'Importación completada. Reservas creadas: %d.', 'rinac' ),
                        $imported
                    )
                ) .
            '</p></div>';
        } elseif ( 'import_error' === $notice ) {
            echo '<div class="notice notice-error is-dismissible"><p>' .
                esc_html__( 'No se pudo importar datos de prueba.', 'rinac' ) .
            '</p></div>';
        }

        echo '<p>' . esc_html__( 'Vista rápida de reservas para operación interna.', 'rinac' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin: 12px 0 20px;">';
        wp_nonce_field( 'rinac_import_demo_data', 'rinac_import_demo_nonce' );
        echo '<input type="hidden" name="action" value="rinac_import_demo_data" />';
        submit_button( __( 'Importar datos de prueba', 'rinac' ), 'secondary', 'submit', false );
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Reserva', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Producto', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Inicio', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Fin', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Slot', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Capacidad eq.', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Estado', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Pedido', 'rinac' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $bookings ) ) {
            echo '<tr><td colspan="8">' . esc_html__( 'No hay reservas registradas.', 'rinac' ) . '</td></tr>';
        } else {
            foreach ( $bookings as $booking ) {
                $booking_id = (int) $booking->ID;
                $product_id = (int) get_post_meta( $booking_id, '_rinac_booking_product_id', true );
                $start = (string) get_post_meta( $booking_id, '_rinac_booking_start', true );
                $end = (string) get_post_meta( $booking_id, '_rinac_booking_end', true );
                $slot_id = (int) get_post_meta( $booking_id, '_rinac_booking_slot_id', true );
                $equivalent_qty = (float) get_post_meta( $booking_id, '_rinac_booking_equivalent_qty', true );
                $status = (string) get_post_meta( $booking_id, '_rinac_booking_status', true );
                $order_id = (int) get_post_meta( $booking_id, '_rinac_booking_order_id', true );

                echo '<tr>';
                echo '<td><a href="' . esc_url( get_edit_post_link( $booking_id ) ?: '' ) . '">#' . esc_html( (string) $booking_id ) . '</a></td>';
                echo '<td>' . esc_html( $product_id > 0 ? ( '#' . $product_id . ' - ' . get_the_title( $product_id ) ) : '-' ) . '</td>';
                echo '<td>' . esc_html( '' !== $start ? $start : '-' ) . '</td>';
                echo '<td>' . esc_html( '' !== $end ? $end : '-' ) . '</td>';
                echo '<td>' . esc_html( $slot_id > 0 ? (string) $slot_id : '-' ) . '</td>';
                echo '<td>' . esc_html( number_format_i18n( $equivalent_qty, 2 ) ) . '</td>';
                echo '<td>' . esc_html( '' !== $status ? $status : 'sin_estado' ) . '</td>';
                echo '<td>' . esc_html( $order_id > 0 ? (string) $order_id : '-' ) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Render ajustes.
     *
     * @return void
     */
    public function renderAjustes(): void {
        $settings = $this->getSettings();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'RINAC - Ajustes', 'rinac' ) . '</h1>';
        echo '<p>' . esc_html__( 'Herramientas administrativas del plugin.', 'rinac' ) . '</p>';
        echo '<h2 style="margin-top:24px;">' . esc_html__( 'Concurrencia (hold)', 'rinac' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'options.php' ) ) . '" style="max-width:900px;">';
        settings_fields( 'rinac_settings_group' );
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row"><label for="rinac_cart_hold_ttl_minutes">' . esc_html__( 'Cart hold TTL (minutos)', 'rinac' ) . '</label></th><td>';
        echo '<input id="rinac_cart_hold_ttl_minutes" name="rinac_settings[cart_hold_ttl_minutes]" type="number" min="1" step="1" value="' . esc_attr( (string) $settings['cart_hold_ttl_minutes'] ) . '" class="small-text" />';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="rinac_cart_hold_max_lifetime_minutes">' . esc_html__( 'Cart hold vida máxima (minutos)', 'rinac' ) . '</label></th><td>';
        echo '<input id="rinac_cart_hold_max_lifetime_minutes" name="rinac_settings[cart_hold_max_lifetime_minutes]" type="number" min="1" step="1" value="' . esc_attr( (string) $settings['cart_hold_max_lifetime_minutes'] ) . '" class="small-text" />';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="rinac_cart_hold_refresh_min_interval_seconds">' . esc_html__( 'Cart hold refresco mínimo (segundos)', 'rinac' ) . '</label></th><td>';
        echo '<input id="rinac_cart_hold_refresh_min_interval_seconds" name="rinac_settings[cart_hold_refresh_min_interval_seconds]" type="number" min="10" step="1" value="' . esc_attr( (string) $settings['cart_hold_refresh_min_interval_seconds'] ) . '" class="small-text" />';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="rinac_order_hold_ttl_pending_minutes">' . esc_html__( 'Order hold pendiente (minutos)', 'rinac' ) . '</label></th><td>';
        echo '<input id="rinac_order_hold_ttl_pending_minutes" name="rinac_settings[order_hold_ttl_pending_minutes]" type="number" min="5" step="1" value="' . esc_attr( (string) $settings['order_hold_ttl_pending_minutes'] ) . '" class="small-text" />';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="rinac_order_hold_ttl_on_hold_hours">' . esc_html__( 'Order hold on-hold (horas)', 'rinac' ) . '</label></th><td>';
        echo '<input id="rinac_order_hold_ttl_on_hold_hours" name="rinac_settings[order_hold_ttl_on_hold_hours]" type="number" min="1" step="1" value="' . esc_attr( (string) $settings['order_hold_ttl_on_hold_hours'] ) . '" class="small-text" />';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="rinac_order_hold_ttl_bacs_hours">' . esc_html__( 'Order hold transferencia/BACS (horas)', 'rinac' ) . '</label></th><td>';
        echo '<input id="rinac_order_hold_ttl_bacs_hours" name="rinac_settings[order_hold_ttl_bacs_hours]" type="number" min="1" step="1" value="' . esc_attr( (string) $settings['order_hold_ttl_bacs_hours'] ) . '" class="small-text" />';
        echo '</td></tr>';

        echo '</tbody></table>';
        submit_button( __( 'Guardar ajustes de concurrencia', 'rinac' ) );
        echo '</form>';

        echo '<div style="border:1px solid #d63638;background:#fff5f5;padding:12px;margin-top:16px;max-width:900px;">';
        echo '<p style="margin-top:0;"><strong>' . esc_html__( 'Importar datos de prueba', 'rinac' ) . '</strong></p>';
        echo '<p>' . esc_html__( 'Esta acción crea/actualiza datos demo para pruebas internas. No usar en producción sin copia de seguridad.', 'rinac' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'rinac_import_demo_data', 'rinac_import_demo_nonce' );
        echo '<input type="hidden" name="action" value="rinac_import_demo_data" />';
        submit_button( __( 'Importar datos de prueba', 'rinac' ), 'delete', 'submit', false );
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Registra opciones persistentes de configuración.
     *
     * @return void
     */
    public function registerSettings(): void {
        register_setting(
            'rinac_settings_group',
            self::SETTINGS_OPTION_KEY,
            array( $this, 'sanitizeSettings' )
        );
    }

    /**
     * Registra acciones admin_post necesarias fuera de admin_menu.
     *
     * @return void
     */
    public function registerAdminPostActions(): void {
        add_action( 'admin_post_rinac_import_demo_data', array( $this, 'handleImportDemoData' ) );
    }

    /**
     * Importa datos demo básicos para pruebas operativas.
     *
     * @return void
     */
    public function handleImportDemoData(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'No tienes permisos para esta acción.', 'rinac' ) );
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $post_data = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();
        $nonce = isset( $post_data['rinac_import_demo_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $post_data['rinac_import_demo_nonce'] ) ) : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'rinac_import_demo_data' ) ) {
            wp_die( esc_html__( 'Nonce inválido.', 'rinac' ) );
        }

        $product_id = $this->getOrCreateDemoReservableProduct();
        $slot_id = $this->getOrCreateDemoSlot();
        $created = 0;

        if ( $product_id > 0 ) {
            if ( $slot_id > 0 ) {
                update_post_meta( $product_id, '_rinac_allowed_slots', array( $slot_id ) );
            }
            for ( $i = 0; $i < 3; $i++ ) {
                $start = gmdate( 'Y-m-d', strtotime( '+' . ( $i + 1 ) . ' day' ) );
                $end = $start;
                $booking_id = $this->bookingRepository->create(
                    array(
                        'post_status' => 'publish',
                        'post_title' => sprintf(
                            /* translators: %d índice demo. */
                            __( 'Reserva demo %d', 'rinac' ),
                            $i + 1
                        ),
                        'product_id' => $product_id,
                        'slot_id' => $slot_id,
                        'start' => $start,
                        'end' => $end,
                        'equivalent_qty' => 1.0 + ( 0.5 * $i ),
                        'booking_status' => 'confirmed',
                    )
                );

                if ( ! is_wp_error( $booking_id ) && is_numeric( $booking_id ) ) {
                    $created++;
                }
            }
        }

        $redirect_url = add_query_arg(
            array(
                'page' => 'rinac_calendario_global',
                'rinac_notice' => $created > 0 ? 'import_ok' : 'import_error',
                'rinac_imported' => $created,
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Obtiene o crea producto reservable demo.
     */
    private function getOrCreateDemoReservableProduct(): int {
        $existing = get_posts(
            array(
                'post_type' => 'product',
                'post_status' => array( 'publish', 'draft', 'private' ),
                'posts_per_page' => 1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => array( 'rinac_reserva' ),
                    ),
                ),
                'fields' => 'ids',
            )
        );

        if ( ! empty( $existing ) ) {
            return (int) $existing[0];
        }

        $product_id = wp_insert_post(
            array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_title' => __( 'Producto demo RINAC', 'rinac' ),
                'post_content' => __( 'Producto de pruebas para reservas.', 'rinac' ),
            ),
            true
        );

        if ( is_wp_error( $product_id ) || ! is_numeric( $product_id ) ) {
            return 0;
        }

        $product_id = (int) $product_id;
        wp_set_object_terms( $product_id, 'rinac_reserva', 'product_type', false );
        update_post_meta( $product_id, '_regular_price', '120' );
        update_post_meta( $product_id, '_price', '120' );
        update_post_meta( $product_id, 'rinac_booking_mode', 'slot_dia' );
        update_post_meta( $product_id, '_rinac_base_capacity', 10 );
        update_post_meta( $product_id, '_rinac_capacity_total_max', 0 );
        update_post_meta( $product_id, '_rinac_capacity_min_booking', 1 );
        update_post_meta( $product_id, '_rinac_payment_mode', 'deposit' );
        update_post_meta( $product_id, '_rinac_deposit_percentage', 30 );

        return $product_id;
    }

    /**
     * Obtiene o crea slot demo.
     */
    private function getOrCreateDemoSlot(): int {
        $existing = get_posts(
            array(
                'post_type' => 'rinac_slot',
                'post_status' => array( 'publish', 'draft', 'private' ),
                'posts_per_page' => 1,
                'fields' => 'ids',
            )
        );

        if ( ! empty( $existing ) ) {
            return (int) $existing[0];
        }

        $slot_id = wp_insert_post(
            array(
                'post_type' => 'rinac_slot',
                'post_status' => 'publish',
                'post_title' => __( 'Slot demo mañana', 'rinac' ),
            ),
            true
        );

        if ( is_wp_error( $slot_id ) || ! is_numeric( $slot_id ) ) {
            return 0;
        }

        $slot_id = (int) $slot_id;
        update_post_meta( $slot_id, '_rinac_slot_label', __( 'Turno mañana', 'rinac' ) );
        update_post_meta( $slot_id, '_rinac_slot_start_time', '10:00' );
        update_post_meta( $slot_id, '_rinac_slot_end_time', '12:00' );
        update_post_meta( $slot_id, '_rinac_capacity_max', 10 );
        update_post_meta( $slot_id, '_rinac_slot_is_active', 1 );

        return $slot_id;
    }

    /**
     * Cuenta reservas por estado de negocio (_rinac_booking_status).
     *
     * @param string $booking_status
     * @return int
     */
    private function countBookingsByBusinessStatus( string $booking_status ): int {
        if ( '' === $booking_status ) {
            return 0;
        }

        $query = new \WP_Query(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'publish', 'pending', 'private', 'draft' ),
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => false,
                'meta_query' => array(
                    array(
                        'key' => '_rinac_booking_status',
                        'value' => sanitize_key( $booking_status ),
                        'compare' => '=',
                    ),
                ),
            )
        );

        return (int) $query->found_posts;
    }

    /**
     * Lee configuración con defaults.
     *
     * @return array<string,int>
     */
    private function getSettings(): array {
        $raw = get_option( self::SETTINGS_OPTION_KEY, array() );
        if ( ! is_array( $raw ) ) {
            $raw = array();
        }

        return array(
            'cart_hold_ttl_minutes' => isset( $raw['cart_hold_ttl_minutes'] ) ? (int) $raw['cart_hold_ttl_minutes'] : 15,
            'cart_hold_max_lifetime_minutes' => isset( $raw['cart_hold_max_lifetime_minutes'] ) ? (int) $raw['cart_hold_max_lifetime_minutes'] : 90,
            'cart_hold_refresh_min_interval_seconds' => isset( $raw['cart_hold_refresh_min_interval_seconds'] ) ? (int) $raw['cart_hold_refresh_min_interval_seconds'] : 60,
            'order_hold_ttl_pending_minutes' => isset( $raw['order_hold_ttl_pending_minutes'] ) ? (int) $raw['order_hold_ttl_pending_minutes'] : 45,
            'order_hold_ttl_on_hold_hours' => isset( $raw['order_hold_ttl_on_hold_hours'] ) ? (int) $raw['order_hold_ttl_on_hold_hours'] : 24,
            'order_hold_ttl_bacs_hours' => isset( $raw['order_hold_ttl_bacs_hours'] ) ? (int) $raw['order_hold_ttl_bacs_hours'] : 72,
        );
    }

    /**
     * Sanitiza ajustes de concurrencia guardados en admin.
     *
     * @param mixed $input
     * @return array<string,int>
     */
    public function sanitizeSettings( $input ): array {
        $settings = is_array( $input ) ? $input : array();

        return array(
            'cart_hold_ttl_minutes' => $this->sanitizeIntRange( $settings, 'cart_hold_ttl_minutes', 15, 1, 240 ),
            'cart_hold_max_lifetime_minutes' => $this->sanitizeIntRange( $settings, 'cart_hold_max_lifetime_minutes', 90, 5, 1440 ),
            'cart_hold_refresh_min_interval_seconds' => $this->sanitizeIntRange( $settings, 'cart_hold_refresh_min_interval_seconds', 60, 10, 600 ),
            'order_hold_ttl_pending_minutes' => $this->sanitizeIntRange( $settings, 'order_hold_ttl_pending_minutes', 45, 5, 1440 ),
            'order_hold_ttl_on_hold_hours' => $this->sanitizeIntRange( $settings, 'order_hold_ttl_on_hold_hours', 24, 1, 720 ),
            'order_hold_ttl_bacs_hours' => $this->sanitizeIntRange( $settings, 'order_hold_ttl_bacs_hours', 72, 1, 720 ),
        );
    }

    /**
     * Sanitiza entero dentro de un rango cerrado.
     *
     * @param array<string,mixed> $settings
     * @param string $key
     * @param int $default
     * @param int $min
     * @param int $max
     * @return int
     */
    private function sanitizeIntRange( array $settings, string $key, int $default, int $min, int $max ): int {
        $value = isset( $settings[ $key ] ) ? (int) $settings[ $key ] : $default;
        if ( $value < $min ) {
            return $min;
        }
        if ( $value > $max ) {
            return $max;
        }
        return $value;
    }
}
