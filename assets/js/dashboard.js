/**
 * JavaScript para el Dashboard de RINAC
 * Funcionalidades avanzadas de estadísticas y análisis
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var dashboardData = {};
    var charts = {};
    var currentDateRange = 'month';
    
    // Inicializar dashboard si estamos en la página correcta
    if ($('#rinac-dashboard').length) {
        initDashboard();
    }
    
    /**
     * Inicializar dashboard
     */
    function initDashboard() {
        loadDashboardData();
        initCharts();
        initFilters();
        initRealTimeUpdates();
        initExportFunctions();
    }
    
    /**
     * Cargar datos del dashboard
     */
    function loadDashboardData() {
        showLoading();
        
        $.ajax({
            url: rinac_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_get_dashboard_data',
                nonce: rinac_admin.nonce,
                date_range: currentDateRange
            },
            success: function(response) {
                if (response.success) {
                    dashboardData = response.data;
                    updateDashboardMetrics();
                    updateCharts();
                } else {
                    showError('Error al cargar datos del dashboard');
                }
            },
            error: function() {
                showError('Error de conexión');
            },
            complete: function() {
                hideLoading();
            }
        });
    }
    
    /**
     * Actualizar métricas del dashboard
     */
    function updateDashboardMetrics() {
        if (!dashboardData.metrics) return;
        
        const metrics = dashboardData.metrics;
        
        // Reservas totales
        $('.metric-reservas-total .metric-value').text(metrics.total_reservas || 0);
        $('.metric-reservas-total .metric-change').text(
            (metrics.reservas_change > 0 ? '+' : '') + metrics.reservas_change + '%'
        ).toggleClass('positive', metrics.reservas_change > 0)
         .toggleClass('negative', metrics.reservas_change < 0);
        
        // Ingresos
        $('.metric-ingresos .metric-value').text(formatCurrency(metrics.total_ingresos || 0));
        $('.metric-ingresos .metric-change').text(
            (metrics.ingresos_change > 0 ? '+' : '') + metrics.ingresos_change + '%'
        ).toggleClass('positive', metrics.ingresos_change > 0)
         .toggleClass('negative', metrics.ingresos_change < 0);
        
        // Ocupación promedio
        $('.metric-ocupacion .metric-value').text((metrics.ocupacion_promedio || 0).toFixed(1) + '%');
        $('.metric-ocupacion .metric-change').text(
            (metrics.ocupacion_change > 0 ? '+' : '') + metrics.ocupacion_change + '%'
        ).toggleClass('positive', metrics.ocupacion_change > 0)
         .toggleClass('negative', metrics.ocupacion_change < 0);
        
        // Productos más populares
        $('.metric-productos .metric-value').text(metrics.productos_activos || 0);
    }
    
    /**
     * Inicializar gráficos
     */
    function initCharts() {
        // Gráfico de reservas por día
        initReservasChart();
        
        // Gráfico de ocupación por horario
        initOcupacionChart();
        
        // Gráfico de ingresos
        initIngresosChart();
        
        // Gráfico de productos más vendidos
        initProductosChart();
    }
    
    /**
     * Gráfico de reservas por día
     */
    function initReservasChart() {
        const ctx = document.getElementById('reservas-chart');
        if (!ctx) return;
        
        charts.reservas = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Reservas',
                    data: [],
                    borderColor: '#007cba',
                    backgroundColor: 'rgba(0, 124, 186, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
    
    /**
     * Gráfico de ocupación por horario
     */
    function initOcupacionChart() {
        const ctx = document.getElementById('ocupacion-chart');
        if (!ctx) return;
        
        charts.ocupacion = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ocupación (%)',
                    data: [],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    /**
     * Gráfico de ingresos
     */
    function initIngresosChart() {
        const ctx = document.getElementById('ingresos-chart');
        if (!ctx) return;
        
        charts.ingresos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ingresos',
                    data: [],
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: '#ffc107',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return formatCurrency(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Gráfico de productos más vendidos
     */
    function initProductosChart() {
        const ctx = document.getElementById('productos-chart');
        if (!ctx) return;
        
        charts.productos = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#007cba',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6c757d',
                        '#17a2b8',
                        '#6f42c1',
                        '#e83e8c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    /**
     * Actualizar todos los gráficos
     */
    function updateCharts() {
        if (!dashboardData.charts) return;
        
        const chartsData = dashboardData.charts;
        
        // Actualizar gráfico de reservas
        if (charts.reservas && chartsData.reservas) {
            charts.reservas.data.labels = chartsData.reservas.labels;
            charts.reservas.data.datasets[0].data = chartsData.reservas.data;
            charts.reservas.update();
        }
        
        // Actualizar gráfico de ocupación
        if (charts.ocupacion && chartsData.ocupacion) {
            charts.ocupacion.data.labels = chartsData.ocupacion.labels;
            charts.ocupacion.data.datasets[0].data = chartsData.ocupacion.data;
            charts.ocupacion.update();
        }
        
        // Actualizar gráfico de ingresos
        if (charts.ingresos && chartsData.ingresos) {
            charts.ingresos.data.labels = chartsData.ingresos.labels;
            charts.ingresos.data.datasets[0].data = chartsData.ingresos.data;
            charts.ingresos.update();
        }
        
        // Actualizar gráfico de productos
        if (charts.productos && chartsData.productos) {
            charts.productos.data.labels = chartsData.productos.labels;
            charts.productos.data.datasets[0].data = chartsData.productos.data;
            charts.productos.update();
        }
    }
    
    /**
     * Inicializar filtros
     */
    function initFilters() {
        // Filtro de rango de fechas
        $('.date-range-filter').on('change', function() {
            currentDateRange = $(this).val();
            loadDashboardData();
        });
        
        // Filtro de productos
        $('.product-filter').on('change', function() {
            updateFilters();
        });
        
        // Aplicar filtros personalizados
        $('#apply-custom-filters').on('click', function() {
            applyCustomFilters();
        });
        
        // Reset filtros
        $('#reset-filters').on('click', function() {
            resetFilters();
        });
    }
    
    /**
     * Aplicar filtros personalizados
     */
    function applyCustomFilters() {
        const filters = {
            fecha_inicio: $('#fecha-inicio').val(),
            fecha_fin: $('#fecha-fin').val(),
            productos: $('#productos-filter').val(),
            estado: $('#estado-filter').val()
        };
        
        showLoading();
        
        $.ajax({
            url: rinac_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_apply_dashboard_filters',
                nonce: rinac_admin.nonce,
                filters: filters
            },
            success: function(response) {
                if (response.success) {
                    dashboardData = response.data;
                    updateDashboardMetrics();
                    updateCharts();
                } else {
                    showError('Error al aplicar filtros');
                }
            },
            complete: function() {
                hideLoading();
            }
        });
    }
    
    /**
     * Reset filtros
     */
    function resetFilters() {
        $('.date-range-filter').val('month');
        $('#productos-filter').val('');
        $('#estado-filter').val('');
        $('#fecha-inicio').val('');
        $('#fecha-fin').val('');
        
        currentDateRange = 'month';
        loadDashboardData();
    }
    
    /**
     * Actualizaciones en tiempo real
     */
    function initRealTimeUpdates() {
        // Actualizar cada 5 minutos
        setInterval(function() {
            loadDashboardData();
        }, 300000);
        
        // Mostrar última actualización
        updateLastRefreshTime();
        setInterval(updateLastRefreshTime, 60000);
    }
    
    /**
     * Actualizar tiempo de última actualización
     */
    function updateLastRefreshTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        $('.last-update-time').text('Última actualización: ' + timeString);
    }
    
    /**
     * Funciones de exportación
     */
    function initExportFunctions() {
        // Exportar datos del dashboard
        $('#export-dashboard-data').on('click', function() {
            exportDashboardData();
        });
        
        // Exportar gráficos como imágenes
        $('#export-charts').on('click', function() {
            exportCharts();
        });
        
        // Generar reporte PDF
        $('#generate-pdf-report').on('click', function() {
            generatePDFReport();
        });
    }
    
    /**
     * Exportar datos del dashboard
     */
    function exportDashboardData() {
        const data = {
            action: 'rinac_export_dashboard_data',
            nonce: rinac_admin.nonce,
            date_range: currentDateRange,
            format: 'excel'
        };
        
        // Crear formulario oculto para descargar
        const form = $('<form>', {
            method: 'POST',
            action: rinac_admin.ajax_url
        });
        
        $.each(data, function(key, value) {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: value
            }));
        });
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        showSuccess('Exportando datos...');
    }
    
    /**
     * Exportar gráficos como imágenes
     */
    function exportCharts() {
        Object.keys(charts).forEach(function(chartKey) {
            const chart = charts[chartKey];
            const link = document.createElement('a');
            link.download = 'rinac-' + chartKey + '-chart.png';
            link.href = chart.toBase64Image();
            link.click();
        });
        
        showSuccess('Gráficos exportados');
    }
    
    /**
     * Generar reporte PDF
     */
    function generatePDFReport() {
        showLoading();
        
        $.ajax({
            url: rinac_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_generate_pdf_report',
                nonce: rinac_admin.nonce,
                date_range: currentDateRange
            },
            success: function(response) {
                if (response.success) {
                    // Abrir PDF en nueva ventana
                    window.open(response.data.pdf_url, '_blank');
                    showSuccess('Reporte PDF generado');
                } else {
                    showError('Error al generar el reporte PDF');
                }
            },
            complete: function() {
                hideLoading();
            }
        });
    }
    
    /**
     * Funciones de utilidad
     */
    function formatCurrency(amount) {
        return new Intl.NumberFormat('es-ES', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }
    
    function showLoading() {
        $('.rinac-loading').show();
    }
    
    function hideLoading() {
        $('.rinac-loading').hide();
    }
    
    function showSuccess(message) {
        showNotification(message, 'success');
    }
    
    function showError(message) {
        showNotification(message, 'error');
    }
    
    function showNotification(message, type) {
        const notification = $('<div>', {
            class: 'rinac-notification rinac-notification-' + type,
            text: message
        });
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 4000);
    }
});
