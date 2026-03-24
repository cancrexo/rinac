## PLAN-NOVO-checklist (RINAC)

IMPORTANTE: cada vez que se modifique el plan de trabajo, hay que actualizar también este archivo `PLAN-NOVO-checklist.md` y `GROK.md` para mantenerlos sincronizados.

### Definición oficial de Slot (referencia rápida)

- [ ] `slot` es la única entidad temporal/operativa para reservar.
- [ ] Un `slot` puede ser etiqueta de servicio (`comida`, `cena`) o franja horaria (`12:00-12:30`).
- [ ] `turno` se entiende como alias/sinónimo de `slot`.
- [ ] Disponibilidad/capacidad se calculan sobre `slot`.

### Pasos del desarrollo (checklist)

1. [ ] Configuración inicial: `composer.json`, `rinac.php`, PSR-4, activación/desactivación, registro `rinac_reserva`, registro CPTs + menú “RINAC” + datos mínimos demo en activación
2. [ ] Clase `AjaxHandler` centralizada + ejemplo de 3 endpoints (`rinac_get_availability`, `rinac_get_calendar_events`, `rinac_create_booking_request`)
3. [ ] Meta boxes y settings para productos `rinac_reserva`
4. [ ] Lógica de disponibilidad y cálculo de capacidad (clase `AvailabilityManager`)
5. [ ] Gestión de recursos y participantes
6. [ ] Sistema de pago depósito + hooks de WooCommerce
7. [ ] Calendario global admin + listado de reservas + botón “Importar datos de prueba”
8. [ ] Control de concurrencia y bloqueos temporales (quote/hold antes de confirmar reserva)
9. [ ] Frontend booking form + FullCalendar integración
10. [ ] Templates y overrides
11. [ ] Documentación completa (`README.md` + inline docs)

### Casos de uso incluidos (alcance funcional)

- [ ] Bodega: reserva por día/slot con capacidad por franja.
- [ ] Restaurante (opción 1): reservas por slot y día (comida/cena o franja horaria).
- [ ] Restaurante (opción 2): reservas por mesa/unidad.
- [ ] Alquiler de coches: reservas por día o rango de fechas.
- [ ] Alquiler de habitaciones: reservas por día o rango de fechas.
- [ ] Interfaz de administración unificada para todos los productos reservables.

### Cambios de diseño acordados

- [ ] Añadir `rinac_booking_mode` para soportar múltiples modos de reserva por producto.
- [ ] Separar capacidad global del producto y capacidad por slot/unidad.
- [ ] Formalizar tipos de participante con fracción de capacidad y reglas de precio.
- [ ] Formalizar recursos como `addon` o `unit` con política de precio.
- [ ] Mantener los casos de uso como orientación funcional (no cerrados), sin `rinac_business_profile`.
- [ ] Introducir flujo quote/hold para reducir sobreserva en concurrencia.
- [ ] Mantener CPT para gestión y prever tabla técnica para consultas intensivas de disponibilidad.
- [ ] Aclaración de calendario: cada producto tiene calendario/configuración propios y el calendario global es una vista agregada.

### Pendiente específico de Slots (estado actual)

- [ ] Añadir metadatos editables en `rinac_slot` (inicio, fin/etiqueta, capacidad máxima, capacidad mínima opcional, prioridad y estado activo).
- [ ] Implementar validaciones de slot (coherencia de datos, solapes cuando aplique y compatibilidad con `rinac_booking_mode`).
- [ ] Formalizar contrato de datos de slot (meta keys y acceso centralizado).
- [ ] Completar cálculo de disponibilidad por `slot_id` para todos los modos con precedencia global/slot.
- [ ] Exponer en AJAX los slots realmente disponibles por fecha.
- [ ] Implementar selector frontend de slots con estados (disponible/completo/no permitido).
- [ ] Mejorar listado admin de slots con columnas/filtros de operación.
- [ ] Reforzar concurrencia por `slot_id` en flujo `quote/hold`.
- [ ] Añadir tests (capacidad, solapes y bloqueos por slot) y documentación operativa.

### Priorización recomendada de ejecución (Slots)

- [ ] Fase 1: Meta boxes + validaciones de `rinac_slot`.
- [ ] Fase 2: Endpoints con respuesta real de slots disponibles.
- [ ] Fase 3: Selector frontend de slots.
- [ ] Fase 4: Tests de concurrencia y regresión.

### Pendiente específico de Tipos de participante (estado actual)

- [ ] Añadir metadatos editables en `rinac_participant` (etiqueta pública, fracción, tipo/valor de precio, mínimos/máximos, activo, orden).
- [ ] Implementar validaciones de guardado (fracción > 0, `price_type` válido, `price_value` >= 0, coherencia min/max).
- [ ] Validar en `quote/create booking` que el tipo exista, esté activo y permitido para el producto.
- [ ] Aplicar límites por tipo y validación de capacidad global derivada de fracciones.
- [ ] Formalizar cálculo de precio por estrategia (`free`, `fixed`, extensible).
- [ ] Exponer en AJAX tipos permitidos con reglas (fracción/precio/límites/estado).
- [ ] Implementar UI frontend de cantidades por tipo y recálculo en vivo.
- [ ] Mostrar errores de negocio claros (no permitido, inactivo, límites, capacidad insuficiente).
- [ ] Añadir tests unitarios/integración para normalización, capacidad, precio y validaciones.

### Priorización recomendada de ejecución (Tipos de participante)

- [ ] Fase 1: Meta boxes + validaciones de `rinac_participant`.
- [ ] Fase 2: Validación estricta en `quote/create booking`.
- [ ] Fase 3: Endpoint/frontend con selector y cálculo en vivo.
- [ ] Fase 4: Tests y hardening final.

### Reglas clave (recordatorio)
- [ ] Congelar contrato de meta keys de `rinac_slot`, `rinac_participant` y `rinac_resource`.
- [ ] Cerrar convención de naming: mantener `_rinac_pt_*` (participantes), `_rinac_slot_*` (slots), `_rinac_resource_*` (recursos), con excepción `_rinac_capacity_max/_rinac_capacity_min` en slot.
- [ ] Implementar `_rinac_slot_label`, `_rinac_slot_start_time`, `_rinac_slot_end_time`, `_rinac_capacity_max`, `_rinac_capacity_min`, `_rinac_slot_is_active`, `_rinac_slot_sort_order`.
- [ ] Implementar `_rinac_pt_label`, `_rinac_pt_capacity_fraction`, `_rinac_pt_price_type`, `_rinac_pt_price_value`, `_rinac_pt_min_qty`, `_rinac_pt_max_qty`, `_rinac_pt_is_active`, `_rinac_pt_sort_order`.
- [ ] Implementar `_rinac_resource_label`, `_rinac_resource_type`, `_rinac_resource_price_policy`, `_rinac_resource_price_value`, `_rinac_resource_min_qty`, `_rinac_resource_max_qty`, `_rinac_resource_is_active`, `_rinac_resource_sort_order`.
- [ ] Validar de forma estricta que quote/create booking solo acepte IDs activos y permitidos por `_rinac_allowed_slots`, `_rinac_allowed_participant_types` y `_rinac_allowed_resources`.
- `text_domain` cargado **exclusivamente** dentro de `init` (nunca antes).
- Callbacks pasados a `add_menu_page`/`add_submenu_page` y a `add_action`/`add_filter` deben ser `public` si se pasan como `array($obj, 'metodo')` (evitar `private/protected`).
- Si un lint se queja de `$_POST`/`$_GET`/`$_REQUEST`: no usar `$GLOBALS`, copiar primero a variables locales con validación de tipo (y opcionalmente `@noinspection`).
- Stubs de WordPress y WooCommerce: instalar como `require-dev` en `vendor/` (no versionar).
- Desde el inicio del proyecto: ejecutar `composer install` y añadir stubs con `composer require --dev php-stubs/wordpress-stubs php-stubs/woocommerce-stubs`.

