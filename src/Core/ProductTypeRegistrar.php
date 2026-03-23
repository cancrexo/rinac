<?php

namespace RINAC\Core;

/**
 * Registro del tipo de producto WooCommerce `rinac_reserva`.
 */
class ProductTypeRegistrar {

    /**
     * Product type (key de WooCommerce).
     *
     * @var string
     */
    private string $productType = 'rinac_reserva';

    /**
     * Registra hooks de WooCommerce para el product type.
     *
     * @return void
     */
    public function register(): void {
        // Mapeo del product type a nuestra clase de producto.
        // En WooCommerce 3+ el filtro se llama con 4 parámetros.
        add_filter( 'woocommerce_product_class', array( $this, 'filterProductClass' ), 10, 4 );

        // Añadir el tipo al selector de admin.
        add_filter( 'product_type_selector', array( $this, 'filterProductTypeSelector' ) );

        // Requisito del documento: registrar mediante el hook (si existe en el runtime).
        add_action( 'woocommerce_register_product_type', array( $this, 'actionRegisterProductType' ), 10, 2 );

        // Disparar el hook de registro para que WooCommerce (si escucha) registre el tipo.
        add_action( 'plugins_loaded', array( $this, 'maybeTriggerRegisterProductType' ), 1 );
    }

    /**
     * Mapea el product type a la clase del producto.
     *
     * @param string $classname Clase actual.
     * @param string $product_type Product type (key).
     * @param string $context Contexto (producto vs variación, etc.).
     * @param int    $product_id ID del producto.
     * @return string
     */
    public function filterProductClass( $classname, $product_type, $context, $product_id ): string {
        if ( (string) $product_type === $this->productType ) {
            // Usamos string para evitar autoload temprano si WooCommerce no está cargado.
            return 'RINAC\\Models\\ReservaProduct';
        }

        return (string) $classname;
    }

    /**
     * Añade el product type al selector del admin.
     *
     * @param array $types Tipos existentes.
     * @return array
     */
    public function filterProductTypeSelector( $types ): array {
        if ( ! is_array( $types ) ) {
            return $types;
        }

        $types[ $this->productType ] = __( 'Reserva RINAC', 'rinac' );

        return $types;
    }

    /**
     * Callback del hook de registro de product type (seguridad).
     *
     * Nota: puede que WooCommerce en ciertas versiones no dispare este hook.
     *
     * @param mixed $product_type Product type.
     * @param mixed $classname Clase asociada.
     * @return void
     */
    public function actionRegisterProductType( $product_type, $classname ): void {
        // No hacemos nada aquí: el mapeo real lo hace `woocommerce_product_class`.
        // Este método existe para cumplir el requisito del documento y para futuras extensiones.
    }

    /**
     * Dispara el hook `woocommerce_register_product_type` cuando WooCommerce está disponible.
     *
     * @return void
     */
    public function maybeTriggerRegisterProductType(): void {
        // Si WooCommerce no está activo, no intentamos registrar.
        if ( ! function_exists( 'WC' ) && ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Usamos string para evitar autoload temprano.
        do_action( 'woocommerce_register_product_type', $this->productType, 'RINAC\\Models\\ReservaProduct' );
    }
}

