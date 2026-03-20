/**
 * JavaScript para el frontend de RINAC
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var bookingData = {
        fecha: null,
        horario: null,
        personas: 1,
        telefono: '',
        comentarios: ''
    };
    
    var availableDates = [];
    var currentStep = 1;
    var isValidating = false;
    
    // Inicializar si estamos en una página de producto VISITAS
    if ($('#rinac-booking-form').length) {
        initBookingForm();
    }
    
    /**
     * Inicializar formulario de reserva
     */
    function initBookingForm() {
        loadAvailableDates();
        initDatePicker();
        initFormValidation();
        initFormSteps();
        initRealTimeUpdates();
        
        // Datos pasados desde PHP
        if (typeof rinacAvailableDates !== 'undefined') {
            availableDates = rinacAvailableDates;
        }
    }
    
    /**
     * Cargar fechas disponibles
     */
    function loadAvailableDates() {
        var productId = rinac_frontend.product_id;
        
        if (!productId) return;
        
        // Las fechas ya vienen desde PHP, pero podemos recargarlas si es necesario
        if (availableDates.length === 0) {
            window.RinacAjax.read(
                rinac_frontend,
                'get_available_dates',
                { product_id: productId },
                function(data) {
                    availableDates = data || [];
                    refreshDatePicker();
                },
                function() {
                    // Silencioso: si falla, el calendario ya tiene fallback con fechas precargadas (si existen).
                }
            );
        }
    }
    
    /**
     * Inicializar selector de fechas
     */
    function initDatePicker() {
        $('#rinac_fecha').datepicker({
            dateFormat: rinac_frontend.calendar_options.date_format,
            firstDay: rinac_frontend.calendar_options.start_day,
            minDate: 0, // No permitir fechas pasadas
            maxDate: '+2y', // Máximo 2 años
            beforeShowDay: function(date) {
                var dateString = $.datepicker.formatDate('yy-mm-dd', date);
                var isAvailable = availableDates.indexOf(dateString) !== -1;
                
                if (isAvailable) {
                    return [true, 'rinac-available-date', 'Fecha disponible'];
                } else {
                    return [false, 'rinac-unavailable-date', 'Fecha no disponible'];
                }
            },
            onSelect: function(dateText, inst) {
                handleDateSelection(dateText);
            },
            showOtherMonths: true,
            selectOtherMonths: false,
            showButtonPanel: true,
            closeText: 'Cerrar',
            currentText: 'Hoy',
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                             'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
            dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá']
        });
    }
    
    /**
     * Manejar selección de fecha
     */
    function handleDateSelection(dateText) {
        bookingData.fecha = dateText;
        
        // Resetear horario seleccionado
        bookingData.horario = null;
        $('#rinac_horario').val('').prop('disabled', true);
        
        // Mostrar loading en horarios
        $('#rinac_horario').html('<option value="">Cargando horarios...</option>');
        
        // Obtener horarios disponibles para la fecha
        loadAvailableTimeSlots(dateText);
        
        // Avanzar al siguiente paso
        updateFormStep(2);
        
        // Smooth scroll al selector de horario
        $('html, body').animate({
            scrollTop: $('#rinac_horario').offset().top - 100
        }, 500);
    }
    
    /**
     * Cargar horarios disponibles para una fecha
     */
    function loadAvailableTimeSlots(fecha) {
        var productId = rinac_frontend.product_id;

        window.RinacAjax.read(
            rinac_frontend,
            'get_horarios',
            { product_id: productId, fecha: fecha },
            function(data) {
                populateTimeSlots(data || []);
            },
            function() {
                showError('Error al cargar los horarios');
                $('#rinac_horario').html('<option value="">Error al cargar horarios</option>');
            }
        );
    }
    
    /**
     * Poblar selector de horarios
     */
    function populateTimeSlots(horarios) {
        var $select = $('#rinac_horario');
        $select.empty();
        
        if (horarios.length === 0) {
            $select.append('<option value="">No hay horarios disponibles</option>');
            return;
        }
        
        $select.append('<option value="">Selecciona un horario</option>');
        
        horarios.forEach(function(horario) {
            var disponibilidadText = '';
            if (horario.disponibles <= 5 && horario.disponibles > 0) {
                disponibilidadText = ' (Últimas ' + horario.disponibles + ' plazas)';
            } else if (horario.disponibles > 5) {
                disponibilidadText = ' (' + horario.disponibles + ' plazas disponibles)';
            }
            
            var option = $('<option></option>')
                .attr('value', horario.descripcion)
                .attr('data-max-personas', horario.max_personas)
                .attr('data-disponibles', horario.disponibles)
                .text(horario.descripcion + disponibilidadText);
            
            $select.append(option);
        });
        
        $select.prop('disabled', false);
        
        // Evento de cambio de horario
        $select.off('change').on('change', function() {
            handleTimeSlotSelection($(this).val());
        });
    }
    
    /**
     * Manejar selección de horario
     */
    function handleTimeSlotSelection(horario) {
        if (!horario) {
            updateFormStep(2);
            return;
        }
        
        bookingData.horario = horario;
        
        var $option = $('#rinac_horario option:selected');
        var maxPersonas = parseInt($option.attr('data-max-personas'));
        var disponibles = parseInt($option.attr('data-disponibles'));
        
        // Actualizar límite de personas
        $('#rinac_personas').attr('max', Math.min(disponibles, maxPersonas));
        
        // Mostrar información de disponibilidad
        updateAvailabilityInfo(disponibles, maxPersonas);
        
        // Avanzar al siguiente paso
        updateFormStep(3);
        
        // Focus en número de personas
        setTimeout(function() {
            $('#rinac_personas').focus();
        }, 100);
    }
    
    /**
     * Actualizar información de disponibilidad
     */
    function updateAvailabilityInfo(disponibles, maxPersonas) {
        var $info = $('.rinac-availability-info');
        var text = '';
        var className = '';
        
        if (disponibles === 0) {
            text = 'Completo';
            className = 'rinac-availability-none';
        } else if (disponibles <= 3) {
            text = 'Últimas ' + disponibles + ' plazas';
            className = 'rinac-availability-low';
        } else if (disponibles <= 10) {
            text = disponibles + ' plazas disponibles';
            className = 'rinac-availability-medium';
        } else {
            text = 'Buena disponibilidad';
            className = 'rinac-availability-high';
        }
        
        $info.text(text).removeClass().addClass('rinac-availability-info ' + className);
    }
    
    /**
     * Inicializar validación del formulario
     */
    function initFormValidation() {
        var $form = $('#rinac-booking-form-element');
        
        // Validación en tiempo real del número de personas
        $('#rinac_personas').on('input change', function() {
            validatePersonasCount();
            updateBookingSummary();
        });
        
        // Validación del teléfono si es requerido
        $('#rinac_telefono').on('input', function() {
            validatePhone();
        });
        
        // Validación al enviar el formulario
        $form.on('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                submitBooking();
            }
        });
        
        // Validación en tiempo real de disponibilidad
        $('#rinac_personas').on('change', function() {
            if (bookingData.fecha && bookingData.horario) {
                checkAvailabilityRealTime();
            }
        });
    }
    
    /**
     * Validar número de personas
     */
    function validatePersonasCount() {
        var $field = $('#rinac_personas');
        var personas = parseInt($field.val());
        var max = parseInt($field.attr('max'));
        var min = parseInt($field.attr('min'));
        
        $field.removeClass('rinac-field-error rinac-field-success');
        $('.rinac-personas-error').remove();
        
        if (isNaN(personas) || personas < min) {
            showFieldError($field, 'Mínimo ' + min + ' persona');
            return false;
        }
        
        if (personas > max) {
            showFieldError($field, 'Máximo ' + max + ' personas disponibles');
            return false;
        }
        
        $field.addClass('rinac-field-success');
        bookingData.personas = personas;
        return true;
    }
    
    /**
     * Validar teléfono
     */
    function validatePhone() {
        var $field = $('#rinac_telefono');
        var phone = $field.val().trim();
        
        $field.removeClass('rinac-field-error rinac-field-success');
        $('.rinac-phone-error').remove();
        
        if ($field.attr('required') && !phone) {
            showFieldError($field, 'El teléfono es obligatorio');
            return false;
        }
        
        if (phone && !/^[+]?[\d\s\-\(\)]+$/.test(phone)) {
            showFieldError($field, 'Formato de teléfono no válido');
            return false;
        }
        
        if (phone) {
            $field.addClass('rinac-field-success');
        }
        
        bookingData.telefono = phone;
        return true;
    }
    
    /**
     * Validar formulario completo
     */
    function validateForm() {
        var isValid = true;
        
        // Limpiar errores anteriores
        clearFormErrors();
        
        // Validar fecha
        if (!bookingData.fecha) {
            showError(rinac_frontend.strings.select_date);
            isValid = false;
        }
        
        // Validar horario
        if (!bookingData.horario) {
            showError(rinac_frontend.strings.select_time);
            isValid = false;
        }
        
        // Validar personas
        if (!validatePersonasCount()) {
            isValid = false;
        }
        
        // Validar teléfono
        if (!validatePhone()) {
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Verificar disponibilidad en tiempo real
     */
    function checkAvailabilityRealTime() {
        if (isValidating) return;
        
        isValidating = true;

        window.RinacAjax.read(
            rinac_frontend,
            'check_availability',
            {
                product_id: rinac_frontend.product_id,
                fecha: bookingData.fecha,
                horario: bookingData.horario,
                personas: bookingData.personas
            },
            function(data) {
                isValidating = false;

                if (!data.available) {
                    showError('No hay suficiente disponibilidad para ' + bookingData.personas + ' personas');
                    updateAvailabilityInfo(data.disponibles, data.max_personas);
                    $('.single_add_to_cart_button').prop('disabled', true);
                } else {
                    hideError();
                    updateAvailabilityInfo(data.disponibles, data.max_personas);
                    $('.single_add_to_cart_button').prop('disabled', false);
                }
            },
            function() {
                isValidating = false;
            }
        );
    }
    
    /**
     * Inicializar pasos del formulario
     */
    function initFormSteps() {
        updateFormStep(1);
    }
    
    /**
     * Actualizar paso del formulario
     */
    function updateFormStep(step) {
        currentStep = step;
        var $form = $('#rinac-booking-form');
        
        // Remover clases de paso anteriores
        $form.removeClass('rinac-form-step-1 rinac-form-step-2 rinac-form-step-3');
        
        // Añadir clase del paso actual
        $form.addClass('rinac-form-step-' + step);
        
        // Habilitar/deshabilitar campos según el paso
        switch (step) {
            case 1:
                $('#rinac_horario').prop('disabled', true);
                $('#rinac_personas').prop('disabled', true);
                $('.single_add_to_cart_button').prop('disabled', true);
                break;
            case 2:
                $('#rinac_personas').prop('disabled', true);
                $('.single_add_to_cart_button').prop('disabled', true);
                break;
            case 3:
                $('#rinac_personas').prop('disabled', false);
                updateBookingSummary();
                break;
        }
    }
    
    /**
     * Actualizar resumen de reserva
     */
    function updateBookingSummary() {
        if (currentStep < 3) {
            $('.rinac-booking-summary').hide();
            return;
        }
        
        var $summary = $('.rinac-booking-summary');
        var $content = $('#rinac-summary-content');
        
        if (!bookingData.fecha || !bookingData.horario) {
            $summary.hide();
            return;
        }
        
        // Formatear fecha
        var fechaFormatted = formatDate(bookingData.fecha);
        
        var html = '<div class="rinac-summary-item">' +
            '<span class="rinac-summary-label">Fecha:</span>' +
            '<span class="rinac-summary-value">' + fechaFormatted + '</span>' +
            '</div>' +
            '<div class="rinac-summary-item">' +
            '<span class="rinac-summary-label">Horario:</span>' +
            '<span class="rinac-summary-value">' + escapeHtml(bookingData.horario) + '</span>' +
            '</div>' +
            '<div class="rinac-summary-item">' +
            '<span class="rinac-summary-label">Personas:</span>' +
            '<span class="rinac-summary-value">' + bookingData.personas + '</span>' +
            '</div>';
        
        $content.html(html);
        $summary.show().addClass('rinac-fade-in');
        
        // Habilitar botón de reserva si todo está completo
        var canSubmit = validatePersonasCount() && validatePhone();
        $('.single_add_to_cart_button').prop('disabled', !canSubmit);
    }
    
    /**
     * Inicializar actualizaciones en tiempo real
     */
    function initRealTimeUpdates() {
        // Actualizar datos cuando cambian los campos
        $('#rinac_comentarios').on('input', function() {
            bookingData.comentarios = $(this).val();
        });
    }
    
    /**
     * Enviar reserva
     */
    function submitBooking() {
        if (isValidating) return;
        
        var $button = $('.single_add_to_cart_button');
        var originalText = $button.text();
        
        // Estado de carga
        $button.prop('disabled', true).text('Procesando...');
        
        // Los datos se envían automáticamente con el formulario
        // El formulario se maneja por WooCommerce pero podemos añadir validación final
        
        setTimeout(function() {
            // Permitir que el formulario se envíe normalmente
            $('#rinac-booking-form-element')[0].submit();
        }, 500);
    }
    
    /**
     * Actualizar selector de fechas
     */
    function refreshDatePicker() {
        $('#rinac_fecha').datepicker('destroy');
        initDatePicker();
    }
    
    /**
     * Utilidades
     */
    
    /**
     * Mostrar error
     */
    function showError(message) {
        var $container = $('#rinac-booking-messages');
        $container.html('<div class="rinac-message rinac-message-error">' + 
                       escapeHtml(message) + '</div>');
    }
    
    /**
     * Ocultar error
     */
    function hideError() {
        $('#rinac-booking-messages').empty();
    }
    
    /**
     * Mostrar error en campo específico
     */
    function showFieldError($field, message) {
        $field.addClass('rinac-field-error');
        
        var errorClass = 'rinac-' + $field.attr('id').replace('rinac_', '') + '-error';
        $('.' + errorClass).remove();
        
        var $error = $('<small class="' + errorClass + ' rinac-field-error-text">' + 
                      escapeHtml(message) + '</small>');
        
        $field.after($error);
    }
    
    /**
     * Limpiar errores del formulario
     */
    function clearFormErrors() {
        $('.rinac-field-error').removeClass('rinac-field-error');
        $('.rinac-field-error-text').remove();
        hideError();
    }
    
    /**
     * Escapar HTML
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
     * Formatear fecha para mostrar
     */
    function formatDate(dateString) {
        var date = new Date(dateString);
        var options = { 
            weekday: 'long',
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        
        return date.toLocaleDateString('es-ES', options);
    }
    
    /**
     * Smooth scroll a elemento
     */
    function scrollToElement($element, offset) {
        offset = offset || 100;
        
        $('html, body').animate({
            scrollTop: $element.offset().top - offset
        }, 500);
    }
    
    /**
     * Detectar cambios en disponibilidad (polling cada 30 segundos)
     */
    if (bookingData.fecha && bookingData.horario) {
        setInterval(function() {
            checkAvailabilityRealTime();
        }, 30000);
    }
    
    /**
     * Manejar cambios de ventana (advertir si hay datos sin guardar)
     */
    window.addEventListener('beforeunload', function(e) {
        if (bookingData.fecha || bookingData.horario) {
            e.preventDefault();
            e.returnValue = '¿Estás seguro de que quieres salir? Los datos de tu reserva se perderán.';
            return e.returnValue;
        }
    });
    
    /**
     * Remover advertencia al enviar formulario
     */
    $('#rinac-booking-form-element').on('submit', function() {
        window.removeEventListener('beforeunload', arguments.callee);
    });
    
    /**
     * Analytics y tracking (si es necesario)
     */
    function trackBookingEvent(eventName, data) {
        // Implementar tracking si es necesario
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                'custom_map': data
            });
        }
        
        if (typeof fbq !== 'undefined') {
            fbq('trackCustom', eventName, data);
        }
    }
    
    // Eventos de tracking
    $(document).on('change', '#rinac_fecha', function() {
        trackBookingEvent('booking_date_selected', {
            date: $(this).val(),
            product_id: rinac_frontend.product_id
        });
    });
    
    $(document).on('change', '#rinac_horario', function() {
        trackBookingEvent('booking_time_selected', {
            time: $(this).val(),
            product_id: rinac_frontend.product_id
        });
    });
});
