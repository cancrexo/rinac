<?php

namespace RINAC\Admin;

use RINAC\Models\ReservaProduct;
use WP_Post;

/**
 * Meta boxes y ajustes de producto para `rinac_reserva`.
 */
class BookingMetaBoxes {

    /**
     * Registra hooks de admin para metadatos de reserva.
     *
     * @return void
     */
    public function register(): void {
        add_action( 'admin_init', array( $this, 'registerSettings' ) );
        add_action( 'add_meta_boxes_product', array( $this, 'addMetaBoxes' ) );
        add_action( 'save_post_product', array( $this, 'saveMetaBoxes' ), 10, 2 );
    }

    /**
     * Punto de extensión para ajustes futuros de WP Settings API.
     *
     * @return void
     */
    public function registerSettings(): void {
        // Intencionalmente vacío en este paso.
    }

    /**
     * Añade meta box para productos WooCommerce.
     *
     * @return void
     */
    public function addMetaBoxes(): void {
        add_meta_box(
            'rinac_booking_settings',
            __( 'RINAC - Ajustes de reserva', 'rinac' ),
            array( $this, 'renderMetaBox' ),
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render del meta box.
     *
     * @param WP_Post $post Producto (post).
     * @return void
     */
    public function renderMetaBox( WP_Post $post ): void {
        if ( ! $this->isRinacReservaProduct( (int) $post->ID ) ) {
            echo '<p>' . esc_html__( 'Este bloque solo aplica a productos de tipo RINAC Reserva.', 'rinac' ) . '</p>';
            return;
        }

        wp_nonce_field( 'rinac_booking_meta_box_save', 'rinac_booking_meta_box_nonce' );

        $booking_mode = (string) get_post_meta( $post->ID, '_rinac_booking_mode', true );
        $business_profile = (string) get_post_meta( $post->ID, '_rinac_business_profile', true );
        $base_capacity = (int) get_post_meta( $post->ID, '_rinac_base_capacity', true );
        $capacity_total_max = (int) get_post_meta( $post->ID, '_rinac_capacity_total_max', true );
        $capacity_min_booking = (int) get_post_meta( $post->ID, '_rinac_capacity_min_booking', true );
        $availability_rules = (string) get_post_meta( $post->ID, '_rinac_availability_rules', true );
        $deposit_percentage = (float) get_post_meta( $post->ID, '_rinac_deposit_percentage', true );

        $selected_slots = $this->normalizeMetaIdList( get_post_meta( $post->ID, '_rinac_allowed_slots', true ) );
        $selected_turnos = $this->normalizeMetaIdList( get_post_meta( $post->ID, '_rinac_allowed_turnos', true ) );
        $selected_participants = $this->normalizeMetaIdList( get_post_meta( $post->ID, '_rinac_allowed_participant_types', true ) );
        $selected_resources = $this->normalizeMetaIdList( get_post_meta( $post->ID, '_rinac_allowed_resources', true ) );

        $booking_modes = $this->getBookingModes();
        $profiles = $this->getBusinessProfiles();

        echo '<p>';
        echo '<label for="rinac_booking_mode"><strong>' . esc_html__( 'Modo de reserva', 'rinac' ) . '</strong></label><br />';
        echo '<select id="rinac_booking_mode" name="rinac_booking_mode">';
        foreach ( $booking_modes as $mode_key => $mode_label ) {
            $selected = selected( $booking_mode, $mode_key, false );
            echo '<option value="' . esc_attr( $mode_key ) . '"' . $selected . '>' . esc_html( $mode_label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_business_profile"><strong>' . esc_html__( 'Perfil de negocio', 'rinac' ) . '</strong></label><br />';
        echo '<select id="rinac_business_profile" name="rinac_business_profile">';
        foreach ( $profiles as $profile_key => $profile_label ) {
            $selected = selected( $business_profile, $profile_key, false );
            echo '<option value="' . esc_attr( $profile_key ) . '"' . $selected . '>' . esc_html( $profile_label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_base_capacity"><strong>' . esc_html__( 'Capacidad base', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" id="rinac_base_capacity" name="rinac_base_capacity" value="' . esc_attr( (string) $base_capacity ) . '" class="small-text" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_capacity_total_max"><strong>' . esc_html__( 'Capacidad global máxima', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" id="rinac_capacity_total_max" name="rinac_capacity_total_max" value="' . esc_attr( (string) $capacity_total_max ) . '" class="small-text" />';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_capacity_min_booking"><strong>' . esc_html__( 'Capacidad mínima por reserva', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" step="1" id="rinac_capacity_min_booking" name="rinac_capacity_min_booking" value="' . esc_attr( (string) $capacity_min_booking ) . '" class="small-text" />';
        echo '</p>';

        $this->renderMultiSelectField(
            'rinac_allowed_slots',
            __( 'Slots permitidos', 'rinac' ),
            'rinac_slot',
            $selected_slots
        );
        $this->renderMultiSelectField(
            'rinac_allowed_turnos',
            __( 'Turnos permitidos', 'rinac' ),
            'rinac_turno',
            $selected_turnos
        );
        $this->renderMultiSelectField(
            'rinac_allowed_participant_types',
            __( 'Tipos de participante permitidos', 'rinac' ),
            'rinac_participant_type',
            $selected_participants
        );
        $this->renderMultiSelectField(
            'rinac_allowed_resources',
            __( 'Recursos permitidos', 'rinac' ),
            'rinac_resource',
            $selected_resources
        );

        echo '<p>';
        echo '<label for="rinac_availability_rules"><strong>' . esc_html__( 'Reglas de disponibilidad', 'rinac' ) . '</strong></label><br />';
        echo '<textarea id="rinac_availability_rules" name="rinac_availability_rules" rows="6" style="width:100%;">' . esc_textarea( $availability_rules ) . '</textarea>';
        echo '<br /><em>' . esc_html__( 'Campo de reglas para la fase de disponibilidad (texto o JSON).', 'rinac' ) . '</em>';
        echo '</p>';

        echo '<p>';
        echo '<label for="rinac_deposit_percentage"><strong>' . esc_html__( 'Depósito (%)', 'rinac' ) . '</strong></label><br />';
        echo '<input type="number" min="0" max="100" step="0.01" id="rinac_deposit_percentage" name="rinac_deposit_percentage" value="' . esc_attr( (string) $deposit_percentage ) . '" class="small-text" />';
        echo '</p>';
    }

    /**
     * Guarda metadatos del producto reservable.
     *
     * @param int     $post_id ID de post.
     * @param WP_Post $post Post del producto.
     * @return void
     */
    public function saveMetaBoxes( int $post_id, WP_Post $post ): void {
        if ( 'product' !== $post->post_type ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $post_data = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();

        $nonce = isset( $post_data['rinac_booking_meta_box_nonce'] ) ? sanitize_text_field( wp_unslash( $post_data['rinac_booking_meta_box_nonce'] ) ) : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'rinac_booking_meta_box_save' ) ) {
            return;
        }

        if ( ! $this->isRinacReservaProduct( $post_id ) ) {
            return;
        }

        $booking_mode = isset( $post_data['rinac_booking_mode'] ) ? sanitize_key( wp_unslash( $post_data['rinac_booking_mode'] ) ) : 'date';
        if ( ! array_key_exists( $booking_mode, $this->getBookingModes() ) ) {
            $booking_mode = 'date';
        }
        update_post_meta( $post_id, '_rinac_booking_mode', $booking_mode );

        $business_profile = isset( $post_data['rinac_business_profile'] ) ? sanitize_key( wp_unslash( $post_data['rinac_business_profile'] ) ) : 'generico';
        if ( ! array_key_exists( $business_profile, $this->getBusinessProfiles() ) ) {
            $business_profile = 'generico';
        }
        update_post_meta( $post_id, '_rinac_business_profile', $business_profile );

        $base_capacity = isset( $post_data['rinac_base_capacity'] ) ? absint( $post_data['rinac_base_capacity'] ) : 0;
        $capacity_total_max = isset( $post_data['rinac_capacity_total_max'] ) ? absint( $post_data['rinac_capacity_total_max'] ) : 0;
        $capacity_min_booking = isset( $post_data['rinac_capacity_min_booking'] ) ? absint( $post_data['rinac_capacity_min_booking'] ) : 0;

        update_post_meta( $post_id, '_rinac_base_capacity', $base_capacity );
        update_post_meta( $post_id, '_rinac_capacity_total_max', $capacity_total_max );
        update_post_meta( $post_id, '_rinac_capacity_min_booking', $capacity_min_booking );

        $availability_rules = isset( $post_data['rinac_availability_rules'] ) ? sanitize_textarea_field( wp_unslash( $post_data['rinac_availability_rules'] ) ) : '';
        update_post_meta( $post_id, '_rinac_availability_rules', $availability_rules );

        $deposit_percentage = isset( $post_data['rinac_deposit_percentage'] ) ? (float) $post_data['rinac_deposit_percentage'] : 0.0;
        $deposit_percentage = max( 0.0, min( 100.0, $deposit_percentage ) );
        update_post_meta( $post_id, '_rinac_deposit_percentage', $deposit_percentage );

        update_post_meta( $post_id, '_rinac_allowed_slots', $this->sanitizeIdArrayFromPost( $post_data, 'rinac_allowed_slots' ) );
        update_post_meta( $post_id, '_rinac_allowed_turnos', $this->sanitizeIdArrayFromPost( $post_data, 'rinac_allowed_turnos' ) );
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
        if ( function_exists( 'wc_get_product' ) ) {
            $wc_get_product_callable = 'wc_get_product';
            $product = $wc_get_product_callable( $product_id );

            if ( $product instanceof ReservaProduct ) {
                return true;
            }

            if ( is_object( $product ) && method_exists( $product, 'get_type' ) && 'rinac_reserva' === (string) $product->get_type() ) {
                return true;
            }
        }

        return has_term( 'rinac_reserva', 'product_type', $product_id );
    }

    /**
     * Dibuja select múltiple para asociar CPTs.
     *
     * @param string $field_name Nombre de campo.
     * @param string $label Etiqueta.
     * @param string $post_type CPT.
     * @param array  $selected IDs seleccionados.
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

        echo '<p>';
        echo '<label for="' . esc_attr( $field_name ) . '"><strong>' . esc_html( $label ) . '</strong></label><br />';
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
     * @param array  $post_data Datos de POST.
     * @param string $key Clave.
     * @return array
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
     * Normaliza valores meta a array de IDs.
     *
     * @param mixed $value Valor original.
     * @return array
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
     * @return array
     */
    private function getBookingModes(): array {
        return array(
            'date'                 => __( 'Fecha concreta', 'rinac' ),
            'date_range'           => __( 'Rango de fechas', 'rinac' ),
            'datetime'             => __( 'Fecha y hora', 'rinac' ),
            'date_range_same_time' => __( 'Rango de fechas con misma franja', 'rinac' ),
            'turno_dia'            => __( 'Turno por día', 'rinac' ),
            'unidad_rango'         => __( 'Unidad + rango de fechas', 'rinac' ),
        );
    }

    /**
     * Lista de perfiles de negocio permitidos.
     *
     * @return array
     */
    private function getBusinessProfiles(): array {
        return array(
            'generico'              => __( 'Genérico', 'rinac' ),
            'bodega'                => __( 'Bodega', 'rinac' ),
            'restaurante_turnos'    => __( 'Restaurante (turnos)', 'rinac' ),
            'restaurante_mesas'     => __( 'Restaurante (mesas)', 'rinac' ),
            'alquiler_coches'       => __( 'Alquiler de coches', 'rinac' ),
            'alquiler_habitaciones' => __( 'Alquiler de habitaciones', 'rinac' ),
        );
    }
}

