/**
 * JavaScript para calendario avanzado con múltiples vistas
 * Calendario interactivo con funcionalidades avanzadas
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var calendar = null;
    var calendarData = {};
    var currentView = 'month';
    var selectedDate = null;
    var availableDates = [];
    var bookingData = {};
    
    // Inicializar calendario si existe el contenedor
    if ($('#rinac-calendar-container').length) {
        initCalendar();
    }
    
    /**
     * Inicializar calendario principal
     */
    function initCalendar() {
        loadCalendarData();
        createCalendar();
        initCalendarEvents();
        initCalendarFilters();
    }
    
    /**
     * Cargar datos del calendario
     */
    function loadCalendarData() {
        if (typeof rinac_calendar !== 'undefined') {
            calendarData = rinac_calendar;
            availableDates = calendarData.available_dates || [];
            bookingData = calendarData.booking_data || {};
        }
    }
    
    /**
     * Crear calendario con FullCalendar
     */
    function createCalendar() {
        const calendarEl = document.getElementById('rinac-calendar-container');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                list: 'Lista'
            },
            height: 'auto',
            firstDay: 1, // Lunes como primer día
            weekNumbers: true,
            weekNumberFormat: { week: 'numeric' },
            
            // Eventos del calendario
            events: function(info, successCallback, failureCallback) {
                loadCalendarEvents(info, successCallback, failureCallback);
            },
            
            // Callbacks
            dateClick: function(info) {
                handleDateClick(info);
            },
            
            eventClick: function(info) {
                handleEventClick(info);
            },
            
            eventDidMount: function(info) {
                customizeEventDisplay(info);
            },
            
            dayCellDidMount: function(info) {
                customizeDayCell(info);
            },
            
            viewDidMount: function(info) {
                currentView = info.view.type;
                updateCalendarControls();
            },
            
            datesSet: function(info) {
                updateDateRange(info.start, info.end);
            }
        });
        
        calendar.render();
    }
    
    /**
     * Cargar eventos del calendario
     */
    function loadCalendarEvents(info, successCallback, failureCallback) {
        $.ajax({
            url: rinac_calendar.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_get_calendar_events',
                nonce: rinac_calendar.nonce,
                start: info.start.toISOString(),
                end: info.end.toISOString(),
                product_id: rinac_calendar.product_id,
                view: currentView
            },
            success: function(response) {
                if (response.success) {
                    const events = formatCalendarEvents(response.data);
                    successCallback(events);
                } else {
                    failureCallback(response.data.message);
                }
            },
            error: function() {
                failureCallback('Error al cargar eventos');
            }
        });
    }
    
    /**
     * Formatear eventos para FullCalendar
     */
    function formatCalendarEvents(data) {
        const events = [];
        
        // Agregar reservas
        if (data.reservas) {
            data.reservas.forEach(function(reserva) {
                events.push({
                    id: 'reserva-' + reserva.id,
                    title: reserva.titulo || 'Reserva',
                    start: reserva.fecha + 'T' + reserva.hora_inicio,
                    end: reserva.fecha + 'T' + reserva.hora_fin,
                    backgroundColor: getReservaColor(reserva.estado),
                    borderColor: getReservaBorderColor(reserva.estado),
                    textColor: '#fff',
                    extendedProps: {
                        type: 'reserva',
                        reserva: reserva
                    }
                });
            });
        }
        
        // Agregar disponibilidad
        if (data.disponibilidad) {
            data.disponibilidad.forEach(function(slot) {
                if (slot.disponible > 0) {
                    events.push({
                        id: 'disponible-' + slot.fecha + '-' + slot.hora.replace(':', ''),
                        title: slot.disponible + ' plazas libres',
                        start: slot.fecha + 'T' + slot.hora,
                        backgroundColor: '#28a745',
                        borderColor: '#1e7e34',
                        textColor: '#fff',
                        display: currentView === 'timeGridDay' ? 'block' : 'none',
                        extendedProps: {
                            type: 'disponible',
                            slot: slot
                        }
                    });
                }
            });
        }
        
        // Agregar días especiales
        if (data.dias_especiales) {
            data.dias_especiales.forEach(function(dia) {
                events.push({
                    id: 'especial-' + dia.fecha,
                    title: dia.nombre,
                    start: dia.fecha,
                    allDay: true,
                    backgroundColor: dia.color || '#ffc107',
                    borderColor: dia.border_color || '#e0a800',
                    textColor: '#000',
                    extendedProps: {
                        type: 'especial',
                        dia: dia
                    }
                });
            });
        }
        
        return events;
    }
    
    /**
     * Obtener color de reserva según estado
     */
    function getReservaColor(estado) {
        const colors = {
            'confirmada': '#28a745',
            'pendiente': '#ffc107',
            'cancelada': '#dc3545',
            'completada': '#6c757d'
        };
        return colors[estado] || '#007bff';
    }
    
    function getReservaBorderColor(estado) {
        const colors = {
            'confirmada': '#1e7e34',
            'pendiente': '#e0a800',
            'cancelada': '#c82333',
            'completada': '#545b62'
        };
        return colors[estado] || '#0056b3';
    }
    
    /**
     * Manejar click en fecha
     */
    function handleDateClick(info) {
        selectedDate = info.dateStr;
        
        // Verificar si la fecha es seleccionable
        if (!isDateSelectable(info.date)) {
            showDateNotAvailable(info.date);
            return;
        }
        
        // Mostrar opciones para la fecha
        showDateOptions(info);
    }
    
    /**
     * Verificar si una fecha es seleccionable
     */
    function isDateSelectable(date) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // No permitir fechas pasadas
        if (date < today) {
            return false;
        }
        
        // Verificar si está en fechas disponibles
        const dateStr = date.toISOString().split('T')[0];
        return availableDates.includes(dateStr);
    }
    
    /**
     * Mostrar opciones para fecha seleccionada
     */
    function showDateOptions(info) {
        const modal = createDateOptionsModal(info);
        $('body').append(modal);
        modal.fadeIn();
        
        // Cargar horarios disponibles
        loadAvailableHorarios(info.dateStr);
    }
    
    /**
     * Crear modal de opciones de fecha
     */
    function createDateOptionsModal(info) {
        const dateFormatted = info.date.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        return $(`
            <div id="date-options-modal" class="rinac-modal">
                <div class="rinac-modal-content">
                    <span class="rinac-modal-close">&times;</span>
                    <h3>Opciones para ${dateFormatted}</h3>
                    <div id="date-options-content">
                        <div class="loading">Cargando horarios disponibles...</div>
                    </div>
                </div>
            </div>
        `);
    }
    
    /**
     * Cargar horarios disponibles para una fecha
     */
    function loadAvailableHorarios(fecha) {
        $.ajax({
            url: rinac_calendar.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_get_available_horarios',
                nonce: rinac_calendar.nonce,
                fecha: fecha,
                product_id: rinac_calendar.product_id
            },
            success: function(response) {
                if (response.success) {
                    displayAvailableHorarios(response.data);
                } else {
                    $('#date-options-content').html('<p class="error">No hay horarios disponibles</p>');
                }
            },
            error: function() {
                $('#date-options-content').html('<p class="error">Error al cargar horarios</p>');
            }
        });
    }
    
    /**
     * Mostrar horarios disponibles
     */
    function displayAvailableHorarios(horarios) {
        let html = '<div class="available-horarios">';
        
        if (horarios.length === 0) {
            html += '<p>No hay horarios disponibles para esta fecha.</p>';
        } else {
            html += '<h4>Horarios disponibles:</h4>';
            html += '<div class="horarios-grid">';
            
            horarios.forEach(function(horario) {
                const isAvailable = horario.disponibles > 0;
                const buttonClass = isAvailable ? 'horario-available' : 'horario-full';
                const disabled = isAvailable ? '' : 'disabled';
                
                html += `
                    <button class="horario-btn ${buttonClass}" 
                            data-hora="${horario.hora}" 
                            ${disabled}>
                        <span class="hora">${horario.hora}</span>
                        <span class="disponibles">${horario.disponibles} plazas</span>
                    </button>
                `;
            });
            
            html += '</div>';
            
            // Formulario de reserva rápida
            html += `
                <div class="quick-booking-form" style="display:none;">
                    <h4>Reserva rápida</h4>
                    <form id="quick-booking-form">
                        <input type="hidden" id="quick-fecha" value="${selectedDate}">
                        <input type="hidden" id="quick-horario" value="">
                        
                        <div class="form-group">
                            <label for="quick-personas">Número de personas:</label>
                            <select id="quick-personas" required>
                                ${generatePersonasOptions()}
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quick-telefono">Teléfono:</label>
                            <input type="tel" id="quick-telefono" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quick-comentarios">Comentarios (opcional):</label>
                            <textarea id="quick-comentarios" rows="3"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="button" onclick="closeModal()">Cancelar</button>
                            <button type="submit" class="button button-primary">Confirmar Reserva</button>
                        </div>
                    </form>
                </div>
            `;
        }
        
        html += '</div>';
        $('#date-options-content').html(html);
        
        // Inicializar eventos
        initDateOptionsEvents();
    }
    
    /**
     * Generar opciones de personas
     */
    function generatePersonasOptions() {
        const maxPersonas = parseInt(rinac_calendar.max_personas) || 10;
        let options = '';
        
        for (let i = 1; i <= maxPersonas; i++) {
            options += `<option value="${i}">${i} persona${i > 1 ? 's' : ''}</option>`;
        }
        
        return options;
    }
    
    /**
     * Inicializar eventos del modal de opciones
     */
    function initDateOptionsEvents() {
        // Cerrar modal
        $(document).on('click', '.rinac-modal-close, .rinac-modal', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Seleccionar horario
        $(document).on('click', '.horario-btn:not(:disabled)', function() {
            $('.horario-btn').removeClass('selected');
            $(this).addClass('selected');
            
            const hora = $(this).data('hora');
            $('#quick-horario').val(hora);
            $('.quick-booking-form').slideDown();
        });
        
        // Enviar formulario de reserva rápida
        $(document).on('submit', '#quick-booking-form', function(e) {
            e.preventDefault();
            processQuickBooking();
        });
    }
    
    /**
     * Procesar reserva rápida
     */
    function processQuickBooking() {
        const formData = {
            action: 'rinac_quick_booking',
            nonce: rinac_calendar.nonce,
            product_id: rinac_calendar.product_id,
            fecha: $('#quick-fecha').val(),
            horario: $('#quick-horario').val(),
            personas: $('#quick-personas').val(),
            telefono: $('#quick-telefono').val(),
            comentarios: $('#quick-comentarios').val()
        };
        
        // Deshabilitar botón
        $('#quick-booking-form button[type="submit"]')
            .prop('disabled', true)
            .text('Procesando...');
        
        $.ajax({
            url: rinac_calendar.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showBookingSuccess(response.data);
                    closeModal();
                    calendar.refetchEvents(); // Actualizar calendario
                } else {
                    showBookingError(response.data.message);
                }
            },
            error: function() {
                showBookingError('Error de conexión');
            },
            complete: function() {
                $('#quick-booking-form button[type="submit"]')
                    .prop('disabled', false)
                    .text('Confirmar Reserva');
            }
        });
    }
    
    /**
     * Manejar click en evento
     */
    function handleEventClick(info) {
        const event = info.event;
        const props = event.extendedProps;
        
        switch (props.type) {
            case 'reserva':
                showReservaDetails(props.reserva);
                break;
            case 'disponible':
                showSlotDetails(props.slot);
                break;
            case 'especial':
                showEspecialDetails(props.dia);
                break;
        }
    }
    
    /**
     * Mostrar detalles de reserva
     */
    function showReservaDetails(reserva) {
        const modal = $(`
            <div id="reserva-details-modal" class="rinac-modal">
                <div class="rinac-modal-content">
                    <span class="rinac-modal-close">&times;</span>
                    <h3>Detalles de Reserva #${reserva.id}</h3>
                    <div class="reserva-details">
                        <p><strong>Cliente:</strong> ${reserva.cliente_nombre}</p>
                        <p><strong>Teléfono:</strong> ${reserva.cliente_telefono}</p>
                        <p><strong>Fecha:</strong> ${formatDate(reserva.fecha)}</p>
                        <p><strong>Horario:</strong> ${reserva.hora_inicio} - ${reserva.hora_fin}</p>
                        <p><strong>Personas:</strong> ${reserva.personas}</p>
                        <p><strong>Estado:</strong> <span class="estado-${reserva.estado}">${reserva.estado}</span></p>
                        ${reserva.comentarios ? '<p><strong>Comentarios:</strong> ' + reserva.comentarios + '</p>' : ''}
                    </div>
                    <div class="reserva-actions">
                        <button class="button" onclick="closeModal()">Cerrar</button>
                        ${getReservaActionButtons(reserva)}
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn();
    }
    
    /**
     * Obtener botones de acción para reserva
     */
    function getReservaActionButtons(reserva) {
        let buttons = '';
        
        if (reserva.estado === 'pendiente') {
            buttons += '<button class="button button-primary" onclick="confirmarReserva(' + reserva.id + ')">Confirmar</button>';
            buttons += '<button class="button button-secondary" onclick="cancelarReserva(' + reserva.id + ')">Cancelar</button>';
        } else if (reserva.estado === 'confirmada') {
            buttons += '<button class="button button-secondary" onclick="cancelarReserva(' + reserva.id + ')">Cancelar</button>';
            buttons += '<button class="button" onclick="editarReserva(' + reserva.id + ')">Editar</button>';
        }
        
        return buttons;
    }
    
    /**
     * Personalizar visualización de eventos
     */
    function customizeEventDisplay(info) {
        const event = info.event;
        const props = event.extendedProps;
        
        // Agregar icono según tipo
        let icon = '';
        switch (props.type) {
            case 'reserva':
                icon = '📅';
                break;
            case 'disponible':
                icon = '✅';
                break;
            case 'especial':
                icon = '⭐';
                break;
        }
        
        if (icon) {
            const iconElement = document.createElement('span');
            iconElement.innerHTML = icon + ' ';
            iconElement.className = 'event-icon';
            info.el.querySelector('.fc-event-title').prepend(iconElement);
        }
        
        // Agregar tooltip
        $(info.el).tooltip({
            title: generateEventTooltip(event),
            placement: 'top',
            html: true
        });
    }
    
    /**
     * Generar tooltip para evento
     */
    function generateEventTooltip(event) {
        const props = event.extendedProps;
        
        switch (props.type) {
            case 'reserva':
                return `
                    <strong>Reserva #${props.reserva.id}</strong><br>
                    Cliente: ${props.reserva.cliente_nombre}<br>
                    Personas: ${props.reserva.personas}<br>
                    Estado: ${props.reserva.estado}
                `;
            case 'disponible':
                return `
                    <strong>Horario disponible</strong><br>
                    Plazas libres: ${props.slot.disponible}<br>
                    Hora: ${props.slot.hora}
                `;
            case 'especial':
                return `
                    <strong>${props.dia.nombre}</strong><br>
                    ${props.dia.descripcion || 'Día especial'}
                `;
            default:
                return event.title;
        }
    }
    
    /**
     * Personalizar celdas de día
     */
    function customizeDayCell(info) {
        const date = info.date;
        const dateStr = date.toISOString().split('T')[0];
        
        // Marcar días con disponibilidad
        if (availableDates.includes(dateStr)) {
            info.el.classList.add('available-date');
        }
        
        // Marcar fin de semana
        if (date.getDay() === 0 || date.getDay() === 6) {
            info.el.classList.add('weekend');
        }
        
        // Marcar día actual
        const today = new Date();
        if (date.toDateString() === today.toDateString()) {
            info.el.classList.add('today');
        }
    }
    
    /**
     * Inicializar eventos del calendario
     */
    function initCalendarEvents() {
        // Botón de actualizar
        $('.calendar-refresh').on('click', function() {
            calendar.refetchEvents();
        });
        
        // Filtros de vista
        $('.calendar-view-filter').on('change', function() {
            const view = $(this).val();
            calendar.changeView(view);
        });
        
        // Filtros de estado
        $('.calendar-status-filter').on('change', function() {
            filterEventsByStatus($(this).val());
        });
        
        // Navegación rápida
        $('.quick-nav-today').on('click', function() {
            calendar.today();
        });
        
        $('.quick-nav-prev').on('click', function() {
            calendar.prev();
        });
        
        $('.quick-nav-next').on('click', function() {
            calendar.next();
        });
    }
    
    /**
     * Inicializar filtros del calendario
     */
    function initCalendarFilters() {
        // Filtro por producto
        $('#calendar-product-filter').on('change', function() {
            const productId = $(this).val();
            updateCalendarData({ product_id: productId });
        });
        
        // Filtro por estado de reserva
        $('#calendar-status-filter').on('change', function() {
            const status = $(this).val();
            filterEventsByStatus(status);
        });
        
        // Filtro por rango de fechas
        $('#calendar-date-range').on('change', function() {
            const range = $(this).val();
            navigateToDateRange(range);
        });
    }
    
    /**
     * Filtrar eventos por estado
     */
    function filterEventsByStatus(status) {
        const events = calendar.getEvents();
        
        events.forEach(function(event) {
            const props = event.extendedProps;
            
            if (status === '' || (props.type === 'reserva' && props.reserva.estado === status)) {
                event.setProp('display', 'auto');
            } else if (props.type === 'reserva') {
                event.setProp('display', 'none');
            }
        });
    }
    
    /**
     * Navegar a rango de fechas
     */
    function navigateToDateRange(range) {
        const today = new Date();
        let targetDate;
        
        switch (range) {
            case 'week':
                targetDate = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
                break;
            case 'month':
                targetDate = new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000);
                break;
            case 'quarter':
                targetDate = new Date(today.getTime() + 90 * 24 * 60 * 60 * 1000);
                break;
            default:
                targetDate = today;
        }
        
        calendar.gotoDate(targetDate);
    }
    
    /**
     * Actualizar datos del calendario
     */
    function updateCalendarData(params) {
        Object.assign(rinac_calendar, params);
        calendar.refetchEvents();
    }
    
    /**
     * Funciones de utilidad
     */
    function closeModal() {
        $('.rinac-modal').fadeOut(function() {
            $(this).remove();
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    function showDateNotAvailable(date) {
        const message = 'La fecha ' + formatDate(date) + ' no está disponible para reservas.';
        showNotification(message, 'warning');
    }
    
    function showBookingSuccess(data) {
        const message = 'Reserva confirmada para el ' + formatDate(data.fecha) + ' a las ' + data.horario;
        showNotification(message, 'success');
    }
    
    function showBookingError(message) {
        showNotification(message, 'error');
    }
    
    function showNotification(message, type) {
        const notification = $(`
            <div class="rinac-notification rinac-notification-${type}">
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 5000);
    }
    
    /**
     * Acciones de reserva (para usar en modales)
     */
    window.confirmarReserva = function(reservaId) {
        updateReservaStatus(reservaId, 'confirmada');
    };
    
    window.cancelarReserva = function(reservaId) {
        updateReservaStatus(reservaId, 'cancelada');
    };
    
    window.editarReserva = function(reservaId) {
        // Redirigir a página de edición o abrir modal de edición
        window.location.href = rinac_calendar.edit_url + '&reserva_id=' + reservaId;
    };
    
    function updateReservaStatus(reservaId, nuevoEstado) {
        $.ajax({
            url: rinac_calendar.ajax_url,
            type: 'POST',
            data: {
                action: 'rinac_update_reserva_status',
                nonce: rinac_calendar.nonce,
                reserva_id: reservaId,
                estado: nuevoEstado
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Estado de reserva actualizado', 'success');
                    closeModal();
                    calendar.refetchEvents();
                } else {
                    showNotification(response.data.message || 'Error al actualizar reserva', 'error');
                }
            },
            error: function() {
                showNotification('Error de conexión', 'error');
            }
        });
    }
});
