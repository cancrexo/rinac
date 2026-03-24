<?php

namespace RINAC\Booking;

/**
 * Lógica de depósito e integración con hooks WooCommerce.
 */
class DepositManager {

    /**
     * Registra hooks de depósito.
     *
     * @return void
     */
    public function register(): void {
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'filterAddCartItemData' ), 10, 3 );
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'actionBeforeCalculateTotals' ), 20 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'actionCreateOrderLineItemMeta' ), 10, 4 );
    }

    /**
     * Añade metadatos de depósito al item cuando llega desde frontend.
     *
     * @param array $cart_item_data Datos del item.
     * @param int   $product_id Producto.
     * @param int   $variation_id Variación.
     * @return array
     */
    public function filterAddCartItemData( array $cart_item_data, int $product_id, int $variation_id ): array {
        unset( $variation_id );

        /** @noinspection PhpUndefinedVariableInspection */
        $post = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();

        $apply_deposit = isset( $post['rinac_apply_deposit'] ) ? (bool) absint( $post['rinac_apply_deposit'] ) : false;
        if ( ! $apply_deposit ) {
            return $cart_item_data;
        }

        $deposit_percentage = $this->getDepositPercentageForProduct( $product_id );
        if ( $deposit_percentage <= 0.0 ) {
            return $cart_item_data;
        }

        $cart_item_data['rinac_apply_deposit'] = true;
        $cart_item_data['rinac_deposit_percentage'] = $deposit_percentage;

        return $cart_item_data;
    }

    /**
     * Ajusta precios del carrito para aplicar depósito.
     *
     * @param mixed $cart Carrito.
     * @return void
     */
    public function actionBeforeCalculateTotals( $cart ): void {
        if ( ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( ! is_array( $cart_item ) || ! isset( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) {
                continue;
            }

            if ( empty( $cart_item['rinac_apply_deposit'] ) ) {
                continue;
            }

            $product = $cart_item['data'];
            if ( ! method_exists( $product, 'get_type' ) || 'rinac_reserva' !== (string) $product->get_type() ) {
                continue;
            }

            $base_price = (float) $product->get_price( 'edit' );
            $deposit_percentage = isset( $cart_item['rinac_deposit_percentage'] ) ? (float) $cart_item['rinac_deposit_percentage'] : 0.0;
            if ( $base_price <= 0.0 || $deposit_percentage <= 0.0 ) {
                continue;
            }

            $deposit_amount = $this->calculateDepositAmount( $base_price, $deposit_percentage );
            $product->set_price( $deposit_amount );

            // Refrescar item con precio adaptado al depósito.
            $cart->cart_contents[ $cart_item_key ]['data'] = $product;
        }
    }

    /**
     * Persiste metadatos de depósito en línea de pedido.
     *
     * @param mixed $item Item de pedido.
     * @param mixed $cart_item_key Clave item.
     * @param array $values Valores item carrito.
     * @param mixed $order Pedido.
     * @return void
     */
    public function actionCreateOrderLineItemMeta( $item, $cart_item_key, array $values, $order ): void {
        unset( $cart_item_key, $order );

        if ( ! is_object( $item ) || ! method_exists( $item, 'add_meta_data' ) ) {
            return;
        }

        $apply_deposit = ! empty( $values['rinac_apply_deposit'] );
        if ( ! $apply_deposit ) {
            return;
        }

        $deposit_percentage = isset( $values['rinac_deposit_percentage'] ) ? (float) $values['rinac_deposit_percentage'] : 0.0;
        $item->add_meta_data( '_rinac_apply_deposit', '1', true );
        $item->add_meta_data( '_rinac_deposit_percentage', (string) $deposit_percentage, true );
    }

    /**
     * Calcula depósito para un importe base.
     *
     * @param float $base_price Importe base.
     * @param float $deposit_percentage Porcentaje depósito.
     * @return float
     */
    public function calculateDepositAmount( float $base_price, float $deposit_percentage ): float {
        $normalized = max( 0.0, min( 100.0, $deposit_percentage ) );

        return round( $base_price * ( $normalized / 100.0 ), 2 );
    }

    /**
     * Lee porcentaje de depósito del producto.
     *
     * @param int $product_id Producto.
     * @return float
     */
    public function getDepositPercentageForProduct( int $product_id ): float {
        $value = (float) get_post_meta( $product_id, '_rinac_deposit_percentage', true );

        return max( 0.0, min( 100.0, $value ) );
    }
}

