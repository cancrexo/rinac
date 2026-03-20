<?php
/**
 * Plugin Name: RINAC : Reservas Is Not Another Calendar
 * Description: Plugin de reservas para WooCommerce con capacidad y calendarios por producto.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: RINAC
 * License: GPLv2 or later
 * Text Domain: rinac
 */

if (!defined('ABSPATH')) {
    exit;
}

// Composer autoload.
$autoload_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    add_action(
        'admin_notices',
        function () {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            echo '<div class="notice notice-error"><p><strong>RINAC:</strong> falta <code>vendor/autoload.php</code>. Ejecuta <code>composer install</code> en el plugin.</p></div>';
        }
    );
    return;
}

// Carga el bootstrap principal.
rinac\Core\Plugin::instance()->init();

