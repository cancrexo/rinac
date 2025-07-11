<!-- 
Formulario principal de reserva para el frontend
Variables disponibles:
- $product: Objeto WooCommerce del producto
- $product_id: ID del producto
- $horarios: Array de horarios disponibles
- $fechas_disponibles: Array de fechas disponibles
- $max_personas: Máximo de personas permitidas
- $require_phone: Si el teléfono es obligatorio
- $strings: Array de cadenas de texto traducidas
-->

<div id="rinac-booking-form" class="rinac-booking-wrapper" data-product-id="<?php echo esc_attr($product_id); ?>">
    <form id="rinac-booking-form-element" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('rinac_add_to_cart', 'rinac_nonce'); ?>
        <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>" />
        
        <!-- Paso 1: Selección de fecha y horario -->
        <div class="booking-step step-1 active" data-step="1">
            <div class="step-header">
                <h3 class="step-title">
                    <span class="step-number">1</span>
                    <?php echo esc_html($strings['select_date']); ?>
                </h3>
                <p class="step-description"><?php _e('Elige cuándo quieres realizar la visita', 'rinac'); ?></p>
            </div>

            <div class="booking-form-content">
                <div class="form-section date-selection">
                    <h4 class="section-title"><?php echo esc_html($strings['date_label']); ?></h4>
                    
                    <!-- Calendario -->
                    <div class="calendar-container">
                        <div id="rinac-datepicker" class="rinac-calendar"></div>
                        <input type="hidden" id="rinac-fecha" name="rinac_fecha" value="" 
                               data-validate="required|future_date|available_date">
                    </div>

                    <!-- Información de la fecha seleccionada -->
                    <div class="selected-date-info" id="selected-date-info" style="display: none;">
                        <div class="date-display">
                            <span class="date-icon">📅</span>
                            <span class="date-text" id="selected-date-text"></span>
                        </div>
                    </div>
                </div>

                <div class="form-section time-selection" id="time-selection" style="display: none;">
                    <h4 class="section-title"><?php echo esc_html($strings['time_label']); ?></h4>
                    
                    <div class="time-slots-container">
                        <div class="loading-time-slots">
                            <div class="spinner"></div>
                            <p><?php _e('Cargando horarios disponibles...', 'rinac'); ?></p>
                        </div>
                        
                        <div class="time-slots-grid" id="time-slots-grid" style="display: none;">
                            <!-- Se llena dinámicamente con JavaScript -->
                        </div>
                    </div>

                    <input type="hidden" id="rinac-horario" name="rinac_horario" value="" data-validate="required">
            </div>
        </div>

        <div class="step-actions">
            <button type="button" class="rinac-btn rinac-btn-primary next-step" data-next="2" disabled>
                <?php _e('Continuar', 'rinac'); ?>
                <span class="btn-arrow">→</span>
            </button>
        </div>
    </div>

    <!-- Paso 2: Número de personas -->
    <div class="booking-step step-2" data-step="2">
        <div class="step-header">
            <h3 class="step-title">
                <span class="step-number">2</span>
                <?php _e('¿Cuántas personas?', 'rinac'); ?>
            </h3>
            <p class="step-description"><?php _e('Indica el número de participantes', 'rinac'); ?></p>
        </div>

        <div class="booking-form-content">
            <div class="form-section personas-selection">
                <div class="personas-input-container">
                    <label for="rinac-personas" class="personas-label">
                        <?php echo esc_html($strings['persons_label']); ?>
                    </label>
                    
                    <div class="personas-counter">
                        <button type="button" class="counter-btn minus" data-action="decrease">-</button>
                        <input type="number" 
                               id="rinac-personas" 
                               name="rinac_personas" 
                               value="1" 
                               min="1" 
                               max="<?php echo esc_attr($max_personas ?? 10); ?>"
                               data-validate="required|min:1|max:<?php echo esc_attr($max_personas ?? 10); ?>|capacity"
                               readonly>
                        <button type="button" class="counter-btn plus" data-action="increase">+</button>
                    </div>
                    
                    <div class="personas-info">
                        <span class="capacity-status" id="capacity-status"></span>
                        <span class="price-per-person"><?php printf(__('Precio por persona: %s', 'rinac'), '<span id="price-per-person">-</span>'); ?></span>
                    </div>
                </div>

                <!-- Información adicional sobre precios -->
                <div class="pricing-info" id="pricing-info">
                    <div class="price-breakdown">
                        <div class="price-item">
                            <span class="label"><?php _e('Subtotal:', 'rinac'); ?></span>
                            <span class="value" id="subtotal-price">-</span>
                        </div>
                        <div class="price-item taxes" id="taxes-info" style="display: none;">
                            <span class="label"><?php _e('Impuestos:', 'rinac'); ?></span>
                            <span class="value" id="taxes-price">-</span>
                        </div>
                        <div class="price-item total">
                            <span class="label"><?php _e('Total:', 'rinac'); ?></span>
                            <span class="value" id="total-price">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="step-actions">
            <button type="button" class="rinac-btn rinac-btn-secondary prev-step" data-prev="1">
                <span class="btn-arrow">←</span>
                <?php _e('Volver', 'rinac'); ?>
            </button>
            <button type="button" class="rinac-btn rinac-btn-primary next-step" data-next="3" disabled>
                <?php _e('Continuar', 'rinac'); ?>
                <span class="btn-arrow">→</span>
            </button>
        </div>
    </div>

    <!-- Paso 3: Datos de contacto -->
    <div class="booking-step step-3" data-step="3">
        <div class="step-header">
            <h3 class="step-title">
                <span class="step-number">3</span>
                <?php _e('Datos de contacto', 'rinac'); ?>
            </h3>
            <p class="step-description"><?php _e('Información necesaria para la reserva', 'rinac'); ?></p>
        </div>

        <div class="booking-form-content">
            <div class="form-section contact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="rinac-nombre"><?php _e('Nombre completo', 'rinac'); ?> <span class="required">*</span></label>
                        <input type="text" 
                               id="rinac-nombre" 
                               name="nombre" 
                               placeholder="<?php _e('Tu nombre completo', 'rinac'); ?>"
                               data-validate="required|minlength:2"
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="rinac-email"><?php _e('Email', 'rinac'); ?> <span class="required">*</span></label>
                        <input type="email" 
                               id="rinac-email" 
                               name="email" 
                               placeholder="<?php _e('tu@email.com', 'rinac'); ?>"
                               data-validate="required|email"
                               required>
                        <small class="form-help"><?php _e('Recibirás la confirmación aquí', 'rinac'); ?></small>
                    </div>
                    <div class="form-group col-6">
                        <label for="rinac-telefono"><?php echo esc_html($strings['phone_label']); ?> 
                        <?php if ($require_phone): ?><span class="required">*</span><?php endif; ?></label>
                        <input type="tel" 
                               id="rinac-telefono" 
                               name="rinac_telefono" 
                               placeholder="<?php _e('+34 600 123 456', 'rinac'); ?>"
                               data-validate="<?php echo $require_phone ? 'required|phone' : 'phone'; ?>"
                               <?php echo $require_phone ? 'required' : ''; ?>>
                        <small class="form-help"><?php _e('Para contactar contigo si es necesario', 'rinac'); ?></small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rinac-comentarios"><?php echo esc_html($strings['comments_label']); ?></label>
                        <textarea id="rinac-comentarios" 
                                  name="rinac_comentarios" 
                                  rows="4" 
                                  placeholder="<?php echo esc_attr($strings['comments_placeholder']); ?>"></textarea>
                        <small class="form-help"><?php _e('Campo opcional', 'rinac'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="step-actions">
            <button type="button" class="rinac-btn rinac-btn-secondary prev-step" data-prev="2">
                <span class="btn-arrow">←</span>
                <?php _e('Volver', 'rinac'); ?>
            </button>
            <button type="button" class="rinac-btn rinac-btn-primary next-step" data-next="4" disabled>
                <?php _e('Continuar', 'rinac'); ?>
                <span class="btn-arrow">→</span>
            </button>
        </div>
    </div>

    <!-- Paso 4: Resumen y confirmación -->
    <div class="booking-step step-4" data-step="4">
        <div class="step-header">
            <h3 class="step-title">
                <span class="step-number">4</span>
                <?php _e('Confirmación', 'rinac'); ?>
            </h3>
            <p class="step-description"><?php _e('Revisa los datos antes de añadir al carrito', 'rinac'); ?></p>
        </div>

        <div class="booking-form-content">
            <div class="booking-summary-card">
                <h4 class="summary-title"><?php _e('Resumen de tu reserva', 'rinac'); ?></h4>
                
                <div class="summary-content">
                    <div class="summary-section">
                        <h5><?php _e('Detalles de la visita', 'rinac'); ?></h5>
                        <div class="summary-item">
                            <span class="label"><?php _e('Producto:', 'rinac'); ?></span>
                            <span class="value"><?php echo esc_html($product->get_name()); ?></span>
                        </div>
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
                    </div>

                    <div class="summary-section">
                        <h5><?php _e('Datos de contacto', 'rinac'); ?></h5>
                        <div class="summary-item">
                            <span class="label"><?php _e('Nombre:', 'rinac'); ?></span>
                            <span class="value" id="summary-nombre">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label"><?php _e('Email:', 'rinac'); ?></span>
                            <span class="value" id="summary-email">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label"><?php _e('Teléfono:', 'rinac'); ?></span>
                            <span class="value" id="summary-telefono">-</span>
                        </div>
                    </div>

                    <div class="summary-section comments" id="summary-comments-section" style="display: none;">
                        <h5><?php _e('Comentarios', 'rinac'); ?></h5>
                        <div class="comments-text" id="summary-comentarios"></div>
                    </div>

                    <div class="summary-section pricing">
                        <div class="price-summary">
                            <div class="price-item">
                                <span class="label"><?php _e('Subtotal:', 'rinac'); ?></span>
                                <span class="value" id="summary-subtotal">-</span>
                            </div>
                            <div class="price-item taxes" id="summary-taxes" style="display: none;">
                                <span class="label"><?php _e('Impuestos:', 'rinac'); ?></span>
                                <span class="value" id="summary-taxes-amount">-</span>
                            </div>
                            <div class="price-item total">
                                <span class="label"><?php _e('Total:', 'rinac'); ?></span>
                                <span class="value total-amount" id="summary-total">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Políticas y términos -->
                <div class="booking-policies">
                    <div class="policy-item">
                        <label class="checkbox-label">
                            <input type="checkbox" id="acepto-politica" name="acepto_politica" required>
                            <span class="checkmark"></span>
                            <?php _e('He leído y acepto la', 'rinac'); ?> 
                            <a href="#" class="policy-link" data-policy="cancellation"><?php _e('política de cancelación', 'rinac'); ?></a>
                        </label>
                    </div>
                    
                    <div class="policy-item">
                        <label class="checkbox-label">
                            <input type="checkbox" id="acepto-terminos" name="acepto_terminos" required>
                            <span class="checkmark"></span>
                            <?php _e('Acepto los', 'rinac'); ?> 
                            <a href="#" class="policy-link" data-policy="terms"><?php _e('términos y condiciones', 'rinac'); ?></a>
                        </label>
                    </div>

                    <div class="policy-item">
                        <label class="checkbox-label">
                            <input type="checkbox" id="acepto-privacidad" name="acepto_privacidad" required>
                            <span class="checkmark"></span>
                            <?php _e('Acepto la', 'rinac'); ?> 
                            <a href="#" class="policy-link" data-policy="privacy"><?php _e('política de privacidad', 'rinac'); ?></a>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="step-actions">
            <button type="button" class="rinac-btn rinac-btn-secondary prev-step" data-prev="3">
                <span class="btn-arrow">←</span>
                <?php _e('Volver', 'rinac'); ?>
            </button>
            <button type="button" class="rinac-btn rinac-btn-success" id="add-to-cart-btn" disabled>
                <span class="btn-icon">🛒</span>
                <?php _e('Añadir al Carrito', 'rinac'); ?>
            </button>
        </div>
    </div>

    <!-- Indicador de pasos -->
    <div class="steps-indicator">
        <div class="step-indicator active" data-step="1">
            <span class="step-number">1</span>
            <span class="step-label"><?php _e('Fecha', 'rinac'); ?></span>
        </div>
        <div class="step-indicator" data-step="2">
            <span class="step-number">2</span>
            <span class="step-label"><?php _e('Personas', 'rinac'); ?></span>
        </div>
        <div class="step-indicator" data-step="3">
            <span class="step-number">3</span>
            <span class="step-label"><?php _e('Contacto', 'rinac'); ?></span>
        </div>
        <div class="step-indicator" data-step="4">
            <span class="step-number">4</span>
            <span class="step-label"><?php _e('Confirmar', 'rinac'); ?></span>
        </div>
    </div>

    <!-- Mensaje de estado -->
    <div class="booking-status-message" id="booking-status" style="display: none;">
        <div class="status-content">
            <span class="status-icon"></span>
            <span class="status-text"></span>
        </div>
    </div>
</div>

<!-- Script con datos del PHP -->
<script type="text/javascript">
    // Datos pasados desde PHP
    window.rinacBookingData = {
        productId: <?php echo json_encode($product_id); ?>,
        availableDates: <?php echo json_encode($fechas_disponibles); ?>,
        maxPersons: <?php echo json_encode($max_personas); ?>,
        requirePhone: <?php echo json_encode($require_phone); ?>,
        horarios: <?php echo json_encode($horarios); ?>,
        strings: <?php echo json_encode($strings); ?>,
        ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo wp_create_nonce('rinac_nonce'); ?>'
    };
    
    // Inicializar formulario de reserva cuando el DOM esté listo
    jQuery(document).ready(function($) {
        if (typeof RINAC_BookingForm !== 'undefined') {
            new RINAC_BookingForm();
        }
    });
</script>

<style>
.rinac-booking-wrapper {
    max-width: 600px;
    margin: 0 auto;
    position: relative;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.booking-step {
    display: none;
    padding: 30px;
    min-height: 400px;
}

.booking-step.active {
    display: block;
}

.step-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.step-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 600;
    color: #333;
}

.step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #007cba;
    color: white;
    border-radius: 50%;
    font-size: 16px;
    font-weight: 600;
}

.step-description {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.booking-form-content {
    margin-bottom: 30px;
}

.form-section {
    margin-bottom: 25px;
}

.section-title {
    margin: 0 0 15px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

/* Calendario */
.calendar-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.rinac-calendar {
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.selected-date-info {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 15px;
    margin-top: 15px;
}

.date-display {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 500;
    color: #1976d2;
}

/* Horarios */
.time-slots-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.loading-time-slots {
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

.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
}

.time-slot {
    background: white;
    border: 2px solid #e1e5e9;
    border-radius: 6px;
    padding: 12px 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.time-slot:hover:not(.disabled) {
    border-color: #007cba;
    box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
}

.time-slot.selected {
    border-color: #007cba;
    background: #007cba;
    color: white;
}

.time-slot.disabled {
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
    font-size: 11px;
    margin-top: 4px;
    opacity: 0.8;
}

/* Contador de personas */
.personas-input-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 25px;
    text-align: center;
}

.personas-label {
    display: block;
    margin-bottom: 20px;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.personas-counter {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 20px;
}

.counter-btn {
    width: 50px;
    height: 50px;
    border: 2px solid #007cba;
    background: white;
    color: #007cba;
    border-radius: 50%;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.counter-btn:hover:not(:disabled) {
    background: #007cba;
    color: white;
}

.counter-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

#rinac-personas {
    width: 80px;
    height: 50px;
    border: 2px solid #007cba;
    border-radius: 8px;
    text-align: center;
    font-size: 20px;
    font-weight: 600;
    color: #007cba;
    background: white;
}

.personas-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 14px;
}

.capacity-status {
    font-weight: 500;
}

.capacity-available { color: #28a745; }
.capacity-warning { color: #ffc107; }
.capacity-full { color: #dc3545; }

/* Información de precios */
.pricing-info {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.price-breakdown {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price-item.total {
    border-top: 2px solid #eee;
    padding-top: 12px;
    margin-top: 8px;
    font-size: 18px;
    font-weight: 600;
    color: #007cba;
}

/* Formulario de contacto */
.contact-form .form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.contact-form .form-group {
    flex: 1;
}

.contact-form .form-group.col-6 {
    flex: 0 0 calc(50% - 7.5px);
}

.contact-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.contact-form .required {
    color: #dc3545;
}

.contact-form input,
.contact-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.contact-form input:focus,
.contact-form textarea:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
}

.contact-form .form-help {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #666;
}

/* Resumen de reserva */
.booking-summary-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
}

.summary-title {
    margin: 0 0 20px 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
    text-align: center;
}

.summary-section {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #dee2e6;
}

.summary-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
}

.summary-section h5 {
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 600;
    color: #495057;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.summary-item .label {
    font-size: 14px;
    color: #666;
}

.summary-item .value {
    font-size: 14px;
    font-weight: 500;
    color: #333;
    text-align: right;
}

.comments-text {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 12px;
    font-size: 14px;
    color: #495057;
    font-style: italic;
}

.price-summary {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
}

.total-amount {
    font-size: 18px;
    font-weight: 600;
    color: #007cba;
}

/* Políticas */
.booking-policies {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.policy-item {
    margin-bottom: 12px;
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

.policy-link {
    color: #007cba;
    text-decoration: none;
}

.policy-link:hover {
    text-decoration: underline;
}

/* Acciones de paso */
.step-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.rinac-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.rinac-btn-primary {
    background: #007cba;
    color: white;
}

.rinac-btn-primary:hover:not(:disabled) {
    background: #0056b3;
}

.rinac-btn-secondary {
    background: #6c757d;
    color: white;
}

.rinac-btn-secondary:hover {
    background: #5a6268;
}

.rinac-btn-success {
    background: #28a745;
    color: white;
}

.rinac-btn-success:hover:not(:disabled) {
    background: #218838;
}

.rinac-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Indicador de pasos */
.steps-indicator {
    display: flex;
    justify-content: center;
    padding: 20px;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.step-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 0 20px;
    position: relative;
}

.step-indicator:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 18px;
    left: 100%;
    width: 40px;
    height: 2px;
    background: #dee2e6;
    z-index: 1;
}

.step-indicator.active .step-number {
    background: #007cba;
    color: white;
}

.step-indicator.completed .step-number {
    background: #28a745;
    color: white;
}

.step-indicator.active:not(:last-child)::after,
.step-indicator.completed:not(:last-child)::after {
    background: #007cba;
}

.step-indicator .step-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #dee2e6;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    position: relative;
    z-index: 2;
}

.step-indicator .step-label {
    font-size: 12px;
    font-weight: 500;
    color: #6c757d;
    text-align: center;
}

.step-indicator.active .step-label,
.step-indicator.completed .step-label {
    color: #333;
}

/* Mensaje de estado */
.booking-status-message {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 15px 20px;
    z-index: 1000;
    max-width: 350px;
}

.status-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.booking-status-message.success {
    border-left: 4px solid #28a745;
}

.booking-status-message.error {
    border-left: 4px solid #dc3545;
}

.booking-status-message.warning {
    border-left: 4px solid #ffc107;
}

.booking-status-message.info {
    border-left: 4px solid #17a2b8;
}

/* Responsive */
@media (max-width: 768px) {
    .rinac-booking-wrapper {
        margin: 0;
        border-radius: 0;
        box-shadow: none;
    }
    
    .booking-step {
        padding: 20px 15px;
    }
    
    .step-title {
        font-size: 20px;
    }
    
    .contact-form .form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .contact-form .form-group.col-6 {
        flex: 1;
    }
    
    .time-slots-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 8px;
    }
    
    .personas-counter {
        gap: 15px;
    }
    
    .counter-btn {
        width: 45px;
        height: 45px;
        font-size: 20px;
    }
    
    #rinac-personas {
        width: 70px;
        height: 45px;
        font-size: 18px;
    }
    
    .step-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .rinac-btn {
        width: 100%;
        justify-content: center;
    }
    
    .steps-indicator {
        padding: 15px 10px;
    }
    
    .step-indicator {
        padding: 0 10px;
    }
    
    .step-indicator:not(:last-child)::after {
        width: 20px;
    }
    
    .step-indicator .step-label {
        font-size: 10px;
    }
    
    .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .summary-item .value {
        text-align: left;
    }
}
</style>
