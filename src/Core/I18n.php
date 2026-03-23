<?php

namespace RINAC\Core;

/**
 * Internacionalización (i18n).
 */
class I18n {

    /**
     * Registra los hooks de i18n.
     *
     * @return void
     */
    public function register(): void {
        // Requisito estricto: cargar `text_domain` SOLO dentro de `init`.
        add_action( 'init', array( $this, 'loadTextdomain' ), 1 );
    }

    /**
     * Carga el text domain del plugin.
     *
     * @return void
     */
    public function loadTextdomain(): void {
        $domain = defined( 'RINAC_TEXTDOMAIN' ) ? RINAC_TEXTDOMAIN : 'rinac';

        load_plugin_textdomain(
            $domain,
            false,
            trailingslashit( defined( 'RINAC_PLUGIN_DIR' ) ? RINAC_PLUGIN_DIR : dirname( __DIR__ ) . '/..' ) . 'languages'
        );

        // Opcional: en el futuro se usarán `wp_set_script_translations()` para JS.
    }
}

