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
        add_action( 'admin_init', array( $this, 'onAdminInit' ) );
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
        if ( class_exists( 'RINAC\\Frontend\\BookingForm' ) ) {
            $booking_form_class = 'RINAC\\Frontend\\BookingForm';
            ( new $booking_form_class() )->register();
        }

        if ( class_exists( 'RINAC\\Ajax\\AjaxHandler' ) ) {
            $ajax_handler_class = 'RINAC\\Ajax\\AjaxHandler';
            ( new $ajax_handler_class() )->register();
        }
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
     * Inicializa funcionalidades de admin.
     *
     * @return void
     */
    public function onAdminInit(): void {
        if ( class_exists( 'RINAC\\Admin\\BookingProductDataTabs' ) ) {
            $booking_tabs_class = 'RINAC\\Admin\\BookingProductDataTabs';
            ( new $booking_tabs_class() )->register();
        }

        if ( class_exists( 'RINAC\\Admin\\ParticipantMetaBoxes' ) ) {
            $participant_meta_class = 'RINAC\\Admin\\ParticipantMetaBoxes';
            ( new $participant_meta_class() )->register();
        }

        if ( class_exists( 'RINAC\\Admin\\ResourceMetaBoxes' ) ) {
            $resource_meta_class = 'RINAC\\Admin\\ResourceMetaBoxes';
            ( new $resource_meta_class() )->register();
        }
    }

    /**
     * Punto de extensión para assets frontend.
     *
     * @return void
     */
    public function onFrontendAssets(): void {
        if ( class_exists( 'RINAC\\Frontend\\BookingForm' ) ) {
            $booking_form_class = 'RINAC\\Frontend\\BookingForm';
            ( new $booking_form_class() )->enqueueAssets();
        }
    }
}
