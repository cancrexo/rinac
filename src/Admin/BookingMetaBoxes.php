<?php

namespace RINAC\Admin;

use WP_Post;

/**
 * Metabox de detalle para `rinac_booking`.
 */
class BookingMetaBoxes {
    private string $nonceAction = 'rinac_booking_meta_save';
    private string $nonceKey = 'rinac_booking_meta_nonce';

    public function register(): void {
        add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
        add_action( 'save_post_rinac_booking', array( $this, 'saveMeta' ) );
        add_filter( 'manage_rinac_booking_posts_columns', array( $this, 'filterAdminColumns' ) );
        add_action( 'manage_rinac_booking_posts_custom_column', array( $this, 'renderAdminColumn' ), 10, 2 );
    }

    public function addMetaBoxes(): void {
        add_meta_box(
            'rinac_booking_details',
            __( 'Detalles de reserva', 'rinac' ),
            array( $this, 'renderMetaBox' ),
            'rinac_booking',
            'normal',
            'high'
        );
    }

    public function renderMetaBox( WP_Post $post ): void {
        $product_id = (int) get_post_meta( $post->ID, '_rinac_booking_product_id', true );
        $slot_id = (int) get_post_meta( $post->ID, '_rinac_booking_slot_id', true );
        $order_id = (int) get_post_meta( $post->ID, '_rinac_booking_order_id', true );
        $start = (string) get_post_meta( $post->ID, '_rinac_booking_start', true );
        $end = (string) get_post_meta( $post->ID, '_rinac_booking_end', true );
        $equivalent_qty = (float) get_post_meta( $post->ID, '_rinac_booking_equivalent_qty', true );
        $status = (string) get_post_meta( $post->ID, '_rinac_booking_status', true );

        wp_nonce_field( $this->nonceAction, $this->nonceKey );

        echo '<p><label><strong>' . esc_html__( 'Producto ID', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" name="rinac_booking_product_id" value="' . esc_attr( (string) $product_id ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Slot ID', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" name="rinac_booking_slot_id" value="' . esc_attr( (string) $slot_id ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Pedido ID', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" name="rinac_booking_order_id" value="' . esc_attr( (string) $order_id ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Inicio', 'rinac' ) . '</strong></label><br />';
        echo '<input type="date" name="rinac_booking_start" value="' . esc_attr( $start ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Fin', 'rinac' ) . '</strong></label><br />';
        echo '<input type="date" name="rinac_booking_end" value="' . esc_attr( $end ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Capacidad equivalente', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="0.01" name="rinac_booking_equivalent_qty" value="' . esc_attr( (string) $equivalent_qty ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Estado interno', 'rinac' ) . '</strong></label><br />';
        echo '<select name="rinac_booking_status">';
        $statuses = array( 'hold', 'confirmed', 'completed', 'cancelled', 'expired', 'partially_refunded' );
        foreach ( $statuses as $status_key ) {
            echo '<option value="' . esc_attr( $status_key ) . '"' . selected( $status, $status_key, false ) . '>' . esc_html( $status_key ) . '</option>';
        }
        echo '</select></p>';
    }

    public function saveMeta( int $post_id ): void {
        if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        /** @noinspection PhpUndefinedVariableInspection */
        $post_data = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();
        $nonce = isset( $post_data[ $this->nonceKey ] ) ? sanitize_text_field( wp_unslash( (string) $post_data[ $this->nonceKey ] ) ) : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, $this->nonceAction ) ) {
            return;
        }

        $product_id = isset( $post_data['rinac_booking_product_id'] ) ? absint( $post_data['rinac_booking_product_id'] ) : 0;
        $slot_id = isset( $post_data['rinac_booking_slot_id'] ) ? absint( $post_data['rinac_booking_slot_id'] ) : 0;
        $order_id = isset( $post_data['rinac_booking_order_id'] ) ? absint( $post_data['rinac_booking_order_id'] ) : 0;
        $start = isset( $post_data['rinac_booking_start'] ) ? sanitize_text_field( wp_unslash( (string) $post_data['rinac_booking_start'] ) ) : '';
        $end = isset( $post_data['rinac_booking_end'] ) ? sanitize_text_field( wp_unslash( (string) $post_data['rinac_booking_end'] ) ) : '';
        $equivalent_qty = isset( $post_data['rinac_booking_equivalent_qty'] ) ? max( 0.0, (float) $post_data['rinac_booking_equivalent_qty'] ) : 0.0;
        $status = isset( $post_data['rinac_booking_status'] ) ? sanitize_key( wp_unslash( (string) $post_data['rinac_booking_status'] ) ) : 'hold';

        update_post_meta( $post_id, '_rinac_booking_product_id', $product_id );
        update_post_meta( $post_id, '_rinac_booking_slot_id', $slot_id );
        update_post_meta( $post_id, '_rinac_booking_order_id', $order_id );
        update_post_meta( $post_id, '_rinac_booking_start', $start );
        update_post_meta( $post_id, '_rinac_booking_end', $end );
        update_post_meta( $post_id, '_rinac_booking_equivalent_qty', $equivalent_qty );
        update_post_meta( $post_id, '_rinac_booking_status', $status );
    }

    /**
     * Ajusta columnas del listado admin de reservas.
     *
     * @param array<string,string> $columns
     * @return array<string,string>
     */
    public function filterAdminColumns( array $columns ): array {
        $ordered = array();

        foreach ( $columns as $key => $label ) {
            if ( 'title' === $key ) {
                $ordered['rinac_booking_id'] = __( 'ID reserva', 'rinac' );
                $ordered['title'] = $label;
                $ordered['rinac_booking_status'] = __( 'Estado RINAC', 'rinac' );
                continue;
            }

            $ordered[ $key ] = $label;
        }

        // Fallback defensivo por si otro plugin altera el set de columnas.
        if ( ! isset( $ordered['rinac_booking_id'] ) ) {
            $ordered = array_merge(
                array( 'rinac_booking_id' => __( 'ID reserva', 'rinac' ) ),
                $ordered
            );
        }
        if ( ! isset( $ordered['rinac_booking_status'] ) ) {
            $ordered['rinac_booking_status'] = __( 'Estado RINAC', 'rinac' );
        }

        return $ordered;
    }

    /**
     * Renderiza celdas custom del listado admin de reservas.
     *
     * @param string $column_name
     * @param int $post_id
     * @return void
     */
    public function renderAdminColumn( string $column_name, int $post_id ): void {
        if ( 'rinac_booking_id' === $column_name ) {
            echo esc_html( (string) $post_id );
            return;
        }

        if ( 'rinac_booking_status' === $column_name ) {
            $status = (string) get_post_meta( $post_id, '_rinac_booking_status', true );
            echo esc_html( '' !== $status ? $status : 'sin_estado' );
        }
    }
}
