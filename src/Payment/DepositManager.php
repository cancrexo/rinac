<?php

namespace RINAC\Payment;

use RINAC\Booking\BookingRecordRepository;
use RINAC\Booking\BookingStatusMapper;

/**
 * Gestiona el cobro de depósito para productos `rinac_reserva`.
 */
class DepositManager {
    private BookingRecordRepository $bookingRepository;
    private BookingStatusMapper $statusMapper;

    public function __construct() {
        $this->bookingRepository = new BookingRecordRepository();
        $this->statusMapper = new BookingStatusMapper();
    }

    /**
     * Registra hooks de WooCommerce para depósito.
     *
     * @return void
     */
    public function register(): void {
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'applyDepositToCartItems' ), 20 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'renderDepositItemData' ), 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'addDepositMetaToOrderItem' ), 10, 4 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'createBookingFromOrderItem' ), 20, 4 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'syncBookingsFromOrderStatus' ), 10, 4 );
    }

    /**
     * Aplica precio a cobrar ahora según modo de pago.
     *
     * @param mixed $cart Instancia del carrito.
     * @return void
     */
    public function applyDepositToCartItems( $cart ): void {
        if ( ! $cart || ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
            return;
        }

        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( ! isset( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) {
                continue;
            }

            $product = $cart_item['data'];
            if ( ! method_exists( $product, 'get_type' ) || 'rinac_reserva' !== $product->get_type() ) {
                continue;
            }

            $product_id = method_exists( $product, 'get_id' ) ? (int) $product->get_id() : 0;
            if ( $product_id <= 0 ) {
                continue;
            }

            $base_unit_price = isset( $cart_item['rinac_full_unit_price'] )
                ? (float) $cart_item['rinac_full_unit_price']
                : (float) $product->get_price( 'edit' );

            $base_unit_price = max( 0.0, $base_unit_price );

            $payment_mode = (string) get_post_meta( $product_id, '_rinac_payment_mode', true );
            if ( '' === $payment_mode ) {
                $payment_mode = 'full';
            }

            $deposit_percentage = (float) get_post_meta( $product_id, '_rinac_deposit_percentage', true );
            $deposit_percentage = max( 0.0, min( 100.0, $deposit_percentage ) );

            $charge_now_unit_price = $base_unit_price;
            if ( 'deposit' === $payment_mode ) {
                $charge_now_unit_price = round( $base_unit_price * ( $deposit_percentage / 100 ), 2 );
            }

            $charge_now_unit_price = max( 0.0, $charge_now_unit_price );
            $pending_unit_price = max( 0.0, round( $base_unit_price - $charge_now_unit_price, 2 ) );

            if ( method_exists( $product, 'set_price' ) ) {
                $product->set_price( $charge_now_unit_price );
            }

            $quantity = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;

            $cart->cart_contents[ $cart_item_key ]['rinac_full_unit_price'] = $base_unit_price;
            $cart->cart_contents[ $cart_item_key ]['rinac_charge_now_unit_price'] = $charge_now_unit_price;
            $cart->cart_contents[ $cart_item_key ]['rinac_pending_unit_price'] = $pending_unit_price;
            $cart->cart_contents[ $cart_item_key ]['rinac_payment_mode'] = $payment_mode;
            $cart->cart_contents[ $cart_item_key ]['rinac_deposit_percentage'] = $deposit_percentage;
            $cart->cart_contents[ $cart_item_key ]['rinac_charge_now_total'] = round( $charge_now_unit_price * $quantity, 2 );
            $cart->cart_contents[ $cart_item_key ]['rinac_pending_total'] = round( $pending_unit_price * $quantity, 2 );
        }
    }

    /**
     * Añade desglose de pago en carrito/checkout.
     *
     * @param array<int,array<string,mixed>> $item_data
     * @param array<string,mixed> $cart_item
     * @return array<int,array<string,mixed>>
     */
    public function renderDepositItemData( array $item_data, array $cart_item ): array {
        if ( ! isset( $cart_item['rinac_payment_mode'] ) ) {
            return $item_data;
        }

        $payment_mode = (string) $cart_item['rinac_payment_mode'];
        $full_total = isset( $cart_item['rinac_full_unit_price'], $cart_item['quantity'] )
            ? round( (float) $cart_item['rinac_full_unit_price'] * (int) $cart_item['quantity'], 2 )
            : 0.0;
        $charge_now = isset( $cart_item['rinac_charge_now_total'] ) ? (float) $cart_item['rinac_charge_now_total'] : 0.0;
        $pending = isset( $cart_item['rinac_pending_total'] ) ? (float) $cart_item['rinac_pending_total'] : 0.0;
        $deposit_percentage = isset( $cart_item['rinac_deposit_percentage'] ) ? (float) $cart_item['rinac_deposit_percentage'] : 0.0;

        $item_data[] = array(
            'key' => __( 'Modo de pago', 'rinac' ),
            'value' => 'deposit' === $payment_mode
                ? sprintf(
                    /* translators: %s is percentage. */
                    __( 'Depósito (%s%%)', 'rinac' ),
                    $this->wcFormatDecimal( $deposit_percentage, 2 )
                )
                : __( 'Pago completo', 'rinac' ),
        );

        $item_data[] = array(
            'key' => __( 'Total servicio', 'rinac' ),
            'value' => $this->wcPrice( $full_total ),
        );

        $item_data[] = array(
            'key' => __( 'A cobrar ahora', 'rinac' ),
            'value' => $this->wcPrice( $charge_now ),
        );

        if ( $pending > 0 ) {
            $item_data[] = array(
                'key' => __( 'Pendiente de cobro', 'rinac' ),
                'value' => $this->wcPrice( $pending ),
            );
        }

        return $item_data;
    }

    /**
     * Persiste metadatos de depósito en la línea del pedido.
     *
     * @param mixed $item Línea de pedido.
     * @param string $cart_item_key Clave del carrito.
     * @param array<string,mixed> $values Valores del carrito.
     * @param mixed $order Pedido.
     * @return void
     */
    public function addDepositMetaToOrderItem( $item, string $cart_item_key, array $values, $order ): void {
        if ( ! $item || ! is_object( $item ) || ! method_exists( $item, 'add_meta_data' ) ) {
            return;
        }

        if ( ! isset( $values['rinac_payment_mode'] ) ) {
            return;
        }

        $item->add_meta_data( '_rinac_payment_mode', (string) $values['rinac_payment_mode'], true );
        $item->add_meta_data( '_rinac_deposit_percentage', isset( $values['rinac_deposit_percentage'] ) ? (float) $values['rinac_deposit_percentage'] : 0.0, true );
        $item->add_meta_data( '_rinac_full_unit_price', isset( $values['rinac_full_unit_price'] ) ? (float) $values['rinac_full_unit_price'] : 0.0, true );
        $item->add_meta_data( '_rinac_charge_now_unit_price', isset( $values['rinac_charge_now_unit_price'] ) ? (float) $values['rinac_charge_now_unit_price'] : 0.0, true );
        $item->add_meta_data( '_rinac_pending_unit_price', isset( $values['rinac_pending_unit_price'] ) ? (float) $values['rinac_pending_unit_price'] : 0.0, true );
        $item->add_meta_data( '_rinac_charge_now_total', isset( $values['rinac_charge_now_total'] ) ? (float) $values['rinac_charge_now_total'] : 0.0, true );
        $item->add_meta_data( '_rinac_pending_total', isset( $values['rinac_pending_total'] ) ? (float) $values['rinac_pending_total'] : 0.0, true );
    }

    /**
     * Crea un post `rinac_booking` mínimo vinculado a la línea de pedido.
     *
     * @param mixed $item Línea de pedido.
     * @param string $cart_item_key Clave de carrito.
     * @param array<string,mixed> $values Valores de carrito.
     * @param mixed $order Pedido WooCommerce.
     * @return void
     */
    public function createBookingFromOrderItem( $item, string $cart_item_key, array $values, $order ): void {
        if ( ! $item || ! is_object( $item ) ) {
            return;
        }

        if ( ! method_exists( $item, 'get_product' ) || ! method_exists( $item, 'get_product_id' ) || ! method_exists( $item, 'get_quantity' ) ) {
            return;
        }

        $product = $item->get_product();
        if ( ! $product || ! method_exists( $product, 'get_type' ) || 'rinac_reserva' !== $product->get_type() ) {
            return;
        }

        if ( ! $order || ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
            return;
        }

        $order_id = (int) $order->get_id();
        $order_item_id = method_exists( $item, 'get_id' ) ? (int) $item->get_id() : 0;
        $product_id = (int) $item->get_product_id();
        $equivalent_qty = isset( $values['rinac_capacity_equivalent_total'] ) && is_numeric( $values['rinac_capacity_equivalent_total'] )
            ? (float) $values['rinac_capacity_equivalent_total']
            : (float) $item->get_quantity();
        $slot_id = isset( $values['rinac_slot_id'] ) ? (int) $values['rinac_slot_id'] : 0;
        $start = isset( $values['rinac_start'] ) ? sanitize_text_field( (string) $values['rinac_start'] ) : '';
        $end = isset( $values['rinac_end'] ) ? sanitize_text_field( (string) $values['rinac_end'] ) : '';

        $existing_booking_id = $this->bookingRepository->findByOrderItemId( $order_item_id );
        if ( $existing_booking_id > 0 ) {
            return;
        }

        $booking_id = $this->bookingRepository->create(
            array(
                'post_status' => 'pending',
                'post_title' => sprintf(
                    /* translators: 1: order id, 2: product id. */
                    __( 'Reserva pedido #%1$d producto #%2$d', 'rinac' ),
                    $order_id,
                    $product_id
                ),
                'product_id' => $product_id,
                'slot_id' => $slot_id,
                'order_id' => $order_id,
                'order_item_id' => $order_item_id,
                'start' => $start,
                'end' => $end,
                'equivalent_qty' => max( 0.0, $equivalent_qty ),
                'booking_status' => 'hold',
            ),
        );

        if ( is_wp_error( $booking_id ) || ! is_numeric( $booking_id ) ) {
            return;
        }

        $booking_id = (int) $booking_id;

        // Relación inversa útil para depuración/navegación.
        if ( method_exists( $item, 'add_meta_data' ) ) {
            $item->add_meta_data( '_rinac_booking_id', $booking_id, true );
        }
    }

    /**
     * Sincroniza reservas cuando cambia el estado de pedido.
     *
     * @param int $order_id ID del pedido.
     * @param string $old_status Estado anterior.
     * @param string $new_status Estado nuevo.
     * @param mixed $order Pedido.
     * @return void
     */
    public function syncBookingsFromOrderStatus( int $order_id, string $old_status, string $new_status, $order ): void {
        if ( $order_id <= 0 ) {
            return;
        }

        $booking_status = $this->statusMapper->mapOrderStatusToBookingStatus( $new_status );
        $post_status = $this->statusMapper->mapOrderStatusToPostStatus( $new_status );
        $booking_ids = $this->bookingRepository->findByOrderId( $order_id );
        if ( empty( $booking_ids ) ) {
            return;
        }

        foreach ( $booking_ids as $booking_id ) {
            $booking_id = (int) $booking_id;
            $this->bookingRepository->setStatuses( $booking_id, $booking_status, $post_status );
        }

        $this->invalidateAvailabilityTransients();
    }

    /**
     * Wrapper para wc_format_decimal evitando avisos de linter.
     */
    private function wcFormatDecimal( float $value, int $decimals = 2 ): string {
        $wc_format_decimal_callable = 'wc_format_decimal';
        if ( function_exists( $wc_format_decimal_callable ) ) {
            return (string) $wc_format_decimal_callable( $value, $decimals );
        }
        return number_format( $value, $decimals, '.', '' );
    }

    /**
     * Wrapper para wc_price evitando avisos de linter.
     */
    private function wcPrice( float $value ): string {
        $wc_price_callable = 'wc_price';
        if ( function_exists( $wc_price_callable ) ) {
            return (string) $wc_price_callable( $value );
        }
        return (string) $this->wcFormatDecimal( $value, 2 );
    }

    /**
     * Limpia caché de disponibilidad para evitar estados obsoletos.
     *
     * @return void
     */
    private function invalidateAvailabilityTransients(): void {
        global $wpdb;
        if ( ! $wpdb || ! isset( $wpdb->options ) ) {
            return;
        }

        $like_transient = $wpdb->esc_like( '_transient_rinac_availability_' ) . '%';
        $like_timeout = $wpdb->esc_like( '_transient_timeout_rinac_availability_' ) . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $like_transient,
                $like_timeout
            )
        );
    }
}
