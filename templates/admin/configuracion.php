<?php
/**
 * Template: Página de Configuración del Admin
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap rinac-admin">
    <h1><?php echo esc_html($strings['page_title']); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rinac_config', 'rinac_config_nonce'); ?>
        
        <div class="rinac-admin-content">
            <!-- Configuración General -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php echo esc_html($strings['general_settings']); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="max_personas_default"><?php echo esc_html($strings['max_personas_default']); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="max_personas_default" 
                                       name="max_personas_default" 
                                       value="<?php echo esc_attr($config['max_personas_default']); ?>" 
                                       min="1" 
                                       max="100" 
                                       class="small-text" />
                                <p class="description"><?php _e('Número máximo de personas por horario por defecto para nuevos productos.', 'rinac'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="rango_calendario"><?php echo esc_html($strings['rango_calendario']); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="rango_calendario" 
                                       name="rango_calendario" 
                                       value="<?php echo esc_attr($config['rango_calendario']); ?>" 
                                       min="30" 
                                       max="730" 
                                       class="small-text" />
                                <p class="description"><?php _e('Número de días hacia el futuro que se mostrarán en el calendario.', 'rinac'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php echo esc_html($strings['telefono_obligatorio']); ?></th>
                            <td>
                                <fieldset>
                                    <label for="telefono_obligatorio">
                                        <input type="checkbox" 
                                               id="telefono_obligatorio" 
                                               name="telefono_obligatorio" 
                                               value="1" 
                                               <?php checked($config['telefono_obligatorio'], 1); ?> />
                                        <?php _e('Hacer que el teléfono sea obligatorio en el formulario de reserva', 'rinac'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Configuración de Email -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php echo esc_html($strings['email_settings']); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php echo esc_html($strings['email_notificaciones']); ?></th>
                            <td>
                                <fieldset>
                                    <label for="email_notificaciones">
                                        <input type="checkbox" 
                                               id="email_notificaciones" 
                                               name="email_notificaciones" 
                                               value="1" 
                                               <?php checked($config['email_notificaciones'], 1); ?> />
                                        <?php _e('Enviar notificaciones por email cuando se hagan nuevas reservas', 'rinac'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="email_admin"><?php echo esc_html($strings['email_admin']); ?></label>
                            </th>
                            <td>
                                <input type="email" 
                                       id="email_admin" 
                                       name="email_admin" 
                                       value="<?php echo esc_attr($config['email_admin']); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Email donde se enviarán las notificaciones de nuevas reservas.', 'rinac'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Información del Sistema -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('Información del Sistema', 'rinac'); ?></h2>
                </div>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Versión de RINAC', 'rinac'); ?></th>
                            <td><code><?php echo esc_html(RINAC_VERSION); ?></code></td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Versión de WordPress', 'rinac'); ?></th>
                            <td><code><?php echo esc_html(get_bloginfo('version')); ?></code></td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Versión de WooCommerce', 'rinac'); ?></th>
                            <td><code><?php echo class_exists('WooCommerce') ? esc_html(WC()->version) : __('No instalado', 'rinac'); ?></code></td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Versión de PHP', 'rinac'); ?></th>
                            <td><code><?php echo esc_html(PHP_VERSION); ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <?php submit_button($strings['save_changes'], 'primary', 'submit_config'); ?>
    </form>
</div>

<style>
.rinac-admin .postbox {
    margin-bottom: 20px;
}

.rinac-admin .postbox-header h2 {
    font-size: 14px;
    padding: 8px 12px;
    margin: 0;
    line-height: 1.4;
}

.rinac-admin .inside {
    padding: 0 12px 12px;
}

.rinac-admin .form-table th {
    width: 200px;
    padding: 20px 10px 20px 0;
}

.rinac-admin .form-table td {
    padding: 15px 10px;
}

.rinac-admin .description {
    font-style: italic;
    color: #666;
}

.rinac-admin code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}
</style>
