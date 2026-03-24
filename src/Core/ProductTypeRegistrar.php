<?php

namespace RINAC\Core;

use RINAC\Models\ReservaProduct;

/**
 * Registro del tipo de producto WooCommerce.
 */
class ProductTypeRegistrar {

    /**
     * Registra hooks del tipo de producto.
     *
     * @return void
     */
    public function registerProductType(): void {
        add_filter( 'product_type_selector', array( $this, 'addProductTypeSelector' ) );
        add_filter( 'woocommerce_product_class', array( $this, 'mapProductClass' ), 10, 2 );
    }

    /**
     * Añade el tipo al selector de WooCommerce.
     *
     * @param array $types Tipos existentes.
     * @return array
     */
    public function addProductTypeSelector( array $types ): array {
        $types['rinac_reserva'] = __( 'RINAC Reserva', 'rinac' );
        return $types;
    }

    /**
     * Mapea clase de producto para `rinac_reserva`.
     *
     * @param string $classname Clase actual.
     * @param string $product_type Tipo de producto.
     * @return string
     */
    public function mapProductClass( string $classname, string $product_type ): string {
        if ( 'rinac_reserva' === $product_type ) {
            return ReservaProduct::class;
        }

        return $classname;
    }
}
