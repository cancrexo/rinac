<?php

namespace RINAC\Core;

/**
 * Registro de CPTs de RINAC.
 */
class PostTypesRegistrar {

    /**
     * Registra los hooks.
     *
     * @return void
     */
    public function register(): void {
        // Prioridad > 1 para que `I18n` cargue antes el textdomain.
        add_action( 'init', array( $this, 'registerPostTypes' ), 5 );
    }

    /**
     * Registra CPTs.
     *
     * @return void
     */
    public function registerPostTypes(): void {
        $supports = array( 'title', 'editor' );

        $common = array(
            'labels'              => array(),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_admin_bar'  => true,
            'has_archive'        => false,
            'rewrite'            => false,
            'supports'           => $supports,
            'capability_type'    => 'post',
            'menu_icon'          => 'dashicons-calendar',
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
            'rinac_turno',
            array_merge(
                $common,
                array(
                    'labels' => array(
                        'name'          => __( 'Turnos', 'rinac' ),
                        'singular_name' => __( 'Turno', 'rinac' ),
                    ),
                )
            )
        );

        register_post_type(
            'rinac_participant_type',
            array_merge(
                $common,
                array(
                    'labels' => array(
                        'name'          => __( 'Tipos de Participantes', 'rinac' ),
                        'singular_name' => __( 'Tipo de Participante', 'rinac' ),
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

