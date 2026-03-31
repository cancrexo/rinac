<?php

namespace RINAC\Admin;

use WP_Post;

/**
 * Meta boxes para gestionar metadatos de `rinac_resource`.
 */
class ResourceMetaBoxes {

    /**
     * Acción de nonce para guardado.
     *
     * @var string
     */
    private string $nonceAction = 'rinac_resource_meta_save';

    /**
     * Clave de nonce.
     *
     * @var string
     */
    private string $nonceKey = 'rinac_resource_meta_nonce';

    /**
     * Registra hooks para meta boxes y guardado.
     *
     * @return void
     */
    public function register(): void {
        add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
        add_action( 'save_post_rinac_resource', array( $this, 'saveMeta' ), 10, 2 );
    }

    /**
     * Añade meta box para recursos.
     *
     * @return void
     */
    public function addMetaBoxes(): void {
        add_meta_box(
            'rinac_resource_details',
            esc_html__( 'Detalles de recurso', 'rinac' ),
            array( $this, 'renderMetaBox' ),
            'rinac_resource',
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

        $label = (string) get_post_meta( $post->ID, '_rinac_resource_label', true );
        $resource_type = (string) get_post_meta( $post->ID, '_rinac_resource_type', true );
        if ( '' === $resource_type ) {
            $resource_type = 'addon';
        }
        $price_policy = (string) get_post_meta( $post->ID, '_rinac_resource_price_policy', true );
        if ( '' === $price_policy ) {
            $price_policy = 'none';
        }
        $price_value = (float) get_post_meta( $post->ID, '_rinac_resource_price_value', true );
        $min_qty = (int) get_post_meta( $post->ID, '_rinac_resource_min_qty', true );
        $max_qty = (int) get_post_meta( $post->ID, '_rinac_resource_max_qty', true );
        $is_active = (int) get_post_meta( $post->ID, '_rinac_resource_is_active', true );
        $sort_order = (int) get_post_meta( $post->ID, '_rinac_resource_sort_order', true );

        echo '<p>';
        echo '<label for="rinac_resource_label"><strong>' . esc_html__( 'Etiqueta pública', 'rinac' ) . '</strong></label><br />';
        echo '<input type="text" id="rinac_resource_label" name="rinac_resource_label" class="regular-text" value="' . esc_attr( $label ) . '" />';
        echo '<br /><span class="description">' . esc_html__( 'Si se deja vacío, se usará el título del post.', 'rinac' ) . '</span>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_resource_type"><strong>' . esc_html__( 'Tipo de recurso', 'rinac' ) . '</strong></label><br />';
        echo '<select id="rinac_resource_type" name="rinac_resource_type">';
        $resource_types = $this->getResourceTypes();
        foreach ( $resource_types as $type_key => $type_label ) {
            $selected = selected( $resource_type, $type_key, false );
            echo '<option value="' . esc_attr( $type_key ) . '"' . $selected . '>' . esc_html( $type_label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_resource_price_policy"><strong>' . esc_html__( 'Política de precio', 'rinac' ) . '</strong></label><br />';
        echo '<select id="rinac_resource_price_policy" name="rinac_resource_price_policy">';
        $price_policies = $this->getPricePolicies();
        foreach ( $price_policies as $policy_key => $policy_label ) {
            $selected = selected( $price_policy, $policy_key, false );
            echo '<option value="' . esc_attr( $policy_key ) . '"' . $selected . '>' . esc_html( $policy_label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_resource_price_value"><strong>' . esc_html__( 'Valor de precio', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_resource_price_value" name="rinac_resource_price_value" step="0.01" min="0" value="' . esc_attr( (string) $price_value ) . '" class="small-text" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_resource_min_qty"><strong>' . esc_html__( 'Mínimo por reserva', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_resource_min_qty" name="rinac_resource_min_qty" step="1" min="0" value="' . esc_attr( (string) $min_qty ) . '" class="small-text" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_resource_max_qty"><strong>' . esc_html__( 'Máximo por reserva', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_resource_max_qty" name="rinac_resource_max_qty" step="1" min="0" value="' . esc_attr( (string) $max_qty ) . '" class="small-text" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_resource_is_active">';
        echo '<input type="checkbox" id="rinac_resource_is_active" name="rinac_resource_is_active" value="1"' . checked( 1, $is_active, false ) . ' />';
        echo ' ' . esc_html__( 'Activo', 'rinac' ) . '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_resource_sort_order"><strong>' . esc_html__( 'Orden', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" id="rinac_resource_sort_order" name="rinac_resource_sort_order" step="1" min="0" value="' . esc_attr( (string) $sort_order ) . '" class="small-text" />';
        echo '</p>';
    }

    /**
     * Guarda metadatos del recurso.
     *
     * @param int     $post_id ID del post.
     * @param WP_Post $post    Post actual.
     * @return void
     */
    public function saveMeta( int $post_id, WP_Post $post ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        if ( 'rinac_resource' !== $post->post_type ) {
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

        $label = isset( $post_data['rinac_resource_label'] ) ? sanitize_text_field( wp_unslash( (string) $post_data['rinac_resource_label'] ) ) : '';
        update_post_meta( $post_id, '_rinac_resource_label', $label );

        $resource_types = $this->getResourceTypes();
        $resource_type = isset( $post_data['rinac_resource_type'] ) ? sanitize_key( wp_unslash( (string) $post_data['rinac_resource_type'] ) ) : 'addon';
        if ( ! array_key_exists( $resource_type, $resource_types ) ) {
            $resource_type = 'addon';
        }
        update_post_meta( $post_id, '_rinac_resource_type', $resource_type );

        $price_policies = $this->getPricePolicies();
        $price_policy = isset( $post_data['rinac_resource_price_policy'] ) ? sanitize_key( wp_unslash( (string) $post_data['rinac_resource_price_policy'] ) ) : 'none';
        if ( ! array_key_exists( $price_policy, $price_policies ) ) {
            $price_policy = 'none';
        }
        update_post_meta( $post_id, '_rinac_resource_price_policy', $price_policy );

        $price_value_raw = isset( $post_data['rinac_resource_price_value'] ) ? (float) $post_data['rinac_resource_price_value'] : 0.0;
        $price_value = max( 0.0, $price_value_raw );
        update_post_meta( $post_id, '_rinac_resource_price_value', $price_value );

        $min_qty = isset( $post_data['rinac_resource_min_qty'] ) ? max( 0, (int) $post_data['rinac_resource_min_qty'] ) : 0;
        $max_qty = isset( $post_data['rinac_resource_max_qty'] ) ? max( 0, (int) $post_data['rinac_resource_max_qty'] ) : 0;
        if ( $min_qty > 0 && $max_qty > 0 && $min_qty > $max_qty ) {
            $max_qty = $min_qty;
        }
        update_post_meta( $post_id, '_rinac_resource_min_qty', $min_qty );
        update_post_meta( $post_id, '_rinac_resource_max_qty', $max_qty );

        $is_active = isset( $post_data['rinac_resource_is_active'] ) ? 1 : 0;
        update_post_meta( $post_id, '_rinac_resource_is_active', $is_active );

        $sort_order_raw = isset( $post_data['rinac_resource_sort_order'] ) ? (int) $post_data['rinac_resource_sort_order'] : 0;
        $sort_order = max( 0, $sort_order_raw );
        update_post_meta( $post_id, '_rinac_resource_sort_order', $sort_order );
    }

    /**
     * Tipos de recurso permitidos.
     *
     * @return array<string,string>
     */
    private function getResourceTypes(): array {
        return array(
            'addon' => esc_html__( 'Extra (addon)', 'rinac' ),
            'unit'  => esc_html__( 'Unidad reservable (unit)', 'rinac' ),
        );
    }

    /**
     * Políticas de precio permitidas.
     *
     * @return array<string,string>
     */
    private function getPricePolicies(): array {
        return array(
            'none'       => esc_html__( 'Sin coste (none)', 'rinac' ),
            'fixed'      => esc_html__( 'Precio fijo (fixed)', 'rinac' ),
            'per_person' => esc_html__( 'Por persona (per_person)', 'rinac' ),
            'per_day'    => esc_html__( 'Por día (per_day)', 'rinac' ),
            'per_night'  => esc_html__( 'Por noche (per_night)', 'rinac' ),
        );
    }
}

