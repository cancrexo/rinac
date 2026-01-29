<?php
/**
 * Plugin Name: RINAC - Rinac Is Not Another Calendar
 * Plugin URI: https://adegaeidos.com
 * Description: Plugin de reservas para WooCommerce que permite crear productos de tipo VISITAS con calendario y gestión de horarios.
 * Version: 1.0.0
 * Author: Adegaeidos
 * Author URI: https://adegaeidos.com
 * Text Domain: rinac
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('RINAC_VERSION', '1.0.0');
define('RINAC_PLUGIN_FILE', __FILE__);
define('RINAC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('RINAC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RINAC_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Clase principal del plugin RINAC
 */
class RINAC {
    
    /**
     * Instancia única del plugin (Singleton)
     */
    private static $instance = null;
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        // Incluir clase de instalación antes de registrar hooks
        require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-install.php';
        
        // Declarar compatibilidad con WooCommerce
        $this->declare_woocommerce_compatibility();
        
        $this->init_hooks();
    }
    
    /**
     * Obtener instancia única del plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar hooks y acciones
     */
    private function init_hooks() {
        // Hook de activación del plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Hook de desactivación del plugin
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Inicializar plugin después de que WordPress esté completamente cargado
        add_action('plugins_loaded', array($this, 'init'));
        
        // Verificar si WooCommerce está activo
        add_action('admin_init', array($this, 'check_woocommerce'));
    }
    
    /**
     * Inicializar el plugin
     */
    public function init() {
        // Verificar dependencias
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Cargar archivos necesarios
        $this->includes();
        
        // Inicializar clases
        $this->init_classes();
        
        // Cargar textdomain para traducciones
        $this->load_textdomain();
        
        // Hook para cuando el plugin esté completamente inicializado
        do_action('rinac_loaded');
    }
    
    /**
     * Verificar dependencias del plugin
     */
    private function check_dependencies() {
        // Verificar si WooCommerce está activo
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Incluir archivos necesarios
     */
    private function includes() {
        // Incluir clases principales (Install ya está incluida en el constructor)
        require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-product-type.php';
        require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-admin.php';
        require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-frontend.php';
        require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-calendar.php';
        require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-validation.php';
        require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-database.php';
        require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-template-helper.php';
        require_once RINAC_PLUGIN_PATH . 'includes/functions.php';
    }
    
    /**
     * Inicializar clases del plugin
     */
    private function init_classes() {
        // Inicializar tipo de producto
        new RINAC_Product_Type();
        
        // Inicializar administración
        if (is_admin()) {
            new RINAC_Admin();
        }
        
        // Inicializar frontend
        if (!is_admin()) {
            new RINAC_Frontend();
        }
        
        // Inicializar validación (tanto admin como frontend)
        new RINAC_Validation();
        
        // Inicializar calendario
        new RINAC_Calendar();
    }
    
    /**
     * Cargar traducciones
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'rinac',
            false,
            dirname(RINAC_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Verificar si WooCommerce está activo
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(RINAC_PLUGIN_BASENAME);
        }
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Verificar dependencias antes de activar
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(RINAC_PLUGIN_BASENAME);
            wp_die(__('RINAC requiere que WooCommerce esté instalado y activado.', 'rinac'));
        }
        
        // Llamar al método de activación
        RINAC_Install::activate();
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Llamar al método de desactivación
        RINAC_Install::deactivate();
    }
    
    /**
     * Aviso de WooCommerce faltante
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('RINAC requiere que WooCommerce esté instalado y activado.', 'rinac');
        echo '</p></div>';
    }
    
    /**
     * Declarar compatibilidad con WooCommerce
     */
    private function declare_woocommerce_compatibility() {
        // Declarar compatibilidad antes de que WooCommerce se inicialice
        add_action('before_woocommerce_init', function() {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                // HPOS (High-Performance Order Storage)
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'custom_order_tables',
                    __FILE__,
                    true
                );
                
                // Cart and Checkout Blocks
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'cart_checkout_blocks',
                    __FILE__,
                    true
                );
            }
        });
    }
}

/**
 * Función para obtener la instancia principal del plugin
 */
function RINAC() {
    return RINAC::get_instance();
}

// Inicializar el plugin
RINAC();
