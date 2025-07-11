<?php
/**
 * Clase para manejar funcionalidades del calendario
 */

if (!defined('ABSPATH')) {
    exit;
}

class RINAC_Calendar {
    
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
        // AJAX para guardar disponibilidad del calendario
        add_action('wp_ajax_rinac_save_calendar_data', array($this, 'ajax_save_calendar_data'));
        
        // AJAX para obtener datos del calendario
        add_action('wp_ajax_rinac_get_calendar_data', array($this, 'ajax_get_calendar_data'));
        
        // AJAX para operaciones masivas de calendario
        add_action('wp_ajax_rinac_bulk_calendar_operation', array($this, 'ajax_bulk_calendar_operation'));
    }
    
    /**
     * Obtener fechas disponibles para un producto
     */
    public function get_product_availability($product_id, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = current_time('Y-m-d');
        }
        
        if (!$end_date) {
            $years = get_option('rinac_calendario_rango_anos', 2);
            $end_date = date('Y-m-d', strtotime("+{$years} years"));
        }
        
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha, disponible FROM $table_disponibilidad 
             WHERE product_id = %d 
             AND fecha BETWEEN %s AND %s
             ORDER BY fecha",
            $product_id,
            $start_date,
            $end_date
        ));
        
        $availability = array();
        foreach ($results as $row) {
            $availability[$row->fecha] = intval($row->disponible);
        }
        
        return $availability;
    }
    
    /**
     * Establecer disponibilidad para una fecha específica
     */
    public function set_date_availability($product_id, $fecha, $disponible) {
        global $wpdb;
        
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';
        
        // Usar INSERT ... ON DUPLICATE KEY UPDATE
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_disponibilidad (product_id, fecha, disponible) 
             VALUES (%d, %s, %d)
             ON DUPLICATE KEY UPDATE disponible = %d",
            $product_id,
            $fecha,
            $disponible,
            $disponible
        ));
        
        return $result !== false;
    }
    
    /**
     * Establecer disponibilidad para un rango de fechas
     */
    public function set_date_range_availability($product_id, $start_date, $end_date, $disponible) {
        $current_date = $start_date;
        $success_count = 0;
        
        while (strtotime($current_date) <= strtotime($end_date)) {
            if ($this->set_date_availability($product_id, $current_date, $disponible)) {
                $success_count++;
            }
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
        
        return $success_count;
    }
    
    /**
     * Obtener estadísticas de reservas para un producto
     */
    public function get_booking_statistics($product_id, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = current_time('Y-m-d');
        }
        
        if (!$end_date) {
            $end_date = date('Y-m-d', strtotime('+1 year'));
        }
        
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        
        // Obtener reservas por fecha
        $reservas_por_fecha = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha, 
                    COUNT(*) as total_reservas,
                    SUM(numero_personas) as total_personas
             FROM $table_reservas 
             WHERE product_id = %d 
             AND fecha BETWEEN %s AND %s
             AND status IN ('pending', 'confirmed', 'processing', 'completed')
             GROUP BY fecha
             ORDER BY fecha",
            $product_id,
            $start_date,
            $end_date
        ));
        
        $statistics = array();
        foreach ($reservas_por_fecha as $row) {
            $statistics[$row->fecha] = array(
                'reservas' => intval($row->total_reservas),
                'personas' => intval($row->total_personas)
            );
        }
        
        return $statistics;
    }
    
    /**
     * Generar calendario HTML para administración
     */
    public function render_admin_calendar($product_id, $year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('n');
        
        $start_date = $year . '-' . sprintf('%02d', $month) . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $availability = $this->get_product_availability($product_id, $start_date, $end_date);
        $statistics = $this->get_booking_statistics($product_id, $start_date, $end_date);
        
        $first_day = date('w', strtotime($start_date));
        $days_in_month = date('t', strtotime($start_date));
        
        // Ajustar primer día según configuración
        $start_day = get_option('rinac_calendario_start_day', 1);
        if ($start_day == 1) { // Empezar en lunes
            $first_day = ($first_day == 0) ? 6 : $first_day - 1;
        }
        
        $calendar_html = '<div class="rinac-calendar" data-year="' . $year . '" data-month="' . $month . '">';
        
        // Cabecera del calendario
        $calendar_html .= '<div class="rinac-calendar-header">';
        $calendar_html .= '<button type="button" class="rinac-calendar-nav" data-action="prev">&lt;</button>';
        $calendar_html .= '<h3>' . date_i18n('F Y', strtotime($start_date)) . '</h3>';
        $calendar_html .= '<button type="button" class="rinac-calendar-nav" data-action="next">&gt;</button>';
        $calendar_html .= '</div>';
        
        // Días de la semana
        $days_of_week = array(
            __('Dom', 'rinac'), __('Lun', 'rinac'), __('Mar', 'rinac'), 
            __('Mié', 'rinac'), __('Jue', 'rinac'), __('Vie', 'rinac'), __('Sáb', 'rinac')
        );
        
        if ($start_day == 1) {
            // Reordenar para empezar en lunes
            $days_of_week = array_slice($days_of_week, 1) + array_slice($days_of_week, 0, 1);
        }
        
        $calendar_html .= '<div class="rinac-calendar-weekdays">';
        foreach ($days_of_week as $day) {
            $calendar_html .= '<div class="rinac-weekday">' . $day . '</div>';
        }
        $calendar_html .= '</div>';
        
        // Días del mes
        $calendar_html .= '<div class="rinac-calendar-days">';
        
        // Días vacíos al inicio
        for ($i = 0; $i < $first_day; $i++) {
            $calendar_html .= '<div class="rinac-day rinac-day-empty"></div>';
        }
        
        // Días del mes
        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date = $year . '-' . sprintf('%02d', $month) . '-' . sprintf('%02d', $day);
            $is_past = strtotime($current_date) < strtotime(date('Y-m-d'));
            
            $disponible = isset($availability[$current_date]) ? $availability[$current_date] : 0;
            $reservas_info = isset($statistics[$current_date]) ? $statistics[$current_date] : array('reservas' => 0, 'personas' => 0);
            
            $classes = array('rinac-day');
            if ($is_past) $classes[] = 'rinac-day-past';
            if ($disponible) $classes[] = 'rinac-day-available';
            else $classes[] = 'rinac-day-unavailable';
            
            $calendar_html .= '<div class="' . implode(' ', $classes) . '" data-date="' . $current_date . '">';
            $calendar_html .= '<span class="rinac-day-number">' . $day . '</span>';
            
            if ($reservas_info['reservas'] > 0) {
                $calendar_html .= '<span class="rinac-day-bookings" title="' . 
                    sprintf(__('%d reservas, %d personas', 'rinac'), $reservas_info['reservas'], $reservas_info['personas']) . '">' .
                    $reservas_info['reservas'] . '</span>';
            }
            
            $calendar_html .= '</div>';
        }
        
        $calendar_html .= '</div>';
        $calendar_html .= '</div>';
        
        return $calendar_html;
    }
    
    /**
     * Generar datos JSON para calendario de frontend
     */
    public function get_frontend_calendar_data($product_id) {
        $years = get_option('rinac_calendario_rango_anos', 2);
        $start_date = current_time('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$years} years"));
        
        $availability = $this->get_product_availability($product_id, $start_date, $end_date);
        
        // Filtrar solo fechas disponibles
        $available_dates = array();
        foreach ($availability as $date => $disponible) {
            if ($disponible == 1) {
                $available_dates[] = $date;
            }
        }
        
        return $available_dates;
    }
    
    /**
     * AJAX: Guardar datos del calendario
     */
    public function ajax_save_calendar_data() {
        check_ajax_referer('rinac_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }
        
        $product_id = intval($_POST['product_id']);
        $date = sanitize_text_field($_POST['date']);
        $disponible = intval($_POST['disponible']);
        
        $success = $this->set_date_availability($product_id, $date, $disponible);
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Disponibilidad actualizada correctamente.', 'rinac'),
                'date' => $date,
                'disponible' => $disponible
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error al actualizar la disponibilidad.', 'rinac')
            ));
        }
    }
    
    /**
     * AJAX: Obtener datos del calendario
     */
    public function ajax_get_calendar_data() {
        check_ajax_referer('rinac_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }
        
        $product_id = intval($_POST['product_id']);
        $year = intval($_POST['year']);
        $month = intval($_POST['month']);
        
        $calendar_html = $this->render_admin_calendar($product_id, $year, $month);
        
        wp_send_json_success(array(
            'calendar_html' => $calendar_html
        ));
    }
    
    /**
     * AJAX: Operaciones masivas del calendario
     */
    public function ajax_bulk_calendar_operation() {
        check_ajax_referer('rinac_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }
        
        $product_id = intval($_POST['product_id']);
        $operation = sanitize_text_field($_POST['operation']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        switch ($operation) {
            case 'enable_range':
                $count = $this->set_date_range_availability($product_id, $start_date, $end_date, 1);
                $message = sprintf(__('Se habilitaron %d fechas.', 'rinac'), $count);
                break;
                
            case 'disable_range':
                $count = $this->set_date_range_availability($product_id, $start_date, $end_date, 0);
                $message = sprintf(__('Se deshabilitaron %d fechas.', 'rinac'), $count);
                break;
                
            case 'enable_weekends':
                $count = $this->set_weekends_availability($product_id, $start_date, $end_date, 1);
                $message = sprintf(__('Se habilitaron %d fines de semana.', 'rinac'), $count);
                break;
                
            case 'disable_weekends':
                $count = $this->set_weekends_availability($product_id, $start_date, $end_date, 0);
                $message = sprintf(__('Se deshabilitaron %d fines de semana.', 'rinac'), $count);
                break;
                
            default:
                wp_send_json_error(array('message' => __('Operación no válida.', 'rinac')));
                return;
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Establecer disponibilidad para fines de semana
     */
    private function set_weekends_availability($product_id, $start_date, $end_date, $disponible) {
        $current_date = $start_date;
        $success_count = 0;
        
        while (strtotime($current_date) <= strtotime($end_date)) {
            $day_of_week = date('w', strtotime($current_date));
            
            // 0 = Domingo, 6 = Sábado
            if ($day_of_week == 0 || $day_of_week == 6) {
                if ($this->set_date_availability($product_id, $current_date, $disponible)) {
                    $success_count++;
                }
            }
            
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
        
        return $success_count;
    }
}
