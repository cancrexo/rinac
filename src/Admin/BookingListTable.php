<?php

namespace RINAC\Admin;

/**
 * Listado básico de reservas en admin.
 */
class BookingListTable {

    /**
     * Render de la página "Reservas".
     *
     * @return void
     */
    public static function renderPage(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos para acceder a esta sección.', 'rinac' ) );
        }

        $bookings = get_posts(
            array(
                'post_type'      => 'rinac_booking',
                'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
                'posts_per_page' => 50,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Reservas', 'rinac' ) . '</h1>';
        echo '<p>' . esc_html__( 'Listado administrativo de reservas RINAC.', 'rinac' ) . '</p>';

        if ( empty( $bookings ) ) {
            echo '<p>' . esc_html__( 'No hay reservas registradas todavía.', 'rinac' ) . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'ID', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Reserva', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Producto', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Inicio', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Fin', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Unidades', 'rinac' ) . '</th>';
        echo '<th>' . esc_html__( 'Estado', 'rinac' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ( $bookings as $booking ) {
            $booking_id = (int) $booking->ID;
            $product_id = (int) get_post_meta( $booking_id, '_rinac_booking_product_id', true );
            $start_ts = (int) get_post_meta( $booking_id, '_rinac_booking_start_ts', true );
            $end_ts = (int) get_post_meta( $booking_id, '_rinac_booking_end_ts', true );
            $units = (int) get_post_meta( $booking_id, '_rinac_booking_units', true );

            $product_title = $product_id > 0 ? get_the_title( $product_id ) : __( '(sin producto)', 'rinac' );
            $start_label = $start_ts > 0 ? wp_date( 'Y-m-d H:i', $start_ts ) : '-';
            $end_label = $end_ts > 0 ? wp_date( 'Y-m-d H:i', $end_ts ) : '-';

            echo '<tr>';
            echo '<td>' . esc_html( (string) $booking_id ) . '</td>';
            echo '<td><a href="' . esc_url( get_edit_post_link( $booking_id ) ?: '#' ) . '">' . esc_html( get_the_title( $booking_id ) ) . '</a></td>';
            echo '<td>' . esc_html( (string) $product_title ) . '</td>';
            echo '<td>' . esc_html( (string) $start_label ) . '</td>';
            echo '<td>' . esc_html( (string) $end_label ) . '</td>';
            echo '<td>' . esc_html( (string) max( 1, $units ) ) . '</td>';
            echo '<td>' . esc_html( get_post_status( $booking_id ) ?: '-' ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}

