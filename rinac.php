<?php
/**
 * Plugin Name: RINAC (Rinac Is Not Another Calendar)
 * Description: Plugin de reservas (Rinac Is Not Another Calendar).
 * Version: 0.1.0
 * Author: Daniel Prol
 * Author URI: https://github.com/cancrexo
 * Text Domain: rinac
 * Domain Path: /languages
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RINAC_VERSION', '0.1.0' );
define( 'RINAC_TEXTDOMAIN', 'rinac' );
define( 'RINAC_PLUGIN_FILE', __FILE__ );
define( 'RINAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RINAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$autoloader_path = RINAC_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $autoloader_path ) ) {
    require_once $autoloader_path;
} else {
    if ( is_admin() ) {
        add_action(
            'admin_notices',
            function () {
                if ( ! current_user_can( 'manage_options' ) ) {
                    return;
                }

                echo '<div class="notice notice-error"><p>';
                echo esc_html__( 'RINAC no puede cargarse porque falta Composer autoload. Ejecuta `composer install` en el plugin.', RINAC_TEXTDOMAIN );
                echo '</p></div>';
            }
        );
    }

    return;
}

register_activation_hook(
    __FILE__,
    array( 'RINAC\\Core\\Plugin', 'activate' )
);

register_deactivation_hook(
    __FILE__,
    array( 'RINAC\\Core\\Plugin', 'deactivate' )
);

$plugin = new RINAC\Core\Plugin();
$plugin->run();

