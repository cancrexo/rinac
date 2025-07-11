<!-- 
Dashboard principal de RINAC
Variables: $metrics, $charts_data, $recent_bookings
-->

<div class="rinac-dashboard">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1><?php _e('Dashboard RINAC', 'rinac'); ?></h1>
            <p class="dashboard-subtitle"><?php _e('Resumen de reservas y estadísticas', 'rinac'); ?></p>
        </div>
        
        <div class="header-actions">
            <div class="date-range-selector">
                <select id="dashboard-date-range" class="date-range-filter">
                    <option value="today"><?php _e('Hoy', 'rinac'); ?></option>
                    <option value="week"><?php _e('Esta semana', 'rinac'); ?></option>
                    <option value="month" selected><?php _e('Este mes', 'rinac'); ?></option>
                    <option value="quarter"><?php _e('Este trimestre', 'rinac'); ?></option>
                    <option value="year"><?php _e('Este año', 'rinac'); ?></option>
                    <option value="custom"><?php _e('Personalizado', 'rinac'); ?></option>
                </select>
            </div>
            
            <button type="button" class="button button-primary" id="export-dashboard-data">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Exportar', 'rinac'); ?>
            </button>
            
            <button type="button" class="button button-secondary calendar-refresh">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Actualizar', 'rinac'); ?>
            </button>
        </div>
    </div>

    <!-- Métricas principales -->
    <div class="dashboard-metrics">
        <div class="metric-card metric-reservas-total">
            <div class="metric-header">
                <div class="metric-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="metric-info">
                    <h3><?php _e('Reservas Totales', 'rinac'); ?></h3>
                    <div class="metric-value"><?php echo esc_html($metrics['total_reservas'] ?? 0); ?></div>
                </div>
            </div>
            <div class="metric-footer">
                <span class="metric-change" data-change="<?php echo esc_attr($metrics['reservas_change'] ?? 0); ?>">
                    <?php 
                    $change = $metrics['reservas_change'] ?? 0;
                    echo $change > 0 ? '+' : '';
                    echo esc_html($change);
                    ?>% <?php _e('vs período anterior', 'rinac'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card metric-ingresos">
            <div class="metric-header">
                <div class="metric-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="metric-info">
                    <h3><?php _e('Ingresos', 'rinac'); ?></h3>
                    <div class="metric-value"><?php echo wc_price($metrics['total_ingresos'] ?? 0); ?></div>
                </div>
            </div>
            <div class="metric-footer">
                <span class="metric-change" data-change="<?php echo esc_attr($metrics['ingresos_change'] ?? 0); ?>">
                    <?php 
                    $change = $metrics['ingresos_change'] ?? 0;
                    echo $change > 0 ? '+' : '';
                    echo esc_html($change);
                    ?>% <?php _e('vs período anterior', 'rinac'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card metric-ocupacion">
            <div class="metric-header">
                <div class="metric-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="metric-info">
                    <h3><?php _e('Ocupación Media', 'rinac'); ?></h3>
                    <div class="metric-value"><?php echo esc_html(number_format($metrics['ocupacion_promedio'] ?? 0, 1)); ?>%</div>
                </div>
            </div>
            <div class="metric-footer">
                <span class="metric-change" data-change="<?php echo esc_attr($metrics['ocupacion_change'] ?? 0); ?>">
                    <?php 
                    $change = $metrics['ocupacion_change'] ?? 0;
                    echo $change > 0 ? '+' : '';
                    echo esc_html($change);
                    ?>% <?php _e('vs período anterior', 'rinac'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card metric-productos">
            <div class="metric-header">
                <div class="metric-icon">
                    <span class="dashicons dashicons-products"></span>
                </div>
                <div class="metric-info">
                    <h3><?php _e('Productos Activos', 'rinac'); ?></h3>
                    <div class="metric-value"><?php echo esc_html($metrics['productos_activos'] ?? 0); ?></div>
                </div>
            </div>
            <div class="metric-footer">
                <span class="metric-description"><?php _e('Productos tipo VISITAS', 'rinac'); ?></span>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dashboard-charts">
        <div class="chart-row">
            <div class="chart-container">
                <div class="chart-header">
                    <h3><?php _e('Reservas por Día', 'rinac'); ?></h3>
                    <div class="chart-actions">
                        <button type="button" class="button button-small" id="export-reservas-chart">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="reservas-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-header">
                    <h3><?php _e('Ocupación por Horario', 'rinac'); ?></h3>
                    <div class="chart-actions">
                        <button type="button" class="button button-small" id="export-ocupacion-chart">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="ocupacion-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-container">
                <div class="chart-header">
                    <h3><?php _e('Ingresos por Período', 'rinac'); ?></h3>
                    <div class="chart-actions">
                        <button type="button" class="button button-small" id="export-ingresos-chart">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="ingresos-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-header">
                    <h3><?php _e('Productos Más Vendidos', 'rinac'); ?></h3>
                    <div class="chart-actions">
                        <button type="button" class="button button-small" id="export-productos-chart">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="productos-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservas recientes y acciones rápidas -->
    <div class="dashboard-content">
        <div class="content-row">
            <!-- Reservas recientes -->
            <div class="recent-bookings">
                <div class="section-header">
                    <h3><?php _e('Reservas Recientes', 'rinac'); ?></h3>
                    <a href="<?php echo admin_url('admin.php?page=rinac-reservas'); ?>" class="button button-secondary">
                        <?php _e('Ver todas', 'rinac'); ?>
                    </a>
                </div>
                
                <div class="bookings-list">
                    <?php if (!empty($recent_bookings)): ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <div class="booking-item" data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                <div class="booking-info">
                                    <div class="booking-header">
                                        <span class="booking-id">#<?php echo esc_html($booking['id']); ?></span>
                                        <span class="booking-status status-<?php echo esc_attr($booking['estado']); ?>">
                                            <?php echo esc_html($booking['estado']); ?>
                                        </span>
                                    </div>
                                    <div class="booking-details">
                                        <div class="booking-product"><?php echo esc_html($booking['producto_nombre']); ?></div>
                                        <div class="booking-date-time">
                                            <?php echo esc_html(date_i18n('d/m/Y', strtotime($booking['fecha']))); ?> - 
                                            <?php echo esc_html($booking['horario']); ?>
                                        </div>
                                        <div class="booking-customer">
                                            <?php echo esc_html($booking['cliente_nombre']); ?> - 
                                            <?php printf(_n('%d persona', '%d personas', $booking['personas'], 'rinac'), $booking['personas']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="booking-actions">
                                    <button type="button" class="button button-small view-booking" data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <?php if ($booking['estado'] === 'pendiente'): ?>
                                        <button type="button" class="button button-small button-primary confirm-booking" data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                            <span class="dashicons dashicons-yes"></span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-bookings">
                            <p><?php _e('No hay reservas recientes.', 'rinac'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="quick-actions">
                <div class="section-header">
                    <h3><?php _e('Acciones Rápidas', 'rinac'); ?></h3>
                </div>
                
                <div class="actions-grid">
                    <a href="<?php echo admin_url('admin.php?page=rinac-reservas&action=new'); ?>" class="action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-plus-alt2"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Nueva Reserva', 'rinac'); ?></h4>
                            <p><?php _e('Crear reserva manual', 'rinac'); ?></p>
                        </div>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=rinac-rangos-horarios&action=new'); ?>" class="action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Nuevo Rango', 'rinac'); ?></h4>
                            <p><?php _e('Crear rango horario', 'rinac'); ?></p>
                        </div>
                    </a>

                    <a href="<?php echo admin_url('post-new.php?post_type=product&rinac_type=visitas'); ?>" class="action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-products"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Nuevo Producto', 'rinac'); ?></h4>
                            <p><?php _e('Crear producto VISITAS', 'rinac'); ?></p>
                        </div>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=rinac-configuracion'); ?>" class="action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-admin-settings"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Configuración', 'rinac'); ?></h4>
                            <p><?php _e('Ajustar configuración', 'rinac'); ?></p>
                        </div>
                    </a>

                    <button type="button" class="action-card" id="generate-pdf-report">
                        <div class="action-icon">
                            <span class="dashicons dashicons-media-document"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Generar Reporte', 'rinac'); ?></h4>
                            <p><?php _e('Reporte PDF completo', 'rinac'); ?></p>
                        </div>
                    </button>

                    <a href="<?php echo admin_url('admin.php?page=rinac-calendario'); ?>" class="action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Calendario', 'rinac'); ?></h4>
                            <p><?php _e('Vista de calendario', 'rinac'); ?></p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de estado -->
    <div class="dashboard-footer">
        <div class="footer-content">
            <div class="last-update">
                <span class="last-update-time"><?php printf(__('Última actualización: %s', 'rinac'), date_i18n('H:i:s')); ?></span>
            </div>
            <div class="system-status">
                <span class="status-indicator status-ok"></span>
                <span><?php _e('Sistema funcionando correctamente', 'rinac'); ?></span>
            </div>
        </div>
    </div>

    <!-- Loading overlay -->
    <div class="rinac-loading" style="display: none;">
        <div class="loading-content">
            <div class="spinner"></div>
            <p><?php _e('Cargando datos...', 'rinac'); ?></p>
        </div>
    </div>
</div>

<style>
.rinac-dashboard {
    margin: 20px 0;
    position: relative;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid #e1e1e1;
    margin-bottom: 30px;
}

.header-content h1 {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: 400;
    color: #23282d;
}

.dashboard-subtitle {
    margin: 0;
    color: #646970;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.date-range-selector select {
    padding: 6px 8px;
    border: 1px solid #8c8f94;
    border-radius: 3px;
}

/* Métricas */
.dashboard-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.metric-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.metric-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.metric-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.metric-reservas-total .metric-icon {
    background: #e3f2fd;
    color: #1976d2;
}

.metric-ingresos .metric-icon {
    background: #f3e5f5;
    color: #7b1fa2;
}

.metric-ocupacion .metric-icon {
    background: #e8f5e8;
    color: #388e3c;
}

.metric-productos .metric-icon {
    background: #fff3e0;
    color: #f57c00;
}

.metric-info h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #646970;
    font-weight: 400;
}

.metric-value {
    font-size: 32px;
    font-weight: 600;
    color: #23282d;
    line-height: 1;
}

.metric-footer {
    font-size: 12px;
    color: #646970;
}

.metric-change {
    display: flex;
    align-items: center;
    gap: 4px;
}

.metric-change[data-change^="+"],
.metric-change.positive {
    color: #00a32a;
}

.metric-change[data-change^="-"],
.metric-change.negative {
    color: #d63638;
}

/* Gráficos */
.dashboard-charts {
    margin-bottom: 40px;
}

.chart-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.chart-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e1e1e1;
}

.chart-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #23282d;
}

.chart-actions {
    display: flex;
    gap: 5px;
}

.chart-body {
    padding: 20px;
    position: relative;
    height: 300px;
}

/* Contenido del dashboard */
.dashboard-content {
    margin-bottom: 40px;
}

.content-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

/* Reservas recientes */
.recent-bookings,
.quick-actions {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e1e1e1;
}

.section-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #23282d;
}

.bookings-list {
    max-height: 400px;
    overflow-y: auto;
}

.booking-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #f1f1f1;
}

.booking-item:last-child {
    border-bottom: none;
}

.booking-item:hover {
    background: #f8f9fa;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.booking-id {
    font-weight: 600;
    color: #23282d;
}

.booking-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pendiente {
    background: #fff3cd;
    color: #856404;
}

.status-confirmada {
    background: #d4edda;
    color: #155724;
}

.status-completada {
    background: #d1ecf1;
    color: #0c5460;
}

.status-cancelada {
    background: #f8d7da;
    color: #721c24;
}

.booking-details {
    font-size: 13px;
    color: #646970;
}

.booking-product {
    font-weight: 500;
    color: #23282d;
    margin-bottom: 4px;
}

.booking-actions {
    display: flex;
    gap: 5px;
}

.no-bookings {
    padding: 40px 20px;
    text-align: center;
    color: #646970;
}

/* Acciones rápidas */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 20px;
}

.action-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    background: #fff;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    cursor: pointer;
}

.action-card:hover {
    border-color: #007cba;
    box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
    text-decoration: none;
    color: inherit;
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f0f6fc;
    color: #007cba;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.action-content h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    color: #23282d;
}

.action-content p {
    margin: 0;
    font-size: 12px;
    color: #646970;
}

/* Footer */
.dashboard-footer {
    padding: 20px 0;
    border-top: 1px solid #e1e1e1;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #646970;
}

.system-status {
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-ok {
    background: #00a32a;
}

.status-warning {
    background: #dba617;
}

.status-error {
    background: #d63638;
}

/* Loading */
.rinac-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.loading-content {
    text-align: center;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007cba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media screen and (max-width: 1200px) {
    .content-row {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
}

@media screen and (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .header-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .dashboard-metrics {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .metric-header {
        gap: 10px;
    }
    
    .metric-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }
    
    .metric-value {
        font-size: 24px;
    }
    
    .chart-row {
        gap: 15px;
    }
    
    .chart-body {
        height: 250px;
        padding: 15px;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 15px;
    }
    
    .booking-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .booking-actions {
        align-self: stretch;
        justify-content: flex-end;
    }
    
    .footer-content {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>
