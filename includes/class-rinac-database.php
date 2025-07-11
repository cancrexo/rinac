<?php
/**
 * Clase para manejar operaciones de base de datos
 */

if (!defined('ABSPATH')) {
    exit;
}

class RINAC_Database {
    
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
        // Hook para actualizar base de datos si es necesario
        add_action('plugins_loaded', array($this, 'maybe_update_database'));
        
        // Hook para limpiar datos antiguos
        add_action('rinac_cleanup_old_data', array($this, 'cleanup_old_data'));
        
        // Programar limpieza automática si no está programada
        if (!wp_next_scheduled('rinac_cleanup_old_data')) {
            wp_schedule_event(time(), 'daily', 'rinac_cleanup_old_data');
        }
    }
    
    /**
     * Verificar si necesita actualizar la base de datos
     */
    public function maybe_update_database() {
        $current_version = get_option('rinac_db_version', '0.0.0');
        
        if (version_compare($current_version, RINAC_VERSION, '<')) {
            $this->update_database($current_version);
        }
    }
    
    /**
     * Actualizar base de datos
     */
    private function update_database($from_version) {
        global $wpdb;
        
        // Aquí puedes añadir migraciones específicas por versión
        if (version_compare($from_version, '1.0.0', '<')) {
            // Migraciones para versión 1.0.0
            $this->migrate_to_1_0_0();
        }
        
        // Actualizar versión de BD
        update_option('rinac_db_version', RINAC_VERSION);
    }
    
    /**
     * Migración a versión 1.0.0
     */
    private function migrate_to_1_0_0() {
        // Las tablas ya se crean en la instalación inicial
        // Aquí se pueden añadir modificaciones futuras
    }
    
    /**
     * Limpiar datos antiguos
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        // Limpiar reservas muy antiguas (más de 2 años)
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        $old_date = date('Y-m-d', strtotime('-2 years'));
        
        $deleted_reservas = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_reservas WHERE fecha < %s AND status = 'completed'",
            $old_date
        ));
        
        // Limpiar disponibilidad antigua (más de 1 año en el pasado)
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';
        $old_availability_date = date('Y-m-d', strtotime('-1 year'));
        
        $deleted_availability = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_disponibilidad WHERE fecha < %s",
            $old_availability_date
        ));
        
        // Log de limpieza
        if ($deleted_reservas > 0 || $deleted_availability > 0) {
            error_log(sprintf(
                'RINAC: Limpieza automática completada. Reservas eliminadas: %d, Disponibilidad eliminada: %d',
                $deleted_reservas,
                $deleted_availability
            ));
        }
    }
    
    /**
     * Obtener estadísticas de la base de datos
     */
    public function get_database_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Contar rangos horarios
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';
        $stats['rangos_horarios'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_rangos");
        
        // Contar horas totales
        $table_horas = $wpdb->prefix . 'rinac_horas';
        $stats['horas_globales'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_horas");
        
        // Contar productos con configuración RINAC
        $table_producto_horas = $wpdb->prefix . 'rinac_producto_horas';
        $stats['productos_configurados'] = $wpdb->get_var("SELECT COUNT(DISTINCT product_id) FROM $table_producto_horas");
        
        // Contar reservas totales
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        $stats['reservas_totales'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_reservas");
        
        // Contar reservas pendientes
        $stats['reservas_pendientes'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_reservas WHERE status = %s AND fecha >= CURDATE()",
            'pending'
        ));
        
        // Contar reservas futuras
        $stats['reservas_futuras'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_reservas WHERE fecha >= CURDATE()");
        
        // Fechas con disponibilidad configurada
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';
        $stats['fechas_disponibles'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_disponibilidad WHERE disponible = %d AND fecha >= CURDATE()",
            1
        ));
        
        return $stats;
    }
    
    /**
     * Obtener reservas por estado
     */
    public function get_reservations_by_status($start_date = null, $end_date = null) {
        global $wpdb;
        
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        
        $where_conditions = array();
        $prepare_values = array();
        
        if ($start_date) {
            $where_conditions[] = "fecha >= %s";
            $prepare_values[] = $start_date;
        }
        
        if ($end_date) {
            $where_conditions[] = "fecha <= %s";
            $prepare_values[] = $end_date;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT status, COUNT(*) as count, SUM(numero_personas) as total_personas 
                  FROM $table_reservas 
                  $where_clause 
                  GROUP BY status";
        
        if (!empty($prepare_values)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $prepare_values));
        } else {
            $results = $wpdb->get_results($query);
        }
        
        $reservations_by_status = array();
        foreach ($results as $row) {
            $reservations_by_status[$row->status] = array(
                'count' => intval($row->count),
                'total_personas' => intval($row->total_personas)
            );
        }
        
        return $reservations_by_status;
    }
    
    /**
     * Obtener productos más reservados
     */
    public function get_top_booked_products($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        
        $where_conditions = array("status IN ('pending', 'confirmed', 'processing', 'completed')");
        $prepare_values = array();
        
        if ($start_date) {
            $where_conditions[] = "fecha >= %s";
            $prepare_values[] = $start_date;
        }
        
        if ($end_date) {
            $where_conditions[] = "fecha <= %s";
            $prepare_values[] = $end_date;
        }
        
        $prepare_values[] = intval($limit);
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $query = "SELECT r.product_id, 
                         COUNT(*) as total_reservas,
                         SUM(r.numero_personas) as total_personas,
                         p.post_title as product_name
                  FROM $table_reservas r
                  LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
                  $where_clause
                  GROUP BY r.product_id
                  ORDER BY total_reservas DESC
                  LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($query, $prepare_values));
    }
    
    /**
     * Obtener horarios más populares
     */
    public function get_popular_time_slots($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        
        $where_conditions = array("status IN ('pending', 'confirmed', 'processing', 'completed')");
        $prepare_values = array();
        
        if ($start_date) {
            $where_conditions[] = "fecha >= %s";
            $prepare_values[] = $start_date;
        }
        
        if ($end_date) {
            $where_conditions[] = "fecha <= %s";
            $prepare_values[] = $end_date;
        }
        
        $prepare_values[] = intval($limit);
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $query = "SELECT hora_descripcion,
                         COUNT(*) as total_reservas,
                         SUM(numero_personas) as total_personas
                  FROM $table_reservas
                  $where_clause
                  GROUP BY hora_descripcion
                  ORDER BY total_reservas DESC
                  LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($query, $prepare_values));
    }
    
    /**
     * Exportar reservas a CSV
     */
    public function export_reservations_csv($start_date = null, $end_date = null, $status = null) {
        global $wpdb;
        
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        
        $where_conditions = array();
        $prepare_values = array();
        
        if ($start_date) {
            $where_conditions[] = "r.fecha >= %s";
            $prepare_values[] = $start_date;
        }
        
        if ($end_date) {
            $where_conditions[] = "r.fecha <= %s";
            $prepare_values[] = $end_date;
        }
        
        if ($status) {
            $where_conditions[] = "r.status = %s";
            $prepare_values[] = $status;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT r.*, 
                         p.post_title as product_name,
                         o.post_title as order_number
                  FROM $table_reservas r
                  LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
                  LEFT JOIN {$wpdb->posts} o ON r.order_id = o.ID
                  $where_clause
                  ORDER BY r.fecha, r.hora_descripcion";
        
        if (!empty($prepare_values)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $prepare_values), ARRAY_A);
        } else {
            $results = $wpdb->get_results($query, ARRAY_A);
        }
        
        return $results;
    }
    
    /**
     * Optimizar tablas del plugin
     */
    public function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'rinac_rangos_horarios',
            $wpdb->prefix . 'rinac_horas',
            $wpdb->prefix . 'rinac_disponibilidad',
            $wpdb->prefix . 'rinac_producto_horas',
            $wpdb->prefix . 'rinac_reservas'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
        
        return true;
    }
    
    /**
     * Verificar integridad de la base de datos
     */
    public function check_database_integrity() {
        global $wpdb;
        
        $issues = array();
        
        // Verificar productos huérfanos en reservas
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        $orphan_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_reservas r 
             LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID 
             WHERE p.ID IS NULL"
        );
        
        if ($orphan_products > 0) {
            $issues[] = sprintf(__('Se encontraron %d reservas con productos que ya no existen.', 'rinac'), $orphan_products);
        }
        
        // Verificar pedidos huérfanos en reservas
        $orphan_orders = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_reservas r 
             LEFT JOIN {$wpdb->posts} o ON r.order_id = o.ID 
             WHERE o.ID IS NULL"
        );
        
        if ($orphan_orders > 0) {
            $issues[] = sprintf(__('Se encontraron %d reservas con pedidos que ya no existen.', 'rinac'), $orphan_orders);
        }
        
        // Verificar configuración de horarios huérfana
        $table_producto_horas = $wpdb->prefix . 'rinac_producto_horas';
        $orphan_product_hours = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_producto_horas ph 
             LEFT JOIN {$wpdb->posts} p ON ph.product_id = p.ID 
             WHERE p.ID IS NULL"
        );
        
        if ($orphan_product_hours > 0) {
            $issues[] = sprintf(__('Se encontraron %d configuraciones de horarios para productos que ya no existen.', 'rinac'), $orphan_product_hours);
        }
        
        return $issues;
    }
    
    /**
     * Reparar problemas de integridad
     */
    public function repair_database_integrity() {
        global $wpdb;
        
        $repaired = array();
        
        // Limpiar reservas huérfanas
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        $deleted_reservas = $wpdb->query(
            "DELETE r FROM $table_reservas r 
             LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID 
             WHERE p.ID IS NULL"
        );
        
        if ($deleted_reservas > 0) {
            $repaired[] = sprintf(__('Se eliminaron %d reservas huérfanas.', 'rinac'), $deleted_reservas);
        }
        
        // Limpiar configuración de horarios huérfana
        $table_producto_horas = $wpdb->prefix . 'rinac_producto_horas';
        $deleted_hours = $wpdb->query(
            "DELETE ph FROM $table_producto_horas ph 
             LEFT JOIN {$wpdb->posts} p ON ph.product_id = p.ID 
             WHERE p.ID IS NULL"
        );
        
        if ($deleted_hours > 0) {
            $repaired[] = sprintf(__('Se eliminaron %d configuraciones de horarios huérfanas.', 'rinac'), $deleted_hours);
        }
        
        // Limpiar disponibilidad huérfana
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';
        $deleted_availability = $wpdb->query(
            "DELETE d FROM $table_disponibilidad d 
             LEFT JOIN {$wpdb->posts} p ON d.product_id = p.ID 
             WHERE p.ID IS NULL"
        );
        
        if ($deleted_availability > 0) {
            $repaired[] = sprintf(__('Se eliminaron %d registros de disponibilidad huérfanos.', 'rinac'), $deleted_availability);
        }
        
        return $repaired;
    }
}
