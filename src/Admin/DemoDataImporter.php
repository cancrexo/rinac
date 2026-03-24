<?php

namespace RINAC\Admin;

/**
 * Importación demo mínima.
 *
 * Se usa en:
 * - activación del plugin (solo si WP_DEBUG o RINAC_LOAD_DEMO_ON_ACTIVATION)
 * - botón "Importar datos de prueba" en Ajustes
 */
class DemoDataImporter {

    /**
     * Importa datos demo mínimos.
     *
     * @return void
     */
    public static function import_minimal_demo(): void {
        self::maybeInsertCpt(
            'rinac_participant_type',
            __( 'Participante demo', 'rinac' ),
            'rinac-demo-participant'
        );
        self::maybeInsertCpt(
            'rinac_resource',
            __( 'Recurso demo', 'rinac' ),
            'rinac-demo-resource'
        );
        self::maybeInsertCpt(
            'rinac_slot',
            __( 'Slot demo', 'rinac' ),
            'rinac-demo-slot'
        );
        self::maybeInsertCpt(
            'rinac_turno',
            __( 'Turno demo', 'rinac' ),
            'rinac-demo-turno'
        );

        // Si WooCommerce está disponible, crea también un producto reservable.
        if ( function_exists( 'wc_get_product' ) && taxonomy_exists( 'product_type' ) ) {
            $title = __( 'Producto reservable RINAC (demo)', 'rinac' );
            $slug  = 'rinac-demo-reserva-producto';

            $existing = get_posts(
                array(
                    'post_type'      => 'product',
                    'name'           => $slug,
                    'post_status'    => 'any',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                )
            );

            if ( empty( $existing ) ) {
                $product_id = wp_insert_post(
                    array(
                        'post_type'   => 'product',
                        'post_status' => 'publish',
                        'post_title'  => $title,
                        'post_name'   => $slug,
                    ),
                    true
                );

                if ( ! is_wp_error( $product_id ) ) {
                    wp_set_object_terms( $product_id, 'rinac_reserva', 'product_type', false );
                }
            }
        }
    }

    /**
     * Handler del botón de importación.
     *
     * @return void
     */
    public static function handle_import_demo_request(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos para importar datos.', 'rinac' ) );
        }

        check_admin_referer( 'rinac_import_demo' );

        self::import_minimal_demo();

        $referer = wp_get_referer();
        if ( is_string( $referer ) && '' !== $referer ) {
            $redirect_url = add_query_arg( 'rinac_demo_imported', '1', $referer );
        } else {
            $redirect_url = admin_url( 'admin.php?page=rinac_settings&rinac_demo_imported=1' );
        }
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Inserta un CPT si no existe.
     *
     * @param string $post_type CPT.
     * @param string $title Título.
     * @param string $slug Slug (post_name).
     * @return void
     */
    private static function maybeInsertCpt( string $post_type, string $title, string $slug ): void {
        $existing = get_posts(
            array(
                'post_type'      => $post_type,
                'name'           => $slug,
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            )
        );

        if ( ! empty( $existing ) ) {
            return;
        }

        wp_insert_post(
            array(
                'post_type'   => $post_type,
                'post_status' => 'publish',
                'post_title'  => $title,
                'post_name'   => $slug,
            )
        );
    }
}

