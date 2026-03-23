<?php

namespace RINAC\Core;

use RINAC\Admin\CalendarioGlobalPage;
use RINAC\Admin\DashboardPage;
use RINAC\Admin\DemoDataImporter;
use RINAC\Admin\SettingsPage;

/**
 * Registro del menú de administración "RINAC".
 */
class MenuRegistrar {

    /**
     * Registra hooks de menú.
     *
     * @return void
     */
    public function register(): void {
        add_action( 'admin_menu', array( $this, 'registerMenus' ) );

        // Handler del botón "Importar datos de prueba".
        add_action( 'admin_post_rinac_import_demo', array( DemoDataImporter::class, 'handle_import_demo_request' ) );
    }

    /**
     * Registra el menú y submenús.
     *
     * @return void
     */
    public function registerMenus(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $capability = 'manage_options';
        $icon        = 'dashicons-calendar-alt';

        $parent_slug = 'rinac';

        // Menú principal (activa Dashboard por defecto).
        add_menu_page(
            __( 'RINAC', 'rinac' ),
            __( 'RINAC', 'rinac' ),
            $capability,
            $parent_slug,
            array( DashboardPage::class, 'render' ),
            $icon,
            3
        );

        // Submenús: orden exacto requerido.
        $this->addDashboardSubmenu( $capability );
        $this->addProductReservablesSubmenu( $capability );
        $this->addRedirectSubmenu( $capability, 'Slots', __( 'Slots', 'rinac' ), 'rinac_slots', 'rinac_slot' );
        $this->addRedirectSubmenu( $capability, 'Turnos', __( 'Turnos', 'rinac' ), 'rinac_turnos', 'rinac_turno' );
        $this->addRedirectSubmenu( $capability, 'Tipos de Participantes', __( 'Tipos de Participantes', 'rinac' ), 'rinac_participant_types', 'rinac_participant_type' );
        $this->addRedirectSubmenu( $capability, 'Recursos', __( 'Recursos', 'rinac' ), 'rinac_resources', 'rinac_resource' );
        $this->addGlobalCalendarSubmenu( $capability );
        $this->addRedirectBookingsSubmenu( $capability );
        $this->addSettingsSubmenu( $capability );
    }

    private function addDashboardSubmenu( string $capability ): void {
        add_submenu_page(
            'rinac',
            __( 'Dashboard', 'rinac' ),
            __( 'Dashboard', 'rinac' ),
            $capability,
            'rinac_dashboard',
            array( DashboardPage::class, 'render' )
        );
    }

    private function addProductReservablesSubmenu( string $capability ): void {
        add_submenu_page(
            'rinac',
            __( 'Productos Reservables', 'rinac' ),
            __( 'Productos Reservables', 'rinac' ),
            $capability,
            'rinac_products_reservables',
            function () use ( $capability ) {
                if ( ! current_user_can( $capability ) ) {
                    wp_die( esc_html__( 'No tienes permisos para acceder a esta sección.', 'rinac' ) );
                }

                if ( ! function_exists( 'wc_get_products' ) && ! function_exists( 'wc_get_product' ) ) {
                    wp_die( esc_html__( 'WooCommerce no parece estar activo.', 'rinac' ) );
                }

                $url = admin_url( 'edit.php?post_type=product&product_type=rinac_reserva' );
                wp_safe_redirect( $url );
                exit;
            }
        );
    }

    /**
     * Submenú que redirige a la lista admin del CPT indicado.
     *
     * @param string $capability Capability.
     * @param string $menu_title Título.
     * @param string $label Label.
     * @param string $menu_slug Slug del submenu (rinac_*).
     * @param string $post_type CPT.
     * @return void
     */
    private function addRedirectSubmenu(
        string $capability,
        string $menu_title,
        string $label,
        string $menu_slug,
        string $post_type
    ): void {
        add_submenu_page(
            'rinac',
            $menu_title,
            $label,
            $capability,
            $menu_slug,
            function () use ( $capability, $post_type ) {
                if ( ! current_user_can( $capability ) ) {
                    wp_die( esc_html__( 'No tienes permisos para acceder a esta sección.', 'rinac' ) );
                }

                $url = admin_url( 'edit.php?post_type=' . rawurlencode( $post_type ) );
                wp_safe_redirect( $url );
                exit;
            }
        );
    }

    private function addGlobalCalendarSubmenu( string $capability ): void {
        add_submenu_page(
            'rinac',
            __( 'Calendario Global', 'rinac' ),
            __( 'Calendario Global', 'rinac' ),
            $capability,
            'rinac_global_calendar',
            array( CalendarioGlobalPage::class, 'render' )
        );
    }

    private function addRedirectBookingsSubmenu( string $capability ): void {
        add_submenu_page(
            'rinac',
            __( 'Reservas', 'rinac' ),
            __( 'Reservas', 'rinac' ),
            $capability,
            'rinac_bookings',
            function () use ( $capability ) {
                if ( ! current_user_can( $capability ) ) {
                    wp_die( esc_html__( 'No tienes permisos para acceder a esta sección.', 'rinac' ) );
                }

                $url = admin_url( 'edit.php?post_type=rinac_booking' );
                wp_safe_redirect( $url );
                exit;
            }
        );
    }

    private function addSettingsSubmenu( string $capability ): void {
        add_submenu_page(
            'rinac',
            __( 'Ajustes', 'rinac' ),
            __( 'Ajustes', 'rinac' ),
            $capability,
            'rinac_settings',
            array( SettingsPage::class, 'render' )
        );
    }
}

