<!-- 
Modal para mostrar detalles de una reserva existente
Variables: $reserva
-->

<div id="reserva-details-modal" class="rinac-modal large">
    <div class="rinac-modal-overlay"></div>
    <div class="rinac-modal-container">
        <div class="rinac-modal-content">
            <!-- Header -->
            <div class="rinac-modal-header">
                <h3 class="rinac-modal-title">
                    <span class="modal-icon">📋</span>
                    <?php printf(__('Reserva #%s', 'rinac'), '<span id="reserva-id"></span>'); ?>
                </h3>
                <button type="button" class="rinac-modal-close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="rinac-modal-body">
                <div class="reserva-details-content">
                    <!-- Estado de la reserva -->
                    <div class="reserva-status-section">
                        <div class="status-badge" id="status-badge">
                            <span class="status-icon"></span>
                            <span class="status-text" id="status-text"></span>
                        </div>
                        <div class="status-date" id="status-date"></div>
                    </div>

                    <!-- Información principal -->
                    <div class="details-grid">
                        <div class="details-section">
                            <h4><?php _e('Información de la Reserva', 'rinac'); ?></h4>
                            <div class="detail-item">
                                <span class="label"><?php _e('Producto:', 'rinac'); ?></span>
                                <span class="value" id="detail-producto"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php _e('Fecha:', 'rinac'); ?></span>
                                <span class="value" id="detail-fecha"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php _e('Horario:', 'rinac'); ?></span>
                                <span class="value" id="detail-horario"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php _e('Duración:', 'rinac'); ?></span>
                                <span class="value" id="detail-duracion"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php _e('Personas:', 'rinac'); ?></span>
                                <span class="value" id="detail-personas"></span>
                            </div>
                            <div class="detail-item price-item">
                                <span class="label"><?php _e('Precio total:', 'rinac'); ?></span>
                                <span class="value price" id="detail-precio"></span>
                            </div>
                        </div>

                        <div class="details-section">
                            <h4><?php _e('Información del Cliente', 'rinac'); ?></h4>
                            <div class="detail-item">
                                <span class="label"><?php _e('Nombre:', 'rinac'); ?></span>
                                <span class="value" id="detail-cliente-nombre"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php _e('Email:', 'rinac'); ?></span>
                                <span class="value" id="detail-cliente-email"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php _e('Teléfono:', 'rinac'); ?></span>
                                <span class="value" id="detail-cliente-telefono"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php _e('Fecha de reserva:', 'rinac'); ?></span>
                                <span class="value" id="detail-fecha-creacion"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Comentarios -->
                    <div class="comments-section" id="comments-section" style="display: none;">
                        <h4><?php _e('Comentarios del Cliente', 'rinac'); ?></h4>
                        <div class="comments-content" id="detail-comentarios"></div>
                    </div>

                    <!-- Historial de estados -->
                    <div class="history-section">
                        <h4><?php _e('Historial de Estados', 'rinac'); ?></h4>
                        <div class="timeline" id="status-timeline">
                            <!-- Se llena dinámicamente -->
                        </div>
                    </div>

                    <!-- Notas internas -->
                    <div class="notes-section">
                        <h4><?php _e('Notas Internas', 'rinac'); ?></h4>
                        <div class="existing-notes" id="existing-notes">
                            <!-- Se llenan dinámicamente -->
                        </div>
                        <div class="add-note">
                            <textarea id="new-note" placeholder="<?php _e('Añadir nota interna...', 'rinac'); ?>" rows="3"></textarea>
                            <button type="button" class="rinac-btn rinac-btn-secondary btn-sm" id="add-note-btn">
                                <?php _e('Añadir Nota', 'rinac'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="rinac-modal-footer">
                <div class="actions-left">
                    <button type="button" class="rinac-btn rinac-btn-outline" id="print-reserva">
                        <span class="btn-icon">🖨️</span>
                        <?php _e('Imprimir', 'rinac'); ?>
                    </button>
                    <button type="button" class="rinac-btn rinac-btn-outline" id="send-email-cliente">
                        <span class="btn-icon">📧</span>
                        <?php _e('Enviar Email', 'rinac'); ?>
                    </button>
                </div>
                
                <div class="actions-right">
                    <button type="button" class="rinac-btn rinac-btn-secondary" data-action="close">
                        <?php _e('Cerrar', 'rinac'); ?>
                    </button>
                    <div class="dropdown-container">
                        <button type="button" class="rinac-btn rinac-btn-primary dropdown-toggle" id="status-actions">
                            <?php _e('Cambiar Estado', 'rinac'); ?>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="dropdown-menu" id="status-dropdown">
                            <button type="button" class="dropdown-item" data-status="confirmada">
                                <span class="status-icon status-confirmada">✓</span>
                                <?php _e('Confirmar', 'rinac'); ?>
                            </button>
                            <button type="button" class="dropdown-item" data-status="completada">
                                <span class="status-icon status-completada">✓</span>
                                <?php _e('Marcar Completada', 'rinac'); ?>
                            </button>
                            <button type="button" class="dropdown-item" data-status="cancelada">
                                <span class="status-icon status-cancelada">✗</span>
                                <?php _e('Cancelar', 'rinac'); ?>
                            </button>
                            <button type="button" class="dropdown-item" data-status="no-show">
                                <span class="status-icon status-no-show">⊘</span>
                                <?php _e('No Show', 'rinac'); ?>
                            </button>
                            <div class="dropdown-divider"></div>
                            <button type="button" class="dropdown-item danger" id="delete-reserva">
                                <span class="status-icon">🗑️</span>
                                <?php _e('Eliminar', 'rinac'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.reserva-details-content {
    max-width: 800px;
    margin: 0 auto;
}

.reserva-status-section {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 8px;
}

.status-badge.pendiente {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-badge.confirmada {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-badge.completada {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.status-badge.cancelada {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-badge.no-show {
    background: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

.status-date {
    font-size: 12px;
    color: #666;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.details-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item .label {
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.detail-item .value {
    font-size: 13px;
    color: #333;
    text-align: right;
}

.detail-item.price-item {
    border-top: 2px solid #eee;
    margin-top: 8px;
    padding-top: 12px;
}

.detail-item.price-item .value {
    font-size: 16px;
    font-weight: 600;
    color: #007cba;
}

.comments-section,
.history-section,
.notes-section {
    margin-bottom: 25px;
}

.comments-section h4,
.history-section h4,
.notes-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
}

.comments-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    font-style: italic;
    color: #666;
    line-height: 1.5;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 12px 15px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 15px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #007cba;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.timeline-status {
    font-weight: 500;
    font-size: 14px;
}

.timeline-date {
    font-size: 12px;
    color: #666;
}

.timeline-user {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

.existing-notes {
    margin-bottom: 15px;
}

.note-item {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 12px 15px;
    margin-bottom: 10px;
}

.note-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 12px;
    color: #666;
}

.note-content {
    font-size: 14px;
    color: #333;
    line-height: 1.4;
}

.add-note {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.add-note textarea {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
    font-size: 13px;
}

.rinac-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.actions-left,
.actions-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.rinac-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.rinac-btn.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.rinac-btn-outline {
    background: #fff;
    color: #666;
    border: 1px solid #ddd;
}

.rinac-btn-outline:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.dropdown-container {
    position: relative;
}

.dropdown-toggle {
    background: #007cba;
    color: white;
}

.dropdown-toggle:hover {
    background: #0056b3;
}

.dropdown-arrow {
    margin-left: 4px;
    font-size: 10px;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 180px;
    z-index: 1000;
    display: none;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 12px;
    border: none;
    background: none;
    text-align: left;
    font-size: 13px;
    color: #333;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.dropdown-item:hover {
    background: #f8f9fa;
}

.dropdown-item.danger {
    color: #dc3545;
}

.dropdown-item.danger:hover {
    background: #f8f9fa;
}

.dropdown-divider {
    height: 1px;
    background: #e9ecef;
    margin: 4px 0;
}

.status-icon {
    font-size: 12px;
}

/* Responsive */
@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .rinac-modal-footer {
        flex-direction: column;
        gap: 15px;
    }
    
    .actions-left,
    .actions-right {
        width: 100%;
        justify-content: center;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .detail-item .value {
        text-align: left;
    }
    
    .timeline {
        padding-left: 20px;
    }
    
    .timeline::before {
        left: 8px;
    }
    
    .timeline-item::before {
        left: -18px;
    }
    
    .add-note {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
