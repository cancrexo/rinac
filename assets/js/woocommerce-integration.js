/**
 * JavaScript para integración avanzada con WooCommerce
 * Funcionalidades específicas para el checkout y carrito
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var cartUpdating = false;
    var checkoutData = {};
    var reservationTimer = null;
    
    // Inicializar según la página
    if ($('body').hasClass('woocommerce-cart')) {
        initCartFunctions();
    }
    
    if ($('body').hasClass('woocommerce-checkout')) {
        initCheckoutFunctions();
    }
    
    if ($('.single-product').length && $('.product-type-visitas').length) {
        initSingleProductFunctions();
    }
    
    /**
     * Funciones del carrito
     */
    function initCartFunctions() {
        // Validar reservas en el carrito
        validateCartReservations();
        
        // Manejar cambios en el carrito
        $(document.body).on('updated_cart_totals', function() {
            validateCartReservations();
        });
        
        // Botón para modificar reserva
        $(document).on('click', '.modify-reservation', function(e) {
            e.preventDefault();
            modifyReservation($(this).data('cart-item-key'));
        });
        
        // Auto-validación cada 30 segundos
        setInterval(validateCartReservations, 30000);
    }
    
    /**
     * Validar reservas en el carrito
     */
    function validateCartReservations() {
        if (cartUpdating) return;
        
        const cartItems = [];
        $('.cart-item[data-product-type="visitas"]').each(function() {
            const $item = $(this);
            cartItems.push({
                key: $item.data('cart-item-key'),
                product_id: $item.data('product-id'),
                fecha: $item.data('fecha'),
                horario: $item.data('horario'),
                personas: $item.find('.qty').val()
            });
        });
        
        if (cartItems.length === 0) return;
        
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_validate_cart_reservations',
                nonce: rinac_wc.nonce,
                cart_items: cartItems
            },
            success: function(response) {
                if (response.success) {
                    updateCartValidationStatus(response.data);
                } else {
                    showCartError(response.data.message || 'Error de validación');
                }
            }
        });
    }
    
    /**
     * Actualizar estado de validación del carrito
     */
    function updateCartValidationStatus(validationData) {
        validationData.forEach(function(item) {
            const $cartItem = $('.cart-item[data-cart-item-key="' + item.key + '"]');
            
            if (!item.available) {
                $cartItem.addClass('reservation-unavailable');
                $cartItem.find('.reservation-status').html(
                    '<span class="error">⚠️ ' + item.message + '</span>'
                );
                
                // Deshabilitar checkout si hay elementos no disponibles
                $('.checkout-button').prop('disabled', true)
                    .text('Resolver problemas de reserva');
                    
            } else if (item.capacity_exceeded) {
                $cartItem.addClass('reservation-warning');
                $cartItem.find('.reservation-status').html(
                    '<span class="warning">⚠️ Capacidad limitada: ' + item.available_spots + ' plazas disponibles</span>'
                );
                
            } else {
                $cartItem.removeClass('reservation-unavailable reservation-warning');
                $cartItem.find('.reservation-status').html(
                    '<span class="success">✓ Reserva confirmada</span>'
                );
                
                // Habilitar checkout si todos los elementos están OK
                if ($('.reservation-unavailable').length === 0) {
                    $('.checkout-button').prop('disabled', false)
                        .text('Proceder al pago');
                }
            }
        });
    }
    
    /**
     * Modificar reserva desde el carrito
     */
    function modifyReservation(cartItemKey) {
        const $cartItem = $('.cart-item[data-cart-item-key="' + cartItemKey + '"]');
        const productId = $cartItem.data('product-id');
        
        // Abrir modal de modificación
        openReservationModal(productId, cartItemKey);
    }
    
    /**
     * Abrir modal de modificación de reserva
     */
    function openReservationModal(productId, cartItemKey) {
        const modal = $(`
            <div id="modify-reservation-modal" class="rinac-modal">
                <div class="rinac-modal-content">
                    <span class="rinac-modal-close">&times;</span>
                    <h3>Modificar Reserva</h3>
                    <div id="modify-reservation-form">
                        <div class="loading">Cargando opciones disponibles...</div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn();
        
        // Cargar formulario de modificación
        loadModificationForm(productId, cartItemKey);
        
        // Cerrar modal
        modal.on('click', '.rinac-modal-close, .rinac-modal', function(e) {
            if (e.target === this) {
                modal.fadeOut(function() {
                    modal.remove();
                });
            }
        });
    }
    
    /**
     * Cargar formulario de modificación
     */
    function loadModificationForm(productId, cartItemKey) {
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_get_modification_form',
                nonce: rinac_wc.nonce,
                product_id: productId,
                cart_item_key: cartItemKey
            },
            success: function(response) {
                if (response.success) {
                    $('#modify-reservation-form').html(response.data.form_html);
                    initModificationFormEvents();
                } else {
                    $('#modify-reservation-form').html('<p class="error">Error al cargar el formulario</p>');
                }
            }
        });
    }
    
    /**
     * Inicializar eventos del formulario de modificación
     */
    function initModificationFormEvents() {
        // Selector de fecha
        $('#modify-fecha').on('change', function() {
            loadAvailableHorarios($(this).val(), '#modify-horario');
        });
        
        // Confirmar modificación
        $('#confirm-modification').on('click', function() {
            saveReservationModification();
        });
    }
    
    /**
     * Guardar modificación de reserva
     */
    function saveReservationModification() {
        const formData = {
            action: 'rinac_modify_cart_reservation',
            nonce: rinac_wc.nonce,
            cart_item_key: $('#modify-cart-item-key').val(),
            fecha: $('#modify-fecha').val(),
            horario: $('#modify-horario').val(),
            personas: $('#modify-personas').val(),
            telefono: $('#modify-telefono').val(),
            comentarios: $('#modify-comentarios').val()
        };
        
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#confirm-modification').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    // Recargar página del carrito
                    window.location.reload();
                } else {
                    showError(response.data.message || 'Error al modificar la reserva');
                    $('#confirm-modification').prop('disabled', false).text('Confirmar cambios');
                }
            }
        });
    }
    
    /**
     * Funciones del checkout
     */
    function initCheckoutFunctions() {
        // Validar reservas antes del pago
        $(document.body).on('checkout_error', function() {
            validateCheckoutReservations();
        });
        
        // Procesar reservas después del pago exitoso
        $(document.body).on('checkout_place_order', function() {
            return processReservationsPayment();
        });
        
        // Mostrar resumen de reservas
        displayReservationSummary();
        
        // Inicializar timer de reserva
        initReservationTimer();
    }
    
    /**
     * Validar reservas en el checkout
     */
    function validateCheckoutReservations() {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: wc_checkout_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'rinac_validate_checkout_reservations',
                    nonce: rinac_wc.nonce
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.data.message);
                    }
                },
                error: function() {
                    reject('Error de conexión durante la validación');
                }
            });
        });
    }
    
    /**
     * Procesar pago de reservas
     */
    function processReservationsPayment() {
        // Esta función se ejecuta cuando se confirma el pedido
        // Las reservas se procesan en el servidor
        
        // Limpiar timer de reserva
        if (reservationTimer) {
            clearInterval(reservationTimer);
        }
        
        return true;
    }
    
    /**
     * Mostrar resumen de reservas en checkout
     */
    function displayReservationSummary() {
        const $orderReview = $('.woocommerce-checkout-review-order');
        
        if ($orderReview.length && $('.cart-item[data-product-type="visitas"]').length) {
            const summaryHtml = generateReservationSummaryHTML();
            $orderReview.after(summaryHtml);
        }
    }
    
    /**
     * Generar HTML del resumen de reservas
     */
    function generateReservationSummaryHTML() {
        let html = '<div class="rinac-reservation-summary">';
        html += '<h3>Resumen de Reservas</h3>';
        
        $('.cart-item[data-product-type="visitas"]').each(function() {
            const $item = $(this);
            const productName = $item.find('.product-name').text();
            const fecha = $item.data('fecha');
            const horario = $item.data('horario');
            const personas = $item.find('.qty').val();
            
            html += '<div class="reservation-summary-item">';
            html += '<h4>' + productName + '</h4>';
            html += '<p><strong>Fecha:</strong> ' + formatDate(fecha) + '</p>';
            html += '<p><strong>Horario:</strong> ' + horario + '</p>';
            html += '<p><strong>Personas:</strong> ' + personas + '</p>';
            html += '</div>';
        });
        
        html += '</div>';
        return html;
    }
    
    /**
     * Timer de reserva
     */
    function initReservationTimer() {
        const timerDuration = 15 * 60; // 15 minutos
        let timeRemaining = timerDuration;
        
        const $timer = $('<div class="rinac-reservation-timer">' +
            '<div class="timer-icon">⏱️</div>' +
            '<div class="timer-text">Tiempo para completar la reserva: <span class="timer-count">15:00</span></div>' +
            '<div class="timer-warning">Las reservas se liberarán automáticamente si no se completa el pago</div>' +
            '</div>');
        
        $('.woocommerce-checkout').prepend($timer);
        
        reservationTimer = setInterval(function() {
            timeRemaining--;
            
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            const timeString = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            $('.timer-count').text(timeString);
            
            // Cambiar color cuando queden 5 minutos
            if (timeRemaining <= 300) {
                $timer.addClass('timer-warning-active');
            }
            
            // Cuando se acabe el tiempo
            if (timeRemaining <= 0) {
                clearInterval(reservationTimer);
                showTimeoutWarning();
            }
        }, 1000);
    }
    
    /**
     * Mostrar advertencia de timeout
     */
    function showTimeoutWarning() {
        const modal = $(`
            <div id="timeout-warning-modal" class="rinac-modal">
                <div class="rinac-modal-content">
                    <h3>⚠️ Tiempo Agotado</h3>
                    <p>El tiempo para completar la reserva ha expirado. Sus reservas han sido liberadas.</p>
                    <p>Por favor, vuelva a realizar la reserva si desea continuar.</p>
                    <div class="modal-actions">
                        <button id="return-to-shop" class="button">Volver a la tienda</button>
                        <button id="retry-reservation" class="button button-primary">Intentar de nuevo</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn();
        
        $('#return-to-shop').on('click', function() {
            window.location.href = wc_checkout_params.shop_url;
        });
        
        $('#retry-reservation').on('click', function() {
            window.location.reload();
        });
    }
    
    /**
     * Funciones del producto individual
     */
    function initSingleProductFunctions() {
        // Verificar disponibilidad en tiempo real
        initRealTimeAvailabilityCheck();
        
        // Preseleccionar fechas populares
        suggestPopularDates();
        
        // Integración con wishlist/favoritos
        initWishlistIntegration();
    }
    
    /**
     * Verificación de disponibilidad en tiempo real
     */
    function initRealTimeAvailabilityCheck() {
        let checkTimeout;
        
        $('#rinac-booking-form input, #rinac-booking-form select').on('change', function() {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAvailabilityRealTime, 500);
        });
    }
    
    /**
     * Verificar disponibilidad en tiempo real
     */
    function checkAvailabilityRealTime() {
        const fecha = $('#rinac-fecha').val();
        const horario = $('#rinac-horario').val();
        const personas = $('#rinac-personas').val();
        
        if (!fecha || !horario || !personas) return;
        
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_check_realtime_availability',
                nonce: rinac_wc.nonce,
                product_id: rinac_wc.product_id,
                fecha: fecha,
                horario: horario,
                personas: personas
            },
            success: function(response) {
                updateAvailabilityStatus(response.data);
            }
        });
    }
    
    /**
     * Actualizar estado de disponibilidad
     */
    function updateAvailabilityStatus(data) {
        const $status = $('.availability-status');
        
        if (data.available) {
            $status.html('<span class="available">✓ Disponible (' + data.spots_left + ' plazas restantes)</span>');
            $('.single_add_to_cart_button').prop('disabled', false);
        } else {
            $status.html('<span class="unavailable">✗ No disponible</span>');
            $('.single_add_to_cart_button').prop('disabled', true);
        }
    }
    
    /**
     * Sugerir fechas populares
     */
    function suggestPopularDates() {
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_get_popular_dates',
                nonce: rinac_wc.nonce,
                product_id: rinac_wc.product_id
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    displayPopularDates(response.data);
                }
            }
        });
    }
    
    /**
     * Mostrar fechas populares
     */
    function displayPopularDates(dates) {
        let html = '<div class="popular-dates">';
        html += '<h4>Fechas populares:</h4>';
        html += '<div class="popular-dates-list">';
        
        dates.forEach(function(date) {
            html += '<button type="button" class="popular-date-btn" data-date="' + date.date + '">';
            html += formatDate(date.date) + ' <span class="booking-count">(' + date.bookings + ' reservas)</span>';
            html += '</button>';
        });
        
        html += '</div></div>';
        
        $('#rinac-booking-form').prepend(html);
        
        // Manejar clicks en fechas populares
        $('.popular-date-btn').on('click', function() {
            $('#rinac-fecha').val($(this).data('date')).trigger('change');
        });
    }
    
    /**
     * Integración con wishlist
     */
    function initWishlistIntegration() {
        // Botón de añadir a favoritos para configuraciones específicas
        $(document).on('click', '.add-to-wishlist-configuration', function() {
            const config = {
                product_id: rinac_wc.product_id,
                fecha: $('#rinac-fecha').val(),
                horario: $('#rinac-horario').val(),
                personas: $('#rinac-personas').val()
            };
            
            addConfigurationToWishlist(config);
        });
    }
    
    /**
     * Añadir configuración a wishlist
     */
    function addConfigurationToWishlist(config) {
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_add_config_to_wishlist',
                nonce: rinac_wc.nonce,
                configuration: config
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Configuración añadida a favoritos');
                } else {
                    showError('Error al añadir a favoritos');
                }
            }
        });
    }
    
    /**
     * Funciones de utilidad
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    function loadAvailableHorarios(fecha, targetSelector) {
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_get_available_horarios',
                nonce: rinac_wc.nonce,
                product_id: rinac_wc.product_id,
                fecha: fecha
            },
            success: function(response) {
                if (response.success) {
                    const $select = $(targetSelector);
                    $select.empty().append('<option value="">Seleccionar horario</option>');
                    
                    response.data.forEach(function(horario) {
                        $select.append('<option value="' + horario.hora + '">' + 
                            horario.hora + ' (' + horario.disponibles + ' plazas)</option>');
                    });
                }
            }
        });
    }
    
    function showCartError(message) {
        $('.woocommerce-cart-form').before(
            '<div class="woocommerce-error" role="alert">' + message + '</div>'
        );
    }
    
    function showError(message) {
        $('.woocommerce-notices-wrapper').append(
            '<div class="woocommerce-error" role="alert">' + message + '</div>'
        );
    }
    
    function showSuccess(message) {
        $('.woocommerce-notices-wrapper').append(
            '<div class="woocommerce-message" role="alert">' + message + '</div>'
        );
    }
});
