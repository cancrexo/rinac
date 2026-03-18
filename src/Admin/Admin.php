<?php

declare(strict_types=1);

namespace Rinac\Admin;

final class Admin
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
        // Añadir menú de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Registrar configuraciones
        add_action('admin_init', array($this, 'register_settings'));

        // Cargar scripts y estilos de admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // AJAX para obtener horas de un rango
        add_action('wp_ajax_rinac_get_rango_horas', array($this, 'ajax_get_rango_horas'));

        // AJAX para gestión de rangos horarios
        add_action('wp_ajax_rinac_save_rango', array($this, 'ajax_save_rango'));
        add_action('wp_ajax_rinac_delete_rango', array($this, 'ajax_delete_rango'));
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('RINAC Reservas', 'rinac'),
            __('RINAC', 'rinac'),
            'manage_woocommerce',
            'rinac-admin',
            array($this, 'admin_page'),
            'dashicons-calendar-alt',
            56
        );

        // Submenús
        add_submenu_page(
            'rinac-admin',
            __('Configuración', 'rinac'),
            __('Configuración', 'rinac'),
            'manage_woocommerce',
            'rinac-configuracion',
            array($this, 'configuracion_page')
        );

        add_submenu_page(
            'rinac-admin',
            __('Rangos Horarios', 'rinac'),
            __('Rangos Horarios', 'rinac'),
            'manage_woocommerce',
            'rinac-rangos',
            array($this, 'rangos_page')
        );

        add_submenu_page(
            'rinac-admin',
            __('Reservas', 'rinac'),
            __('Reservas', 'rinac'),
            'manage_woocommerce',
            'rinac-reservas',
            array($this, 'reservas_page')
        );
    }

    /**
     * Registrar configuraciones del plugin
     */
    public function register_settings()
    {
        // Registrar configuraciones
        register_setting('rinac_settings', 'rinac_maximo_personas_hora_default');
        register_setting('rinac_settings', 'rinac_calendario_rango_anos');
        register_setting('rinac_settings', 'rinac_email_notifications');
        register_setting('rinac_settings', 'rinac_require_phone');
        register_setting('rinac_settings', 'rinac_calendario_start_day');
    }

    /**
     * Cargar scripts y estilos de administración
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post;

        // Cargar en páginas del plugin
        if (strpos($hook, 'rinac') !== false) {
            wp_enqueue_script(
                'rinac-admin',
                RINAC_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                RINAC_VERSION,
                true
            );

            wp_enqueue_style(
                'rinac-admin',
                RINAC_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                RINAC_VERSION
            );
        }

        // Cargar en páginas de productos
        if (($hook == 'post.php' || $hook == 'post-new.php') &&
            isset($post) && $post->post_type == 'product') {

            wp_enqueue_script(
                'rinac-product',
                RINAC_PLUGIN_URL . 'assets/js/product.js',
                array('jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'),
                RINAC_VERSION,
                true
            );

            wp_enqueue_style(
                'rinac-product',
                RINAC_PLUGIN_URL . 'assets/css/product.css',
                array('jquery-ui-style'),
                RINAC_VERSION
            );

            // Localizar script
            wp_localize_script('rinac-product', 'rinac_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rinac_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('¿Estás seguro de que quieres eliminar este elemento?', 'rinac'),
                    'error_general' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'rinac')
                )
            ));
        }
    }

    /**
     * Página principal de administración
     */
    public function admin_page()
    {
        // Preparar datos para el dashboard
        $dashboard_data = $this->get_dashboard_data();

        $template_data = array(
            'dashboard_data' => $dashboard_data,
            'settings' => array(
                'max_personas_default' => get_option('rinac_maximo_personas_hora_default', 10),
                'calendario_rango' => get_option('rinac_calendario_rango_anos', 2),
                'email_notifications' => get_option('rinac_email_notifications', true),
                'require_phone' => get_option('rinac_require_phone', false)
            ),
            'strings' => array(
                'page_title' => __('RINAC - Panel de Control', 'rinac'),
                'settings_title' => __('Configuración General', 'rinac'),
                'max_persons_label' => __('Máximo personas por hora (por defecto)', 'rinac'),
                'calendar_range_label' => __('Rango del calendario (años)', 'rinac'),
                'email_notifications_label' => __('Notificaciones por email', 'rinac'),
                'require_phone_label' => __('Requerir teléfono', 'rinac'),
                'save_changes' => __('Guardar Cambios', 'rinac')
            )
        );

        // Renderizar dashboard usando plantilla
        echo \RINAC_Template_Helper::get_template('admin/dashboard.php', $template_data);
    }

    /**
     * Obtener datos para el dashboard
     */
    private function get_dashboard_data()
    {
        global $wpdb;

        $data = array();

        // Estadísticas de reservas
        $table_reservas = $wpdb->prefix . 'rinac_reservas';

        $data['total_reservas'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_reservas");
        $data['reservas_mes_actual'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_reservas WHERE MONTH(fecha_reserva) = %d AND YEAR(fecha_reserva) = %d",
                date('n'),
                date('Y')
            )
        );

        $data['reservas_pendientes'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_reservas WHERE status = 'pendiente'");
        $data['reservas_confirmadas'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_reservas WHERE status = 'confirmada'");

        // Productos con tipo VISITAS
        $data['productos_visitas'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'product' 
             AND p.post_status = 'publish' 
             AND pm.meta_key = '_product_type' 
             AND pm.meta_value = 'visitas'"
        );

        // Próximas reservas (próximos 7 días)
        $data['proximas_reservas'] = $wpdb->get_results(
            "SELECT r.*, p.post_title as producto_nombre 
             FROM $table_reservas r
             LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
             WHERE r.fecha_reserva BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             AND r.status = 'confirmada'
             ORDER BY r.fecha_reserva, r.hora
             LIMIT 10"
        );

        return $data;
    }

    /**
     * Página de gestión de rangos horarios
     */
    public function rangos_page()
    {
        global $wpdb;

        // Procesar formulario si se envió
        if (isset($_POST['submit_rango']) && wp_verify_nonce($_POST['rinac_nonce'], 'rinac_rangos')) {
            $this->process_rango_form();
        }

        // Obtener rangos existentes
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';
        $rangos = $wpdb->get_results("SELECT * FROM $table_rangos ORDER BY nombre");

        // Determinar si estamos editando un rango específico
        $rango_id = isset($_GET['rango_id']) ? intval($_GET['rango_id']) : null;
        $rango_data = null;
        $horas = array();

        if ($rango_id) {
            $rango_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_rangos WHERE id = %d",
                $rango_id
            ));

            if ($rango_data) {
                $table_horas = $wpdb->prefix . 'rinac_horas';
                $horas = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_horas WHERE rango_id = %d ORDER BY orden",
                    $rango_id
                ));
            }
        }

        $template_data = array(
            'rangos' => $rangos,
            'rango_id' => $rango_id,
            'rango_data' => $rango_data,
            'horas' => $horas,
            'strings' => array(
                'page_title' => __('Gestión de Rangos Horarios', 'rinac'),
                'add_new_range' => __('Añadir Nuevo Rango', 'rinac'),
                'range_name' => __('Nombre del Rango', 'rinac'),
                'add_time' => __('Añadir Horario', 'rinac'),
                'save_range' => __('Guardar Rango', 'rinac'),
                'existing_ranges' => __('Rangos Existentes', 'rinac'),
                'name' => __('Nombre', 'rinac'),
                'schedules' => __('Horarios', 'rinac'),
                'actions' => __('Acciones', 'rinac'),
                'edit' => __('Editar', 'rinac'),
                'delete' => __('Eliminar', 'rinac')
            )
        );

        echo \RINAC_Template_Helper::get_template('admin/rangos-horarios-form.php', $template_data);
    }

    /**
     * Renderizar item de hora individual
     */
    public function render_hora_item($hora_data, $index = 0)
    {
        $template_data = array(
            'hora' => $hora_data,
            'index' => $index,
            'strings' => array(
                'time' => __('Hora', 'rinac'),
                'capacity' => __('Capacidad', 'rinac'),
                'remove' => __('Eliminar', 'rinac')
            )
        );

        return \RINAC_Template_Helper::get_template('admin/hora-item.php', $template_data);
    }

    /**
     * Página de gestión de reservas
     */
    public function reservas_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo __('Gestión de Reservas', 'rinac'); ?></h1>
            <p><?php echo __('Funcionalidad de gestión de reservas - En desarrollo', 'rinac'); ?></p>
        </div>
        <?php
    }

    /**
     * Página de configuración del plugin
     */
    public function configuracion_page()
    {
        // Procesar formulario si se envió
        if (isset($_POST['submit_config']) && wp_verify_nonce($_POST['rinac_config_nonce'], 'rinac_config')) {
            $this->process_config_form();
        }

        // Obtener configuraciones actuales
        $config = array(
            'max_personas_default' => get_option('rinac_max_personas_default', 10),
            'rango_calendario' => get_option('rinac_rango_calendario', 365),
            'telefono_obligatorio' => get_option('rinac_telefono_obligatorio', 0),
            'email_notificaciones' => get_option('rinac_email_notificaciones', 1),
            'email_admin' => get_option('rinac_email_admin', get_option('admin_email')),
        );

        $template_data = array(
            'config' => $config,
            'strings' => array(
                'page_title' => __('Configuración de RINAC', 'rinac'),
                'general_settings' => __('Configuración General', 'rinac'),
                'max_personas_default' => __('Máximo personas por defecto', 'rinac'),
                'rango_calendario' => __('Rango del calendario (días)', 'rinac'),
                'telefono_obligatorio' => __('Teléfono obligatorio', 'rinac'),
                'email_settings' => __('Configuración de Email', 'rinac'),
                'email_notificaciones' => __('Activar notificaciones por email', 'rinac'),
                'email_admin' => __('Email del administrador', 'rinac'),
                'save_changes' => __('Guardar Cambios', 'rinac'),
            )
        );

        // Debug: Verificar si la función exists y el template existe
        if (!class_exists('RINAC_Template_Helper')) {
            echo '<div class="wrap"><h1>Error: RINAC_Template_Helper no existe</h1></div>';
            return;
        }

        $template_path = RINAC_PLUGIN_PATH . 'templates/admin/configuracion.php';
        if (!file_exists($template_path)) {
            echo '<div class="wrap"><h1>Error: Template no encontrado en: ' . esc_html($template_path) . '</h1></div>';
            return;
        }

        $template_output = \RINAC_Template_Helper::get_template('admin/configuracion.php', $template_data);

        if (empty($template_output)) {
            // Fallback si el template no genera output
            ?>
            <div class="wrap">
                <h1><?php echo esc_html($template_data['strings']['page_title']); ?></h1>
                <p>Error: El template de configuración no pudo cargarse correctamente.</p>
                <p>Ruta del template: <?php echo esc_html($template_path); ?></p>
                <p>Datos del template: <?php echo '<pre>' . print_r($template_data, true) . '</pre>'; ?></p>
            </div>
            <?php
        } else {
            echo $template_output;
        }
    }

    /**
     * Procesar formulario de configuración
     */
    private function process_config_form()
    {
        // Validar y guardar configuraciones
        if (isset($_POST['max_personas_default'])) {
            update_option('rinac_max_personas_default', intval($_POST['max_personas_default']));
        }

        if (isset($_POST['rango_calendario'])) {
            update_option('rinac_rango_calendario', intval($_POST['rango_calendario']));
        }

        update_option('rinac_telefono_obligatorio', isset($_POST['telefono_obligatorio']) ? 1 : 0);
        update_option('rinac_email_notificaciones', isset($_POST['email_notificaciones']) ? 1 : 0);

        if (isset($_POST['email_admin']) && is_email($_POST['email_admin'])) {
            update_option('rinac_email_admin', sanitize_email($_POST['email_admin']));
        }

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuración guardada correctamente.', 'rinac') . '</p></div>';
        });
    }

    /**
     * Procesar formulario de rango horario
     */
    private function process_rango_form()
    {
        if (!isset($_POST['rango_nombre']) || empty($_POST['rango_nombre'])) {
            return;
        }

        global $wpdb;
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';

        $nombre = sanitize_text_field($_POST['rango_nombre']);

        // Insertar rango
        $result = $wpdb->insert(
            $table_rangos,
            array(
                'nombre' => $nombre,
                'fecha_creacion' => current_time('mysql')
            ),
            array('%s', '%s')
        );

        if ($result) {
            $rango_id = $wpdb->insert_id;

            // Procesar horas si se enviaron
            if (isset($_POST['horas']) && is_array($_POST['horas'])) {
                $table_horas = $wpdb->prefix . 'rinac_horas';
                $orden = 0;

                foreach ($_POST['horas'] as $hora_data) {
                    if (!empty($hora_data['hora']) && !empty($hora_data['capacidad'])) {
                        $wpdb->insert(
                            $table_horas,
                            array(
                                'rango_id' => $rango_id,
                                'hora' => sanitize_text_field($hora_data['hora']),
                                'capacidad' => intval($hora_data['capacidad']),
                                'orden' => $orden++
                            ),
                            array('%d', '%s', '%d', '%d')
                        );
                    }
                }
            }

            // Mostrar mensaje de éxito
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Rango horario guardado correctamente.', 'rinac') . '</p></div>';
            });
        }
    }

    /**
     * AJAX para obtener horas de un rango
     */
    public function ajax_get_rango_horas()
    {
        check_ajax_referer('rinac_admin_nonce', 'nonce');

        $rango_id = intval($_POST['rango_id']);

        if (!$rango_id) {
            wp_die(__('ID de rango inválido', 'rinac'));
        }

        global $wpdb;
        $table_horas = $wpdb->prefix . 'rinac_horas';

        $horas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_horas WHERE rango_id = %d ORDER BY orden",
            $rango_id
        ));

        wp_send_json_success($horas);
    }

    /**
     * AJAX para guardar rango
     */
    public function ajax_save_rango()
    {
        check_ajax_referer('rinac_admin_nonce', 'nonce');

        // Lógica similar a process_rango_form pero para AJAX
        $nombre = sanitize_text_field($_POST['nombre']);
        $horas = isset($_POST['horas']) ? $_POST['horas'] : array();

        global $wpdb;
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';

        $result = $wpdb->insert(
            $table_rangos,
            array(
                'nombre' => $nombre,
                'fecha_creacion' => current_time('mysql')
            ),
            array('%s', '%s')
        );

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Rango guardado correctamente', 'rinac'),
                'rango_id' => $wpdb->insert_id
            ));
        } else {
            wp_send_json_error(__('Error al guardar el rango', 'rinac'));
        }
    }

    /**
     * AJAX para eliminar rango
     */
    public function ajax_delete_rango()
    {
        check_ajax_referer('rinac_admin_nonce', 'nonce');

        $rango_id = intval($_POST['rango_id']);

        if (!$rango_id) {
            wp_send_json_error(__('ID de rango inválido', 'rinac'));
        }

        global $wpdb;
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';
        $table_horas = $wpdb->prefix . 'rinac_horas';

        // Eliminar horas asociadas
        $wpdb->delete($table_horas, array('rango_id' => $rango_id), array('%d'));

        // Eliminar rango
        $result = $wpdb->delete($table_rangos, array('id' => $rango_id), array('%d'));

        if ($result) {
            wp_send_json_success(__('Rango eliminado correctamente', 'rinac'));
        } else {
            wp_send_json_error(__('Error al eliminar el rango', 'rinac'));
        }
    }
}

