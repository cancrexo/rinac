<?php

namespace RINAC\Admin;

/**
 * Página Calendario Global (stub inicial).
 */
class CalendarioGlobalPage {

    /**
     * Render de la página.
     *
     * @return void
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos para acceder a esta sección.', 'rinac' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Calendario Global', 'rinac' ) . '</h1>';
        echo '<p>' . esc_html__( 'Vista inicial (stub). Se desarrollará con FullCalendar en fases posteriores.', 'rinac' ) . '</p>';
        echo '</div>';
    }
}

