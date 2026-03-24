<?php

namespace RINAC\Core;

/**
 * Registro de CPTs base.
 */
class PostTypesRegistrar {

    /**
     * Registra CPTs de RINAC.
     *
     * @return void
     */
    public function registerPostTypes(): void {
        $common = array(
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_admin_bar'   => true,
            'has_archive'         => false,
            'rewrite'             => false,
            'supports'            => array( 'title', 'editor' ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        );

        register_post_type(
            'rinac_slot',
            array_merge(
                $common,
                array(
                    'labels' => array(
                        'name'          => __( 'Slots', 'rinac' ),
                        'singular_name' => __( 'Slot', 'rinac' ),
                    ),
                )
            )
        );

        register_post_type(
            'rinac_participant',
            array_merge(
                $common,
                array(
                    'labels' => array(
                        'name'          => __( 'Tipos de participantes', 'rinac' ),
                        'singular_name' => __( 'Tipo de participante', 'rinac' ),
                    ),
                )
            )
        );

        register_post_type(
            'rinac_resource',
            array_merge(
                $common,
                array(
                    'labels' => array(
                        'name'          => __( 'Recursos', 'rinac' ),
                        'singular_name' => __( 'Recurso', 'rinac' ),
                    ),
                )
            )
        );

        register_post_type(
            'rinac_booking',
            array_merge(
                $common,
                array(
                    'labels' => array(
                        'name'          => __( 'Reservas', 'rinac' ),
                        'singular_name' => __( 'Reserva', 'rinac' ),
                    ),
                )
            )
        );
    }
}
