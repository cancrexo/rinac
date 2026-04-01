## PLAN-NOVO-checklist (RINAC)

IMPORTANTE: cada vez que se modifique el plan de trabajo, hay que actualizar también este archivo `PLAN-NOVO-checklist.md` y `GROK.md` para mantenerlos sincronizados.

### Pasos del desarrollo (checklist)

1. [x] Configuración inicial: `composer.json`, `rinac.php`, PSR-4, activación/desactivación, registro `rinac_reserva`, registro CPTs + menú “RINAC” + datos mínimos demo en activación
2. [x] Clase `AjaxHandler` centralizada + ejemplo de 3 endpoints (`rinac_get_availability`, `rinac_get_calendar_events`, `rinac_create_booking_request`)
3. [x] Pestañas `woocommerce-product-data` y settings para productos `rinac_reserva`
4. [x] Lógica de disponibilidad y cálculo de capacidad (clase `AvailabilityManager`)
5. [x] Gestión de recursos y participantes (`ParticipantManager`, `ResourceManager`, `BookingManager`)
6. [x] Sistema de pago con depósito + hooks de WooCommerce (`DepositManager`)
7. [x] Calendario global admin + listado de reservas + botón “Importar datos de prueba”
8. [x] Control de concurrencia y bloqueos temporales (quote/hold antes de confirmar reserva)
9. [ ] Frontend booking form + FullCalendar integración
10. [ ] Templates y overrides
11. [ ] Documentación completa (`README.md` + inline docs)

### Tareas activas por fase (detalle operativo)

#### Gate backend completo (obligatorio antes de frontend)
- [x] Cerrar contrato canónico de `rinac_booking` (meta keys obligatorias y normalización en todos los flujos: quote/hold/create/order/admin import).
- [x] Endurecer idempotencia y anti-carreras en confirmación de `hold_token` (sin doble consumo de capacidad).
- [x] Completar matriz de transición de estados `pedido -> reserva` (incluyendo cancelaciones, fallos, reembolsos parciales/totales y reintentos).
- [x] Garantizar coherencia de capacidad global/slot en todos los `booking_mode` soportados.
- [x] Formalizar e implementar política de limpieza de holds (cron + lazy cleanup) con TTL configurable.
- [x] Asegurar invalidación de caché de disponibilidad ante todos los eventos críticos (confirmación/cancelación/expiración/edición relevante).
- [x] Definir contrato final de endpoints backend (`availability`, `quote`, `create`) con payloads y errores estables.
- [x] Diferenciar explícitamente `cart_hold` vs `order_hold` con meta `_rinac_hold_scope`.
- [x] Implementar expiración deslizante para `cart_hold` (refresh por actividad con límite temporal máximo).
- [x] Implementar expiración de `order_hold` dependiente de estado/método de pago del pedido.
- [x] Mantener liberación de capacidad por expiración/cancelación basándose en `_rinac_booking_status`.
- [x] Añadir tests backend mínimos obligatorios:
  - [x] unit: `HoldManager` (create/get/confirm/expire/idempotencia),
  - [x] unit: `DepositManager` (full/deposit y persistencia),
  - [x] unit: `AvailabilityManager` (ocupación con holds activos/expirados/cancelados),
  - [x] integración: flujo `quote -> hold -> confirm -> order status -> capacity`.
- [x] No iniciar desarrollo frontend de Paso 9 hasta completar todos los puntos anteriores.

#### Fase Slots
- [ ] Añadir metadatos editables en `rinac_slot` (inicio, fin/etiqueta, capacidad máxima, capacidad mínima opcional, prioridad y estado activo).
- [ ] Implementar validaciones de slot (coherencia de datos, solapes cuando aplique y compatibilidad con `rinac_booking_mode`).
- [ ] Formalizar contrato de datos de slot (meta keys y acceso centralizado).
- [ ] Completar cálculo de disponibilidad por `slot_id` para todos los modos con precedencia global/slot.
- [ ] Exponer en AJAX los slots realmente disponibles por fecha.
- [ ] Implementar selector frontend de slots con estados (disponible/completo/no permitido).
- [ ] Mejorar listado admin de slots con columnas/filtros de operación.
- [ ] Reforzar concurrencia por `slot_id` en flujo `quote/hold`.
- [ ] Añadir tests (capacidad, solapes y bloqueos por slot) y documentación operativa.

#### Fase Tipos de participante
- [x] Añadir metadatos editables en `rinac_participant` (etiqueta pública, fracción, tipo/valor de precio, mínimos/máximos, activo, orden).
- [x] Implementar validaciones de guardado (fracción > 0, `price_type` válido, `price_value` >= 0, coherencia min/max).
- [x] Validar en `quote/create booking` que el tipo exista, esté activo y permitido para el producto.
- [x] Aplicar límites por tipo y validación de capacidad global derivada de fracciones.
- [x] Formalizar cálculo de precio por estrategia (`free`, `fixed`, extensible).
- [x] Exponer en AJAX tipos permitidos con reglas (fracción/precio/límites/estado).
- [x] Formalizar contrato backend de errores de negocio (`code`, `message`, `context`) y exponerlo en `rinac_create_booking_request`.
- [x] Implementar UI frontend de cantidades por tipo y recálculo en vivo.
- [x] Mostrar errores de negocio claros (no permitido, inactivo, límites, capacidad insuficiente).
- [x] Añadir tests unitarios/integración para normalización, capacidad, precio y validaciones.

#### Fase Recursos
- [x] Añadir metadatos editables en `rinac_resource` (tipo `addon/unit`, política de precio, valor, activo, orden y límites opcionales).
- [x] Implementar validaciones de guardado (`resource_type` válido, `price_policy` válida, `price_value` >= 0 y coherencia de límites).
- [x] Validar en `quote/create booking` que el recurso exista, esté activo y permitido para el producto.
- [x] Aplicar límites por recurso y reglas por modo/perfil cuando aplique.
- [x] Formalizar cálculo de precio por política (`none`, `fixed`, `per_person`, `per_day`, `per_night`).
- [x] Exponer en AJAX recursos permitidos con reglas (tipo/política/precio/límites/estado).
- [x] Formalizar contrato backend de errores de negocio (`code`, `message`, `context`) y exponerlo en `rinac_create_booking_request`.
- [x] Implementar UI frontend de selección de recursos (simple/cantidad) con recálculo en vivo.
- [x] Mostrar errores de negocio claros (no permitido, inactivo, límites, incompatibilidades).
- [x] Añadir tests unitarios/integración para normalización, validaciones y cálculo por política.

### Decisiones de diseño confirmadas (sin checkbox)
- `slot` es la entidad temporal/operativa de reserva.
- `turno` se entiende como alias/sinónimo de `slot`.
- Un `slot` puede ser etiqueta de servicio (`comida`, `cena`) o franja horaria.
- Los casos de uso son orientación funcional (no cerrados), sin `rinac_business_profile`.
- Mantener `_rinac_pt_*` (participantes), `_rinac_slot_*` (slots), `_rinac_resource_*` (recursos), con excepción `_rinac_capacity_max/_rinac_capacity_min` en slot.
- Validar en quote/create booking solo IDs activos y permitidos por `_rinac_allowed_slots`, `_rinac_allowed_participant_types` y `_rinac_allowed_resources`.

### Reglas permanentes (sin checkbox)
- `text_domain` cargado **exclusivamente** dentro de `init` (nunca antes).
- Callbacks pasados a `add_menu_page`/`add_submenu_page` y a `add_action`/`add_filter` deben ser `public` si se pasan como `array($obj, 'metodo')` (evitar `private/protected`).
- Si un lint se queja de `$_POST`/`$_GET`/`$_REQUEST`: no usar `$GLOBALS`, copiar primero a variables locales con validación de tipo (y opcionalmente `@noinspection`).
- Stubs de WordPress y WooCommerce: instalar como `require-dev` en `vendor/` (no versionar).
- Desde el inicio del proyecto: ejecutar `composer install` y añadir stubs con `composer require --dev php-stubs/wordpress-stubs php-stubs/woocommerce-stubs`.

### Referencia funcional
- Ver casos de uso y alcance funcional en `PLAN-NOVO.md` (sección de casos de uso).

### Comandos de validación manual (operativos)

> Nota: sustituir IDs de ejemplo (`product_id`, `slot_id`, `participant_id`, `BOOKING_ID`) por los del entorno actual.

1) Crear nonce válido para AJAX RINAC:
```bash
../tools/wp eval 'echo wp_create_nonce("rinac_ajax"), PHP_EOL;'
```

2) Comprobar disponibilidad:
```bash
curl -k -X POST "https://woo.lan/wp-admin/admin-ajax.php" \
  -d "action=rinac_get_availability" \
  -d "nonce=<NONCE>" \
  -d "product_id=182" \
  -d "slot_id=164" \
  -d "start=2026-04-10" \
  -d "end=2026-04-10"
```

3) Crear quote/hold:
```bash
curl -k -X POST "https://woo.lan/wp-admin/admin-ajax.php" \
  -d "action=rinac_quote_booking" \
  -d "nonce=<NONCE>" \
  -d "product_id=182" \
  -d "slot_id=164" \
  -d "start=2026-04-10" \
  -d "end=2026-04-10" \
  -d "participants[0][id]=167" \
  -d "participants[0][qty]=1"
```

4) Liberar un hold concreto:
```bash
../tools/wp post meta update <BOOKING_ID> _rinac_booking_status expired --path=/home/tecnico/webproyects/woo.lan/httpdocs --url=woo.lan
../tools/wp post update <BOOKING_ID> --post_status=draft --path=/home/tecnico/webproyects/woo.lan/httpdocs --url=woo.lan
```

5) Liberar todos los holds de un producto:
```bash
WP="../tools/wp --path=/home/tecnico/webproyects/woo.lan/httpdocs --url=woo.lan"
PRODUCT_ID=182
for ID in $($WP post list --post_type=rinac_booking --post_status=any --field=ID); do
  PID=$($WP post meta get "$ID" _rinac_booking_product_id --format=plaintext 2>/dev/null || echo "")
  BST=$($WP post meta get "$ID" _rinac_booking_status --format=plaintext 2>/dev/null || echo "")
  if [ "$PID" = "$PRODUCT_ID" ] && [ "$BST" = "hold" ]; then
    $WP post meta update "$ID" _rinac_booking_status expired >/dev/null
    $WP post update "$ID" --post_status=draft >/dev/null
    echo "Hold liberado: $ID"
  fi
done
```

