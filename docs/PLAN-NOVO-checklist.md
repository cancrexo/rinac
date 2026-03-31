## PLAN-NOVO-checklist (RINAC)

IMPORTANTE: cada vez que se modifique el plan de trabajo, hay que actualizar también este archivo `PLAN-NOVO-checklist.md` y `GROK.md` para mantenerlos sincronizados.

### Pasos del desarrollo (checklist)

1. [x] Configuración inicial: `composer.json`, `rinac.php`, PSR-4, activación/desactivación, registro `rinac_reserva`, registro CPTs + menú “RINAC” + datos mínimos demo en activación
2. [x] Clase `AjaxHandler` centralizada + ejemplo de 3 endpoints (`rinac_get_availability`, `rinac_get_calendar_events`, `rinac_create_booking_request`)
3. [x] Pestañas `woocommerce-product-data` y settings para productos `rinac_reserva`
4. [x] Lógica de disponibilidad y cálculo de capacidad (clase `AvailabilityManager`)
5. [ ] Gestión de recursos y participantes (`ParticipantManager`, `ResourceManager`, `BookingManager`)
6. [ ] Sistema de pago con depósito + hooks de WooCommerce (`DepositManager`)
7. [ ] Calendario global admin + listado de reservas + botón “Importar datos de prueba”
8. [ ] Control de concurrencia y bloqueos temporales (quote/hold antes de confirmar reserva)
9. [ ] Frontend booking form + FullCalendar integración
10. [ ] Templates y overrides
11. [ ] Documentación completa (`README.md` + inline docs)

### Tareas activas por fase (detalle operativo)

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
- [ ] Añadir metadatos editables en `rinac_participant` (etiqueta pública, fracción, tipo/valor de precio, mínimos/máximos, activo, orden).
- [ ] Implementar validaciones de guardado (fracción > 0, `price_type` válido, `price_value` >= 0, coherencia min/max).
- [ ] Validar en `quote/create booking` que el tipo exista, esté activo y permitido para el producto.
- [ ] Aplicar límites por tipo y validación de capacidad global derivada de fracciones.
- [ ] Formalizar cálculo de precio por estrategia (`free`, `fixed`, extensible).
- [ ] Exponer en AJAX tipos permitidos con reglas (fracción/precio/límites/estado).
- [ ] Implementar UI frontend de cantidades por tipo y recálculo en vivo.
- [ ] Mostrar errores de negocio claros (no permitido, inactivo, límites, capacidad insuficiente).
- [ ] Añadir tests unitarios/integración para normalización, capacidad, precio y validaciones.

#### Fase Recursos
- [ ] Añadir metadatos editables en `rinac_resource` (tipo `addon/unit`, política de precio, valor, activo, orden y límites opcionales).
- [ ] Implementar validaciones de guardado (`resource_type` válido, `price_policy` válida, `price_value` >= 0 y coherencia de límites).
- [ ] Validar en `quote/create booking` que el recurso exista, esté activo y permitido para el producto.
- [ ] Aplicar límites por recurso y reglas por modo/perfil cuando aplique.
- [ ] Formalizar cálculo de precio por política (`none`, `fixed`, `per_person`, `per_day`, `per_night`).
- [ ] Exponer en AJAX recursos permitidos con reglas (tipo/política/precio/límites/estado).
- [ ] Implementar UI frontend de selección de recursos (simple/cantidad) con recálculo en vivo.
- [ ] Mostrar errores de negocio claros (no permitido, inactivo, límites, incompatibilidades).
- [ ] Añadir tests unitarios/integración para normalización, validaciones y cálculo por política.

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

