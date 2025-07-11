<!-- 
Item individual de hora para el formulario de rangos horarios
Variables: $index, $hora
-->

<div class="hora-item" data-index="<?php echo esc_attr($index); ?>">
    <div class="hora-time">
        <span class="drag-handle dashicons dashicons-menu"></span>
        <input type="time" 
               name="horas[<?php echo esc_attr($index); ?>][hora]" 
               value="<?php echo esc_attr($hora['hora'] ?? ''); ?>"
               data-validate="required"
               required>
    </div>
    
    <div class="hora-capacidad">
        <input type="number" 
               name="horas[<?php echo esc_attr($index); ?>][capacidad]" 
               value="<?php echo esc_attr($hora['capacidad'] ?? 10); ?>"
               min="1"
               max="100"
               data-validate="required|min:1|max:100"
               required>
    </div>
    
    <div class="hora-duracion">
        <input type="number" 
               name="horas[<?php echo esc_attr($index); ?>][duracion]" 
               value="<?php echo esc_attr($hora['duracion'] ?? 60); ?>"
               min="15"
               max="480"
               step="15"
               data-validate="required|min:15|max:480"
               required>
    </div>
    
    <div class="hora-estado">
        <select name="horas[<?php echo esc_attr($index); ?>][estado]">
            <option value="activo" <?php selected($hora['estado'] ?? 'activo', 'activo'); ?>><?php _e('Activo', 'rinac'); ?></option>
            <option value="inactivo" <?php selected($hora['estado'] ?? '', 'inactivo'); ?>><?php _e('Inactivo', 'rinac'); ?></option>
        </select>
    </div>
    
    <div class="hora-actions">
        <button type="button" class="button button-small duplicate-hora" title="<?php _e('Duplicar', 'rinac'); ?>">
            <span class="dashicons dashicons-admin-page"></span>
        </button>
        <button type="button" class="button button-small button-link-delete remove-hora" title="<?php _e('Eliminar', 'rinac'); ?>">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
</div>
