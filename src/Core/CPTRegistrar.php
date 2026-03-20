<?php

namespace rinac\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class CPTRegistrar {
    public static function register_all(): void {
        self::register_slot();
        self::register_turno();
        self::register_participant_type();
        self::register_resource();
        self::register_booking();
    }

    /**
     * Slots horarios para disponibilidad por producto.
     */
    private static function register_slot(): void {
        register_post_type(
            self::slot_slug(),
            [
                'labels' => [
                    'name' => __('Slots', Constants::TEXT_DOMAIN),
                    'singular_name' => __('Slot', Constants::TEXT_DOMAIN),
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'hierarchical' => false,
                'supports' => ['title'],
                'menu_icon' => 'dashicons-clock',
                'rewrite' => false,
            ]
        );
    }

    private static function register_turno(): void {
        register_post_type(
            self::turno_slug(),
            [
                'labels' => [
                    'name' => __('Turnos', Constants::TEXT_DOMAIN),
                    'singular_name' => __('Turno', Constants::TEXT_DOMAIN),
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'hierarchical' => false,
                'supports' => ['title'],
                'menu_icon' => 'dashicons-calendar-alt',
                'rewrite' => false,
            ]
        );
    }

    private static function register_participant_type(): void {
        register_post_type(
            self::participant_type_slug(),
            [
                'labels' => [
                    'name' => __('Tipos de participantes', Constants::TEXT_DOMAIN),
                    'singular_name' => __('Tipo de participante', Constants::TEXT_DOMAIN),
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'hierarchical' => false,
                'supports' => ['title'],
                'menu_icon' => 'dashicons-admin-users',
                'rewrite' => false,
            ]
        );
    }

    private static function register_resource(): void {
        register_post_type(
            self::resource_slug(),
            [
                'labels' => [
                    'name' => __('Recursos', Constants::TEXT_DOMAIN),
                    'singular_name' => __('Recurso', Constants::TEXT_DOMAIN),
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'hierarchical' => false,
                'supports' => ['title'],
                'menu_icon' => 'dashicons-admin-home',
                'rewrite' => false,
            ]
        );
    }

    /**
     * Reservas confirmadas (creadas al final del checkout).
     * Esta pantalla se ampliará más adelante (fase 8).
     */
    private static function register_booking(): void {
        register_post_type(
            self::booking_slug(),
            [
                'labels' => [
                    'name' => __('Reservas', Constants::TEXT_DOMAIN),
                    'singular_name' => __('Reserva', Constants::TEXT_DOMAIN),
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'show_in_rest' => false,
                'hierarchical' => false,
                'supports' => ['title', 'custom-fields'],
                'menu_icon' => 'dashicons-yes',
                'rewrite' => false,
            ]
        );
    }

    private static function slot_slug(): string {
        return Constants::PREFIX . 'slot';
    }

    private static function turno_slug(): string {
        return Constants::PREFIX . 'turno';
    }

    private static function participant_type_slug(): string {
        return Constants::PREFIX . 'participant_type';
    }

    private static function resource_slug(): string {
        return Constants::PREFIX . 'resource';
    }

    private static function booking_slug(): string {
        return Constants::PREFIX . 'booking';
    }
}

