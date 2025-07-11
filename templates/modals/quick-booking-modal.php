<!-- 
Modal para reserva rápida desde el calendario
Variables: $product_id, $fecha, $horarios_disponibles
-->

<div id="quick-booking-modal" class="rinac-modal medium">
    <div class="rinac-modal-overlay"></div>
    <div class="rinac-modal-container">
        <div class="rinac-modal-content">
            <!-- Header -->
            <div class="rinac-modal-header">
                <h3 class="rinac-modal-title">
                    <span class="modal-icon">📅</span>
                    <?php _e('Reserva Rápida', 'rinac'); ?>
                </h3>
                <button type="button" class="rinac-modal-close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="rinac-modal-body">
                <div class="quick-booking-content">
                    <!-- Información de la fecha -->
                    <div class="selected-date-info">
                        <h4><?php _e('Fecha seleccionada:', 'rinac'); ?></h4>
                        <p class="date-display" id="selected-date-display"></p>
                    </div>

                    <!-- Horarios disponibles -->
                    <div class="available-slots-section">
                        <h4><?php _e('Horarios disponibles:', 'rinac'); ?></h4>
                        <div id="available-slots-container" class="slots-grid">
                            <div class="loading-slots">
                                <div class="spinner"></div>
                                <p><?php _e('Cargando horarios...', 'rinac'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de reserva -->
                    <div id="booking-form-section" class="booking-form-section" style="display: none;">
                        <h4><?php _e('Datos de la reserva:', 'rinac'); ?></h4>
                        
                        <form id="quick-booking-form" class="rinac-form">
                            <input type="hidden" id="qb-product-id" value="<?php echo esc_attr($product_id ?? ''); ?>">
                            <input type="hidden" id="qb-fecha" value="">
                            <input type="hidden" id="qb-horario" value="">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="qb-personas"><?php _e('Número de personas', 'rinac'); ?> <span class="required">*</span></label>
                                    <select id="qb-personas" name="personas" required data-validate="required|min:1">
                                        <option value=""><?php _e('Seleccionar...', 'rinac'); ?></option>
                                        <?php for ($i = 1; $i <= 20; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo ($i == 1) ? __('persona', 'rinac') : __('personas', 'rinac'); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="capacity-info"></div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="qb-nombre"><?php _e('Nombre completo', 'rinac'); ?> <span class="required">*</span></label>
                                    <input type="text" id="qb-nombre" name="nombre" required data-validate="required|minlength:2">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label for="qb-telefono"><?php _e('Teléfono', 'rinac'); ?> <span class="required">*</span></label>
                                    <input type="tel" id="qb-telefono" name="telefono" required data-validate="required|phone">
                                    <small class="form-help"><?php _e('Formato: +34 600 123 456', 'rinac'); ?></small>
                                </div>
                                <div class="form-group col-6">
                                    <label for="qb-email"><?php _e('Email', 'rinac'); ?> <span class="required">*</span></label>
                                    <input type="email" id="qb-email" name="email" required data-validate="required|email">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="qb-comentarios"><?php _e('Comentarios adicionales', 'rinac'); ?></label>
                                    <textarea id="qb-comentarios" name="comentarios" rows="3" placeholder="<?php _e('Información adicional, necesidades especiales, etc...', 'rinac'); ?>"></textarea>
                                </div>
                            </div>

                            <!-- Resumen de la reserva -->
                            <div class="booking-summary">
                                <h5><?php _e('Resumen de la reserva:', 'rinac'); ?></h5>
                                <div class="summary-content">
                                    <div class="summary-item">
                                        <span class="label"><?php _e('Fecha:', 'rinac'); ?></span>
                                        <span class="value" id="summary-fecha">-</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="label"><?php _e('Horario:', 'rinac'); ?></span>
                                        <span class="value" id="summary-horario">-</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="label"><?php _e('Personas:', 'rinac'); ?></span>
                                        <span class="value" id="summary-personas">-</span>
                                    </div>
                                    <div class="summary-item price-item">
                                        <span class="label"><?php _e('Precio total:', 'rinac'); ?></span>
                                        <span class="value price" id="summary-precio">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Términos y condiciones -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="qb-acepto-terminos" name="acepto_terminos" required>
                                        <span class="checkmark"></span>
                                        <?php _e('Acepto los', 'rinac'); ?> 
                                        <a href="#" class="terms-link"><?php _e('términos y condiciones', 'rinac'); ?></a>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="rinac-modal-footer">
                <button type="button" class="rinac-btn rinac-btn-secondary" data-action="cancel">
                    <?php _e('Cancelar', 'rinac'); ?>
                </button>
                <button type="button" class="rinac-btn rinac-btn-primary" id="confirm-quick-booking" disabled>
                    <span class="btn-icon">✓</span>
                    <?php _e('Confirmar Reserva', 'rinac'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.quick-booking-content .selected-date-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 25px;
    border-left: 4px solid #007cba;
}

.selected-date-info h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 14px;
    font-weight: 600;
}

.date-display {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
    color: #007cba;
}

.available-slots-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px;
    margin-bottom: 25px;
}

.slot-button {
    background: #fff;
    border: 2px solid #e1e5e9;
    border-radius: 6px;
    padding: 12px 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.slot-button:hover:not(:disabled) {
    border-color: #007cba;
    box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
}

.slot-button.selected {
    border-color: #007cba;
    background: #007cba;
    color: white;
}

.slot-button:disabled {
    background: #f5f5f5;
    color: #999;
    cursor: not-allowed;
    opacity: 0.6;
}

.slot-time {
    display: block;
    font-weight: 600;
    font-size: 14px;
}

.slot-capacity {
    display: block;
    font-size: 12px;
    margin-top: 4px;
    opacity: 0.8;
}

.loading-slots {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px 20px;
}

.spinner {
    width: 30px;
    height: 30px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007cba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.booking-form-section {
    border-top: 1px solid #eee;
    padding-top: 25px;
}

.booking-form-section h4 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.rinac-form .form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.rinac-form .form-group {
    flex: 1;
}

.rinac-form .form-group.col-6 {
    flex: 0 0 calc(50% - 7.5px);
}

.rinac-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.rinac-form .required {
    color: #dc3545;
}

.rinac-form input,
.rinac-form select,
.rinac-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.rinac-form input:focus,
.rinac-form select:focus,
.rinac-form textarea:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
}

.rinac-form .form-help {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #666;
}

.capacity-info {
    margin-top: 5px;
    font-size: 12px;
}

.capacity-available {
    color: #28a745;
}

.capacity-warning {
    color: #ffc107;
}

.capacity-full {
    color: #dc3545;
}

.booking-summary {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
    margin: 20px 0;
}

.booking-summary h5 {
    margin: 0 0 12px 0;
    color: #333;
    font-size: 14px;
    font-weight: 600;
}

.summary-content {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-item .label {
    font-size: 13px;
    color: #666;
}

.summary-item .value {
    font-size: 13px;
    font-weight: 500;
    color: #333;
}

.summary-item.price-item {
    border-top: 1px solid #dee2e6;
    padding-top: 8px;
    margin-top: 8px;
}

.summary-item.price-item .value {
    font-size: 16px;
    font-weight: 600;
    color: #007cba;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    font-size: 13px;
    line-height: 1.4;
}

.checkbox-label input[type="checkbox"] {
    width: auto !important;
    margin-right: 8px;
    margin-top: 2px;
}

.terms-link {
    color: #007cba;
    text-decoration: none;
}

.terms-link:hover {
    text-decoration: underline;
}

.rinac-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.rinac-btn-secondary {
    background: #6c757d;
    color: white;
}

.rinac-btn-secondary:hover {
    background: #5a6268;
}

.rinac-btn-primary {
    background: #007cba;
    color: white;
}

.rinac-btn-primary:hover:not(:disabled) {
    background: #0056b3;
}

.rinac-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
    .rinac-form .form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .rinac-form .form-group.col-6 {
        flex: 1;
    }
    
    .slots-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }
    
    .slot-button {
        padding: 10px 6px;
    }
    
    .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }
}
</style>
