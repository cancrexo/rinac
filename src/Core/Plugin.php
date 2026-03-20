<?php

namespace rinac\Core;

use rinac\Ajax\AjaxHandler;
use rinac\Admin\MetaBoxes\RinacReservationProductMetaBoxes;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin {
    private static ?Plugin $instance = null;

    public static function instance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init(): void {
        add_action('plugins_loaded', [$this, 'boot']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function boot(): void {
        Localization::load_textdomain();
        CPTRegistrar::register_all();
        $this->maybe_register_product_type();
        AjaxHandler::register();

        if (is_admin()) {
            RinacReservationProductMetaBoxes::register();
        }
    }

    /**
     * Activación del plugin: registrar CPTs + flush rewrite.
     */
    public function activate(): void {
        CPTRegistrar::register_all();
        flush_rewrite_rules();
    }

    public function deactivate(): void {
        flush_rewrite_rules();
    }

    private function maybe_register_product_type(): void {
        // Tipo custom "Reserva" (product_type).
        add_filter('product_type_selector', function ($types) {
            // $types suele ser un array slug => label.
            $types['rinac_reserva'] = __('Reserva', Constants::TEXT_DOMAIN);
            return $types;
        });

        add_filter(
            'woocommerce_product_class',
            function ($class_name, $product_type, $product) {
                if ($product_type === 'rinac_reserva') {
                    return '\\rinac\\Booking\\RinacReservationProduct';
                }
                return $class_name;
            },
            10,
            3
        );
    }
}

