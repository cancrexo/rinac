<?php

namespace RINAC\Core;

/**
 * Menú de administración de RINAC.
 */
class MenuRegistrar {

    /**
     * Registra menú y submenús base.
     *
     * @return void
     */
    public function registerAdminMenu(): void {
        $capability = 'manage_woocommerce';
        $slug = 'rinac_dashboard';

        add_menu_page(
            __( 'RINAC', 'rinac' ),
            __( 'RINAC', 'rinac' ),
            $capability,
            $slug,
            array( $this, 'renderDashboard' ),
            'dashicons-calendar-alt',
            56
        );

        add_submenu_page( $slug, __( 'Dashboard', 'rinac' ), __( 'Dashboard', 'rinac' ), $capability, $slug, array( $this, 'renderDashboard' ) );
        add_submenu_page( $slug, __( 'Productos reservables', 'rinac' ), __( 'Productos reservables', 'rinac' ), $capability, 'rinac_productos_reservables', array( $this, 'renderProductosReservables' ) );
        add_submenu_page( $slug, __( 'Slots', 'rinac' ), __( 'Slots', 'rinac' ), $capability, 'edit.php?post_type=rinac_slot' );
        add_submenu_page( $slug, __( 'Tipos de participantes', 'rinac' ), __( 'Tipos de participantes', 'rinac' ), $capability, 'edit.php?post_type=rinac_participant' );
        add_submenu_page( $slug, __( 'Recursos', 'rinac' ), __( 'Recursos', 'rinac' ), $capability, 'edit.php?post_type=rinac_resource' );
        add_submenu_page( $slug, __( 'Calendario global', 'rinac' ), __( 'Calendario global', 'rinac' ), $capability, 'rinac_calendario_global', array( $this, 'renderCalendarioGlobal' ) );
        add_submenu_page( $slug, __( 'Reservas', 'rinac' ), __( 'Reservas', 'rinac' ), $capability, 'edit.php?post_type=rinac_booking' );
        add_submenu_page( $slug, __( 'Ajustes', 'rinac' ), __( 'Ajustes', 'rinac' ), $capability, 'rinac_ajustes', array( $this, 'renderAjustes' ) );
    }

    /**
     * Render dashboard.
     *
     * @return void
     */
    public function renderDashboard(): void {
        echo '<div class="wrap"><h1>' . esc_html__( 'RINAC - Dashboard', 'rinac' ) . '</h1></div>';
    }

    /**
     * Render productos reservables.
     *
     * @return void
     */
    public function renderProductosReservables(): void {
        echo '<div class="wrap"><h1>' . esc_html__( 'RINAC - Productos reservables', 'rinac' ) . '</h1></div>';
    }

    /**
     * Render calendario global.
     *
     * @return void
     */
    public function renderCalendarioGlobal(): void {
        echo '<div class="wrap"><h1>' . esc_html__( 'RINAC - Calendario global', 'rinac' ) . '</h1></div>';
    }

    /**
     * Render ajustes.
     *
     * @return void
     */
    public function renderAjustes(): void {
        echo '<div class="wrap"><h1>' . esc_html__( 'RINAC - Ajustes', 'rinac' ) . '</h1></div>';
    }
}
