#!/usr/bin/env bash
set -euo pipefail

# Test manual de capacidad por slot en 3 llamadas.
# Objetivo:
#   - 1ª quote -> OK
#   - 2ª quote -> OK
#   - 3ª quote -> ERROR por capacidad insuficiente (slot lleno)
#
# Requisitos previos en admin:
#   - Producto rinac_reserva con:
#       - modo reserva: slot_dia (o equivalente con slots)
#       - capacidad base: >= 2
#       - capacidad global máxima: 0 (o >=2)
#       - capacidad mínima por reserva: 1
#   - Slot asociado al producto con:
#       - capacidad máxima: 2
#       - activo: sí
#
# Modo de pago para esta prueba:
#   - recomendado: full
#   - depósito: 0
#
# Uso:
#   BASE_URL="https://woo.lan" \
#   PRODUCT_ID="166" \
#   SLOT_ID="301" \
#   START_DATE="2026-04-10" \
#   END_DATE="2026-04-10" \
#   NONCE="tu_nonce_rinac_ajax" \
#   ./scripts/test-slot-capacidad.sh
#
# Nota:
#   NONCE debe ser válido para acción rinac_ajax.

BASE_URL="${BASE_URL:-}"
PRODUCT_ID="${PRODUCT_ID:-}"
SLOT_ID="${SLOT_ID:-}"
START_DATE="${START_DATE:-}"
END_DATE="${END_DATE:-}"
NONCE="${NONCE:-}"

if [[ -z "$BASE_URL" || -z "$PRODUCT_ID" || -z "$SLOT_ID" || -z "$START_DATE" || -z "$END_DATE" || -z "$NONCE" ]]; then
    echo "Faltan variables obligatorias."
    echo "Necesitas: BASE_URL, PRODUCT_ID, SLOT_ID, START_DATE, END_DATE, NONCE"
    exit 1
fi

AJAX_URL="${BASE_URL%/}/wp-admin/admin-ajax.php"

quote_once() {
    local label="$1"
    echo
    echo "=== ${label} ==="
    curl -sS -k -X POST "$AJAX_URL" \
        --data-urlencode "action=rinac_quote_booking" \
        --data-urlencode "nonce=${NONCE}" \
        --data-urlencode "product_id=${PRODUCT_ID}" \
        --data-urlencode "slot_id=${SLOT_ID}" \
        --data-urlencode "start=${START_DATE}" \
        --data-urlencode "end=${END_DATE}" \
        --data-urlencode "days=1" \
        --data-urlencode "nights=1" \
        --data-urlencode "participants[0][id]=0" \
        --data-urlencode "participants[0][qty]=0"
}

check_availability() {
    echo
    echo "=== Availability ==="
    curl -sS -k -X POST "$AJAX_URL" \
        --data-urlencode "action=rinac_get_availability" \
        --data-urlencode "nonce=${NONCE}" \
        --data-urlencode "product_id=${PRODUCT_ID}" \
        --data-urlencode "slot_id=${SLOT_ID}" \
        --data-urlencode "start=${START_DATE}" \
        --data-urlencode "end=${END_DATE}"
}

echo "Iniciando test de capacidad por slot..."
echo "Producto: ${PRODUCT_ID} | Slot: ${SLOT_ID} | Rango: ${START_DATE} -> ${END_DATE}"

check_availability
quote_once "Quote #1 (esperado: success)"
quote_once "Quote #2 (esperado: success)"
quote_once "Quote #3 (esperado: error capacidad)"
check_availability

echo
echo "Test finalizado."
