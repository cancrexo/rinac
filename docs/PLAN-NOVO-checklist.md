## PLAN-NOVO-checklist (RINAC)

IMPORTANTE: cada vez que se modifique el plan de trabajo, hay que actualizar también este archivo `PLAN-NOVO-checklist.md` y `GROK.md` para mantenerlos sincronizados.

### Pasos del desarrollo (checklist)

1. [x] Configuración inicial: `composer.json`, `rinac.php`, PSR-4, activación/desactivación, registro `rinac_reserva`, registro CPTs + menú “RINAC” + datos mínimos demo en activación
2. [x] Clase `AjaxHandler` centralizada + ejemplo de 3 endpoints (`rinac_get_availability`, `rinac_get_calendar_events`, `rinac_create_booking_request`)
3. [ ] Meta boxes y settings para productos `rinac_reserva`
4. [ ] Lógica de disponibilidad y cálculo de capacidad (clase `AvailabilityManager`)
5. [ ] Frontend booking form + FullCalendar integración
6. [ ] Gestión de recursos y participantes
7. [ ] Sistema de pago depósito + hooks de WooCommerce
8. [ ] Calendario global admin + listado de reservas + botón “Importar datos de prueba”
9. [ ] Templates y overrides
10. [ ] Documentación completa (`README.md` + inline docs)

### Reglas clave (recordatorio)
- `text_domain` cargado **exclusivamente** dentro de `init` (nunca antes).
- Callbacks pasados a `add_menu_page`/`add_submenu_page` y a `add_action`/`add_filter` deben ser `public` si se pasan como `array($obj, 'metodo')` (evitar `private/protected`).
- Si un lint se queja de `$_POST`/`$_GET`/`$_REQUEST`: no usar `$GLOBALS`, copiar primero a variables locales con validación de tipo (y opcionalmente `@noinspection`).
- Stubs de WordPress y WooCommerce: instalar como `require-dev` en `vendor/` (no versionar).

