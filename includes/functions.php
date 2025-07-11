<?php
/**
 * Funciones globales para el plugin RINAC
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderizar plantilla RINAC
 */
function rinac_get_template($template_name, $data = array()) {
    return RINAC_Template_Helper::get_template($template_name, $data);
}

/**
 * Incluir plantilla RINAC directamente
 */
function rinac_include_template($template_name, $data = array()) {
    RINAC_Template_Helper::include_template($template_name, $data);
}

/**
 * Localizar plantilla RINAC
 */
function rinac_locate_template($template_name) {
    return RINAC_Template_Helper::locate_template($template_name);
}

/**
 * Renderizar modal RINAC
 */
function rinac_render_modal($modal_id, $title, $content, $size = 'medium') {
    return RINAC_Template_Helper::render_modal($modal_id, $title, $content, $size);
}

/**
 * Formatear fecha RINAC
 */
function rinac_format_date($date, $format = null) {
    return RINAC_Template_Helper::format_date($date, $format);
}

/**
 * Formatear hora RINAC
 */
function rinac_format_time($time, $format = 'H:i') {
    return RINAC_Template_Helper::format_time($time, $format);
}

/**
 * Obtener clase CSS para estado de reserva
 */
function rinac_get_booking_status_class($status) {
    return RINAC_Template_Helper::get_booking_status_class($status);
}

/**
 * Obtener texto de estado de reserva
 */
function rinac_get_booking_status_text($status) {
    return RINAC_Template_Helper::get_booking_status_text($status);
}

/**
 * Verificar si es un producto de tipo VISITAS
 */
function rinac_is_visitas_product($product) {
    if (is_numeric($product)) {
        $product = wc_get_product($product);
    }
    
    return $product && $product->get_type() === 'visitas';
}

/**
 * Obtener datos de reserva de un item del carrito
 */
function rinac_get_cart_item_booking_data($cart_item) {
    return isset($cart_item['rinac_booking_data']) ? $cart_item['rinac_booking_data'] : false;
}

/**
 * Obtener horarios disponibles para una fecha y producto
 */
function rinac_get_available_times($product_id, $date) {
    global $wpdb;
    
    $table_horas = $wpdb->prefix . 'rinac_producto_horas';
    $table_reservas = $wpdb->prefix . 'rinac_reservas';
    
    // Obtener horarios del producto
    $horarios = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_horas WHERE product_id = %d ORDER BY orden",
        $product_id
    ));
    
    $available_times = array();
    
    foreach ($horarios as $horario) {
        // Verificar disponibilidad
        $reservas_existentes = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(num_personas), 0) FROM $table_reservas 
             WHERE product_id = %d AND fecha_reserva = %s AND hora = %s 
             AND status IN ('pendiente', 'confirmada')",
            $product_id,
            $date,
            $horario->hora
        ));
        
        $disponibles = $horario->capacidad - $reservas_existentes;
        
        if ($disponibles > 0) {
            $available_times[] = array(
                'hora' => $horario->hora,
                'capacidad' => $horario->capacidad,
                'disponibles' => $disponibles,
                'formatted_time' => rinac_format_time($horario->hora)
            );
        }
    }
    
    return $available_times;
}

/**
 * Verificar disponibilidad para una reserva
 */
function rinac_check_booking_availability($product_id, $date, $time, $num_personas) {
    global $wpdb;
    
    $table_horas = $wpdb->prefix . 'rinac_producto_horas';
    $table_reservas = $wpdb->prefix . 'rinac_reservas';
    
    // Obtener capacidad del horario
    $horario = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_horas WHERE product_id = %d AND hora = %s",
        $product_id,
        $time
    ));
    
    if (!$horario) {
        return array(
            'available' => false,
            'message' => __('Horario no válido', 'rinac')
        );
    }
    
    // Verificar reservas existentes
    $reservas_existentes = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(num_personas), 0) FROM $table_reservas 
         WHERE product_id = %d AND fecha_reserva = %s AND hora = %s 
         AND status IN ('pendiente', 'confirmada')",
        $product_id,
        $date,
        $time
    ));
    
    $disponibles = $horario->capacidad - $reservas_existentes;
    
    if ($num_personas > $disponibles) {
        return array(
            'available' => false,
            'message' => sprintf(__('Solo quedan %d plazas disponibles', 'rinac'), $disponibles)
        );
    }
    
    return array(
        'available' => true,
        'message' => sprintf(__('%d plazas disponibles', 'rinac'), $disponibles)
    );
}

/**
 * Obtener resumen de una reserva
 */
function rinac_get_booking_summary($booking_data) {
    if (!is_array($booking_data)) {
        return '';
    }
    
    $summary = array();
    
    if (isset($booking_data['fecha'])) {
        $summary[] = '<strong>' . __('Fecha:', 'rinac') . '</strong> ' . rinac_format_date($booking_data['fecha']);
    }
    
    if (isset($booking_data['hora'])) {
        $summary[] = '<strong>' . __('Hora:', 'rinac') . '</strong> ' . rinac_format_time($booking_data['hora']);
    }
    
    if (isset($booking_data['num_personas'])) {
        $summary[] = '<strong>' . __('Personas:', 'rinac') . '</strong> ' . intval($booking_data['num_personas']);
    }
    
    if (isset($booking_data['telefono']) && !empty($booking_data['telefono'])) {
        $summary[] = '<strong>' . __('Teléfono:', 'rinac') . '</strong> ' . esc_html($booking_data['telefono']);
    }
    
    if (isset($booking_data['comentarios']) && !empty($booking_data['comentarios'])) {
        $summary[] = '<strong>' . __('Comentarios:', 'rinac') . '</strong> ' . esc_html($booking_data['comentarios']);
    }
    
    return implode('<br>', $summary);
}

/**
 * Generar nonce para RINAC
 */
function rinac_create_nonce($action = 'rinac_nonce') {
    return wp_create_nonce($action);
}

/**
 * Verificar nonce para RINAC
 */
function rinac_verify_nonce($nonce, $action = 'rinac_nonce') {
    return wp_verify_nonce($nonce, $action);
}

/**
 * Log de errores RINAC
 */
function rinac_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf('[RINAC] [%s] %s', strtoupper($level), $message));
    }
}

/**
 * Obtener configuración del plugin
 */
function rinac_get_option($option_name, $default = false) {
    return get_option('rinac_' . $option_name, $default);
}

/**
 * Actualizar configuración del plugin
 */
function rinac_update_option($option_name, $value) {
    return update_option('rinac_' . $option_name, $value);
}
