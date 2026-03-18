<!--
Pantalla: RINAC → Rangos horarios
Variables: $rango_id, $rango_data, $rangos, $productos_por_rango, $strings
-->

<div class="wrap rinac-admin-form rangos-horarios-form">
    <h1><?php echo esc_html($strings['page_title'] ?? __('Gestión de Rangos Horarios', 'rinac')); ?></h1>

    <h2><?php echo !empty($rango_id) ? esc_html__('Editar Rango Horario', 'rinac') : esc_html__('Nuevo Rango Horario', 'rinac'); ?></h2>
    <p><?php esc_html_e('Los rangos horarios permiten crear plantillas reutilizables de horarios para asignar a diferentes productos.', 'rinac'); ?></p>

    <form method="post">
        <?php wp_nonce_field('rinac_rangos', 'rinac_nonce'); ?>
        <input type="hidden" name="rango_id" value="<?php echo esc_attr($rango_id ?? ''); ?>">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="rango-nombre"><?php esc_html_e('Nombre del Rango', 'rinac'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="rango-nombre"
                            name="nombre"
                            value="<?php echo esc_attr($rango_data['nombre'] ?? ''); ?>"
                            class="regular-text"
                            required
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="rango-estado"><?php esc_html_e('Estado', 'rinac'); ?></label>
                    </th>
                    <td>
                        <select id="rango-estado" name="estado">
                            <option value="activo" <?php selected($rango_data['estado'] ?? 'activo', 'activo'); ?>><?php esc_html_e('Activo', 'rinac'); ?></option>
                            <option value="inactivo" <?php selected($rango_data['estado'] ?? '', 'inactivo'); ?>><?php esc_html_e('Inactivo', 'rinac'); ?></option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit" style="margin-top: 0;">
            <button type="submit" name="submit_rango" value="1" class="button button-primary">
                <?php echo !empty($rango_id) ? esc_html__('Actualizar Rango', 'rinac') : esc_html__('Crear Rango', 'rinac'); ?>
            </button>
            <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=rinac-rangos')); ?>">
                <?php esc_html_e('Cancelar', 'rinac'); ?>
            </a>
        </p>
    </form>

    <hr>

    <h2><?php esc_html_e('Rangos existentes', 'rinac'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('ID', 'rinac'); ?></th>
                <th><?php esc_html_e('Nombre', 'rinac'); ?></th>
                <th><?php esc_html_e('Estado', 'rinac'); ?></th>
                <th><?php esc_html_e('Nº productos', 'rinac'); ?></th>
                <th><?php esc_html_e('Acciones', 'rinac'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rangos)): ?>
                <?php foreach ($rangos as $rango): ?>
                    <?php
                    $id = intval($rango['id']);
                    $count = isset($productos_por_rango[$id]) ? intval($productos_por_rango[$id]) : 0;

                    $edit_url = add_query_arg(
                        array('page' => 'rinac-rangos', 'rango_id' => $id),
                        admin_url('admin.php')
                    );
                    $delete_url = wp_nonce_url(
                        add_query_arg(
                            array('page' => 'rinac-rangos', 'action' => 'rinac_delete_rango', 'rango_id' => $id),
                            admin_url('admin.php')
                        ),
                        'rinac_delete_rango_' . $id
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html($id); ?></td>
                        <td><?php echo esc_html($rango['nombre']); ?></td>
                        <td><?php echo esc_html($rango['estado'] ?? 'activo'); ?></td>
                        <td><?php echo esc_html($count); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url($edit_url); ?>" title="<?php echo esc_attr__('Editar', 'rinac'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <a
                                class="button button-small button-link-delete"
                                href="<?php echo esc_url($delete_url); ?>"
                                title="<?php echo esc_attr__('Borrar', 'rinac'); ?>"
                                onclick="return confirm('<?php echo esc_js(__('¿Seguro que quieres borrar este rango?', 'rinac')); ?>');"
                            >
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No hay rangos creados todavía.', 'rinac'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

