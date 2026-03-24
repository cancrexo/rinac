<?php

namespace RINAC\Core;

/**
 * Núcleo de arranque del plugin.
 */
class Plugin {

    /**
     * Registra hooks principales.
     *
     * @return void
     */
    public function register(): void {
        add_action( 'init', array( $this, 'onInit' ) );
        add_action( 'admin_menu', array( $this, 'onAdminMenu' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'onFrontendAssets' ) );
    }

    /**
     * Inicialización base en `init`.
     *
     * @return void
     */
    public function onInit(): void {
        ( new I18n() )->loadTextdomain();
        ( new PostTypesRegistrar() )->registerPostTypes();
        ( new ProductTypeRegistrar() )->registerProductType();
    }

    /**
     * Construye el menú de administración.
     *
     * @return void
     */
    public function onAdminMenu(): void {
        ( new MenuRegistrar() )->registerAdminMenu();
    }

    /**
     * Punto de extensión para assets frontend.
     *
     * @return void
     */
    public function onFrontendAssets(): void {
        // Placeholder del paso 1.
    }
}
