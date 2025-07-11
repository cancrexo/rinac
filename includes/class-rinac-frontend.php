<?php
/**
 * Clase para manejar el frontend del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RINAC_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Modificar formulario de producto en frontend
        add_action('woocommerce_before_single_product_summary', array($this, 'show_visitas_booking_form'), 25);
        
        // Ocultar botón de añadir al carrito estándar para productos VISITAS
        add_action('woocommerce_single_product_summary', array($this, 'remove_default_add_to_cart'), 1);
        
        // Procesar datos de reserva antes de añadir al carrito
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_booking_data_to_cart'), 10, 3);
        
        // Mostrar datos de reserva en carrito
        add_filter('woocommerce_get_item_data', array($this, 'display_booking_data_in_cart'), 10, 2);
        
        // Guardar datos de reserva en pedido
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_booking_data_to_order'), 10, 4);
        
        // Cargar scripts y estilos de frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX para obtener horarios disponibles
        add_action('wp_ajax_rinac_get_horarios', array($this, 'ajax_get_horarios'));
        add_action('wp_ajax_nopriv_rinac_get_horarios', array($this, 'ajax_get_horarios'));
        
        // AJAX para verificar disponibilidad
        add_action('wp_ajax_rinac_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_rinac_check_availability', array($this, 'ajax_check_availability'));
        
        // AJAX para renderizar modales
        add_action('wp_ajax_rinac_render_quick_booking_modal', array($this, 'ajax_render_quick_booking_modal'));
        add_action('wp_ajax_nopriv_rinac_render_quick_booking_modal', array($this, 'ajax_render_quick_booking_modal'));
        
        add_action('wp_ajax_rinac_render_booking_details_modal', array($this, 'ajax_render_booking_details_modal'));
        add_action('wp_ajax_nopriv_rinac_render_booking_details_modal', array($this, 'ajax_render_booking_details_modal'));
    }
    
    /**
     * Cargar scripts y estilos de frontend
     */
    public function enqueue_frontend_scripts() {
        if (is_product()) {
            global $post;
            $product = wc_get_product($post->ID);
            
            if ($product && $product->get_type() === 'visitas') {
                wp_enqueue_script(
                    'rinac-frontend',
                    RINAC_PLUGIN_URL . 'assets/js/frontend.js',
                    array('jquery', 'jquery-ui-datepicker'),
                    RINAC_VERSION,
                    true
                );
                
                wp_enqueue_style(
                    'rinac-frontend',
                    RINAC_PLUGIN_URL . 'assets/css/frontend.css',
                    array(),
                    RINAC_VERSION
                );
                
                wp_enqueue_style('jquery-ui-style');
                
                // Localizar script
                wp_localize_script('rinac-frontend', 'rinac_frontend', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('rinac_frontend_nonce'),
                    'product_id' => $post->ID,
                    'strings' => array(
                        'select_date' => __('Selecciona una fecha', 'rinac'),
                        'select_time' => __('Selecciona un horario', 'rinac'),
                        'enter_persons' => __('Indica el número de personas', 'rinac'),
                        'no_availability' => __('No hay disponibilidad para esta fecha y hora', 'rinac'),
                        'max_persons_exceeded' => __('El número de personas excede el máximo permitido', 'rinac'),
                        'error_general' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'rinac')
                    ),
                    'calendar_options' => array(
                        'start_day' => get_option('rinac_calendario_start_day', 1),
                        'date_format' => 'yy-mm-dd'
                    )
                ));
            }
        }
    }
    
    /**
     * Remover botón de añadir al carrito por defecto para productos VISITAS
     */
    public function remove_default_add_to_cart() {
        global $product;
        
        if ($product && $product->get_type() === 'visitas') {
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        }
    }
    
    /**
     * Mostrar formulario de reserva para productos VISITAS
     */
    public function show_visitas_booking_form() {
        global $product;
        
        if (!$product || $product->get_type() !== 'visitas') {
            return;
        }
        
        $product_id = $product->get_id();
        $horarios = $this->get_product_horarios($product_id);
        $fechas_disponibles = $this->get_available_dates($product_id);
        
        // Datos para la plantilla
        $template_data = array(
            'product' => $product,
            'product_id' => $product_id,
            'horarios' => $horarios,
            'fechas_disponibles' => $fechas_disponibles,
            'max_personas' => get_post_meta($product_id, '_rinac_max_personas', true) ?: get_option('rinac_max_personas_default', 10),
            'require_phone' => get_option('rinac_require_phone', false),
            'strings' => array(
                'select_date' => __('Selecciona tu reserva', 'rinac'),
                'date_label' => __('Fecha de la visita:', 'rinac'),
                'time_label' => __('Horario:', 'rinac'),
                'persons_label' => __('Número de personas:', 'rinac'),
                'phone_label' => __('Teléfono de contacto:', 'rinac'),
                'comments_label' => __('Comentarios adicionales:', 'rinac'),
                'select_date_first' => __('Primero selecciona una fecha', 'rinac'),
                'select_date_placeholder' => __('Selecciona una fecha', 'rinac'),
                'comments_placeholder' => __('Información adicional sobre tu reserva (opcional)', 'rinac')
            )
        );
        
        // Renderizar usando plantilla
        echo RINAC_Template_Helper::get_template('forms/booking-form.php', $template_data);
    }
    
    /**
     * Obtener horarios de un producto
     */
    private function get_product_horarios($product_id) {
        global $wpdb;
        
        $table_horas = $wpdb->prefix . 'rinac_producto_horas';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_horas WHERE product_id = %d ORDER BY orden",
            $product_id
        ));
    }
    
    /**
     * Obtener fechas disponibles de un producto
     */
    private function get_available_dates($product_id) {
        global $wpdb;
        
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';
        
        // Obtener fechas marcadas como disponibles
        $fechas_disponibles = $wpdb->get_col($wpdb->prepare(
            "SELECT fecha FROM $table_disponibilidad 
             WHERE product_id = %d AND disponible = 1 
             AND fecha >= CURDATE()
             ORDER BY fecha",
            $product_id
        ));
        
        return $fechas_disponibles;
    }
    
    /**
     * Añadir datos de reserva al carrito
     */
    public function add_booking_data_to_cart($cart_item_data, $product_id, $variation_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'visitas') {
            return $cart_item_data;
        }
        
        // Verificar nonce
        if (!isset($_POST['rinac_nonce']) || !wp_verify_nonce($_POST['rinac_nonce'], 'rinac_add_to_cart')) {
            wc_add_notice(__('Error de seguridad. Inténtalo de nuevo.', 'rinac'), 'error');
            return $cart_item_data;
        }
        
        // Validar campos requeridos
        $required_fields = array('rinac_fecha', 'rinac_horario', 'rinac_personas');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wc_add_notice(__('Todos los campos son obligatorios.', 'rinac'), 'error');
                return $cart_item_data;
            }
        }
        
        $fecha = sanitize_text_field($_POST['rinac_fecha']);
        $horario = sanitize_text_field($_POST['rinac_horario']);
        $personas = intval($_POST['rinac_personas']);
        $telefono = isset($_POST['rinac_telefono']) ? sanitize_text_field($_POST['rinac_telefono']) : '';
        $comentarios = isset($_POST['rinac_comentarios']) ? sanitize_textarea_field($_POST['rinac_comentarios']) : '';
        
        // Validar disponibilidad
        if (!$this->validate_booking_availability($product_id, $fecha, $horario, $personas)) {
            return $cart_item_data;
        }
        
        // Añadir datos de reserva al item del carrito
        $cart_item_data['rinac_booking'] = array(
            'fecha' => $fecha,
            'horario' => $horario,
            'personas' => $personas,
            'telefono' => $telefono,
            'comentarios' => $comentarios,
            'product_id' => $product_id
        );
        
        // Hacer único el item del carrito para evitar agrupaciones
        $cart_item_data['unique_key'] = md5(microtime() . rand());
        
        return $cart_item_data;
    }
    
    /**
     * Mostrar datos de reserva en el carrito
     */
    public function display_booking_data_in_cart($item_data, $cart_item) {
        if (isset($cart_item['rinac_booking'])) {
            $booking = $cart_item['rinac_booking'];
            
            $item_data[] = array(
                'key'   => __('Fecha', 'rinac'),
                'value' => date_i18n(get_option('date_format'), strtotime($booking['fecha']))
            );
            
            $item_data[] = array(
                'key'   => __('Horario', 'rinac'),
                'value' => $booking['horario']
            );
            
            $item_data[] = array(
                'key'   => __('Personas', 'rinac'),
                'value' => $booking['personas']
            );
            
            if (!empty($booking['telefono'])) {
                $item_data[] = array(
                    'key'   => __('Teléfono', 'rinac'),
                    'value' => $booking['telefono']
                );
            }
            
            if (!empty($booking['comentarios'])) {
                $item_data[] = array(
                    'key'   => __('Comentarios', 'rinac'),
                    'value' => $booking['comentarios']
                );
            }
        }
        
        return $item_data;
    }
    
    /**
     * Guardar datos de reserva en el pedido
     */
    public function save_booking_data_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['rinac_booking'])) {
            $booking = $values['rinac_booking'];
            
            $item->add_meta_data(__('Fecha', 'rinac'), date_i18n(get_option('date_format'), strtotime($booking['fecha'])));
            $item->add_meta_data(__('Horario', 'rinac'), $booking['horario']);
            $item->add_meta_data(__('Personas', 'rinac'), $booking['personas']);
            
            if (!empty($booking['telefono'])) {
                $item->add_meta_data(__('Teléfono', 'rinac'), $booking['telefono']);
            }
            
            if (!empty($booking['comentarios'])) {
                $item->add_meta_data(__('Comentarios', 'rinac'), $booking['comentarios']);
            }
            
            // Guardar en tabla de reservas
            $this->save_reservation_to_database($order, $item, $booking);
        }
    }
    
    /**
     * Guardar reserva en base de datos
     */
    private function save_reservation_to_database($order, $item, $booking) {
        global $wpdb;
        
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        
        $wpdb->insert(
            $table_reservas,
            array(
                'order_id' => $order->get_id(),
                'order_item_id' => $item->get_id(),
                'product_id' => $booking['product_id'],
                'fecha' => $booking['fecha'],
                'hora_descripcion' => $booking['horario'],
                'numero_personas' => $booking['personas'],
                'status' => 'pending'
            )
        );
    }
    
    /**
     * Validar disponibilidad de reserva
     */
    private function validate_booking_availability($product_id, $fecha, $horario, $personas) {
        // Verificar que la fecha esté disponible
        if (!$this->is_date_available($product_id, $fecha)) {
            wc_add_notice(__('La fecha seleccionada no está disponible.', 'rinac'), 'error');
            return false;
        }
        
        // Verificar capacidad máxima
        $max_personas = $this->get_max_personas_for_slot($product_id, $horario);
        $personas_reservadas = $this->get_reserved_persons($product_id, $fecha, $horario);
        
        if (($personas_reservadas + $personas) > $max_personas) {
            wc_add_notice(
                sprintf(
                    __('No hay suficiente disponibilidad. Máximo %d personas, ya reservadas %d.', 'rinac'),
                    $max_personas,
                    $personas_reservadas
                ),
                'error'
            );
            return false;
        }
        
        return true;
    }
    
    /**
     * Verificar si una fecha está disponible
     */
    private function is_date_available($product_id, $fecha) {
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
     * Obtener máximo de personas para un slot específico
     */
    private function get_max_personas_for_slot($product_id, $horario) {
        global $wpdb;
        
        $table_horas = $wpdb->prefix . 'rinac_producto_horas';
        
        $capacidad = $wpdb->get_var($wpdb->prepare(
            "SELECT capacidad FROM $table_horas WHERE product_id = %d AND hora = %s",
            $product_id,
            $horario
        ));
        
        return $capacidad ? intval($capacidad) : (get_post_meta($product_id, '_rinac_max_personas', true) ?: 10);
    }
    
    /**
     * Obtener personas ya reservadas para un slot
     */
    private function get_reserved_persons($product_id, $fecha, $horario) {
        global $wpdb;
        
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        
        $reservadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(num_personas), 0) FROM $table_reservas 
             WHERE product_id = %d AND fecha_reserva = %s AND hora = %s 
             AND status IN ('pendiente', 'confirmada')",
            $product_id,
            $fecha,
            $horario
        ));
        
        return intval($reservadas);
    }
    
    /**
     * AJAX: Obtener horarios para una fecha
     */
    public function ajax_get_horarios() {
        check_ajax_referer('rinac_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $fecha = sanitize_text_field($_POST['fecha']);
        
        if (!$product_id || !$fecha) {
            wp_send_json_error(__('Datos incompletos', 'rinac'));
        }
        
        $horarios = $this->get_available_times_for_date($product_id, $fecha);
        
        wp_send_json_success($horarios);
    }
    
    /**
     * Obtener horarios disponibles para una fecha específica
     */
    private function get_available_times_for_date($product_id, $fecha) {
        global $wpdb;
        
        $table_horas = $wpdb->prefix . 'rinac_producto_horas';
        
        $horarios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_horas WHERE product_id = %d ORDER BY orden",
            $product_id
        ));
        
        $available_times = array();
        
        foreach ($horarios as $horario) {
            $reservadas = $this->get_reserved_persons($product_id, $fecha, $horario->hora);
            $disponibles = $horario->capacidad - $reservadas;
            
            if ($disponibles > 0) {
                $available_times[] = array(
                    'hora' => $horario->hora,
                    'hora_formatted' => date('H:i', strtotime($horario->hora)),
                    'capacidad' => $horario->capacidad,
                    'disponibles' => $disponibles
                );
            }
        }
        
        return $available_times;
    }
    
    /**
     * AJAX: Verificar disponibilidad
     */
    public function ajax_check_availability() {
        check_ajax_referer('rinac_frontend_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $fecha = sanitize_text_field($_POST['fecha']);
        $horario = sanitize_text_field($_POST['horario']);
        $personas = intval($_POST['personas']);
        
        $max_personas = $this->get_max_personas_for_slot($product_id, $horario);
        $personas_reservadas = $this->get_reserved_persons($product_id, $fecha, $horario);
        $disponibles = $max_personas - $personas_reservadas;
        
        $response = array(
            'available' => $disponibles >= $personas,
            'max_personas' => $max_personas,
            'personas_reservadas' => $personas_reservadas,
            'disponibles' => $disponibles
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Renderizar plantilla con datos
     */
    private function render_template($template_name, $data = array()) {
        $template_path = RINAC_PLUGIN_PATH . 'templates/' . $template_name;
        
        if (file_exists($template_path)) {
            // Extraer variables para la plantilla
            extract($data);
            
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '';
    }
    
    /**
     * Obtener plantilla con fallback al tema
     */
    private function get_template($template_name, $data = array()) {
        // Buscar primero en el tema activo
        $theme_template = get_template_directory() . '/rinac/' . $template_name;
        $child_theme_template = get_stylesheet_directory() . '/rinac/' . $template_name;
        
        $template_path = '';
        
        // Prioridad: tema hijo > tema padre > plugin
        if (file_exists($child_theme_template)) {
            $template_path = $child_theme_template;
        } elseif (file_exists($theme_template)) {
            $template_path = $theme_template;
        } else {
            $template_path = RINAC_PLUGIN_PATH . 'templates/' . $template_name;
        }
        
        if (file_exists($template_path)) {
            extract($data);
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '';
    }
    
    /**
     * Renderizar modal de reserva rápida
     */
    public function render_quick_booking_modal($product_id = null) {
        if (!$product_id) {
            global $product;
            $product_id = $product ? $product->get_id() : 0;
        }
        
        $template_data = array(
            'product_id' => $product_id,
            'horarios' => $this->get_product_horarios($product_id),
            'fechas_disponibles' => $this->get_available_dates($product_id),
            'strings' => array(
                'modal_title' => __('Reserva Rápida', 'rinac'),
                'select_date' => __('Seleccionar fecha', 'rinac'),
                'select_time' => __('Seleccionar horario', 'rinac'),
                'persons' => __('Personas', 'rinac'),
                'book_now' => __('Reservar Ahora', 'rinac'),
                'cancel' => __('Cancelar', 'rinac')
            )
        );
        
        return RINAC_Template_Helper::get_template('modals/quick-booking-modal.php', $template_data);
    }
    
    /**
     * Renderizar modal de detalles de reserva
     */
    public function render_booking_details_modal($booking_data) {
        $template_data = array(
            'booking' => $booking_data,
            'strings' => array(
                'modal_title' => __('Detalles de la Reserva', 'rinac'),
                'date' => __('Fecha', 'rinac'),
                'time' => __('Horario', 'rinac'),
                'persons' => __('Personas', 'rinac'),
                'status' => __('Estado', 'rinac'),
                'comments' => __('Comentarios', 'rinac'),
                'edit' => __('Editar', 'rinac'),
                'cancel' => __('Cancelar Reserva', 'rinac'),
                'close' => __('Cerrar', 'rinac')
            )
        );
        
        return RINAC_Template_Helper::get_template('modals/reserva-details-modal.php', $template_data);
    }
    
    /**
     * AJAX: Renderizar modal de reserva rápida
     */
    public function ajax_render_quick_booking_modal() {
        check_ajax_referer('rinac_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        
        if (!$product_id) {
            wp_die(__('ID de producto inválido', 'rinac'));
        }
        
        $modal_html = $this->render_quick_booking_modal($product_id);
        
        wp_send_json_success(array(
            'html' => $modal_html
        ));
    }
    
    /**
     * AJAX: Renderizar modal de detalles de reserva
     */
    public function ajax_render_booking_details_modal() {
        check_ajax_referer('rinac_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id']);
        
        if (!$booking_id) {
            wp_die(__('ID de reserva inválido', 'rinac'));
        }
        
        // Obtener datos de la reserva
        global $wpdb;
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        
        $booking_data = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, p.post_title as producto_nombre 
             FROM $table_reservas r
             LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
             WHERE r.id = %d",
            $booking_id
        ));
        
        if (!$booking_data) {
            wp_die(__('Reserva no encontrada', 'rinac'));
        }
        
        $modal_html = $this->render_booking_details_modal($booking_data);
        
        wp_send_json_success(array(
            'html' => $modal_html
        ));
    }
}
