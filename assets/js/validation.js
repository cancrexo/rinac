/**
 * JavaScript para validaciones avanzadas en tiempo real
 * Sistema de validación complejo para formularios y reservas
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var validationRules = {};
    var validationMessages = {};
    var validationTimers = {};
    var currentValidations = {};
    
    // Inicializar validaciones
    if (typeof rinac_validation !== 'undefined') {
        validationRules = rinac_validation.rules;
        validationMessages = rinac_validation.messages;
        initValidationSystem();
    }
    
    /**
     * Inicializar sistema de validación
     */
    function initValidationSystem() {
        initFormValidation();
        initRealTimeValidation();
        initCustomValidators();
        initConditionalValidation();
        initBulkValidation();
    }
    
    /**
     * Validación de formularios
     */
    function initFormValidation() {
        // Validar formularios al enviar
        $('form[data-rinac-validate]').on('submit', function(e) {
            const formId = $(this).attr('id') || 'form';
            if (!validateForm(formId)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Validar campos individuales
        $('[data-validate]').each(function() {
            const $field = $(this);
            const rules = $field.data('validate').split('|');
            
            $field.on('blur change', function() {
                validateField($field, rules);
            });
            
            // Validación en tiempo real para campos críticos
            if ($field.hasClass('realtime-validate')) {
                $field.on('input', function() {
                    debounceValidation($field, rules, 500);
                });
            }
        });
    }
    
    /**
     * Validación en tiempo real
     */
    function initRealTimeValidation() {
        // Validación de disponibilidad de fechas
        $('#rinac-fecha').on('change', function() {
            validateDateAvailability($(this).val());
        });
        
        // Validación de capacidad de horarios
        $('#rinac-horario').on('change', function() {
            validateHorarioCapacity($('#rinac-fecha').val(), $(this).val(), $('#rinac-personas').val());
        });
        
        // Validación de número de personas
        $('#rinac-personas').on('change input', function() {
            validatePersonasCount($(this).val());
        });
        
        // Validación de teléfono en tiempo real
        $('#rinac-telefono').on('input', function() {
            debounceValidation($(this), ['phone'], 300);
        });
        
        // Validación de email
        $('#rinac-email').on('input', function() {
            debounceValidation($(this), ['email'], 500);
        });
    }
    
    /**
     * Validar formulario completo
     */
    function validateForm(formId) {
        const $form = $('#' + formId);
        let isValid = true;
        const errors = [];
        
        // Limpiar errores previos
        clearFormErrors($form);
        
        // Validar cada campo
        $form.find('[data-validate]').each(function() {
            const $field = $(this);
            const rules = $field.data('validate').split('|');
            const fieldResult = validateField($field, rules);
            
            if (!fieldResult.valid) {
                isValid = false;
                errors.push({
                    field: $field.attr('name') || $field.attr('id'),
                    message: fieldResult.message
                });
            }
        });
        
        // Validaciones personalizadas del formulario
        const customValidation = validateFormCustom(formId);
        if (!customValidation.valid) {
            isValid = false;
            errors.push(...customValidation.errors);
        }
        
        // Mostrar errores si existen
        if (!isValid) {
            displayFormErrors($form, errors);
            scrollToFirstError($form);
        }
        
        return isValid;
    }
    
    /**
     * Validar campo individual
     */
    function validateField($field, rules) {
        const value = $field.val();
        const fieldName = $field.attr('name') || $field.attr('id');
        
        // Limpiar error previo
        clearFieldError($field);
        
        for (let i = 0; i < rules.length; i++) {
            const rule = rules[i].trim();
            const validation = executeValidationRule(rule, value, $field);
            
            if (!validation.valid) {
                displayFieldError($field, validation.message);
                return {
                    valid: false,
                    message: validation.message
                };
            }
        }
        
        // Campo válido
        displayFieldSuccess($field);
        return { valid: true };
    }
    
    /**
     * Ejecutar regla de validación
     */
    function executeValidationRule(rule, value, $field) {
        const parts = rule.split(':');
        const ruleName = parts[0];
        const ruleParam = parts[1];
        
        switch (ruleName) {
            case 'required':
                return validateRequired(value);
                
            case 'email':
                return validateEmail(value);
                
            case 'phone':
                return validatePhone(value);
                
            case 'date':
                return validateDate(value);
                
            case 'time':
                return validateTime(value);
                
            case 'min':
                return validateMin(value, parseInt(ruleParam));
                
            case 'max':
                return validateMax(value, parseInt(ruleParam));
                
            case 'minlength':
                return validateMinLength(value, parseInt(ruleParam));
                
            case 'maxlength':
                return validateMaxLength(value, parseInt(ruleParam));
                
            case 'numeric':
                return validateNumeric(value);
                
            case 'alpha':
                return validateAlpha(value);
                
            case 'alphanumeric':
                return validateAlphanumeric(value);
                
            case 'url':
                return validateUrl(value);
                
            case 'future_date':
                return validateFutureDate(value);
                
            case 'working_day':
                return validateWorkingDay(value);
                
            case 'available_date':
                return validateAvailableDate(value, $field);
                
            case 'available_time':
                return validateAvailableTime(value, $field);
                
            case 'capacity':
                return validateCapacity(value, $field);
                
            default:
                // Validación personalizada
                if (typeof window['validate_' + ruleName] === 'function') {
                    return window['validate_' + ruleName](value, ruleParam, $field);
                }
                return { valid: true };
        }
    }
    
    /**
     * Validaciones básicas
     */
    function validateRequired(value) {
        const isValid = value !== null && value !== undefined && value.toString().trim() !== '';
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.required
        };
    }
    
    function validateEmail(value) {
        if (!value) return { valid: true };
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(value);
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.email
        };
    }
    
    function validatePhone(value) {
        if (!value) return { valid: true };
        
        // Formato español: permite +34, 0034, o número directo
        const phoneRegex = /^(\+34|0034|34)?[6789]\d{8}$/;
        const cleanValue = value.replace(/[\s\-\(\)]/g, '');
        const isValid = phoneRegex.test(cleanValue);
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.phone
        };
    }
    
    function validateDate(value) {
        if (!value) return { valid: true };
        
        const date = new Date(value);
        const isValid = !isNaN(date.getTime());
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.date
        };
    }
    
    function validateTime(value) {
        if (!value) return { valid: true };
        
        const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
        const isValid = timeRegex.test(value);
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.time
        };
    }
    
    function validateNumeric(value) {
        if (!value) return { valid: true };
        
        const isValid = /^\d+$/.test(value);
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.numeric
        };
    }
    
    function validateMin(value, min) {
        if (!value) return { valid: true };
        
        const numValue = parseFloat(value);
        const isValid = !isNaN(numValue) && numValue >= min;
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.min.replace(':min', min)
        };
    }
    
    function validateMax(value, max) {
        if (!value) return { valid: true };
        
        const numValue = parseFloat(value);
        const isValid = !isNaN(numValue) && numValue <= max;
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.max.replace(':max', max)
        };
    }
    
    /**
     * Validaciones específicas de RINAC
     */
    function validateFutureDate(value) {
        if (!value) return { valid: true };
        
        const selectedDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const isValid = selectedDate >= today;
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.future_date
        };
    }
    
    function validateWorkingDay(value) {
        if (!value) return { valid: true };
        
        const date = new Date(value);
        const dayOfWeek = date.getDay();
        
        // Asumir que lunes-viernes son días laborables (1-5)
        const isValid = dayOfWeek >= 1 && dayOfWeek <= 5;
        return {
            valid: isValid,
            message: isValid ? '' : validationMessages.working_day
        };
    }
    
    function validateAvailableDate(value, $field) {
        // Esta validación se hace vía AJAX
        if (!value) return { valid: true };
        
        // Marcar como pendiente de validación asíncrona
        markAsyncValidation($field, 'available_date', value);
        
        return { valid: true }; // Se validará asíncronamente
    }
    
    function validateAvailableTime(value, $field) {
        if (!value) return { valid: true };
        
        const fecha = $('#rinac-fecha').val();
        if (!fecha) return { valid: true };
        
        // Marcar como pendiente de validación asíncrona
        markAsyncValidation($field, 'available_time', { fecha: fecha, hora: value });
        
        return { valid: true };
    }
    
    function validateCapacity(value, $field) {
        if (!value) return { valid: true };
        
        const fecha = $('#rinac-fecha').val();
        const horario = $('#rinac-horario').val();
        
        if (!fecha || !horario) return { valid: true };
        
        // Marcar como pendiente de validación asíncrona
        markAsyncValidation($field, 'capacity', { fecha: fecha, horario: horario, personas: value });
        
        return { valid: true };
    }
    
    /**
     * Validación asíncrona
     */
    function markAsyncValidation($field, type, data) {
        const fieldId = $field.attr('id') || $field.attr('name');
        
        // Limpiar timer previo
        if (validationTimers[fieldId]) {
            clearTimeout(validationTimers[fieldId]);
        }
        
        // Programar validación
        validationTimers[fieldId] = setTimeout(function() {
            executeAsyncValidation($field, type, data);
        }, 500);
    }
    
    /**
     * Ejecutar validación asíncrona
     */
    function executeAsyncValidation($field, type, data) {
        const fieldId = $field.attr('id') || $field.attr('name');
        
        // Mostrar indicador de carga
        showFieldLoading($field);
        
        $.ajax({
            url: rinac_validation.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_validate_async',
                nonce: rinac_validation.nonce,
                type: type,
                data: data,
                product_id: rinac_validation.product_id
            },
            success: function(response) {
                hideFieldLoading($field);
                
                if (response.success) {
                    if (response.data.valid) {
                        displayFieldSuccess($field);
                        currentValidations[fieldId] = true;
                    } else {
                        displayFieldError($field, response.data.message);
                        currentValidations[fieldId] = false;
                    }
                } else {
                    displayFieldError($field, 'Error de validación');
                    currentValidations[fieldId] = false;
                }
            },
            error: function() {
                hideFieldLoading($field);
                displayFieldError($field, 'Error de conexión');
                currentValidations[fieldId] = false;
            }
        });
    }
    
    /**
     * Validación con debounce
     */
    function debounceValidation($field, rules, delay) {
        const fieldId = $field.attr('id') || $field.attr('name');
        
        if (validationTimers[fieldId]) {
            clearTimeout(validationTimers[fieldId]);
        }
        
        validationTimers[fieldId] = setTimeout(function() {
            validateField($field, rules);
        }, delay);
    }
    
    /**
     * Validaciones específicas de disponibilidad
     */
    function validateDateAvailability(fecha) {
        if (!fecha) return;
        
        $.ajax({
            url: rinac_validation.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_check_date_availability',
                nonce: rinac_validation.nonce,
                fecha: fecha,
                product_id: rinac_validation.product_id
            },
            success: function(response) {
                updateDateAvailability(response.data);
            }
        });
    }
    
    function validateHorarioCapacity(fecha, horario, personas) {
        if (!fecha || !horario || !personas) return;
        
        $.ajax({
            url: rinac_validation.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_check_horario_capacity',
                nonce: rinac_validation.nonce,
                fecha: fecha,
                horario: horario,
                personas: personas,
                product_id: rinac_validation.product_id
            },
            success: function(response) {
                updateHorarioCapacity(response.data);
            }
        });
    }
    
    function validatePersonasCount(personas) {
        const max = parseInt(rinac_validation.max_personas) || 10;
        const min = 1;
        
        const $field = $('#rinac-personas');
        const numPersonas = parseInt(personas);
        
        if (isNaN(numPersonas) || numPersonas < min) {
            displayFieldError($field, 'Mínimo ' + min + ' persona');
            return false;
        }
        
        if (numPersonas > max) {
            displayFieldError($field, 'Máximo ' + max + ' personas');
            return false;
        }
        
        displayFieldSuccess($field);
        return true;
    }
    
    /**
     * Actualizar interfaces de disponibilidad
     */
    function updateDateAvailability(data) {
        const $dateField = $('#rinac-fecha');
        const $horariosField = $('#rinac-horario');
        
        if (data.available) {
            displayFieldSuccess($dateField);
            
            // Actualizar horarios disponibles
            $horariosField.empty().append('<option value="">Seleccionar horario</option>');
            
            if (data.horarios && data.horarios.length > 0) {
                data.horarios.forEach(function(horario) {
                    $horariosField.append(
                        '<option value="' + horario.hora + '">' + 
                        horario.hora + ' (' + horario.disponibles + ' plazas)</option>'
                    );
                });
                $horariosField.prop('disabled', false);
            } else {
                $horariosField.append('<option value="">No hay horarios disponibles</option>');
                $horariosField.prop('disabled', true);
            }
        } else {
            displayFieldError($dateField, data.message || 'Fecha no disponible');
            $horariosField.empty().append('<option value="">Seleccionar fecha primero</option>');
            $horariosField.prop('disabled', true);
        }
    }
    
    function updateHorarioCapacity(data) {
        const $capacityInfo = $('.capacity-info');
        
        if (data.available) {
            $capacityInfo.html(
                '<span class="capacity-available">✓ ' + data.spots_left + ' plazas disponibles</span>'
            );
            $('.single_add_to_cart_button').prop('disabled', false);
        } else {
            $capacityInfo.html(
                '<span class="capacity-full">✗ ' + (data.message || 'Sin plazas disponibles') + '</span>'
            );
            $('.single_add_to_cart_button').prop('disabled', true);
        }
    }
    
    /**
     * Funciones de interfaz
     */
    function displayFieldError($field, message) {
        clearFieldStatus($field);
        
        $field.addClass('field-error');
        
        const errorHtml = '<div class="field-error-message">' + message + '</div>';
        $field.closest('.form-group, .form-field').append(errorHtml);
    }
    
    function displayFieldSuccess($field) {
        clearFieldStatus($field);
        $field.addClass('field-success');
    }
    
    function clearFieldError($field) {
        clearFieldStatus($field);
    }
    
    function clearFieldStatus($field) {
        $field.removeClass('field-error field-success field-loading');
        $field.closest('.form-group, .form-field').find('.field-error-message, .field-loading-indicator').remove();
    }
    
    function showFieldLoading($field) {
        clearFieldStatus($field);
        $field.addClass('field-loading');
        
        const loadingHtml = '<div class="field-loading-indicator">Validando...</div>';
        $field.closest('.form-group, .form-field').append(loadingHtml);
    }
    
    function hideFieldLoading($field) {
        $field.removeClass('field-loading');
        $field.closest('.form-group, .form-field').find('.field-loading-indicator').remove();
    }
    
    function clearFormErrors($form) {
        $form.find('.field-error-message, .form-error-message').remove();
        $form.find('.field-error, .field-success, .field-loading').removeClass('field-error field-success field-loading');
    }
    
    function displayFormErrors($form, errors) {
        let errorHtml = '<div class="form-error-summary"><ul>';
        
        errors.forEach(function(error) {
            errorHtml += '<li>' + error.message + '</li>';
        });
        
        errorHtml += '</ul></div>';
        
        $form.prepend(errorHtml);
    }
    
    function scrollToFirstError($form) {
        const $firstError = $form.find('.field-error').first();
        if ($firstError.length) {
            $('html, body').animate({
                scrollTop: $firstError.offset().top - 100
            }, 500);
            $firstError.focus();
        }
    }
    
    /**
     * Validaciones personalizadas específicas
     */
    function validateFormCustom(formId) {
        const errors = [];
        
        // Validación específica para formulario de reserva
        if (formId === 'rinac-booking-form') {
            // Verificar que la fecha no sea en el pasado
            const fecha = $('#rinac-fecha').val();
            if (fecha) {
                const selectedDate = new Date(fecha);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    errors.push({
                        field: 'fecha',
                        message: 'No se pueden hacer reservas en fechas pasadas'
                    });
                }
            }
            
            // Verificar combinación fecha-horario válida
            const horario = $('#rinac-horario').val();
            if (fecha && horario) {
                const dateTime = new Date(fecha + ' ' + horario);
                const now = new Date();
                
                if (dateTime <= now) {
                    errors.push({
                        field: 'horario',
                        message: 'No se pueden hacer reservas en horarios pasados'
                    });
                }
            }
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    /**
     * Validaciones en lote
     */
    function initBulkValidation() {
        // Para formularios con múltiples reservas
        $('.validate-bulk').on('click', function() {
            const $container = $(this).closest('.bulk-container');
            const $forms = $container.find('form[data-rinac-validate]');
            
            let allValid = true;
            
            $forms.each(function() {
                const formId = $(this).attr('id');
                if (!validateForm(formId)) {
                    allValid = false;
                }
            });
            
            if (allValid) {
                $(this).closest('form').submit();
            }
        });
    }
    
    /**
     * Validaciones condicionales
     */
    function initConditionalValidation() {
        // Validaciones que dependen de otros campos
        $('[data-conditional-validate]').each(function() {
            const $field = $(this);
            const conditions = $field.data('conditional-validate');
            
            // Escuchar cambios en campos de condición
            $(conditions.field).on('change', function() {
                evaluateConditionalValidation($field, conditions);
            });
        });
    }
    
    function evaluateConditionalValidation($field, conditions) {
        const $conditionField = $(conditions.field);
        const conditionValue = $conditionField.val();
        
        let shouldValidate = false;
        
        switch (conditions.operator) {
            case 'equals':
                shouldValidate = conditionValue === conditions.value;
                break;
            case 'not_equals':
                shouldValidate = conditionValue !== conditions.value;
                break;
            case 'greater_than':
                shouldValidate = parseFloat(conditionValue) > parseFloat(conditions.value);
                break;
            case 'less_than':
                shouldValidate = parseFloat(conditionValue) < parseFloat(conditions.value);
                break;
            case 'contains':
                shouldValidate = conditionValue.includes(conditions.value);
                break;
        }
        
        if (shouldValidate) {
            $field.attr('data-validate', conditions.rules);
        } else {
            $field.removeAttr('data-validate');
            clearFieldError($field);
        }
    }
    
    /**
     * API pública para validaciones personalizadas
     */
    window.RinacValidator = {
        validateField: validateField,
        validateForm: validateForm,
        addCustomValidator: function(name, validator) {
            window['validate_' + name] = validator;
        },
        clearFieldError: clearFieldError,
        displayFieldError: displayFieldError,
        displayFieldSuccess: displayFieldSuccess
    };
});
