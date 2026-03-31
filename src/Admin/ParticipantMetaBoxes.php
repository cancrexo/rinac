<?php

namespace RINAC\Admin;

use WP_Post;

/**
 * Meta boxes para gestionar metadatos de `rinac_participant`.
 */
class ParticipantMetaBoxes {

    /**
     * Acción de nonce para guardado.
     *
     * @var string
     */
    private string $nonceAction = 'rinac_participant_meta_save';

    /**
     * Clave de nonce.
     *
     * @var string
     */
    private string $nonceKey = 'rinac_participant_meta_nonce';

    /**
     * Registra hooks para meta boxes y guardado.
     *
     * @return void
     */
    public function register(): void {
        add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
        add_action( 'save_post_rinac_participant', array( $this, 'saveMeta' ), 10, 2 );
    }

    /**
     * Añade meta box para tipos de participante.
     *
     * @return void
     */
    public function addMetaBoxes(): void {
        add_meta_box(
            'rinac_participant_details',
            esc_html__( 'Detalles de tipo de participante', 'rinac' ),
            array( $this, 'renderMetaBox' ),
            'rinac_participant',
            'normal',
            'default'
        );
    }

    /**
     * Renderiza el meta box.
     *
     * @param WP_Post $post Post actual.
     * @return void
     */
    public function renderMetaBox( WP_Post $post ): void {
        wp_nonce_field( $this->nonceAction, $this->nonceKey );

        $label = (string) get_post_meta( $post->ID, '_rinac_pt_label', true );
        $capacity_fraction = (float) get_post_meta( $post->ID, '_rinac_pt_capacity_fraction', true );
        if ( $capacity_fraction <= 0 ) {
            $capacity_fraction = 1.0;
        }
        $price_type = (string) get_post_meta( $post->ID, '_rinac_pt_price_type', true );
        if ( '' === $price_type ) {
            $price_type = 'free';
        }
        $price_value = (float) get_post_meta( $post->ID, '_rinac_pt_price_value', true );
        $min_qty = (int) get_post_meta( $post->ID, '_rinac_pt_min_qty', true );
        $max_qty = (int) get_post_meta( $post->ID, '_rinac_pt_max_qty', true );
        $is_active = (int) get_post_meta( $post->ID, '_rinac_pt_is_active', true );
        $sort_order = (int) get_post_meta( $post->ID, '_rinac_pt_sort_order', true );

        echo '<p>';
        echo '<label for="rinac_pt_label"><strong>' . esc_html__( 'Etiqueta pública', 'rinac' ) . '</strong></label><br />';
        echo '<input type="text" id="rinac_pt_label" name="rinac_pt_label" class="regular-text" value="' . esc_attr( $label ) . '" />';
        echo '<br /><span class="description">' . esc_html__( 'Si se deja vacío, se usará el título del post.', 'rinac' ) . '</span>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_pt_capacity_fraction"><strong>' . esc_html__( 'Fracción de capacidad', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_pt_capacity_fraction" name="rinac_pt_capacity_fraction" step="0.1" min="0.1" value="' . esc_attr( (string) $capacity_fraction ) . '" class="small-text" />';
        echo '<br /><span class="description">' . esc_html__( 'Cuánta capacidad consume una unidad de este tipo (1 = persona completa, 0.5 = medio).', 'rinac' ) . '</span>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_pt_price_type"><strong>' . esc_html__( 'Tipo de precio', 'rinac' ) . '</strong></label><br />';
        echo '<select id="rinac_pt_price_type" name="rinac_pt_price_type">';
        $price_types = $this->getPriceTypes();
        foreach ( $price_types as $type_key => $type_label ) {
            $selected = selected( $price_type, $type_key, false );
            echo '<option value="' . esc_attr( $type_key ) . '"' . $selected . '>' . esc_html( $type_label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_pt_price_value"><strong>' . esc_html__( 'Valor de precio', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_pt_price_value" name="rinac_pt_price_value" step="0.01" min="0" value="' . esc_attr( (string) $price_value ) . '" class="small-text" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_pt_min_qty"><strong>' . esc_html__( 'Mínimo por reserva', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_pt_min_qty" name="rinac_pt_min_qty" step="1" min="0" value="' . esc_attr( (string) $min_qty ) . '" class="small-text" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_pt_max_qty"><strong>' . esc_html__( 'Máximo por reserva', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_pt_max_qty" name="rinac_pt_max_qty" step="1" min="0" value="' . esc_attr( (string) $max_qty ) . '" class="small-text" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_pt_is_active">';
        echo '<input type="checkbox" id="rinac_pt_is_active" name="rinac_pt_is_active" value="1"' . checked( 1, $is_active, false ) . ' />';
        echo ' ' . esc_html__( 'Activo', 'rinac' ) . '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_pt_sort_order"><strong>' . esc_html__( 'Orden', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_pt_sort_order" name="rinac_pt_sort_order" step="1" min="0" value="' . esc_attr( (string) $sort_order ) . '" class="small-text" />';
        echo '</p>';
    }

    /**
     * Guarda los metadatos del tipo de participante.
     *
     * @param int     $post_id ID del post.
     * @param WP_Post $post    Instancia del post.
     * @return void
     */
    public function saveMeta( int $post_id, WP_Post $post ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        if ( 'rinac_participant' !== $post->post_type ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $post_data = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();

        $nonce = isset( $post_data[ $this->nonceKey ] ) ? sanitize_text_field( wp_unslash( (string) $post_data[ $this->nonceKey ] ) ) : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, $this->nonceAction ) ) {
            return;
        }

        $label = isset( $post_data['rinac_pt_label'] ) ? sanitize_text_field( wp_unslash( (string) $post_data['rinac_pt_label'] ) ) : '';
        update_post_meta( $post_id, '_rinac_pt_label', $label );

        $capacity_fraction_raw = isset( $post_data['rinac_pt_capacity_fraction'] ) ? (float) $post_data['rinac_pt_capacity_fraction'] : 1.0;
        $capacity_fraction = $capacity_fraction_raw > 0.0 ? $capacity_fraction_raw : 1.0;
        update_post_meta( $post_id, '_rinac_pt_capacity_fraction', $capacity_fraction );

        $price_types = $this->getPriceTypes();
        $price_type = isset( $post_data['rinac_pt_price_type'] ) ? sanitize_key( wp_unslash( (string) $post_data['rinac_pt_price_type'] ) ) : 'free';
        if ( ! array_key_exists( $price_type, $price_types ) ) {
            $price_type = 'free';
        }
        update_post_meta( $post_id, '_rinac_pt_price_type', $price_type );

        $price_value_raw = isset( $post_data['rinac_pt_price_value'] ) ? (float) $post_data['rinac_pt_price_value'] : 0.0;
        $price_value = max( 0.0, $price_value_raw );
        update_post_meta( $post_id, '_rinac_pt_price_value', $price_value );

        $min_qty = isset( $post_data['rinac_pt_min_qty'] ) ? max( 0, (int) $post_data['rinac_pt_min_qty'] ) : 0;
        $max_qty = isset( $post_data['rinac_pt_max_qty'] ) ? max( 0, (int) $post_data['rinac_pt_max_qty'] ) : 0;
        if ( $min_qty > 0 && $max_qty > 0 && $min_qty > $max_qty ) {
            $max_qty = $min_qty;
        }
        update_post_meta( $post_id, '_rinac_pt_min_qty', $min_qty );
        update_post_meta( $post_id, '_rinac_pt_max_qty', $max_qty );

        $is_active = isset( $post_data['rinac_pt_is_active'] ) ? 1 : 0;
        update_post_meta( $post_id, '_rinac_pt_is_active', $is_active );

        $sort_order_raw = isset( $post_data['rinac_pt_sort_order'] ) ? (int) $post_data['rinac_pt_sort_order'] : 0;
        $sort_order = max( 0, $sort_order_raw );
        update_post_meta( $post_id, '_rinac_pt_sort_order', $sort_order );
    }

    /**
     * Tipos de precio permitidos.
     *
     * @return array<string,string>
     */
    private function getPriceTypes(): array {
        return array(
            'free'  => esc_html__( 'Sin recargo (free)', 'rinac' ),
            'fixed' => esc_html__( 'Precio fijo por unidad (fixed)', 'rinac' ),
        );
    }
}

