<?php

namespace RINAC\Admin;

/**
 * Página Dashboard (stub inicial).
 */
class DashboardPage {

    /**
     * Render de la página.
     *
     * @return void
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos para acceder a esta sección.', 'rinac' ) );
        }

        $count_slots = (int) wp_count_posts( 'rinac_slot' )->publish;
        $count_turno = (int) wp_count_posts( 'rinac_turno' )->publish;
        $count_reservas = (int) wp_count_posts( 'rinac_booking' )->publish;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Dashboard', 'rinac' ) . '</h1>';
        echo '<p>' . esc_html__( 'Resumen básico de RINAC (vista inicial).', 'rinac' ) . '</p>';
        echo '<ul>';
        echo '<li>' . sprintf( esc_html__( 'Slots: %d', 'rinac' ), $count_slots ) . '</li>';
        echo '<li>' . sprintf( esc_html__( 'Turnos: %d', 'rinac' ), $count_turno ) . '</li>';
        echo '<li>' . sprintf( esc_html__( 'Reservas: %d', 'rinac' ), $count_reservas ) . '</li>';
        echo '</ul>';
        echo '</div>';
    }
}

