<?php

namespace RINAC\Core;

use RINAC\Admin\DemoDataImporter;
use RINAC\Core\I18n;
use RINAC\Ajax\AjaxHandler;
use RINAC\Core\MenuRegistrar;
use RINAC\Core\PostTypesRegistrar;
use RINAC\Core\ProductTypeRegistrar;

/**
 * Punto central del plugin.
 */
class Plugin {

    /**
     * Arranca el plugin.
     *
     * @return void
     */
    public function run(): void {
        ( new I18n() )->register();
        ( new PostTypesRegistrar() )->register();
        ( new ProductTypeRegistrar() )->register();
        ( new MenuRegistrar() )->register();
        ( new AjaxHandler() )->register();
    }

    /**
     * Hook de activación.
     *
     * @return void
     */
    public static function activate(): void {
        // Registrar CPTs mínimos AHORA (no esperar al hook `init`).
        ( new PostTypesRegistrar() )->registerPostTypes();

        // Registrar producto type / mapping (y disparar el hook si procede).
        $productTypeRegistrar = new ProductTypeRegistrar();
        $productTypeRegistrar->register();
        $productTypeRegistrar->maybeTriggerRegisterProductType();

        if ( self::shouldLoadDemoOnActivation() ) {
            DemoDataImporter::import_minimal_demo();
        }

        flush_rewrite_rules();
    }

    /**
     * Hook de desactivación.
     *
     * @return void
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    /**
     * Determina si se deben cargar datos demo mínimos.
     *
     * @return bool
     */
    private static function shouldLoadDemoOnActivation(): bool {
        $wp_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
        $forced = defined( 'RINAC_LOAD_DEMO_ON_ACTIVATION' ) && (bool) constant( 'RINAC_LOAD_DEMO_ON_ACTIVATION' );

        return (bool) ( $wp_debug || $forced );
    }
}

