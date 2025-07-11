<?php
/**
 * Clase para manejar la instalación y configuración inicial del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RINAC_Install {
    
    /**
     * Crear tablas de base de datos
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla para rangos horarios globales
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';
        $sql_rangos = "CREATE TABLE $table_rangos (
            id int(11) NOT NULL AUTO_INCREMENT,
            nombre varchar(30) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tabla para horas dentro de cada rango
        $table_horas = $wpdb->prefix . 'rinac_horas';
        $sql_horas = "CREATE TABLE $table_horas (
            id int(11) NOT NULL AUTO_INCREMENT,
            rango_id int(11) NOT NULL,
            hora time NOT NULL,
            capacidad int(11) NOT NULL DEFAULT 10,
            orden int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY rango_id (rango_id)
        ) $charset_collate;";
        
        // Tabla para disponibilidad de fechas por producto
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';
        $sql_disponibilidad = "CREATE TABLE $table_disponibilidad (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            fecha date NOT NULL,
            disponible tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_fecha (product_id, fecha),
            KEY product_id (product_id),
            KEY fecha (fecha)
        ) $charset_collate;";
        
        // Tabla para horas asignadas a productos específicos
        $table_producto_horas = $wpdb->prefix . 'rinac_producto_horas';
        $sql_producto_horas = "CREATE TABLE $table_producto_horas (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            hora time NOT NULL,
            capacidad int(11) NOT NULL DEFAULT 10,
            orden int(11) NOT NULL DEFAULT 0,
            rango_id int(11) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY hora (hora)
        ) $charset_collate;";
        
        // Tabla para reservas realizadas
        $table_reservas = $wpdb->prefix . 'rinac_reservas';
        $sql_reservas = "CREATE TABLE $table_reservas (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NULL,
            order_item_id int(11) NULL,
            product_id int(11) NOT NULL,
            customer_id int(11) NULL,
            fecha_reserva date NOT NULL,
            hora time NOT NULL,
            num_personas int(11) NOT NULL DEFAULT 1,
            email varchar(255) NULL,
            telefono varchar(50) NULL,
            comentarios text NULL,
            status varchar(20) NOT NULL DEFAULT 'pendiente',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY customer_id (customer_id),
            KEY fecha_reserva (fecha_reserva),
            KEY status (status)
        ) $charset_collate;";
        
        // Incluir la función dbDelta de WordPress
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Ejecutar las consultas
        dbDelta($sql_rangos);
        dbDelta($sql_horas);
        dbDelta($sql_disponibilidad);
        dbDelta($sql_producto_horas);
        dbDelta($sql_reservas);
        
        // Guardar versión de la base de datos
        update_option('rinac_db_version', RINAC_VERSION);
    }
    
    /**
     * Crear opciones por defecto
     */
    public static function create_default_options() {
        // Opciones generales del plugin
        $default_options = array(
            'maximo_personas_hora_default' => 10,
            'calendario_rango_anos' => 2,
            'email_notifications' => true,
            'require_phone' => false,
            'calendario_start_day' => 1 // 1 = Lunes, 0 = Domingo
        );
        
        foreach ($default_options as $option_name => $option_value) {
            if (!get_option('rinac_' . $option_name)) {
                add_option('rinac_' . $option_name, $option_value);
            }
        }
        
        // Crear rango horario de ejemplo
        self::create_sample_data();
    }
    
    /**
     * Crear datos de ejemplo
     */
    private static function create_sample_data() {
        global $wpdb;
        
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';
        $table_horas = $wpdb->prefix . 'rinac_horas';
        
        // Verificar si ya existen rangos
        $existing_ranges = $wpdb->get_var("SELECT COUNT(*) FROM $table_rangos");
        
        if ($existing_ranges == 0) {
            // Crear rango de ejemplo
            $wpdb->insert(
                $table_rangos,
                array(
                    'nombre' => 'Horario Estándar'
                )
            );
            
            $rango_id = $wpdb->insert_id;
            
            // Crear horas de ejemplo
            $horas_ejemplo = array(
                array('descripcion' => '10:00h - 11:00h', 'orden' => 1),
                array('descripcion' => '11:30h - 12:30h', 'orden' => 2),
                array('descripcion' => '16:00h - 17:00h', 'orden' => 3),
                array('descripcion' => '17:30h - 18:30h', 'orden' => 4)
            );
            
            $maximo_default = get_option('rinac_maximo_personas_hora_default', 10);
            
            foreach ($horas_ejemplo as $hora) {
                $wpdb->insert(
                    $table_horas,
                    array(
                        'rango_id' => $rango_id,
                        'descripcion' => $hora['descripcion'],
                        'maximo_personas_slot' => $maximo_default,
                        'orden' => $hora['orden']
                    )
                );
            }
        }
    }
    
    /**
     * Eliminar tablas del plugin (usado en desinstalación)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'rinac_reservas',
            $wpdb->prefix . 'rinac_producto_horas',
            $wpdb->prefix . 'rinac_disponibilidad',
            $wpdb->prefix . 'rinac_horas',
            $wpdb->prefix . 'rinac_rangos_horarios'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Eliminar opciones
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rinac_%'");
    }
    
    /**
     * Activar plugin
     */
    public static function activate() {
        // Crear tablas de base de datos
        self::create_tables();
        
        // Configuración inicial
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Desactivar plugin
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Configurar opciones por defecto
     */
    private static function set_default_options() {
        // Configuraciones por defecto
        $default_options = array(
            'rinac_maximo_personas_hora_default' => 10,
            'rinac_calendario_rango_anos' => 2,
            'rinac_email_notifications' => true,
            'rinac_require_phone' => false,
            'rinac_calendario_start_day' => 1
        );
        
        foreach ($default_options as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                update_option($option_name, $default_value);
            }
        }
        
        // Actualizar versión de la base de datos
        update_option('rinac_db_version', '1.0.0');
    }
}
