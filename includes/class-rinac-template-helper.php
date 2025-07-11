<?php
/**
 * Clase Helper para renderizado de plantillas RINAC
 */

if (!defined('ABSPATH')) {
    exit;
}

class RINAC_Template_Helper {
    
    /**
     * Renderizar plantilla con datos
     */
    public static function render_template($template_name, $data = array()) {
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
    public static function get_template($template_name, $data = array()) {
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
            // Aplicar filtro para permitir modificaciones
            $template_path = apply_filters('rinac_template_path', $template_path, $template_name);
            
            // Aplicar filtro a los datos
            $data = apply_filters('rinac_template_data', $data, $template_name);
            
            extract($data);
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '';
    }
    
    /**
     * Incluir plantilla directamente (sin buffer)
     */
    public static function include_template($template_name, $data = array()) {
        $template_path = self::locate_template($template_name);
        
        if ($template_path) {
            // Aplicar filtro a los datos
            $data = apply_filters('rinac_template_data', $data, $template_name);
            
            extract($data);
            include $template_path;
        }
    }
    
    /**
     * Localizar plantilla con fallback
     */
    public static function locate_template($template_name) {
        // Buscar primero en el tema activo
        $theme_template = get_template_directory() . '/rinac/' . $template_name;
        $child_theme_template = get_stylesheet_directory() . '/rinac/' . $template_name;
        
        // Prioridad: tema hijo > tema padre > plugin
        if (file_exists($child_theme_template)) {
            return $child_theme_template;
        } elseif (file_exists($theme_template)) {
            return $theme_template;
        } else {
            $plugin_template = RINAC_PLUGIN_PATH . 'templates/' . $template_name;
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return false;
    }
    
    /**
     * Obtener datos comunes para plantillas
     */
    public static function get_common_template_data() {
        return array(
            'plugin_url' => RINAC_PLUGIN_URL,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rinac_nonce'),
            'current_user_id' => get_current_user_id(),
            'is_admin' => is_admin(),
            'strings' => array(
                'loading' => __('Cargando...', 'rinac'),
                'error' => __('Ha ocurrido un error', 'rinac'),
                'success' => __('Operación exitosa', 'rinac'),
                'cancel' => __('Cancelar', 'rinac'),
                'confirm' => __('Confirmar', 'rinac'),
                'yes' => __('Sí', 'rinac'),
                'no' => __('No', 'rinac'),
                'save' => __('Guardar', 'rinac'),
                'edit' => __('Editar', 'rinac'),
                'delete' => __('Eliminar', 'rinac'),
                'required' => __('Este campo es obligatorio', 'rinac')
            )
        );
    }
    
    /**
     * Renderizar modal base con contenido
     */
    public static function render_modal($modal_id, $title, $content, $size = 'medium') {
        $modal_data = array(
            'modal_id' => $modal_id,
            'title' => $title,
            'content' => $content,
            'size' => $size,
            'strings' => self::get_common_template_data()['strings']
        );
        
        return self::get_template('modals/base-modal.php', $modal_data);
    }
    
    /**
     * Formatear fecha para mostrar
     */
    public static function format_date($date, $format = null) {
        if (!$format) {
            $format = get_option('date_format', 'd/m/Y');
        }
        
        if (is_string($date)) {
            $date = DateTime::createFromFormat('Y-m-d', $date);
        }
        
        return $date ? $date->format($format) : '';
    }
    
    /**
     * Formatear hora para mostrar
     */
    public static function format_time($time, $format = 'H:i') {
        if (is_string($time)) {
            $time = DateTime::createFromFormat('H:i:s', $time);
        }
        
        return $time ? $time->format($format) : '';
    }
    
    /**
     * Obtener clases CSS para estado de reserva
     */
    public static function get_booking_status_class($status) {
        $classes = array(
            'pendiente' => 'rinac-status-pending',
            'confirmada' => 'rinac-status-confirmed',
            'completada' => 'rinac-status-completed',
            'cancelada' => 'rinac-status-cancelled',
            'no_presentado' => 'rinac-status-no-show'
        );
        
        return isset($classes[$status]) ? $classes[$status] : 'rinac-status-unknown';
    }
    
    /**
     * Obtener texto legible para estado de reserva
     */
    public static function get_booking_status_text($status) {
        $statuses = array(
            'pendiente' => __('Pendiente', 'rinac'),
            'confirmada' => __('Confirmada', 'rinac'),
            'completada' => __('Completada', 'rinac'),
            'cancelada' => __('Cancelada', 'rinac'),
            'no_presentado' => __('No se presentó', 'rinac')
        );
        
        return isset($statuses[$status]) ? $statuses[$status] : __('Desconocido', 'rinac');
    }
}
