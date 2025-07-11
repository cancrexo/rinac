<?php
/**
 * Clase para manejar el tipo de producto VISITAS
 */

if (!defined('ABSPATH')) {
    exit;
}

class RINAC_Product_Type {
    
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
        // Añadir tipo de producto VISITAS
        add_filter('product_type_selector', array($this, 'add_visitas_product_type'));
        
        // Manejar el tipo de producto en el save
        add_action('woocommerce_process_product_meta', array($this, 'save_visitas_product_data'));
        
        // Añadir pestañas específicas para productos VISITAS
        add_filter('woocommerce_product_data_tabs', array($this, 'add_visitas_product_tabs'));
        
        // Mostrar paneles de las pestañas
        add_action('woocommerce_product_data_panels', array($this, 'show_visitas_product_panels'));
        
        // Modificar campos que se muestran según el tipo de producto
        add_action('admin_footer', array($this, 'visitas_product_type_options'));
        
        // Asegurar que los productos VISITAS son virtuales
        add_filter('woocommerce_product_class', array($this, 'set_visitas_product_class'), 10, 2);
        
        // Establecer valores por defecto para nuevos productos VISITAS
        add_action('wp_insert_post', array($this, 'set_default_values_for_new_visitas'), 10, 3);
    }
    
    /**
     * Añadir tipo de producto VISITAS al selector
     */
    public function add_visitas_product_type($types) {
        $types['visitas'] = __('Visitas', 'rinac');
        return $types;
    }
    
    /**
     * Añadir pestañas específicas para productos VISITAS
     */
    public function add_visitas_product_tabs($tabs) {
        // Pestaña de Configuración General
        $tabs['rinac_general'] = array(
            'label'    => __('Configuración RINAC', 'rinac'),
            'target'   => 'rinac_general_product_data',
            'class'    => array('show_if_visitas'),
            'priority' => 21,
        );
        
        // Pestaña de Calendario
        $tabs['rinac_calendar'] = array(
            'label'    => __('Calendario', 'rinac'),
            'target'   => 'rinac_calendar_product_data',
            'class'    => array('show_if_visitas'),
            'priority' => 22,
        );
        
        // Pestaña de Horarios
        $tabs['rinac_horarios'] = array(
            'label'    => __('Horarios', 'rinac'),
            'target'   => 'rinac_horarios_product_data',
            'class'    => array('show_if_visitas'),
            'priority' => 23,
        );
        
        return $tabs;
    }
    
    /**
     * Mostrar paneles de las pestañas
     */
    public function show_visitas_product_panels() {
        global $post;
        
        // Panel de Configuración General
        echo '<div id="rinac_general_product_data" class="panel woocommerce_options_panel hidden">';
        
        // Obtener valor actual o usar el valor por defecto de configuración
        $current_value = get_post_meta($post->ID, '_rinac_maximo_personas_hora', true);
        $default_value = get_option('rinac_max_personas_default', 10);
        $value = $current_value ? $current_value : $default_value;
        
        woocommerce_wp_text_input(array(
            'id'          => '_rinac_maximo_personas_hora',
            'label'       => __('Máximo personas por hora', 'rinac'),
            'value'       => $value,
            'placeholder' => $default_value,
            'desc_tip'    => true,
            'description' => __('Número máximo de personas que pueden reservar por hora', 'rinac'),
            'type'        => 'number',
            'custom_attributes' => array(
                'step' => '1',
                'min'  => '1'
            )
        ));
        
        echo '</div>';
        
        // Panel de Calendario
        echo '<div id="rinac_calendar_product_data" class="panel woocommerce_options_panel hidden">';
        echo '<div class="options_group">';
        echo '<h4>' . __('Gestión de Disponibilidad', 'rinac') . '</h4>';
        echo '<div id="rinac-calendar-container">';
        echo '<p>' . __('Haz clic en las fechas para alternar su disponibilidad:', 'rinac') . '</p>';
        echo '<div id="rinac-calendar"></div>';
        echo '<input type="hidden" id="rinac_disponibilidad_data" name="_rinac_disponibilidad" value="" />';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Panel de Horarios
        echo '<div id="rinac_horarios_product_data" class="panel woocommerce_options_panel hidden">';
        echo '<div class="options_group">';
        echo '<h4>' . __('Configuración de Horarios', 'rinac') . '</h4>';
        
        // Selector de rango horario predefinido
        echo '<p class="form-field">';
        echo '<label for="rinac_rango_selector">' . __('Añadir rango predefinido:', 'rinac') . '</label>';
        echo '<select id="rinac_rango_selector">';
        echo '<option value="">' . __('Selecciona un rango...', 'rinac') . '</option>';
        
        // Obtener rangos horarios disponibles
        $rangos = $this->get_rangos_horarios();
        foreach ($rangos as $rango) {
            echo '<option value="' . esc_attr($rango->id) . '">' . esc_html($rango->nombre) . '</option>';
        }
        
        echo '</select>';
        echo '<button type="button" id="rinac_add_rango" class="button">' . __('Añadir Rango', 'rinac') . '</button>';
        echo '</p>';
        
        // Contenedor para horarios del producto
        echo '<div id="rinac-horarios-container">';
        echo '<h5>' . __('Horarios del producto:', 'rinac') . '</h5>';
        echo '<div id="rinac-horarios-list" class="rinac-sortable">';
        
        // Mostrar horarios existentes del producto
        $this->show_existing_horarios($post->ID);
        
        echo '</div>';
        echo '<button type="button" id="rinac_add_hora_manual" class="button">' . __('Añadir Hora Manual', 'rinac') . '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Obtener rangos horarios de la base de datos
     */
    private function get_rangos_horarios() {
        global $wpdb;
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';
        return $wpdb->get_results("SELECT * FROM $table_rangos ORDER BY nombre");
    }
    
    /**
     * Mostrar horarios existentes del producto
     */
    private function show_existing_horarios($product_id) {
        global $wpdb;
        $table_horas = $wpdb->prefix . 'rinac_producto_horas';
        
        $horarios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_horas WHERE product_id = %d ORDER BY orden",
            $product_id
        ));
        
        foreach ($horarios as $horario) {
            $this->render_horario_item($horario->descripcion, $horario->maximo_personas_slot, $horario->id);
        }
    }
    
    /**
     * Renderizar un elemento de horario
     */
    private function render_horario_item($descripcion = '', $maximo_personas = '', $id = '') {
        $maximo_default = get_option('rinac_maximo_personas_hora_default', 10);
        if (empty($maximo_personas)) {
            $maximo_personas = $maximo_default;
        }
        
        echo '<div class="rinac-horario-item" data-id="' . esc_attr($id) . '">';
        echo '<span class="rinac-drag-handle">⋮⋮</span>';
        echo '<input type="text" name="rinac_horarios_descripcion[]" value="' . esc_attr($descripcion) . '" placeholder="' . __('ej: 15:00h - 16:00h', 'rinac') . '" />';
        echo '<input type="number" name="rinac_horarios_personas[]" value="' . esc_attr($maximo_personas) . '" min="1" step="1" />';
        echo '<input type="hidden" name="rinac_horarios_id[]" value="' . esc_attr($id) . '" />';
        echo '<button type="button" class="rinac-remove-horario button">' . __('Eliminar', 'rinac') . '</button>';
        echo '</div>';
    }
    
    /**
     * Renderizar panel usando plantilla
     */
    private function render_panel_template($template_name, $data = array()) {
        global $post;
        
        // Agregar datos comunes
        $data = array_merge($data, array(
            'post' => $post,
            'product_id' => $post->ID,
            'nonce' => wp_create_nonce('rinac_admin_nonce')
        ));
        
        return RINAC_Template_Helper::get_template('admin/' . $template_name, $data);
    }
    
    /**
     * JavaScript para manejar comportamiento del tipo de producto
     */
    public function visitas_product_type_options() {
        global $post, $pagenow;
        
        if ($pagenow != 'post.php' && $pagenow != 'post-new.php') return;
        if (!$post || $post->post_type != 'product') return;
        
        // El JavaScript está manejado en assets/js/product.js
        // Aquí solo agregamos CSS específico si es necesario
        ?>
        <style type="text/css">
            .rinac-horario-item {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 10px;
                margin: 5px 0;
                border-radius: 3px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .rinac-drag-handle {
                cursor: move;
                color: #666;
                font-weight: bold;
            }
            
            .rinac-horario-item input[type="text"] {
                flex: 2;
            }
            
            .rinac-horario-item input[type="number"] {
                width: 80px;
            }
            
            .rinac-remove-horario {
                background: #dc3232;
                color: white;
                border-color: #dc3232;
            }
            
            .rinac-remove-horario:hover {
                background: #c62d2d;
            }
            
            #rinac-horarios-list.ui-sortable .rinac-horario-item {
                cursor: move;
            }
            
            .show_if_visitas {
                display: none;
            }
        </style>
        <?php
    }
    
    /**
     * Guardar datos del producto VISITAS
     */
    public function save_visitas_product_data($post_id) {
        $product = wc_get_product($post_id);
        
        if (!$product || $product->get_type() !== 'visitas') {
            return;
        }
        
        // Guardar máximo personas por hora
        if (isset($_POST['_rinac_maximo_personas_hora'])) {
            $max_personas = intval($_POST['_rinac_maximo_personas_hora']);
        } else {
            // Si no se especifica, usar el valor por defecto de configuración
            $max_personas = get_option('rinac_max_personas_default', 10);
        }
        
        // Asegurar que siempre hay un valor válido
        if ($max_personas <= 0) {
            $max_personas = get_option('rinac_max_personas_default', 10);
        }
        
        update_post_meta($post_id, '_rinac_maximo_personas_hora', $max_personas);
        
        // Guardar horarios del producto
        $this->save_product_horarios($post_id);
        
        // Asegurar que el producto es virtual
        update_post_meta($post_id, '_virtual', 'yes');
        update_post_meta($post_id, '_manage_stock', 'no');
    }
    
    /**
     * Guardar horarios del producto
     */
    private function save_product_horarios($product_id) {
        global $wpdb;
        
        if (!isset($_POST['rinac_horarios_descripcion']) || !is_array($_POST['rinac_horarios_descripcion'])) {
            return;
        }
        
        $table_horas = $wpdb->prefix . 'rinac_producto_horas';
        
        // Eliminar horarios existentes
        $wpdb->delete($table_horas, array('product_id' => $product_id));
        
        $descripciones = $_POST['rinac_horarios_descripcion'];
        $personas = $_POST['rinac_horarios_personas'];
        $ids = isset($_POST['rinac_horarios_id']) ? $_POST['rinac_horarios_id'] : array();
        
        foreach ($descripciones as $index => $descripcion) {
            if (empty(trim($descripcion))) continue;
            
            $wpdb->insert(
                $table_horas,
                array(
                    'product_id' => $product_id,
                    'descripcion' => sanitize_text_field($descripcion),
                    'maximo_personas_slot' => intval($personas[$index]),
                    'orden' => $index + 1
                )
            );
        }
    }
    
    /**
     * Asegurar que los productos VISITAS usen la clase correcta
     */
    public function set_visitas_product_class($classname, $product_type) {
        if ($product_type == 'visitas') {
            $classname = 'RINAC_Product_Visitas';
        }
        return $classname;
    }
    
    /**
     * Establecer valores por defecto para nuevos productos VISITAS
     */
    public function set_default_values_for_new_visitas($post_id, $post, $update) {
        // Solo procesar si es un nuevo producto (no una actualización)
        if ($update) {
            return;
        }
        
        // Solo procesar productos
        if ($post->post_type !== 'product') {
            return;
        }
        
        // Verificar si es tipo VISITAS (puede que aún no esté establecido)
        $product_type = get_post_meta($post_id, '_product_type', true);
        if ($product_type === 'visitas') {
            // Establecer máximo personas por defecto si no está establecido
            $current_max = get_post_meta($post_id, '_rinac_maximo_personas_hora', true);
            if (empty($current_max)) {
                $default_max = get_option('rinac_max_personas_default', 10);
                update_post_meta($post_id, '_rinac_maximo_personas_hora', $default_max);
            }
        }
    }
}
