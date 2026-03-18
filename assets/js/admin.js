/**
 * JavaScript para la administración de RINAC
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Inicializar funcionalidades según la página
    if (typeof rinac_admin !== 'undefined') {
        initRinacAdmin();
    }
    
    /**
     * Inicializar administración de RINAC
     */
    function initRinacAdmin() {
        initRangosHorarios();
        initBulkOperations();
        initDataExport();
        initNotifications();
    }
    
    /**
     * Gestión de rangos horarios
     */
    function initRangosHorarios() {
        var horaCounter = 0;
        var maxPersonasDefault = parseInt(rinac_admin.max_personas_default) || 10;
        
        // Hacer sortable la lista de horas
        if ($('#rinac-horas-list').length) {
            $('#rinac-horas-list').sortable({
                handle: '.rinac-drag-handle',
                placeholder: 'rinac-hora-placeholder',
                start: function(event, ui) {
                    ui.placeholder.height(ui.item.height());
                }
            });
        }
        
        // Añadir nueva hora
        $(document).on('click', '#add-hora', function(e) {
            e.preventDefault();
            addHoraField('', maxPersonasDefault);
        });
        
        // Eliminar hora
        $(document).on('click', '.remove-hora', function(e) {
            e.preventDefault();
            
            if (confirm(rinac_admin.strings.confirm_delete)) {
                $(this).closest('.rinac-hora-field').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
        
        // Editar rango existente
        $(document).on('click', '.rinac-edit-rango', function(e) {
            e.preventDefault();
            var rangoId = $(this).data('id');
            editRango(rangoId);
        });
        
        // Eliminar rango
        $(document).on('click', '.rinac-delete-rango', function(e) {
            e.preventDefault();
            var rangoId = $(this).data('id');
            
            if (confirm(rinac_admin.strings.confirm_delete)) {
                deleteRango(rangoId);
            }
        });
        
        /**
         * Añadir campo de hora
         */
        function addHoraField(descripcion, maxPersonas) {
            horaCounter++;
            
            var html = '<div class="rinac-hora-field rinac-fade-in">' +
                '<span class="rinac-drag-handle">⋮⋮</span>' +
                '<input type="text" name="horas_descripcion[]" value="' + escapeHtml(descripcion) + '" ' +
                'placeholder="' + rinac_admin.strings.placeholder_hora + '" required />' +
                '<input type="number" name="horas_personas[]" value="' + maxPersonas + '" min="1" step="1" ' +
                'title="' + rinac_admin.strings.max_personas_tooltip + '" />' +
                '<button type="button" class="button remove-hora">' + rinac_admin.strings.remove + '</button>' +
                '</div>';
            
            $('#rinac-horas-list').append(html);
            
            // Focus en el nuevo campo
            $('#rinac-horas-list .rinac-hora-field:last input[type="text"]').focus();
        }
        
        /**
         * Editar rango existente
         */
        function editRango(rangoId) {
            showLoading();

            window.RinacAjax.read(
                rinac_admin,
                'get_rango_details',
                { rango_id: rangoId },
                function(data) {
                    hideLoading();

                    populateEditForm(data);
                    $('html, body').animate({
                        scrollTop: $('#rinac-form-section').offset().top - 50
                    }, 500);
                },
                function(err) {
                    hideLoading();
                    showNotification((err && err.message) ? err.message : rinac_admin.strings.error_general, 'error');
                }
            );
        }
        
        /**
         * Eliminar rango
         */
        function deleteRango(rangoId) {
            showLoading();

            window.RinacAjax.write(
                rinac_admin,
                'delete_rango',
                { rango_id: rangoId },
                function(data) {
                    hideLoading();

                    $('.rinac-rango-item[data-id="' + rangoId + '"]').fadeOut(300, function() {
                        $(this).remove();
                    });
                    showNotification(data.message, 'success');
                },
                function(err) {
                    hideLoading();
                    showNotification((err && err.message) ? err.message : rinac_admin.strings.error_general, 'error');
                }
            );
        }
        
        /**
         * Poblar formulario de edición
         */
        function populateEditForm(data) {
            $('input[name="rango_nombre"]').val(data.rango.nombre);
            $('input[name="rango_id"]').val(data.rango.id);
            
            // Limpiar horas existentes
            $('#rinac-horas-list').empty();
            
            // Añadir horas del rango
            if (data.horas && data.horas.length > 0) {
                data.horas.forEach(function(hora) {
                    addHoraField(hora.descripcion, hora.maximo_personas_slot);
                });
            }
            
            // Cambiar texto del botón
            $('input[name="submit_rango"]').val(rinac_admin.strings.update_rango);
        }
    }
    
    /**
     * Operaciones masivas
     */
    function initBulkOperations() {
        // Seleccionar todos los checkboxes
        $(document).on('change', '#select-all-items', function() {
            var isChecked = $(this).is(':checked');
            $('.rinac-bulk-checkbox').prop('checked', isChecked);
            updateBulkActions();
        });
        
        // Checkbox individual
        $(document).on('change', '.rinac-bulk-checkbox', function() {
            updateBulkActions();
        });
        
        // Ejecutar acción masiva
        $(document).on('click', '#rinac-bulk-action-apply', function(e) {
            e.preventDefault();
            
            var action = $('#rinac-bulk-action').val();
            var selectedItems = $('.rinac-bulk-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action || selectedItems.length === 0) {
                showNotification(rinac_admin.strings.select_items_and_action, 'warning');
                return;
            }
            
            if (confirm(rinac_admin.strings.confirm_bulk_action)) {
                executeBulkAction(action, selectedItems);
            }
        });
        
        /**
         * Actualizar estado de acciones masivas
         */
        function updateBulkActions() {
            var selectedCount = $('.rinac-bulk-checkbox:checked').length;
            var totalCount = $('.rinac-bulk-checkbox').length;
            
            if (selectedCount > 0) {
                $('#rinac-bulk-actions').show();
                $('#rinac-selected-count').text(selectedCount);
            } else {
                $('#rinac-bulk-actions').hide();
            }
            
            // Actualizar estado del checkbox "seleccionar todo"
            if (selectedCount === totalCount && totalCount > 0) {
                $('#select-all-items').prop('checked', true).prop('indeterminate', false);
            } else if (selectedCount > 0) {
                $('#select-all-items').prop('checked', false).prop('indeterminate', true);
            } else {
                $('#select-all-items').prop('checked', false).prop('indeterminate', false);
            }
        }
        
        /**
         * Ejecutar acción masiva
         */
        function executeBulkAction(action, items) {
            showLoading();

            window.RinacAjax.write(
                rinac_admin,
                'bulk_action',
                { bulk_action: action, items: items },
                function(data) {
                    hideLoading();

                    showNotification(data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                },
                function(err) {
                    hideLoading();
                    showNotification((err && err.message) ? err.message : rinac_admin.strings.error_general, 'error');
                }
            );
        }
    }
    
    /**
     * Exportación de datos
     */
    function initDataExport() {
        $(document).on('click', '#rinac-export-data', function(e) {
            e.preventDefault();
            
            var startDate = $('#export-start-date').val();
            var endDate = $('#export-end-date').val();
            var format = $('#export-format').val();
            
            if (!startDate || !endDate) {
                showNotification(rinac_admin.strings.select_date_range, 'warning');
                return;
            }
            
            // Crear formulario dinámico para descarga
            var form = $('<form>', {
                'method': 'POST',
                'action': rinac_admin.ajax_url
            }).append(
                $('<input>', {'name': 'action', 'value': 'rinac_export_data', 'type': 'hidden'}),
                $('<input>', {'name': 'start_date', 'value': startDate, 'type': 'hidden'}),
                $('<input>', {'name': 'end_date', 'value': endDate, 'type': 'hidden'}),
                $('<input>', {'name': 'format', 'value': format, 'type': 'hidden'}),
                $('<input>', {'name': 'nonce', 'value': rinac_admin.nonce, 'type': 'hidden'})
            );
            
            form.appendTo('body').submit().remove();
            
            showNotification(rinac_admin.strings.export_started, 'info');
        });
    }
    
    /**
     * Sistema de notificaciones
     */
    function initNotifications() {
        // Auto-ocultar notificaciones después de 5 segundos
        setTimeout(function() {
            $('.rinac-notice').fadeOut(500);
        }, 5000);
        
        // Botón para cerrar notificaciones manualmente
        $(document).on('click', '.rinac-notice-dismiss', function() {
            $(this).closest('.rinac-notice').fadeOut(300);
        });
    }
    
    /**
     * Utilidades
     */
    
    /**
     * Mostrar indicador de carga
     */
    function showLoading() {
        if ($('#rinac-loading-overlay').length === 0) {
            $('body').append('<div id="rinac-loading-overlay" class="rinac-loading-overlay">' +
                '<div class="rinac-loading-spinner"></div>' +
                '<div class="rinac-loading-text">' + rinac_admin.strings.loading + '</div>' +
                '</div>');
        }
        $('#rinac-loading-overlay').fadeIn(200);
    }
    
    /**
     * Ocultar indicador de carga
     */
    function hideLoading() {
        $('#rinac-loading-overlay').fadeOut(200);
    }
    
    /**
     * Mostrar notificación
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var notification = $('<div class="rinac-notice rinac-notice-' + type + '">' +
            '<p>' + escapeHtml(message) + '</p>' +
            '<button type="button" class="rinac-notice-dismiss">&times;</button>' +
            '</div>');
        
        // Remover notificaciones existentes del mismo tipo
        $('.rinac-notice-' + type).remove();
        
        // Añadir nueva notificación
        if ($('#rinac-notifications').length) {
            $('#rinac-notifications').append(notification);
        } else {
            $('body').prepend('<div id="rinac-notifications"></div>');
            $('#rinac-notifications').append(notification);
        }
        
        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            notification.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Escapar HTML para prevenir XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text ? text.toString().replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }
    
    /**
     * Formatear fecha para visualización
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        
        var date = new Date(dateString);
        var options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        
        return date.toLocaleDateString(rinac_admin.locale || 'es-ES', options);
    }
    
    /**
     * Debounce function para optimizar búsquedas
     */
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    /**
     * Búsqueda en tiempo real
     */
    if ($('#rinac-search').length) {
        var searchHandler = debounce(function() {
            var searchTerm = $(this).val();
            filterResults(searchTerm);
        }, 300);
        
        $('#rinac-search').on('input', searchHandler);
    }
    
    /**
     * Filtrar resultados
     */
    function filterResults(searchTerm) {
        $('.rinac-filterable-item').each(function() {
            var itemText = $(this).text().toLowerCase();
            var searchLower = searchTerm.toLowerCase();
            
            if (searchTerm === '' || itemText.indexOf(searchLower) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Mostrar mensaje si no hay resultados
        var visibleItems = $('.rinac-filterable-item:visible').length;
        if (visibleItems === 0 && searchTerm !== '') {
            if ($('#rinac-no-results').length === 0) {
                $('.rinac-filterable-container').append(
                    '<div id="rinac-no-results" class="rinac-no-results">' +
                    rinac_admin.strings.no_results_found +
                    '</div>'
                );
            }
        } else {
            $('#rinac-no-results').remove();
        }
    }
    
    /**
     * Tooltips dinámicos
     */
    if ($('.rinac-tooltip').length) {
        $('.rinac-tooltip').hover(
            function() {
                var tooltip = $(this).attr('data-tooltip');
                if (tooltip) {
                    $(this).append('<div class="rinac-tooltip-content">' + escapeHtml(tooltip) + '</div>');
                }
            },
            function() {
                $('.rinac-tooltip-content').remove();
            }
        );
    }
    
    /**
     * Inicialización de gráficos (si Chart.js está disponible)
     */
    if (typeof Chart !== 'undefined' && $('#rinac-stats-chart').length) {
        initStatsChart();
    }
    
    function initStatsChart() {
        var ctx = document.getElementById('rinac-stats-chart').getContext('2d');
        
        $.ajax({
            url: rinac_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_get_stats_data',
                nonce: rinac_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var chart = new Chart(ctx, {
                        type: 'line',
                        data: response.data,
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: rinac_admin.strings.reservations_chart_title
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
        });
    }
});
