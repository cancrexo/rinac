<!-- 
Formulario para gestionar rangos horarios en el admin
Variables: $rango_id, $rango_data, $horas
-->

<div class="rinac-admin-form rangos-horarios-form">
    <div class="form-header">
        <h2><?php echo $rango_id ? __('Editar Rango Horario', 'rinac') : __('Nuevo Rango Horario', 'rinac'); ?></h2>
        <p class="form-description"><?php _e('Los rangos horarios permiten crear plantillas reutilizables de horarios para asignar a diferentes productos.', 'rinac'); ?></p>
    </div>

    <form id="rangos-horarios-form" class="rinac-form" data-rinac-validate>
        <input type="hidden" name="action" value="rinac_save_rango_horario">
        <input type="hidden" name="rango_id" value="<?php echo esc_attr($rango_id ?? ''); ?>">
        <?php wp_nonce_field('rinac_save_rango_horario', 'rinac_nonce'); ?>

        <!-- Información básica -->
        <div class="form-section">
            <h3 class="section-title"><?php _e('Información General', 'rinac'); ?></h3>
            
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="rango-nombre"><?php _e('Nombre del Rango', 'rinac'); ?> <span class="required">*</span></label>
                    <input type="text" 
                           id="rango-nombre" 
                           name="nombre" 
                           value="<?php echo esc_attr($rango_data['nombre'] ?? ''); ?>"
                           placeholder="<?php _e('Ej: Horario de Verano', 'rinac'); ?>"
                           data-validate="required|minlength:3"
                           required>
                    <small class="form-help"><?php _e('Nombre descriptivo para identificar este rango', 'rinac'); ?></small>
                </div>
                
                <div class="form-group col-6">
                    <label for="rango-estado"><?php _e('Estado', 'rinac'); ?></label>
                    <select id="rango-estado" name="estado">
                        <option value="activo" <?php selected($rango_data['estado'] ?? 'activo', 'activo'); ?>><?php _e('Activo', 'rinac'); ?></option>
                        <option value="inactivo" <?php selected($rango_data['estado'] ?? '', 'inactivo'); ?>><?php _e('Inactivo', 'rinac'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="rango-descripcion"><?php _e('Descripción', 'rinac'); ?></label>
                    <textarea id="rango-descripcion" 
                              name="descripcion" 
                              rows="3"
                              placeholder="<?php _e('Descripción opcional del rango horario...', 'rinac'); ?>"><?php echo esc_textarea($rango_data['descripcion'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Configuración de horarios -->
        <div class="form-section">
            <div class="section-header">
                <h3 class="section-title"><?php _e('Horarios del Rango', 'rinac'); ?></h3>
                <div class="section-actions">
                    <button type="button" class="button button-secondary" id="add-bulk-hours">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Añadir Múltiples', 'rinac'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="add-single-hour">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Añadir Hora', 'rinac'); ?>
                    </button>
                </div>
            </div>

            <!-- Lista de horas -->
            <div class="horas-container">
                <div class="horas-header">
                    <div class="hora-col"><?php _e('Hora', 'rinac'); ?></div>
                    <div class="capacidad-col"><?php _e('Capacidad', 'rinac'); ?></div>
                    <div class="duracion-col"><?php _e('Duración (min)', 'rinac'); ?></div>
                    <div class="estado-col"><?php _e('Estado', 'rinac'); ?></div>
                    <div class="acciones-col"><?php _e('Acciones', 'rinac'); ?></div>
                </div>

                <div id="horas-list" class="horas-list">
                    <?php if (!empty($horas)): ?>
                        <?php foreach ($horas as $index => $hora): ?>
                            <?php include 'hora-item.php'; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-horas">
                            <p><?php _e('No hay horarios definidos aún.', 'rinac'); ?></p>
                            <p><?php _e('Usa los botones de arriba para añadir horarios.', 'rinac'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Configuración avanzada -->
        <div class="form-section advanced-section">
            <h3 class="section-title">
                <span class="toggle-advanced" data-target="advanced-options">
                    <span class="dashicons dashicons-arrow-right"></span>
                    <?php _e('Opciones Avanzadas', 'rinac'); ?>
                </span>
            </h3>
            
            <div id="advanced-options" class="advanced-options" style="display: none;">
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="rango-zona-horaria"><?php _e('Zona Horaria', 'rinac'); ?></label>
                        <select id="rango-zona-horaria" name="zona_horaria">
                            <option value=""><?php _e('Por defecto del sitio', 'rinac'); ?></option>
                            <?php 
                            $timezones = timezone_identifiers_list();
                            $selected_tz = $rango_data['zona_horaria'] ?? '';
                            foreach ($timezones as $tz): 
                            ?>
                                <option value="<?php echo esc_attr($tz); ?>" <?php selected($selected_tz, $tz); ?>><?php echo esc_html($tz); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group col-4">
                        <label for="rango-orden"><?php _e('Orden de Visualización', 'rinac'); ?></label>
                        <input type="number" 
                               id="rango-orden" 
                               name="orden" 
                               value="<?php echo esc_attr($rango_data['orden'] ?? 0); ?>"
                               min="0"
                               step="1">
                        <small class="form-help"><?php _e('0 = al final', 'rinac'); ?></small>
                    </div>
                    
                    <div class="form-group col-4">
                        <label for="rango-color"><?php _e('Color Identificativo', 'rinac'); ?></label>
                        <input type="color" 
                               id="rango-color" 
                               name="color" 
                               value="<?php echo esc_attr($rango_data['color'] ?? '#007cba'); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                   id="rango-aplicar-fines-semana" 
                                   name="aplicar_fines_semana" 
                                   value="1"
                                   <?php checked($rango_data['aplicar_fines_semana'] ?? 0, 1); ?>>
                            <?php _e('Aplicar también en fines de semana', 'rinac'); ?>
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                   id="rango-aplicar-festivos" 
                                   name="aplicar_festivos" 
                                   value="1"
                                   <?php checked($rango_data['aplicar_festivos'] ?? 0, 1); ?>>
                            <?php _e('Aplicar también en días festivos', 'rinac'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones del formulario -->
        <div class="form-actions">
            <div class="actions-left">
                <?php if ($rango_id): ?>
                    <button type="button" class="button button-secondary" id="duplicate-rango">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php _e('Duplicar', 'rinac'); ?>
                    </button>
                    <button type="button" class="button button-link-delete" id="delete-rango">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Eliminar', 'rinac'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="actions-right">
                <button type="button" class="button" onclick="history.back()">
                    <?php _e('Cancelar', 'rinac'); ?>
                </button>
                <button type="submit" class="button button-primary" id="save-rango">
                    <span class="dashicons dashicons-saved"></span>
                    <?php echo $rango_id ? __('Actualizar Rango', 'rinac') : __('Crear Rango', 'rinac'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal para añadir múltiples horas -->
<div id="bulk-hours-modal" class="rinac-modal medium" style="display: none;">
    <div class="rinac-modal-overlay"></div>
    <div class="rinac-modal-container">
        <div class="rinac-modal-content">
            <div class="rinac-modal-header">
                <h3 class="rinac-modal-title"><?php _e('Añadir Múltiples Horarios', 'rinac'); ?></h3>
                <button type="button" class="rinac-modal-close">&times;</button>
            </div>
            
            <div class="rinac-modal-body">
                <div class="bulk-hours-form">
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="bulk-hora-inicio"><?php _e('Hora de inicio', 'rinac'); ?></label>
                            <input type="time" id="bulk-hora-inicio" value="09:00">
                        </div>
                        <div class="form-group col-6">
                            <label for="bulk-hora-fin"><?php _e('Hora de fin', 'rinac'); ?></label>
                            <input type="time" id="bulk-hora-fin" value="18:00">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-4">
                            <label for="bulk-intervalo"><?php _e('Intervalo (minutos)', 'rinac'); ?></label>
                            <select id="bulk-intervalo">
                                <option value="30">30 minutos</option>
                                <option value="60" selected>1 hora</option>
                                <option value="90">1.5 horas</option>
                                <option value="120">2 horas</option>
                            </select>
                        </div>
                        <div class="form-group col-4">
                            <label for="bulk-capacidad"><?php _e('Capacidad por horario', 'rinac'); ?></label>
                            <input type="number" id="bulk-capacidad" value="10" min="1">
                        </div>
                        <div class="form-group col-4">
                            <label for="bulk-duracion"><?php _e('Duración (minutos)', 'rinac'); ?></label>
                            <input type="number" id="bulk-duracion" value="60" min="15">
                        </div>
                    </div>
                    
                    <div class="preview-horas">
                        <h4><?php _e('Vista previa:', 'rinac'); ?></h4>
                        <div id="preview-horas-list"></div>
                    </div>
                </div>
            </div>
            
            <div class="rinac-modal-footer">
                <button type="button" class="button" data-action="cancel"><?php _e('Cancelar', 'rinac'); ?></button>
                <button type="button" class="button button-primary" id="confirm-bulk-hours"><?php _e('Añadir Horarios', 'rinac'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
.rinac-admin-form {
    max-width: 1200px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    margin: 20px 0;
}

.form-header {
    padding: 20px 30px;
    border-bottom: 1px solid #e1e1e1;
    background: #f9f9f9;
}

.form-header h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 400;
    color: #23282d;
}

.form-description {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.rinac-form {
    padding: 30px;
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e1e1e1;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
    color: #23282d;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-actions {
    display: flex;
    gap: 10px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    flex: 1;
}

.form-group.col-4 { flex: 0 0 calc(33.333% - 13.333px); }
.form-group.col-6 { flex: 0 0 calc(50% - 10px); }

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #23282d;
}

.required {
    color: #d63638;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #007cba;
    box-shadow: 0 0 0 1px #007cba;
    outline: none;
}

.form-help {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #646970;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}

/* Sección de horas */
.horas-container {
    background: #f9f9f9;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
}

.horas-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    gap: 15px;
    padding: 15px 20px;
    background: #f1f1f1;
    border-bottom: 1px solid #e1e1e1;
    font-weight: 600;
    font-size: 13px;
    color: #23282d;
}

.horas-list {
    min-height: 100px;
}

.hora-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    gap: 15px;
    padding: 15px 20px;
    border-bottom: 1px solid #e1e1e1;
    align-items: center;
    background: #fff;
    transition: background-color 0.2s ease;
}

.hora-item:hover {
    background: #f8f9fa;
}

.hora-item:last-child {
    border-bottom: none;
}

.hora-item .drag-handle {
    cursor: move;
    color: #8c8f94;
    margin-right: 8px;
}

.hora-time {
    display: flex;
    align-items: center;
    font-weight: 500;
}

.hora-actions {
    display: flex;
    gap: 5px;
}

.hora-actions .button {
    padding: 4px 8px;
    font-size: 12px;
    height: auto;
    line-height: 1;
}

.empty-horas {
    padding: 40px 20px;
    text-align: center;
    color: #646970;
}

.empty-horas p {
    margin: 0 0 8px 0;
}

/* Opciones avanzadas */
.advanced-section .section-title {
    cursor: pointer;
    user-select: none;
}

.toggle-advanced {
    display: flex;
    align-items: center;
    gap: 8px;
}

.toggle-advanced .dashicons {
    transition: transform 0.2s ease;
}

.toggle-advanced.expanded .dashicons {
    transform: rotate(90deg);
}

.advanced-options {
    margin-top: 20px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
}

/* Acciones del formulario */
.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 30px;
    border-top: 1px solid #e1e1e1;
    margin-top: 30px;
}

.actions-left,
.actions-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* Modal de horas múltiples */
.bulk-hours-form .form-row {
    margin-bottom: 20px;
}

.preview-horas {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.preview-horas h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #333;
}

#preview-horas-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.preview-hora-item {
    background: #007cba;
    color: white;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
}

/* Estados de validación */
.field-error input,
.field-error select,
.field-error textarea {
    border-color: #d63638;
    box-shadow: 0 0 0 1px #d63638;
}

.field-success input,
.field-success select,
.field-success textarea {
    border-color: #00a32a;
    box-shadow: 0 0 0 1px #00a32a;
}

.field-error-message {
    display: block;
    margin-top: 4px;
    color: #d63638;
    font-size: 12px;
}

/* Responsive */
@media screen and (max-width: 768px) {
    .rinac-form {
        padding: 20px 15px;
    }
    
    .form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .form-group.col-4,
    .form-group.col-6 {
        flex: 1;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .section-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .horas-header,
    .hora-item {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .horas-header {
        display: none;
    }
    
    .hora-item {
        display: block;
        padding: 15px;
    }
    
    .hora-item > div {
        margin-bottom: 8px;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .actions-left,
    .actions-right {
        width: 100%;
        justify-content: center;
    }
}
</style>
