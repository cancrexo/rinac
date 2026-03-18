/**
 * Cliente AJAX centralizado para RINAC.
 *
 * - Soporta lectura/escritura (rinac_ajax_read / rinac_ajax_write)
 * - Permite callbacks de éxito y error
 * - Maneja errores con try/catch (red, parseo, etc.)
 */
(function(window, $) {
    'use strict';

    function normalizeError(err) {
        if (err && err.message) {
            return err;
        }

        return {
            message: (typeof err === 'string' && err) ? err : 'Error desconocido'
        };
    }

    function buildRequestData(action, op, payload, nonce) {
        return {
            action: action,
            op: op,
            payload: payload || {},
            nonce: nonce || ''
        };
    }

    /**
     * Realiza una petición AJAX al router central.
     *
     * @param {Object} options
     * @param {Object} options.context Objeto localizado (ej: rinac_admin o rinac_frontend)
     * @param {String} options.mode 'read'|'write'
     * @param {String} options.op Operación (ej: 'get_horarios')
     * @param {Object} options.payload Datos de entrada
     * @param {Function} options.onSuccess Callback cuando success=true
     * @param {Function} options.onError Callback cuando success=false o error de red
     */
    function request(options) {
        var ctx = options && options.context ? options.context : {};
        var mode = options && options.mode ? options.mode : 'read';
        var op = options && options.op ? options.op : '';
        var payload = options && options.payload ? options.payload : {};
        var onSuccess = options && typeof options.onSuccess === 'function' ? options.onSuccess : function() {};
        var onError = options && typeof options.onError === 'function' ? options.onError : function() {};

        try {
            if (!ctx.ajax_url) {
                throw new Error('Falta ajax_url en el contexto');
            }
            if (!ctx.nonce) {
                throw new Error('Falta nonce en el contexto');
            }
            if (!op) {
                throw new Error('Falta op');
            }

            var action = (mode === 'write') ? 'rinac_ajax_write' : 'rinac_ajax_read';
            var data = buildRequestData(action, op, payload, ctx.nonce);

            return $.ajax({
                url: ctx.ajax_url,
                type: 'POST',
                data: data
            }).done(function(response) {
                try {
                    if (response && response.success) {
                        onSuccess(response.data, response);
                        return;
                    }

                    var err = (response && response.data) ? response.data : { message: 'Error desconocido' };
                    onError(normalizeError(err), response);
                } catch (e) {
                    onError(normalizeError(e), response);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                var err = {
                    message: errorThrown || textStatus || 'Error de conexión'
                };
                onError(normalizeError(err), null);
            });
        } catch (e) {
            onError(normalizeError(e), null);
            return $.Deferred().reject(e).promise();
        }
    }

    window.RinacAjax = window.RinacAjax || {};
    window.RinacAjax.request = request;
    window.RinacAjax.read = function(context, op, payload, onSuccess, onError) {
        return request({
            context: context,
            mode: 'read',
            op: op,
            payload: payload,
            onSuccess: onSuccess,
            onError: onError
        });
    };
    window.RinacAjax.write = function(context, op, payload, onSuccess, onError) {
        return request({
            context: context,
            mode: 'write',
            op: op,
            payload: payload,
            onSuccess: onSuccess,
            onError: onError
        });
    };
})(window, jQuery);

