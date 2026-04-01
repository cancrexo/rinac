<?php

namespace RINAC\Frontend;

/**
 * Renderiza y gestiona el bloque frontend básico de reserva.
 */
class BookingForm {

    /**
     * Registra hooks frontend.
     *
     * @return void
     */
    public function register(): void {
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'renderFormFields' ) );
    }

    /**
     * Encola assets del bloque de reserva.
     *
     * @return void
     */
    public function enqueueAssets(): void {
        $is_product_callable = 'is_product';
        if ( ! function_exists( $is_product_callable ) || ! $is_product_callable() ) {
            return;
        }

        $product_id = function_exists( 'get_the_ID' ) ? absint( get_the_ID() ) : 0;
        if ( $product_id <= 0 ) {
            return;
        }

        $wc_get_product_callable = 'wc_get_product';
        $product = $wc_get_product_callable( $product_id );
        if ( ! $product || 'rinac_reserva' !== $product->get_type() ) {
            return;
        }

        wp_enqueue_style(
            'rinac-frontend-booking',
            RINAC_URL . 'assets/rinac-frontend.css',
            array(),
            RINAC_VERSION
        );

        wp_enqueue_script(
            'rinac-frontend-booking',
            RINAC_URL . 'assets/rinac-frontend.js',
            array(),
            RINAC_VERSION,
            true
        );

        wp_localize_script(
            'rinac-frontend-booking',
            'rinacBookingConfig',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'rinac_ajax' ),
                'productId' => $product_id,
                'i18n' => array(
                    'loading' => __( 'Cargando reglas de reserva...', 'rinac' ),
                    'emptyRules' => __( 'No hay tipos de participante ni recursos configurados.', 'rinac' ),
                    'validate' => __( 'Validar selección', 'rinac' ),
                    'requestError' => __( 'No se pudo validar la solicitud.', 'rinac' ),
                    'participants' => __( 'Participantes', 'rinac' ),
                    'resources' => __( 'Recursos', 'rinac' ),
                    'estimatedTotal' => __( 'Total estimado', 'rinac' ),
                    'remainingCapacity' => __( 'Capacidad restante', 'rinac' ),
                ),
            )
        );
    }

    /**
     * Render de campos base en producto reservable.
     *
     * @return void
     */
    public function renderFormFields(): void {
        global $product;
        if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_type' ) ) {
            return;
        }
        if ( 'rinac_reserva' !== $product->get_type() ) {
            return;
        }

        echo '<div id="rinac-booking-form" class="rinac-booking-form" data-product-id="' . esc_attr( (string) $product->get_id() ) . '">';
        echo '<h4>' . esc_html__( 'Configuración de reserva', 'rinac' ) . '</h4>';

        echo '<p class="form-row form-row-first">';
        echo '<label for="rinac_start">' . esc_html__( 'Inicio', 'rinac' ) . '</label>';
        echo '<input type="date" id="rinac_start" class="input-text" />';
        echo '</p>';

        echo '<p class="form-row form-row-last">';
        echo '<label for="rinac_end">' . esc_html__( 'Fin', 'rinac' ) . '</label>';
        echo '<input type="date" id="rinac_end" class="input-text" />';
        echo '</p>';

        echo '<div class="clear"></div>';

        echo '<p class="form-row form-row-first">';
        echo '<label for="rinac_days">' . esc_html__( 'Días', 'rinac' ) . '</label>';
        echo '<input type="number" min="1" step="1" value="1" id="rinac_days" class="input-text" />';
        echo '</p>';

        echo '<p class="form-row form-row-last">';
        echo '<label for="rinac_nights">' . esc_html__( 'Noches', 'rinac' ) . '</label>';
        echo '<input type="number" min="1" step="1" value="1" id="rinac_nights" class="input-text" />';
        echo '</p>';

        echo '<div class="clear"></div>';

        echo '<div class="rinac-booking-columns">';
        echo '<div class="rinac-booking-col">';
        echo '<h5>' . esc_html__( 'Participantes', 'rinac' ) . '</h5>';
        echo '<div id="rinac-participants-list"></div>';
        echo '</div>';
        echo '<div class="rinac-booking-col">';
        echo '<h5>' . esc_html__( 'Recursos', 'rinac' ) . '</h5>';
        echo '<div id="rinac-resources-list"></div>';
        echo '</div>';
        echo '</div>';

        echo '<p>';
        echo '<button type="button" id="rinac-validate-booking" class="button alt">' . esc_html__( 'Validar selección', 'rinac' ) . '</button>';
        echo '</p>';

        echo '<div id="rinac-booking-errors" class="rinac-booking-errors" aria-live="polite"></div>';
        echo '<div id="rinac-booking-summary" class="rinac-booking-summary" aria-live="polite"></div>';
        echo '</div>';
    }
}
