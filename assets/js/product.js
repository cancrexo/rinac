/**
 * JavaScript para la configuración de productos RINAC
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var calendarData = {};
    var currentYear = new Date().getFullYear();
    var currentMonth = new Date().getMonth();
    var productId = $('#post_ID').val();
    
    // Inicializar funcionalidades
    initProductTypeHandling();
    initCalendarManagement();
    initHorariosManagement();
    initValidation();
    
    /**
     * Manejo del tipo de producto
     */
    function initProductTypeHandling() {
        // Mostrar/ocultar opciones según el tipo de producto
        $('select#product-type').on('change', function() {
            var productType = $(this).val();
            
            if (productType === 'visitas') {
                showVisitasOptions();
                initVisitasSpecificFeatures();
            } else {
                hideVisitasOptions();
            }
        }).trigger('change');
        
        /**
         * Mostrar opciones específicas de VISITAS
         */
        function showVisitasOptions() {
            $('.show_if_visitas').fadeIn(300);
            $('.hide_if_visitas').fadeOut(300);
            
            // Marcar como virtual automáticamente
            $('#_virtual').prop('checked', true).trigger('change');
            $('#_virtual').closest('.options_group').hide();
            
            // Ocultar campos no necesarios para VISITAS
            $('.show_if_simple, .show_if_variable').hide();
            $('.hide_if_virtual').hide();
            $('.shipping_options').hide();
        }
        
        /**
         * Ocultar opciones específicas de VISITAS
         */
        function hideVisitasOptions() {
            $('.show_if_visitas').hide();
            $('#_virtual').closest('.options_group').show();
        }
        
        /**
         * Inicializar características específicas de VISITAS
         */
        function initVisitasSpecificFeatures() {
            // Validación de precio mínimo
            $('#_regular_price').attr('min', '0.01');
            
            // Tooltip informativo
            if (!$('#rinac-product-info').length) {
                $('.product_data .inside').prepend(
                    '<div id="rinac-product-info" class="rinac-product-message info">' +
                    '<strong>Producto VISITAS:</strong> Este producto permite reservas con fecha y hora específicas.' +
                    '</div>'
                );
            }
        }
    }
    
    /**
     * Gestión del calendario
     */
    function initCalendarManagement() {
        if ($('#rinac-calendar-container').length === 0) return;
        
        loadCalendarData();
        initCalendarControls();
        initBulkCalendarOperations();
        
        /**
         * Cargar datos del calendario
         */
        function loadCalendarData() {
            if (!productId) return;
            
            showCalendarLoading();
            
            $.ajax({
                url: rinac_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'rinac_get_calendar_data',
                    product_id: productId,
                    year: currentYear,
                    month: currentMonth + 1,
                    nonce: rinac_admin.nonce
                },
                success: function(response) {
                    hideCalendarLoading();
                    
                    if (response.success) {
                        $('#rinac-calendar').html(response.data.calendar_html);
                        calendarData = response.data;
                        bindCalendarEvents();
                    } else {
                        showCalendarError('Error al cargar el calendario');
                    }
                },
                error: function() {
                    hideCalendarLoading();
                    showCalendarError('Error de conexión al cargar el calendario');
                }
            });
        }
        
        /**
         * Controles de navegación del calendario
         */
        function initCalendarControls() {
            // Navegación del calendario
            $(document).on('click', '.rinac-calendar-nav', function() {
                var action = $(this).data('action');
                
                if (action === 'prev') {
                    if (currentMonth === 0) {
                        currentMonth = 11;
                        currentYear--;
                    } else {
                        currentMonth--;
                    }
                } else if (action === 'next') {
                    if (currentMonth === 11) {
                        currentMonth = 0;
                        currentYear++;
                    } else {
                        currentMonth++;
                    }
                }
                
                loadCalendarData();
            });
            
            // Vista rápida de año
            $(document).on('click', '.rinac-calendar-header h3', function() {
                showYearMonthPicker();
            });
        }
        
        /**
         * Eventos del calendario
         */
        function bindCalendarEvents() {
            // Click en días del calendario
            $(document).off('click', '.rinac-day').on('click', '.rinac-day', function() {
                var $day = $(this);
                
                if ($day.hasClass('rinac-day-empty') || $day.hasClass('rinac-day-past')) {
                    return;
                }
                
                var fecha = $day.data('date');
                var isAvailable = $day.hasClass('rinac-day-available');
                
                toggleDateAvailability(fecha, !isAvailable);
            });
        }
        
        /**
         * Alternar disponibilidad de fecha
         */
        function toggleDateAvailability(fecha, available) {
            if (!productId || !fecha) return;
            
            var $day = $('.rinac-day[data-date="' + fecha + '"]');
            $day.addClass('rinac-updating');
            
            $.ajax({
                url: rinac_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'rinac_save_calendar_data',
                    product_id: productId,
                    date: fecha,
                    disponible: available ? 1 : 0,
                    nonce: rinac_admin.nonce
                },
                success: function(response) {
                    $day.removeClass('rinac-updating');
                    
                    if (response.success) {
                        if (available) {
                            $day.removeClass('rinac-day-unavailable').addClass('rinac-day-available');
                        } else {
                            $day.removeClass('rinac-day-available').addClass('rinac-day-unavailable');
                        }
                        
                        showCalendarSuccess('Disponibilidad actualizada');
                    } else {
                        showCalendarError(response.data.message || 'Error al actualizar');
                    }
                },
                error: function() {
                    $day.removeClass('rinac-updating');
                    showCalendarError('Error de conexión');
                }
            });
        }
        
        /**
         * Operaciones masivas del calendario
         */
        function initBulkCalendarOperations() {
            // Habilitar todo el mes
            $(document).on('click', '#rinac-enable-month', function() {
                bulkCalendarOperation('enable_month');
            });
            
            // Deshabilitar todo el mes
            $(document).on('click', '#rinac-disable-month', function() {
                bulkCalendarOperation('disable_month');
            });
            
            // Habilitar fines de semana
            $(document).on('click', '#rinac-enable-weekends', function() {
                bulkCalendarOperation('enable_weekends');
            });
            
            // Deshabilitar fines de semana
            $(document).on('click', '#rinac-disable-weekends', function() {
                bulkCalendarOperation('disable_weekends');
            });
        }
        
        /**
         * Ejecutar operación masiva del calendario
         */
        function bulkCalendarOperation(operation) {
            if (!confirm('¿Estás seguro de realizar esta operación?')) {
                return;
            }
            
            var startDate = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-01';
            var endDate = new Date(currentYear, currentMonth + 1, 0).toISOString().split('T')[0];
            
            showCalendarLoading();
            
            $.ajax({
                url: rinac_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'rinac_bulk_calendar_operation',
                    product_id: productId,
                    operation: operation,
                    start_date: startDate,
                    end_date: endDate,
                    nonce: rinac_admin.nonce
                },
                success: function(response) {
                    hideCalendarLoading();
                    
                    if (response.success) {
                        loadCalendarData(); // Recargar calendario
                        showCalendarSuccess(response.data.message);
                    } else {
                        showCalendarError(response.data.message || 'Error en la operación');
                    }
                },
                error: function() {
                    hideCalendarLoading();
                    showCalendarError('Error de conexión');
                }
            });
        }
        
        /**
         * Selector de año y mes
         */
        function showYearMonthPicker() {
            var currentDate = new Date();
            var minYear = currentDate.getFullYear();
            var maxYear = minYear + 3;
            
            var html = '<div id="rinac-date-picker-modal" class="rinac-modal">' +
                '<div class="rinac-modal-content">' +
                '<h3>Ir a fecha</h3>' +
                '<div class="rinac-date-picker-form">' +
                '<select id="rinac-year-select">';
            
            for (var year = minYear; year <= maxYear; year++) {
                var selected = year === currentYear ? ' selected' : '';
                html += '<option value="' + year + '"' + selected + '>' + year + '</option>';
            }
            
            html += '</select>' +
                '<select id="rinac-month-select">';
            
            var months = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            
            months.forEach(function(month, index) {
                var selected = index === currentMonth ? ' selected' : '';
                html += '<option value="' + index + '"' + selected + '>' + month + '</option>';
            });
            
            html += '</select>' +
                '</div>' +
                '<div class="rinac-modal-actions">' +
                '<button type="button" id="rinac-date-picker-go" class="button-primary">Ir</button>' +
                '<button type="button" id="rinac-date-picker-cancel" class="button">Cancelar</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            $('body').append(html);
            
            // Eventos del modal
            $('#rinac-date-picker-go').on('click', function() {
                currentYear = parseInt($('#rinac-year-select').val());
                currentMonth = parseInt($('#rinac-month-select').val());
                loadCalendarData();
                $('#rinac-date-picker-modal').remove();
            });
            
            $('#rinac-date-picker-cancel').on('click', function() {
                $('#rinac-date-picker-modal').remove();
            });
        }
        
        // Funciones de estado del calendario
        function showCalendarLoading() {
            $('#rinac-calendar').addClass('rinac-loading-overlay loading');
        }
        
        function hideCalendarLoading() {
            $('#rinac-calendar').removeClass('rinac-loading-overlay loading');
        }
        
        function showCalendarSuccess(message) {
            showCalendarMessage(message, 'success');
        }
        
        function showCalendarError(message) {
            showCalendarMessage(message, 'error');
        }
        
        function showCalendarMessage(message, type) {
            var $container = $('#rinac-calendar-container');
            $container.find('.rinac-calendar-message').remove();
            
            var $message = $('<div class="rinac-calendar-message rinac-message-' + type + '">' +
                escapeHtml(message) + '</div>');
            
            $container.prepend($message);
            
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }
    
    /**
     * Gestión de horarios
     */
    function initHorariosManagement() {
        if ($('#rinac-horarios-container').length === 0) return;
        
        initHorariosSorting();
        initHorariosActions();
        loadExistingHorarios();
        
        /**
         * Hacer sortable los horarios
         */
        function initHorariosSorting() {
            $('#rinac-horarios-list').sortable({
                handle: '.rinac-drag-handle',
                placeholder: 'rinac-horario-placeholder',
                start: function(event, ui) {
                    ui.placeholder.height(ui.item.height());
                    ui.placeholder.addClass('rinac-sortable-placeholder');
                },
                stop: function(event, ui) {
                    updateHorariosOrder();
                }
            });
        }
        
        /**
         * Acciones de horarios
         */
        function initHorariosActions() {
            // Añadir rango predefinido
            $('#rinac_add_rango').on('click', function() {
                var rangoId = $('#rinac_rango_selector').val();
                
                if (!rangoId) {
                    alert('Selecciona un rango horario');
                    return;
                }
                
                addRangoPredefinido(rangoId);
            });
            
            // Añadir hora manual
            $('#rinac_add_hora_manual').on('click', function() {
                var maxPersonasDefault = parseInt($('#_rinac_maximo_personas_hora').val()) || 
                                        parseInt(rinac_admin.max_personas_default) || 10;
                addHorarioItem('', maxPersonasDefault);
            });
            
            // Eliminar horario
            $(document).on('click', '.rinac-remove-horario', function() {
                if (confirm('¿Eliminar este horario?')) {
                    $(this).closest('.rinac-horario-item').fadeOut(300, function() {
                        $(this).remove();
                        updateHorariosOrder();
                    });
                }
            });
            
            // Validación en tiempo real de horarios
            $(document).on('input', 'input[name="rinac_horarios_descripcion[]"]', function() {
                validateHorarioFormat($(this));
            });
            
            $(document).on('input', 'input[name="rinac_horarios_personas[]"]', function() {
                validatePersonasCount($(this));
            });
        }
        
        /**
         * Añadir rango predefinido
         */
        function addRangoPredefinido(rangoId) {
            $.ajax({
                url: rinac_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'rinac_get_rango_horas',
                    rango_id: rangoId,
                    nonce: rinac_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(function(hora) {
                            addHorarioItem(hora.descripcion, hora.maximo_personas_slot);
                        });
                        
                        // Reset del selector
                        $('#rinac_rango_selector').val('');
                        
                        showHorariosMessage('Horarios añadidos correctamente', 'success');
                    } else {
                        showHorariosMessage('No se pudieron obtener los horarios del rango', 'error');
                    }
                },
                error: function() {
                    showHorariosMessage('Error al obtener los horarios', 'error');
                }
            });
        }
        
        /**
         * Añadir item de horario
         */
        function addHorarioItem(descripcion, maxPersonas) {
            var html = '<div class="rinac-horario-item rinac-fade-in">' +
                '<span class="rinac-drag-handle" title="Arrastra para reordenar">⋮⋮</span>' +
                '<input type="text" name="rinac_horarios_descripcion[]" value="' + escapeHtml(descripcion) + '" ' +
                'placeholder="ej: 15:00h - 16:00h" class="rinac-horario-descripcion" />' +
                '<input type="number" name="rinac_horarios_personas[]" value="' + maxPersonas + '" ' +
                'min="1" step="1" class="rinac-horario-personas" title="Máximo de personas para este horario" />' +
                '<input type="hidden" name="rinac_horarios_id[]" value="" />' +
                '<button type="button" class="rinac-remove-horario button" title="Eliminar horario">✕</button>' +
                '</div>';
            
            $('#rinac-horarios-list').append(html);
            
            // Focus en el nuevo campo
            $('#rinac-horarios-list .rinac-horario-item:last .rinac-horario-descripcion').focus();
            
            updateHorariosOrder();
        }
        
        /**
         * Cargar horarios existentes
         */
        function loadExistingHorarios() {
            // Los horarios existentes ya se cargan desde PHP
            // Aquí solo inicializamos la validación
            $('input[name="rinac_horarios_descripcion[]"]').each(function() {
                validateHorarioFormat($(this));
            });
            
            $('input[name="rinac_horarios_personas[]"]').each(function() {
                validatePersonasCount($(this));
            });
            
            updateHorariosOrder();
        }
        
        /**
         * Actualizar orden de horarios
         */
        function updateHorariosOrder() {
            $('#rinac-horarios-list .rinac-horario-item').each(function(index) {
                $(this).attr('data-order', index + 1);
            });
        }
        
        /**
         * Validar formato de horario
         */
        function validateHorarioFormat(input) {
            var value = input.val().trim();
            var $item = input.closest('.rinac-horario-item');
            
            // Patrón básico para horarios (flexible)
            var pattern = /\d{1,2}:\d{2}/;
            
            if (value && !pattern.test(value)) {
                input.addClass('rinac-field-invalid');
                showFieldError(input, 'Formato sugerido: HH:MM - HH:MM');
            } else {
                input.removeClass('rinac-field-invalid').addClass('rinac-field-valid');
                hideFieldError(input);
            }
        }
        
        /**
         * Validar número de personas
         */
        function validatePersonasCount(input) {
            var value = parseInt(input.val());
            
            if (isNaN(value) || value < 1) {
                input.addClass('rinac-field-invalid');
                showFieldError(input, 'Debe ser un número mayor a 0');
            } else if (value > 100) {
                input.addClass('rinac-field-invalid');
                showFieldError(input, 'Número muy alto (máximo recomendado: 100)');
            } else {
                input.removeClass('rinac-field-invalid').addClass('rinac-field-valid');
                hideFieldError(input);
            }
        }
        
        /**
         * Mostrar error en campo
         */
        function showFieldError(input, message) {
            var errorId = 'error-' + input.attr('name') + '-' + input.closest('.rinac-horario-item').index();
            
            hideFieldError(input); // Limpiar error anterior
            
            var $error = $('<div class="rinac-field-error-message" id="' + errorId + '">' + 
                          escapeHtml(message) + '</div>');
            
            input.after($error);
        }
        
        /**
         * Ocultar error en campo
         */
        function hideFieldError(input) {
            input.siblings('.rinac-field-error-message').remove();
        }
        
        /**
         * Mostrar mensaje en sección de horarios
         */
        function showHorariosMessage(message, type) {
            var $container = $('#rinac-horarios-container');
            $container.find('.rinac-horarios-message').remove();
            
            var $message = $('<div class="rinac-horarios-message rinac-message-' + type + '">' +
                escapeHtml(message) + '</div>');
            
            $container.prepend($message);
            
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }
    
    /**
     * Validación general del formulario
     */
    function initValidation() {
        // Validación antes de guardar
        $('#post').on('submit', function(e) {
            if ($('#product-type').val() !== 'visitas') {
                return true;
            }
            
            var errors = [];
            
            // Validar precio
            var price = parseFloat($('#_regular_price').val());
            if (!price || price <= 0) {
                errors.push('El precio debe ser mayor a 0 para productos VISITAS');
            }
            
            // Validar que hay al menos un horario
            var horariosCount = $('input[name="rinac_horarios_descripcion[]"]').filter(function() {
                return $(this).val().trim() !== '';
            }).length;
            
            if (horariosCount === 0) {
                errors.push('Debes configurar al menos un horario para el producto');
            }
            
            // Validar configuración de máximo personas
            var maxPersonas = parseInt($('#_rinac_maximo_personas_hora').val());
            if (!maxPersonas || maxPersonas < 1) {
                errors.push('Configura el máximo de personas por hora');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Errores encontrados:\n\n' + errors.join('\n'));
                return false;
            }
        });
        
        // Validación en tiempo real del precio
        $('#_regular_price').on('input', function() {
            var price = parseFloat($(this).val());
            var $field = $(this);
            
            if (price && price > 0) {
                $field.removeClass('rinac-field-invalid').addClass('rinac-field-valid');
            } else {
                $field.addClass('rinac-field-invalid');
            }
        });
        
        // Validación del máximo de personas
        $('#_rinac_maximo_personas_hora').on('input', function() {
            var maxPersonas = parseInt($(this).val());
            var $field = $(this);
            
            if (maxPersonas && maxPersonas >= 1) {
                $field.removeClass('rinac-field-invalid').addClass('rinac-field-valid');
            } else {
                $field.addClass('rinac-field-invalid');
            }
        });
    }
    
    /**
     * Utilidades
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
     * Inicialización de tooltips
     */
    if ($('.rinac-help-tip').length) {
        $('.rinac-help-tip').hover(
            function() {
                var $this = $(this);
                var tooltip = $this.attr('data-tip');
                
                if (tooltip && !$this.find('.rinac-tooltip-content').length) {
                    $this.append('<div class="rinac-tooltip-content">' + escapeHtml(tooltip) + '</div>');
                }
            },
            function() {
                $(this).find('.rinac-tooltip-content').remove();
            }
        );
    }
    
    /**
     * Autoguardado de configuración (cada 2 minutos)
     */
    if (productId && $('#product-type').val() === 'visitas') {
        setInterval(function() {
            autoSaveConfiguration();
        }, 120000); // 2 minutos
    }
    
    function autoSaveConfiguration() {
        // Implementar autoguardado si es necesario
        // Por ahora solo guardamos en localStorage como backup
        var config = {
            maxPersonas: $('#_rinac_maximo_personas_hora').val(),
            horarios: [],
            timestamp: Date.now()
        };
        
        $('input[name="rinac_horarios_descripcion[]"]').each(function(index) {
            var descripcion = $(this).val();
            var personas = $('input[name="rinac_horarios_personas[]"]').eq(index).val();
            
            if (descripcion.trim()) {
                config.horarios.push({
                    descripcion: descripcion,
                    personas: personas
                });
            }
        });
        
        localStorage.setItem('rinac_product_config_' + productId, JSON.stringify(config));
    }
});
