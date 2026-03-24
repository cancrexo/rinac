<?php

namespace RINAC\Core;

/**
 * Carga de traducciones.
 */
class I18n {

    /**
     * Carga textdomain del plugin (solo en init).
     *
     * @return void
     */
    public function loadTextdomain(): void {
        load_plugin_textdomain(
            'rinac',
            false,
            dirname( plugin_basename( RINAC_PLUGIN_FILE ) ) . '/languages'
        );
    }
}
