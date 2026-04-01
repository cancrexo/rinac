(function () {
    "use strict";

    if (typeof window.rinacBookingConfig === "undefined") {
        return;
    }

    var config = window.rinacBookingConfig;
    var root = document.getElementById("rinac-booking-form");
    if (!root) {
        return;
    }

    var participantsList = document.getElementById("rinac-participants-list");
    var resourcesList = document.getElementById("rinac-resources-list");
    var errorsBox = document.getElementById("rinac-booking-errors");
    var summaryBox = document.getElementById("rinac-booking-summary");
    var validateButton = document.getElementById("rinac-validate-booking");

    function postAjax(payload) {
        var body = new URLSearchParams(payload);
        return fetch(config.ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: body.toString()
        }).then(function (response) {
            return response.json();
        });
    }

    function toInt(value, fallback) {
        var parsed = parseInt(value, 10);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function renderRules(listEl, rows, type) {
        var html = "";
        rows.forEach(function (row) {
            var min = toInt(row.min_qty, 0);
            var max = toInt(row.max_qty, 99);
            var label = row.label || ("#" + row.id);
            var meta = type === "participant"
                ? ("fracción " + row.capacity_fraction + " | " + row.price_type + ": " + row.price_value)
                : (row.resource_type + " | " + row.price_policy + ": " + row.price_value);

            html += "<div class='rinac-line'>";
            html += "<label>" + label + "</label>";
            html += "<small>" + meta + "</small>";
            html += "<input type='number' min='" + min + "' " + (max > 0 ? "max='" + max + "'" : "") + " step='1' value='0' data-kind='" + type + "' data-id='" + row.id + "' data-min='" + min + "' data-max='" + max + "' />";
            html += "</div>";
        });
        listEl.innerHTML = html || "<em>" + config.i18n.emptyRules + "</em>";
    }

    function collectLines(kind) {
        var nodes = root.querySelectorAll("input[data-kind='" + kind + "']");
        var lines = [];
        nodes.forEach(function (node) {
            var id = toInt(node.getAttribute("data-id"), 0);
            var qty = toInt(node.value, 0);
            if (id > 0 && qty > 0) {
                lines.push({ id: id, qty: qty });
            }
        });
        return lines;
    }

    function renderErrors(errors, errorMessages) {
        if (!errorsBox) {
            return;
        }
        if ((!errors || !errors.length) && (!errorMessages || !errorMessages.length)) {
            errorsBox.innerHTML = "";
            return;
        }

        var lines = [];
        if (errors && errors.length) {
            errors.forEach(function (error) {
                var text = (error.code ? "[" + error.code + "] " : "") + (error.message || "");
                lines.push("<li>" + text + "</li>");
            });
        } else {
            errorMessages.forEach(function (message) {
                lines.push("<li>" + message + "</li>");
            });
        }
        errorsBox.innerHTML = "<ul>" + lines.join("") + "</ul>";
    }

    function renderSummary(payload) {
        if (!summaryBox || !payload) {
            return;
        }
        var pricing = payload.pricing || {};
        var capacity = payload.capacity || {};
        summaryBox.innerHTML =
            "<p><strong>" + config.i18n.estimatedTotal + ":</strong> " + (pricing.total_estimated || 0) + "</p>" +
            "<p><strong>" + config.i18n.remainingCapacity + ":</strong> " + (capacity.remaining_capacity_after || 0) + "</p>";
    }

    function validateSelection() {
        if (validateButton) {
            validateButton.disabled = true;
        }
        var payload = {
            action: "rinac_create_booking_request",
            nonce: config.nonce,
            product_id: config.productId,
            start: (document.getElementById("rinac_start") || {}).value || "",
            end: (document.getElementById("rinac_end") || {}).value || "",
            days: toInt((document.getElementById("rinac_days") || {}).value, 1),
            nights: toInt((document.getElementById("rinac_nights") || {}).value, 1)
        };

        collectLines("participant").forEach(function (line, idx) {
            payload["participants[" + idx + "][id]"] = line.id;
            payload["participants[" + idx + "][qty]"] = line.qty;
        });

        collectLines("resource").forEach(function (line, idx) {
            payload["resources[" + idx + "][id]"] = line.id;
            payload["resources[" + idx + "][qty]"] = line.qty;
        });

        return postAjax(payload)
            .then(function (json) {
                if (!json || !json.success) {
                    var data = json && json.data ? json.data : {};
                    renderErrors(data.errors || [], data.error_messages || []);
                    summaryBox.innerHTML = "";
                    return;
                }
                renderErrors([], []);
                renderSummary(json.data && json.data.payload ? json.data.payload : null);
            })
            .catch(function () {
                renderErrors([{ code: "request_error", message: config.i18n.requestError }], []);
            })
            .finally(function () {
                if (validateButton) {
                    validateButton.disabled = false;
                }
            });
    }

    function boot() {
        if (participantsList) {
            participantsList.textContent = config.i18n.loading;
        }
        if (resourcesList) {
            resourcesList.textContent = config.i18n.loading;
        }

        Promise.all([
            postAjax({
                action: "rinac_get_allowed_participants",
                nonce: config.nonce,
                product_id: config.productId
            }),
            postAjax({
                action: "rinac_get_allowed_resources",
                nonce: config.nonce,
                product_id: config.productId
            })
        ]).then(function (responses) {
            var pRows = responses[0] && responses[0].success && responses[0].data ? (responses[0].data.items || []) : [];
            var rRows = responses[1] && responses[1].success && responses[1].data ? (responses[1].data.items || []) : [];
            renderRules(participantsList, pRows, "participant");
            renderRules(resourcesList, rRows, "resource");
        }).catch(function () {
            renderErrors([{ code: "load_rules_error", message: config.i18n.requestError }], []);
        });

        root.addEventListener("change", function (event) {
            var target = event.target;
            if (!target || !target.matches("input[data-kind], #rinac_start, #rinac_end, #rinac_days, #rinac_nights")) {
                return;
            }
            validateSelection();
        });

        if (validateButton) {
            validateButton.addEventListener("click", function () {
                validateSelection();
            });
        }
    }

    boot();
})();
