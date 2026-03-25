<?php

namespace RINAC\Admin;

use WP_Post;

/**
 * Pestañas adicionales dentro de `woocommerce-product-data` para productos `rinac_reserva`.
 */
class BookingProductDataTabs {

    /**
     * Nonce action/clave para guardado.
     *
     * @var string
     */
    private string $nonceAction = 'rinac_product_data_tabs_save';

    /**
     * @var string
     */
    private string $nonceKey = 'rinac_product_data_tabs_nonce';

    /**
     * Registra filtros de WooCommerce para pestañas/panels y guardado.
     *
     * @return void
     */
    public function register(): void {
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'addTabs' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'renderPanels' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'saveMeta' ), 10, 2 );
    }

    /**
     * Añade pestañas al editor del producto.
     *
     * @param array<string,mixed> $tabs Tabs existentes.
     * @return array<string,mixed>
     */
    public function addTabs( array $tabs ): array {
        $tabs['rinac_config_producto'] = array(
            'label'    => __( 'Configuración de producto', 'rinac' ),
            'target'   => 'rinac_config_producto_data',
            'priority' => 50,
        );

        $tabs['rinac_slots'] = array(
            'label'    => __( 'Slots', 'rinac' ),
            'target'   => 'rinac_slots_data',
            'priority' => 51,
        );

        $tabs['rinac_participantes'] = array(
            'label'    => __( 'Participantes', 'rinac' ),
            'target'   => 'rinac_participantes_data',
            'priority' => 52,
        );

        $tabs['rinac_recursos'] = array(
            'label'    => __( 'Recursos', 'rinac' ),
            'target'   => 'rinac_recursos_data',
            'priority' => 53,
        );

        $tabs['rinac_disponibilidad'] = array(
            'label'    => __( 'Disponibilidad', 'rinac' ),
            'target'   => 'rinac_disponibilidad_data',
            'priority' => 54,
        );

        return $tabs;
    }

    /**
     * Renderiza los paneles correspondientes.
     *
     * @return void
     */
    public function renderPanels(): void {
        global $post;

        if ( ! ( $post instanceof WP_Post ) ) {
            return;
        }

        $product_id = (int) $post->ID;
        if ( $product_id <= 0 || ! $this->isRinacReservaProduct( $product_id ) ) {
            return;
        }

        $booking_mode = (string) get_post_meta( $product_id, 'rinac_booking_mode', true );
        $base_capacity = (int) get_post_meta( $product_id, '_rinac_base_capacity', true );
        $capacity_total_max = (int) get_post_meta( $product_id, '_rinac_capacity_total_max', true );
        $capacity_min_booking = (int) get_post_meta( $product_id, '_rinac_capacity_min_booking', true );
        $availability_rules = (string) get_post_meta( $product_id, '_rinac_availability_rules', true );
        $deposit_percentage = (float) get_post_meta( $product_id, '_rinac_deposit_percentage', true );

        $selected_slots = $this->normalizeMetaIdList( get_post_meta( $product_id, '_rinac_allowed_slots', true ) );
        $selected_participants = $this->normalizeMetaIdList( get_post_meta( $product_id, '_rinac_allowed_participant_types', true ) );
        $selected_resources = $this->normalizeMetaIdList( get_post_meta( $product_id, '_rinac_allowed_resources', true ) );

        // Nonce: se incluye solo una vez en el primer panel.
        wp_nonce_field( $this->nonceAction, $this->nonceKey );

        echo '<div id="rinac_config_producto_data" class="panel woocommerce_options_panel">';
        echo '<div class="options_group">';
        $this->renderBookingModeSelect( $booking_mode );
        $this->renderNumberField( 'rinac_base_capacity', __( 'Capacidad base', 'rinac' ), $base_capacity, 0 );
        $this->renderNumberField(
            'rinac_capacity_min_booking',
            __( 'Capacidad mínima por reserva', 'rinac' ),
            $capacity_min_booking,
            0
        );
        $this->renderNumberField( 'rinac_capacity_total_max', __( 'Capacidad global máxima', 'rinac' ), $capacity_total_max, 0 );
        $this->renderNumberField( 'rinac_deposit_percentage', __( 'Depósito (%)', 'rinac' ), (string) $deposit_percentage, 0, 100, 0.01, 'number' );
        echo '</div>';
        echo '</div>';

        echo '<div id="rinac_slots_data" class="panel woocommerce_options_panel">';
        echo '<div class="options_group">';
        $this->renderMultiSelectField( 'rinac_allowed_slots', __( 'Slots permitidos', 'rinac' ), 'rinac_slot', $selected_slots );
        echo '</div>';
        echo '</div>';

        echo '<div id="rinac_participantes_data" class="panel woocommerce_options_panel">';
        echo '<div class="options_group">';
        $this->renderMultiSelectField( 'rinac_allowed_participant_types', __( 'Tipos de participante permitidos', 'rinac' ), 'rinac_participant', $selected_participants );
        echo '</div>';
        echo '</div>';

        echo '<div id="rinac_recursos_data" class="panel woocommerce_options_panel">';
        echo '<div class="options_group">';
        $this->renderMultiSelectField( 'rinac_allowed_resources', __( 'Recursos permitidos', 'rinac' ), 'rinac_resource', $selected_resources );
        echo '</div>';
        echo '</div>';

        echo '<div id="rinac_disponibilidad_data" class="panel woocommerce_options_panel">';
        echo '<div class="options_group">';
        echo '<p class="form-field">';
        echo '<label for="rinac_availability_rules"><strong>' . esc_html__( 'Reglas de disponibilidad (opcional)', 'rinac' ) . '</strong></label><br />';
        echo '<textarea id="rinac_availability_rules" name="rinac_availability_rules" rows="6" style="width:100%;">' . esc_textarea( $availability_rules ) . '</textarea>';
        echo '</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Guarda metadatos del producto.
     *
     * @param int $post_id ID del producto.
     * @param mixed $post Post (puede ser WP_Post según WooCommerce).
     * @return void
     */
    public function saveMeta( int $post_id, $post = null ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! $this->isRinacReservaProduct( $post_id ) ) {
            return;
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $post_data = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();

        $nonce = isset( $post_data[ $this->nonceKey ] ) ? sanitize_text_field( wp_unslash( (string) $post_data[ $this->nonceKey ] ) ) : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, $this->nonceAction ) ) {
            return;
        }

        $booking_mode = isset( $post_data['rinac_booking_mode'] ) ? sanitize_key( wp_unslash( (string) $post_data['rinac_booking_mode'] ) ) : 'date';
        if ( ! array_key_exists( $booking_mode, $this->getBookingModes() ) ) {
            $booking_mode = 'date';
        }
        update_post_meta( $post_id, 'rinac_booking_mode', $booking_mode );

        $base_capacity = isset( $post_data['rinac_base_capacity'] ) ? absint( $post_data['rinac_base_capacity'] ) : 0;
        $capacity_total_max = isset( $post_data['rinac_capacity_total_max'] ) ? absint( $post_data['rinac_capacity_total_max'] ) : 0;
        $capacity_min_booking = isset( $post_data['rinac_capacity_min_booking'] ) ? absint( $post_data['rinac_capacity_min_booking'] ) : 0;
        update_post_meta( $post_id, '_rinac_base_capacity', $base_capacity );
        update_post_meta( $post_id, '_rinac_capacity_total_max', $capacity_total_max );
        update_post_meta( $post_id, '_rinac_capacity_min_booking', $capacity_min_booking );

        $availability_rules = isset( $post_data['rinac_availability_rules'] ) ? sanitize_textarea_field( wp_unslash( (string) $post_data['rinac_availability_rules'] ) ) : '';
        update_post_meta( $post_id, '_rinac_availability_rules', $availability_rules );

        $deposit_percentage = isset( $post_data['rinac_deposit_percentage'] ) ? (float) $post_data['rinac_deposit_percentage'] : 0.0;
        $deposit_percentage = max( 0.0, min( 100.0, $deposit_percentage ) );
        update_post_meta( $post_id, '_rinac_deposit_percentage', $deposit_percentage );

        update_post_meta( $post_id, '_rinac_allowed_slots', $this->sanitizeIdArrayFromPost( $post_data, 'rinac_allowed_slots' ) );
        update_post_meta( $post_id, '_rinac_allowed_participant_types', $this->sanitizeIdArrayFromPost( $post_data, 'rinac_allowed_participant_types' ) );
        update_post_meta( $post_id, '_rinac_allowed_resources', $this->sanitizeIdArrayFromPost( $post_data, 'rinac_allowed_resources' ) );
    }

    /**
     * Comprueba si el producto es `rinac_reserva`.
     *
     * @param int $product_id ID del producto.
     * @return bool
     */
    private function isRinacReservaProduct( int $product_id ): bool {
        return has_term( 'rinac_reserva', 'product_type', $product_id );
    }

    /**
     * Render select booking mode.
     *
     * @param string $booking_mode Valor actual.
     * @return void
     */
    private function renderBookingModeSelect( string $booking_mode ): void {
        echo '<p class="form-field">';
        echo '<label for="rinac_booking_mode"><strong>' . esc_html__( 'Modo de reserva', 'rinac' ) . '</strong></label>';
        echo '<select id="rinac_booking_mode" name="rinac_booking_mode">';
        foreach ( $this->getBookingModes() as $mode_key => $mode_label ) {
            $selected = selected( $booking_mode, $mode_key, false );
            echo '<option value="' . esc_attr( $mode_key ) . '"' . $selected . '>' . esc_html( $mode_label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';
    }

    /**
     * Render de número.
     *
     * @param string $name Name del input.
     * @param string $label Label del campo.
     * @param mixed $value Valor actual.
     * @param int $min Mínimo.
     * @param int|null $max Máximo (opcional).
     * @param float $step Step.
     * @param string $type Tipo de input.
     * @return void
     */
    private function renderNumberField(
        string $name,
        string $label,
        $value,
        int $min,
        ?int $max = null,
        float $step = 1.0,
        string $type = 'number'
    ): void {
        $max_attr = is_null( $max ) ? '' : ' max="' . esc_attr( (string) $max ) . '"';
        echo '<p class="form-field">';
        echo '<label for="' . esc_attr( $name ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
        echo '<input type="' . esc_attr( $type ) . '" min="' . esc_attr( (string) $min ) . '" step="' . esc_attr( (string) $step ) . '"' . $max_attr . ' id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" class="small-text" />';
        echo '</p>';
    }

    /**
     * Render select múltiple para asociar CPTs.
     *
     * @param string $field_name Nombre del campo.
     * @param string $label Etiqueta.
     * @param string $post_type CPT.
     * @param array<int> $selected IDs seleccionados.
     * @return void
     */
    private function renderMultiSelectField( string $field_name, string $label, string $post_type, array $selected ): void {
        $posts = get_posts(
            array(
                'post_type'      => $post_type,
                'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        echo '<p class="form-field">';
        echo '<label for="' . esc_attr( $field_name ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
        echo '<select id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '[]" multiple="multiple" style="width:100%; min-height:120px;">';

        foreach ( $posts as $item ) {
            $id = (int) $item->ID;
            $selected_attr = in_array( $id, $selected, true ) ? ' selected="selected"' : '';
            echo '<option value="' . esc_attr( (string) $id ) . '"' . $selected_attr . '>' . esc_html( get_the_title( $id ) ) . '</option>';
        }

        echo '</select>';
        echo '</p>';
    }

    /**
     * Sanitiza array de IDs desde POST.
     *
     * @param array<mixed> $post_data Datos POST.
     * @param string $key Clave.
     * @return array<int>
     */
    private function sanitizeIdArrayFromPost( array $post_data, string $key ): array {
        if ( ! isset( $post_data[ $key ] ) || ! is_array( $post_data[ $key ] ) ) {
            return array();
        }

        $ids = array_map( 'absint', $post_data[ $key ] );
        $ids = array_filter(
            $ids,
            function ( int $id ): bool {
                return $id > 0;
            }
        );

        return array_values( array_unique( $ids ) );
    }

    /**
     * Normaliza meta a array de IDs.
     *
     * @param mixed $value Valor original.
     * @return array<int>
     */
    private function normalizeMetaIdList( $value ): array {
        if ( ! is_array( $value ) ) {
            return array();
        }

        return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
    }

    /**
     * Lista de modos de reserva permitidos.
     *
     * @return array<string,string>
     */
    private function getBookingModes(): array {
        return array(
            'date'                 => __( 'Fecha concreta', 'rinac' ),
            'date_range'           => __( 'Rango de fechas', 'rinac' ),
            'datetime'             => __( 'Fecha y hora', 'rinac' ),
            'date_range_same_time' => __( 'Rango de fechas con misma franja', 'rinac' ),
            'slot_dia'            => __( 'Slot por día', 'rinac' ),
            'unidad_rango'         => __( 'Unidad + rango de fechas', 'rinac' ),
        );
    }
}

