<?php

namespace RINAC\Admin;

use WP_Post;

/**
 * Metabox de detalle para `rinac_slot`.
 */
class SlotMetaBoxes {
    private string $nonceAction = 'rinac_slot_meta_save';
    private string $nonceKey = 'rinac_slot_meta_nonce';

    public function register(): void {
        add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
        add_action( 'save_post_rinac_slot', array( $this, 'saveMeta' ) );
    }

    public function addMetaBoxes(): void {
        add_meta_box(
            'rinac_slot_details',
            __( 'Detalles de slot', 'rinac' ),
            array( $this, 'renderMetaBox' ),
            'rinac_slot',
            'normal',
            'high'
        );
    }

    public function renderMetaBox( WP_Post $post ): void {
        $label = (string) get_post_meta( $post->ID, '_rinac_slot_label', true );
        $start = (string) get_post_meta( $post->ID, '_rinac_slot_start_time', true );
        $end = (string) get_post_meta( $post->ID, '_rinac_slot_end_time', true );
        $capacity_max = (int) get_post_meta( $post->ID, '_rinac_capacity_max', true );
        $capacity_min = (int) get_post_meta( $post->ID, '_rinac_capacity_min', true );
        $sort_order = (int) get_post_meta( $post->ID, '_rinac_slot_sort_order', true );
        $is_active = (string) get_post_meta( $post->ID, '_rinac_slot_is_active', true );
        $is_active = '' === $is_active ? '1' : $is_active;

        wp_nonce_field( $this->nonceAction, $this->nonceKey );
        echo '<p><label><strong>' . esc_html__( 'Etiqueta pública', 'rinac' ) . '</strong></label><br />';
        echo '<input type="text" name="rinac_slot_label" value="' . esc_attr( $label ) . '" class="widefat" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Hora inicio (HH:MM)', 'rinac' ) . '</strong></label><br />';
        echo '<input type="time" name="rinac_slot_start_time" value="' . esc_attr( $start ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Hora fin (HH:MM)', 'rinac' ) . '</strong></label><br />';
        echo '<input type="time" name="rinac_slot_end_time" value="' . esc_attr( $end ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Capacidad máxima', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" name="rinac_capacity_max" value="' . esc_attr( (string) $capacity_max ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Capacidad mínima', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" name="rinac_capacity_min" value="' . esc_attr( (string) $capacity_min ) . '" /></p>';

        echo '<p><label><strong>' . esc_html__( 'Orden', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" name="rinac_slot_sort_order" value="' . esc_attr( (string) $sort_order ) . '" /></p>';

        echo '<p><label>';
        echo '<input type="checkbox" name="rinac_slot_is_active" value="1" ' . checked( '1', $is_active, false ) . ' />';
        echo ' ' . esc_html__( 'Activo', 'rinac' ) . '</label></p>';
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

        $label = isset( $post_data['rinac_slot_label'] ) ? sanitize_text_field( wp_unslash( (string) $post_data['rinac_slot_label'] ) ) : '';
        $start = isset( $post_data['rinac_slot_start_time'] ) ? sanitize_text_field( wp_unslash( (string) $post_data['rinac_slot_start_time'] ) ) : '';
        $end = isset( $post_data['rinac_slot_end_time'] ) ? sanitize_text_field( wp_unslash( (string) $post_data['rinac_slot_end_time'] ) ) : '';
        $capacity_max = isset( $post_data['rinac_capacity_max'] ) ? absint( $post_data['rinac_capacity_max'] ) : 0;
        $capacity_min = isset( $post_data['rinac_capacity_min'] ) ? absint( $post_data['rinac_capacity_min'] ) : 0;
        $sort_order = isset( $post_data['rinac_slot_sort_order'] ) ? absint( $post_data['rinac_slot_sort_order'] ) : 0;
        $is_active = isset( $post_data['rinac_slot_is_active'] ) ? 1 : 0;

        update_post_meta( $post_id, '_rinac_slot_label', $label );
        update_post_meta( $post_id, '_rinac_slot_start_time', $start );
        update_post_meta( $post_id, '_rinac_slot_end_time', $end );
        update_post_meta( $post_id, '_rinac_capacity_max', $capacity_max );
        update_post_meta( $post_id, '_rinac_capacity_min', $capacity_min );
        update_post_meta( $post_id, '_rinac_slot_sort_order', $sort_order );
        update_post_meta( $post_id, '_rinac_slot_is_active', $is_active );
    }
}
