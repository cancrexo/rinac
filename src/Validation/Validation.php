<?php

declare(strict_types=1);

namespace Rinac\Validation;

final class Validation
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks()
    {
        // Validación antes de añadir al carrito
        \add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 5);

        // Validación al actualizar carrito
        \add_action('woocommerce_check_cart_items', array($this, 'validate_cart_items'));

        // Validación antes del checkout
        \add_action('woocommerce_checkout_process', array($this, 'validate_checkout'));

        // Validación de concurrencia al crear pedido
        \add_action('woocommerce_checkout_order_processed', array($this, 'validate_order_concurrency'), 5, 3);
    }

    /**
     * Validar añadir al carrito
     */
    public function validate_add_to_cart($passed, $product_id, $quantity, $variation_id = '', $variations = array())
    {
        $product = \wc_get_product($product_id);

        if (!$product || $product->get_type() !== 'visitas') {
            return $passed;
        }

        // Verificar que los datos de reserva estén presentes
        if (!isset($_POST['rinac_nonce']) || !\wp_verify_nonce($_POST['rinac_nonce'], 'rinac_add_to_cart')) {
            \wc_add_notice(__('Error de seguridad en la reserva.', 'rinac'), 'error');
            return false;
        }

        // Validar campos requeridos
        $required_fields = array(
            'rinac_fecha' => __('Fecha de la visita', 'rinac'),
            'rinac_horario' => __('Horario', 'rinac'),
            'rinac_personas' => __('Número de personas', 'rinac')
        );

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                \wc_add_notice(sprintf(__('El campo "%s" es obligatorio.', 'rinac'), $label), 'error');
                return false;
            }
        }

        $fecha = \sanitize_text_field($_POST['rinac_fecha']);
        $horario = \sanitize_text_field($_POST['rinac_horario']);
        $personas = intval($_POST['rinac_personas']);
        $telefono = isset($_POST['rinac_telefono']) ? \sanitize_text_field($_POST['rinac_telefono']) : '';

        // Validar teléfono si es requerido
        if (\get_option('rinac_require_phone', false) && empty($telefono)) {
            \wc_add_notice(__('El campo teléfono es obligatorio.', 'rinac'), 'error');
            return false;
        }

        // Validar formato de fecha
        if (!$this->validate_date_format($fecha)) {
            \wc_add_notice(__('Formato de fecha no válido.', 'rinac'), 'error');
            return false;
        }

        // Validar que la fecha no sea del pasado
        if (strtotime($fecha) < strtotime(date('Y-m-d'))) {
            \wc_add_notice(__('No se pueden hacer reservas para fechas pasadas.', 'rinac'), 'error');
            return false;
        }

        // Validar número de personas
        if ($personas < 1 || $personas > 50) {
            \wc_add_notice(__('El número de personas debe estar entre 1 y 50.', 'rinac'), 'error');
            return false;
        }

        // Validar disponibilidad de la fecha
        if (!$this->is_date_available($product_id, $fecha)) {
            \wc_add_notice(__('La fecha seleccionada no está disponible.', 'rinac'), 'error');
            return false;
        }

        // Validar que el horario existe para el producto
        if (!$this->is_time_slot_valid($product_id, $horario)) {
            \wc_add_notice(__('El horario seleccionado no es válido.', 'rinac'), 'error');
            return false;
        }

        // Validar disponibilidad de capacidad
        if (!$this->validate_capacity($product_id, $fecha, $horario, $personas)) {
            return false;
        }

        return $passed;
    }

    /**
     * Validar elementos del carrito
     */
    public function validate_cart_items()
    {
        foreach (\WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['rinac_booking'])) {
                $booking = $cart_item['rinac_booking'];
                $product_id = $booking['product_id'];

                // Re-validar disponibilidad
                if (!$this->is_date_available($product_id, $booking['fecha'])) {
                    \wc_add_notice(
                        sprintf(
                            __('La fecha %s ya no está disponible para "%s". Por favor, elimina el producto del carrito y selecciona una nueva fecha.', 'rinac'),
                            \date_i18n(\get_option('date_format'), strtotime($booking['fecha'])),
                            \get_the_title($product_id)
                        ),
                        'error'
                    );
                }

                // Re-validar capacidad
                if (!$this->validate_capacity($product_id, $booking['fecha'], $booking['horario'], $booking['personas'], $cart_item_key)) {
                    // El mensaje de error ya se muestra en validate_capacity
                }
            }
        }
    }

    /**
     * Validar antes del checkout
     */
    public function validate_checkout()
    {
        foreach (\WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['rinac_booking'])) {
                $booking = $cart_item['rinac_booking'];
                $product_id = $booking['product_id'];

                // Validación final antes del checkout
                if (!$this->validate_final_availability($product_id, $booking['fecha'], $booking['horario'], $booking['personas'])) {
                    \wc_add_notice(
                        sprintf(
                            __('La reserva para "%s" ya no está disponible. Por favor, revisa tu carrito.', 'rinac'),
                            \get_the_title($product_id)
                        ),
                        'error'
                    );
                }
            }
        }
    }

    /**
     * Validación de concurrencia al procesar pedido
     */
    public function validate_order_concurrency($order_id, $posted_data, $order)
    {
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            if ($product && $product->get_type() === 'visitas') {
                // Obtener datos de reserva del meta del item
                $fecha = $item->get_meta(__('Fecha', 'rinac'));
                $horario = $item->get_meta(__('Horario', 'rinac'));
                $personas = $item->get_meta(__('Personas', 'rinac'));

                if ($fecha && $horario && $personas) {
                    // Convertir fecha al formato de base de datos
                    $fecha_db = date('Y-m-d', strtotime($fecha));

                    // Validación final de concurrencia
                    if (!$this->validate_final_availability($product->get_id(), $fecha_db, $horario, intval($personas))) {
                        // Cancelar pedido si no hay disponibilidad
                        $order->update_status('failed', __('Reserva no disponible por concurrencia.', 'rinac'));

                        \wc_add_notice(
                            __('Lo sentimos, la reserva ya no está disponible. Tu pedido ha sido cancelado.', 'rinac'),
                            'error'
                        );

                        // Redirigir al carrito
                        \wp_redirect(\wc_get_cart_url());
                        exit;
                    }
                }
            }
        }
    }

    /**
     * Validar formato de fecha
     */
    private function validate_date_format($fecha)
    {
        $date = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $date && $date->format('Y-m-d') === $fecha;
    }

    /**
     * Verificar si una fecha está disponible
     */
    private function is_date_available($product_id, $fecha)
    {
        global $wpdb;

        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';

        $disponible = $wpdb->get_var($wpdb->prepare(
            "SELECT disponible FROM $table_disponibilidad 
             WHERE product_id = %d AND fecha = %s",
            $product_id,
            $fecha
        ));

        return $disponible == 1;
    }

    /**
     * Verificar si un horario es válido para un producto
     */
    private function is_time_slot_valid($product_id, $horario)
    {
        global $wpdb;

        $table_horas = $wpdb->prefix . 'rinac_producto_horas';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_horas 
             WHERE product_id = %d AND descripcion = %s",
            $product_id,
            $horario
        ));

        return $exists > 0;
    }

    /**
     * Validar capacidad disponible
     */
    private function validate_capacity($product_id, $fecha, $horario, $personas, $exclude_cart_item = null)
    {
        $max_personas = $this->get_max_capacity($product_id, $horario);
        $personas_reservadas = $this->get_reserved_capacity($product_id, $fecha, $horario);
        $personas_en_carrito = $this->get_cart_capacity($product_id, $fecha, $horario, $exclude_cart_item);

        $total_ocupacion = $personas_reservadas + $personas_en_carrito + $personas;

        if ($total_ocupacion > $max_personas) {
            $disponibles = $max_personas - $personas_reservadas - $personas_en_carrito;

            if ($disponibles <= 0) {
                \wc_add_notice(
                    sprintf(
                        __('No hay disponibilidad para el horario %s el día %s.', 'rinac'),
                        $horario,
                        \date_i18n(\get_option('date_format'), strtotime($fecha))
                    ),
                    'error'
                );
            } else {
                \wc_add_notice(
                    sprintf(
                        __('Solo quedan %d plazas disponibles para el horario %s el día %s. Has seleccionado %d personas.', 'rinac'),
                        $disponibles,
                        $horario,
                        \date_i18n(\get_option('date_format'), strtotime($fecha)),
                        $personas
                    ),
                    'error'
                );
            }

            return false;
        }

        return true;
    }

    /**
     * Validación final de disponibilidad (con bloqueo para evitar concurrencia)
     */
    private function validate_final_availability($product_id, $fecha, $horario, $personas)
    {
        global $wpdb;

        // Usar transacción para evitar problemas de concurrencia
        $wpdb->query('START TRANSACTION');

        try {
            $max_personas = $this->get_max_capacity($product_id, $horario);
            $personas_reservadas = $this->get_reserved_capacity($product_id, $fecha, $horario);

            $total_ocupacion = $personas_reservadas + $personas;

            if ($total_ocupacion > $max_personas) {
                $wpdb->query('ROLLBACK');
                return false;
            }

            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Obtener capacidad máxima para un horario
     */
    private function get_max_capacity($product_id, $horario)
    {
        global $wpdb;

        $table_horas = $wpdb->prefix . 'rinac_producto_horas';

        $max_personas = $wpdb->get_var($wpdb->prepare(
            "SELECT maximo_personas_slot FROM $table_horas 
             WHERE product_id = %d AND descripcion = %s",
            $product_id,
            $horario
        ));

        return $max_personas ? intval($max_personas) : 0;
    }

    /**
     * Obtener capacidad ya reservada
     */
    private function get_reserved_capacity($product_id, $fecha, $horario)
    {
        global $wpdb;

        $table_reservas = $wpdb->prefix . 'rinac_reservas';

        $personas_reservadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(numero_personas), 0) FROM $table_reservas 
             WHERE product_id = %d AND fecha = %s AND hora_descripcion = %s 
             AND status IN ('pending', 'confirmed', 'processing', 'completed')",
            $product_id,
            $fecha,
            $horario
        ));

        return intval($personas_reservadas);
    }

    /**
     * Obtener capacidad en el carrito actual
     */
    private function get_cart_capacity($product_id, $fecha, $horario, $exclude_cart_item = null)
    {
        if (!\WC()->cart) {
            return 0;
        }

        $total_personas = 0;

        foreach (\WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            // Excluir el item actual si se especifica
            if ($exclude_cart_item && $cart_item_key === $exclude_cart_item) {
                continue;
            }

            if (isset($cart_item['rinac_booking'])) {
                $booking = $cart_item['rinac_booking'];

                if ($booking['product_id'] == $product_id &&
                    $booking['fecha'] === $fecha &&
                    $booking['horario'] === $horario) {

                    $total_personas += intval($booking['personas']);
                }
            }
        }

        return $total_personas;
    }

    /**
     * Obtener información de disponibilidad para un slot
     */
    public function get_availability_info($product_id, $fecha, $horario)
    {
        $max_personas = $this->get_max_capacity($product_id, $horario);
        $personas_reservadas = $this->get_reserved_capacity($product_id, $fecha, $horario);
        $personas_en_carrito = $this->get_cart_capacity($product_id, $fecha, $horario);

        $disponibles = $max_personas - $personas_reservadas - $personas_en_carrito;

        return array(
            'max_personas' => $max_personas,
            'personas_reservadas' => $personas_reservadas,
            'personas_en_carrito' => $personas_en_carrito,
            'disponibles' => max(0, $disponibles),
            'porcentaje_ocupacion' => $max_personas > 0 ? round((($personas_reservadas + $personas_en_carrito) / $max_personas) * 100, 2) : 0
        );
    }
}

