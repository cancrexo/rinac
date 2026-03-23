<?php

namespace RINAC\Admin;

/**
 * Página de Ajustes (stub inicial).
 */
class SettingsPage {

    /**
     * Render de la página.
     *
     * @return void
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos para acceder a esta sección.', 'rinac' ) );
        }

        $imported = isset( $_GET['rinac_demo_imported'] ) && '1' === (string) $_GET['rinac_demo_imported']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Ajustes', 'rinac' ) . '</h1>';

        if ( $imported ) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html__( 'Datos de prueba importados correctamente.', 'rinac' );
            echo '</p></div>';
        }

        echo '<h2>' . esc_html__( 'Importar datos de prueba', 'rinac' ) . '</h2>';

        $nonce = wp_create_nonce( 'rinac_import_demo' );
        $url   = admin_url( 'admin-post.php?action=rinac_import_demo&_wpnonce=' . $nonce );

        echo '<p style="color:#b32d2e;font-weight:600;">';
        echo esc_html__(
            'Atencion: este proceso importará datos mínimos de ejemplo en tu instalación.',
            'rinac'
        );
        echo '</p>';

        echo '<a class="button button-secondary" href="' . esc_url( $url ) . '">';
        echo esc_html__( 'Importar datos de prueba', 'rinac' );
        echo '</a>';

        echo '</div>';
    }
}

