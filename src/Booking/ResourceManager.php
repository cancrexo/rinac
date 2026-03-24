<?php

namespace RINAC\Booking;

/**
 * Gestiona recursos asociados a una reserva.
 */
class ResourceManager {

    /**
     * Punto de entrada para hooks futuros.
     *
     * @return void
     */
    public function register(): void {
        // Sin hooks en este paso. Se integrará con checkout y admin avanzado más adelante.
    }

    /**
     * Normaliza recursos enviados desde request.
     *
     * Formato esperado:
     * - array( array( 'resource_id' => 123, 'qty' => 1 ), ... )
     *
     * @param mixed $resources_raw Datos crudos.
     * @return array
     */
    public function normalizeResources( $resources_raw ): array {
        if ( ! is_array( $resources_raw ) ) {
            return array();
        }

        $normalized = array();

        foreach ( $resources_raw as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $resource_id = isset( $item['resource_id'] ) ? absint( $item['resource_id'] ) : 0;
            $qty = isset( $item['qty'] ) ? absint( $item['qty'] ) : 1;

            if ( $resource_id <= 0 || $qty <= 0 ) {
                continue;
            }

            $normalized[] = array(
                'resource_id' => $resource_id,
                'qty'         => $qty,
            );
        }

        return $normalized;
    }

    /**
     * Valida recursos contra los permitidos del producto.
     *
     * @param int   $product_id Producto reservable.
     * @param array $resources Recursos normalizados.
     * @return bool
     */
    public function validateResourcesForProduct( int $product_id, array $resources ): bool {
        $allowed = get_post_meta( $product_id, '_rinac_allowed_resources', true );
        if ( ! is_array( $allowed ) ) {
            // Sin lista explícita: admitimos recursos existentes.
            $allowed = array();
        }

        foreach ( $resources as $resource ) {
            $resource_id = isset( $resource['resource_id'] ) ? absint( $resource['resource_id'] ) : 0;
            if ( $resource_id <= 0 ) {
                return false;
            }

            if ( ! empty( $allowed ) && ! in_array( $resource_id, $allowed, true ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcula coste adicional de recursos.
     *
     * @param array $resources Recursos normalizados.
     * @return float
     */
    public function calculateResourcesExtra( array $resources ): float {
        $extra = 0.0;

        foreach ( $resources as $resource ) {
            $resource_id = isset( $resource['resource_id'] ) ? absint( $resource['resource_id'] ) : 0;
            $qty = isset( $resource['qty'] ) ? absint( $resource['qty'] ) : 0;
            if ( $resource_id <= 0 || $qty <= 0 ) {
                continue;
            }

            $price = (float) get_post_meta( $resource_id, '_rinac_resource_price_value', true );
            $extra += max( 0.0, $price ) * $qty;
        }

        return $extra;
    }
}

