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

        $imported = isset( $_GET['rinac_demo_imported'] ) && '1' === (string) $_GET['rinac_demo_imported']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $count_bookings = (int) wp_count_posts( 'rinac_booking' )->publish;
        $count_slots = (int) wp_count_posts( 'rinac_slot' )->publish;
        $count_turnos = (int) wp_count_posts( 'rinac_turno' )->publish;

        $recent_bookings = get_posts(
            array(
                'post_type'      => 'rinac_booking',
                'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
                'posts_per_page' => 10,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'fields'         => 'ids',
            )
        );

        $nonce = wp_create_nonce( 'rinac_import_demo' );
        $import_url = admin_url( 'admin-post.php?action=rinac_import_demo&_wpnonce=' . $nonce );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Calendario Global', 'rinac' ) . '</h1>';
        echo '<p>' . esc_html__( 'Vista global administrativa de reservas y ocupación.', 'rinac' ) . '</p>';

        if ( $imported ) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html__( 'Datos de prueba importados correctamente.', 'rinac' );
            echo '</p></div>';
        }

        echo '<ul style="margin:12px 0 18px 0;">';
        echo '<li>' . sprintf( esc_html__( 'Reservas publicadas: %d', 'rinac' ), $count_bookings ) . '</li>';
        echo '<li>' . sprintf( esc_html__( 'Slots activos: %d', 'rinac' ), $count_slots ) . '</li>';
        echo '<li>' . sprintf( esc_html__( 'Turnos activos: %d', 'rinac' ), $count_turnos ) . '</li>';
        echo '</ul>';

        echo '<h2>' . esc_html__( 'Últimas reservas', 'rinac' ) . '</h2>';

        if ( empty( $recent_bookings ) ) {
            echo '<p>' . esc_html__( 'No hay reservas registradas todavía.', 'rinac' ) . '</p>';
        } else {
            echo '<table class="widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__( 'ID', 'rinac' ) . '</th>';
            echo '<th>' . esc_html__( 'Reserva', 'rinac' ) . '</th>';
            echo '<th>' . esc_html__( 'Producto', 'rinac' ) . '</th>';
            echo '<th>' . esc_html__( 'Inicio', 'rinac' ) . '</th>';
            echo '<th>' . esc_html__( 'Fin', 'rinac' ) . '</th>';
            echo '<th>' . esc_html__( 'Estado', 'rinac' ) . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ( $recent_bookings as $booking_id ) {
                $product_id = (int) get_post_meta( $booking_id, '_rinac_booking_product_id', true );
                $start_ts = (int) get_post_meta( $booking_id, '_rinac_booking_start_ts', true );
                $end_ts = (int) get_post_meta( $booking_id, '_rinac_booking_end_ts', true );

                $product_title = $product_id > 0 ? get_the_title( $product_id ) : __( '(sin producto)', 'rinac' );
                $start_label = $start_ts > 0 ? wp_date( 'Y-m-d H:i', $start_ts ) : '-';
                $end_label = $end_ts > 0 ? wp_date( 'Y-m-d H:i', $end_ts ) : '-';

                echo '<tr>';
                echo '<td>' . esc_html( (string) $booking_id ) . '</td>';
                echo '<td><a href="' . esc_url( get_edit_post_link( $booking_id ) ?: '#' ) . '">' . esc_html( get_the_title( $booking_id ) ) . '</a></td>';
                echo '<td>' . esc_html( (string) $product_title ) . '</td>';
                echo '<td>' . esc_html( (string) $start_label ) . '</td>';
                echo '<td>' . esc_html( (string) $end_label ) . '</td>';
                echo '<td>' . esc_html( get_post_status( $booking_id ) ?: '-' ) . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        }

        echo '<h2 style="margin-top:20px;">' . esc_html__( 'Importación de datos de prueba', 'rinac' ) . '</h2>';
        echo '<p style="color:#b32d2e;font-weight:600;">';
        echo esc_html__( 'Atención: esta acción insertará datos de ejemplo en la instalación actual.', 'rinac' );
        echo '</p>';
        echo '<a class="button button-secondary" href="' . esc_url( $import_url ) . '">';
        echo esc_html__( 'Importar datos de prueba', 'rinac' );
        echo '</a>';
        echo '</div>';
    }
}

